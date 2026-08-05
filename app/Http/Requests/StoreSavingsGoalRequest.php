<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StoreSavingsGoalRequest extends FormRequest
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
            'target_minor_amount' => ['required', 'integer', 'min:1'],
            'target_date' => ['nullable', 'date'],
        ];
    }
}
