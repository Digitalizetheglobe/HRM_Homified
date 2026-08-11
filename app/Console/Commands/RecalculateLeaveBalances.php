<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use Carbon\Carbon;

class RecalculateLeaveBalances extends Command
{
    protected $signature = 'leaves:recalculate-balances';
    protected $description = 'Recalculate leave balances based on the 30-day eligibility rule from joining date';

    public function handle()
    {
        $this->info('Starting recalculation of leave balances...');
        
        $employees = Employee::whereNotNull('company_doj')->get();
        $updatedCount = 0;
        
        foreach ($employees as $employee) {
            $eligibilityDate = Carbon::parse($employee->company_doj)->addDays(30);
            $eligibilityMonthStart = Carbon::create($eligibilityDate->year, $eligibilityDate->month, 1);
            
            // Get distinct leave type IDs for this employee
            $leaveTypeIds = EmployeeLeaveBalance::where('employee_id', $employee->id)
                ->pluck('leave_type_id')
                ->unique();
                
            foreach ($leaveTypeIds as $typeId) {
                // Get balances ordered chronologically
                $balances = EmployeeLeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $typeId)
                    ->orderBy('year', 'asc')
                    ->orderBy('month', 'asc')
                    ->get();
                    
                $carryForward = 0;
                
                foreach ($balances as $balance) {
                    $balanceDate = Carbon::create($balance->year, $balance->month, 1);
                    
                    // If the balance month is before they were eligible, allocate 0
                    if ($balanceDate->lt($eligibilityMonthStart)) {
                        $allocated = 0;
                    } else {
                        // Assuming standard policy of 1 leave per type per month
                        $allocated = 1.0; 
                    }
                    
                    // Update record
                    $balance->allocated_days = $allocated;
                    $balance->carry_forward_days = $carryForward;
                    $balance->save();
                    
                    // Calculate next month's carry forward
                    $available = ($allocated + $carryForward) - $balance->used_days;
                    $carryForward = max(0, $available);
                    
                    $updatedCount++;
                }
            }
        }
        
        $this->info("Recalculation completed successfully. Updated {$updatedCount} balance records.");
    }
}
