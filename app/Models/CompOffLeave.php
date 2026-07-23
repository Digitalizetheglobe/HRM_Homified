<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompOffLeave extends Model
{
    protected $table = 'comp_off_leaves';
    protected $fillable = [
        'employees_id', 
        'comp_off_date',    
        'comp_off_data',
        'created_at',
        'updated_at'
    ];    // Add other fields as needed

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employees_id');
    }
}