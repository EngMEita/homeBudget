<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'household_id',
        'parent_id',
        'name',
        'type',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            $category->uuid ??= (string) Str::uuid();
        });
    }
}
