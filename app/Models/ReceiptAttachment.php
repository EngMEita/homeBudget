<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReceiptAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'receipt_id',
        'uploaded_by_user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attachment): void {
            $attachment->uuid ??= (string) Str::uuid();
        });
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }
}
