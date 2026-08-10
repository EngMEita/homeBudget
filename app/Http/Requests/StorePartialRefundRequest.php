<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StorePartialRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('createTransaction', $household);
    }

    public function rules(): array
    {
        return ['account_id' => ['required', 'integer', 'exists:accounts,id'], 'amount_minor' => ['required', 'integer', 'min:1'], 'transaction_date' => ['required', 'date'], 'description' => ['nullable', 'string', 'max:255']];
    }
}
