<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLocationLog extends Model
{
    use HasFactory;

    protected $table = 'employee_location_logs';

    protected $fillable = [
        'employee_id',
        'latitude',
        'longitude',
        'pinged_at',
    ];

    protected $casts = [
        'pinged_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Relationship with the Employee model.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
