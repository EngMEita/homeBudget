<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\Household;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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

        $health = $this->healthCheck();
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
        abort_unless($backup->household_id === $household->id && $backup->path, 404);

        $target = database_path('database.sqlite');
        $source = Storage::disk($backup->disk)->path($backup->path);
        abort_unless(File::exists($source), 404);

        $preRestore = $this->createManualBackup($household, $userId);
        File::copy($source, $target);

        $health = $this->healthCheck();
        $restoreLog = BackupLog::create([
            'household_id' => $household->id,
            'created_by' => $userId,
            'type' => 'restore',
            'disk' => $backup->disk,
            'path' => $backup->path,
            'size_bytes' => File::size($target),
            'status' => $health['ok'] ? 'completed' : 'warning',
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
}
