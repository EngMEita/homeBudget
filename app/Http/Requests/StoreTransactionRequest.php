<?php

namespace App\Http\Requests;

use App\Models\Household;
use App\Models\Account;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        return $household instanceof Household && $this->user()?->can('createTransaction', $household);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'counterpart_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'type' => ['required', Rule::in(['expense', 'income', 'transfer', 'refund'])],
            'status' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'base_amount_minor' => ['nullable', 'integer'],
            'transfer_fee_minor' => ['nullable', 'integer', 'min:0'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate_source' => ['nullable', 'string', 'max:100'],
            'exchange_rate_date' => ['nullable', 'date'],
            'transaction_date' => ['required', 'date'],
            'client_uuid' => ['nullable', 'uuid'],
            'version' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('type') !== 'transfer') {
                    return;
                }

                $accountId = (int) $this->input('account_id');
                $counterpartAccountId = (int) $this->input('counterpart_account_id');

                if ($accountId === $counterpartAccountId) {
                    $validator->errors()->add('counterpart_account_id', 'Counterpart account must be different from the source account.');
                    return;
                }

                $source = Account::query()->find($accountId);
                $counterpart = Account::query()->find($counterpartAccountId);

                if (! $source || ! $counterpart) {
                    return;
                }

                if ($source->household_id !== $counterpart->household_id) {
                    $validator->errors()->add('counterpart_account_id', 'Both transfer accounts must belong to the same household.');
                }

                if ($source->currency_id === $counterpart->currency_id && $this->filled('exchange_rate')) {
                    $validator->errors()->add('exchange_rate', 'Exchange rate should be omitted when both accounts use the same currency.');
                }

                if ($source->currency_id !== $counterpart->currency_id && ! $this->filled('exchange_rate')) {
                    $validator->errors()->add('exchange_rate', 'Exchange rate is required for cross-currency transfers.');
                }

                $fee = (int) $this->input('transfer_fee_minor', 0);

                if ($fee < 0) {
                    $validator->errors()->add('transfer_fee_minor', 'Transfer fee cannot be negative.');
                }

                if ($fee > (int) $this->input('amount_minor', 0)) {
                    $validator->errors()->add('transfer_fee_minor', 'Transfer fee cannot exceed the transfer amount.');
                }
            },
        ];
    }
}
