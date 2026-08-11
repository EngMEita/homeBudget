<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\Household;
use App\Models\SyncOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemHealthService
{
    public function forHousehold(Household $household): array
    {
        $checks = [
            $this->databaseIntegrity(),
            $this->foreignKeysEnabled(),
            $this->sqliteWriteMode(),
            $this->storageWritable(),
            $this->latestBackup($household),
            $this->syncBacklog($household),
        ];

        return [
            'status' => $this->overallStatus($checks),
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    private function databaseIntegrity(): array
    {
        try {
            $integrity = DB::selectOne('PRAGMA integrity_check');
            $foreignKeys = DB::select('PRAGMA foreign_key_check');
            $ok = ($integrity->integrity_check ?? null) === 'ok' && count($foreignKeys) === 0;

            return $this->check(
                'database_integrity',
                $ok ? 'ok' : 'failed',
                $ok ? 'SQLite integrity checks are clean.' : 'SQLite integrity or foreign-key checks failed.',
                [
                    'integrity_check' => $integrity->integrity_check ?? null,
                    'foreign_key_violations' => count($foreignKeys),
                ]
            );
        } catch (Throwable $exception) {
            return $this->check('database_integrity', 'failed', 'SQLite health check failed.', ['error' => $exception->getMessage()]);
        }
    }

    private function foreignKeysEnabled(): array
    {
        try {
            $result = DB::selectOne('PRAGMA foreign_keys');
            $enabled = (int) ($result->foreign_keys ?? 0) === 1;

            return $this->check(
                'sqlite_foreign_keys',
                $enabled ? 'ok' : 'failed',
                $enabled ? 'SQLite foreign keys are enabled.' : 'SQLite foreign keys are disabled.',
                ['enabled' => $enabled]
            );
        } catch (Throwable $exception) {
            return $this->check('sqlite_foreign_keys', 'failed', 'Unable to inspect SQLite foreign-key mode.', ['error' => $exception->getMessage()]);
        }
    }

    private function sqliteWriteMode(): array
    {
        try {
            $journal = DB::selectOne('PRAGMA journal_mode');
            $busyTimeout = DB::selectOne('PRAGMA busy_timeout');
            $journalMode = strtolower((string) ($journal->journal_mode ?? ''));
            $timeout = (int) ($busyTimeout->timeout ?? $busyTimeout->busy_timeout ?? 0);

            return $this->check(
                'sqlite_write_mode',
                $timeout >= 1000 ? 'ok' : 'warning',
                $timeout >= 1000 ? 'SQLite write contention timeout is configured.' : 'SQLite busy timeout is low.',
                ['journal_mode' => $journalMode, 'busy_timeout_ms' => $timeout]
            );
        } catch (Throwable $exception) {
            return $this->check('sqlite_write_mode', 'warning', 'Unable to inspect SQLite write settings.', ['error' => $exception->getMessage()]);
        }
    }

    private function storageWritable(): array
    {
        $path = 'health/healthcheck-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.tmp';

        try {
            Storage::disk('local')->put($path, 'ok');
            $exists = Storage::disk('local')->exists($path);
            Storage::disk('local')->delete($path);

            return $this->check(
                'storage_writable',
                $exists ? 'ok' : 'failed',
                $exists ? 'Local storage is writable.' : 'Local storage write verification failed.',
                ['disk' => 'local']
            );
        } catch (Throwable $exception) {
            return $this->check('storage_writable', 'failed', 'Local storage is not writable.', ['error' => $exception->getMessage()]);
        }
    }

    private function latestBackup(Household $household): array
    {
        $backup = BackupLog::query()
            ->where('household_id', $household->id)
            ->where('type', 'manual')
            ->latest('id')
            ->first();

        if (! $backup) {
            return $this->check('latest_backup', 'warning', 'No manual backup has been created yet.', ['backup_id' => null]);
        }

        $pathExists = $backup->path ? File::exists(Storage::disk($backup->disk ?: 'local')->path($backup->path)) : false;
        $ok = $backup->status === 'completed' && $pathExists;

        return $this->check(
            'latest_backup',
            $ok ? 'ok' : 'warning',
            $ok ? 'Latest manual backup is present.' : 'Latest manual backup is missing, failed, or incomplete.',
            [
                'backup_id' => $backup->id,
                'status' => $backup->status,
                'completed_at' => $backup->completed_at?->toIso8601String(),
                'path_exists' => $pathExists,
            ]
        );
    }

    private function syncBacklog(Household $household): array
    {
        $pending = SyncOperation::query()->where('household_id', $household->id)->where('status', 'pending')->count();
        $conflicts = SyncOperation::query()->where('household_id', $household->id)->where('status', 'conflict')->count();

        return $this->check(
            'offline_sync_backlog',
            $conflicts > 0 ? 'warning' : 'ok',
            $conflicts > 0 ? 'Offline sync conflicts need review.' : 'Offline sync has no conflicts.',
            ['pending' => $pending, 'conflicts' => $conflicts]
        );
    }

    private function check(string $name, string $status, string $message, array $meta = []): array
    {
        return compact('name', 'status', 'message', 'meta');
    }

    private function overallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        if (in_array('failed', $statuses, true)) {
            return 'failed';
        }

        return in_array('warning', $statuses, true) ? 'warning' : 'ok';
    }
}
