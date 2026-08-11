<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompOffLeave;
use App\Models\Employee;
use App\Models\AttendanceEmployee;
use Carbon\Carbon;

class CheckEmployeeCompOff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comp-off:check {employee_id : Employee ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Comp-Off records for a specific employee and verify against attendance records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeId = $this->argument('employee_id');
        
        $employee = Employee::find($employeeId);
        
        if (!$employee) {
            $this->error("❌ Employee with ID {$employeeId} not found.");
            return 1;
        }

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("  Comp-Off Check for: {$employee->full_name} (ID: {$employeeId})");
        $this->info("═══════════════════════════════════════════════════════");
        $this->newLine();

        // Get employee's week-off day
        $weekOffDay = $employee->week_off_day;
        $this->info("📅 Week-Off Day: {$weekOffDay}");
        $this->newLine();

        // Get all Comp-Off records for this employee
        $compOffRecords = CompOffLeave::where('employees_id', $employeeId)
            ->orderBy('comp_off_date', 'asc')
            ->get();

        if ($compOffRecords->isEmpty()) {
            $this->info("ℹ️  No Comp-Off records found for this employee.");
            return 0;
        }

        $this->info("📊 Comp-Off Records Found: {$compOffRecords->count()}");
        $this->newLine();

        $this->info("┌──────────────┬──────────────┬─────────────────────┬─────────────┐");
        $this->info("│ Comp-Off ID  │ Date         │ Day of Week         │ Status      │");
        $this->info("├──────────────┼──────────────┼─────────────────────┼─────────────┤");

        foreach ($compOffRecords as $compOff) {
            $date = Carbon::parse($compOff->comp_off_date);
            $dayName = $date->format('l');
            $dateStr = $date->format('Y-m-d');
            
            // Check if this was actually the employee's week-off day
            $isWeekOff = (strtolower($dayName) === strtolower($weekOffDay));
            
            // Check if employee was present on this date
            $attendance = AttendanceEmployee::where([
                'employee_id' => $employeeId,
                'date' => $dateStr,
                'status' => 'Present'
            ])->first();

            $status = '';
            if (!$isWeekOff) {
                $status = '⚠️  NOT Week-Off';
            } elseif (!$attendance) {
                $status = '❌ No Attendance';
            } else {
                $status = '✅ Valid';
            }

            $this->info(sprintf(
                "│ %-12s │ %-12s │ %-19s │ %-11s │",
                $compOff->id,
                $dateStr,
                $dayName,
                $status
            ));
        }

        $this->info("└──────────────┴──────────────┴─────────────────────┴─────────────┘");
        $this->newLine();

        // Summary
        $this->info("📋 Summary:");
        $this->info("   Total Comp-Off Records: {$compOffRecords->count()}");
        
        $validCount = 0;
        $invalidCount = 0;
        
        foreach ($compOffRecords as $compOff) {
            $date = Carbon::parse($compOff->comp_off_date);
            $dayName = strtolower($date->format('l'));
            $isWeekOff = ($dayName === strtolower($weekOffDay));
            
            $attendance = AttendanceEmployee::where([
                'employee_id' => $employeeId,
                'date' => $compOff->comp_off_date,
                'status' => 'Present'
            ])->first();
            
            if ($isWeekOff && $attendance) {
                $validCount++;
            } else {
                $invalidCount++;
            }
        }
        
        $this->info("   ✅ Valid Comp-Offs: {$validCount}");
        if ($invalidCount > 0) {
            $this->warn("   ⚠️  Invalid Comp-Offs: {$invalidCount}");
        }
        $this->newLine();

        // Check for Tuesdays with Present attendance but no Comp-Off
        if (strtolower($weekOffDay) === 'tuesday') {
            $this->info("🔍 Checking for Tuesdays with Present attendance but no Comp-Off:");
            
            $tuesdayAttendances = AttendanceEmployee::where('employee_id', $employeeId)
                ->where('status', 'Present')
                ->get()
                ->filter(function($attendance) {
                    $date = Carbon::parse($attendance->date);
                    return strtolower($date->format('l')) === 'tuesday';
                });

            $missingCompOffs = [];
            foreach ($tuesdayAttendances as $attendance) {
                $dateStr = Carbon::parse($attendance->date)->format('Y-m-d');
                $hasCompOff = CompOffLeave::where([
                    'employees_id' => $employeeId,
                    'comp_off_date' => $dateStr
                ])->exists();
                
                if (!$hasCompOff) {
                    $missingCompOffs[] = $dateStr;
                }
            }

            if (!empty($missingCompOffs)) {
                $this->warn("   ⚠️  Found Tuesdays with Present attendance but no Comp-Off:");
                foreach ($missingCompOffs as $date) {
                    $this->line("      • {$date}");
                }
            } else {
                $this->info("   ✅ All Tuesdays with Present attendance have Comp-Off records.");
            }
        }

        return 0;
    }
}
