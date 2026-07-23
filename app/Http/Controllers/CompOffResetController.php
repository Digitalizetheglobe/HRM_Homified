<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompOffLeave;
use App\Models\Employee;
use App\Models\AttendanceEmployee;
use App\Services\CompOffNormalizationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompOffResetController extends Controller
{
    public function reset()
    {
        if (\Auth::user()->type != 'company') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            DB::table('comp_off_leaves')->truncate();
            
            return redirect()->route('home')->with('success', __('All comp-off balances have been reset to 0 successfully.'));
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', __('Failed to reset comp-off balances: ' . $e->getMessage()));
        }
    }

    /**
     * Process Comp-Offs automatically for yesterday's date (called by cron via URL)
     * This is designed to be called automatically every night via Hostinger hPanel cron job
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processDaily(Request $request)
    {
        try {
            // Process yesterday's date (since this runs after midnight)
            $processDate = Carbon::yesterday();

            // Get all companies/creators
            $creators = \DB::table('users')
                ->whereIn('type', ['company', 'Director'])
                ->pluck('id');

            $totalCompOffsCreated = 0;
            $totalCompOffsSkipped = 0;
            $results = [];

            // Excluded employee IDs
            $excludedEmployeeIds = CompOffNormalizationService::EXCLUDED_EMPLOYEE_IDS;

            foreach ($creators as $creatorId) {
                // Get employees for this creator
                $employees = Employee::where('created_by', $creatorId)->get();

                foreach ($employees as $employee) {
                    // Skip excluded employees
                    if (in_array($employee->id, $excludedEmployeeIds)) {
                        continue;
                    }

                    // Get employee's week-off day
                    $weekOffDay = $employee->week_off_day;
                    
                    if (!$weekOffDay) {
                        continue;
                    }

                    // Check if yesterday was this employee's week-off day (case-insensitive)
                    $dayName = strtolower(trim($processDate->format('l')));
                    $weekOffDayNormalized = strtolower(trim((string) $weekOffDay));
                    
                    if ($dayName !== $weekOffDayNormalized) {
                        continue;
                    }

                    // Same rules as AttendanceEmployeeController week-off comp off: valid clock-in,
                    // status Present / Half Day / Single Punch In — no minimum hours, single punch allowed.
                    $attendance = AttendanceEmployee::where('employee_id', $employee->id)
                        ->where('date', $processDate->format('Y-m-d'))
                        ->first();

                    if (CompOffNormalizationService::attendanceQualifiesForEarnedCompOff($attendance)) {
                        // Check if comp-off already exists for this date
                        $existingCompOff = CompOffLeave::where([
                            'employees_id' => $employee->id,
                            'comp_off_date' => $processDate->format('Y-m-d')
                        ])->exists();

                        if (!$existingCompOff) {
                            try {
                                // Create comp-off record
                                CompOffLeave::create([
                                    'employees_id' => $employee->id,
                                    'comp_off_date' => $processDate->format('Y-m-d'),
                                    'comp_off_data' => 1.0,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                                
                                $totalCompOffsCreated++;
                                $results[] = "Created Comp-Off for {$employee->full_name} (ID: {$employee->id}) on {$processDate->format('Y-m-d')} ({$attendance->status})";
                            } catch (\Exception $e) {
                                $results[] = "Error for {$employee->full_name}: " . $e->getMessage();
                            }
                        } else {
                            $totalCompOffsSkipped++;
                        }
                    }
                }
            }

            $response = [
                'success' => true,
                'date' => $processDate->format('Y-m-d'),
                'created' => $totalCompOffsCreated,
                'skipped' => $totalCompOffsSkipped,
                'message' => "Comp-Off processing completed for {$processDate->format('Y-m-d')}. Created: {$totalCompOffsCreated}, Skipped: {$totalCompOffsSkipped}",
                'timestamp' => now()->toDateTimeString()
            ];

            // Return JSON response (works for both browser and cron)
            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }

    /**
     * Process Comp-Offs for employees who worked on their week-off days in a specific year
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processYear(Request $request)
    {
        if (\Auth::user()->type != 'company' && \Auth::user()->type != 'hr' && \Auth::user()->type != 'Director') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            // Get year from request (works for both GET and POST)
            // If accessed via GET URL, can use ?year=2026 in the URL
            // If no year provided, defaults to current year
            $year = $request->input('year', Carbon::now()->year);
            $startDate = Carbon::create($year, 1, 1)->startOfDay();
            $endDate = Carbon::create($year, 12, 31)->endOfDay();

            // Get all employees
            $employees = Employee::where('created_by', \Auth::user()->creatorId())->get();
            
            $compOffsCreated = 0;
            $compOffsSkipped = 0;
            $errors = [];
            $debugInfo = []; // For debugging specific employees

            // Excluded employee IDs
            $excludedEmployeeIds = CompOffNormalizationService::EXCLUDED_EMPLOYEE_IDS;

            foreach ($employees as $employee) {
                // Skip excluded employees
                if (in_array($employee->id, $excludedEmployeeIds)) {
                    continue;
                }

                // Get employee's week-off day
                $weekOffDay = $employee->week_off_day;
                
                if (!$weekOffDay) {
                    continue;
                }

                // Attendance on week off: Present, Half Day, or Single Punch with valid clock-in (no min. hours).
                $attendances = AttendanceEmployee::where('employee_id', $employee->id)
                    ->whereNotNull('clock_in')
                    ->where('clock_in', '!=', '00:00:00')
                    ->whereIn('status', [
                        AttendanceEmployee::STATUS_PRESENT,
                        AttendanceEmployee::STATUS_HALF_DAY,
                        AttendanceEmployee::STATUS_SINGLE_PUNCH,
                    ])
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->get();

                foreach ($attendances as $attendance) {
                    if (!CompOffNormalizationService::attendanceQualifiesForEarnedCompOff($attendance)) {
                        continue;
                    }

                    $attendanceDate = Carbon::parse($attendance->date);
                    $dayName = strtolower(trim($attendanceDate->format('l'))); // Full day name (monday, tuesday, etc.)
                    $weekOffDayNormalized = strtolower(trim((string) $weekOffDay));
                    
                    // Check if this was the employee's week-off day (case-insensitive comparison)
                    if ($dayName === $weekOffDayNormalized) {
                        // Check if comp-off already exists for this date
                        $existingCompOff = CompOffLeave::where([
                            'employees_id' => $employee->id,
                            'comp_off_date' => $attendanceDate->format('Y-m-d')
                        ])->exists();

                        if (!$existingCompOff) {
                            try {
                                // Create comp-off record
                                CompOffLeave::create([
                                    'employees_id' => $employee->id,
                                    'comp_off_date' => $attendanceDate->format('Y-m-d'),
                                    'comp_off_data' => 1.0, // Full day comp-off
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                                
                                $compOffsCreated++;
                                $debugInfo[] = "Created Comp-Off for Employee {$employee->id} ({$employee->full_name}) on {$attendanceDate->format('Y-m-d')} ({$dayName}, {$attendance->status})";
                            } catch (\Exception $e) {
                                $errors[] = "Error creating comp-off for employee {$employee->id} on {$attendanceDate->format('Y-m-d')}: " . $e->getMessage();
                            }
                        } else {
                            $compOffsSkipped++;
                            $debugInfo[] = "Skipped Comp-Off for Employee {$employee->id} ({$employee->full_name}) on {$attendanceDate->format('Y-m-d')} (already exists)";
                        }
                    }
                }
            }

            $message = "Comp-Off processing completed for year {$year}! ";
            $message .= "Created: {$compOffsCreated} Comp-Off(s). ";
            
            if ($compOffsSkipped > 0) {
                $message .= "Skipped: {$compOffsSkipped} (already exist). ";
            }
            
            if (!empty($errors)) {
                $message .= "Errors: " . count($errors) . ". ";
            }

            // Store debug info in session for detailed view (optional)
            if (!empty($debugInfo) && count($debugInfo) <= 100) {
                // Only store if not too many entries to avoid session size issues
                session()->flash('debug_info', $debugInfo);
            }
            
            // Add summary of what was found for employee 3 (Dnyaneshwar) if processing
            $employee3 = Employee::find(3);
            if ($employee3 && $employee3->created_by == \Auth::user()->creatorId()) {
                $emp3WeekOff = strtolower(trim((string) ($employee3->week_off_day ?? '')));
                $tuesdayAttendances = AttendanceEmployee::where('employee_id', 3)
                    ->whereNotNull('clock_in')
                    ->where('clock_in', '!=', '00:00:00')
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->get()
                    ->filter(function($att) use ($emp3WeekOff) {
                        $dayName = strtolower(trim(Carbon::parse($att->date)->format('l')));
                        return $dayName === $emp3WeekOff;
                    });
                
                $existingCompOffs = CompOffLeave::where('employees_id', 3)
                    ->whereBetween('comp_off_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->get();
                
                $dates = $tuesdayAttendances->map(function($att) {
                    return Carbon::parse($att->date)->format('Y-m-d');
                })->toArray();
                
                $compOffDates = $existingCompOffs->pluck('comp_off_date')->toArray();
                
                session()->flash('employee3_info', [
                    "Employee 3 (Dnyaneshwar): Found " . $tuesdayAttendances->count() . " " . ucfirst($emp3WeekOff) . " attendance(s) on: " . implode(', ', $dates),
                    "Existing Comp-Offs: " . $existingCompOffs->count() . " on: " . implode(', ', $compOffDates)
                ]);
            }

            return redirect()->route('leave.compoff.index')
                ->with('success', $message)
                ->with('errors', $errors);

        } catch (\Exception $e) {
            return redirect()->route('leave.compoff.index')
                ->with('error', __('Failed to process Comp-Offs: ' . $e->getMessage()));
        }
    }

}
