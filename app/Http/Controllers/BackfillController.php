<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\AttendanceEmployee;
use App\Models\CompOffLeave;
use Carbon\Carbon;

class BackfillController extends Controller
{
    public function run(Request $request)
    {
        // Get all employees
        $employees = Employee::all();
        
        $processed = 0;
        $errors = [];
        
        foreach ($employees as $employee) {
            // Exclude specific employees from comp-off
            $excludedEmployeeIds = [4, 13, 22, 23];
            if (in_array($employee->id, $excludedEmployeeIds)) {
                continue;
            }

            try {
                // Get all attendances where employee was present
                $attendances = AttendanceEmployee::where('employee_id', $employee->id)
                    ->where('status', 'Present')
                    ->get();
                
                foreach ($attendances as $attendance) {
                    $attendanceDate = Carbon::parse($attendance->date);
                    $dayName = $attendanceDate->format('l');
                    
                    // Check if this was the employee's week-off day
                    if ($employee->week_off_day === $dayName) {
                        // Check if comp-off already exists for this date
                        $existingCompOff = CompOffLeave::where('employees_id', $employee->id)
                            ->where('comp_off_date', $attendanceDate->format('Y-m-d'))
                            ->exists();
                            
                        if (!$existingCompOff) {
                            // Create comp-off record
                            CompOffLeave::create([
                                'employees_id' => $employee->id,
                                'comp_off_date' => $attendanceDate->format('Y-m-d'),
                                'comp_off_data' => 1.0, // Assuming full day comp-off
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                            
                            $processed++;
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Error processing employee {$employee->id}: " . $e->getMessage();
            }
        }
        
        return response()->json([
            'message' => 'Backfill completed successfully',
            'comp_offs_created' => $processed,
            'errors' => $errors
        ]);
    }
}