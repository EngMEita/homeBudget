<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentLegsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('createTransaction', $household);
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'payment_legs' => ['required', 'array', 'min:2'],
            'payment_legs.*.account_id' => ['required', 'integer', 'distinct', 'exists:accounts,id'],
            'payment_legs.*.amount_minor' => ['required', 'integer', 'min:1'],
        ];
    }
}
