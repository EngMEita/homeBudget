<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentLeg extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'transaction_id', 'household_id', 'account_id', 'currency_id', 'amount_minor', 'base_amount_minor'];

    protected static function booted(): void
    {
        static::creating(fn (self $leg) => $leg->uuid ??= (string) Str::uuid());
    }

    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
}
