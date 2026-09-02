<?php

namespace Tests\Feature;

use App\Services\MpesaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MpesaServiceTest extends TestCase
{
    public function test_it_builds_the_daraja_sandbox_stk_request(): void
    {
        config([
            'services.mpesa.environment' => 'sandbox',
            'services.mpesa.consumer_key' => 'test-key',
            'services.mpesa.consumer_secret' => 'test-secret',
            'services.mpesa.shortcode' => '174379',
            'services.mpesa.passkey' => str_repeat('a', 64),
            'services.mpesa.callback_url' => 'https://example.test/api/payments/mpesa/callback',
        ]);
        Cache::forget('mpesa.oauth_token');
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'sandbox-token',
                'expires_in' => '3599',
            ]),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'MerchantRequestID' => 'merchant-1',
                'CheckoutRequestID' => 'checkout-1',
                'ResponseCode' => '0',
                'CustomerMessage' => 'Success',
            ]),
        ]);

        $result = app(MpesaService::class)->stkPush('0708374149', 1, 'invoice-reference', 'Starmax Tenant Services payment');

        $this->assertSame('checkout-1', $result['CheckoutRequestID']);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            && $request['BusinessShortCode'] === '174379'
            && $request['PartyA'] === '254708374149'
            && $request['PartyB'] === '174379'
            && $request['Amount'] === 1
            && $request['CallBackURL'] === 'https://example.test/api/payments/mpesa/callback'
            && ! empty($request['Password'])
            && ! empty($request['Timestamp'])
        );
    }

    public function test_public_callback_rejects_an_invalid_payload(): void
    {
        $this->postJson('/api/payments/mpesa/callback', [])
            ->assertStatus(400)
            ->assertJson(['message' => 'Invalid M-Pesa callback payload.']);
    }
}
