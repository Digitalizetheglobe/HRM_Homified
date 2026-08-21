<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\CodeCoverage\Percentage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    protected $table = 'employees';
    protected $fillable = [
        'user_id',
        'custom_id',
        'name',
        'name',
        'middle_name',
        'last_name',
        'dob',
        'blood_group',
        'gender',
        'phone',
        'office_phone_one',
        'office_phone_two',
        'emergency_number',
        'address',
        'email',
        'password',
        'employee_id',
        'biometric_emp_id',
        'branch_id',
        'site_id',
        'department_id',
        'designation_id',
        'education_details',
        'experience_details',
        'company_doj',
        'shift',
        'documents',
        'account_holder_name',
        'account_number',
        'bank_name',
        'bank_identifier_code',
        'branch_location',
        'tax_payer_id',
        'work_location',
        'salary_type',
        'account_type',
        'salary',
        'created_by',
        'project_id',
        'week_off_day',
        'education_images',
        'approval_status',
        'approved_at',
        'approved_by',
        'rejection_reason',

    ];

    protected $casts = [
        'education_details' => 'array',
        'experience_details' => 'array',
        'education_images' => 'array',
    ];

    public const SHIFTS = [
        'first' => 'First Shift (10:30 AM to 7:30 PM)',
        'second' => 'Second Shift (11:00 AM to 7:00 PM)',
        'third' => 'Third Shift (12:00 PM to 7:00 PM) — Saturday & Sunday only',
    ];

    public const SHIFT_TIMINGS = [
        'first' => ['start' => '10:30:00', 'end' => '19:30:00'],
        'second' => ['start' => '11:00:00', 'end' => '19:00:00'],
        'third' => ['start' => '12:00:00', 'end' => '19:00:00'],
    ];

    public const SHIFT_GRACE_MINUTES = 15;

    public function shiftKey(): string
    {
        return (!empty($this->shift) && isset(self::SHIFT_TIMINGS[$this->shift]))
            ? $this->shift
            : 'first';
    }

    public function shiftStartTime(): string
    {
        return self::SHIFT_TIMINGS[$this->shiftKey()]['start'];
    }

    public function shiftEndTime(): string
    {
        return self::SHIFT_TIMINGS[$this->shiftKey()]['end'];
    }

    public function shiftLabel(): string
    {
        if (empty($this->shift)) {
            return __('Not Set');
        }

        return self::SHIFTS[$this->shift] ?? $this->shift;
    }


    public function approvedBy()
{
    return $this->belongsTo(User::class, 'approved_by');
}

    public function project()
    {
        return $this->belongsTo(Project::class, 'site_id');
    }

    public function headedProjects()
{
    return $this->belongsToMany(Project::class, 'project_site_heads', 'employee_id', 'project_id');
}


    public function salary_type()
    {
        return $this->hasOne('App\Models\PayslipType', 'id', 'salary_type' )->pluck('name')->first();
    }

    public function account_type()
    {
        return $this->hasOne('App\Models\AccountList', 'id', 'account_type')->pluck('account_name')->first();
    }
 
    public function get_net_salary()
    {
        $allowances      = Allowance::where('employee_id', '=', $this->id)->get();
        $total_allowance = 0;
        foreach ($allowances as $allowance) {
            if ($allowance->type == 'percentage') {
                $employee          = Employee::find($allowance->employee_id);
                $total_allowance  = $allowance->amount * $employee->salary / 100  + $total_allowance;
            } else {
                $total_allowance = $allowance->amount + $total_allowance;
            }
        }

        //commission
        $commissions      = Commission::where('employee_id', '=', $this->id)->get();

        $total_commission = 0;
        foreach ($commissions as $commission) {
            if ($commission->type == 'percentage') {
                $employee          = Employee::find($commission->employee_id);
                $total_commission  = $commission->amount * $employee->salary / 100 + $total_commission;
            } else {
                $total_commission = $commission->amount + $total_commission;
            }
        }



        //Loan
        $loans      = Loan::where('employee_id', '=', $this->id)->get();
        $total_loan = 0;
        foreach ($loans as $loan) {
            if ($loan->type == 'percentage') {
                $employee = Employee::find($loan->employee_id);
                $total_loan  = $loan->amount * $employee->salary / 100   + $total_loan;
            } else {
                $total_loan = $loan->amount + $total_loan;
            }
           
        }

        //Saturation Deduction
        $saturation_deductions      = SaturationDeduction::where('employee_id', '=', $this->id)->get();
        $total_saturation_deduction = 0;
        foreach ($saturation_deductions as $saturation_deduction) {
            if ($saturation_deduction->type == 'percentage') {
                $employee          = Employee::find($saturation_deduction->employee_id);
                $total_saturation_deduction  = $saturation_deduction->amount * $employee->salary / 100 + $total_saturation_deduction;
            } else {
                $total_saturation_deduction = $saturation_deduction->amount + $total_saturation_deduction;
            }
        }

        //OtherPayment
        $other_payments      = OtherPayment::where('employee_id', '=', $this->id)->get();
        $total_other_payment = 0;
        foreach ($other_payments as $other_payment) {
            if ($other_payment->type == 'percentage') {
                $employee          = Employee::find($other_payment->employee_id);
                $total_other_payment  = $other_payment->amount * $employee->salary / 100  + $total_other_payment;
            } else {
                $total_other_payment = $other_payment->amount + $total_other_payment;
            }
        }

        //Overtime
        $over_times      = Overtime::where('employee_id', '=', $this->id)->get();
        $total_over_time = 0;
        foreach ($over_times as $over_time) {
            $total_work      = $over_time->number_of_days * $over_time->hours;
            $amount          = $total_work * $over_time->rate;
            $total_over_time = $amount + $total_over_time;
        }


        //Net Salary Calculate
        $advance_salary = $total_allowance + $total_commission - $total_loan - $total_saturation_deduction + $total_other_payment + $total_over_time;

        $employee       = Employee::where('id', '=', $this->id)->first();

        $net_salary     = (!empty($employee->salary) ? $employee->salary : 0) + $advance_salary;

        return $net_salary;
    }

    public static function allowance($id)
    {
        //allowance
        $allowances      = Allowance::where('employee_id', '=', $id)->get();
        $total_allowance = 0;
        foreach ($allowances as $allowance) {
            $total_allowance = $allowance->amount + $total_allowance;
        }

        $allowance_json = json_encode($allowances);

        return $allowance_json;
    }

    public static function commission($id)
    {
        //commission
        $commissions      = Commission::where('employee_id', '=', $id)->get();
        $total_commission = 0;

        foreach ($commissions as $commission) {
            $total_commission = $commission->amount + $total_commission;
        }
        $commission_json = json_encode($commissions);

        return $commission_json;
    }

    public static function loan($id)
    {
        //Loan
        $loans      = Loan::where('employee_id', '=', $id)->get();
        $total_loan = 0;
        foreach ($loans as $loan) {
            $total_loan = $loan->amount + $total_loan;
        }
        $loan_json = json_encode($loans);

        return $loan_json;
    }

    public static function saturation_deduction($id)
    {
        //Saturation Deduction
        $saturation_deductions      = SaturationDeduction::where('employee_id', '=', $id)->get();
        $total_saturation_deduction = 0;
        foreach ($saturation_deductions as $saturation_deduction) {
            $total_saturation_deduction = $saturation_deduction->amount + $total_saturation_deduction;
        }
        $saturation_deduction_json = json_encode($saturation_deductions);

        return $saturation_deduction_json;
    }

    public static function other_payment($id)
    {
        //OtherPayment
        $other_payments      = OtherPayment::where('employee_id', '=', $id)->get();
        $total_other_payment = 0;
        foreach ($other_payments as $other_payment) {
            $total_other_payment = $other_payment->amount + $total_other_payment;
        }
        $other_payment_json = json_encode($other_payments);

        return $other_payment_json;
    }

    public static function overtime($id)
    {
        //Overtime
        $over_times      = Overtime::where('employee_id', '=', $id)->get();
        $total_over_time = 0;
        foreach ($over_times as $over_time) {
            $total_work      = $over_time->number_of_days * $over_time->hours;
            $amount          = $total_work * $over_time->rate;
            $total_over_time = $amount + $total_over_time;
        }
        $over_time_json = json_encode($over_times);

        return $over_time_json;
    }

    public static function employee_id()
    {
        $employee = Employee::latest()->first();

        return !empty($employee) ? $employee->id + 1 : 1;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class); // or whatever is appropriate
    }

    public function phone()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'phone');
    }

 

    public function salaryType()
    {
        return $this->hasOne('App\Models\PayslipType', 'id', 'salary_type');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    public function paySlip()
    {
        return $this->hasOne('App\Models\PaySlip', 'id', 'employee_id');
    }
    


    public function present_status($employee_id, $data)
    {
        return AttendanceEmployee::where('employee_id', $employee_id)->where('date', $data)->first();
    }
    public static function employee_name($name)
    {

        $employee = Employee::where('id', $name)->first();
        if (!empty($employee)) {
            return $employee->full_name;
        }
    }


    public static function login_user($name)
    {
        $user = User::where('id', $name)->first();
        return $user->name;
    }

    public static function employee_salary($salary)
    {

        $employee = Employee::where("salary", $salary)->first();
        if ($employee->salary == '0' || $employee->salary == '0.0') {
            return "-";
        } else {
            return $employee->salary;
        }
    }
    
    public function getFormattedIdAttribute()
{
    return \Auth::user()->employeeIdFormat($this->employee_id);
}

    /**
     * Get the employee's full name (name + last_name)
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        $name = trim($this->name ?? '');
        $lastName = trim($this->last_name ?? '');
        return trim($name . ' ' . $lastName) ?: ($name ?: '-');
    }
    public function getUsedLeaves($leaveTypeId)
    {
        $date = Utility::AnnualLeaveCycle(); // Make sure Utility is imported
        
        return LocalLeave::where('employee_id', $this->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'Approved')
            ->whereBetween('created_at', [$date['start_date'], $date['end_date']])
            ->sum('total_leave_days');
    }

   

    


    public function projects()
    {
        return $this->hasMany(Project::class, 'employees_id'); // 'employees_id' = foreign key
    }

    // In app/Models/Employee.php
    public function terminations()
    {
        return $this->hasMany(Termination::class, 'employee_id');
    }

    public function activeTermination()
    {
        return $this->hasOne(Termination::class, 'employee_id')
            ->where('termination_date', '<=', now()->format('Y-m-d'));
    }

    /**
     * Employee is considered terminated if they have a termination record
     * with termination_date <= today (see activeTermination()).
     */
    public function scopeNotTerminated($query)
    {
        return $query->whereDoesntHave('activeTermination');
    }

    // In your Employee model (app/Models/Employee.php)
public function salaryIncrements()
{
    return $this->hasMany(SalaryIncrement::class);
}

/**
 * Check if employee has uploaded all required documents and received approval
 * @return bool
 */
public function hasApprovedDocuments()
{
    // If employee is already approved, return true
    if ($this->approval_status === 'approved') {
        return true;
    }
    
    // If employee is explicitly rejected, return false
    if ($this->approval_status === 'rejected') {
        return false;
    }
    
    // Get all required documents for the company
    $requiredDocuments = Document::where('created_by', $this->created_by)
        ->where('is_required', 1)
        ->get();
    
    // If no required documents are configured, check if approval is pending
    if ($requiredDocuments->isEmpty()) {
        // If no required documents exist, employee should be manually approved
        return $this->approval_status === 'approved';
    }
    
    // Check if employee has uploaded all required documents
    $employeeDocuments = EmployeeDocument::where('employee_id', $this->id)
        ->whereIn('document_id', $requiredDocuments->pluck('id'))
        ->get();
    
    // Check if all required documents are uploaded
    if ($employeeDocuments->count() < $requiredDocuments->count()) {
        return false;
    }
    
    // Check if all uploaded documents have values (files)
    foreach ($employeeDocuments as $empDoc) {
        if (empty($empDoc->document_value)) {
            return false;
        }
    }
    
    // If all documents are uploaded, employee still needs manual approval
    return $this->approval_status === 'approved';
}

/**
 * Check if employee can access full system (documents approved)
 * @return bool
 */
public function canAccessSystem()
{
    return $this->hasApprovedDocuments();
}

 


    /**
     * Paid leave (Earned / Sick) starts 6 months after date of joining.
     * Example: join 10 Aug 2026 → eligible from 10 Feb 2027.
     */
    public const PAID_LEAVE_WAITING_MONTHS = 6;

    public function leaveEligibleFrom()
    {
        if (empty($this->company_doj)) {
            return null;
        }

        return \Carbon\Carbon::parse($this->company_doj)
            ->addMonthsNoOverflow(self::PAID_LEAVE_WAITING_MONTHS)
            ->startOfDay();
    }

    /**
     * Whether the employee can apply paid leave on the given date.
     */
    public function isEligibleForPaidLeave($date = null): bool
    {
        $from = $this->leaveEligibleFrom();
        if (!$from) {
            return true;
        }

        $checkDate = $date ? \Carbon\Carbon::parse($date) : now();

        return $checkDate->copy()->startOfDay()->gte($from);
    }

    /**
     * Whether monthly EL/SL should be allocated for the given month.
     * Allocation starts in the month when the 6-month wait ends.
     */
    public function isEligibleForPaidLeaveAllocation($date = null): bool
    {
        $from = $this->leaveEligibleFrom();
        if (!$from) {
            return true;
        }

        $checkDate = $date ? \Carbon\Carbon::parse($date) : now();

        return $from->lte($checkDate->copy()->endOfMonth());
    }

    public function isJoiningMonth($year, $month): bool
    {
        if (empty($this->company_doj)) {
            return false;
        }

        $doj = \Carbon\Carbon::parse($this->company_doj);

        return (int) $doj->year === (int) $year && (int) $doj->month === (int) $month;
    }

    public function compOffBalance()
    {
        $earned = \DB::table('comp_off_leaves')
            ->where('employees_id', $this->id)
            ->count();
            
        $used = \App\Models\Leave::where('employee_id', $this->id)
            ->whereHas('leaveType', function($query) {
                $query->where('title', 'Comp-Off');
            })
            ->where('status', 'Approved')
            ->sum('total_leave_days');
            
        return max(0, $earned - $used);
    }
}
