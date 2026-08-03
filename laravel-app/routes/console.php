<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\RentReminderService;

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
