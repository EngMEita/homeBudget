<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestoreBackupRequest;
use App\Http\Resources\BackupLogResource;
use App\Models\BackupLog;
use App\Models\Household;
use App\Services\BackupService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BackupController extends Controller
{
    public function index(Household $household): AnonymousResourceCollection
    {
        Gate::authorize('export', $household);

        return BackupLogResource::collection($household->backupLogs()->latest('id')->paginate(20));
    }

    public function store(Household $household, BackupService $service): BackupLogResource
    {
        Gate::authorize('export', $household);

        return new BackupLogResource($service->createManualBackup($household, request()->user()->id));
    }

    public function restore(RestoreBackupRequest $request, Household $household, BackupService $service): BackupLogResource
    {
        $backup = BackupLog::query()->whereKey($request->integer('backup_log_id'))->firstOrFail();

        return new BackupLogResource($service->restore($household, $request->user()->id, $backup));
    }
}
