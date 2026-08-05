<?php

namespace App\Http\Requests;

class StoreTransferRequest extends StoreTransactionRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'counterpart_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);
    }
}
