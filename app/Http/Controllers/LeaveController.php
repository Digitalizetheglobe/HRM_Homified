<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave as LocalLeave;
use App\Models\LeaveType;
use App\Mail\LeaveActionSend;
use App\Models\Utility;
use App\Models\User;
use App\Services\LeaveLedgerService;
use App\Notifications\LeaveCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Imports\EmployeesImport;
use App\Exports\LeaveExport;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\GoogleCalendar\Event as GoogleEvent;
use App\Models\EmployeeLeaveBalance;
use Twilio\Rest\Client;


class LeaveController extends Controller
{

    
    public function index(Request $request)
    {

        if (\Auth::user()->type == 'company' || \Auth::user()->can('leave.manage.view.all') || \Auth::user()->type == 'employee') {
            $leaveBalances = [];
            $compOffBalance = 0;
            
            if (\Auth::user()->type == 'employee' && (!\Auth::user()->can('leave.manage.view.all') || request()->has('own'))) {
                $user     = \Auth::user();
                $employee = Employee::where('user_id', '=', $user->id)->first();
                $leaves = LocalLeave::where('employee_id', '=', $employee->id)->orderBy('id', 'desc')->get();
                
                if ($employee) {
                    // Get Comp-Off balance
                    $compOffBalance = $employee->compOffBalance();
                    
                    // Get Earned Leave and Sick Leave balances
                    $now = now();
                    $isEligible = true;
                    if ($employee->company_doj) {
                        $joiningDate = \Carbon\Carbon::parse($employee->company_doj);
                        if ($joiningDate->diffInDays($now) < 30) {
                            $isEligible = false;
                        }
                    }

                    $earnedLeaveType = LeaveType::where('title', 'Earned Leave')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first();
                    $sickLeaveType = LeaveType::where('title', 'Sick Leave')
                        ->where('created_by', \Auth::user()->creatorId())
                        ->first();

                    if ($earnedLeaveType) {
                        $earnedBalance = EmployeeLeaveBalance::where('employee_id', $employee->id)
                            ->where('leave_type_id', $earnedLeaveType->id)
                            ->where('year', $now->year)
                            ->where('month', $now->month)
                            ->first();

                        if ($earnedBalance) {
                            $allocated = (float)$earnedBalance->allocated_days;
                            $used = (float)$earnedBalance->used_days;
                            $carryForward = (float)$earnedBalance->carry_forward_days;
                        } else {
                            $allocated = $isEligible ? 1.0 : 0.0;
                            $used = 0.0;
                            $carryForward = 0.0;
                        }
                        
                        $available = ($allocated + $carryForward) - $used;

                        $leaveBalances['earned_leave'] = [
                            'allocated' => $allocated,
                            'used' => $used,
                            'carry_forward' => $carryForward,
                            'available' => max(0, $available),
                        ];
                    } else {
                        $leaveBalances['earned_leave'] = [
                            'allocated' => $isEligible ? 1.0 : 0.0,
                            'used' => 0,
                            'carry_forward' => 0,
                            'available' => $isEligible ? 1.0 : 0.0,
                        ];
                    }

                    if ($sickLeaveType) {
                        $sickBalance = EmployeeLeaveBalance::where('employee_id', $employee->id)
                            ->where('leave_type_id', $sickLeaveType->id)
                            ->where('year', $now->year)
                            ->where('month', $now->month)
                            ->first();

                        if ($sickBalance) {
                            $allocated = (float)$sickBalance->allocated_days;
                            $used = (float)$sickBalance->used_days;
                            $carryForward = (float)$sickBalance->carry_forward_days;
                        } else {
                            $allocated = $isEligible ? 0.5 : 0.0;
                            $used = 0.0;
                            $carryForward = 0.0;
                        }

                        $available = ($allocated + $carryForward) - $used;

                        $leaveBalances['sick_leave'] = [
                            'allocated' => $allocated,
                            'used' => $used,
                            'carry_forward' => $carryForward,
                            'available' => max(0, $available),
                        ];
                    } else {
                        $leaveBalances['sick_leave'] = [
                            'allocated' => $isEligible ? 0.5 : 0.0,
                            'used' => 0,
                            'carry_forward' => 0,
                            'available' => $isEligible ? 0.5 : 0.0,
                        ];
                    }
                }
            } else {
                // Filter leaves based on user type
                $query = LocalLeave::query();

                if (\Auth::user()->type == 'company' || \Auth::user()->isHR()) {
                    $query->where('created_by', '=', \Auth::user()->creatorId());
                } elseif (strtolower(\Auth::user()->type) == 'director') {
                    $query->where('created_by', '=', \Auth::user()->creatorId());
                
                } else {
                    $query->where('created_by', '=', \Auth::user()->creatorId());
                }

                // Apply Month and Year filters
                if ($request->filled('month')) {
                    $month = $request->month;
                    $year = $request->filled('year') ? $request->year : date('Y');
                    
                    $startDate = $year . '-' . $month . '-01';
                    $endDate = date('Y-m-t', strtotime($startDate));
                    
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q2) use ($startDate, $endDate) {
                              $q2->where('start_date', '<=', $startDate)
                                 ->where('end_date', '>=', $endDate);
                          });
                    });
                } elseif ($request->filled('year')) {
                    $query->where(function($q) use ($request) {
                        $q->whereYear('start_date', $request->year)
                          ->orWhereYear('end_date', $request->year);
                    });
                }

                $leaves = $query->orderBy('id', 'desc')
                               ->with(['employees', 'leaveType'])
                               ->get();
            }

            // Ensure $isEligible is defined even if not an employee
            if (!isset($isEligible)) { $isEligible = true; }
            return view('leave.index', compact('leaves', 'leaveBalances', 'compOffBalance', 'isEligible'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

        // public function employeeJson(Request $request)
        // {
        //     $employees = Employee::where('branch_id', $request->branch)->get()->mapWithKeys(function ($employee) {
        //         return [$employee->id => $employee->full_name];
        //     })->toArray();

        //     return response()->json($employees);
        // }

   public function create()
{
    if (\Auth::user()->type == 'company' || \Auth::user()->can('leave.manage.create.all') || \Auth::user()->type == 'employee') {
        $compOffBalance = 0;
        $compOffLeaveTypeId = null;
        $employeeId = null;

        if (Auth::user()->type == 'employee' && (!Auth::user()->can('leave.manage.create.all') || request()->has('own'))) {
            $employees = Employee::where('user_id', '=', \Auth::user()->id)->first();
            if ($employees) {
                $employeeId = $employees->id;
                $compOffBalance = $employees->compOffBalance();
            }
        } else {
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->full_name];
            });
            // Default to first employee if exists
            if ($employees->count() > 0) {
                $employeeId = $employees->keys()->first();
                $employee = Employee::find($employeeId);
                $compOffBalance = $employee ? $employee->compOffBalance() : 0;
            }
        }

        // Get leave types, but exclude Earned Leave and Sick Leave as they're handled internally
        // Include Comp-Off even if balance is 0, so it can be shown/hidden dynamically
        $leavetypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())
            ->whereNotIn('title', ['Earned Leave', 'Sick Leave'])
            ->get();
        
        // Ensure Comp-Off leave type exists, create if it doesn't
        $compOffLeaveType = $leavetypes->firstWhere('title', 'Comp-Off');
        if (!$compOffLeaveType) {
            $compOffLeaveType = LeaveType::create([
                'title' => 'Comp-Off',
                'days' => 0, // Comp-Off is not limited by days, it's based on available balance
                'created_by' => \Auth::user()->creatorId(),
            ]);
            // Refresh the collection to include the new Comp-Off type
            $leavetypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())
                ->whereNotIn('title', ['Earned Leave', 'Sick Leave'])
                ->get();
        }
        
        if ($compOffLeaveType) {
            $compOffLeaveTypeId = $compOffLeaveType->id;
        }
        
        return view('leave.create', compact('employees', 'leavetypes', 'compOffBalance', 'compOffLeaveTypeId', 'employeeId'));
    } else {
        return response()->json(['error' => __('Permission denied.')], 401);
    }
}  

    public function store(Request $request)
{
    if (\Auth::user()->type == 'company' || \Auth::user()->can('leave.manage.create.all') || \Auth::user()->type == 'employee') {
        $leave_type_select = $request->leave_type_select; // earned_leave, sick_leave, or special_* values
        $leave_duration_type = $request->leave_duration_type; // full_day or half_day
        $isCasualLeave = false;
        $isSpecialLeave = false;
        $actual_leave_type = null;
        
        // Check if a special leave type from leave_types table is selected
        if ($leave_type_select && strpos($leave_type_select, 'special_') === 0) {
            $isSpecialLeave = true;
            // Extract leave type ID from value like "special_5"
            $specialLeaveTypeId = str_replace('special_', '', $leave_type_select);
            $selectedLeaveType = LeaveType::find($specialLeaveTypeId);
            if ($selectedLeaveType) {
                $actual_leave_type = $selectedLeaveType;
                $isCasualLeave = ($selectedLeaveType->title === 'Leave Without Pay' || ($selectedLeaveType->unlimited ?? 0) == 1);
            }
        } else if ($leave_type_select) {
            // Regular leave type (Earned Leave or Sick Leave)
            if ($leave_type_select === 'earned_leave') {
                $actual_leave_type = LeaveType::where('title', 'Earned Leave')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();
                
                if (!$actual_leave_type) {
                    // Create Earned Leave type if it doesn't exist
                    $actual_leave_type = LeaveType::create([
                        'title' => 'Earned Leave',
                        'days' => 1,
                        'created_by' => \Auth::user()->creatorId(),
                    ]);
                }
            } else if ($leave_type_select === 'sick_leave') {
                $actual_leave_type = LeaveType::where('title', 'Sick Leave')
                    ->where('created_by', \Auth::user()->creatorId())
                    ->first();
                
                if (!$actual_leave_type) {
                    // Create Sick Leave type if it doesn't exist
                    $actual_leave_type = LeaveType::create([
                        'title' => 'Sick Leave',
                        'days' => 1,
                        'created_by' => \Auth::user()->creatorId(),
                    ]);
                }
            }
        }
        
        // Validation rules
        $rules = [
            'employee_id' => 'required',
            'leave_reason' => 'required',
        ];
        
        // Validate that either leave_type_select or special_leave_type is selected
        if (!$isSpecialLeave && !$leave_type_select) {
            return redirect()->back()->with('error', __('Please select a leave type.'));
        }
        
        if ($isSpecialLeave) {
            // For special leave types, duration is always full_day
            $leave_duration_type = 'full_day';
        } else {
            // For regular leaves, validate duration
            $rules['leave_duration_type'] = 'required|in:full_day,half_day';
        }
        
        if ($isCasualLeave) {
            // For Leave Without Pay (unlimited), dates are optional
            $rules['start_date'] = 'nullable';
            $rules['end_date'] = 'nullable';
        } else if ($isSpecialLeave) {
            // For other special leave types, dates are required
            $rules['start_date'] = 'required';
            $rules['end_date'] = 'required';
        } else {
            // For regular leaves (Earned Leave or Sick Leave)
            if ($leave_duration_type === 'full_day') {
                $rules['start_date'] = 'required';
                $rules['end_date'] = 'required';
            } else if ($leave_duration_type === 'half_day') {
                $rules['leave_date'] = 'required';
                $rules['half_day_session'] = 'required|in:first_half,second_half';
                // Set start_date and end_date from leave_date for half day
                if ($request->leave_date) {
                    $request->merge([
                        'start_date' => $request->leave_date,
                        'end_date' => $request->leave_date
                    ]);
                }
            }
        }
        
        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        // Calculate total leave days
        $total_leave_days = 0;
        $request_duration_type = $request->leave_duration_type ?? 'full_day';
        
        // Normalize duration type for calculations
        // For special leave types, treat as full_day for calculation
        // Also ensure we always have a valid enum value (full_day or half_day)
        if ($isSpecialLeave) {
            $calc_duration_type = 'full_day'; // All special leave types are treated as full_day
        } elseif ($request_duration_type === 'full_day' || $request_duration_type === 'half_day') {
            $calc_duration_type = $request_duration_type; // Use valid enum value
        } else {
            $calc_duration_type = 'full_day'; // Default to full_day for any invalid value
        }
        

        
        $isCompOff = $actual_leave_type && $actual_leave_type->title === 'Comp-Off';
        
        if ($isCasualLeave) {
            // For Leave Without Pay, calculate days if dates provided, otherwise set to 0
            if ($request->start_date && $request->end_date) {
                // Exclude Week Off days from Leave Without Pay calculation
                $total_leave_days = LeaveLedgerService::calculateWorkingDays($request->employee_id, $request->start_date, $request->end_date);
            } else {
                $total_leave_days = 0; // No date restriction for Leave Without Pay
            }
        } else if ($isCompOff) {
            // For Comp-Off, exclude Week Off days from calculation
            $total_leave_days = LeaveLedgerService::calculateWorkingDays($request->employee_id, $request->start_date, $request->end_date);
        } else {
            // For regular leave types (Earned Leave or Sick Leave)
            if ($calc_duration_type === 'half_day') {
                $total_leave_days = 0.5; // Half day is always 0.5
            } else {
                // Full day: calculate days between start_date and end_date (inclusive)
                $startDate = new \DateTime($request->start_date);
                $endDate = new \DateTime($request->end_date);
                $endDate->modify('+1 day'); // Include end date in calculation
                $total_leave_days = $startDate->diff($endDate)->days;
            }
        }

        // For Comp-Off and Leave Without Pay, skip the available days check (Leave Without Pay is unlimited)
        if (!$isCompOff && !$isCasualLeave) {
            // Check monthly balance for the actual leave type (Earned Leave or Sick Leave)
            $now = now();
            $balance = EmployeeLeaveBalance::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $actual_leave_type->id)
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->first();
                
            if ($balance) {
                $availableDays = ($balance->allocated_days + $balance->carry_forward_days) - $balance->used_days;
                
                // For full day leave, only allow full days (floor the available balance)
                if ($calc_duration_type === 'full_day') {
                    $maxFullDays = floor($availableDays);
                    if ($total_leave_days > $maxFullDays) {
                        return redirect()->back()->with('error', __('You can only apply for maximum '.$maxFullDays.' full days. Available balance: '.number_format($availableDays, 2).' days.'));
                    }
                } else {
                    // For half day, check if already exists for this date and session
                    if ($calc_duration_type === 'half_day') {
                        $existingHalfDay = LocalLeave::where('employee_id', $request->employee_id)
                            ->where('leave_type_id', $actual_leave_type->id)
                            ->where('leave_duration_type', 'half_day')
                            ->where('start_date', $request->start_date)
                            ->where('half_day_session', $request->half_day_session)
                            ->whereIn('status', ['Pending', 'Approved'])
                            ->first();
                        
                        if ($existingHalfDay) {
                            $sessionName = $request->half_day_session === 'first_half' ? __('First Half') : __('Second Half');
                            return redirect()->back()->with('error', __('You have already applied for a '.$sessionName.' leave on '.$request->start_date.'. Please select a different date or session.'));
                        }
                        
                        // Also check if a full day leave exists for this date
                        $existingFullDay = LocalLeave::where('employee_id', $request->employee_id)
                            ->where('leave_type_id', $actual_leave_type->id)
                            ->where('leave_duration_type', 'full_day')
                            ->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->start_date)
                            ->whereIn('status', ['Pending', 'Approved'])
                            ->first();
                        
                        if ($existingFullDay) {
                            return redirect()->back()->with('error', __('You have already applied for a full day leave covering '.$request->start_date.'. Please select a different date.'));
                        }
                    }
                    
                    if ($total_leave_days > $availableDays) {
                        return redirect()->back()->with('error', __('You only have '.number_format($availableDays, 2).' days available for '.$actual_leave_type->title.' this month.'));
                    }
                }
            } else {
                // If no monthly balance is allocated, fall back to annual check
                $date = Utility::AnnualLeaveCycle();

                if (\Auth::user()->type == 'employee' && (!\Auth::user()->isHR() || request()->has('own'))) {
                    $leaves_used = LocalLeave::where('employee_id', '=', $request->employee_id)
                        ->where('leave_type_id', $actual_leave_type->id)
                        ->where('status', 'Approved')
                        ->whereBetween('created_at', [$date['start_date'], $date['end_date']])
                        ->sum('total_leave_days');

                    $leaves_pending = LocalLeave::where('employee_id', '=', $request->employee_id)
                        ->where('leave_type_id', $actual_leave_type->id)
                        ->where('status', 'Pending')
                        ->whereBetween('created_at', [$date['start_date'], $date['end_date']])
                        ->sum('total_leave_days');
                } else {
                    $leaves_used = LocalLeave::where('employee_id', '=', $request->employee_id)
                        ->where('leave_type_id', $actual_leave_type->id)
                        ->where('status', 'Approved')
                        ->whereBetween('created_at', [$date['start_date'], $date['end_date']])
                        ->sum('total_leave_days');

                    $leaves_pending = LocalLeave::where('employee_id', '=', $request->employee_id)
                        ->where('leave_type_id', $actual_leave_type->id)
                        ->where('status', 'Pending')
                        ->whereBetween('created_at', [$date['start_date'], $date['end_date']])
                        ->sum('total_leave_days');
                }

                $return = $actual_leave_type->days - $leaves_used;
                
                // Check if requested days exceed available days
                if ($total_leave_days > $return) {
                    return redirect()->back()->with('error', __('You cannot take more than '.$return.' days for '.$actual_leave_type->title));
                }

                if (!empty($leaves_pending) && $leaves_pending + $total_leave_days > $return) {
                    return redirect()->back()->with('error', __('Multiple leave entry is pending.'));
                }
            }
        }

        $leave = new LocalLeave();
        $leave->employee_id = $request->employee_id;
        $leave->leave_type_id = $actual_leave_type->id; // Use the actual leave type
        
        // Normalize leave_duration_type for database storage
        // Database column is ENUM('full_day', 'half_day') and doesn't allow NULL
        // All leave types (including special ones) should use 'full_day' or 'half_day'
        if ($isSpecialLeave) {
            $leave->leave_duration_type = 'full_day'; // All special leave types default to full_day
            $leave->half_day_session = null; // No session for special leaves
        } else {
            $leave->leave_duration_type = $calc_duration_type; // Use normalized value (full_day or half_day)
            // Set half_day_session only for half day leaves
            if ($calc_duration_type === 'half_day' && $request->half_day_session) {
                $leave->half_day_session = $request->half_day_session; // first_half or second_half
            } else {
                $leave->half_day_session = null;
            }
        }
        $leave->applied_on = date('Y-m-d');
        $leave->start_date = $request->start_date ?? date('Y-m-d'); // Default to today if not provided for Leave Without Pay
        $leave->end_date = $request->end_date ?? date('Y-m-d'); // Default to today if not provided for Leave Without Pay
        
        // For full day leave, ensure we only deduct full days (1.0 per day)
        // Note: For Comp-Off and Leave Without Pay, total_leave_days already excludes Week Off days
        if ($calc_duration_type === 'full_day') {
            $leave->total_leave_days = $total_leave_days; // Already calculated (excluding Week Off for CO/LOP)
        } else if ($calc_duration_type === 'half_day') {
            $leave->total_leave_days = 0.5; // Half day is always 0.5
        } else {
            $leave->total_leave_days = $total_leave_days; // Use calculated value (already excludes Week Off for CO/LOP)
        }
        
        $leave->leave_reason = $request->leave_reason;
        $leave->remark = $request->remark ?? null;
        $leave->status = 'Pending';
        $leave->created_by = \Auth::user()->creatorId();
        
        // Store half day session in remark if provided
        if ($calc_duration_type === 'half_day' && $request->has('half_day_session')) {
            $sessionText = $request->half_day_session === 'first_half' ? 'First Half' : 'Second Half';
            $leave->remark = ($leave->remark ? $leave->remark . ' | ' : '') . 'Half Day Session: ' . $sessionText;
        }
        
        $leave->save();

        // Note: Comp-Off deduction happens when leave is approved, not when created
        // See changeaction() method for Comp-Off deduction logic

        // Send SMS notification to specified numbers
        $employee = Employee::find($leave->employee_id);
        $leaveTypeName = $actual_leave_type ? $actual_leave_type->title : 'Leave';
        $this->sendLeaveCreationSMS($employee, $leaveTypeName, $leave);

        // Send notification to company users and director users
        
        $notificationData = [
            'leave_id' => $leave->id,
            'message' => 'New leave application: ' . ($employee ? $employee->full_name : 'Employee') . ' applied for ' . $leaveTypeName,
            'employee_name' => $employee ? $employee->full_name : 'Employee',
            'leave_type' => $leaveTypeName,
            'url' => route('leave.index'),
        ];

        // Get all company users and director users
        $companyUsers = User::where('type', 'company')
            ->where('created_by', \Auth::user()->creatorId())
            ->get();
        
        $directorUsers = User::where(function($query) {
                $query->where('type', 'director')
                      ->orWhere('type', 'Director');
            })
            ->where('created_by', \Auth::user()->creatorId())
            ->get();

        // Send notifications
        foreach ($companyUsers as $user) {
            $user->notify(new LeaveCreatedNotification($notificationData));
        }
        
        foreach ($directorUsers as $user) {
            $user->notify(new LeaveCreatedNotification($notificationData));
        }

        // Google calendar sync
        if ($request->get('synchronize_type') == 'google_calender') {
            $type = 'leave';
            $request1 = new GoogleEvent();
            $request1->title = !empty(\Auth::user()->getLeaveType($leave->leave_type_id)) ? 
                \Auth::user()->getLeaveType($leave->leave_type_id)->title : '';
            $request1->start_date = $request->start_date;
            $request1->end_date = $request->end_date;
            Utility::addCalendarData($request1, $type);
        }

        return redirect()->route('leave.index')->with('success', __('Leave successfully created.'));
    } else {
        return redirect()->back()->with('error', __('Permission denied.'));
    }
}

    public function show(LocalLeave $leave)
    {
        if (\Auth::user()->can('Manage Leave')) {
            if ($leave->created_by == \Auth::user()->creatorId()) {
                $employee  = Employee::find($leave->employee_id);
                $leavetype = LeaveType::find($leave->leave_type_id);
                
                return view('leave.show', compact('employee', 'leavetype', 'leave'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function edit(LocalLeave $leave)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->can('leave.manage.edit.all') || (\Auth::user()->type == 'employee' && $leave->employee_id == (\Auth::user()->employee->id ?? 0))) {
            if ($leave->created_by == \Auth::user()->creatorId()) {

                if (Auth::user()->type == 'employee' && !Auth::user()->can('leave.manage.edit.all')) {
                    $employees = Employee::where('user_id', '=', \Auth::user()->id)->first();
                } else {
                    $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->full_name];
            });
                }

                // $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

                // Exclude Earned Leave and Sick Leave as they're handled internally
                $leavetypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())
                    ->whereNotIn('title', ['Earned Leave', 'Sick Leave'])
                    ->get();

                return view('leave.edit', compact('leave', 'employees', 'leavetypes'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, $leave)
    {
        $leave = LocalLeave::find($leave);
        if (!(\Auth::user()->type == 'company' || \Auth::user()->can('leave.manage.edit.all') || (\Auth::user()->type == 'employee' && $leave->employee_id == (\Auth::user()->employee->id ?? 0)))) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        
        if ($leave->created_by == Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'employee_id' => 'required',
                        'leave_type_id' => 'required',
                        'start_date' => 'required',
                        'end_date' => 'required',
                        'leave_reason' => 'required',
                        'remark' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $leave_type = LeaveType::find($request->leave_type_id);
                $employee = Employee::where('employee_id', '=', \Auth::user()->creatorId())->first();

                $isCompOff = $leave_type && $leave_type->title === 'Comp-Off';
                $isCasualLeave = $leave_type && ($leave_type->title === 'Leave Without Pay' || $leave_type->unlimited == 1);
                
                // Calculate total leave days - exclude Week Off for CO and LOP
                if ($isCompOff || $isCasualLeave) {
                    // For Comp-Off and Leave Without Pay, exclude Week Off days
                    $employeeId = \Auth::user()->type == 'employee' ? $employee->id : $request->employee_id;
                    $total_leave_days = LeaveLedgerService::calculateWorkingDays($employeeId, $request->start_date, $request->end_date);
                } else {
                    // For other leave types, calculate normally
                    $startDate = new \DateTime($request->start_date);
                    $endDate = new \DateTime($request->end_date);
                    $endDate->add(new \DateInterval('P1D'));
                    $total_leave_days = !empty($startDate->diff($endDate)) ? $startDate->diff($endDate)->days : 0;
                }
                
                $date = Utility::AnnualLeaveCycle();

                if (\Auth::user()->type == 'employee' && (!\Auth::user()->isHR() || request()->has('own'))) {
                    // Leave day
                    $leaves_used   = LocalLeave::whereNotIn('id', [$leave->id])->where('employee_id', '=', $employee->id)->where('leave_type_id', $leave_type->id)->where('status', 'Approved')->whereBetween('created_at', [$date['start_date'],$date['end_date']])->sum('total_leave_days');

                    $leaves_pending  = LocalLeave::whereNotIn('id', [$leave->id])->where('employee_id', '=', $employee->id)->where('leave_type_id', $leave_type->id)->where('status', 'Pending')->whereBetween('created_at', [$date['start_date'],$date['end_date']])->sum('total_leave_days');
                } else {
                    // Leave day
                    $leaves_used   = LocalLeave::whereNotIn('id', [$leave->id])->where('employee_id', '=', $request->employee_id)->where('leave_type_id', $leave_type->id)->where('status', 'Approved')->whereBetween('created_at', [$date['start_date'],$date['end_date']])->sum('total_leave_days');

                    $leaves_pending  = LocalLeave::whereNotIn('id', [$leave->id])->where('employee_id', '=', $request->employee_id)->where('leave_type_id', $leave_type->id)->where('status', 'Pending')->whereBetween('created_at', [$date['start_date'],$date['end_date']])->sum('total_leave_days');
                }

                $return = $leave_type->days - $leaves_used;
                if ($total_leave_days > $return) {
                    return redirect()->back()->with('error', __('You are not eligible for leave.'));
                }

                if (!empty($leaves_pending) && $leaves_pending + $total_leave_days > $return) {
                    return redirect()->back()->with('error', __('Multiple leave entry is pending.'));
                }

                if ($leave_type->days >= $total_leave_days) {
                    if (\Auth::user()->type == 'employee' && (!\Auth::user()->isHR() || request()->has('own'))) {
                        $leave->employee_id = $employee->id;
                    } else {
                        $leave->employee_id      = $request->employee_id;
                    }
                    $leave->leave_type_id    = $request->leave_type_id;
                    $leave->start_date       = $request->start_date;
                    $leave->end_date         = $request->end_date;
                    $leave->total_leave_days = $total_leave_days;
                    $leave->leave_reason     = $request->leave_reason;
                    $leave->remark           = $request->remark;
                    // $leave->status           = $request->status;

                    $leave->save();

                    return redirect()->route('leave.index')->with('success', __('Leave successfully updated.'));
                } else {
                    return redirect()->back()->with('error', __('Leave type ' . $leave_type->name . ' is provide maximum ' . $leave_type->days . "  days please make sure your selected days is under " . $leave_type->days . ' days.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
    }

    public function destroy(LocalLeave $leave)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->can('leave.manage.delete.all') || (\Auth::user()->type == 'employee' && $leave->employee_id == (\Auth::user()->employee->id ?? 0))) {
            if ($leave->created_by == \Auth::user()->creatorId()) {
                // If leave was approved, restore the balance before deleting
                if ($leave->status == 'Approved') {
                    $total_leave_days = $leave->total_leave_days;
                    $leaveType = LeaveType::find($leave->leave_type_id);
                    $isCompOff = $leaveType && $leaveType->title === 'Comp-Off';
                    
                    if ($isCompOff) {
                        // Restore Comp-Off records
                        LeaveLedgerService::restoreCompOffBalance($leave->employee_id, $total_leave_days, $leave->start_date, $leave->end_date);
                    } else {
                        // For regular leaves, restore monthly balance
                        LeaveLedgerService::restoreRegularLeaveBalance($leave->employee_id, $leave->leave_type_id, $total_leave_days);
                    }
                }
                
                $leave->delete();

                return redirect()->route('leave.index')->with('success', __('Leave successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function export()
    {
        $name = 'leave_' . date('Y-m-d i:h:s');
        $data = Excel::download(new LeaveExport(), $name . '.xlsx');

        return $data;
    }

    public function action($id)
    {
        if (!(\Auth::user()->type == 'company' || \Auth::user()->can('leave.manage.action.all'))) {
            return redirect()->back()->with('error', __('Permission denied. Only employees with the correct permissions can perform this action.'));
        }

        $leave     = LocalLeave::find($id);
        $employee  = Employee::find($leave->employee_id);
        $leavetype = LeaveType::find($leave->leave_type_id);

        return view('leave.action', compact('employee', 'leavetype', 'leave'));
    }

    public function changeaction(Request $request)
    {
        if (!(\Auth::user()->type == 'company' || \Auth::user()->can('leave.manage.action.all'))) {
            return redirect()->back()->with('error', __('Permission denied. Only employees with the correct permissions can change leave request status.'));
        }

        $leave = LocalLeave::find($request->leave_id);
        $previousStatus = $leave->status; // Get current status before updating
        $newStatus = $request->status;
        $total_leave_days = $leave->total_leave_days;
        
        // Check if this is a Comp-Off leave or Leave Without Pay
        $leaveType = LeaveType::find($leave->leave_type_id);
        $isCompOff = $leaveType && $leaveType->title === 'Comp-Off';
        $isCasualLeave = $leaveType && $leaveType->title === 'Leave Without Pay';
        $isCompanyUser = Auth::user()->type == 'company';
        $isDirectorUser = strtolower(Auth::user()->type) == 'director';
        $isHrUser = strtolower(Auth::user()->type) == 'hr';
        
        // Handle forwarding for ALL leave types (Company User)
        if ($isCompanyUser && $newStatus == 'Approved') {
            $directorId = $request->input('director_id');
            
            if (!empty($directorId)) {
                // Company approved and wants to forward to director/HR
                $leave->company_approved = true;
                $leave->forwarded_to_director_id = $directorId;
                $leave->forwarded_by_company_id = Auth::id();
                $leave->forwarded_at = now();
                $leave->status = 'Pending'; // Keep as pending until director/HR or company approves
                $leave->save();
                
                return redirect()->route('leave.index')->with('success', __('Leave approved and forwarded successfully.'));
            }
        }
        
        // Handle director approval for forwarded leaves (ALL leave types)
        if ($isDirectorUser) {
            // Check if this is a forwarded leave
            if (!$leave->forwarded_to_director_id || $leave->forwarded_to_director_id != Auth::id() || !$leave->company_approved) {
                return redirect()->route('leave.index')->with('error', __('You can only approve leaves that are forwarded to you by company users.'));
            }
            
            // This is a forwarded leave - allow approval
            if ($newStatus == 'Approved') {
                $leave->director_approved = true;
                $leave->status = 'Approved'; // Final approval
            } else {
                $leave->status = 'Rejected';
            }
            $leave->save();
            
            // Deduct balance when director approves (if not Leave Without Pay)
            if ($newStatus == 'Approved' && !$isCasualLeave) {
                // Deduct balance for forwarded leaves (excluding Leave Without Pay)
                if ($isCompOff) {
                    $daysToDeduct = (int)$total_leave_days;
                    $availableCompOff = \DB::table('comp_off_leaves')
                        ->where('employees_id', $leave->employee_id)
                        ->count();
                    
                    if ($availableCompOff >= $daysToDeduct) {
                        // \DB::table('comp_off_leaves')
                        //     ->where('employees_id', $leave->employee_id)
                        //     ->orderBy('comp_off_date', 'asc')
                        //     ->limit($daysToDeduct)
                        //     ->delete();
                    } else {
                        return redirect()->back()->with('error', __('Insufficient Comp-Off balance. Available: ' . $availableCompOff . ', Required: ' . $daysToDeduct));
                    }
                } else {
                    // For regular leaves, update monthly balance
                    $now = now();
                    $balance = EmployeeLeaveBalance::where('employee_id', $leave->employee_id)
                        ->where('leave_type_id', $leave->leave_type_id)
                        ->where('year', $now->year)
                        ->where('month', $now->month)
                        ->first();
                        
                    if ($balance) {
                        $balance->used_days += $total_leave_days;
                        $balance->save();
                    } else {
                        if ($leaveType) {
                            $defaultAllocation = 0;
                            if ($leaveType->title === 'Earned Leave') {
                                $defaultAllocation = 1.0;
                            } elseif ($leaveType->title === 'Sick Leave') {
                                $defaultAllocation = 0.5;
                            }
                            
                            EmployeeLeaveBalance::create([
                                'employee_id' => $leave->employee_id,
                                'leave_type_id' => $leave->leave_type_id,
                                'year' => $now->year,
                                'month' => $now->month,
                                'allocated_days' => $defaultAllocation,
                                'used_days' => $total_leave_days,
                                'carry_forward_days' => 0,
                            ]);
                        }
                    }
                }
            }
            
            // Send notification
            $setting = Utility::settings(\Auth::user()->creatorId());
            $emp = Employee::find($leave->employee_id);
            if (isset($setting['twilio_leave_approve_notification']) && $setting['twilio_leave_approve_notification'] == 1) {
                $uArr = ['leave_status' => $leave->status];
                Utility::send_twilio_msg($emp->phone, 'leave_approve_reject', $uArr);
            }
            
            $setings = Utility::settings();
            if ($setings['leave_status'] == 1) {
                $employee = Employee::where('id', $leave->employee_id)->where('created_by', '=', \Auth::user()->creatorId())->first();
                $uArr = [
                    'leave_email' => $employee->email,
                    'leave_status_name' => $employee->full_name,
                    'leave_status' => $newStatus,
                    'leave_reason' => $leave->leave_reason,
                    'leave_start_date' => $leave->start_date,
                    'leave_end_date' => $leave->end_date,
                    'total_leave_days' => $leave->total_leave_days,
                ];
                $resp = Utility::sendEmailTemplate('leave_status', [$employee->email], $uArr);
                return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
            
            return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.'));
        }
        
        // Handle HR approval for forwarded leaves (ALL leave types)
        if ($isHrUser && $leave->forwarded_to_director_id == Auth::id() && $leave->company_approved) {
            if ($newStatus == 'Approved') {
                $leave->director_approved = true;
                $leave->status = 'Approved'; // Final approval
            } else {
                $leave->status = 'Rejected';
            }
            $leave->save();
            
            // Deduct balance when HR approves (if not Leave Without Pay)
            if ($newStatus == 'Approved' && !$isCasualLeave) {
                if ($isCompOff) {
                    $daysToDeduct = (int)$total_leave_days;
                    $availableCompOff = \DB::table('comp_off_leaves')
                        ->where('employees_id', $leave->employee_id)
                        ->count();
                    
                    if ($availableCompOff >= $daysToDeduct) {
                        // \DB::table('comp_off_leaves')
                        //     ->where('employees_id', $leave->employee_id)
                        //     ->orderBy('comp_off_date', 'asc')
                        //     ->limit($daysToDeduct)
                        //     ->delete();
                    } else {
                        return redirect()->back()->with('error', __('Insufficient Comp-Off balance. Available: ' . $availableCompOff . ', Required: ' . $daysToDeduct));
                    }
                } else {
                    $now = now();
                    $balance = EmployeeLeaveBalance::where('employee_id', $leave->employee_id)
                        ->where('leave_type_id', $leave->leave_type_id)
                        ->where('year', $now->year)
                        ->where('month', $now->month)
                        ->first();
                        
                    if ($balance) {
                        $balance->used_days += $total_leave_days;
                        $balance->save();
                    } else {
                        if ($leaveType) {
                            $defaultAllocation = 0;
                            if ($leaveType->title === 'Earned Leave') {
                                $defaultAllocation = 1.0;
                            } elseif ($leaveType->title === 'Sick Leave') {
                                $defaultAllocation = 0.5;
                            }
                            
                            EmployeeLeaveBalance::create([
                                'employee_id' => $leave->employee_id,
                                'leave_type_id' => $leave->leave_type_id,
                                'year' => $now->year,
                                'month' => $now->month,
                                'allocated_days' => $defaultAllocation,
                                'used_days' => $total_leave_days,
                                'carry_forward_days' => 0,
                            ]);
                        }
                    }
                }
            }
            
            // Send notification
            $setting = Utility::settings(\Auth::user()->creatorId());
            $emp = Employee::find($leave->employee_id);
            if (isset($setting['twilio_leave_approve_notification']) && $setting['twilio_leave_approve_notification'] == 1) {
                $uArr = ['leave_status' => $leave->status];
                Utility::send_twilio_msg($emp->phone, 'leave_approve_reject', $uArr);
            }
            
            $setings = Utility::settings();
            if ($setings['leave_status'] == 1) {
                $employee = Employee::where('id', $leave->employee_id)->where('created_by', '=', \Auth::user()->creatorId())->first();
                $uArr = [
                    'leave_email' => $employee->email,
                    'leave_status_name' => $employee->full_name,
                    'leave_status' => $newStatus,
                    'leave_reason' => $leave->leave_reason,
                    'leave_start_date' => $leave->start_date,
                    'leave_end_date' => $leave->end_date,
                    'total_leave_days' => $leave->total_leave_days,
                ];
                $resp = Utility::sendEmailTemplate('leave_status', [$employee->email], $uArr);
                return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
            
            return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.'));
        }
        
        // Handle Company User approvals/rejections (for all leave types, including forwarded ones)
        // Company User can approve/reject even if leave was forwarded
        if ($isCompanyUser && $previousStatus == 'Pending') {
            // Mark as company approved if approving
            if ($newStatus == 'Approved') {
                $leave->company_approved = true;
            }
        }
        
        // If changing from Approved to Rejected, restore balances first
        if ($previousStatus == 'Approved' && $newStatus == 'Rejected') {
            if ($isCompOff) {
                // Restoration logic removed as we no longer delete records
            } else {
                // Restore regular leave balance
                LeaveLedgerService::restoreRegularLeaveBalance($leave->employee_id, $leave->leave_type_id, $total_leave_days);
            }
        }
        
        // If changing to Approved (from Pending or Rejected), deduct balances
        // Only deduct if it wasn't already approved (prevent double deduction)
        // Also skip if director/HR already approved (to prevent double deduction)
        if ($newStatus == 'Approved' && $previousStatus != 'Approved') {
            // If this was forwarded and director already approved, don't deduct again
            if ($leave->forwarded_to_director_id && $leave->director_approved) {
                // Director already approved and deducted balance, so skip
                // Company User can still approve/reject but balance already deducted
            } else {
                // Deduct balance (either Company User approving directly, or director/HR hasn't approved yet)
                if ($isCompOff) {
                // For Comp-Off, total_leave_days already excludes Week Off days
                // Deduct Comp-Off records equal to total_leave_days (working days only)
                $daysToDeduct = (int)$total_leave_days;
                
                // Check available Comp-Off balance before deducting
                $availableCompOff = \DB::table('comp_off_leaves')
                    ->where('employees_id', $leave->employee_id)
                    ->count();
                
                if ($availableCompOff >= $daysToDeduct) {
                    // \DB::table('comp_off_leaves')
                    //     ->where('employees_id', $leave->employee_id)
                    //     ->orderBy('comp_off_date', 'asc')
                    //     ->limit($daysToDeduct)
                    //     ->delete();
                } else {
                    // Not enough Comp-Off balance, reject the approval
                    return redirect()->back()->with('error', __('Insufficient Comp-Off balance. Available: ' . $availableCompOff . ', Required: ' . $daysToDeduct));
                }
                } else if (!$isCasualLeave) {
                // For regular leaves (not Leave Without Pay), update monthly balance
                $now = now();
                $balance = EmployeeLeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year', $now->year)
                    ->where('month', $now->month)
                    ->first();
                    
                if ($balance) {
                    $balance->used_days += $total_leave_days;
                    $balance->save();
                } else {
                    // If balance doesn't exist, create it with proper allocation
                    if ($leaveType) {
                        // Determine default allocation based on leave type
                        // Both Earned Leave and Sick Leave get 1.0 day per month
                        // Total: 2 days per month (1 Earned + 1 Sick)
                        $defaultAllocation = 0;
                        if ($leaveType->title === 'Earned Leave') {
                            $defaultAllocation = 1.0;
                        } elseif ($leaveType->title === 'Sick Leave') {
                            $defaultAllocation = 1.0; // Changed from 0.5 to 1.0
                        }
                        
                        EmployeeLeaveBalance::create([
                            'employee_id' => $leave->employee_id,
                            'leave_type_id' => $leave->leave_type_id,
                            'year' => $now->year,
                            'month' => $now->month,
                            'allocated_days' => $defaultAllocation,
                            'used_days' => $total_leave_days,
                            'carry_forward_days' => 0,
                        ]);
                    }
                }
                }
            }
        }
        
        // Update leave status
        $leave->status = $newStatus;
        
        $leave->save();

        // twilio
        $setting = Utility::settings(\Auth::user()->creatorId());
        $emp = Employee::find($leave->employee_id);
        if (isset($setting['twilio_leave_approve_notification']) && $setting['twilio_leave_approve_notification'] == 1) {
            // $msg = __("Your leave has been") . ' ' . $leave->status . '.';

            $uArr = [
                'leave_status' => $leave->status,
            ];


            Utility::send_twilio_msg($emp->phone, 'leave_approve_reject', $uArr);
        }

        $setings = Utility::settings();

        if ($setings['leave_status'] == 1) {
            $employee     = Employee::where('id', $leave->employee_id)->where('created_by', '=', \Auth::user()->creatorId())->first();

            $uArr = [
                'leave_email' => $employee->email,
                'leave_status_name' => $employee->full_name,
                'leave_status' => $request->status,
                'leave_reason' => $leave->leave_reason,
                'leave_start_date' => $leave->start_date,
                'leave_end_date' => $leave->end_date,
                'total_leave_days' => $leave->total_leave_days,

            ];
            $resp = Utility::sendEmailTemplate('leave_status', [$employee->email], $uArr);
            return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        }

        return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.'));
    }

    public function jsoncount(Request $request)
    {
        $date = Utility::AnnualLeaveCycle();
        $leave_counts = LeaveType::select(\DB::raw('COALESCE(SUM(leaves.total_leave_days),0) AS total_leave, leave_types.title, leave_types.days,leave_types.id'))
            ->leftjoin(
                'leaves',
                function ($join) use ($request, $date) {
                    $join->on('leaves.leave_type_id', '=', 'leave_types.id');
                    $join->where('leaves.employee_id', '=', $request->employee_id);
                    $join->where('leaves.status', '=', 'Approved');
                    $join->whereBetween('leaves.created_at', [$date['start_date'],$date['end_date']]);
                }
            )->where('leave_types.created_by', '=', \Auth::user()->creatorId())->groupBy('leave_types.id')->get();
        return $leave_counts;
    }

    public function calender(Request $request)
    {
        $created_by = \Auth::user()->creatorId();
        $Meetings = LocalLeave::where('created_by', $created_by)->get();

        $today_date = date('m');
        $current_month_event = LocalLeave::select('id', 'start_date', 'employee_id', 'created_at')->whereRaw('MONTH(start_date)=' . $today_date)->get();

        $arrMeeting = [];

        foreach ($Meetings as $meeting) {
            $arr['id']        = $meeting['id'];
            $arr['employee_id']     = $meeting['employee_id'];
            // $arr['leave_type_id']     = date('Y-m-d', strtotime($meeting['start_date']));
        }

        $leaves = LocalLeave::where('created_by', '=', \Auth::user()->creatorId())->get();
        if (\Auth::user()->type == 'employee' && (!\Auth::user()->isHR() || request()->has('own'))) {
            $user     = \Auth::user();
            $employee = Employee::where('user_id', '=', $user->id)->first();
            $leaves   = LocalLeave::where('employee_id', '=', $employee->id)->get();
        } else {
            $leaves = LocalLeave::where('created_by', '=', \Auth::user()->creatorId())->get();
        }

        return view('leave.calender', compact('leaves'));
    }

    public function get_leave_data(Request $request)
    {
        $arrayJson = [];
        if ($request->get('calender_type') == 'google_calender') {
            $type = 'leave';
            $arrayJson =  Utility::getCalendarData($type);
        } else {
            $data = LocalLeave::where('created_by', \Auth::user()->creatorId())->get();

            foreach ($data as $val) {
                $end_date = date_create($val->end_date);
                date_add($end_date, date_interval_create_from_date_string("1 days"));
                $arrayJson[] = [
                    "id" => $val->id,
                    "title" => !empty(\Auth::user()->getLeaveType($val->leave_type_id)) ? \Auth::user()->getLeaveType($val->leave_type_id)->title : '',
                    "start" => $val->start_date,
                    "end" => date_format($end_date, "Y-m-d H:i:s"),
                    "className" => $val->color,
                    "textColor" => '#FFF',
                    "allDay" => true,
                    "url" => route('leave.action', $val['id']),
                ];
            }
        }

        return $arrayJson;
    }

    public function compOffIndex()
    {
        if (!(\Auth::user()->type == 'company' || \Auth::user()->can('leave.compoff.view.all') || \Auth::user()->can('leave.compoff.view.own'))) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return view('leave.compoff');
    }

    public function carryforwardIndex(Request $request)
    {
        $user = \Auth::user();
        
        $canViewAll = $user->type == 'company' || $user->can('leave.carryforward.view.all');
        $canViewOwn = $user->can('leave.carryforward.view.own');
        
        if (!$canViewAll && !$canViewOwn) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if ($canViewAll) {
            $currentYear = date('Y');
            $employees = Employee::where('created_by', \Auth::user()->creatorId())
                ->whereNotIn('id', function($query) {
                    $query->select('employee_id')->from('terminations');
                })
                ->get();
            
            $selectedEmployeeId = $request->get('employee_id');
            $employeeData = null;
            
            if ($selectedEmployeeId) {
                $selectedEmployee = Employee::find($selectedEmployeeId);
                $now = now();
                
                // Recalculate carry forward for all leave types first to ensure accuracy
                if ($selectedEmployee) {
                    $allLeaveTypes = LeaveType::where('created_by', \Auth::user()->creatorId())->get();
                    foreach ($allLeaveTypes as $lType) {
                        $this->updateCarryForward($selectedEmployee, $lType, $now);
                    }
                }

                // Fetch leave balances for the selected employee for the current year
                $balances = EmployeeLeaveBalance::with('leaveType')
                    ->where('employee_id', $selectedEmployeeId)
                    ->where('year', $currentYear)
                    ->orderBy('month', 'asc')
                    ->get();
                    
                $employeeData = [];
                foreach ($balances as $balance) {
                    $leaveTitle = $balance->leaveType ? $balance->leaveType->title : 'Unknown';
                    
                    if (!isset($employeeData[$leaveTitle])) {
                        $employeeData[$leaveTitle] = [];
                    }
                    
                    $employeeData[$leaveTitle][] = [
                        'month' => date('F', mktime(0, 0, 0, $balance->month, 10)),
                        'opening_balance' => $balance->carry_forward_days,
                        'allocated' => $balance->allocated_days,
                        'used' => $balance->used_days,
                        'available' => $balance->available_days, // This also acts as carry forward for next month
                    ];
                }
            }
                
            return view('leave.carryforward', compact('employees', 'selectedEmployeeId', 'employeeData', 'currentYear', 'canViewAll'));
        } else {
            // Self-service logic for own view
            $currentYear = date('Y');
            $employee = Employee::where('user_id', $user->id)->first();
            
            if (!$employee) {
                return redirect()->back()->with('error', __('Employee profile not found.'));
            }
            
            $selectedEmployeeId = $employee->id;
            $now = now();
            
            $allLeaveTypes = LeaveType::where('created_by', \Auth::user()->creatorId())->get();
            foreach ($allLeaveTypes as $lType) {
                $this->updateCarryForward($employee, $lType, $now);
            }
            
            $balances = EmployeeLeaveBalance::with('leaveType')
                ->where('employee_id', $selectedEmployeeId)
                ->where('year', $currentYear)
                ->orderBy('month', 'asc')
                ->get();
                
            $employeeData = [];
            foreach ($balances as $balance) {
                $leaveTitle = $balance->leaveType ? $balance->leaveType->title : 'Unknown';
                if (!isset($employeeData[$leaveTitle])) {
                    $employeeData[$leaveTitle] = [];
                }
                $employeeData[$leaveTitle][] = [
                    'month' => date('F', mktime(0, 0, 0, $balance->month, 10)),
                    'opening_balance' => $balance->carry_forward_days,
                    'allocated' => $balance->allocated_days,
                    'used' => $balance->used_days,
                    'available' => $balance->available_days,
                ];
            }
            
            $employees = collect([$employee]); // Pass dummy collection so view doesn't break
            
            return view('leave.carryforward', compact('employees', 'selectedEmployeeId', 'employeeData', 'currentYear', 'canViewAll'));
        }
    }

    public function getCompOffBalance($employeeId)
    {
        $employee = Employee::find($employeeId);
        $balance = $employee ? $employee->compOffBalance() : 0;
        
        return response()->json([
            'balance' => $balance,
            'status' => 'success'
        ]);
    }
    
    public function getMonthlyBalance($employeeId)
    {
        $now = now();
        $earnedLeaveType = LeaveType::where('title', 'Earned Leave')
            ->where('created_by', \Auth::user()->creatorId())
            ->first();
        $sickLeaveType = LeaveType::where('title', 'Sick Leave')
            ->where('created_by', \Auth::user()->creatorId())
            ->first();
        
        $earnedAvailable = 0;
        $sickAvailable = 0;
        
        if ($earnedLeaveType) {
            $earnedBalance = EmployeeLeaveBalance::where('employee_id', $employeeId)
                ->where('leave_type_id', $earnedLeaveType->id)
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->first();
            
            if ($earnedBalance) {
                $earnedAvailable = ($earnedBalance->allocated_days + $earnedBalance->carry_forward_days) - $earnedBalance->used_days;
            } else {
                $earnedAvailable = 1.0; // Default monthly allocation
            }
        }
        
        if ($sickLeaveType) {
            $sickBalance = EmployeeLeaveBalance::where('employee_id', $employeeId)
                ->where('leave_type_id', $sickLeaveType->id)
                ->where('year', $now->year)
                ->where('month', $now->month)
                ->first();
            
            if ($sickBalance) {
                $sickAvailable = ($sickBalance->allocated_days + $sickBalance->carry_forward_days) - $sickBalance->used_days;
            } else {
                $sickAvailable = 0.5; // Default monthly allocation
            }
        }
        
        return response()->json([
            'earned_available' => $earnedAvailable,
            'sick_available' => $sickAvailable,
            'total_available' => $earnedAvailable + $sickAvailable,
            'status' => 'success'
        ]);
    }
    
    public function checkHalfDayAvailability(Request $request)
    {
        $employeeId = $request->employee_id;
        $date = $request->date;
        
        // Check if there's already a half day leave for this date
        $existingLeave = LocalLeave::where('employee_id', $employeeId)
            ->where('leave_duration_type', 'half_day')
            ->where('start_date', $date)
            ->whereIn('status', ['Pending', 'Approved'])
            ->first();
        
        return response()->json([
            'exists' => $existingLeave ? true : false,
            'status' => 'success'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        if (!Auth::user()->can('Delete Employee')) {
            abort(403, 'Permission Denied');
        }
        
        $request->validate([
            'unit_ids' => 'required|string' // We'll receive a JSON string
        ]);
        
        // Decode the JSON string to array
        $unitIds = json_decode($request->unit_ids, true);
        
        if (!is_array($unitIds) || empty($unitIds)) {
            return redirect()->route('units.index')
                ->with('error', 'No units selected for deletion.');
        }
        
        $deleteCount = Unit::whereIn('id', $unitIds)->delete();
        
        return redirect()->route('units.index')
            ->with('success', "Successfully deleted $deleteCount units.");
    }

    /**
     * Send WhatsApp notification when a leave is created
     * Uses Twilio Content Template for WhatsApp
     */
    private function sendLeaveCreationSMS($employee, $leaveTypeName, $leave)
    {
        \Log::info('sendLeaveCreationSMS function called (WhatsApp)', [
            'employee_id' => $employee ? $employee->id : null,
            'leave_id' => $leave->id ?? null
        ]);
        
        try {
            // Get Twilio settings
            $settings = Utility::settings(\Auth::user()->creatorId());
            
            // Get Twilio credentials - Forced to use .env values per request
            $account_sid = env('TWILIO_SID');
            $auth_token = env('TWILIO_AUTH_TOKEN');
            
            if (!$account_sid || !$auth_token) {
                \Log::error('Twilio credentials not configured in .env');
                return;
            }

            \Log::info('Twilio client initialized for WhatsApp');
            
            // Use TwilioService for WhatsApp template
            $twilioService = new \App\Services\TwilioService();
            \Log::info('Using TwilioService for WhatsApp template');
            
            // Prepare message variables for template
            $employeeName = $employee ? $employee->full_name : 'Employee';
            $startDate = date('d-m-Y', strtotime($leave->start_date));
            $endDate = date('d-m-Y', strtotime($leave->end_date));
            
            // Get Leave Duration
            $leaveDuration = 'Full Day'; // Default
            if ($leave->leave_duration_type == 'half_day') {
                if ($leave->half_day_session == 'first_half') {
                    $leaveDuration = 'Half Day (First Half)';
                } elseif ($leave->half_day_session == 'second_half') {
                    $leaveDuration = 'Half Day (Second Half)';
                } else {
                    $leaveDuration = 'Half Day';
                }
            }
            
            // Prepare content variables for template, matching the {{1}}, {{2}}, etc.
            $contentVariables = [
                "1" => $employeeName,
                "2" => $leaveTypeName,
                "3" => $leaveDuration,
                "4" => $startDate,
                "5" => $endDate
            ];
            
            // Recipients: Admin numbers only (Employee is excluded per request)
            $toNumbers = [
                '+919923196779', // Primary Admin
                // Add the second number here when available
                // Add the third number here when available
            ];
            
            // If settings contain a comma-separated list, use those instead
            if (!empty($settings['twilio_notification_numbers'])) {
                $toNumbers = explode(',', $settings['twilio_notification_numbers']);
            }
            
            // Ensure we have unique non-empty numbers and they are properly formatted
            $toNumbers = array_unique(array_filter($toNumbers));

            if (empty($toNumbers)) {
                \Log::warning('No recipient numbers found for leave creation WhatsApp notification');
                return;
            }

            // Send to multiple numbers
            $successCount = 0;
            $failCount = 0;
            
            foreach ($toNumbers as $toNumber) {
                $result = $twilioService->sendWhatsAppTemplate($toNumber, $contentVariables);
                
                if ($result) {
                    $successCount++;
                    \Log::info('Leave notification sent via TwilioService (template) to ' . $toNumber . ' - SID: ' . $result->sid);
                } else {
                    $failCount++;
                    \Log::error('Failed to send leave notification via TwilioService (template) to ' . $toNumber);
                }
            }
            
            \Log::info('WhatsApp template sending completed', [
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'total_recipients' => count($toNumbers)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in sendLeaveCreationSMS: ' . $e->getMessage());
            \Log::error('Error details: ' . $e->getTraceAsString());
        }
    }

    public function leaveDetails()
    {
        if (!(\Auth::user()->type == 'company' || \Auth::user()->can('leave.details.view.all') || \Auth::user()->can('leave.details.view.own'))) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        if (request()->has('own')) {
            $employees = Employee::where('user_id', \Auth::user()->id)->notTerminated()->get();
        } else {
            $employees = Employee::where('created_by', \Auth::user()->creatorId())->notTerminated()->get();
        }
        
        $employeeLeaveDetails = [];
        $now = now();

        foreach ($employees as $employee) {
            // Get Comp-Off balance
            $compOffBalance = $employee->compOffBalance();

            // Get Earned Leave balance - using EmployeeLeaveBalance model
            $earnedLeaveBalance = $this->getEmployeeCurrentLeaveBalance($employee, 'Earned Leave', $now);
            
            // Get Sick Leave balance - using EmployeeLeaveBalance model
            $sickLeaveBalance = $this->getEmployeeCurrentLeaveBalance($employee, 'Sick Leave', $now);

            $employeeLeaveDetails[] = [
                'employee' => $employee,
                'compOffBalance' => $compOffBalance,
                'earnedLeaveBalance' => $earnedLeaveBalance,
                'sickLeaveBalance' => $sickLeaveBalance
            ];
        }

        return view('leave.leave-details', compact('employeeLeaveDetails'));
    }

    private function getEmployeeCurrentLeaveBalance($employee, $leaveTypeName, $now)
    {
        $leaveType = LeaveType::where('title', $leaveTypeName)
            ->where('created_by', \Auth::user()->creatorId())
            ->first();
        
        if (!$leaveType) {
            return 1.0; // Default if leave type doesn't exist
        }

        // Check eligibility (30 days from DOJ)
        if (!empty($employee->company_doj)) {
            $eligibilityDate = \Carbon\Carbon::parse($employee->company_doj)->addDays(30);
            if ($now->lt($eligibilityDate)) {
                return 0.0; // Not eligible yet
            }
        }

        // Get current month balance record
        $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        // If no balance record exists, create one with proper allocation
        if (!$balance) {
            $balance = new EmployeeLeaveBalance();
            $balance->employee_id = $employee->id;
            $balance->leave_type_id = $leaveType->id;
            $balance->year = $now->year;
            $balance->month = $now->month;
            $balance->allocated_days = 1.0; // 1 day per month
            $balance->used_days = 0.0;
            $balance->carry_forward_days = 0.0;
            $balance->save();
        }

        // If allocated is 0, update it to 1.0
        if ($balance->allocated_days == 0) {
            $balance->allocated_days = 1.0;
            $balance->save();
        }

        // Calculate carry forward from previous months
        $this->updateCarryForward($employee, $leaveType, $now);

        // Refresh balance after carry forward update
        $balance->refresh();

        // Use the model's built-in method to get available days
        return max(0, $balance->getAvailableDaysAttribute());
    }

    private function updateCarryForward($employee, $leaveType, $now)
    {
        $startMonth = 1;

        // Check eligibility and determine start month
        if (!empty($employee->company_doj)) {
            $eligibilityDate = \Carbon\Carbon::parse($employee->company_doj)->addDays(30);
            
            if ($now->lt($eligibilityDate)) {
                return; // Not eligible yet
            }

            if ($eligibilityDate->year == $now->year) {
                $startMonth = $eligibilityDate->month;
            } elseif ($eligibilityDate->year > $now->year) {
                return; // Not eligible this year
            }
        }

        // For each month from startMonth to current month, ensure carry forward is calculated
        for ($month = $startMonth; $month <= $now->month; $month++) {
            $monthBalance = EmployeeLeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $now->year)
                ->where('month', $month)
                ->first();

            $carryForward = 0.0;

            // Calculate carry forward from previous month
            if ($month > 1) {
                $previousMonthBalance = EmployeeLeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $now->year)
                    ->where('month', $month - 1)
                    ->first();
                
                if ($previousMonthBalance) {
                    // Calculate previous month's available days
                    $previousAvailable = $previousMonthBalance->allocated_days + $previousMonthBalance->carry_forward_days - $previousMonthBalance->used_days;
                    $carryForward = max(0, $previousAvailable);
                }
            }

            if (!$monthBalance) {
                // Create balance record for this month
                $monthBalance = new EmployeeLeaveBalance();
                $monthBalance->employee_id = $employee->id;
                $monthBalance->leave_type_id = $leaveType->id;
                $monthBalance->year = $now->year;
                $monthBalance->month = $month;
                $monthBalance->allocated_days = 1.0; // Default allocation
                $monthBalance->used_days = 0.0;
                $monthBalance->carry_forward_days = $carryForward;
                $monthBalance->save();
            } else {
                // Update existing balance record with correct carry forward
                $monthBalance->carry_forward_days = $carryForward;
                $monthBalance->save();
            }
        }
    }

    public function reason($id)
    {
        $leave = LocalLeave::find($id);
        
        return view('leave.reason', compact('leave'));
    }
}
