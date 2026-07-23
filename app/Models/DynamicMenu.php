<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DynamicMenu extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    
    public function children()
    {
        return $this->hasMany(DynamicMenu::class, 'parent_id')->orderBy('order');
    }
}
