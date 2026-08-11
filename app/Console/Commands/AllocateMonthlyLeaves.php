<?php
// app/Console/Commands/AllocateMonthlyLeaves.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\EmployeeLeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class AllocateMonthlyLeaves extends Command
{
    protected $signature = 'leaves:allocate-monthly';
    protected $description = 'Allocate 1.5 leaves monthly to all employees (Earned Leave) and reset carry forward at year end';

    public function handle()
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        
        // Get all employees, grouped by creator_id to handle multi-tenant scenarios
        $employees = Employee::whereNotNull('created_by')->get();
        
        if ($employees->count() == 0) {
            $this->warn('No employees found.');
            return;
        }
        
        $this->info("Processing {$employees->count()} employees...");
        
        foreach ($employees as $employee) {
            // Check eligibility (30 days from DOJ)
            if (!empty($employee->company_doj)) {
                $eligibilityDate = Carbon::parse($employee->company_doj)->addDays(30);
                if ($now->lt($eligibilityDate)) {
                    continue; // Skip this employee, not eligible yet
                }
            }

            // Get or create leave types for this employee's company
            $earnedLeaveType = $this->getOrCreateLeaveTypeForCompany('Earned Leave', 1.5, $employee->created_by);
            $sickLeaveType = $this->getOrCreateLeaveTypeForCompany('Sick Leave', 0.0, $employee->created_by);
            
            // Process Earned Leave (1.5 days per month)
            $this->allocateLeaveForEmployee($employee, $earnedLeaveType, 1.5, $year, $month, $now);
            
            // Process Sick Leave (0 days per month)
            $this->allocateLeaveForEmployee($employee, $sickLeaveType, 0.0, $year, $month, $now);
        }
        
        $this->info('Monthly leaves allocated successfully.');
    }
    
    private function getOrCreateLeaveTypeForCompany($title, $defaultDays, $creatorId)
    {
        // Try to find leave type for this company
        $leaveType = LeaveType::where('title', $title)
            ->where('created_by', $creatorId)
            ->first();
        
        if (!$leaveType) {
            $leaveType = LeaveType::create([
                'title' => $title,
                'days' => $defaultDays,
                'created_by' => $creatorId,
            ]);
            
            $this->info("Created leave type: {$title} for company {$creatorId}");
        }
        
        return $leaveType;
    }
    
    private function allocateLeaveForEmployee($employee, $leaveType, $allocatedDays, $year, $month, $now)
    {
        // Get previous month's balance for carry forward (handles year transition)
        $previousMonth = $now->copy()->subMonth();
        $prevBalance = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $previousMonth->year)
            ->where('month', $previousMonth->month)
            ->first();
        
        // Calculate carry forward: unused days from previous month
        $carryForward = 0;
        if ($prevBalance) {
            $availableDays = ($prevBalance->allocated_days + $prevBalance->carry_forward_days) - $prevBalance->used_days;
            $carryForward = max(0, $availableDays);
            
            // Reset carry forward if year changes (all leaves vanish at end of year)
            if ($previousMonth->year != $year) {
                $this->info("Year transition carry forward reset to 0 for {$employee->full_name}. Unused {$carryForward} days have vanished.");
                $carryForward = 0;
            }
        }
        
        // Check if balance already exists for this month
        $existingBalance = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        
        if ($existingBalance) {
            // Update existing balance
            $existingBalance->allocated_days = $allocatedDays;
            $existingBalance->carry_forward_days = $carryForward;
            $existingBalance->save();
        } else {
            // Create new monthly allocation
            EmployeeLeaveBalance::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'month' => $month,
                'allocated_days' => $allocatedDays,
                'used_days' => 0,
                'carry_forward_days' => $carryForward
            ]);
        }
    }
}