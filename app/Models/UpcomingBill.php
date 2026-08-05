<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UpcomingBill extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'household_id',
        'account_id',
        'currency_id',
        'recurring_rule_id',
        'created_by',
        'name',
        'amount_minor',
        'base_amount_minor',
        'due_on',
        'status',
        'reminder_status',
    ];

    protected $casts = ['due_on' => 'date'];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
