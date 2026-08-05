<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Household;
use App\Models\Transaction;

class BalanceService
{
    public function accountBalance(Account $account): int
    {
        $transactionTotal = Transaction::query()
            ->where('account_id', $account->getKey())
            ->where('status', 'confirmed')
            ->sum('amount_minor');

        return (int) $account->opening_balance_minor + (int) $transactionTotal;
    }

    public function householdBalance(Household $household): int
    {
        return (int) $household->accounts()->get()->sum(fn (Account $account) => $this->accountBalance($account));
    }
}
