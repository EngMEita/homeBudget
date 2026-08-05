<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'path' => $this->path,
            'size_bytes' => $this->size_bytes,
            'status' => $this->status,
            'health_check' => $this->health_check,
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
