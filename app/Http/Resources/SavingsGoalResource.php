<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $target = max(1, (int) $this->target_minor_amount);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'currency_id' => $this->currency_id,
            'target_minor_amount' => $this->target_minor_amount,
            'current_minor_amount' => $this->current_minor_amount,
            'progress_percent' => min(100, intdiv(((int) $this->current_minor_amount) * 100, $target)),
            'target_date' => $this->target_date?->toDateString(),
            'status' => $this->status,
        ];
    }
}
