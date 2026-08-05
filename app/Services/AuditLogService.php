<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Household;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(Household $household, ?int $userId, string $event, ?Model $auditable = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'household_id' => $household->id,
            'user_id' => $userId,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata,
        ]);
    }
}
