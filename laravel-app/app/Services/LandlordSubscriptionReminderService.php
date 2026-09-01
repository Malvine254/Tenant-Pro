<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class LandlordSubscriptionReminderService
{
    private const REMINDER_MILESTONES = [0, 1, 3, 7];

    public function __construct(
        private readonly TenantEmailService $emailService,
        private readonly TenantAppNotificationService $notificationService,
        private readonly LandlordSubscriptionService $subscriptionService,
    ) {}

    public function runDailyReminders(): array
    {
        $timezone = config('deployment.subscription_timezone', 'Africa/Nairobi');
        $today = Carbon::today($timezone);
        $sent = 0;
        $skipped = 0;

        $this->synchronizeExpirations();

        $landlords = User::query()
            ->with('role')
            ->where('requires_subscription', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'LANDLORD'))
            ->whereNull('managed_landlord_id')
            ->get();

        foreach ($landlords as $landlord) {
            if (! $this->subscriptionService->evaluate($landlord)['allowed']) {
                $skipped++;

                continue;
            }

            $dueDate = $landlord->service_paid_until ?? $landlord->trial_ends_at;
            if (! $dueDate instanceof Carbon) {
                $skipped++;

                continue;
            }

            $dueDate = $dueDate->copy()->timezone($timezone)->startOfDay();
            $daysUntilDue = $today->diffInDays($dueDate, false);
            if ($daysUntilDue < 0 || $daysUntilDue > max(self::REMINDER_MILESTONES)) {
                $skipped++;

                continue;
            }

            // If a scheduler run was missed, send the nearest applicable reminder
            // rather than permanently losing the seven/three/one-day notice.
            $milestone = collect(self::REMINDER_MILESTONES)
                ->first(fn (int $threshold) => $threshold >= $daysUntilDue);

            $reminderKey = 'subscription-due-'.$dueDate->toDateString().'-'.$milestone;
            if ($this->alreadySent($landlord->id, $reminderKey)) {
                $skipped++;

                continue;
            }

            $body = $daysUntilDue === 0
                ? 'Your TenantPro subscription is due today. Renew to avoid interruption to tenant operations.'
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
                    'reminder_milestone' => $milestone,
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

    public function synchronizeExpirations(): array
    {
        $locked = 0;
        $checked = 0;

        $landlords = User::query()
            ->with('role')
            ->where('requires_subscription', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'LANDLORD'))
            ->whereNull('managed_landlord_id')
            ->where(function ($query) {
                $query->whereNotNull('service_paid_until')
                    ->orWhereNotNull('trial_ends_at');
            })
            ->get();

        foreach ($landlords as $landlord) {
            $checked++;
            $evaluation = $this->subscriptionService->evaluate($landlord);
            if ($evaluation['allowed']) {
                continue;
            }

            $dueDate = $evaluation['due_at'] ?? $landlord->service_paid_until ?? $landlord->trial_ends_at;
            $dueKey = $dueDate instanceof Carbon ? $dueDate->toIso8601String() : 'unknown';
            $lockKey = 'subscription-locked-'.$dueKey;
            if ($this->alreadySent($landlord->id, $lockKey, 'SUBSCRIPTION_LOCKED')) {
                continue;
            }

            $body = 'Your subscription has expired. Tenant billing, payments, maintenance, invitations and support operations are now locked until renewal.';
            $this->notificationService->notify(
                $landlord,
                'SUBSCRIPTION_LOCKED',
                'Tenant operations locked',
                $body,
                [
                    'reminder_key' => $lockKey,
                    'due_date' => $dueDate instanceof Carbon ? $dueDate->toDateString() : null,
                    'destination' => 'SUBSCRIPTION',
                ]
            );
            $this->emailService->sendSubscriptionLocked($landlord, $dueDate instanceof Carbon ? $dueDate : null);
            $locked++;
        }

        return ['checked' => $checked, 'locked' => $locked];
    }

    private function alreadySent(
        string $landlordId,
        string $reminderKey,
        string $type = 'SUBSCRIPTION_REMINDER'
    ): bool {
        return Notification::query()
            ->where('user_id', $landlordId)
            ->where('type', $type)
            ->where('metadata->reminder_key', $reminderKey)
            ->exists();
    }
}
