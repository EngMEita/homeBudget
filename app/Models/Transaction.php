<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'client_uuid',
        'household_id',
        'account_id',
        'counterpart_account_id',
        'currency_id',
        'category_id',
        'created_by',
        'updated_by',
        'type',
        'status',
        'description',
        'amount_minor',
        'base_amount_minor',
        'transfer_fee_minor',
        'exchange_rate',
        'exchange_rate_source',
        'exchange_rate_date',
        'transaction_date',
        'metadata',
        'version',
    ];

    protected $casts = [
        'metadata' => 'array',
        'transaction_date' => 'date',
        'exchange_rate_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $transaction): void {
            $transaction->uuid ??= (string) Str::uuid();
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function counterpartAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'counterpart_account_id');
    }

    public function scopeForHousehold(Builder $query, int $householdId): Builder
    {
        return $query->where('household_id', $householdId);
    }
}
