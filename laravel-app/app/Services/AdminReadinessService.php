<?php

namespace App\Services;

use App\Models\User;

class AdminReadinessService
{
    public function __construct(private readonly PlatformSettingsService $platformSettings) {}

    public function for(User $user): array
    {
        $checkpoints = [];
        $profileMissing = collect([
            'name' => filled($user->first_name) || filled($user->name),
            'email address' => filled($user->email),
            'phone number' => filled($user->phone_number),
        ])->reject()->keys()->values()->all();

        $checkpoints['profile'] = [
            'label' => 'Account profile',
            'complete' => $profileMissing === [],
            'message' => $profileMissing === []
                ? 'Your contact profile is complete.'
                : 'Add your '.implode(', ', $profileMissing).'.',
            'tab' => 'account',
        ];

        if ($user->isLandlord()) {
            $owner = $user->isLandlordStaff() ? $user->landlordAccountOwner()->first() : $user;
            $settings = $owner && is_array($owner->app_settings) ? ($owner->app_settings['paymentSettings'] ?? []) : [];
            $paymentType = strtoupper((string) ($settings['payment_type'] ?? 'PAYBILL')) === 'TILL' ? 'TILL' : 'PAYBILL';
            $number = trim((string) ($paymentType === 'TILL'
                ? ($settings['till_number'] ?? '')
                : ($settings['paybill_number'] ?? '')));
            $reference = trim((string) ($settings['account_reference'] ?? ''));
            $paymentReady = $number !== '' && ($paymentType === 'TILL'
                || ($reference !== '' && ! in_array(strtolower($reference), ['tenant pro', 'starmax tenant services'], true)));

            $checkpoints['payment'] = [
                'label' => 'Tenant payment channel',
                'complete' => $paymentReady,
                'message' => $paymentReady
                    ? "{$paymentType} details are ready for tenant payments."
                    : ($user->isLandlordStaff()
                        ? 'The account owner must finish the PayBill or Till setup before tenant onboarding.'
                        : 'Configure a PayBill or Till number'.($paymentType === 'PAYBILL' ? ' and account reference' : '').'.'),
                'tab' => $user->isLandlordOwner() ? 'payment' : null,
            ];
        }

        if ($user->role?->name === 'SUPER_ADMIN') {
            $daraja = $this->platformSettings->daraja();
            $missing = collect([
                'consumer key' => filled($daraja['consumer_key']),
                'consumer secret' => filled($daraja['consumer_secret']),
                'shortcode' => filled($daraja['shortcode']),
                'passkey' => filled($daraja['passkey']),
                'callback URL' => filled($daraja['callback_url']),
            ])->reject()->keys()->values()->all();

            $checkpoints['daraja'] = [
                'label' => 'Platform Daraja API',
                'complete' => $missing === [],
                'message' => $missing === []
                    ? 'Platform M-Pesa credentials are configured.'
                    : 'Missing '.implode(', ', $missing).'.',
                'tab' => 'daraja',
            ];
        }

        $missing = collect($checkpoints)->reject(fn (array $checkpoint) => $checkpoint['complete'])->all();

        return [
            'complete' => $missing === [],
            'checkpoints' => $checkpoints,
            'missing' => $missing,
            'missing_count' => count($missing),
        ];
    }
}
