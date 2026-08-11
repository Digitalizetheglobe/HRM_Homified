<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use Carbon\Carbon;

class CleanupInvalidLeaveBalances extends Command
{
    protected $signature = 'leaves:cleanup-invalid-balances';
    protected $description = 'Cleanup leave balances assigned before the 30-day eligibility period';

    public function handle()
    {
        $this->info('Cleaning up invalid leave balances...');
        
        $employees = Employee::whereNotNull('company_doj')->get();
        $deletedCount = 0;
        $updatedCount = 0;
        
        foreach ($employees as $employee) {
            $eligibilityDate = Carbon::parse($employee->company_doj)->addDays(30);
            
            // Find all balances where the month/year is BEFORE the eligibility month/year
            $invalidBalances = EmployeeLeaveBalance::where('employee_id', $employee->id)
                ->where(function($query) use ($eligibilityDate) {
                    $query->where('year', '<', $eligibilityDate->year)
                          ->orWhere(function($q) use ($eligibilityDate) {
                              $q->where('year', $eligibilityDate->year)
                                ->where('month', '<', $eligibilityDate->month);
                          });
                })->get();
                
            foreach ($invalidBalances as $balance) {
                if ($balance->used_days > 0) {
                    $balance->allocated_days = 0;
                    $balance->carry_forward_days = 0;
                    $balance->save();
                    $updatedCount++;
                    $this->info("Zeroed allocation for Employee {$employee->id} - Month: {$balance->month}, Year: {$balance->year} (Has {$balance->used_days} used days)");
                } else {
                    $balance->delete();
                    $deletedCount++;
                    $this->info("Deleted balance for Employee {$employee->id} - Month: {$balance->month}, Year: {$balance->year}");
                }
            }
        }
        
        $this->info("Cleanup completed. Deleted {$deletedCount} records, Updated {$updatedCount} records.");
    }
}
