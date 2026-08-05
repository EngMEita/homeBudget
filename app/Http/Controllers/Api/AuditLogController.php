<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\Household;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function __invoke(Household $household): AnonymousResourceCollection
    {
        Gate::authorize('manage', $household);

        return AuditLogResource::collection($household->auditLogs()->latest('id')->paginate(30));
    }
}
