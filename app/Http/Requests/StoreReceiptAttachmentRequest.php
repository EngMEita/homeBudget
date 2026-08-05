<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('updateTransaction', $household);
    }

    public function rules(): array
    {
        return [
            'attachment' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp'],
        ];
    }
}
