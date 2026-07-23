<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
{
    use HasFactory;
    use \App\Traits\HasDataScope;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'applied_on',
        'start_date',
        'end_date',
        'total_leave_days',
        'leave_duration_type',
        'half_day_session',
        'leave_reason',
        'remark',
        'status',
        'is_special_approval',
        'created_by',
        'forwarded_to_director_id',
        'forwarded_by_company_id',
        'forwarded_at',
        'company_approved',
        'director_approved',
    ];

    public function leaveType()
    {
        return $this->hasOne('App\Models\LeaveType', 'id', 'leave_type_id');
    }

    public function employees()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

   
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function forwardedToDirector()
    {
        return $this->belongsTo(User::class, 'forwarded_to_director_id');
    }

    public function forwardedByCompany()
    {
        return $this->belongsTo(User::class, 'forwarded_by_company_id');
    }
}
