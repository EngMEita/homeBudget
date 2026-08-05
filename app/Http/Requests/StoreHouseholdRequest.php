<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'base_currency_code' => ['required', 'string', 'size:3'],
            'default_locale' => ['nullable', 'string', 'max:5'],
        ];
    }
}
