<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseholdUser extends Model
{
    protected $fillable = [
        'household_id',
        'user_id',
        'role',
        'can_view_balances',
        'can_create_transactions',
        'can_view_transactions',
    ];
}
