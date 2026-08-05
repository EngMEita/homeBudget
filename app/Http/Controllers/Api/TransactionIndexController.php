<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionIndexController extends Controller
{
    public function __invoke(Request $request, Household $household): AnonymousResourceCollection
    {
        Gate::authorize('viewTransactions', $household);

        $transactions = $this->filteredQuery($request, $household)
            ->paginate((int) $request->input('per_page', 10));

        return TransactionResource::collection($transactions);
    }

    public function export(Request $request, Household $household): StreamedResponse
    {
        Gate::authorize('viewTransactions', $household);

        $transactions = $this->filteredQuery($request, $household)
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        $filename = sprintf('household-%d-transactions.csv', $household->getKey());

        return response()->streamDownload(function () use ($transactions): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'id',
                'date',
                'type',
                'description',
                'amount_minor',
                'base_amount_minor',
                'transfer_fee_minor',
                'exchange_rate',
                'exchange_rate_source',
                'exchange_rate_date',
            ]);

            foreach ($transactions as $transaction) {
                fputcsv($output, [
                    $transaction->id,
                    $transaction->transaction_date?->toDateString(),
                    $transaction->type,
                    $transaction->description,
                    $transaction->amount_minor,
                    $transaction->base_amount_minor,
                    $transaction->transfer_fee_minor,
                    $transaction->exchange_rate,
                    $transaction->exchange_rate_source,
                    $transaction->exchange_rate_date?->format('Y-m-d'),
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request, Household $household)
    {
        return $household->transactions()
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('currency_id'), fn ($query) => $query->where('currency_id', (int) $request->input('currency_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('transaction_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('transaction_date', '<=', $request->date('date_to')));
    }
}
