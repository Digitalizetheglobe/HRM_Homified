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
