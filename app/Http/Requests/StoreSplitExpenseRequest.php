<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSplitExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('createTransaction', $household);
    }

    public function rules(): array
    {
        return [
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'base_amount_minor' => ['nullable', 'integer'],
            'transaction_date' => ['required', 'date'],
            'client_uuid' => ['nullable', 'uuid'],
            'payment_legs' => ['required', 'array', 'min:2'],
            'payment_legs.*.account_id' => ['required', 'integer', 'distinct', 'exists:accounts,id'],
            'payment_legs.*.amount_minor' => ['required', 'integer', 'min:1'],
            'payment_legs.*.base_amount_minor' => ['nullable', 'integer'],
        ];
    }
}
