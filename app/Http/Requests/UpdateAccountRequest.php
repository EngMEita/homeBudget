<?php

namespace App\Http\Requests;

class UpdateAccountRequest extends StoreAccountRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household !== null && $this->user()?->can('updateAccount', $household);
    }
}
