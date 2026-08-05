<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household && $this->user()?->can('manage', $household);
    }

    public function rules(): array
    {
        $household = $this->route('household');

        return [
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('household_id', $household?->id)],
            'statement_balance_minor' => ['required', 'integer'],
            'reconciled_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
