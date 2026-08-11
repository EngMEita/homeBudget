<?php

namespace Tests\Feature;

use App\Enums\HouseholdRole;
use App\Models\BackupLog;
use App\Models\Household;
use App\Models\HouseholdUser;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupChecksumTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_rejects_backup_when_recorded_checksum_does_not_match_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $household = Household::factory()->create(['owner_user_id' => $user->id]);
        HouseholdUser::create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
            'can_view_balances' => true,
            'can_create_transactions' => true,
            'can_view_transactions' => true,
        ]);

        $path = 'backups/candidate.sqlite';
        Storage::disk('local')->put($path, file_get_contents(database_path('database.sqlite')));
        $backup = BackupLog::create([
            'household_id' => $household->id,
            'created_by' => $user->id,
            'type' => 'manual',
            'path' => $path,
            'status' => 'completed',
            'health_check' => ['sha256' => str_repeat('0', 64)],
        ]);

        $restoreLog = app(BackupService::class)->restore($household, $user->id, $backup);

        $this->assertSame('failed', $restoreLog->status);
        $this->assertSame('Selected backup checksum does not match the recorded backup manifest.', $restoreLog->health_check['reason']);
        $this->assertArrayHasKey('checksum_actual', $restoreLog->health_check['source_health_check']);
    }
}
