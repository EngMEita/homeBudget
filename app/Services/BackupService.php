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

        Storage::disk('local')->put($path, File::get($source));

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
}
