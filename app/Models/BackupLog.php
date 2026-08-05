<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'household_id',
        'created_by',
        'type',
        'disk',
        'path',
        'size_bytes',
        'status',
        'health_check',
        'completed_at',
    ];

    protected $casts = ['health_check' => 'array', 'completed_at' => 'datetime'];
}
