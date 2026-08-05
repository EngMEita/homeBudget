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

class BackupRestoreSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_records_failure_when_backup_file_is_not_valid_sqlite(): void
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

        Storage::disk('local')->put('backups/corrupt.sqlite', 'not a sqlite database');
        $backup = BackupLog::create([
            'household_id' => $household->id,
            'created_by' => $user->id,
            'type' => 'manual',
            'path' => 'backups/corrupt.sqlite',
            'status' => 'completed',
        ]);

        $restoreLog = app(BackupService::class)->restore($household, $user->id, $backup);

        $this->assertSame('restore', $restoreLog->type);
        $this->assertSame('failed', $restoreLog->status);
        $this->assertSame('Selected backup failed validation before restore.', $restoreLog->health_check['reason']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'backup.restore_failed']);
    }
}
