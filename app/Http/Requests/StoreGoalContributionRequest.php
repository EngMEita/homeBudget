<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StoreGoalContributionRequest extends FormRequest
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
            'amount_minor' => ['required', 'integer', 'min:1'],
            'contributed_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
