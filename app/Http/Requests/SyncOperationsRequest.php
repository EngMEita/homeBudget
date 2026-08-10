<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('createTransaction', $household);
    }

    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:50'],
            'operations.*.client_uuid' => ['required', 'uuid'],
            'operations.*.operation_type' => ['required', Rule::in(['transaction.create', 'transaction.update', 'transaction.delete', 'receipt.create', 'receipt.attachment.create'])],
            'operations.*.payload' => ['required', 'array'],
            'operations.*.payload.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'operations.*.payload.currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'operations.*.payload.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'operations.*.payload.type' => ['required_if:operations.*.operation_type,transaction.create', Rule::in(['expense', 'income', 'transfer', 'refund'])],
            'operations.*.payload.status' => ['sometimes', 'string', 'max:50'],
            'operations.*.payload.description' => ['nullable', 'string', 'max:255'],
            'operations.*.payload.amount_minor' => ['required_if:operations.*.operation_type,transaction.create', 'integer', 'min:1'],
            'operations.*.payload.base_amount_minor' => ['nullable', 'integer'],
            'operations.*.payload.paid_by_user_id' => ['required_if:operations.*.operation_type,receipt.create', 'integer', 'exists:users,id'],
            'operations.*.payload.total_minor_amount' => ['required_if:operations.*.operation_type,receipt.create', 'integer', 'min:1'],
            'operations.*.payload.base_currency_minor_amount' => ['required_if:operations.*.operation_type,receipt.create', 'integer'],
            'operations.*.payload.exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'operations.*.payload.exchange_rate_source' => ['nullable', 'string', 'max:100'],
            'operations.*.payload.exchange_rate_date' => ['nullable', 'date'],
            'operations.*.payload.payment_legs' => ['sometimes', 'array', 'min:2'],
            'operations.*.payload.payment_legs.*.account_id' => ['required_with:operations.*.payload.payment_legs', 'integer', 'distinct', 'exists:accounts,id'],
            'operations.*.payload.payment_legs.*.amount_minor' => ['required_with:operations.*.payload.payment_legs', 'integer', 'min:1'],
            'operations.*.payload.payment_legs.*.base_amount_minor' => ['nullable', 'integer', 'min:1'],
            'operations.*.payload.payment_legs.*.exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'operations.*.payload.payment_legs.*.exchange_rate_source' => ['nullable', 'string', 'max:100'],
            'operations.*.payload.payment_legs.*.exchange_rate_date' => ['nullable', 'date'],
            'operations.*.payload.transaction_date' => ['required', 'date'],
            'operations.*.payload.version' => ['sometimes', 'integer', 'min:1'],
            'operations.*.payload.transaction_id' => ['required_if:operations.*.operation_type,transaction.update,transaction.delete', 'integer', 'exists:transactions,id'],
            'operations.*.payload.allocations' => ['sometimes', 'array'],
            'operations.*.payload.allocations.*.category_id' => ['required_with:operations.*.payload.allocations', 'integer', 'exists:categories,id'],
            'operations.*.payload.allocations.*.amount_minor' => ['required_with:operations.*.payload.allocations', 'integer', 'min:1'],
            'operations.*.payload.receipt_client_uuid' => ['required_if:operations.*.operation_type,receipt.attachment.create', 'uuid'],
            'operations.*.payload.original_name' => ['required_if:operations.*.operation_type,receipt.attachment.create', 'string', 'max:255'],
            'operations.*.payload.mime_type' => ['required_if:operations.*.operation_type,receipt.attachment.create', 'string', 'max:100'],
            'operations.*.payload.file_base64' => ['exclude_unless:operations.*.operation_type,receipt.attachment.create', 'required_without:operations.*.payload.file_base64_chunks', 'string'],
            'operations.*.payload.file_base64_chunks' => ['exclude_unless:operations.*.operation_type,receipt.attachment.create', 'required_without:operations.*.payload.file_base64', 'array'],
            'operations.*.payload.file_base64_chunks.*' => ['string'],
        ];
    }
}
