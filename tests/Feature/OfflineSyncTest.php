<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\Receipt;
use App\Models\Category;
use App\Models\ReceiptAttachment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_applies_transaction_create_and_replay_is_idempotent(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();
        $clientUuid = (string) Str::uuid();
        $payload = $this->syncPayload($clientUuid, $account->id, $currency->id, 1200);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied');

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied');

        $this->assertSame(1, Transaction::query()->where('client_uuid', $clientUuid)->count());
    }

    public function test_sync_reports_conflict_for_same_client_uuid_with_different_payload(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();
        $clientUuid = (string) Str::uuid();

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", $this->syncPayload($clientUuid, $account->id, $currency->id, 1200))
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied');

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", $this->syncPayload($clientUuid, $account->id, $currency->id, 1500))
            ->assertOk()
            ->assertJsonPath('results.0.status', 'conflict')
            ->assertJsonPath('results.0.conflict_reason', 'The client UUID was already synced with a different payload.')
            ->assertJsonPath('results.0.client_payload.amount_minor', 1500)
            ->assertJsonPath('results.0.server_payload.amount_minor', 1200)
            ->assertJsonPath('results.0.server_result.version', 1);
    }

    public function test_sync_applies_receipt_create(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();
        $clientUuid = (string) Str::uuid();

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", [
                'operations' => [
                    [
                        'client_uuid' => $clientUuid,
                        'operation_type' => 'receipt.create',
                        'payload' => [
                            'account_id' => $account->id,
                            'currency_id' => $currency->id,
                            'paid_by_user_id' => $user->id,
                            'total_minor_amount' => 2500,
                            'base_currency_minor_amount' => 2500,
                            'transaction_date' => '2026-08-04',
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied');

        $this->assertSame(1, Receipt::query()->where('client_uuid', $clientUuid)->count());
    }

    public function test_sync_applies_receipt_create_with_allocations(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();
        $category = Category::factory()->create(['household_id' => $household->id]);
        $clientUuid = (string) Str::uuid();

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", [
                'operations' => [
                    [
                        'client_uuid' => $clientUuid,
                        'operation_type' => 'receipt.create',
                        'payload' => [
                            'account_id' => $account->id,
                            'currency_id' => $currency->id,
                            'paid_by_user_id' => $user->id,
                            'total_minor_amount' => 2500,
                            'base_currency_minor_amount' => 2500,
                            'transaction_date' => '2026-08-04',
                            'allocations' => [
                                ['category_id' => $category->id, 'amount_minor' => 1000],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied');

        $receipt = Receipt::query()->where('client_uuid', $clientUuid)->firstOrFail();
        $this->assertSame('partially_categorized', $receipt->categorization_status);
        $this->assertSame(1, $receipt->allocations()->count());
    }

    public function test_sync_applies_receipt_attachment_after_receipt_create(): void
    {
        Storage::fake('local');

        [$user, $household, $account, $currency] = $this->seedContext();
        $receiptClientUuid = (string) Str::uuid();
        $attachmentClientUuid = (string) Str::uuid();

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", [
                'operations' => [
                    [
                        'client_uuid' => $receiptClientUuid,
                        'operation_type' => 'receipt.create',
                        'payload' => [
                            'account_id' => $account->id,
                            'currency_id' => $currency->id,
                            'paid_by_user_id' => $user->id,
                            'total_minor_amount' => 2500,
                            'base_currency_minor_amount' => 2500,
                            'transaction_date' => '2026-08-04',
                        ],
                    ],
                    [
                        'client_uuid' => $attachmentClientUuid,
                        'operation_type' => 'receipt.attachment.create',
                        'payload' => [
                            'account_id' => $account->id,
                            'currency_id' => $currency->id,
                            'transaction_date' => '2026-08-04',
                            'receipt_client_uuid' => $receiptClientUuid,
                            'original_name' => 'offline.txt',
                            'mime_type' => 'text/plain',
                            'file_base64' => base64_encode('offline attachment'),
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.1.status', 'applied');

        $attachment = ReceiptAttachment::query()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_sync_applies_chunked_receipt_attachment_after_receipt_create(): void
    {
        Storage::fake('local');

        [$user, $household, $account, $currency] = $this->seedContext();
        $receiptClientUuid = (string) Str::uuid();
        $attachmentClientUuid = (string) Str::uuid();
        $encoded = base64_encode('offline chunked attachment');

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", [
                'operations' => [
                    [
                        'client_uuid' => $receiptClientUuid,
                        'operation_type' => 'receipt.create',
                        'payload' => [
                            'account_id' => $account->id,
                            'currency_id' => $currency->id,
                            'paid_by_user_id' => $user->id,
                            'total_minor_amount' => 2500,
                            'base_currency_minor_amount' => 2500,
                            'transaction_date' => '2026-08-04',
                        ],
                    ],
                    [
                        'client_uuid' => $attachmentClientUuid,
                        'operation_type' => 'receipt.attachment.create',
                        'payload' => [
                            'account_id' => $account->id,
                            'currency_id' => $currency->id,
                            'transaction_date' => '2026-08-04',
                            'receipt_client_uuid' => $receiptClientUuid,
                            'original_name' => 'offline.txt',
                            'mime_type' => 'text/plain',
                            'file_base64_chunks' => [substr($encoded, 0, 8), substr($encoded, 8)],
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.1.status', 'applied');

        $attachment = ReceiptAttachment::query()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertSame(strlen('offline chunked attachment'), $attachment->size_bytes);
    }

    public function test_sync_updates_transaction_with_matching_version(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();
        $transaction = Transaction::create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'type' => 'expense',
            'status' => 'confirmed',
            'description' => 'Old',
            'amount_minor' => 1000,
            'base_amount_minor' => 1000,
            'transaction_date' => '2026-08-04',
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", [
                'operations' => [[
                    'client_uuid' => (string) Str::uuid(),
                    'operation_type' => 'transaction.update',
                    'payload' => [
                        'transaction_id' => $transaction->id,
                        'account_id' => $account->id,
                        'currency_id' => $currency->id,
                        'type' => 'expense',
                        'status' => 'confirmed',
                        'description' => 'Updated offline',
                        'amount_minor' => 1500,
                        'base_amount_minor' => 1500,
                        'transaction_date' => '2026-08-04',
                        'version' => 1,
                    ],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied')
            ->assertJsonPath('results.0.result.version', 2);

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'description' => 'Updated offline']);
    }

    public function test_sync_rejects_transaction_update_with_stale_version(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();
        $transaction = Transaction::create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'type' => 'expense',
            'status' => 'confirmed',
            'description' => 'Server wins',
            'amount_minor' => 1000,
            'base_amount_minor' => 1000,
            'transaction_date' => '2026-08-04',
            'created_by' => $user->id,
            'version' => 2,
        ]);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", [
                'operations' => [[
                    'client_uuid' => (string) Str::uuid(),
                    'operation_type' => 'transaction.update',
                    'payload' => [
                        'transaction_id' => $transaction->id,
                        'account_id' => $account->id,
                        'currency_id' => $currency->id,
                        'type' => 'expense',
                        'description' => 'Stale offline',
                        'amount_minor' => 1500,
                        'base_amount_minor' => 1500,
                        'transaction_date' => '2026-08-04',
                        'version' => 1,
                    ],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'conflict')
            ->assertJsonPath('results.0.conflict_reason', 'Transaction version conflict.')
            ->assertJsonPath('results.0.client_payload.version', 1)
            ->assertJsonPath('results.0.server_payload.version', 2)
            ->assertJsonPath('results.0.server_payload.description', 'Server wins');
    }

    public function test_sync_deletes_transaction_with_matching_version(): void
    {
        [$user, $household, $account, $currency] = $this->seedContext();
        $transaction = Transaction::create([
            'household_id' => $household->id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'type' => 'expense',
            'status' => 'confirmed',
            'description' => 'Delete me',
            'amount_minor' => 1000,
            'base_amount_minor' => 1000,
            'transaction_date' => '2026-08-04',
            'created_by' => $user->id,
            'version' => 1,
        ]);

        $this->actingAs($user)
            ->postJson("/api/households/{$household->id}/sync", [
                'operations' => [[
                    'client_uuid' => (string) Str::uuid(),
                    'operation_type' => 'transaction.delete',
                    'payload' => [
                        'transaction_id' => $transaction->id,
                        'account_id' => $account->id,
                        'currency_id' => $currency->id,
                        'transaction_date' => '2026-08-04',
                        'version' => 1,
                    ],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied')
            ->assertJsonPath('results.0.result.deleted', true);

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    private function syncPayload(string $clientUuid, int $accountId, int $currencyId, int $amountMinor): array
    {
        return [
            'operations' => [
                [
                    'client_uuid' => $clientUuid,
                    'operation_type' => 'transaction.create',
                    'payload' => [
                        'account_id' => $accountId,
                        'currency_id' => $currencyId,
                        'type' => 'expense',
                        'status' => 'confirmed',
                        'description' => 'Offline grocery',
                        'amount_minor' => $amountMinor,
                        'base_amount_minor' => $amountMinor,
                        'transaction_date' => '2026-08-04',
                        'version' => 1,
                    ],
                ],
            ],
        ];
    }

    private function seedContext(): array
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        $currency = Currency::factory()->create(['code' => 'SAR']);
        $accountType = AccountType::factory()->create(['household_id' => $household->id]);

        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        $account = Account::create([
            'household_id' => $household->id,
            'account_type_id' => $accountType->id,
            'currency_id' => $currency->id,
            'name' => 'Cash',
            'opening_balance_minor' => 0,
            'is_shared' => true,
            'is_active' => true,
        ]);

        return [$user, $household, $account, $currency];
    }
}
