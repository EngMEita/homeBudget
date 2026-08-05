<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'household_id',
        'account_type_id',
        'currency_id',
        'name',
        'opening_balance_minor',
        'is_shared',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $account): void {
            $account->uuid ??= (string) Str::uuid();
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeForHousehold(Builder $query, int $householdId): Builder
    {
        return $query->where('household_id', $householdId);
    }
}
