<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpcomingBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'amount_minor' => $this->amount_minor,
            'base_amount_minor' => $this->base_amount_minor,
            'due_on' => $this->due_on?->toDateString(),
            'status' => $this->status,
            'reminder_status' => $this->reminder_status,
        ];
    }
}
