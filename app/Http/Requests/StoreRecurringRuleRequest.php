<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringRuleRequest extends FormRequest
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
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'frequency' => ['sometimes', Rule::in(['weekly', 'monthly', 'yearly'])],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'base_amount_minor' => ['nullable', 'integer', 'min:1'],
            'starts_on' => ['required', 'date'],
            'next_run_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'auto_post' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
