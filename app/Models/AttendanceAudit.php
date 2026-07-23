<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'previous_status',
        'new_status',
        'modified_by',
    ];

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
