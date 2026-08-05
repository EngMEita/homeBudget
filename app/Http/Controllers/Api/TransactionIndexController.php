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

        $format = $request->string('format')->toString() ?: 'csv';
        if ($format === 'xls') {
            return $this->exportExcel($transactions, $household->getKey());
        }

        if ($format === 'pdf') {
            return $this->exportPdf($transactions, $household->getKey());
        }

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

    private function exportExcel($transactions, int $householdId): StreamedResponse
    {
        return response()->streamDownload(function () use ($transactions): void {
            echo "<html><meta charset=\"utf-8\"><body><table border=\"1\">";
            echo '<tr><th>ID</th><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Base amount</th><th>Fee</th></tr>';
            foreach ($transactions as $transaction) {
                echo '<tr>';
                echo '<td>'.e($transaction->id).'</td>';
                echo '<td>'.e($transaction->transaction_date?->toDateString()).'</td>';
                echo '<td>'.e($transaction->type).'</td>';
                echo '<td>'.e($transaction->description).'</td>';
                echo '<td>'.e($transaction->amount_minor).'</td>';
                echo '<td>'.e($transaction->base_amount_minor).'</td>';
                echo '<td>'.e($transaction->transfer_fee_minor).'</td>';
                echo '</tr>';
            }
            echo '</table></body></html>';
        }, sprintf('household-%d-transactions.xls', $householdId), ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function exportPdf($transactions, int $householdId): StreamedResponse
    {
        return response()->streamDownload(function () use ($transactions): void {
            $lines = ["HomeBudget Transactions", str_repeat('-', 24)];
            foreach ($transactions as $transaction) {
                $lines[] = sprintf(
                    '#%d %s %s %s %d',
                    $transaction->id,
                    $transaction->transaction_date?->toDateString(),
                    $transaction->type,
                    $transaction->description,
                    $transaction->amount_minor
                );
            }
            $content = implode("\\n", array_map(fn ($line) => str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $line), $lines));
            $stream = "BT /F1 10 Tf 40 780 Td ({$content}) Tj ET";
            $pdf = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
                ."2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
                ."3 0 obj << /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 595 842] /Contents 5 0 R >> endobj\n"
                ."4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"
                ."5 0 obj << /Length ".strlen($stream)." >> stream\n{$stream}\nendstream endobj\ntrailer << /Root 1 0 R >>\n%%EOF";
            echo $pdf;
        }, sprintf('household-%d-transactions.pdf', $householdId), ['Content-Type' => 'application/pdf']);
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
