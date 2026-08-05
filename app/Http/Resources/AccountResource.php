<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'household_id' => $this->household_id,
            'account_type_id' => $this->account_type_id,
            'currency_id' => $this->currency_id,
            'account_type_name' => $this->whenLoaded('accountType', fn () => $this->accountType?->name),
            'currency_code' => $this->whenLoaded('currency', fn () => $this->currency?->code),
            'name' => $this->name,
            'opening_balance_minor' => $this->opening_balance_minor,
            'is_shared' => $this->is_shared,
            'is_active' => $this->is_active,
        ];
    }
}
