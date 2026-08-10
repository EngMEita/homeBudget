<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Receipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'client_uuid',
        'household_id',
        'transaction_id',
        'account_id',
        'currency_id',
        'merchant_id',
        'paid_by_user_id',
        'total_minor_amount',
        'base_currency_minor_amount',
        'exchange_rate',
        'transaction_date',
        'transaction_time',
        'receipt_status',
        'categorization_status',
        'receipt_number',
        'notes',
        'version',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $receipt): void {
            $receipt->uuid ??= (string) Str::uuid();
        });
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ReceiptAllocation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReceiptAttachment::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
