<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringRule extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'household_id',
        'account_id',
        'currency_id',
        'category_id',
        'created_by',
        'name',
        'type',
        'frequency',
        'amount_minor',
        'base_amount_minor',
        'starts_on',
        'next_run_on',
        'ends_on',
        'auto_post',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'next_run_on' => 'date',
        'ends_on' => 'date',
        'auto_post' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
