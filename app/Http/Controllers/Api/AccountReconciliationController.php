<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountReconciliationRequest;
use App\Http\Resources\AccountReconciliationResource;
use App\Models\Account;
use App\Models\AccountReconciliation;
use App\Models\Household;
use App\Models\Transaction;
use App\Services\BalanceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AccountReconciliationController extends Controller
{
    public function index(Household $household, Account $account): AnonymousResourceCollection
    {
        Gate::authorize('view', $household);
        abort_unless($account->household_id === $household->id, 404);

        return AccountReconciliationResource::collection(
            $account->reconciliations()->latest('reconciled_on')->latest('id')->paginate(20)
        );
    }

    public function store(StoreAccountReconciliationRequest $request, Household $household, BalanceService $balances): AccountReconciliationResource
    {
        Gate::authorize('manage', $household);

        $account = Account::query()
            ->whereKey($request->integer('account_id'))
            ->where('household_id', $household->id)
            ->firstOrFail();

        $reconciliation = DB::transaction(function () use ($request, $household, $account, $balances): AccountReconciliation {
            $previousBalance = $balances->accountBalance($account);
            $statementBalance = $request->integer('statement_balance_minor');
            $difference = $statementBalance - $previousBalance;

            $transaction = null;
            if ($difference !== 0) {
                $transaction = Transaction::create([
                    'household_id' => $household->id,
                    'account_id' => $account->id,
                    'currency_id' => $account->currency_id,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                    'type' => 'adjustment',
                    'status' => 'confirmed',
                    'description' => $request->string('notes')->toString() ?: 'Account reconciliation adjustment',
                    'amount_minor' => $difference,
                    'base_amount_minor' => $difference,
                    'transaction_date' => $request->date('reconciled_on')->toDateString(),
                    'version' => 1,
                    'metadata' => ['source' => 'account_reconciliation'],
                ]);
            }

            return AccountReconciliation::create([
                'household_id' => $household->id,
                'account_id' => $account->id,
                'transaction_id' => $transaction?->id,
                'created_by' => $request->user()->id,
                'previous_balance_minor' => $previousBalance,
                'statement_balance_minor' => $statementBalance,
                'difference_minor' => $difference,
                'reconciled_on' => $request->date('reconciled_on')->toDateString(),
                'notes' => $request->string('notes')->toString() ?: null,
            ]);
        });

        return new AccountReconciliationResource($reconciliation);
    }
}
