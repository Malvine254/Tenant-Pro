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
            ];
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

            return [
                'allowed' => true,
                'status' => self::STATUS_ACTIVE,
                'message' => null,
            ];
        }

        if ($user->trial_ends_at instanceof Carbon && $user->trial_ends_at->isFuture()) {
            if ($user->billing_status !== self::STATUS_TRIAL || !$user->requires_subscription) {
                $user->update([
                    'requires_subscription' => true,
                    'billing_status' => self::STATUS_TRIAL,
                ]);
            }

            return [
                'allowed' => true,
                'status' => self::STATUS_TRIAL,
                'message' => 'Free trial active until '.$user->trial_ends_at->format('d M Y').'.',
            ];
        }

        if (!$user->requires_subscription && !$user->trial_ends_at) {
            return [
                'allowed' => true,
                'status' => self::STATUS_NOT_REQUIRED,
                'message' => null,
            ];
        }

        if ($user->billing_status !== self::STATUS_PAST_DUE) {
            $user->update([
                'requires_subscription' => true,
                'billing_status' => self::STATUS_PAST_DUE,
            ]);
        }

        $trialEndedOn = $user->trial_ends_at?->format('d M Y') ?? 'your trial end date';

        return [
            'allowed' => false,
            'status' => self::STATUS_PAST_DUE,
            'message' => 'Your free trial ended on '.$trialEndedOn.'. Please renew your service subscription to access the portal.',
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

        return $landlord->refresh();
    }
}
