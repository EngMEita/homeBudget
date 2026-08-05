<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountReconciliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'account_id' => $this->account_id,
            'transaction_id' => $this->transaction_id,
            'previous_balance_minor' => $this->previous_balance_minor,
            'statement_balance_minor' => $this->statement_balance_minor,
            'difference_minor' => $this->difference_minor,
            'reconciled_on' => $this->reconciled_on?->toDateString(),
            'notes' => $this->notes,
        ];
    }
}
