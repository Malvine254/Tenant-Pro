<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\RentReminderService;
use App\Services\LandlordSubscriptionReminderService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tenantpro:send-rent-reminders', function (RentReminderService $service) {
    $result = $service->runDailyReminders();
    $this->info(sprintf(
        'Rent reminders done. Generated: %d, Considered: %d, Sent: %d, Skipped: %d',
        $result['generated'] ?? 0,
        $result['invoicesConsidered'] ?? 0,
        $result['sent'] ?? 0,
        $result['skipped'] ?? 0,
    ));
})->purpose('Send periodic rent due and overdue reminders with in-app notifications');

Schedule::command('tenantpro:send-rent-reminders')
    ->dailyAt(env('RENT_REMINDER_TIME', '09:15'))
    ->withoutOverlapping()
    ->onOneServer();

Artisan::command('tenantpro:send-subscription-reminders', function (LandlordSubscriptionReminderService $service) {
    $result = $service->runDailyReminders();
    $this->info(sprintf(
        'Subscription reminders done. Considered: %d, Sent: %d, Skipped: %d',
        $result['considered'] ?? 0,
        $result['sent'] ?? 0,
        $result['skipped'] ?? 0,
    ));
})->purpose('Send landlord subscription renewal reminders by email and notification');

Artisan::command('tenantpro:sync-landlord-subscriptions', function (LandlordSubscriptionReminderService $service) {
    $result = $service->synchronizeExpirations();
    $this->info(sprintf(
        'Subscription status sync done. Checked: %d, Newly locked: %d',
        $result['checked'] ?? 0,
        $result['locked'] ?? 0,
    ));
})->purpose('Apply expired landlord subscription locks and notify affected landlords');

Schedule::command('tenantpro:sync-landlord-subscriptions')
    ->everyMinute()
    ->timezone(config('deployment.subscription_timezone', 'Africa/Nairobi'))
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('tenantpro:send-subscription-reminders')
    ->dailyAt(env('SUBSCRIPTION_REMINDER_TIME', '09:30'))
    ->timezone(config('deployment.subscription_timezone', 'Africa/Nairobi'))
    ->withoutOverlapping()
    ->onOneServer();
