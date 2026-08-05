<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StoreUpcomingBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('createTransaction', $household);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'recurring_rule_id' => ['nullable', 'integer', 'exists:recurring_rules,id'],
            'name' => ['required', 'string', 'max:120'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'base_amount_minor' => ['nullable', 'integer', 'min:1'],
            'due_on' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'max:50'],
            'reminder_status' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
