<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceEmployee extends Model
{

    const STATUS_PRESENT = 'Present';
    const STATUS_HALF_DAY = 'Half Day';
    const STATUS_ABSENT = 'Absent';
    const STATUS_SINGLE_PUNCH = 'Single Punch In';
    const REQUIRED_WORKING_HOURS = 5.0; // 5 hours in decimal

    const REASON_ON_TIME = 'on_time';
    const REASON_LATE_MARK = 'late_mark';
    const REASON_HALF_DAY_LATE_MARK = 'half_day_late_mark';
    const REASON_HALF_DAY_INSUFFICIENT_HOURS = 'half_day_insufficient_hours';
    const REASON_HALF_DAY_MISSING_PUNCH_OUT = 'half_day_missing_punch_out';
    const REASON_SINGLE_PUNCH = 'single_punch';
    const REASON_ABSENT = 'absent';
    const REASON_MANUAL = 'manual';


    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'clock_in',
        'clock_out',
        'late',
        'early_leaving',
        'overtime',
        'total_rest',
        'created_by',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_location',
        'clock_in_accuracy',
        'clock_in_location_captured_at',
        'gps_quality',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_location',
        'clock_out_accuracy',
        'clock_out_location_captured_at',
        'clock_in_2',
        'clock_out_2',
        'clock_in_2_latitude',
        'clock_in_2_longitude',
        'clock_in_2_location',
        'clock_in_2_accuracy',
        'clock_in_2_location_captured_at',
        'clock_out_2_latitude',
        'clock_out_2_longitude',
        'clock_out_2_location',
        'clock_out_2_accuracy',
        'clock_out_2_location_captured_at',
        'missed_punchout_notified_at',
        'status_reason',
        'late_cycle_number',
    ];

    public function employees()
    {
        return $this->hasOne('App\Models\Employee', 'user_id', 'employee_id');
    }

    public function employee()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

    // Location fields are optional - no validation required
    protected $casts = [
        'clock_in_location_captured_at' => 'datetime',
        'clock_out_location_captured_at' => 'datetime',
        'clock_in_2_location_captured_at' => 'datetime',
        'clock_out_2_location_captured_at' => 'datetime',
    ];
    
    
}
