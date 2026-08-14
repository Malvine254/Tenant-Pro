<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        abort_unless($user && $user->isLandlord(), 403, 'Only landlords can manage payment settings.');

        $settings = is_array($user->app_settings) ? $user->app_settings : [];
        $paymentSettings = is_array($settings['paymentSettings'] ?? null) ? $settings['paymentSettings'] : [];

        return view('admin.settings.index', [
            'paymentSettings' => $paymentSettings,
            'user' => $user,
        ]);
    }

    public function updatePayment(Request $request)
    {
        $user = Auth::user();

        abort_unless($user && $user->isLandlord(), 403, 'Only landlords can manage payment settings.');

        $data = $request->validate([
            'payment_type' => ['required', 'string', 'in:PAYBILL,TILL'],
            'paybill_number' => ['nullable', 'string', 'max:20'],
            'till_number' => ['nullable', 'string', 'max:20'],
            'account_reference' => ['nullable', 'string', 'max:50'],
            'business_name' => ['nullable', 'string', 'max:100'],
            'passkey' => ['nullable', 'string', 'max:255'],
            'short_code_note' => ['nullable', 'string', 'max:255'],
            'use_default_config' => ['nullable', 'boolean'],
        ]);

        $existing = is_array($user->app_settings) ? $user->app_settings : [];
        $paymentSettings = $existing['paymentSettings'] ?? [];

        $paymentSettings = array_merge($paymentSettings, [
            'payment_type' => strtoupper($data['payment_type']),
            'paybill_number' => trim((string) ($data['paybill_number'] ?? $paymentSettings['paybill_number'] ?? '')),
            'till_number' => trim((string) ($data['till_number'] ?? $paymentSettings['till_number'] ?? '')),
            'account_reference' => trim((string) ($data['account_reference'] ?? $paymentSettings['account_reference'] ?? 'Tenant Pro')),
            'business_name' => trim((string) ($data['business_name'] ?? $paymentSettings['business_name'] ?? '')),
            'passkey' => trim((string) ($data['passkey'] ?? $paymentSettings['passkey'] ?? '')),
            'short_code_note' => trim((string) ($data['short_code_note'] ?? $paymentSettings['short_code_note'] ?? '')),
            'use_default_config' => (bool) ($data['use_default_config'] ?? $paymentSettings['use_default_config'] ?? false),
        ]);

        if ($paymentSettings['payment_type'] === 'PAYBILL') {
            $paymentSettings['paybill_passkey'] = $paymentSettings['passkey'];
            $paymentSettings['till_number'] = '';
            $paymentSettings['till_passkey'] = '';
        } else {
            $paymentSettings['till_passkey'] = $paymentSettings['passkey'];
            $paymentSettings['paybill_number'] = '';
            $paymentSettings['paybill_passkey'] = '';
        }

        $existing['paymentSettings'] = $paymentSettings;
        $user->update(['app_settings' => $existing]);

        return redirect()->route('admin.settings.index')->with('success', 'Daraja payment settings saved successfully.');
    }
}
