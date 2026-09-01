<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaService
{
    public function __construct(private readonly PlatformSettingsService $platformSettings) {}

    public function isConfigured(): bool
    {
        $daraja = $this->platformSettings->daraja();

        return filled($daraja['consumer_key'])
            && filled($daraja['consumer_secret'])
            && filled($daraja['passkey'])
            && filled($daraja['callback_url']);
    }

    public function stkPush(string $phone, float $amount, string $reference, string $description, ?array $landlordSettings = null): array
    {
        $timestamp = now()->format('YmdHis');
        $settings = $this->resolveDarajaConfig($landlordSettings);
        $shortCode = trim((string) ($settings['shortcode'] ?? ''));
        $passkey = (string) ($settings['passkey'] ?? $this->platformSettings->daraja()['passkey']);
        $transactionType = (string) ($settings['transaction_type'] ?? 'CustomerPayBillOnline');
        $accountReference = trim((string) ($settings['account_reference'] ?? ''));

        if ($shortCode === '' || $passkey === '' || $accountReference === '') {
            throw new RuntimeException('M-Pesa payment details are incomplete. Configure the shortcode and account reference first.');
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
            'CallBackURL' => (string) $this->platformSettings->daraja()['callback_url'],
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
            $shortcode = trim((string) ($settings['till_number'] ?? ''));
            if ($shortcode === '') {
                throw new RuntimeException('A Till number must be configured before requesting an STK Push.');
            }

            return [
                'shortcode' => $shortcode,
                'passkey' => (string) $this->platformSettings->daraja()['passkey'],
                'transaction_type' => 'CustomerBuyGoodsOnline',
                'party_b' => $shortcode,
                'account_reference' => trim((string) ($settings['account_reference'] ?? '')) ?: 'Till payment',
            ];
        }

        $shortcode = trim((string) ($settings['paybill_number'] ?? ''));
        $accountReference = trim((string) ($settings['account_reference'] ?? ''));
        if ($shortcode === '' || $accountReference === '' || strcasecmp($accountReference, 'Tenant Pro') === 0) {
            throw new RuntimeException('A Paybill number and account reference must be configured before requesting an STK Push.');
        }

        return [
            'shortcode' => $shortcode,
            'passkey' => (string) $this->platformSettings->daraja()['passkey'],
            'transaction_type' => 'CustomerPayBillOnline',
            'party_b' => $shortcode,
            'account_reference' => $accountReference,
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
        $daraja = $this->platformSettings->daraja();
        $key = (string) $daraja['consumer_key'];
        $secret = (string) $daraja['consumer_secret'];
        if ($key === '' || $secret === '') {
            throw new RuntimeException('M-Pesa API credentials are incomplete. Configure the consumer key and secret first.');
        }
        $cacheKey = 'mpesa.oauth_token.'.hash('sha256', $daraja['environment'].'|'.$key.'|'.$secret);

        return Cache::remember($cacheKey, now()->addMinutes(55), function () use ($key, $secret): string {
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
        return $this->platformSettings->daraja()['environment'] === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function testConnection(): void
    {
        $this->accessToken();
    }

    public function environment(): string
    {
        return (string) $this->platformSettings->daraja()['environment'];
    }

    public function simulationEnabled(): bool
    {
        return $this->environment() === 'sandbox'
            && (bool) $this->platformSettings->daraja()['simulate'];
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
