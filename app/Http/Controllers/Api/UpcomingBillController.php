<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUpcomingBillRequest;
use App\Http\Resources\UpcomingBillResource;
use App\Models\Household;
use App\Services\RecurringService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UpcomingBillController extends Controller
{
    public function index(Household $household): AnonymousResourceCollection
    {
        Gate::authorize('viewTransactions', $household);

        return UpcomingBillResource::collection($household->upcomingBills()->orderBy('due_on')->paginate(20));
    }

    public function store(StoreUpcomingBillRequest $request, Household $household, RecurringService $service): UpcomingBillResource
    {
        $bill = $service->createBill($household, $request->user()->id, $request->validated());

        return new UpcomingBillResource($bill);
    }
}
