<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\AttendanceEmployee;
use App\Models\CompOffLeave;
use App\Services\CompOffNormalizationService;
use Carbon\Carbon;

class ProcessCompOffEarnings extends Command
{
    protected $signature = 'comp-off:process {--date= : Specific date to process (Y-m-d)}';
    protected $description = 'Process comp-off earnings for employees who worked on their week-off days';

    public function handle()
    {
        // Default to processing yesterday's date (since it runs at midnight)
        $processDate = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $this->info("Processing comp-off earnings for: " . $processDate->format('Y-m-d'));

        // Get all employees
        $employees = Employee::all();
        $compOffsCreated = 0;

        foreach ($employees as $employee) {
            $this->info("Checking employee ID: " . $employee->id);

            // Exclude specific employees from comp-off
            $excludedEmployeeIds = CompOffNormalizationService::EXCLUDED_EMPLOYEE_IDS;
            if (in_array($employee->id, $excludedEmployeeIds)) {
                $this->info("⚠️ Skipping excluded employee ID: {$employee->id}");
                continue;
            }

            // Get employee's week-off day
            $weekOffDay = $employee->week_off_day;
            
            if (!$weekOffDay) {
                $this->info("❌ No week-off day configured for employee ID: {$employee->id}");
                continue;
            }

            // Check if the processing date was this employee's week-off day (case-insensitive)
            $dayName = strtolower(trim($processDate->format('l')));
            $weekOffDayNormalized = strtolower(trim((string) $weekOffDay));
            
            if ($dayName !== $weekOffDayNormalized) {
                $this->info("❌ {$processDate->format('Y-m-d')} is not {$employee->name}'s week-off day ({$weekOffDay})");
                continue;
            }

            $attendance = AttendanceEmployee::where('employee_id', $employee->id)
                ->where('date', $processDate->format('Y-m-d'))
                ->first();

            if (CompOffNormalizationService::attendanceQualifiesForEarnedCompOff($attendance)) {
                $this->info("✅ Employee worked on week-off day.");

                // Check if comp-off already exists for this date
                $existingCompOff = CompOffLeave::where([
                    'employees_id' => $employee->id,
                    'comp_off_date' => $processDate->format('Y-m-d')
                ])->exists();

                if (!$existingCompOff) {
                    // Grant comp-off
                    CompOffLeave::create([
                        'employees_id' => $employee->id,
                        'comp_off_date' => $processDate->format('Y-m-d'),
                        'comp_off_data' => 1.0, // Full day comp-off
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $compOffsCreated++;
                    $this->info("✅ Comp-off granted for employee ID: {$employee->id} for date: {$processDate->format('Y-m-d')}");
                } else {
                    $this->info("⚠️ Comp-off already exists for this date.");
                }
            } else {
                $this->info("❌ Employee did not work on week-off day or was absent.");
            }
        }

        $this->info("Comp-off processing completed! {$compOffsCreated} comp-offs created.");
    }
}