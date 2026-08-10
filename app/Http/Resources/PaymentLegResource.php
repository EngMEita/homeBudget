<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentLegResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'account_name' => $this->whenLoaded('account', fn () => $this->account->name),
            'currency_id' => $this->currency_id,
            'amount_minor' => $this->amount_minor,
            'base_amount_minor' => $this->base_amount_minor,
        ];
    }
}
