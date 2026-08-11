<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeSheet;
use App\Models\Employee;
use App\Models\User;
use App\Models\Utility;
use App\Mail\FollowUpReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestFollowUpEmail extends Command
{
    protected $signature = 'test:followup-email {timesheet_id}';
    protected $description = 'Test sending follow-up email for a specific timesheet';

    public function handle()
    {
        $timesheetId = $this->argument('timesheet_id');
        
        $this->info("Testing follow-up email for timesheet ID: {$timesheetId}");
        
        $timeSheet = TimeSheet::with(['employee', 'assignedEmployee', 'project'])->find($timesheetId);
        
        if (!$timeSheet) {
            $this->error("Timesheet not found!");
            return 1;
        }
        
        $this->info("Timesheet found: {$timeSheet->full_name}");
        $this->info("Follow-up date: {$timeSheet->follow_up_date}");
        
        // Determine employee
        $employeeToNotify = null;
        
        if ($timeSheet->assigned_to) {
            $assignedUser = User::find($timeSheet->assigned_to);
            if ($assignedUser) {
                $employeeToNotify = Employee::where('user_id', $assignedUser->id)->first();
                $this->info("Found assigned employee: " . ($employeeToNotify ? $employeeToNotify->name : 'Not found'));
            }
        }
        
        if (!$employeeToNotify) {
            $originalUser = User::find($timeSheet->employee_id);
            if ($originalUser) {
                $employeeToNotify = Employee::where('user_id', $originalUser->id)->first();
                $this->info("Found original employee: " . ($employeeToNotify ? $employeeToNotify->name : 'Not found'));
            }
        }
        
        if (!$employeeToNotify) {
            $this->error("No employee found!");
            return 1;
        }
        
        $this->info("Employee: {$employeeToNotify->name}");
        $this->info("Email: {$employeeToNotify->email}");
        
        if (empty($employeeToNotify->email)) {
            $this->error("Employee email is empty!");
            return 1;
        }
        
        // Get company ID
        $companyId = $timeSheet->employee_id ? User::find($timeSheet->employee_id)->creatorId() : 1;
        $this->info("Company ID: {$companyId}");
        
        // Load mail settings
        $this->info("Loading mail settings...");
        try {
            $settings = Utility::getSMTPDetails($companyId);
            $this->info("Mail settings loaded:");
            $this->line("  Driver: " . ($settings['mail_driver'] ?? 'N/A'));
            $this->line("  Host: " . ($settings['mail_host'] ?? 'N/A'));
            $this->line("  Port: " . ($settings['mail_port'] ?? 'N/A'));
            $this->line("  From: " . ($settings['mail_from_address'] ?? 'N/A'));
        } catch (\Exception $e) {
            $this->error("Error loading mail settings: " . $e->getMessage());
            return 1;
        }
        
        // Get last remark
        $lastRemark = "Test remark";
        if (!empty($timeSheet->feedback_information)) {
            $feedbacks = json_decode($timeSheet->feedback_information, true);
            if (is_array($feedbacks) && !empty($feedbacks)) {
                $lastFeedback = end($feedbacks);
                if (isset($lastFeedback['description'])) {
                    $lastRemark = trim($lastFeedback['description']);
                }
            }
        }
        if (empty($lastRemark) && !empty($timeSheet->executive_remark)) {
            $lastRemark = trim($timeSheet->executive_remark);
        }
        
        $this->info("Last remark: " . substr($lastRemark, 0, 50) . '...');
        
        // Send email
        $this->info("Attempting to send email...");
        try {
            Mail::to($employeeToNotify->email)->send(new FollowUpReminder($timeSheet, $lastRemark));
            $this->info("✅ Email sent successfully!");
            $this->info("Check inbox: {$employeeToNotify->email}");
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error sending email: " . $e->getMessage());
            $this->error("File: " . $e->getFile());
            $this->error("Line: " . $e->getLine());
            return 1;
        }
    }
}










