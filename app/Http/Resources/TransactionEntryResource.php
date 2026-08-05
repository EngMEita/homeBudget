<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'transaction_id' => $this->transaction_id,
            'account_id' => $this->account_id,
            'household_id' => $this->household_id,
            'currency_id' => $this->currency_id,
            'amount_minor' => $this->amount_minor,
            'direction' => $this->direction,
            'entry_type' => $this->entry_type,
        ];
    }
}
