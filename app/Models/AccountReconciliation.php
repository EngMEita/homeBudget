<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountReconciliation extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'household_id',
        'account_id',
        'transaction_id',
        'created_by',
        'previous_balance_minor',
        'statement_balance_minor',
        'difference_minor',
        'reconciled_on',
        'notes',
    ];

    protected $casts = ['reconciled_on' => 'date'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
