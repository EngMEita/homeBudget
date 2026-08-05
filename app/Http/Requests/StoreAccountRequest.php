<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('manage', $household);
    }

    public function rules(): array
    {
        return [
            'account_type_id' => ['required', 'integer', 'exists:account_types,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'name' => ['required', 'string', 'max:255'],
            'opening_balance_minor' => ['required', 'integer'],
            'is_shared' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
