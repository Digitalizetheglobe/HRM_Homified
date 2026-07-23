<?php

namespace App\Http\Controllers;

    use App\Imports\AttendanceImport;
    use App\Models\AttendanceEmployee;
    use App\Models\Branch;
    use App\Models\Department;
    use App\Models\Employee;
    use App\Models\IpRestrict;
    use App\Models\Termination;
    use App\Models\User;
    use App\Models\Utility;
    use Carbon\Carbon;
    use DateTime;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;  // Add this line
    use App\Models\CompOffLeave;
    use App\Models\LeaveType;
    use App\Models\EmployeeLeaveBalance;
    use App\Models\Leave as LocalLeave;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;
    use App\Models\EmployeePayableDay;

    class AttendanceEmployeeController extends Controller
{

    

        public function index(Request $request)
        {
            if (\Auth::user()->can('attendance.marked.view.own') || \Auth::user()->can('attendance.marked.view.all')) {
                $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $branch->prepend('All', '');

                $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $department->prepend('All', '');

                // Get terminated employee IDs
                $terminatedEmployeeIds = Termination::pluck('employee_id')->toArray();

                // Get employees for filter dropdown - exclude Director, Hr, and terminated users
                $employees = Employee::where('created_by', \Auth::user()->creatorId())
                    ->whereNotIn('id', $terminatedEmployeeIds)
                    ->whereHas('user', function($query) {
                        $query->where('type', 'employee');
                    })
                    ->with('user')
                    ->get()
                    ->mapWithKeys(function ($employee) {
                        return [$employee->id => $employee->full_name];
                    });
                $employees->prepend('All', '');

                if (\Auth::user()->type == 'employee' && (!\Auth::user()->can('attendance.marked.view.all') || $request->has('own'))) {
                    $emp = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;

                    $attendanceEmployee = AttendanceEmployee::where('employee_id', $emp)
                                    ->orderBy('date', 'desc')
                                    ->orderBy('clock_in', 'desc');

                    if ($request->type == 'monthly' && !empty($request->month)) {
                        $month = date('m', strtotime($request->month));
                        $year  = date('Y', strtotime($request->month));


                        $start_date = date($year . '-' . $month . '-01');
                        $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));

                        // old date
                        // $end_date   = date($year . '-' . $month . '-t');

                        $attendanceEmployee->whereBetween(
                            'date',
                            [
                                $start_date,
                                $end_date,
                            ]
                        );
                    } elseif ($request->type == 'daily' && !empty($request->date)) {
                        $attendanceEmployee->where('date', $request->date);
                    } else {
                        $month      = date('m');
                        $year       = date('Y');
                        $start_date = date($year . '-' . $month . '-01');
                        $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));

                        // old date
                        // $end_date   = date($year . '-' . $month . '-t');

                        $attendanceEmployee->whereBetween(
                            'date',
                            [
                                $start_date,
                                $end_date,
                            ]
                        );
                    }

                    $attendanceEmployee = $attendanceEmployee->get();
                } else {
                    $employee = Employee::select('id')->where('created_by', \Auth::user()->creatorId())
                        ->whereHas('user', function($query) {
                            $query->where('type', 'employee');
                        });
                    if (!empty($request->branch)) {
                        $employee->where('branch_id', $request->branch);
                    }

                    if (!empty($request->department)) {
                        $employee->where('department_id', $request->department);
                    }

                    if (!empty($request->employee)) {
                        $employee->where('id', $request->employee);
                    }

                    $employee = $employee->get()->pluck('id');

                    $attendanceEmployee = AttendanceEmployee::whereIn('employee_id', $employee)
                                    ->orderBy('date', 'desc')
                                    ->orderBy('clock_in', 'desc');
                    
                    if ($request->type == 'monthly' && !empty($request->month)) {

                        $month = date('m', strtotime($request->month));
                        $year  = date('Y', strtotime($request->month));
                        $start_date = date($year . '-' . $month . '-01');
                        $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));

                        // old date
                        // $end_date   = date($year . '-' . $month . '-t');

                        $attendanceEmployee->whereBetween(
                            'date',
                            [
                                $start_date,
                                $end_date,
                            ]
                        );
                    } elseif ($request->type == 'daily' && !empty($request->date)) {
                        $attendanceEmployee->where('date', $request->date);
                    } else {

                        $month      = date('m');
                        $year       = date('Y');
                        $start_date = date($year . '-' . $month . '-01');
                        $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));
                        // old date
                        // $end_date   = date($year . '-' . $month . '-t');

                        $attendanceEmployee->whereBetween(
                            'date',
                            [
                                $start_date,
                                $end_date,
                            ]
                        );
                    }

                $attendanceEmployee = $attendanceEmployee->get();

                // Calculate late marks and early leaving dynamically for existing data
                $attendanceEmployee->transform(function ($attendance) {
                    $attendance->late = $this->calculateLateMark($attendance->clock_in, $attendance->date);
                    $attendance->early_leaving = $this->calculateEarlyLeaving($attendance->clock_out, $attendance->date);
                    return $attendance;
                });
            }

            return view('attendance.index', compact('attendanceEmployee', 'branch', 'department', 'employees'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        public function create()
        {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('attendance.marked.create.all')) {
                $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())
                    ->whereHas('user', function($query) {
                        $query->where('type', 'employee');
                    })
                    ->orderBy('employee_id', 'asc')
                    ->get()->mapWithKeys(function ($employee) {
                        return [$employee->id => \Auth::user()->employeeIdFormat($employee->employee_id) . ' - ' . $employee->full_name];
                    });

                // Get employees with single punch in (clock_in exists but clock_out is null or empty)
                $singlePunchEmployees = AttendanceEmployee::where('created_by', '=', Auth::user()->creatorId())
                    ->whereNotNull('clock_in')
                    ->where(function($query) {
                        $query->whereNull('clock_out')
                            ->orWhere('clock_out', '=', '')
                            ->orWhere('clock_out', '=', '00:00:00');
                    })
                    ->pluck('employee_id')
                    ->unique()
                    ->toArray();

                return view('attendance.create', compact('employees', 'singlePunchEmployees'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        
        public function store(Request $request)
        {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('attendance.marked.create.all')) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'employee_id' => 'required',
                        'date' => 'required',
                        'clock_in' => 'required',
                        'clock_out' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                // Check for existing attendance
                $attendance = AttendanceEmployee::where('employee_id', '=', $request->employee_id)
                    ->where('date', '=', $request->date)
                    ->where('clock_out', '=', '00:00:00')
                    ->get()
                    ->toArray();

                if ($attendance) {
                    return redirect()->route('attendanceemployee.index')->with('error', __('Employee Attendance Already Created.'));
                }

                $date = date("Y-m-d");

                // Calculate late time
                $totalLateSeconds = strtotime($request->clock_in) - strtotime($date . Utility::getValByName('company_start_time'));
                $hours = floor($totalLateSeconds / 3600);
                $mins  = floor($totalLateSeconds / 60 % 60);
                $secs  = floor($totalLateSeconds % 60);
                $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                // Calculate early leaving
                $totalEarlyLeavingSeconds = strtotime($date . Utility::getValByName('company_end_time')) - strtotime($request->clock_out);
                $hours = floor($totalEarlyLeavingSeconds / 3600);
                $mins  = floor($totalEarlyLeavingSeconds / 60 % 60);
                $secs  = floor($totalEarlyLeavingSeconds % 60);
                $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                // Calculate overtime
                if (strtotime($request->clock_out) > strtotime($date . Utility::getValByName('company_end_time'))) {
                    $totalOvertimeSeconds = strtotime($request->clock_out) - strtotime($date . Utility::getValByName('company_end_time'));
                    $hours = floor($totalOvertimeSeconds / 3600);
                    $mins  = floor($totalOvertimeSeconds / 60 % 60);
                    $secs  = floor($totalOvertimeSeconds % 60);
                    $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                } else {
                    $overtime = '00:00:00';
                }

                // Calculate total worked hours
                $workedSeconds = strtotime($request->clock_out) - strtotime($request->clock_in);
                $workedHours = $workedSeconds / 3600;
                
                // Determine status
                $clockInTime = strtotime($request->clock_in);
                $halfDayThreshold = strtotime($date . ' 12:00:00');

                if ($clockInTime >= $halfDayThreshold || $workedHours < AttendanceEmployee::REQUIRED_WORKING_HOURS) {
                    $status = AttendanceEmployee::STATUS_HALF_DAY;
                } else {
                    $status = AttendanceEmployee::STATUS_PRESENT;
                }

                $employeeAttendance = new AttendanceEmployee();
                $employeeAttendance->employee_id   = $request->employee_id;
                $employeeAttendance->date          = $request->date;
                $employeeAttendance->status        = $status;
                $employeeAttendance->clock_in      = $request->clock_in . ':00';
                $employeeAttendance->clock_out     = $request->clock_out . ':00';
                $employeeAttendance->late          = $late;
                $employeeAttendance->early_leaving = $earlyLeaving;
                $employeeAttendance->overtime      = $overtime;
                $employeeAttendance->total_rest    = '00:00:00';
                $employeeAttendance->created_by    = \Auth::user()->creatorId();
                $employeeAttendance->save();

                // Check for Comp-Off earning
                $employee = Employee::find($request->employee_id);
                $dayName = \Carbon\Carbon::parse($request->date)->format('l');
                if ($employee && strtolower($employee->week_off_day) === strtolower($dayName)) {
                    // Award Comp-Off for any valid attendance status (Present, Half Day, or Single Punch In)
                    if ($status == AttendanceEmployee::STATUS_PRESENT || $status == AttendanceEmployee::STATUS_HALF_DAY || $status == AttendanceEmployee::STATUS_SINGLE_PUNCH) {
                        $this->addCompOff($employee->id, $request->date);
                    }
                }

                return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully created.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        
        public function show(Request $request)
        {
            // return redirect()->back();
            return redirect()->route('attendanceemployee.index');
        }

        public function edit($id)
        {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('attendance.marked.edit.all')) {
                $attendanceEmployee = AttendanceEmployee::where('id', $id)->first();
                $employees          = Employee::where('created_by', '=', \Auth::user()->creatorId())
                    ->whereHas('user', function($query) {
                        $query->where('type', 'employee');
                    })
                    ->orderBy('employee_id', 'asc')
                    ->get()->mapWithKeys(function ($employee) {
                        return [$employee->id => \Auth::user()->employeeIdFormat($employee->employee_id) . ' - ' . $employee->full_name];
                    });

                return view('attendance.edit', compact('attendanceEmployee', 'employees'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        // public function update(Request $request, $id)
        // {
        //     if (\Auth::user()->type == 'company' || \Auth::user()->type == 'hr') {
        //         $employeeId      = AttendanceEmployee::where('employee_id', $request->employee_id)->first();
        //         $check = AttendanceEmployee::where('employee_id', '=', $request->employee_id)->where('date', $request->date)->first();

        //         $startTime = Utility::getValByName('company_start_time');
        //         $endTime   = Utility::getValByName('company_end_time');

        //         $clockIn = $request->clock_in;
        //         $clockOut = $request->clock_out;

        //         if ($clockIn) {
        //             $status = "present";
        //         } else {
        //             $status = "leave";
        //         }

        //         $totalLateSeconds = strtotime($clockIn) - strtotime($startTime);

        //         $hours = floor($totalLateSeconds / 3600);
        //         $mins  = floor($totalLateSeconds / 60 % 60);
        //         $secs  = floor($totalLateSeconds % 60);
        //         $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        //         $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($clockOut);
        //         $hours                    = floor($totalEarlyLeavingSeconds / 3600);
        //         $mins                     = floor($totalEarlyLeavingSeconds / 60 % 60);
        //         $secs                     = floor($totalEarlyLeavingSeconds % 60);
        //         $earlyLeaving             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        //         if (strtotime($clockOut) > strtotime($endTime)) {
        //             //Overtime
        //             $totalOvertimeSeconds = strtotime($clockOut) - strtotime($endTime);
        //             $hours                = floor($totalOvertimeSeconds / 3600);
        //             $mins                 = floor($totalOvertimeSeconds / 60 % 60);
        //             $secs                 = floor($totalOvertimeSeconds % 60);
        //             $overtime             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        //         } else {
        //             $overtime = '00:00:00';
        //         }
        //         if ($check->date == date('Y-m-d')) {
        //             $check->update([
        //                 'late' => $late,
        //                 'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
        //                 'overtime' => $overtime,
        //                 'clock_in' => $clockIn,
        //                 'clock_out' => $clockOut
        //             ]);

        //             return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully updated.'));
        //         } else {
        //             return redirect()->route('attendanceemployee.index')->with('error', __('You can only update current day attendance'));
        //         }
        //     }

        //     $employeeId      = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
        //     $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)->where('date', date('Y-m-d'))->first();
        //     if (!empty($todayAttendance) && $todayAttendance->clock_out == '00:00:00') {
        //         $startTime = Utility::getValByName('company_start_time');
        //         $endTime   = Utility::getValByName('company_end_time');
        //         if (Auth::user()->type == 'employee') {

        //             $date = date("Y-m-d");
        //             $time = date("H:i:s");

        //             //early Leaving
        //             $totalEarlyLeavingSeconds = strtotime($date . $endTime) - time();
        //             $hours                    = floor($totalEarlyLeavingSeconds / 3600);
        //             $mins                     = floor($totalEarlyLeavingSeconds / 60 % 60);
        //             $secs                     = floor($totalEarlyLeavingSeconds % 60);
        //             $earlyLeaving             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        //             if (time() > strtotime($date . $endTime)) {
        //                 //Overtime
        //                 $totalOvertimeSeconds = time() - strtotime($date . $endTime);
        //                 $hours                = floor($totalOvertimeSeconds / 3600);
        //                 $mins                 = floor($totalOvertimeSeconds / 60 % 60);
        //                 $secs                 = floor($totalOvertimeSeconds % 60);
        //                 $overtime             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        //             } else {
        //                 $overtime = '00:00:00';
        //             }

        //             $attendanceEmployee                = AttendanceEmployee::find($id);
        //             $attendanceEmployee->clock_out     = $time;
        //             $attendanceEmployee->early_leaving = $earlyLeaving;
        //             $attendanceEmployee->overtime      = $overtime;
        //             $attendanceEmployee->save();

        //             return redirect()->route('dashboard')->with('success', __('Employee successfully clock Out.'));
        //         } else {
        //             $date = date("Y-m-d");
        //             //late
        //             $totalLateSeconds = strtotime($request->clock_in) - strtotime($date . $startTime);

        //             $hours = floor($totalLateSeconds / 3600);
        //             $mins  = floor($totalLateSeconds / 60 % 60);
        //             $secs  = floor($totalLateSeconds % 60);
        //             $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        //             //early Leaving
        //             $totalEarlyLeavingSeconds = strtotime($date . $endTime) - strtotime($request->clock_out);
        //             $hours                    = floor($totalEarlyLeavingSeconds / 3600);
        //             $mins                     = floor($totalEarlyLeavingSeconds / 60 % 60);
        //             $secs                     = floor($totalEarlyLeavingSeconds % 60);
        //             $earlyLeaving             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);


        //             if (strtotime($request->clock_out) > strtotime($date . $endTime)) {
        //                 //Overtime
        //                 $totalOvertimeSeconds = strtotime($request->clock_out) - strtotime($date . $endTime);
        //                 $hours                = floor($totalOvertimeSeconds / 3600);
        //                 $mins                 = floor($totalOvertimeSeconds / 60 % 60);
        //                 $secs                 = floor($totalOvertimeSeconds % 60);
        //                 $overtime             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        //             } else {
        //                 $overtime = '00:00:00';
        //             }
        
        //             $attendanceEmployee                = AttendanceEmployee::find($id);
        //             $attendanceEmployee->employee_id   = $request->employee_id;
        //             $attendanceEmployee->date          = $request->date;
        //             $attendanceEmployee->clock_in      = $request->clock_in;
        //             $attendanceEmployee->clock_out     = $request->clock_out;
        //             $attendanceEmployee->late          = $late;
        //             $attendanceEmployee->early_leaving = $earlyLeaving;
        //             $attendanceEmployee->overtime      = $overtime;
        //             $attendanceEmployee->total_rest    = '00:00:00';

        //             $attendanceEmployee->save();

        //             return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully updated.'));
        //         }
        //     } else {
        //         return redirect()->back()->with('error', __('Employee are not allow multiple time clock in & clock for every day.'));
        //     }
        // }

        public function update(Request $request, $id)
        {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('attendance.marked.edit.all')) {
                $check = AttendanceEmployee::where('id', '=', $id)
                                        ->where('employee_id', '=', $request->employee_id)
                                        ->where('date', $request->date)
                                        ->first();

                if (!$check) {
                    return redirect()->route('attendanceemployee.index')->with('error', __('Attendance record not found.'));
                }

                $startTime = Utility::getValByName('company_start_time');
                $endTime   = Utility::getValByName('company_end_time');

                $clockIn = $request->clock_in;
                $clockOut = $request->clock_out;

                // Calculate late time
                $totalLateSeconds = strtotime($clockIn) - strtotime($startTime);
                $hours = floor($totalLateSeconds / 3600);
                $mins  = floor($totalLateSeconds / 60 % 60);
                $secs  = floor($totalLateSeconds % 60);
                $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                // Calculate early leaving
                $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($clockOut);
                $hours = floor($totalEarlyLeavingSeconds / 3600);
                $mins  = floor($totalEarlyLeavingSeconds / 60 % 60);
                $secs  = floor($totalEarlyLeavingSeconds % 60);
                $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                // Calculate overtime
                if (strtotime($clockOut) > strtotime($endTime)) {
                    $totalOvertimeSeconds = strtotime($clockOut) - strtotime($endTime);
                    $hours = floor($totalOvertimeSeconds / 3600);
                    $mins  = floor($totalOvertimeSeconds / 60 % 60);
                    $secs  = floor($totalOvertimeSeconds % 60);
                    $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                } else {
                    $overtime = '00:00:00';
                }

                // Calculate total worked hours
                $workedSeconds = strtotime($clockOut) - strtotime($clockIn);
                $workedHours = $workedSeconds / 3600;
                
                // Determine status
                $clockInTime = strtotime($clockIn);
                $halfDayThreshold = strtotime($request->date . ' 12:00:00');

                if ($clockInTime >= $halfDayThreshold || $workedHours < AttendanceEmployee::REQUIRED_WORKING_HOURS) {
                    $status = AttendanceEmployee::STATUS_HALF_DAY;
                } else {
                    $status = AttendanceEmployee::STATUS_PRESENT;
                }

                if ($check->date == date('Y-m-d')) {
                    $check->update([
                        'late' => $late,
                        'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
                        'overtime' => $overtime,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => $status
                    ]);

                    // Check for Comp-Off earning
                    $employee = Employee::find($request->employee_id);
                    $dayName = \Carbon\Carbon::parse($request->date)->format('l');
                    if ($employee && strtolower($employee->week_off_day) === strtolower($dayName)) {
                        // Award Comp-Off for any valid attendance status (Present, Half Day, or Single Punch In)
                        if ($status == AttendanceEmployee::STATUS_PRESENT || $status == AttendanceEmployee::STATUS_HALF_DAY || $status == AttendanceEmployee::STATUS_SINGLE_PUNCH) {
                            $this->addCompOff($employee->id, $request->date);
                        }
                    }

                    return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully updated.'));
                } else {
                    return redirect()->route('attendanceemployee.index')->with('error', __('You can only update current day attendance.'));
                }
            }

            $employeeId      = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
            $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)->where('date', date('Y-m-d'))->first();

            $startTime = Utility::getValByName('company_start_time');
            $endTime   = Utility::getValByName('company_end_time');
            if (Auth::user()->type == 'employee') {

                $date = date("Y-m-d");
                $time = date("H:i:s");

                //early Leaving
                $totalEarlyLeavingSeconds = strtotime($date . $endTime) - time();
                $hours                    = floor($totalEarlyLeavingSeconds / 3600);
                $mins                     = floor($totalEarlyLeavingSeconds / 60 % 60);
                $secs                     = floor($totalEarlyLeavingSeconds % 60);
                $earlyLeaving             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                if (time() > strtotime($date . $endTime)) {
                    //Overtime
                    $totalOvertimeSeconds = time() - strtotime($date . $endTime);
                    $hours                = floor($totalOvertimeSeconds / 3600);
                    $mins                 = floor($totalOvertimeSeconds / 60 % 60);
                    $secs                 = floor($totalOvertimeSeconds % 60);
                    $overtime             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                } else {
                    $overtime = '00:00:00';
                }

                $attendanceEmployee['clock_out']     = $time;
                $attendanceEmployee['early_leaving'] = $earlyLeaving;
                $attendanceEmployee['overtime']      = $overtime;

                if (!empty($request->date)) {
                    $attendanceEmployee['date']       =  $request->date;
                }
                AttendanceEmployee::where('id', $id)->update($attendanceEmployee);

                return redirect()->route('dashboard')->with('success', __('Employee successfully clock Out.'));
            } else {
                $date = date("Y-m-d");
                $clockout_time = date("H:i:s");
                //late
                $totalLateSeconds = strtotime($clockout_time) - strtotime($date . $startTime);

                $hours            = abs(floor($totalLateSeconds / 3600));
                $mins             = abs(floor($totalLateSeconds / 60 % 60));
                $secs             = abs(floor($totalLateSeconds % 60));

                $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                //early Leaving
                $totalEarlyLeavingSeconds = strtotime($date . $endTime) - strtotime($clockout_time);
                $hours                    = floor($totalEarlyLeavingSeconds / 3600);
                $mins                     = floor($totalEarlyLeavingSeconds / 60 % 60);
                $secs                     = floor($totalEarlyLeavingSeconds % 60);
                $earlyLeaving             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);


                if (strtotime($clockout_time) > strtotime($date . $endTime)) {
                    //Overtime
                    $totalOvertimeSeconds = strtotime($clockout_time) - strtotime($date . $endTime);
                    $hours                = floor($totalOvertimeSeconds / 3600);
                    $mins                 = floor($totalOvertimeSeconds / 60 % 60);
                    $secs                 = floor($totalOvertimeSeconds % 60);
                    $overtime             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                } else {
                    $overtime = '00:00:00';
                }

                $attendanceEmployee                = AttendanceEmployee::find($id);
                $attendanceEmployee->clock_out     = $clockout_time;
                $attendanceEmployee->late          = $late;
                $attendanceEmployee->early_leaving = $earlyLeaving;
                $attendanceEmployee->overtime      = $overtime;
                $attendanceEmployee->total_rest    = '00:00:00';

                $attendanceEmployee->save();

                return redirect()->back()->with('success', __('Employee attendance successfully updated.'));
            }
        }

        public function destroy($id)
        {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('attendance.marked.delete.all')) {
                $attendance = AttendanceEmployee::where('id', $id)->first();

                $attendance->delete();

                return redirect()->route('attendanceemployee.index')->with('success', __('Attendance successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        // public function attendance(Request $request)
        // {
        //     $settings = Utility::settings();

        //     if ($settings['ip_restrict'] == 'on') {
        //         $userIp = request()->ip();
        //         $ip     = IpRestrict::where('created_by', \Auth::user()->creatorId())->whereIn('ip', [$userIp])->first();
        //         if (!empty($ip)) {
        //             return redirect()->back()->with('error', __('this ip is not allowed to clock in & clock out.'));
        //         }
        //     }

        //     $employeeId      = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
        //     $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)->where('date', date('Y-m-d'))->first();
        //     if (empty($todayAttendance)) {

        //         $startTime = Utility::getValByName('company_start_time');
        //         $endTime   = Utility::getValByName('company_end_time');

        //         $attendance = AttendanceEmployee::orderBy('id', 'desc')->where('employee_id', '=', $employeeId)->where('clock_out', '=', '00:00:00')->first();

        //         if ($attendance != null) {
        //             $attendance            = AttendanceEmployee::find($attendance->id);
        //             $attendance->clock_out = $endTime;
        //             $attendance->save();
        //         }

        //         $date = date("Y-m-d");
        //         $time = date("H:i:s");

        //         //late
        //         $totalLateSeconds = time() - strtotime($date . $startTime);
        //         $hours            = floor($totalLateSeconds / 3600);
        //         $mins             = floor($totalLateSeconds / 60 % 60);
        //         $secs             = floor($totalLateSeconds % 60);
        //         $late             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);


        //         $checkDb = AttendanceEmployee::where('employee_id', '=', \Auth::user()->id)->get()->toArray();


        //         if (empty($checkDb)) {
        //             $employeeAttendance                = new AttendanceEmployee();
        //             $employeeAttendance->employee_id   = $employeeId;
        //             $employeeAttendance->date          = $date;
        //             $employeeAttendance->status        = 'Present';
        //             $employeeAttendance->clock_in      = $time;
        //             $employeeAttendance->clock_out     = '00:00:00';
        //             $employeeAttendance->late          = $late;
        //             $employeeAttendance->early_leaving = '00:00:00';
        //             $employeeAttendance->overtime      = '00:00:00';
        //             $employeeAttendance->total_rest    = '00:00:00';
        //             $employeeAttendance->created_by    = \Auth::user()->id;

        //             $employeeAttendance->save();

        //             return redirect()->route('dashboard')->with('success', __('Employee Successfully Clock In.'));
        //         }
        //         foreach ($checkDb as $check) {


        //             $employeeAttendance                = new AttendanceEmployee();
        //             $employeeAttendance->employee_id   = $employeeId;
        //             $employeeAttendance->date          = $date;
        //             $employeeAttendance->status        = 'Present';
        //             $employeeAttendance->clock_in      = $time;
        //             $employeeAttendance->clock_out     = '00:00:00';
        //             $employeeAttendance->late          = $late;
        //             $employeeAttendance->early_leaving = '00:00:00';
        //             $employeeAttendance->overtime      = '00:00:00';
        //             $employeeAttendance->total_rest    = '00:00:00';
        //             $employeeAttendance->created_by    = \Auth::user()->id;

        //             $employeeAttendance->save();

        //             return redirect()->route('dashboard')->with('success', __('Employee Successfully Clock In.'));
        //         }
        //     } else {
        //         return redirect()->back()->with('error', __('Employee are not allow multiple time clock in & clock for every day.'));
        //     }
        // }

        
        public function bulkAttendance(Request $request)
        {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('attendance.bulk.view.all')) {

                $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $branch->prepend('Select Branch', '');

                $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $department->prepend('Select Department', '');

                $employees = [];
                if (!empty($request->branch) && !empty($request->department)) {
                    $employees = Employee::where('created_by', \Auth::user()->creatorId())
                        ->whereHas('user', function($query) {
                            $query->where('type', 'employee');
                        })
                        ->where('branch_id', $request->branch)
                        ->where('department_id', $request->department)
                        ->get();
                }

                return view('attendance.bulk', compact('employees', 'branch', 'department'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        public function bulkAttendanceData(Request $request)
        {
            if (\Auth::user()->type == 'company' || \Auth::user()->can('attendance.bulk.create.all')) {
                if (!empty($request->branch) && !empty($request->department)) {
                    $startTime = Utility::getValByName('company_start_time');
                    $endTime   = Utility::getValByName('company_end_time');
                    $date      = $request->date;

                    $employees = $request->employee_id;
                    $atte      = [];
                    foreach ($employees as $employee) {
                        $present = 'present-' . $employee;
                        $in      = 'in-' . $employee;
                        $out     = 'out-' . $employee;
                        $atte[]  = $present;
                        if ($request->$present == 'on') {

                            $in  = date("H:i:s", strtotime($request->$in));
                            $out = date("H:i:s", strtotime($request->$out));

                            $totalLateSeconds = strtotime($in) - strtotime($startTime);

                            $hours = floor($totalLateSeconds / 3600);
                            $mins  = floor($totalLateSeconds / 60 % 60);
                            $secs  = floor($totalLateSeconds % 60);
                            $late  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                            //early Leaving
                            $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($out);
                            $hours                    = floor($totalEarlyLeavingSeconds / 3600);
                            $mins                     = floor($totalEarlyLeavingSeconds / 60 % 60);
                            $secs                     = floor($totalEarlyLeavingSeconds % 60);
                            $earlyLeaving             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                            // Calculate total worked hours
                            $workedSeconds = strtotime($out) - strtotime($in);
                            $workedHours = $workedSeconds / 3600;
                            
                            // Determine status
                            if ($workedHours >= AttendanceEmployee::REQUIRED_WORKING_HOURS) {
                                $status = AttendanceEmployee::STATUS_PRESENT;
                            } else {
                                $status = AttendanceEmployee::STATUS_HALF_DAY;
                            }

                            if (strtotime($out) > strtotime($endTime)) {
                                //Overtime
                                $totalOvertimeSeconds = strtotime($out) - strtotime($endTime);
                                $hours                = floor($totalOvertimeSeconds / 3600);
                                $mins                 = floor($totalOvertimeSeconds / 60 % 60);
                                $secs                 = floor($totalOvertimeSeconds % 60);
                                $overtime             = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                            } else {
                                $overtime = '00:00:00';
                            }

                            $attendance = AttendanceEmployee::where('employee_id', '=', $employee)->where('date', '=', $request->date)->first();

                            if (!empty($attendance)) {
                                $employeeAttendance = $attendance;
                            } else {
                                $employeeAttendance              = new AttendanceEmployee();
                                $employeeAttendance->employee_id = $employee;
                                $employeeAttendance->created_by  = \Auth::user()->creatorId();
                            }

                            $employeeAttendance->date          = $request->date;
                            $employeeAttendance->status        = $status; // Updated status
                            $employeeAttendance->clock_in      = $in;
                            $employeeAttendance->clock_out     = $out;
                            $employeeAttendance->late          = $late;
                            $employeeAttendance->early_leaving = ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00';
                            $employeeAttendance->overtime      = $overtime;
                            $employeeAttendance->total_rest    = '00:00:00';
                            $employeeAttendance->save();

                            // Check for Comp-Off earning
                            $empRecord = Employee::find($employee);
                            $dayName = \Carbon\Carbon::parse($request->date)->format('l');
                            if ($empRecord && strtolower($empRecord->week_off_day) === strtolower($dayName)) {
                                // Award Comp-Off for any valid attendance status (Present, Half Day, or Single Punch In)
                                if ($status == AttendanceEmployee::STATUS_PRESENT || $status == AttendanceEmployee::STATUS_HALF_DAY || $status == AttendanceEmployee::STATUS_SINGLE_PUNCH) {
                                    $this->addCompOff($empRecord->id, $request->date);
                                }
                            }
                        }
                    }

                    return redirect()->back()->with('success', __('Employee attendance successfully created.'));
                } else {
                    return redirect()->back()->with('error', __('Branch & department field required.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        public function importFile()
        {
            return view('attendance.import');
        }

        public function import(Request $request)
        {
            $rules = [
                'file' => 'required|mimes:csv,txt,xlsx',
            ];
            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->getMessageBag()->first());
            }

            try {
                $data = (new AttendanceImport())->toArray(request()->file('file'))[0];
                
                // Determine if this is our "Report" format
                $isReportFormat = false;
                foreach ($data as $row) {
                    if (isset($row[0]) && (strpos($row[0], 'Employee Code:') !== false || strpos($row[0], 'Empployee Code:') !== false)) {
                        $isReportFormat = true;
                        break;
                    }
                }

                if ($isReportFormat) {
                    return $this->processReportImport($data);
                } else {
                    // Fallback to flat format logic (standard import)
                    return $this->processFlatImport($data);
                }

            } catch (\Exception $e) {
                \Log::error('Attendance Import Error: ' . $e->getMessage());
                return redirect()->back()->with('error', __('Error processing file: ') . $e->getMessage());
            }
        }

        /**
         * Process the multi-row Report format (Exported format)
         */
        protected function processReportImport($data)
        {
            $dates = [];
            $currentEmployee = null;
            $processedCount = 0;
            $month = null;
            $year = null;
            $totalPayableDaysCol = -1;
            $finalPayableSalaryCol = -1;

            $startTime = Utility::getValByName('company_start_time');
            $endTime   = Utility::getValByName('company_end_time');

            for ($i = 0; $i < count($data); $i++) {
                $row = $data[$i];
                if (empty($row)) continue;

                $firstCell = trim((string)($row[0] ?? ''));

                // Extract period from first row if not already set
                if ($month === null) {
                    $periodStr = trim((string)($data[0][0] ?? ''));
                    if (preg_match('/([A-Za-z]+ \d+ \d{4}) To ([A-Za-z]+ \d+ \d{4})/', $periodStr, $matches)) {
                        $periodDate = \Carbon\Carbon::parse($matches[1]);
                        $month = $periodDate->month;
                        $year = $periodDate->year;
                    } else {
                        $month = date('m');
                        $year = date('Y');
                    }
                }

                // Find summary column indices if not already set
                if ($finalPayableSalaryCol === -1) {
                    for ($k = 0; $k < min(10, count($data)); $k++) {
                        foreach ($data[$k] as $colIdx => $cellVal) {
                            $cellVal = trim((string)$cellVal);
                            if ($cellVal === 'Total Payable Days') $totalPayableDaysCol = $colIdx;
                            if ($cellVal === 'Final Payable Salary') $finalPayableSalaryCol = $colIdx;
                        }
                        if ($finalPayableSalaryCol !== -1) break;
                    }
                }

                // 1. Identify Employee Code row
                if (strpos($firstCell, 'Employee Code:') !== false || strpos($firstCell, 'Empployee Code:') !== false) {
                    $empCode = trim(str_replace(['Employee Code:', 'Empployee Code:'], '', $firstCell));
                    $currentEmployee = Employee::where('employee_id', $empCode)->where('created_by', \Auth::user()->creatorId())->first();
                    
                    // Extract summary values if columns were found
                    if ($currentEmployee && $finalPayableSalaryCol !== -1) {
                        $totalPayableDaysVal = $row[$totalPayableDaysCol] ?? 0;
                        $finalPayableSalaryVal = $row[$finalPayableSalaryCol] ?? 0;
                        
                        EmployeePayableDay::updateOrCreate(
                            [
                                'employee_id' => $currentEmployee->id,
                                'month' => $month,
                                'year' => $year,
                            ],
                            [
                                'total_payable_days' => $totalPayableDaysVal,
                                'final_payable_salary' => $finalPayableSalaryVal,
                                'created_by' => \Auth::user()->creatorId(),
                            ]
                        );
                    }
                    continue;
                }

                // 2. Identify Dates row (labeled "Days")
                if ($firstCell === 'Days') {
                    $dates = [];
                    for ($j = 1; $j < count($row); $j++) {
                        $val = trim((string)($row[$j] ?? ''));
                        if (empty($val)) break; 
                        $dates[$j] = $val;
                    }
                    continue;
                }

                // 3. Process Status, InTime, OutTime rows
                if ($currentEmployee && $firstCell === 'Status') {
                    $statusRow = $row;
                    $inTimeRow = $data[$i + 1] ?? [];
                    $outTimeRow = $data[$i + 2] ?? [];
                    
                    if (trim((string)($inTimeRow[0] ?? '')) !== 'InTime' || trim((string)($outTimeRow[0] ?? '')) !== 'OutTime') {
                        continue;
                    }

                    // Extract period from first row
                    $periodStr = trim((string)($data[0][0] ?? ''));
                    preg_match('/([A-Za-z]+ \d+ \d{4}) To ([A-Za-z]+ \d+ \d{4})/', $periodStr, $matches);
                    $baseDate = !empty($matches[1]) ? \Carbon\Carbon::parse($matches[1]) : \Carbon\Carbon::now();

                    foreach ($dates as $colIdx => $dayLabel) {
                        $dayNum = (int)filter_var($dayLabel, FILTER_SANITIZE_NUMBER_INT);
                        if (!$dayNum) continue;

                        $dateObj = $baseDate->copy()->day($dayNum);
                        $date = $dateObj->toDateString();
                        
                        $newStatus = trim((string)($statusRow[$colIdx] ?? ''));
                        $newIn = trim((string)($inTimeRow[$colIdx] ?? ''));
                        $newOut = trim((string)($outTimeRow[$colIdx] ?? ''));

                        if (empty($newStatus)) continue;

                        $this->applyAttendanceTransition($currentEmployee, $date, $newStatus, $newIn, $newOut, $startTime, $endTime);
                        $processedCount++;
                    }
                    
                    $i += 3; 
                }
            }

            return redirect()->back()->with('success', sprintf(__('Import completed. Processed %d records.'), $processedCount));
        }

        /**
         * Process traditional flat format (Email, Date, In, Out)
         */
        protected function processFlatImport($data)
        {
            $startTime = Utility::getValByName('company_start_time');
            $endTime   = Utility::getValByName('company_end_time');
            $processedCount = 0;

            foreach ($data as $key => $value) {
                if ($key == 0) continue; 

                $employeeData = Employee::where('email', $value[0])->where('created_by', \Auth::user()->creatorId())->first();
                if (!$employeeData) continue;

                $date = $value[1];
                $newIn = $value[2];
                $newOut = $value[3];
                $newStatus = !empty($newIn) ? 'P' : 'LOP';

                $this->applyAttendanceTransition($employeeData, $date, $newStatus, $newIn, $newOut, $startTime, $endTime);
                $processedCount++;
            }

            return redirect()->back()->with('success', sprintf(__('Import completed. Processed %d records.'), $processedCount));
        }

        /**
         * Core Transition Logic
         */
        protected function applyAttendanceTransition($employee, $date, $newStatus, $newIn, $newOut, $startTime, $endTime)
        {
            $newStatus = strtoupper($newStatus);

            // Get current state from database to detect changes
            $currentAtt = AttendanceEmployee::where('employee_id', $employee->id)->where('date', $date)->first();
            $currentLeaveCode = $this->getLeaveStatus($employee->id, $date);
            
            $currentStatus = '';
            if ($currentAtt) {
                if ($currentAtt->status === 'Present') $currentStatus = 'P';
                elseif ($currentAtt->status === 'Single Punch In') $currentStatus = 'SP';
                elseif ($currentAtt->late !== '00:00:00') $currentStatus = 'LM';
                else $currentStatus = 'P';
            } elseif ($currentLeaveCode) {
                $currentStatus = $currentLeaveCode;
            } else {
                // If neither attendance nor leave exists, check if it's a Weekly Off
                $dayName = \Carbon\Carbon::parse($date)->format('l');
                if (strtolower($employee->week_off_day) === strtolower($dayName)) {
                    $currentStatus = 'WO';
                } else {
                    $currentStatus = 'A'; // Default for no record
                }
            }

            // ONLY apply transition if the status has changed
            // ONLY apply transition if the status has changed or if it's an update (like HD)
            if ($newStatus === $currentStatus && !in_array($newStatus, ['P', 'HD'])) {
                return;
            }
            if ($newStatus === 'P' && $currentAtt && $currentAtt->status !== 'Half Day') {
                $this->updateAttendanceTimes($currentAtt, $newIn, $newOut, $date);
                return;
            }

            if (in_array($newStatus, ['P', 'LM', 'SP', 'HD'])) {
                $this->processPresentTransition($employee, $date, $newIn, $newOut, $startTime, $endTime, $newStatus);
            }
            elseif (in_array($newStatus, ['LOP', 'EL', 'SL', 'CO'])) {
                $this->processLeaveTransition($employee, $date, $newStatus);
            }
            elseif (in_array($newStatus, ['WO', 'A'])) {
                $this->processWOTransition($employee, $date);
            }
        }

        protected function updateAttendanceTimes($attendance, $in, $out, $date)
        {
            if (empty($in) || $in === '00:00' || $in === '00:00:00') return;
            if (empty($out) || $out === '00:00' || $out === '00:00:00') return;

            if (strlen($in) == 5) $in .= ':00';
            if (strlen($out) == 5) $out .= ':00';

            // Only update if actually different
            if ($attendance->clock_in !== $in || $attendance->clock_out !== $out) {
                $attendance->clock_in = $in;
                $attendance->clock_out = $out;
                $attendance->late = $this->calculateLateMark($in, $date);
                $attendance->early_leaving = $this->calculateEarlyLeaving($out, $date);
                $attendance->save();
            }
        }

        protected function processPresentTransition($employee, $date, $in, $out, $startTime, $endTime, $statusCode)
        {
            if (empty($in) || $in === '00:00' || $in === '00:00:00') $in = $startTime;
            if (empty($out) || $out === '00:00' || $out === '00:00:00') $out = $endTime;

            if (strlen($in) == 5) $in .= ':00';
            if (strlen($out) == 5) $out .= ':00';

            $late = $this->calculateLateMark($in, $date);
            $earlyLeaving = $this->calculateEarlyLeaving($out, $date);
            
            $status = 'Present';
            if ($statusCode === 'SP') $status = 'Single Punch In';
            if ($statusCode === 'HD') $status = 'Half Day';

            AttendanceEmployee::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $date],
                [
                    'status' => $status,
                    'clock_in' => $in,
                    'clock_out' => $out,
                    'late' => $late,
                    'early_leaving' => $earlyLeaving,
                    'total_rest' => '00:00:00',
                    'created_by' => \Auth::user()->creatorId(),
                ]
            );

            $dayName = \Carbon\Carbon::parse($date)->format('l');
            if (strtolower($employee->week_off_day) === strtolower($dayName)) {
                // Award Comp-Off for any valid attendance status (Present, Half Day, or Single Punch In)
                if ($status == 'Present' || $status == 'Half Day' || $status == 'Single Punch In') {
                    $this->addCompOff($employee->id, $date);
                }
            }
        }

        protected function processLeaveTransition($employee, $date, $code)
        {
            AttendanceEmployee::where('employee_id', $employee->id)->where('date', $date)->delete();
            CompOffLeave::where('employees_id', $employee->id)->where('comp_off_date', $date)->delete();

            $leaveTypeId = 6; 
            if ($code === 'EL') $leaveTypeId = 2;
            elseif ($code === 'SL') $leaveTypeId = 1;
            elseif ($code === 'CO') $leaveTypeId = 4;

            $exists = LocalLeave::where('employee_id', $employee->id)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->where('status', 'Approved')
                ->exists();

            if (!$exists) {
                $leave = new LocalLeave();
                $leave->employee_id = $employee->id;
                $leave->leave_type_id = $leaveTypeId; // Fixed: Not guarded by mass assignment
                $leave->applied_on = date('Y-m-d');
                $leave->start_date = $date;
                $leave->end_date = $date;
                $leave->total_leave_days = 1;
                $leave->leave_duration_type = 'full_day';
                $leave->leave_reason = __('Imported / Calendar Edit');
                $leave->status = 'Approved';
                $leave->created_by = \Auth::user()->creatorId();
                $leave->save();
            }
        }

        protected function processWOTransition($employee, $date)
        {
            AttendanceEmployee::where('employee_id', $employee->id)->where('date', $date)->delete();
            CompOffLeave::where('employees_id', $employee->id)->where('comp_off_date', $date)->delete();
            LocalLeave::where('employee_id', $employee->id)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->delete();
        }

        protected function addCompOff($employeeId, $date)
        {
            $exists = CompOffLeave::where('employees_id', $employeeId)->where('comp_off_date', $date)->exists();
            if (!$exists) {
                CompOffLeave::create([
                    'employees_id' => $employeeId,
                    'comp_off_date' => $date,
                    'comp_off_data' => 1.0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }



        public function attendance(Request $request)
        {
            $isAjax = $request->ajax() || $request->wantsJson();

            // ================= EMPLOYEE =================
            $employeeId = Auth::user()->employee->id;
            $date = date('Y-m-d');
            $time = date('H:i:s');

            $attendance = AttendanceEmployee::where('employee_id', $employeeId)
                ->where('date', $date)
                ->first();

            // Determine explicit action from request parameters
            $isPunchInRequest = $request->has('in');
            $isPunchOutRequest = $request->has('out');

            // Fallback for backward compatibility if neither is explicitly passed
            if (!$isPunchInRequest && !$isPunchOutRequest) {
                if (!$attendance) {
                    $isPunchInRequest = true;
                } else {
                    $isPunchOutRequest = true;
                }
            }

            // ================= PUNCH IN =================
            if ($isPunchInRequest) {
                if ($attendance) {
                    // Already punched in - return success response gracefully to handle retries/duplicates
                    return response()->json([
                        'success' => true,
                        'message' => 'Already punched in for today.',
                        'attendance_id' => $attendance->id
                    ]);
                }

                // GPS location is MANDATORY - block punch-in if location not provided
                $latitude  = $request->latitude;
                $longitude = $request->longitude;
                $accuracy  = $request->accuracy ?? null;

                // Validate location is provided
                if (!$latitude || !$longitude) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Location is required. Please enable location services and try again.'
                    ], 400);
                }

                // Validate location coordinates
                if (!is_numeric($latitude) || !is_numeric($longitude)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid location coordinates. Please try again.'
                    ], 400);
                }

                $attendance = new AttendanceEmployee();
                $attendance->employee_id = $employeeId;
                $attendance->date = $date;
                $attendance->clock_in = $time; // Record time immediately
                $attendance->clock_out = '00:00:00';
                $attendance->status = AttendanceEmployee::STATUS_SINGLE_PUNCH;
                $attendance->late = $this->calculateLateMark($time, $date);
                $attendance->created_by = Auth::user()->id;

                // Set location (mandatory - validated above)
                try {
                    $location = $this->geocodeCoordinates($latitude, $longitude);
                    $attendance->clock_in_latitude = $latitude;
                    $attendance->clock_in_longitude = $longitude;
                    $attendance->clock_in_accuracy = $accuracy;
                    $attendance->clock_in_location = $location;
                    $attendance->clock_in_location_captured_at = now();
                } catch (\Throwable $e) {
                    // Location geocoding failed - still save coordinates
                    $attendance->clock_in_latitude = $latitude;
                    $attendance->clock_in_longitude = $longitude;
                    $attendance->clock_in_accuracy = $accuracy;
                    $attendance->clock_in_location = 'Lat: ' . number_format($latitude, 5) . ', Lng: ' . number_format($longitude, 5);
                    $attendance->clock_in_location_captured_at = now();
                    \Log::warning('Location geocoding failed during punch-in', [
                        'error' => $e->getMessage()
                    ]);
                }

                $attendance->save();

                // Check for Comp-Off earning immediately on Punch In if it's a Week-Off day
                $dayName = \Carbon\Carbon::parse($date)->format('l');
                $employeeRecord = Auth::user()->employee;
                if ($employeeRecord && strtolower($employeeRecord->week_off_day) === strtolower($dayName)) {
                    $this->addCompOff($employeeRecord->id, $date);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Punch In successful.',
                    'attendance_id' => $attendance->id // Return ID for location update
                ]);
            }

            // ================= PUNCH OUT =================
            if ($isPunchOutRequest) {
                if (!$attendance) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No punch in record found for today. Please punch in first.'
                    ], 400);
                }

                if ($attendance->clock_out !== '00:00:00') {
                    // Already punched out - return success response gracefully
                    return response()->json([
                        'success' => true,
                        'message' => 'Already punched out for today.'
                    ]);
                }

                // GPS location is MANDATORY - block punch-out if location not provided
                $latitude  = $request->latitude;
                $longitude = $request->longitude;
                $accuracy  = $request->accuracy ?? null;

                // Validate location is provided
                if (!$latitude || !$longitude) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Location is required. Please enable location services and try again.'
                    ], 400);
                }

                // Validate location coordinates
                if (!is_numeric($latitude) || !is_numeric($longitude)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid location coordinates. Please try again.'
                    ], 400);
                }

                $clockIn  = strtotime($attendance->clock_in);
                $clockOut = strtotime($time);
                $workedSeconds = max($clockOut - $clockIn, 0);

                $attendance->clock_out = $time; // Record time immediately
                $attendance->early_leaving = $this->calculateEarlyLeaving($time, $date);

                // Calculate overtime
                $totalOvertimeSeconds = $workedSeconds - (8.5 * 3600); // 8.5 hours standard
                if ($totalOvertimeSeconds > 0) {
                    $attendance->overtime = gmdate('H:i:s', $totalOvertimeSeconds);
                } else {
                    $attendance->overtime = '00:00:00';
                }

                // Set location (mandatory - validated above)
                try {
                    $location = $this->geocodeCoordinates($latitude, $longitude);
                    $attendance->clock_out_latitude = $latitude;
                    $attendance->clock_out_longitude = $longitude;
                    $attendance->clock_out_accuracy = $accuracy;
                    $attendance->clock_out_location = $location;
                    $attendance->clock_out_location_captured_at = now();
                } catch (\Throwable $e) {
                    // Location geocoding failed - still save coordinates
                    $attendance->clock_out_latitude = $latitude;
                    $attendance->clock_out_longitude = $longitude;
                    $attendance->clock_out_accuracy = $accuracy;
                    $attendance->clock_out_location = 'Lat: ' . number_format($latitude, 5) . ', Lng: ' . number_format($longitude, 5);
                    $attendance->clock_out_location_captured_at = now();
                    \Log::warning('Location geocoding failed during punch-out', [
                        'error' => $e->getMessage()
                    ]);
                }

                $workedHours = $workedSeconds / 3600;
                $clockInTime = strtotime($attendance->clock_in);
                $halfDayThreshold = strtotime($date . ' 12:00:00');

                if ($clockInTime >= $halfDayThreshold || $workedHours < AttendanceEmployee::REQUIRED_WORKING_HOURS) {
                    $attendance->status = AttendanceEmployee::STATUS_HALF_DAY;
                } else {
                    $attendance->status = AttendanceEmployee::STATUS_PRESENT;
                }

                $attendance->save();

                // Check for Comp-Off earning on Week-Off day
                $dayName = \Carbon\Carbon::parse($date)->format('l');
                $employee = Auth::user()->employee;
                if ($employee && strtolower($employee->week_off_day) === strtolower($dayName)) {
                    // Award Comp-Off for any valid attendance status (Present, Half Day, or Single Punch In)
                    if ($attendance->status == AttendanceEmployee::STATUS_PRESENT || $attendance->status == AttendanceEmployee::STATUS_HALF_DAY || $attendance->status == AttendanceEmployee::STATUS_SINGLE_PUNCH) {
                        $this->addCompOff($employee->id, $date);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Punch Out successful.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid action request.'
            ], 400);
        }

    /**
     * Get location from IP address using free IP geolocation services
     * Falls back to multiple services if one fails
     */
    protected function getLocationFromIP($ip)
    {
        // Skip private/local IPs
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $services = [
            [
                'url' => "https://ipapi.co/{$ip}/json/",
                'parse' => function($data) {
                    if (isset($data['latitude']) && isset($data['longitude'])) {
                        return [
                            'latitude' => $data['latitude'],
                            'longitude' => $data['longitude'],
                            'address' => ($data['city'] ?? '') . ', ' . ($data['region'] ?? '') . ', ' . ($data['country_name'] ?? '')
                        ];
                    }
                    return null;
                }
            ],
            [
                'url' => "http://ip-api.com/json/{$ip}",
                'parse' => function($data) {
                    if ($data['status'] === 'success' && isset($data['lat']) && isset($data['lon'])) {
                        return [
                            'latitude' => $data['lat'],
                            'longitude' => $data['lon'],
                            'address' => ($data['city'] ?? '') . ', ' . ($data['regionName'] ?? '') . ', ' . ($data['country'] ?? '')
                        ];
                    }
                    return null;
                }
            ],
            [
                'url' => "https://freegeoip.app/json/{$ip}",
                'parse' => function($data) {
                    if (isset($data['latitude']) && isset($data['longitude'])) {
                        return [
                            'latitude' => $data['latitude'],
                            'longitude' => $data['longitude'],
                            'address' => ($data['city'] ?? '') . ', ' . ($data['region_name'] ?? '') . ', ' . ($data['country_name'] ?? '')
                        ];
                    }
                    return null;
                }
            ]
        ];

        foreach ($services as $service) {
            try {
                $client = new \GuzzleHttp\Client([
                    'timeout' => 5,
                    'connect_timeout' => 3,
                    'headers' => [
                        'User-Agent' => 'HRM-System/1.0 (attendance)',
                        'Accept' => 'application/json'
                    ]
                ]);

                $response = $client->get($service['url']);
                $data = json_decode($response->getBody(), true);

                if ($data) {
                    $location = $service['parse']($data);
                    if ($location) {
                        return $location;
                    }
                }
            } catch (\Throwable $e) {
                // Try next service
                \Log::debug('IP geolocation service failed', [
                    'service' => $service['url'],
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return null;
    }


    public function updateLocationFromIP(Request $request)
    {
        $attendanceId = $request->attendance_id;
        $type = $request->type; // 'in' or 'out'

        if (!$attendanceId || !in_array($type, ['in', 'out'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters.'
            ], 400);
        }

        $employeeId = Auth::user()->employee->id;
        $attendance = AttendanceEmployee::where('id', $attendanceId)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found.'
            ], 404);
        }

        try {
            // Get user's IP address
            $ip = $request->ip();
            
            // Handle forwarded IPs (if behind proxy/load balancer)
            if ($request->header('X-Forwarded-For')) {
                $ips = explode(',', $request->header('X-Forwarded-For'));
                $ip = trim($ips[0]);
            } elseif ($request->header('X-Real-IP')) {
                $ip = $request->header('X-Real-IP');
            }

            // Get location from IP using free IP geolocation service
            $location = $this->getLocationFromIP($ip);

            if ($location && isset($location['latitude']) && isset($location['longitude'])) {
                $latitude = $location['latitude'];
                $longitude = $location['longitude'];
                $address = $location['address'] ?? $this->geocodeCoordinates($latitude, $longitude);

                if ($type === 'in') {
                    // SESSION 2 check
                    if (!empty($attendance->clock_in_2) && (empty($attendance->clock_in_2_latitude) || $attendance->clock_in_2_latitude == 0)) {
                        $attendance->clock_in_2_latitude = $latitude;
                        $attendance->clock_in_2_longitude = $longitude;
                        $attendance->clock_in_2_location = $address . ' (IP-based)';
                        $attendance->clock_in_2_accuracy = null;
                        $attendance->clock_in_2_location_captured_at = now();
                        $attendance->save();
                    } else if (empty($attendance->clock_in_latitude)) {
                        $attendance->clock_in_latitude = $latitude;
                        $attendance->clock_in_longitude = $longitude;
                        $attendance->clock_in_location = $address . ' (IP-based)';
                        $attendance->clock_in_accuracy = null;
                        $attendance->clock_in_location_captured_at = now();
                        $attendance->save();
                    }
                } else {
                    // SESSION 2 check
                    if (!empty($attendance->clock_out_2) && (empty($attendance->clock_out_2_latitude) || $attendance->clock_out_2_latitude == 0)) {
                        $attendance->clock_out_2_latitude = $latitude;
                        $attendance->clock_out_2_longitude = $longitude;
                        $attendance->clock_out_2_location = $address . ' (IP-based)';
                        $attendance->clock_out_2_accuracy = null;
                        $attendance->clock_out_2_location_captured_at = now();
                        $attendance->save();
                    } else if (empty($attendance->clock_out_latitude)) {
                        $attendance->clock_out_latitude = $latitude;
                        $attendance->clock_out_longitude = $longitude;
                        $attendance->clock_out_location = $address . ' (IP-based)';
                        $attendance->clock_out_accuracy = null;
                        $attendance->clock_out_location_captured_at = now();
                        $attendance->save();
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Location updated from IP address.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Could not determine location from IP address.'
            ]);

        } catch (\Throwable $e) {
            \Log::warning('IP-based location update failed', [
                'attendance_id' => $attendanceId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Location update from IP failed.'
            ]);
        }
    }

    /**
     * Update location for existing attendance record
     * This endpoint is called asynchronously after punch-in/out to update location
     */
    public function updateLocation(Request $request)
    {
        $attendanceId = $request->attendance_id;
        $type = $request->type; // 'in' or 'out'
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $accuracy = $request->accuracy ?? null;
        $locationSource = $request->source ?? 'gps';

        if (!$attendanceId || !$latitude || !$longitude || !in_array($type, ['in', 'out'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters.'
            ], 400);
        }

        $employeeId = Auth::user()->employee->id;
        $attendance = AttendanceEmployee::where('id', $attendanceId)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found.'
            ], 404);
        }

        try {
            $location = $this->geocodeCoordinates($latitude, $longitude);
            $isGPS = strpos($locationSource, 'gps') === 0;
            $isIP = $locationSource === 'ip_based';

            if ($type === 'in') {
                // SESSION 2 check
                if (!empty($attendance->clock_in_2) && $attendance->clock_in_2 != '00:00:00' && (empty($attendance->clock_in_2_latitude) || $attendance->clock_in_2_latitude == 0)) {
                    $attendance->clock_in_2_latitude = $latitude;
                    $attendance->clock_in_2_longitude = $longitude;
                    $attendance->clock_in_2_accuracy = $accuracy;
                    $attendance->clock_in_2_location = $location;
                    $attendance->clock_in_2_location_captured_at = now();
                    $attendance->save();
                } else {
                    // Original priority logic for session 1
                    $existingHasGPS = !empty($attendance->clock_in_latitude) && $attendance->clock_in_accuracy !== null;
                    $existingIsIP = !empty($attendance->clock_in_latitude) && $attendance->clock_in_accuracy === null;
                    
                    $shouldUpdate = false;
                    if (empty($attendance->clock_in_latitude)) {
                        $shouldUpdate = true;
                    } elseif ($isGPS && !$existingHasGPS) {
                        $shouldUpdate = true;
                    } elseif ($isGPS && $existingHasGPS) {
                        if ($locationSource === 'gps_high' || $locationSource === 'gps_medium') {
                            $shouldUpdate = true;
                        }
                    } elseif ($isIP && !$existingHasGPS && !$existingIsIP) {
                        $shouldUpdate = true;
                    }

                    if ($shouldUpdate) {
                        $attendance->clock_in_latitude = $latitude;
                        $attendance->clock_in_longitude = $longitude;
                        $attendance->clock_in_accuracy = $accuracy;
                        $attendance->clock_in_location = $location;
                        $attendance->clock_in_location_captured_at = now();
                        $attendance->save();
                    }
                }
            } else {
                // SESSION 2 check
                if (!empty($attendance->clock_out_2) && $attendance->clock_out_2 != '00:00:00' && (empty($attendance->clock_out_2_latitude) || $attendance->clock_out_2_latitude == 0)) {
                    $attendance->clock_out_2_latitude = $latitude;
                    $attendance->clock_out_2_longitude = $longitude;
                    $attendance->clock_out_2_accuracy = $accuracy;
                    $attendance->clock_out_2_location = $location;
                    $attendance->clock_out_2_location_captured_at = now();
                    $attendance->save();
                } else {
                    // Original priority logic for session 1
                    $existingHasGPS = !empty($attendance->clock_out_latitude) && $attendance->clock_out_accuracy !== null;
                    $existingIsIP = !empty($attendance->clock_out_latitude) && $attendance->clock_out_accuracy === null;
                    
                    $shouldUpdate = false;
                    if (empty($attendance->clock_out_latitude)) {
                        $shouldUpdate = true;
                    } elseif ($isGPS && !$existingHasGPS) {
                        $shouldUpdate = true;
                    } elseif ($isGPS && $existingHasGPS) {
                        if ($locationSource === 'gps_high' || $locationSource === 'gps_medium') {
                            $shouldUpdate = true;
                        }
                    } elseif ($isIP && !$existingHasGPS && !$existingIsIP) {
                        $shouldUpdate = true;
                    }

                    if ($shouldUpdate) {
                        $attendance->clock_out_latitude = $latitude;
                        $attendance->clock_out_longitude = $longitude;
                        $attendance->clock_out_accuracy = $accuracy;
                        $attendance->clock_out_location = $location;
                        $attendance->clock_out_location_captured_at = now();
                        $attendance->save();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully.',
                'location' => $location
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Location update failed', [
                'attendance_id' => $attendanceId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Location update attempted.'
            ]);
        }
    }

    protected function geocodeCoordinates($latitude, $longitude)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 1.5,
                'connect_timeout' => 1.5,
                'headers' => [
                    'User-Agent' => 'HRM-System/1.0 (attendance)',
                    'Accept' => 'application/json'
                ]
            ]);

            $response = $client->get('https://nominatim.openstreetmap.org/reverse', [
                'query' => [
                    'format' => 'json',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (!empty($data['display_name'])) {
                return $data['display_name'];
            }

        } catch (\Throwable $e) {
            \Log::warning('Geocoding failed', [
                'lat' => $latitude,
                'lng' => $longitude,
                'error' => $e->getMessage()
            ]);
        }

        // ✅ HARD FALLBACK (NEVER NULL)
        return 'Lat: ' . number_format($latitude, 5) . ', Lng: ' . number_format($longitude, 5);
    }


        private function isLocationValid($latitude, $longitude, $accuracy)
        {
            // Get company location settings
            $companyLatitude = Utility::getValByName('company_latitude');
            $companyLongitude = Utility::getValByName('company_longitude');
            $allowedRadius = Utility::getValByName('allowed_radius') ?: 100; // meters
        
            // If company location isn't set, skip validation
            if (empty($companyLatitude) || empty($companyLongitude)) {
                return true;
            }
        
            // Check if accuracy is too low (more than 50 meters)
            if ($accuracy > 50) {
                return false;
            }
        
            // Calculate distance between employee and company location
            $distance = $this->calculateDistance(
                $companyLatitude,
                $companyLongitude,
                $latitude,
                $longitude
            );
        
            return $distance <= $allowedRadius;
        }
        

        private function calculateDistance($lat1, $lon1, $lat2, $lon2)
        {
            $earthRadius = 6371000; // meters
        
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
        
            $a = sin($dLat/2) * sin($dLat/2) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                sin($dLon/2) * sin($dLon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
            return $earthRadius * $c;
        }

        function getAddressFromCoordinates($latitude, $longitude) {
        if (empty($latitude) || empty($longitude)) {
            return "Location not available";
        }
        
        // Use Google Maps Geocoding API
        $apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // You need to get this from Google
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";
        
        // Make API request
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        // Check if we got a valid response
        if ($data['status'] === 'OK' && !empty($data['results'][0]['formatted_address'])) {
            return $data['results'][0]['formatted_address'];
        }
        
        return "Location not available";
    }

        public function calendar(Request $request)
        {
            if (\Auth::user()->can('attendance.calendar.view.own') || \Auth::user()->can('attendance.calendar.view.all')) {
                $employees = [];
                $selectedEmployee = null;
                
                // Get terminated employee IDs
                $terminatedEmployeeIds = Termination::pluck('employee_id')->toArray();
                
                // Exclude terminated employees and non-employee users (Director, Hr) from the list
                $allEmployees = Employee::where('created_by', \Auth::user()->creatorId())
                    ->whereNotIn('id', $terminatedEmployeeIds)
                    ->whereHas('user', function($query) {
                        $query->where('type', 'employee');
                    })
                    ->orderBy('employee_id', 'asc')
                    ->get();

                // For employee users - automatically select their own record
                if (\Auth::user()->type == 'employee' && (!\Auth::user()->can('attendance.calendar.view.all') || $request->has('own'))) {
                    $selectedEmployee = Employee::where('user_id', \Auth::user()->id)->first();
                    if ($selectedEmployee) {
                        $employees = [$selectedEmployee];
                    }
                } 
                // For company users - check if employee is selected
                else {
                    if ($request->has('employee_id') && $request->employee_id) {
                        $selectedEmployee = Employee::find($request->employee_id);
                        if ($selectedEmployee) {
                            $employees = [$selectedEmployee];
                        }
                    }
                }

                // Get current month and year
                $currentMonth = request()->input('month', date('m'));
                $currentYear = request()->input('year', date('Y'));

                $currentDate = \Carbon\Carbon::create($currentYear, $currentMonth, 1);
                $previousMonth = $currentDate->copy()->subMonth();
                $nextMonth = $currentDate->copy()->addMonth();

                $attendanceData = [];

                // Only process data if we have a selected employee
                if ($selectedEmployee) {
                    foreach ($employees as $employee) {
                        // Get all attendance records (no month filter)
                        $attendances = DB::table('attendance_employees')
                            ->where('employee_id', $employee->id)
                            ->get()
                            ->map(function ($item) {
                                $date = \Carbon\Carbon::parse($item->date)->format('Y-m-d');
                                return [
                                    'date' => $date,
                                    'clock_in' => $item->clock_in,
                                    'clock_out' => $item->clock_out,
                                    'status' => $item->status
                                ];
                            });

                        // Get all approved leaves (no month filter)
                        $leaves = LocalLeave::where('employee_id', $employee->id)
                            ->where('status', 'Approved')
                            ->with('leaveType')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'start_date' => \Carbon\Carbon::parse($item->start_date)->format('Y-m-d'),
                                    'end_date' => \Carbon\Carbon::parse($item->end_date)->format('Y-m-d'),
                                    'leave_reason' => $item->leave_reason,
                                    'leave_type' => $item->leaveType ? $item->leaveType->title : 'Unknown'
                                ];
                            });

                        $weekOffDay = strtolower($employee->week_off_day); // e.g. 'sunday'

                        $employeeData = [];

                        // Mark 'present' or 'single_punch' from attendance records
                        foreach ($attendances as $attendance) {
                            $isSinglePunch = empty($attendance['clock_out']) || 
                                            $attendance['clock_out'] == '00:00:00' || 
                                            $attendance['clock_out'] == null;
                            
                            // Calculate late mark dynamically for existing data
                            $lateTime = $this->calculateLateMark($attendance['clock_in'], $attendance['date']);
                            $isLate = $lateTime !== '00:00:00';

                            // Calculate early leaving dynamically for existing data
                            $earlyLeavingTime = $this->calculateEarlyLeaving($attendance['clock_out'], $attendance['date']);
                            $isEarlyLeaving = $earlyLeavingTime !== '00:00:00';

                            $type = $isSinglePunch ? 'single_punch' : 'present';
                            if ($attendance['status'] === 'Half Day') {
                                $type = 'half_day';
                            }

                            $employeeData[$attendance['date']] = [
                                'type' => $type,
                                'clock_in' => $attendance['clock_in'],
                                'clock_out' => $attendance['clock_out'],
                                'is_late' => $isLate,
                                'late_time' => $lateTime,
                                'is_early_leaving' => $isEarlyLeaving,
                                'early_leaving_time' => $earlyLeavingTime,
                                'raw_status' => $attendance['status']
                            ];
                        }

                        // Mark 'leave' days
                        foreach ($leaves as $leave) {
                            $start = \Carbon\Carbon::parse($leave['start_date']);
                            $end = \Carbon\Carbon::parse($leave['end_date']);

                            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                                $formattedDate = $date->format('Y-m-d');

                                if (!isset($employeeData[$formattedDate])) {
                                    $employeeData[$formattedDate] = [
                                        'type' => 'leave',
                                        'reason' => $leave['leave_reason'],
                                        'leave_type' => $leave['leave_type']
                                    ];
                                }
                            }
                        }

                        // Get all earned comp-offs
                        $earnedCompOffs = \App\Models\CompOffLeave::where('employees_id', $employee->id)
                            ->pluck('comp_off_date')
                            ->toArray();
                            
                        sort($earnedCompOffs); // Sort chronologically

                        $compOffLeaveTypeId = \App\Models\LeaveType::where('title', 'Comp-Off')->value('id') ?? 0;
                        $totalUsedCompOffs = \App\Models\Leave::where('employee_id', $employee->id)
                            ->where('leave_type_id', $compOffLeaveTypeId)
                            ->where('status', 'Approved')
                            ->sum('total_leave_days');
                        
                        $usedCount = $totalUsedCompOffs;
                        
                        $totalEarned = count($earnedCompOffs);
                        $totalRemaining = $totalEarned - $totalUsedCompOffs;

                        foreach ($earnedCompOffs as $eoDate) {
                            if (!isset($employeeData[$eoDate])) {
                                $employeeData[$eoDate] = ['type' => 'present']; // Default base type if missing
                            }
                            
                            $employeeData[$eoDate]['earned_comp_off'] = true;
                            
                            if ($usedCount > 0) {
                                $employeeData[$eoDate]['used_comp_off'] = true;
                                $usedCount--;
                            } else {
                                $employeeData[$eoDate]['used_comp_off'] = false;
                            }
                        }

                        // Fill in 'week_off' and 'absent' for all dates in the calendar view
                        // We'll process 3 months to ensure smooth navigation
                        $startRange = $currentDate->copy()->subMonth(); // Show previous month
                        $endRange = $currentDate->copy()->addMonth(); // Show next month
                        
                        for ($date = $startRange->copy(); $date->lte($endRange); $date->addDay()) {
                            $dateFormatted = $date->format('Y-m-d');
                            $dayName = strtolower($date->format('l')); // e.g. 'sunday'

                            if (!isset($employeeData[$dateFormatted])) {
                                if ($weekOffDay && $dayName === $weekOffDay) {
                                    $employeeData[$dateFormatted] = ['type' => 'week_off'];
                                } elseif (!$weekOffDay && in_array($dayName, ['saturday', 'sunday'])) {
                                    $employeeData[$dateFormatted] = ['type' => 'week_off'];
                                } elseif ($date->lte(\Carbon\Carbon::today())) {
                                    $employeeData[$dateFormatted] = ['type' => 'absent'];
                                }
                                // else: future dates remain unmarked
                            }
                        }

                        // Sort data by date
                        ksort($employeeData);

                        $elType = \App\Models\LeaveType::where('title', 'Earned Leave')->first();
                        $slType = \App\Models\LeaveType::where('title', 'Sick Leave')->first();
                        
                        $elTotalEarned = 0; $elTotalUsed = 0; $elTotalRemaining = 0;
                        $slTotalEarned = 0; $slTotalUsed = 0; $slTotalRemaining = 0;

                        if ($elType) {
                            $elBalance = \App\Models\EmployeeLeaveBalance::where('employee_id', $employee->id)
                                ->where('leave_type_id', $elType->id)
                                ->where('year', $currentYear)
                                ->where('month', $currentMonth)
                                ->first();
                            if ($elBalance) {
                                $elTotalEarned = $elBalance->allocated_days + $elBalance->carry_forward_days;
                                $elTotalUsed = $elBalance->used_days;
                                $elTotalRemaining = $elTotalEarned - $elTotalUsed;
                            }
                        }

                        if ($slType) {
                            $slBalance = \App\Models\EmployeeLeaveBalance::where('employee_id', $employee->id)
                                ->where('leave_type_id', $slType->id)
                                ->where('year', $currentYear)
                                ->where('month', $currentMonth)
                                ->first();
                            if ($slBalance) {
                                $slTotalEarned = $slBalance->allocated_days + $slBalance->carry_forward_days;
                                $slTotalUsed = $slBalance->used_days;
                                $slTotalRemaining = $slTotalEarned - $slTotalUsed;
                            }
                        }

                        $attendanceData[$employee->id] = [
                            'name' => $employee->full_name,
                            'week_off' => $weekOffDay,
                            'total_earned_comp_offs' => $totalEarned,
                            'total_used_comp_offs' => $totalUsedCompOffs,
                            'total_remaining_comp_offs' => $totalRemaining,
                            'el_earned' => $elTotalEarned,
                            'el_used' => $elTotalUsed,
                            'el_remaining' => $elTotalRemaining,
                            'sl_earned' => $slTotalEarned,
                            'sl_used' => $slTotalUsed,
                            'sl_remaining' => $slTotalRemaining,
                            'data' => $employeeData
                        ];
                    }
                }

                return view('attendance.calendar', [
                    'attendanceData' => $attendanceData,
                    'currentMonth' => $currentMonth,
                    'currentYear' => $currentYear,
                    'previousMonth' => $previousMonth->format('m'),
                    'previousYear' => $previousMonth->format('Y'),
                    'nextMonth' => $nextMonth->format('m'),
                    'nextYear' => $nextMonth->format('Y'),
                    'allEmployees' => $allEmployees,
                    'selectedEmployee' => $selectedEmployee
                ]);
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        public function export(Request $request)
        {
            if (\Auth::user()->can('Manage Attendance')) {
                // Get the same filtered data as the index method
                if (\Auth::user()->type == 'employee' && (!\Auth::user()->isHR() || $request->has('own'))) {
                    $emp = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
                    $query = AttendanceEmployee::where('employee_id', $emp);
                    $baseEmployeeIds = collect([$emp])->filter();
                } else {
                    $employee = Employee::select('id')->where('created_by', \Auth::user()->creatorId())
                        ->whereHas('user', function($query) {
                            $query->where('type', 'employee');
                        });
                    
                    if (!empty($request->branch)) {
                        $employee->where('branch_id', $request->branch);
                    }

                    if (!empty($request->department)) {
                        $employee->where('department_id', $request->department);
                    }

                    if (!empty($request->employee)) {
                        $employee->where('id', $request->employee);
                    }

                    $baseEmployeeIds = $employee->get()->pluck('id');
                    $query = AttendanceEmployee::whereIn('employee_id', $baseEmployeeIds);
                }
                
                // Get all dates based on export mode
                $dates = [];
                $tz = \App\Models\Utility::getValByName('timezone') ?: config('app.timezone');
                $today = \Carbon\Carbon::now($tz)->startOfDay();

                if ($request->export_mode == 'daily') {
                    $dates = [$request->export_date ?? date('Y-m-d')];
                } elseif ($request->export_mode == 'specific') {
                    $year = $request->export_year ?? date('Y');
                    $selectedMonths = $request->selected_months ?? [];
                    sort($selectedMonths);
                    foreach ($selectedMonths as $m) {
                        $monthStr = sprintf('%02d', $m);
                        $start = \Carbon\Carbon::createFromDate($year, $monthStr, 1);
                        $end = $start->copy()->endOfMonth();
                        
                        if ($start->lte($today) && $end->gt($today)) {
                            $end = $today;
                        }
                        
                        $current = $start->copy();
                        while ($current <= $end) {
                            $dates[] = $current->format('Y-m-d');
                            $current->addDay();
                        }
                    }
                } elseif ($request->export_mode == 'range') {
                    $year = $request->export_year ?? date('Y');
                    $startMonth = (int) $request->start_month;
                    $endMonth = (int) $request->end_month;
                    if ($startMonth > $endMonth) {
                        $temp = $startMonth;
                        $startMonth = $endMonth;
                        $endMonth = $temp;
                    }
                    $start_date = date($year . '-' . sprintf('%02d', $startMonth) . '-01');
                    $end_date = date('Y-m-t', strtotime('01-' . sprintf('%02d', $endMonth) . '-' . $year));

                    $startCarbon = \Carbon\Carbon::parse($start_date, $tz)->startOfDay();
                    $endCarbon = \Carbon\Carbon::parse($end_date, $tz)->startOfDay();
                    if ($startCarbon->lte($today) && $endCarbon->gt($today)) {
                        $end_date = $today->format('Y-m-d');
                    }
                    
                    $current = \Carbon\Carbon::parse($start_date);
                    $end = \Carbon\Carbon::parse($end_date);
                    while ($current <= $end) {
                        $dates[] = $current->format('Y-m-d');
                        $current->addDay();
                    }
                } else {
                    // Existing monthly behavior:
                    if ($request->type == 'monthly' && !empty($request->month)) {
                        $month = date('m', strtotime($request->month));
                        $year = date('Y', strtotime($request->month));
                        $start_date = date($year . '-' . $month . '-01');
                        $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));
                    } else {
                        $month = date('m');
                        $year = date('Y');
                        $start_date = date($year . '-' . $month . '-01');
                        $end_date = date('Y-m-t', strtotime('01-' . $month . '-' . $year));
                    }

                    $startCarbon = \Carbon\Carbon::parse($start_date, $tz)->startOfDay();
                    $endCarbon = \Carbon\Carbon::parse($end_date, $tz)->startOfDay();
                    if ($startCarbon->lte($today) && $endCarbon->gt($today)) {
                        $end_date = $today->format('Y-m-d');
                    }
                    
                    $current = \Carbon\Carbon::parse($start_date);
                    $end = \Carbon\Carbon::parse($end_date);
                    while ($current <= $end) {
                        $dates[] = $current->format('Y-m-d');
                        $current->addDay();
                    }
                }

                if (!empty($dates)) {
                    $start_date = min($dates);
                    $end_date = max($dates);
                } else {
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d');
                }

                if (!empty($dates)) {
                    $query->whereIn('date', $dates);
                } else {
                    $query->whereRaw('1 = 0');
                }

                $attendances = $query->orderBy('date', 'asc')
                                    ->orderBy('clock_in', 'asc')
                                    ->get();

                // Get all employees in the export set:
                $attendanceEmployeeIds = $attendances->pluck('employee_id')->unique()->values();

                $leavesForEmployeeList = LocalLeave::query()
                    ->whereIn('employee_id', $baseEmployeeIds)
                    ->where('status', 'Approved')
                    ->where(function ($q) use ($start_date, $end_date) {
                        $q->whereBetween('start_date', [$start_date, $end_date])
                        ->orWhereBetween('end_date', [$start_date, $end_date])
                        ->orWhere(function ($q2) use ($start_date, $end_date) {
                            $q2->where('start_date', '<=', $start_date)
                                ->where('end_date', '>=', $end_date);
                        });
                    })
                    ->pluck('employee_id')
                    ->unique()
                    ->values();

                $employeeIds = $attendanceEmployeeIds->merge($leavesForEmployeeList)->unique()->values();

                $employees = Employee::whereIn('id', $employeeIds)
                                    ->with('user')
                                    ->get();

                // Group attendance by employee and date
                $attendanceData = [];
                foreach ($attendances as $attendance) {
                    $lateTime = $this->calculateLateMark($attendance->clock_in, $attendance->date);
                    $earlyLeavingTime = $this->calculateEarlyLeaving($attendance->clock_out, $attendance->date);
                    $attendanceData[$attendance->employee_id][$attendance->date] = [
                        'status' => $attendance->status,
                        'clock_in' => $attendance->clock_in,
                        'clock_out' => $attendance->clock_out,
                        'total' => $this->calculateWorkedHours($attendance->clock_in, $attendance->clock_out),
                        'late' => $lateTime,
                        'early_leaving' => $earlyLeavingTime
                    ];
                }

                // Build leave/comp-off codes by employee+date for export status mapping
                $leaveCodes = [];
                $codePriority = ['CO' => 4, 'LOP' => 3, 'SL' => 2, 'EL' => 1];

                $leaves = LocalLeave::query()
                    ->with('leaveType')
                    ->whereIn('employee_id', $employeeIds)
                    ->where('status', 'Approved')
                    ->where(function ($q) use ($start_date, $end_date) {
                        $q->whereBetween('start_date', [$start_date, $end_date])
                        ->orWhereBetween('end_date', [$start_date, $end_date])
                        ->orWhere(function ($q2) use ($start_date, $end_date) {
                            $q2->where('start_date', '<=', $start_date)
                                ->where('end_date', '>=', $end_date);
                        });
                    })
                    ->get();

                foreach ($leaves as $leave) {
                    $empId = (int) $leave->employee_id;
                    $typeTitle = strtolower(trim(optional($leave->leaveType)->title ?? ''));
                    $duration = strtolower(trim($leave->leave_duration_type ?? 'full_day'));

                    $code = 'EL';
                    if ($typeTitle === 'comp-off' || $typeTitle === 'comp off' || str_contains($typeTitle, 'comp')) {
                        $code = 'CO';
                    } elseif ($typeTitle === 'leave without pay' || str_contains($typeTitle, 'without pay')) {
                        $code = 'LOP';
                    } elseif ($typeTitle === 'sick leave' || str_contains($typeTitle, 'sick')) {
                        $code = 'SL';
                    } elseif ($typeTitle === 'earned leave' || str_contains($typeTitle, 'earned')) {
                        $code = 'EL';
                    } elseif ($duration === 'half_day') {
                        // Fallback: if leave type is not specified, use duration
                        $code = 'SL';
                    } else {
                        // Default to EL for full day leaves when type is not specified
                        $code = 'EL';
                    }

                    $leaveStart = \Carbon\Carbon::parse($leave->start_date);
                    $leaveEnd = \Carbon\Carbon::parse($leave->end_date);
                    $periodStart = $leaveStart->lt(\Carbon\Carbon::parse($start_date)) ? \Carbon\Carbon::parse($start_date) : $leaveStart;
                    $periodEnd = $leaveEnd->gt(\Carbon\Carbon::parse($end_date)) ? \Carbon\Carbon::parse($end_date) : $leaveEnd;

                    $datesSet = array_flip($dates);
                    for ($d = $periodStart->copy(); $d->lte($periodEnd); $d->addDay()) {
                        $dateKey = $d->toDateString();
                        if (isset($datesSet[$dateKey])) {
                            $existing = $leaveCodes[$empId][$dateKey] ?? null;
                            if (!$existing) {
                                $leaveCodes[$empId][$dateKey] = $code;
                                continue;
                            }
                            if (($codePriority[$code] ?? 0) > ($codePriority[$existing] ?? 0)) {
                                $leaveCodes[$empId][$dateKey] = $code;
                            }
                        }
                    }
                }

                // Final status codes per employee+date for the Excel export
                $statusCodes = [];
                foreach ($employees as $emp) {
                    $empId = (int) $emp->id;
                    $weekOff = strtolower(trim((string) ($emp->week_off_day ?? '')));

                    foreach ($dates as $date) {
                        // Never mark future dates in export (extra safety; dates are already clamped)
                        if (\Carbon\Carbon::parse($date, $tz)->startOfDay()->gt($today)) {
                            $statusCodes[$empId][$date] = '';
                            continue;
                        }

                        $dayName = strtolower(\Carbon\Carbon::parse($date)->format('l')); // monday, tuesday...
                        $isWeekOff = $weekOff !== ''
                            ? ($dayName === strtolower($weekOff))
                            : in_array($dayName, ['saturday', 'sunday'], true);

                        $att = $attendanceData[$empId][$date] ?? null;
                        $leaveCode = $leaveCodes[$empId][$date] ?? null;

                        $clockIn = $att['clock_in'] ?? null;
                        $clockOut = $att['clock_out'] ?? null;
                        $hasPunch = !empty($clockIn) && $clockIn !== '00:00:00';
                        $isToday = \Carbon\Carbon::parse($date, $tz)->isSameDay($today);

                        // Priority order: Punched (P/LM/SP) > WO > Other leaves (EL, CO, LOP) > Absent
                        
                        // Priority 0: Check manual status from AttendanceEmployee
                        $manualStatus = $att['status'] ?? null;
                        if (!empty($manualStatus) && $manualStatus !== 'Present') {
                            if ($manualStatus === 'Sick Leave') {
                                $statusCodes[$empId][$date] = 'SL';
                                continue;
                            } elseif ($manualStatus === 'Earned Leave') {
                                $statusCodes[$empId][$date] = 'EL';
                                continue;
                            } elseif (str_contains(strtolower($manualStatus), 'comp')) {
                                $statusCodes[$empId][$date] = 'CO';
                                continue;
                            } elseif ($manualStatus === 'Absent' || $manualStatus === 'Leave Without Pay') {
                                $statusCodes[$empId][$date] = 'LOP';
                                continue;
                            } elseif ($manualStatus === 'Half Day / Single Punch') {
                                $statusCodes[$empId][$date] = 'SP';
                                continue;
                            } elseif ($manualStatus === 'Late') {
                                $statusCodes[$empId][$date] = 'LM';
                                continue;
                            } elseif ($manualStatus === 'Week Off') {
                                $statusCodes[$empId][$date] = 'WO';
                                continue;
                            }
                        }

                        // Priority 1: If employee actually punched
                        if ($hasPunch) {
                            $lateTime = $att['late'] ?? '00:00:00';
                            if ($lateTime !== '00:00:00') {
                                $statusCodes[$empId][$date] = 'LM';
                            } else {
                                if (empty($clockOut) || $clockOut === '00:00:00') {
                                    $statusCodes[$empId][$date] = ($leaveCode === 'SL') ? 'SL' : 'SP';
                                } else {
                                    $statusCodes[$empId][$date] = ($leaveCode === 'SL') ? 'SL' : 'P';
                                }
                            }
                            continue;
                        }

                        // Priority 2: Check for Week Off if no punch
                        if ($isWeekOff) {
                            $statusCodes[$empId][$date] = 'WO';
                            continue;
                        }

                        // Priority 3: Check for leave codes (EL, CO, LOP, SL)
                        if (!empty($leaveCode)) {
                            $statusCodes[$empId][$date] = $leaveCode;
                            continue;
                        }

                        // For TODAY only: no punch and no leave yet
                        if ($isToday) {
                            $statusCodes[$empId][$date] = '';
                            continue;
                        }

                        // Default absent
                        $statusCodes[$empId][$date] = 'LOP';
                    }
                }

                // Collect leave details for each employee (EL, SL, CO)
                // This will be used to display leaves in the Excel export
                $leaveDetails = [];
                foreach ($leaves as $leave) {
                    $empId = (int) $leave->employee_id;
                    $typeTitle = strtolower(trim(optional($leave->leaveType)->title ?? ''));
                    $duration = strtolower(trim($leave->leave_duration_type ?? 'full_day'));

                    $code = 'EL';
                    if ($typeTitle === 'comp-off' || $typeTitle === 'comp off' || str_contains($typeTitle, 'comp')) {
                        $code = 'CO';
                    } elseif ($typeTitle === 'sick leave' || str_contains($typeTitle, 'sick')) {
                        $code = 'SL';
                    } elseif ($typeTitle === 'earned leave' || str_contains($typeTitle, 'earned')) {
                        $code = 'EL';
                    } elseif ($duration === 'half_day') {
                        $code = 'SL';
                    } else {
                        $code = 'EL';
                    }

                    // Only collect EL, SL, CO leaves (exclude LOP)
                    if (in_array($code, ['EL', 'SL', 'CO'])) {
                        $leaveStart = \Carbon\Carbon::parse($leave->start_date);
                        $leaveEnd = \Carbon\Carbon::parse($leave->end_date);
                        $periodStart = $leaveStart->lt(\Carbon\Carbon::parse($start_date)) ? \Carbon\Carbon::parse($start_date) : $leaveStart;
                        $periodEnd = $leaveEnd->gt(\Carbon\Carbon::parse($end_date)) ? \Carbon\Carbon::parse($end_date) : $leaveEnd;

                        // Find employee to check week off
                        $employee = $employees->firstWhere('id', $empId);
                        $weekOff = $employee ? strtolower(trim((string) ($employee->week_off_day ?? ''))) : '';

                        for ($d = $periodStart->copy(); $d->lte($periodEnd); $d->addDay()) {
                            $dateKey = $d->toDateString();
                            
                            // Only count if it's in our selected export dates
                            if (isset($datesSet[$dateKey])) {
                                // Check if this date is a Week Off (WO takes priority, so don't show leave in leaves table)
                                $dayName = strtolower($d->format('l'));
                                $isWeekOff = ($weekOff === $dayName);
                                
                                // Only add if not a Week Off day (WO days are shown in status, not in leaves section)
                                if (!$isWeekOff) {
                                    if (!isset($leaveDetails[$empId][$code])) {
                                        $leaveDetails[$empId][$code] = [];
                                    }
                                    $leaveDetails[$empId][$code][] = $dateKey;
                                }
                            }
                        }
                    }
                }

                // Fetch saved final payable salary values
                $selMonth = (int)date('m', strtotime($start_date));
                $selYear = (int)date('Y', strtotime($start_date));
                $savedPayableDays = EmployeePayableDay::whereIn('employee_id', $employeeIds)
                    ->where('month', $selMonth)
                    ->where('year', $selYear)
                    ->get()
                    ->keyBy('employee_id');

                // Calculate payable days totals for each employee
                // Total Payable Days = Present (P) + Earned Leave (EL) + Sick Leave (SL) + Weekly Off (WO) + Comp Off (CO)
                // Note: SL counts as 0.5 days only if employee punched in and worked < 8.5 hours
                $payableDaysTotals = [];
                foreach ($employees as $employee) {
                    $empId = (int) $employee->id;
                    $presentDays = 0;
                    $lmDays = 0;
                    $lopDays = 0;
                    $elDays = 0;
                    $slDays = 0; // This will be in half-days (0.5 increments)
                    $woDays = 0;
                    $coDays = 0;
                    
                    if (isset($statusCodes[$empId])) {
                        foreach ($statusCodes[$empId] as $date => $code) {
                            // Skip empty codes (future dates)
                            if (empty($code)) {
                                continue;
                            }
                            
                            switch ($code) {
                                case 'P':
                                case 'SP': // Count Single Punch as Present (usually)
                                    // Present: Always count as 1 full day
                                    $presentDays++;
                                    break;
                                case 'LM': // Count Late Mark separately
                                    // Late Mark: Count as 1 full day (also counts as present for payable)
                                    $lmDays++;
                                    $presentDays++; // Also count as present for payable days
                                    break;
                                case 'LOP':
                                    // Loss of Pay: Count as 1 full day
                                    $lopDays++;
                                    break;
                                case 'EL':
                                    // Earned Leave: Count as 1 full day
                                    $elDays++;
                                    break;
                                case 'SL':
                                    // Sick Leave: Count as 1 full day
                                    // It is typically a fully paid leave day.
                                    $slDays += 1;
                                    break;
                                case 'WO':
                                    // Weekly Off: Count as 1 full day
                                    $woDays++;
                                    break;
                                case 'CO':
                                    // Comp Off: Count as 1 full day
                                    $coDays++;
                                    break;
                            }
                        }
                    }
                    
                    // Calculate total: P + EL + SL + WO + CO
                    $totalPayableDays = $presentDays + $elDays + $slDays + $woDays + $coDays;
                    
                    $payableDaysTotals[$empId] = [
                        'present' => $presentDays,
                        'lm' => $lmDays,
                        'lop' => $lopDays,
                        'wo' => $woDays,
                        'el' => $elDays,
                        'sl' => $slDays,
                        'co' => $coDays,
                        'total' => $totalPayableDays,
                        'final_salary' => isset($savedPayableDays[$empId]) ? $savedPayableDays[$empId]->final_payable_salary : $totalPayableDays
                    ];
                }

                // Generate Excel file
                $fileName = 'attendance_' . date('Y-m-d') . '.xlsx';
                
                return \Excel::download(new class($dates, $employees, $attendanceData, $start_date, $end_date, $statusCodes, $payableDaysTotals, $leaveDetails) implements \Maatwebsite\Excel\Concerns\FromView, \Maatwebsite\Excel\Concerns\WithStyles {
                    private $dates;
                    private $employees;
                    private $attendanceData;
                    private $start_date;
                    private $end_date;
                    private $statusCodes;
                    private $payableDaysTotals;
                    private $leaveDetails;

                    public function __construct($dates, $employees, $attendanceData, $start_date, $end_date, $statusCodes, $payableDaysTotals, $leaveDetails)
                    {
                        $this->dates = $dates;
                        $this->employees = $employees;
                        $this->attendanceData = $attendanceData;
                        $this->start_date = $start_date;
                        $this->end_date = $end_date;
                        $this->statusCodes = $statusCodes;
                        $this->payableDaysTotals = $payableDaysTotals;
                        $this->leaveDetails = $leaveDetails;
                    }

                    public function view(): \Illuminate\View\View
                    {
                        return view('attendance.export', [
                            'dates' => $this->dates,
                            'employees' => $this->employees,
                            'attendanceData' => $this->attendanceData,
                            'statusCodes' => $this->statusCodes,
                            'start_date' => $this->start_date,
                            'end_date' => $this->end_date,
                            'payableDaysTotals' => $this->payableDaysTotals,
                            'leaveDetails' => $this->leaveDetails
                        ]);
                    }

                    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
                    {
                        // Apply borders to all cells
                        // Main table columns (dates + 1 for label column)
                        $mainTableColumns = count($this->dates) + 1;
                        // Summary table is separate, so we need to account for both tables
                        // Estimate: main table + spacing + summary table (8 columns: Present, LM, LOP, WO, EL, SL, CO, Total)
                        $lastColumn = $mainTableColumns + 10;
                        $monthsCount = count(array_unique(array_map(function($date) {
                            return substr($date, 0, 7);
                        }, $this->dates)));
                        
                        $lastRow = (count($this->employees) * (2 + ($monthsCount * 6))) + 2;
                        
                        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumn) . $lastRow)
                            ->getBorders()
                            ->getAllBorders()
                            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                        
                        // Center align all cells
                        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumn) . $lastRow)
                            ->getAlignment()
                            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    }
                }, $fileName);
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }

        protected function getLeaveStatus($employeeId, $date)
        {
            $leave = LocalLeave::with('leaveType')
                ->where('employee_id', $employeeId)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->where('status', 'Approved')
                ->first();

            if (!$leave) return null;

            $typeTitle = strtolower(trim($leave->leaveType->title ?? ''));
            if ($typeTitle === 'comp-off' || str_contains($typeTitle, 'comp')) return 'CO';
            if ($typeTitle === 'sick leave' || str_contains($typeTitle, 'sick')) return 'SL';
            if ($typeTitle === 'earned leave' || str_contains($typeTitle, 'earned')) return 'EL';
            if (str_contains($typeTitle, 'without pay')) return 'LOP';
            
            return 'EL'; // Default
        }

        private function calculateWorkedHours($clockIn, $clockOut)
        {
            if ($clockIn == '00:00:00' || $clockOut == '00:00:00') {
                return '00:00';
            }
            
            $start = \Carbon\Carbon::parse($clockIn);
            $end = \Carbon\Carbon::parse($clockOut);
            
            $diff = $start->diff($end);
            
            return sprintf('%02d:%02d', $diff->h, $diff->i);
        }

        /**
         * Calculate worked hours as decimal (for comparison with REQUIRED_WORKING_HOURS)
         */
        private function calculateWorkedHoursDecimal($clockIn, $clockOut)
        {
            if ($clockIn == '00:00:00' || $clockOut == '00:00:00') {
                return 0;
            }
            
            // Parse times - assuming same day
            $start = \Carbon\Carbon::parse($clockIn);
            $end = \Carbon\Carbon::parse($clockOut);
            
            // If end time is earlier than start (crossed midnight), add 24 hours
            if ($end->lt($start)) {
                $end->addDay();
            }
            
            $workedSeconds = $end->diffInSeconds($start);
            $workedHours = $workedSeconds / 3600;
            
            return $workedHours;
        }


        protected function calculateAttendanceStatus($clockIn, $clockOut, $date)
        {
            // If no clock in at all, return Absent
            if (empty($clockIn) || $clockIn == '00:00:00') {
                return 'Absent';
            }
            
            // If clocked in but not out, return Single Punch In
            if (empty($clockOut) || $clockOut == '00:00:00') {
                return 'Single Punch In';
            }
            
            // Calculate total worked time
            $start = \Carbon\Carbon::parse($date . ' ' . $clockIn);
            $end = \Carbon\Carbon::parse($date . ' ' . $clockOut);
            $totalMinutes = $end->diffInMinutes($start);
            
            // Determine status based on worked hours
            if ($totalMinutes >= 510) { // 8.5 hours = 510 minutes
                return 'Present';
            } elseif ($totalMinutes >= 270) { // 4.5 hours = 270 minutes
                return 'Half Day';
            } else {
                return 'Absent';
            }
        }

        /**
         * Calculate late mark based on office timings
         * Monday-Friday: Late after 10:40 AM
         * Saturday-Sunday: Late after 10:10 AM
         * 
         * @param string $clockIn Time in H:i:s format
         * @param string $date Date in Y-m-d format
         * @return string Late duration in H:i:s format (00:00:00 if not late)
         */
        protected function calculateLateMark($clockIn, $date)
        {
            // If no clock in, return no late mark
            if (empty($clockIn) || $clockIn == '00:00:00') {
                return '00:00:00';
            }

            // Parse the date to get day of week
            $dateCarbon = \Carbon\Carbon::parse($date);
            $dayOfWeek = $dateCarbon->dayOfWeek; // 0 = Sunday, 6 = Saturday
            
            // Parse clock in time
            $clockInTime = \Carbon\Carbon::parse($date . ' ' . $clockIn);
            
            // Determine late threshold based on day of week
            // Monday (1) to Friday (5): 10:40 AM
            // Saturday (6) and Sunday (0): 10:10 AM
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                // Weekday: Late after 10:40 AM
                $lateThreshold = \Carbon\Carbon::parse($date . ' 10:40:00');
            } else {
                // Weekend: Late after 10:10 AM
                $lateThreshold = \Carbon\Carbon::parse($date . ' 10:10:00');
            }
            
            // Check if employee is late
            if ($clockInTime->gt($lateThreshold)) {
                // Calculate late duration
                $lateSeconds = $clockInTime->diffInSeconds($lateThreshold);
                return gmdate('H:i:s', $lateSeconds);
            }
            
            return '00:00:00';
        }

        /**
         * Calculate early leaving based on office timings
         * Office Out Time: 7:00 PM (19:00:00)
         * 
         * @param string $clockOut Time in H:i:s format
         * @param string $date Date in Y-m-d format
         * @return string Early leaving duration in H:i:s format (00:00:00 if not early)
         */
        protected function calculateEarlyLeaving($clockOut, $date)
        {
            // If no clock out or zero time, return no early leaving
            if (empty($clockOut) || $clockOut == '00:00:00' || $clockOut == null) {
                return '00:00:00';
            }

            // Parse clock out time
            $clockOutTime = \Carbon\Carbon::parse($date . ' ' . $clockOut);
            
            // Office Out Time: 7:00 PM
            $outThreshold = \Carbon\Carbon::parse($date . ' 19:00:00');
            
            // Check if employee left early
            if ($clockOutTime->lt($outThreshold)) {
                // Calculate early leaving duration
                $earlySeconds = $outThreshold->diffInSeconds($clockOutTime);
                return gmdate('H:i:s', $earlySeconds);
            }
            
            return '00:00:00';
        }

        public function updateCalendarAttendance(Request $request)
        {
            if (\Auth::user()->type == 'company' || \Auth::user()->type == 'super admin' || \Auth::user()->can('attendance.calendar.update.all')) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'employee_id' => 'required',
                        'date' => 'required|date',
                        'status' => 'required',
                    ]
                );

                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()->first()]);
                }

                $employee = Employee::find($request->employee_id);
                if (!$employee) {
                    return response()->json(['error' => __('Employee not found.')]);
                }

                $date = $request->date;
                $newStatus = $request->status;
                $in = $request->clock_in ?? '10:00:00';
                $out = $request->clock_out ?? '19:00:00';

                // Determine current status for audit
                $currentAtt = AttendanceEmployee::where('employee_id', $employee->id)->where('date', $date)->first();
                $currentLeaveCode = $this->getLeaveStatus($employee->id, $date);
                $previousStatus = 'Absent';
                
                if ($currentAtt) {
                    $previousStatus = $currentAtt->status;
                } elseif ($currentLeaveCode) {
                    $previousStatus = $currentLeaveCode; // EL, SL, CO
                } else {
                    $dayName = \Carbon\Carbon::parse($date)->format('l');
                    if (strtolower($employee->week_off_day) === strtolower($dayName)) {
                        $previousStatus = 'Week Off';
                    }
                }

                // Map UI status to core logic codes
                $statusCode = 'P'; // Default
                if ($newStatus === 'Present') $statusCode = 'P';
                if ($newStatus === 'Half Day') $statusCode = 'HD';
                if ($newStatus === 'Absent') $statusCode = 'A'; // Changed from LOP to A to treat it as true Absent
                if ($newStatus === 'Paid Leave') $statusCode = 'EL';
                if ($newStatus === 'Sick Leave') $statusCode = 'SL';
                if ($newStatus === 'Comp-Off') $statusCode = 'CO';

                // --- NEW BALANCE ENFORCEMENT LOGIC ---
                // If transitioning to a Leave (from Present/Absent)
                $isNewLeave = in_array($statusCode, ['EL', 'SL', 'CO']);
                $isOldLeave = in_array($currentLeaveCode, ['EL', 'SL', 'CO', 'LOP']); // Included LOP

                // If applying a new leave that wasn't there before
                if ($isNewLeave && $currentLeaveCode !== $statusCode) {
                    $leaveTypeId = 6; 
                    if ($statusCode === 'EL') $leaveTypeId = 2;
                    elseif ($statusCode === 'SL') $leaveTypeId = 1;
                    elseif ($statusCode === 'CO') $leaveTypeId = 4;

                    if ($statusCode === 'CO') {
                        // Check Comp-Off balance
                        $earnedCompOffs = \App\Models\CompOffLeave::where('employees_id', $employee->id)->count();
                        $usedCompOffs = \App\Models\Leave::where('employee_id', $employee->id)
                            ->where('leave_type_id', $leaveTypeId)
                            ->where('status', 'Approved')
                            ->sum('total_leave_days');
                            
                        if (($earnedCompOffs - $usedCompOffs) < 1) {
                            return response()->json(['error' => __('Insufficient Comp-Off balance.')]);
                        }
                    } else {
                        // Check regular leave balance
                        $now = now();
                        $balance = \App\Models\EmployeeLeaveBalance::where('employee_id', $employee->id)
                            ->where('leave_type_id', $leaveTypeId)
                            ->where('year', $now->year)
                            ->where('month', $now->month)
                            ->first();

                        $available = 0;
                        if ($balance) {
                            $available = ($balance->allocated_days + $balance->carry_forward_days) - $balance->used_days;
                        }
                        
                        if ($available < 1) {
                            return response()->json(['error' => __('Insufficient balance for this Leave type.')]);
                        }
                        
                        // Deduct regular leave
                        \App\Services\LeaveLedgerService::applyRegularLeaveBalanceDeduction($employee->id, $leaveTypeId, 1.0);
                    }
                }

                // If removing a leave (transitioning to Present/Absent or a DIFFERENT leave)
                if ($isOldLeave && $currentLeaveCode !== $statusCode) {
                    $oldLeaveTypeId = 6;
                    if ($currentLeaveCode === 'EL') $oldLeaveTypeId = 2;
                    elseif ($currentLeaveCode === 'SL') $oldLeaveTypeId = 1;
                    elseif ($currentLeaveCode === 'CO') $oldLeaveTypeId = 4;

                    // Find the single-day leave block to restore (if it's multi-day, this might be tricky, but calendar edits typically only affect single days)
                    $oldLeave = \App\Models\Leave::where('employee_id', $employee->id)
                        ->where('start_date', '<=', $date)
                        ->where('end_date', '>=', $date)
                        ->where('status', 'Approved')
                        ->first();
                        
                    if ($oldLeave) {
                        // Restore balance (only for regular leaves, CO is virtual based on rows)
                        if ($oldLeaveTypeId !== 4 && $oldLeaveTypeId !== 6) { // Don't restore LOP (6) as it doesn't use balance
                            \App\Services\LeaveLedgerService::restoreRegularLeaveBalance($employee->id, $oldLeaveTypeId, 1.0);
                        }
                        // Delete it here to prevent applyAttendanceTransition from indiscriminately deleting a multi-day block without restoration
                        $oldLeave->delete();
                    }
                }
                // --- END BALANCE ENFORCEMENT LOGIC ---

                $startTime = '10:00:00';
                $endTime = '19:00:00';

                // Core Transition Logic
                $this->applyAttendanceTransition($employee, $date, $statusCode, $in, $out, $startTime, $endTime);

                // Log audit
                \App\Models\AttendanceAudit::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'modified_by' => \Auth::user()->id
                ]);

                return response()->json(['success' => __('Attendance successfully updated.')]);
            }
            return response()->json(['error' => __('Permission denied.')]);
        }
    }
