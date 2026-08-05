<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'type' => $this->type,
            'frequency' => $this->frequency,
            'amount_minor' => $this->amount_minor,
            'base_amount_minor' => $this->base_amount_minor,
            'starts_on' => $this->starts_on?->toDateString(),
            'next_run_on' => $this->next_run_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'auto_post' => $this->auto_post,
            'is_active' => $this->is_active,
        ];
    }
}
