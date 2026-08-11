<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\Household;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDO;
use Throwable;

class BackupService
{
    public function createManualBackup(Household $household, int $userId): BackupLog
    {
        $log = BackupLog::create([
            'household_id' => $household->id,
            'created_by' => $userId,
            'type' => 'manual',
            'status' => 'pending',
        ]);

        $source = database_path('database.sqlite');
        $path = sprintf('backups/household-%d-%s.sqlite', $household->id, now()->format('YmdHis'));

        if (! File::exists($source)) {
            $log->forceFill([
                'status' => 'failed',
                'health_check' => ['error' => 'SQLite database file was not found.'],
            ])->save();

            return $log;
        }

        $absoluteTarget = Storage::disk('local')->path($path);
        File::ensureDirectoryExists(dirname($absoluteTarget));
        $createdBySqlite = $this->createSqliteBackup($source, $absoluteTarget);
        if (! $createdBySqlite) {
            Storage::disk('local')->put($path, File::get($source));
        }

        $health = $this->healthCheck() + [
            'sha256' => hash_file('sha256', $absoluteTarget),
        ];
        $log->forceFill([
            'path' => $path,
            'size_bytes' => Storage::disk('local')->size($path),
            'status' => $health['ok'] ? 'completed' : 'warning',
            'health_check' => $health,
            'completed_at' => now(),
        ])->save();

        app(AuditLogService::class)->record($household, $userId, 'backup.created', $log, ['status' => $log->status]);

        return $log;
    }

    public function restore(Household $household, int $userId, BackupLog $backup): BackupLog
    {
        return Cache::lock('sqlite-backup-restore', 120)->block(5, function () use ($household, $userId, $backup): BackupLog {
            return $this->restoreWithExclusiveLock($household, $userId, $backup);
        });
    }

    private function restoreWithExclusiveLock(Household $household, int $userId, BackupLog $backup): BackupLog
    {
        abort_unless($backup->household_id === $household->id && $backup->path, 404);

        $target = database_path('database.sqlite');
        $source = Storage::disk($backup->disk)->path($backup->path);
        abort_unless(File::exists($source), 404);

        $sourceHealth = $this->healthCheckPath($source);
        if ($backup->health_check['sha256'] ?? null) {
            $sourceHash = hash_file('sha256', $source);
            if (! hash_equals((string) $backup->health_check['sha256'], $sourceHash)) {
                $sourceHealth = array_merge($sourceHealth, [
                    'ok' => false,
                    'checksum_expected' => $backup->health_check['sha256'],
                    'checksum_actual' => $sourceHash,
                ]);
            }
        }
        if (! $sourceHealth['ok']) {
            return $this->recordFailedRestore($household, $userId, $backup, [
                'reason' => isset($sourceHealth['checksum_expected'])
                    ? 'Selected backup checksum does not match the recorded backup manifest.'
                    : 'Selected backup failed validation before restore.',
                'source_health_check' => $sourceHealth,
            ]);
        }

        $preRestore = $this->createManualBackup($household, $userId);

        DB::disconnect();
        File::copy($source, $target);
        DB::reconnect();

        $health = $this->healthCheck();
        if (! $health['ok']) {
            $rollbackHealth = $this->rollbackFailedRestore($preRestore, $target);
            $health = $health + ['rollback_health_check' => $rollbackHealth];
        }

        $restoreLog = BackupLog::create([
            'household_id' => $household->id,
            'created_by' => $userId,
            'type' => 'restore',
            'disk' => $backup->disk ?: 'local',
            'path' => $backup->path,
            'size_bytes' => File::size($target),
            'status' => $health['ok'] ? 'completed' : 'failed',
            'health_check' => $health + ['pre_restore_backup_id' => $preRestore->id],
            'completed_at' => now(),
        ]);

        app(AuditLogService::class)->record($household, $userId, 'backup.restored', $restoreLog, [
            'backup_log_id' => $backup->id,
            'pre_restore_backup_id' => $preRestore->id,
        ]);

        return $restoreLog;
    }

    public function healthCheck(): array
    {
        $integrity = DB::select('PRAGMA integrity_check');
        $foreignKeys = DB::select('PRAGMA foreign_key_check');

        return [
            'ok' => ($integrity[0]->integrity_check ?? null) === 'ok' && count($foreignKeys) === 0,
            'integrity_check' => $integrity[0]->integrity_check ?? null,
            'foreign_key_violations' => count($foreignKeys),
        ];
    }

    private function createSqliteBackup(string $source, string $target): bool
    {
        $sqlite = PHP_OS_FAMILY === 'Windows' ? 'sqlite3.exe' : 'sqlite3';
        $command = sprintf('%s %s ".backup %s"', escapeshellcmd($sqlite), escapeshellarg($source), escapeshellarg($target));
        exec($command, $output, $exitCode);

        return $exitCode === 0 && File::exists($target);
    }

    private function healthCheckPath(string $path): array
    {
        try {
            $pdo = new PDO('sqlite:'.$path);
            $integrity = $pdo->query('PRAGMA integrity_check')->fetchColumn();
            $foreignKeys = $pdo->query('PRAGMA foreign_key_check')->fetchAll();

            return [
                'ok' => $integrity === 'ok' && count($foreignKeys) === 0,
                'integrity_check' => $integrity,
                'foreign_key_violations' => count($foreignKeys),
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function rollbackFailedRestore(BackupLog $preRestore, string $target): array
    {
        if (! $preRestore->path) {
            return ['ok' => false, 'error' => 'No pre-restore backup was available.'];
        }

        $source = Storage::disk($preRestore->disk)->path($preRestore->path);
        if (! File::exists($source)) {
            return ['ok' => false, 'error' => 'Pre-restore backup file was missing.'];
        }

        DB::disconnect();
        File::copy($source, $target);
        DB::reconnect();

        return $this->healthCheck();
    }

    private function recordFailedRestore(Household $household, int $userId, BackupLog $backup, array $health): BackupLog
    {
        $restoreLog = BackupLog::create([
            'household_id' => $household->id,
            'created_by' => $userId,
            'type' => 'restore',
            'disk' => $backup->disk ?: 'local',
            'path' => $backup->path,
            'size_bytes' => 0,
            'status' => 'failed',
            'health_check' => $health + ['backup_log_id' => $backup->id],
            'completed_at' => now(),
        ]);

        app(AuditLogService::class)->record($household, $userId, 'backup.restore_failed', $restoreLog, [
            'backup_log_id' => $backup->id,
        ]);

        return $restoreLog;
    }
}
