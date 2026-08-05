<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StoreDebtInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('createTransaction', $household);
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['nullable', 'integer', 'exists:transactions,id'],
            'principal_minor_amount' => ['required', 'integer', 'min:1'],
            'interest_minor_amount' => ['sometimes', 'integer', 'min:0'],
            'paid_on' => ['required', 'date'],
        ];
    }
}
