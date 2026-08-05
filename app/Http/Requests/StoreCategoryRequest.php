<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('household_id', $household?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['expense', 'income', 'transfer', 'refund'])],
            'is_active' => ['boolean'],
        ];
    }
}
