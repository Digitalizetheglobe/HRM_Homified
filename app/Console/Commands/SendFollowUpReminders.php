<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeSheet;
use App\Models\Employee;
use App\Models\User;
use App\Models\Utility;
use App\Mail\FollowUpReminder;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendFollowUpReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send follow-up reminder emails to employees for clients with follow-up dates scheduled for today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today()->toDateString();
        
        $this->info("Checking for follow-ups scheduled for: {$today}");
        
        // Exact string match for the JSON date format: "followup_date": "YYYY-MM-DD"
        // This avoids complex JSON queries and works on most DBs given the consistent format
        $jsonDateSearch = '"followup_date": "' . $today . '"';
        // Also try with no space after colon just in case
        $jsonDateSearchNoSpace = '"followup_date":"' . $today . '"';

        // Get all timesheets where:
        // 1. Main follow_up_date is today
        // 2. OR feedback_information contains today's date in followup_date field
        $timeSheets = TimeSheet::query()
            ->where(function($query) use ($today, $jsonDateSearch, $jsonDateSearchNoSpace) {
                $query->whereDate('follow_up_date', $today)
                      ->orWhere('feedback_information', 'like', "%{$jsonDateSearch}%")
                      ->orWhere('feedback_information', 'like', "%{$jsonDateSearchNoSpace}%");
            })
            ->with(['employee', 'assignedEmployee', 'project'])
            ->get();
        
        if ($timeSheets->isEmpty()) {
            $this->info('No follow-ups scheduled for today.');
            return 0;
        }
        
        $sentCount = 0;
        $errorCount = 0;
        
        foreach ($timeSheets as $timeSheet) {
            try {
                // Determine which employee to send email to
                // Priority: assigned_to employee > original employee (employee_id)
                $employeeToNotify = null;
                $employeeEmail = null;
                
                if ($timeSheet->assigned_to) {
                    // Get employee from assigned_to (user_id)
                    $assignedUser = User::find($timeSheet->assigned_to);
                    if ($assignedUser) {
                        $employeeToNotify = Employee::where('user_id', $assignedUser->id)->first();
                    }
                }
                
                // If no assigned employee, use the original creator
                if (!$employeeToNotify) {
                    $originalUser = User::find($timeSheet->employee_id);
                    if ($originalUser) {
                        $employeeToNotify = Employee::where('user_id', $originalUser->id)->first();
                    }
                }
                
                if (!$employeeToNotify || empty($employeeToNotify->email)) {
                    $this->warn("Skipping timesheet ID {$timeSheet->id}: No valid employee email found");
                    $errorCount++;
                    continue;
                }
                
                $employeeEmail = $employeeToNotify->email;
                
                // Get the last remark
                $lastRemark = $this->getLastRemark($timeSheet);
                
                // Configure mail settings from database
                // Get company ID from the timesheet's creator
                $timesheetCreator = User::find($timeSheet->employee_id);
                $companyId = $timesheetCreator ? $timesheetCreator->creatorId() : 1;
                Utility::getSMTPDetails($companyId);
                
                // Send email
                Mail::to($employeeEmail)->send(new FollowUpReminder($timeSheet, $lastRemark));
                
                // Small delay to avoid overwhelming the mail server
                usleep(100000); // 0.1 second delay
                
                $sentCount++;
                $this->info("Sent follow-up reminder to {$employeeEmail} for client: {$timeSheet->full_name}");
                
                Log::info("Follow-up reminder sent", [
                    'timesheet_id' => $timeSheet->id,
                    'client_name' => $timeSheet->full_name,
                    'employee_email' => $employeeEmail,
                    'follow_up_date' => $today,
                    'trigger' => 'Scheduled Command'
                ]);
                
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error sending follow-up reminder for timesheet ID {$timeSheet->id}: " . $e->getMessage());
                Log::error("Follow-up reminder error", [
                    'timesheet_id' => $timeSheet->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        $this->info("Follow-up reminders sent: {$sentCount}, Errors: {$errorCount}");
        return 0;
    }
    
    /**
     * Get the last remark for a timesheet
     * Priority: Last feedback > Executive remark
     */
    private function getLastRemark(TimeSheet $timeSheet)
    {
        // Check if there are feedbacks
        if (!empty($timeSheet->feedback_information)) {
            $feedbacks = json_decode($timeSheet->feedback_information, true);
            if (is_array($feedbacks) && !empty($feedbacks)) {
                // Get the last feedback (most recent)
                $lastFeedback = end($feedbacks);
                if (isset($lastFeedback['description']) && !empty(trim($lastFeedback['description']))) {
                    return trim($lastFeedback['description']);
                }
            }
        }
        
        // Fallback to executive remark
        if (!empty($timeSheet->executive_remark)) {
            return trim($timeSheet->executive_remark);
        }
        
        return 'No remarks available.';
    }
}
