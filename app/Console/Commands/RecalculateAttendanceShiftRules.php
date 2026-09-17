<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\AttendanceRuleService;
use Illuminate\Console\Command;

class RecalculateAttendanceShiftRules extends Command
{
    protected $signature = 'attendance:recalculate-shift-rules {--employee= : Recalculate a single employee ID}';

    protected $description = 'Recalculate month-wise late marks, late-cycle half days, and attendance status reasons';

    public function handle(AttendanceRuleService $service): int
    {
        $employeeId = $this->option('employee');
        $query = Employee::query()->orderBy('id');
        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $count = 0;
        $query->chunkById(50, function ($employees) use ($service, &$count) {
            foreach ($employees as $employee) {
                $service->recalculateFrom((int) $employee->id, '1970-01-01');
                $count++;
            }
        });

        $this->info("Recalculated attendance rules for {$count} employee(s).");

        return 0;
    }
}
