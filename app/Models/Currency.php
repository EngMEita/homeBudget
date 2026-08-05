<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_en',
        'name_ar',
        'symbol',
        'decimal_places',
        'minor_unit_factor',
        'is_active',
    ];
}
