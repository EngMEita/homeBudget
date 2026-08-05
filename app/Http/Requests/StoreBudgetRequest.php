<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('viewReports', $household);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'period_type' => ['sometimes', Rule::in(['monthly', 'custom'])],
            'base_currency_code' => ['nullable', 'string', 'size:3'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'status' => ['sometimes', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.category_id' => ['required', 'integer', 'exists:categories,id'],
            'lines.*.planned_minor_amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
