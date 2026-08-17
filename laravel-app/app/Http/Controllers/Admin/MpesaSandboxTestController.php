<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MpesaSandboxTestController extends Controller
{
    public function index()
    {
        return view('admin.mpesa-sandbox-test', [
            'environment' => config('services.mpesa.environment', 'sandbox'),
            'simulate' => filter_var(config('services.mpesa.simulate', true), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function store(Request $request, MpesaService $mpesa)
    {
        $data = $request->validate([
            'payment_type' => ['required', 'string', 'in:PAYBILL,TILL'],
            'short_code' => ['required', 'string', 'max:20'],
            'phone_number' => ['required', 'string', 'regex:/^(?:254|\\+254|0)?[17]\\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1', 'max:500000'],
            'account_reference' => ['nullable', 'string', 'max:50'],
        ]);

        $paymentType = strtoupper((string) $data['payment_type']);
        $shortCode = trim((string) $data['short_code']);
        $phoneNumber = trim((string) $data['phone_number']);
        $amount = round((float) $data['amount'], 2);
        $reference = 'sandbox-test-'.Str::uuid()->toString();
        $accountReference = trim((string) ($data['account_reference'] ?? 'Tenant Pro Sandbox'));

        if (config('services.mpesa.environment') !== 'sandbox') {
            return back()->withErrors([
                'mpesa' => 'This test page is enabled only for sandbox mode.',
            ])->withInput();
        }

        if (filter_var(config('services.mpesa.simulate', true), FILTER_VALIDATE_BOOL)) {
            return back()->with('success', sprintf(
                'Sandbox payment simulated successfully. Phone: %s | %s: %s | Amount: KES %s | Receipt: SIM-%s',
                $phoneNumber,
                $paymentType,
                $shortCode,
                number_format($amount, 2),
                strtoupper(Str::substr(str_replace('-', '', (string) Str::uuid()), 0, 12))
            ))->withInput();
        }

        try {
            $result = $mpesa->stkPush(
                $phoneNumber,
                $amount,
                $reference,
                'Sandbox test payment',
                [
                    'payment_type' => $paymentType,
                    'paybill_number' => $paymentType === 'PAYBILL' ? $shortCode : '',
                    'till_number' => $paymentType === 'TILL' ? $shortCode : '',
                    'account_reference' => $accountReference,
                ]
            );

            return back()->with('success', sprintf(
                'STK push sent successfully. %s', $result['CustomerMessage'] ?? 'Check your phone.'
            ))->withInput();
        } catch (\Throwable $e) {
            return back()->withErrors([
                'mpesa' => $e->getMessage(),
            ])->withInput();
        }
    }
}
