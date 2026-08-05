<?php

namespace App\Http\Requests;

class UpdateTransactionRequest extends StoreTransactionRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household !== null && $this->user()?->can('updateTransaction', $household);
    }
}
