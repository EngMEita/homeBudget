<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\UpcomingBill;
use App\Notifications\UpcomingBillDueNotification;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('homebudget:send-bill-reminders {--days=7}', function () {
    $days = (int) $this->option('days');
    $bills = UpcomingBill::query()
        ->with('household.members')
        ->where('status', 'scheduled')
        ->where('reminder_status', 'pending')
        ->whereDate('due_on', '<=', now()->addDays($days)->toDateString())
        ->get();

    foreach ($bills as $bill) {
        foreach ($bill->household?->members ?? [] as $member) {
            $member->notify(new UpcomingBillDueNotification($bill));
        }
        $bill->forceFill(['reminder_status' => 'sent'])->save();
    }

    $this->info("Sent reminders for {$bills->count()} bills.");
})->purpose('Send upcoming household bill reminders');

Schedule::command('homebudget:send-bill-reminders')->dailyAt('08:00');
