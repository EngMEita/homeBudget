<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class DebtInstallment extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'debt_id',
        'transaction_id',
        'created_by',
        'principal_minor_amount',
        'interest_minor_amount',
        'paid_on',
    ];

    protected $casts = ['paid_on' => 'date'];
}
