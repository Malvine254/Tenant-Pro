<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class LandlordSubscriptionReminderService
{
    private const REMINDER_DAYS = [7, 3, 1, 0];

    public function __construct(
        private readonly TenantEmailService $emailService,
        private readonly TenantAppNotificationService $notificationService,
    ) {
    }

    public function runDailyReminders(): array
    {
        $today = Carbon::today();
        $sent = 0;
        $skipped = 0;

        $landlords = User::query()
            ->with('role')
            ->where('requires_subscription', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'LANDLORD'))
            ->get();

        foreach ($landlords as $landlord) {
            $dueDate = $landlord->service_paid_until ?? $landlord->trial_ends_at;
            if (!$dueDate instanceof Carbon) {
                $skipped++;
                continue;
            }

            $dueDate = $dueDate->copy()->startOfDay();
            $daysUntilDue = $today->diffInDays($dueDate, false);
            if (!in_array($daysUntilDue, self::REMINDER_DAYS, true)) {
                $skipped++;
                continue;
            }

            $reminderKey = 'subscription-due-'.$dueDate->toDateString().'-'.$daysUntilDue;
            if ($this->alreadySent($landlord->id, $reminderKey)) {
                $skipped++;
                continue;
            }

            $body = $daysUntilDue === 0
                ? 'Your TenantPro subscription is due today. Renew to avoid interruption to portal access.'
                : 'Your TenantPro subscription is due in '.$daysUntilDue.' day'.($daysUntilDue === 1 ? '' : 's').'.';

            $this->notificationService->notify(
                $landlord,
                'SUBSCRIPTION_REMINDER',
                $daysUntilDue === 0 ? 'Subscription due today' : 'Subscription renewal reminder',
                $body,
                [
                    'reminder_key' => $reminderKey,
                    'due_date' => $dueDate->toDateString(),
                    'days_until_due' => $daysUntilDue,
                ]
            );
            $this->emailService->sendSubscriptionDueReminder($landlord, $dueDate, $daysUntilDue);
            $sent++;
        }

        Log::info('Landlord subscription reminders completed', [
            'considered' => $landlords->count(),
            'sent' => $sent,
            'skipped' => $skipped,
        ]);

        return ['considered' => $landlords->count(), 'sent' => $sent, 'skipped' => $skipped];
    }

    private function alreadySent(string $landlordId, string $reminderKey): bool
    {
        return Notification::query()
            ->where('user_id', $landlordId)
            ->where('type', 'SUBSCRIPTION_REMINDER')
            ->where('metadata->reminder_key', $reminderKey)
            ->exists();
    }
}
