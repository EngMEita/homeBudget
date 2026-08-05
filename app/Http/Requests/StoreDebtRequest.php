<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDebtRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'counterparty_name' => ['required', 'string', 'max:120'],
            'direction' => ['sometimes', Rule::in(['owed_by_household', 'owed_to_household'])],
            'principal_minor_amount' => ['required', 'integer', 'min:1'],
            'opened_on' => ['required', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:opened_on'],
        ];
    }
}
