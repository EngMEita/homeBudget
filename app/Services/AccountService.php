<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Household;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function create(Household $household, array $data): Account
    {
        return DB::transaction(function () use ($household, $data): Account {
            return Account::create([
                'household_id' => $household->getKey(),
                'account_type_id' => $data['account_type_id'],
                'currency_id' => $data['currency_id'],
                'name' => $data['name'],
                'opening_balance_minor' => (int) $data['opening_balance_minor'],
                'is_shared' => $data['is_shared'] ?? true,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(Account $account, array $data): Account
    {
        $account->forceFill([
            'account_type_id' => $data['account_type_id'],
            'currency_id' => $data['currency_id'],
            'name' => $data['name'],
            'opening_balance_minor' => (int) $data['opening_balance_minor'],
            'is_shared' => $data['is_shared'] ?? $account->is_shared,
            'is_active' => $data['is_active'] ?? $account->is_active,
        ])->save();

        return $account;
    }
}
