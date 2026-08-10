<?php

namespace App\Http\Resources;

use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(ReceiptService::class);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'client_uuid' => $this->client_uuid,
            'household_id' => $this->household_id,
            'account_id' => $this->account_id,
            'currency_id' => $this->currency_id,
            'total_minor_amount' => $this->total_minor_amount,
            'base_currency_minor_amount' => $this->base_currency_minor_amount,
            'receipt_status' => $this->receipt_status,
            'categorization_status' => $this->categorization_status,
            'categorized_minor_amount' => $service->categorizedTotal($this->resource),
            'remaining_uncategorized_minor_amount' => $service->remainingUncategorizedAmount($this->resource),
            'allocations' => $this->whenLoaded('allocations', fn () => $this->allocations->map(fn ($allocation) => [
                'id' => $allocation->id,
                'category_id' => $allocation->category_id,
                'amount_minor' => $allocation->amount_minor,
                'notes' => $allocation->notes,
            ])->values()->all(), []),
            'attachments' => $this->whenLoaded('attachments', fn () => ReceiptAttachmentResource::collection($this->attachments)->resolve(), []),
            'payment_legs' => $this->whenLoaded('transaction', fn () => PaymentLegResource::collection($this->transaction->load('paymentLegs.account')->paymentLegs)->resolve(), []),
        ];
    }
}
