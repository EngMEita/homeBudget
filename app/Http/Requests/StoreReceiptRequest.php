<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('createTransaction', $household);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'paid_by_user_id' => ['required', 'integer', 'exists:users,id'],
            'total_minor_amount' => ['required', 'integer', 'min:1'],
            'base_currency_minor_amount' => ['required', 'integer'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i:s'],
            'receipt_status' => ['sometimes', 'string', 'max:50'],
            'categorization_status' => ['sometimes', 'string', 'max:50'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'client_uuid' => ['nullable', 'uuid'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'allocations' => ['sometimes', 'array'],
            'allocations.*.category_id' => ['required_with:allocations', 'integer', 'exists:categories,id'],
            'allocations.*.amount_minor' => ['required_with:allocations', 'integer', 'min:1'],
            'allocations.*.beneficiary_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'allocations.*.notes' => ['nullable', 'string'],
        ];
    }
}
