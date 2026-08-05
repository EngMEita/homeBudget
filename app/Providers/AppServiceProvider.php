<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Household;
use App\Models\Transaction;
use App\Policies\HouseholdPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Household::class, HouseholdPolicy::class);
        Gate::guessPolicyNamesUsing(function (string $modelClass): ?string {
            return match ($modelClass) {
                Account::class => HouseholdPolicy::class,
                Transaction::class => HouseholdPolicy::class,
                default => null,
            };
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });
    }
}
