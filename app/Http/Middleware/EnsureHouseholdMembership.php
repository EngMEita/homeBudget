<?php

namespace App\Http\Middleware;

use App\Models\Household;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHouseholdMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $household = $request->route('household');

        if ($household instanceof Household && ! $request->user()?->households()->whereKey($household->getKey())->exists()) {
            abort(403);
        }

        return $next($request);
    }
}
