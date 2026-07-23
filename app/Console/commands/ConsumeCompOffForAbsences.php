<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\AttendanceEmployee;
use App\Models\LeaveType;
use App\Models\Leave as LocalLeave;
use Carbon\Carbon;

class ConsumeCompOffForAbsences extends Command
{
    protected $signature = 'comp-off:consume-absences {--start-date= : Start date (Y-m-d)} {--end-date= : End date (Y-m-d)}';
    protected $description = 'Automatically consume Comp-Off balances for absent employees';

    public function handle()
    {
        $startDateStr = $this->option('start-date');
        $endDateStr = $this->option('end-date');

        // If no dates provided, run for yesterday by default
        if (!$startDateStr && !$endDateStr) {
            $startDate = Carbon::yesterday();
            $endDate = Carbon::yesterday();
        } else {
            $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::today()->startOfMonth();
            $endDate = $endDateStr ? Carbon::parse($endDateStr) : Carbon::today();
        }

        $this->info("Processing comp-off consumption from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        $employees = Employee::all();
        $totalConsumed = 0;

        foreach ($employees as $employee) {
            // Find the Comp-Off LeaveType for this employee's creator
            $compOffLeaveTypeId = LeaveType::where('title', 'Comp-Off')
                ->where('created_by', $employee->created_by)
                ->value('id');

            // Fallback just in case
            if (!$compOffLeaveTypeId) {
                $compOffLeaveTypeId = LeaveType::where('title', 'Comp-Off')->value('id');
            }

            if (!$compOffLeaveTypeId) {
                $this->error("Comp-Off leave type not found for Employee ID: {$employee->id}");
                continue;
            }

            // Loop through each day chronologically
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('Y-m-d');

                // Find absence record for this date
                $attendance = AttendanceEmployee::where('employee_id', $employee->id)
                    ->where('date', $dateString)
                    ->where('status', AttendanceEmployee::STATUS_ABSENT)
                    ->first();

                if ($attendance) {
                    $balance = $employee->compOffBalance();
                    if ($balance >= 1) {
                        // Avoid duplicates if an approved leave already exists
                        $existingLeave = LocalLeave::where('employee_id', $employee->id)
                            ->where('start_date', '<=', $dateString)
                            ->where('end_date', '>=', $dateString)
                            ->where('status', 'Approved')
                            ->exists();

                        if (!$existingLeave) {
                            // Deduct 1 comp-off by creating an approved leave
                            LocalLeave::create([
                                'employee_id' => $employee->id,
                                'leave_type_id' => $compOffLeaveTypeId,
                                'applied_on' => now()->toDateString(),
                                'start_date' => $dateString,
                                'end_date' => $dateString,
                                'total_leave_days' => '1',
                                'leave_duration_type' => 'full_day',
                                'leave_reason' => 'Auto-consumed for Absence',
                                'status' => 'Approved',
                                'company_approved' => 1,
                                'created_by' => $employee->created_by ?? 1,
                            ]);

                            // Update attendance status to reflect Comp Off
                            $attendance->update([
                                'status' => 'Comp Off'
                            ]);

                            $totalConsumed++;
                            $this->info("✅ Consumed 1 Comp-Off for Employee ID {$employee->id} on {$dateString}");
                        }
                    }
                }
                
                $currentDate->addDay();
            }
        }

        $this->info("Process completed! Total Comp-Offs consumed: {$totalConsumed}");
    }
}
