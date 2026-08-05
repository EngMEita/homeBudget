<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class LedgerPostingService
{
    public function post(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $transaction->forceFill([
                'status' => 'confirmed',
                'version' => $transaction->version + 1,
            ])->save();
        });
    }
}
