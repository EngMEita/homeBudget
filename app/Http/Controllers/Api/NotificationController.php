<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function index(Household $household): AnonymousResourceCollection
    {
        Gate::authorize('view', $household);

        return NotificationResource::collection(
            request()->user()
                ->notifications()
                ->where('data->household_id', $household->id)
                ->latest()
                ->paginate(20)
        );
    }

    public function markRead(Household $household, string $notificationId): JsonResponse
    {
        Gate::authorize('view', $household);

        $notification = request()->user()
            ->notifications()
            ->where('data->household_id', $household->id)
            ->whereKey($notificationId)
            ->firstOrFail();

        /** @var DatabaseNotification $notification */
        $notification->markAsRead();

        return response()->json(['read' => true]);
    }
}
