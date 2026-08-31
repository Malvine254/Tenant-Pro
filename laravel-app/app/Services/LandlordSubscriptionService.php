<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class LandlordSubscriptionService
{
    public const STATUS_NOT_REQUIRED = 'not_required';
    public const STATUS_TRIAL = 'trial';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';

    public function __construct(
        private readonly TenantEmailService $emailService,
        private readonly TenantAppNotificationService $notificationService,
    ) {
    }

    public function initializeTrial(User $landlord, float $monthlyFee = 2500.00): User
    {
        if (!$landlord->isLandlord()) {
            return $landlord;
        }

        if ($landlord->trial_started_at || $landlord->subscription_started_at || $landlord->requires_subscription) {
            return $landlord;
        }

        $now = now();
        $landlord->update([
            'requires_subscription' => true,
            'billing_status' => self::STATUS_TRIAL,
            'monthly_service_fee' => $monthlyFee,
            'trial_started_at' => $now,
            'trial_ends_at' => $now->copy()->addMonth(),
            'subscription_started_at' => null,
            'subscription_last_paid_at' => null,
            'service_paid_until' => null,
        ]);

        return $landlord->refresh();
    }

    public function evaluate(User $user): array
    {
        if (!$user->isLandlord()) {
            return [
                'allowed' => true,
                'status' => self::STATUS_NOT_REQUIRED,
                'message' => null,
                'due_at' => null,
                'days_remaining' => null,
            ];
        }

        if ($user->isLandlordStaff()) {
            $owner = $user->landlordAccountOwner;
            if (! $owner || ! $owner->is_active) {
                return [
                    'allowed' => false,
                    'status' => self::STATUS_PAST_DUE,
                    'message' => 'The landlord account that granted your access is inactive.',
                    'due_at' => null,
                    'days_remaining' => 0,
                ];
            }

            return $this->evaluate($owner);
        }

        // Backfill legacy landlord records that missed trial initialization.
        if (!$user->trial_started_at && !$user->trial_ends_at && !$user->subscription_started_at && !$user->service_paid_until) {
            $this->initializeTrial($user);
            $user->refresh();
        }

        if ($user->service_paid_until instanceof Carbon && $user->service_paid_until->isFuture()) {
            if ($user->billing_status !== self::STATUS_ACTIVE || !$user->requires_subscription) {
                $user->update([
                    'requires_subscription' => true,
                    'billing_status' => self::STATUS_ACTIVE,
                ]);
            }

            $dueAt = $this->localDueAt($user->service_paid_until);
            return [
                'allowed' => true,
                'status' => self::STATUS_ACTIVE,
                'message' => 'Subscription active until '.$dueAt->format('d M Y, H:i').'.',
                'due_at' => $dueAt,
                'days_remaining' => $this->daysRemaining($dueAt),
            ];
        }

        if (!$user->service_paid_until && !$user->subscription_started_at
            && $user->trial_ends_at instanceof Carbon && $user->trial_ends_at->isFuture()) {
            if ($user->billing_status !== self::STATUS_TRIAL || !$user->requires_subscription) {
                $user->update([
                    'requires_subscription' => true,
                    'billing_status' => self::STATUS_TRIAL,
                ]);
            }

            $dueAt = $this->localDueAt($user->trial_ends_at);
            return [
                'allowed' => true,
                'status' => self::STATUS_TRIAL,
                'message' => 'Free trial active until '.$dueAt->format('d M Y, H:i').'.',
                'due_at' => $dueAt,
                'days_remaining' => $this->daysRemaining($dueAt),
            ];
        }

        if (!$user->requires_subscription && !$user->trial_ends_at) {
            return [
                'allowed' => true,
                'status' => self::STATUS_NOT_REQUIRED,
                'message' => null,
                'due_at' => null,
                'days_remaining' => null,
            ];
        }

        if ($user->billing_status !== self::STATUS_PAST_DUE) {
            $user->update([
                'requires_subscription' => true,
                'billing_status' => self::STATUS_PAST_DUE,
            ]);
        }

        $dueDate = ($user->service_paid_until ?? $user->trial_ends_at)?->copy();
        $dueDate = $dueDate ? $this->localDueAt($dueDate) : null;
        $endedOn = $dueDate?->format('d M Y') ?? 'the renewal date';
        $wasPaidSubscription = $user->service_paid_until !== null || $user->subscription_started_at !== null;

        return [
            'allowed' => false,
            'status' => self::STATUS_PAST_DUE,
            'message' => ($wasPaidSubscription ? 'Your TenantPro subscription expired on ' : 'Your free trial ended on ')
                .$endedOn.'. Tenant billing, payments, maintenance, invitations and support operations are locked until the subscription is renewed.',
            'due_at' => $dueDate?->copy(),
            'days_remaining' => 0,
        ];
    }

    public function recordPayment(User $landlord, int $months = 1): User
    {
        if (!$landlord->isLandlord()) {
            return $landlord;
        }

        $startAt = $landlord->service_paid_until && $landlord->service_paid_until->isFuture()
            ? $landlord->service_paid_until->copy()
            : now();

        $landlord->update([
            'requires_subscription' => true,
            'billing_status' => self::STATUS_ACTIVE,
            'subscription_started_at' => $landlord->subscription_started_at ?? now(),
            'subscription_last_paid_at' => now(),
            'service_paid_until' => $startAt->addMonths(max(1, $months)),
        ]);

        $landlord = $landlord->refresh();

        $this->notificationService->notify(
            $landlord,
            'SUBSCRIPTION_RENEWED',
            'Subscription active',
            'Your renewal was recorded. Tenant operations are available immediately.',
            [
                'service_paid_until' => $landlord->service_paid_until?->toISOString(),
                'destination' => 'SUBSCRIPTION',
            ]
        );
        $this->emailService->sendSubscriptionRenewed($landlord);

        return $landlord;
    }

    private function daysRemaining(Carbon $dueAt): int
    {
        $timezone = config('deployment.subscription_timezone', 'Africa/Nairobi');
        return max(0, Carbon::today($timezone)->diffInDays($dueAt->copy()->startOfDay(), false));
    }

    private function localDueAt(Carbon $dueAt): Carbon
    {
        return $dueAt->copy()->timezone(config('deployment.subscription_timezone', 'Africa/Nairobi'));
    }
}
