<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TransactionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'transaction_id',
        'account_id',
        'household_id',
        'currency_id',
        'amount_minor',
        'direction',
        'entry_type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            $entry->uuid ??= (string) Str::uuid();
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
