<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'household_id' => $this->household_id,
            'account_id' => $this->account_id,
            'counterpart_account_id' => $this->counterpart_account_id,
            'currency_id' => $this->currency_id,
            'category_id' => $this->category_id,
            'type' => $this->type,
            'status' => $this->status,
            'description' => $this->description,
            'amount_minor' => $this->amount_minor,
            'base_amount_minor' => $this->base_amount_minor,
            'transfer_fee_minor' => $this->transfer_fee_minor,
            'exchange_rate' => $this->exchange_rate,
            'exchange_rate_source' => $this->exchange_rate_source,
            'exchange_rate_date' => $this->exchange_rate_date?->format('Y-m-d'),
            'transaction_date' => $this->transaction_date?->toDateString(),
            'version' => $this->version,
            'payment_legs' => PaymentLegResource::collection($this->whenLoaded('paymentLegs')),
        ];
    }
}
