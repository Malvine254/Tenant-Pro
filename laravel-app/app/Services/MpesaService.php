<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaService
{
    public function stkPush(string $phone, float $amount, string $reference, string $description, ?array $landlordSettings = null): array
    {
        $timestamp = now()->format('YmdHis');
        $settings = $this->resolveDarajaConfig($landlordSettings);
        $shortCode = (string) ($settings['shortcode'] ?? config('services.mpesa.shortcode'));
        $passkey = (string) ($settings['passkey'] ?? config('services.mpesa.passkey'));
        $transactionType = (string) ($settings['transaction_type'] ?? 'CustomerPayBillOnline');
        $accountReference = (string) ($settings['account_reference'] ?? substr($reference, 0, 12));

        if ($shortCode === '' || $passkey === '') {
            throw new RuntimeException('M-Pesa shortcode or passkey is not configured.');
        }

        $response = $this->client()->post('/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $shortCode,
            'Password' => base64_encode($shortCode.$passkey.$timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => $transactionType,
            'Amount' => (int) ceil($amount),
            'PartyA' => $this->normalizePhone($phone),
            'PartyB' => (string) ($settings['party_b'] ?? $shortCode),
            'PhoneNumber' => $this->normalizePhone($phone),
            'CallBackURL' => (string) config('services.mpesa.callback_url'),
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($description, 0, 30),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('M-Pesa rejected the STK Push request (HTTP '.$response->status().').');
        }

        $data = $response->json();
        if (($data['ResponseCode'] ?? null) !== '0' || empty($data['CheckoutRequestID'])) {
            throw new RuntimeException($data['errorMessage'] ?? $data['ResponseDescription'] ?? 'M-Pesa did not accept the STK Push request.');
        }

        return $data;
    }

    private function resolveDarajaConfig(?array $landlordSettings): array
    {
        $settings = is_array($landlordSettings) ? $landlordSettings : [];
        $paymentType = strtoupper((string) ($settings['payment_type'] ?? 'PAYBILL'));

        if ($paymentType === 'TILL') {
            $shortcode = (string) ($settings['till_number'] ?? config('services.mpesa.shortcode'));
            return [
                'shortcode' => $shortcode,
                'passkey' => (string) config('services.mpesa.passkey'),
                'transaction_type' => 'CustomerBuyGoodsOnline',
                'party_b' => $shortcode,
                'account_reference' => (string) ($settings['account_reference'] ?? 'Tenant Pro'),
            ];
        }

        $shortcode = (string) ($settings['paybill_number'] ?? config('services.mpesa.shortcode'));
        return [
            'shortcode' => $shortcode,
            'passkey' => (string) config('services.mpesa.passkey'),
            'transaction_type' => 'CustomerPayBillOnline',
            'party_b' => $shortcode,
            'account_reference' => (string) ($settings['account_reference'] ?? 'Tenant Pro'),
        ];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->timeout(30);
    }

    private function accessToken(): string
    {
        return Cache::remember('mpesa.oauth_token', now()->addMinutes(55), function (): string {
            $key = (string) config('services.mpesa.consumer_key');
            $secret = (string) config('services.mpesa.consumer_secret');
            if ($key === '' || $secret === '') {
                throw new RuntimeException('M-Pesa consumer credentials are not configured.');
            }

            $response = Http::withBasicAuth($key, $secret)
                ->acceptJson()
                ->timeout(30)
                ->get($this->baseUrl().'/oauth/v1/generate', ['grant_type' => 'client_credentials']);

            if (! $response->successful() || empty($response->json('access_token'))) {
                throw new RuntimeException('Unable to authenticate with M-Pesa.');
            }

            return (string) $response->json('access_token');
        });
    }

    private function baseUrl(): string
    {
        return config('services.mpesa.environment') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }
        if (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            $phone = '254'.$phone;
        }

        if (! preg_match('/^254[17]\d{8}$/', $phone)) {
            throw new RuntimeException('Enter a valid Kenyan M-Pesa number.');
        }

        return $phone;
    }
}
