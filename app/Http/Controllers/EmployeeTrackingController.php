<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLocationLog;
use App\Models\AttendanceEmployee;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeTrackingController extends Controller
{
    /**
     * Display the tracking dashboard (Admin Only).
     */
    public function index(Request $request)
    {
        // Restrict to admins and company users only
        if (Auth::user()->type !== 'company' && Auth::user()->type !== 'hr' && !\Auth::user()->can('attendance.employee_tracking.view.all')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Get list of active branches and departments for filters
        $branches = Branch::where('created_by', Auth::user()->creatorId())->pluck('name', 'id')->toArray();
        $departments = Department::where('created_by', Auth::user()->creatorId())->pluck('name', 'id')->toArray();

        // Get list of active employees
        $employees = Employee::where('created_by', Auth::user()->creatorId())
            ->whereHas('user', function ($query) {
                $query->where('type', 'employee');
            })
            ->with('user')
            ->get();

        return view('employee.tracking', compact('branches', 'departments', 'employees'));
    }

    /**
     * API endpoint for receiving location pings from background script or admin simulation.
     */
    public function pingLocation(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 401);
        }

        $employee = null;

        if ($user->type === 'employee') {
            $employee = $user->employee;
        } else if (($user->type === 'company' || $user->type === 'hr' || $user->can('attendance.employee_tracking.view.all')) && $request->has('employee_id')) {
            $employee = Employee::find($request->employee_id);
        }

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found or unauthorized.'
            ], 404);
        }

        // Validate Coordinates
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // Check if the employee is currently clocked in today (skip strict clock-in check for admin manual pings)
        $today = Carbon::today()->toDateString();
        $attendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        $isClockedIn = false;

        if ($attendance) {
            $slot1Active = !empty($attendance->clock_in) && 
                           (empty($attendance->clock_out) || $attendance->clock_out === '00:00:00');
            
            $slot2Active = !empty($attendance->clock_in_2) && $attendance->clock_in_2 !== '00:00:00' && 
                           (empty($attendance->clock_out_2) || $attendance->clock_out_2 === '00:00:00');

            if ($slot1Active || $slot2Active) {
                $isClockedIn = true;
            }
        }

        if (!$isClockedIn && $user->type === 'employee') {
            return response()->json([
                'success' => false,
                'message' => 'Location tracking is inactive. You must be punched in.'
            ], 200);
        }

        // Log the coordinates
        $log = EmployeeLocationLog::create([
            'employee_id' => $employee->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'pinged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location logged successfully.',
            'log' => $log
        ]);
    }

    /**
     * Simulation endpoint for testing live movement / route updates.
     */
    public function simulatePing(Request $request)
    {
        if (Auth::user()->type !== 'company' && Auth::user()->type !== 'hr' && !\Auth::user()->can('attendance.employee_tracking.view.all')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $log = EmployeeLocationLog::create([
            'employee_id' => $request->employee_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'pinged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test location ping logged successfully.',
            'log' => $log
        ]);
    }

    /**
     * API/Fetch endpoint to retrieve tracking data for the admin dashboard.
     */
    public function getTrackingData(Request $request)
    {
        if (Auth::user()->type !== 'company' && Auth::user()->type !== 'hr' && !\Auth::user()->can('attendance.employee_tracking.view.all')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required',
        ]);

        $employeeId = $request->employee_id;
        try {
            $date = Carbon::parse($request->date)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format.'
            ], 422);
        }

        $employee = Employee::find($employeeId);

        // Check if employee is clocked in on this date
        $isClockedIn = false;
        $attendance = AttendanceEmployee::where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();

        if ($attendance) {
            $slot1Active = !empty($attendance->clock_in) && 
                           (empty($attendance->clock_out) || $attendance->clock_out === '00:00:00');
            $slot2Active = !empty($attendance->clock_in_2) && $attendance->clock_in_2 !== '00:00:00' && 
                           (empty($attendance->clock_out_2) || $attendance->clock_out_2 === '00:00:00');
            if ($slot1Active || $slot2Active) {
                $isClockedIn = true;
            }
        }

        // Fetch location logs for the date ordered by pinged_at asc
        $logs = EmployeeLocationLog::where('employee_id', $employeeId)
            ->whereDate('pinged_at', $date)
            ->orderBy('pinged_at', 'asc')
            ->get();

        $formattedLogs = [];
        foreach ($logs as $index => $log) {
            $formattedLogs[] = [
                'id' => $log->id,
                'lat' => (float)$log->latitude,
                'lng' => (float)$log->longitude,
                'time' => $log->pinged_at->format('h:i A'),
                'full_time' => $log->pinged_at->format('M d, Y h:i:s A'),
                'timestamp' => $log->pinged_at->timestamp,
                'diff' => $log->pinged_at->diffForHumans(),
                'type' => 'waypoint',
            ];
        }

        // Prepend clock-in location if available and missing from logs
        if ($attendance && !empty($attendance->clock_in_latitude) && !empty($attendance->clock_in_longitude)) {
            $clockInTimeFormatted = Carbon::parse($attendance->clock_in)->format('h:i A');
            $clockInFullTime = Carbon::parse($date . ' ' . $attendance->clock_in)->format('M d, Y h:i:s A');
            $clockInTimestamp = Carbon::parse($date . ' ' . $attendance->clock_in)->timestamp;
            $hasStart = false;
            if (count($formattedLogs) > 0) {
                $dist = abs((float)$formattedLogs[0]['lat'] - (float)$attendance->clock_in_latitude) +
                        abs((float)$formattedLogs[0]['lng'] - (float)$attendance->clock_in_longitude);
                if ($dist < 0.0001) {
                    $hasStart = true;
                    $formattedLogs[0]['type'] = 'start';
                }
            }
            if (!$hasStart) {
                array_unshift($formattedLogs, [
                    'id' => 'clock-in',
                    'lat' => (float)$attendance->clock_in_latitude,
                    'lng' => (float)$attendance->clock_in_longitude,
                    'time' => $clockInTimeFormatted,
                    'full_time' => $clockInFullTime,
                    'timestamp' => $clockInTimestamp,
                    'diff' => Carbon::parse($date . ' ' . $attendance->clock_in)->diffForHumans(),
                    'type' => 'start',
                ]);
            }
        }

        // Append clock-out location if available and missing from logs
        if ($attendance && !empty($attendance->clock_out) && $attendance->clock_out !== '00:00:00' &&
            !empty($attendance->clock_out_latitude) && !empty($attendance->clock_out_longitude)) {
            $clockOutTimeFormatted = Carbon::parse($attendance->clock_out)->format('h:i A');
            $clockOutFullTime = Carbon::parse($date . ' ' . $attendance->clock_out)->format('M d, Y h:i:s A');
            $clockOutTimestamp = Carbon::parse($date . ' ' . $attendance->clock_out)->timestamp;
            $hasEnd = false;
            $count = count($formattedLogs);
            if ($count > 0) {
                $dist = abs((float)$formattedLogs[$count - 1]['lat'] - (float)$attendance->clock_out_latitude) +
                        abs((float)$formattedLogs[$count - 1]['lng'] - (float)$attendance->clock_out_longitude);
                if ($dist < 0.0001) {
                    $hasEnd = true;
                    $formattedLogs[$count - 1]['type'] = 'end';
                }
            }
            if (!$hasEnd) {
                $formattedLogs[] = [
                    'id' => 'clock-out',
                    'lat' => (float)$attendance->clock_out_latitude,
                    'lng' => (float)$attendance->clock_out_longitude,
                    'time' => $clockOutTimeFormatted,
                    'full_time' => $clockOutFullTime,
                    'timestamp' => $clockOutTimestamp,
                    'diff' => Carbon::parse($date . ' ' . $attendance->clock_out)->diffForHumans(),
                    'type' => 'end',
                ];
            }
        }

        // Re-index point numbers
        foreach ($formattedLogs as $idx => &$item) {
            $item['index'] = $idx + 1;
        }
        unset($item);

        // Calculate cumulative distance in KM via Haversine formula
        $totalDistanceMeters = 0;
        for ($i = 1; $i < count($formattedLogs); $i++) {
            $lat1 = deg2rad($formattedLogs[$i-1]['lat']);
            $lng1 = deg2rad($formattedLogs[$i-1]['lng']);
            $lat2 = deg2rad($formattedLogs[$i]['lat']);
            $lng2 = deg2rad($formattedLogs[$i]['lng']);

            $dlat = $lat2 - $lat1;
            $dlng = $lng2 - $lng1;

            $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $totalDistanceMeters += 6371000 * $c;
        }

        $totalDistanceKm = round($totalDistanceMeters / 1000, 2);

        $startLocation = count($formattedLogs) > 0 ? $formattedLogs[0] : null;
        $currentLocation = count($formattedLogs) > 0 ? $formattedLogs[count($formattedLogs) - 1] : null;

        $hasClockIn = ($attendance && !empty($attendance->clock_in) && $attendance->clock_in !== '00:00:00');
        $hasClockOut = ($attendance && !empty($attendance->clock_out) && $attendance->clock_out !== '00:00:00');

        $healthStatus = 'no_data';
        $minsSinceLastPing = null;
        $diagnosticMessage = 'No location logs recorded for selected date.';
        $isStationary = false;

        if ($currentLocation && isset($currentLocation['timestamp'])) {
            $lastPingCarbon = Carbon::createFromTimestamp($currentLocation['timestamp']);
            $minsSinceLastPing = round(now()->diffInSeconds($lastPingCarbon) / 60, 1);
            
            // Check if last 2 points are within 15 meters of each other
            $count = count($formattedLogs);
            if ($count >= 2) {
                $p1 = $formattedLogs[$count - 2];
                $p2 = $formattedLogs[$count - 1];
                $distLastTwo = (abs($p1['lat'] - $p2['lat']) + abs($p1['lng'] - $p2['lng'])) * 111000;
                if ($distLastTwo < 15) {
                    $isStationary = true;
                }
            } else {
                $isStationary = true;
            }

            if ($isClockedIn) {
                if ($minsSinceLastPing <= 3) {
                    if ($isStationary) {
                        $healthStatus = 'live_stationary';
                        $diagnosticMessage = 'Confirmed Active & Stationary at current location.';
                    } else {
                        $healthStatus = 'live_moving';
                        $diagnosticMessage = 'Confirmed Active & Moving on route.';
                    }
                } else if ($minsSinceLastPing <= 7) {
                    $healthStatus = 'signal_delayed';
                    $diagnosticMessage = 'Signal Delayed: Slow network or weak GPS (Last update ' . round($minsSinceLastPing) . ' mins ago).';
                } else {
                    $healthStatus = 'signal_lost';
                    $timeAgoStr = $lastPingCarbon->diffForHumans();
                    $diagnosticMessage = 'TRACKING SIGNAL LOST: No location update received for ' . round($minsSinceLastPing) . ' minutes (last ping ' . $timeAgoStr . '). Employee phone may be turned off, background browser suspended, or GPS disabled.';
                }
            } else {
                if ($hasClockOut) {
                    $healthStatus = 'clocked_out';
                    $diagnosticMessage = 'Employee is off duty (Clocked Out).';
                } else {
                    $healthStatus = 'not_clocked_in';
                    $diagnosticMessage = 'Employee has not clocked in today.';
                }
            }
        }

        return response()->json([
            'success' => true,
            'employee_name' => $employee ? $employee->name : 'Employee',
            'route' => $formattedLogs,
            'total_points' => count($formattedLogs),
            'total_distance_km' => $totalDistanceKm,
            'start_location' => $startLocation,
            'current_location' => $currentLocation,
            'is_clocked_in' => $isClockedIn,
            'has_clock_in' => $hasClockIn,
            'has_clock_out' => $hasClockOut,
            'health_status' => $healthStatus,
            'mins_since_last_ping' => $minsSinceLastPing,
            'diagnostic_message' => $diagnosticMessage,
            'is_stationary' => $isStationary,
        ]);
    }
}

