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
     * API endpoint for receiving location pings from the background script.
     */
    public function pingLocation(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->type !== 'employee') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or not an employee.'
            ], 401);
        }

        $employee = $user->employee;
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found.'
            ], 404);
        }

        // Validate Coordinates
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // Check if the employee is currently clocked in today
        $today = Carbon::today()->toDateString();
        $attendance = AttendanceEmployee::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        $isClockedIn = false;

        if ($attendance) {
            // Check first slot clock-in/out
            $slot1Active = !empty($attendance->clock_in) && 
                           (empty($attendance->clock_out) || $attendance->clock_out === '00:00:00');
            
            // Check second slot clock-in/out (if supports multi-punch)
            $slot2Active = !empty($attendance->clock_in_2) && $attendance->clock_in_2 !== '00:00:00' && 
                           (empty($attendance->clock_out_2) || $attendance->clock_out_2 === '00:00:00');

            if ($slot1Active || $slot2Active) {
                $isClockedIn = true;
            }
        }

        if (!$isClockedIn) {
            return response()->json([
                'success' => false,
                'message' => 'Location tracking is inactive. You must be punched in.'
            ], 200); // Return 200 so we don't trigger console errors in browser
        }

        // Log the coordinates
        EmployeeLocationLog::create([
            'employee_id' => $employee->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'pinged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location logged successfully.'
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

        \Log::info('Tracking Data Request parameters:', $request->all());

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required',
        ]);

        $employeeId = $request->employee_id;
        try {
            // Support multiple formats by using Carbon to parse
            $date = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format.'
            ], 422);
        }

        // Fetch location logs for the specific date
        $logs = EmployeeLocationLog::where('employee_id', $employeeId)
            ->whereDate('pinged_at', $date)
            ->orderBy('pinged_at', 'asc')
            ->get(['latitude', 'longitude', 'pinged_at']);

        // Format dates for display
        $formattedLogs = $logs->map(function ($log) {
            return [
                'lat' => (float)$log->latitude,
                'lng' => (float)$log->longitude,
                'time' => $log->pinged_at->format('h:i A'),
            ];
        });

        // Also fetch the employee's last active location (latest log overall)
        $latestLog = EmployeeLocationLog::where('employee_id', $employeeId)
            ->orderBy('pinged_at', 'desc')
            ->first(['latitude', 'longitude', 'pinged_at']);

        $currentLocation = null;
        if ($latestLog) {
            $currentLocation = [
                'lat' => (float)$latestLog->latitude,
                'lng' => (float)$latestLog->longitude,
                'time' => $latestLog->pinged_at->diffForHumans(),
            ];
        }

        // Check if the employee is currently clocked in on this date
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

        return response()->json([
            'success' => true,
            'route' => $formattedLogs,
            'current_location' => $currentLocation,
            'is_clocked_in' => $isClockedIn
        ]);
    }
}
