<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'counterparty_name' => $this->counterparty_name,
            'direction' => $this->direction,
            'currency_id' => $this->currency_id,
            'principal_minor_amount' => $this->principal_minor_amount,
            'remaining_minor_amount' => $this->remaining_minor_amount,
            'status' => $this->status,
            'opened_on' => $this->opened_on?->toDateString(),
            'due_on' => $this->due_on?->toDateString(),
        ];
    }
}
