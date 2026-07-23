<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class BookingForm extends Model
{
    // Removed duplicate declaration of $fillable

    use HasFactory;

    protected $table = 'booking_forms'; // Manually specify the table name

    protected $casts = [
    'assigned_data' => 'array',
    'payment_data' => 'array',
];

    protected $fillable = [
        // Employee Reference
        'employee_id',

        // Primary Applicant
        'primary_applicant_name',
        'primary_applicant_contact_no',
        'primary_applicant_email',
        'primary_applicant_occupation',
        'primary_applicant_company',
        'primary_applicant_designation',
        'primary_applicant_birth_date',
        'primary_applicant_nationality',
        'primary_applicant_pan_no',
        'primary_applicant_aadhar_no',

        // Secondary Applicant
        'secondary_applicant_name',
        'secondary_applicant_contact_no',
        'secondary_applicant_email',
        'secondary_applicant_occupation',
        'secondary_applicant_company',
        'secondary_applicant_designation',
        'secondary_applicant_birth_date',
        'secondary_applicant_nationality',
        'secondary_applicant_pan_no',
        'secondary_applicant_aadhar_no',

        // Project Details
        'project_id',
        'unit_id',
        'unit_size',
        'booking_date',
        'plot_area',
        'carpet_area',
        'built_up_area',
        'rate_per_sq_ft',
        'basic_cost',
        'cost_infrastructure',
        'maintenance_cost', // Add this if missing
        'gst',
        'stamp_duty',
        'registration',
        'other_charges',
        'other',
        'total_cost',
        'agreement_cost',
        'legal_charges',

        // Payment Details (Stored as JSON)
        'payment_data',
        'remaining',
        'is_cancelled',
        'agreement',


    ];

    public function setPaymentDataAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['payment_data'] = json_encode($value);
        } else {
            $this->attributes['payment_data'] = $value;
        }
    }

    public function getPaymentDataAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

   public function employee()
   {
       return $this->belongsTo(User::class, 'employee_id');
   }

   // app/Models/BookingForm.php
public function unit()
{
    return $this->belongsTo(Unit::class, 'unit_id');
}

// In BookingForm.php model
public function timesheet()
{
    return $this->belongsTo(TimeSheet::class, 'enquiry_id');
}

public function project()
{
    return $this->belongsTo(Project::class, 'project_id');
}


   
// app/Models/Unit.php

public function scopeAvailable($query)
{
    return $query->where('is_approved', 1);
}

public function book()
{
    if (!$this->is_approved) {
        throw new \Exception('Unit is not available for booking');
    }
    
    $this->is_approved = 0;
    return $this->save();
}

public function release()
{
    $this->is_approved = 1;
    return $this->save();
}

// In Project model
public function scopeAssignedToEmployee($query, $employeeId)
{
    return $query->where(function($q) use ($employeeId) {
        $q->whereJsonContains('assigned_data', ['employee_ids' => [(string)$employeeId]])
          ->orWhereJsonContains('assigned_data', ['employee_ids' => $employeeId]);
    });
}

}





