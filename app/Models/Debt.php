<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'household_id',
        'currency_id',
        'created_by',
        'name',
        'counterparty_name',
        'direction',
        'principal_minor_amount',
        'remaining_minor_amount',
        'status',
        'opened_on',
        'due_on',
    ];

    protected $casts = ['opened_on' => 'date', 'due_on' => 'date'];

    public function installments(): HasMany
    {
        return $this->hasMany(DebtInstallment::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
