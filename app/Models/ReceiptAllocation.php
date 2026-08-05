<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReceiptAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'receipt_id',
        'category_id',
        'amount_minor',
        'beneficiary_user_id',
        'created_by',
        'updated_by',
        'notes',
        'version',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $allocation): void {
            $allocation->uuid ??= (string) Str::uuid();
        });
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
