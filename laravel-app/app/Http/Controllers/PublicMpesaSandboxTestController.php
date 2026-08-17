<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicMpesaSandboxTestController extends Controller
{
    public function index()
    {
        return view('public.mpesa-sandbox-test', [
            'environment' => config('services.mpesa.environment', 'sandbox'),
            'simulate' => filter_var(config('services.mpesa.simulate', true), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_type' => ['required', 'string', 'in:PAYBILL,TILL'],
            'short_code' => ['required', 'string', 'max:20'],
            'phone_number' => ['required', 'string', 'regex:/^(?:254|\\+254|0)?[17]\\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1', 'max:500000'],
            'account_reference' => ['nullable', 'string', 'max:50'],
        ]);

        if (config('services.mpesa.environment') !== 'sandbox') {
            return back()->withErrors([
                'mpesa' => 'This public sandbox test page is available only when the app is in sandbox mode.',
            ])->withInput();
        }

        $paymentType = strtoupper((string) $data['payment_type']);
        $shortCode = trim((string) $data['short_code']);
        $phoneNumber = trim((string) $data['phone_number']);
        $amount = round((float) $data['amount'], 2);
        $receipt = 'SIM-'.strtoupper(Str::substr(str_replace('-', '', (string) Str::uuid()), 0, 12));

        return back()->with('success', sprintf(
            'Sandbox payment request accepted. Customer: %s | %s: %s | Amount: KES %s | Receipt: %s',
            $phoneNumber,
            $paymentType,
            $shortCode,
            number_format($amount, 2),
            $receipt
        ))->withInput();
    }
}
