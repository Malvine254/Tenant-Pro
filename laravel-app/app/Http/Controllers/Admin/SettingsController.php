<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    private function currentUser(): User
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    public function index()
    {
        $user = $this->currentUser();

        $isAdmin = in_array($user->role?->name, ['SUPER_ADMIN', 'ADMIN'], true);
        $isLandlord = $user->isLandlord();
        $settings = is_array($user->app_settings) ? $user->app_settings : [];
        $paymentSettings = is_array($settings['paymentSettings'] ?? null) ? $settings['paymentSettings'] : [];
        $tenantSettings = is_array($settings['tenantSettings'] ?? null) ? $settings['tenantSettings'] : [];
        $tenantSummary = [
            'properties' => 0,
            'units' => 0,
            'activeTenants' => 0,
            'pendingInvites' => 0,
        ];

        if ($isLandlord) {
            $tenantSummary = [
                'properties' => $user->properties()->count(),
                'units' => Unit::query()->whereHas('property', fn ($query) => $query->where('landlord_id', $user->id))->count(),
                'activeTenants' => Tenant::query()
                    ->where('is_active', true)
                    ->whereHas('unit.property', fn ($query) => $query->where('landlord_id', $user->id))
                    ->count(),
                'pendingInvites' => Invitation::query()
                    ->where('status', 'PENDING')
                    ->whereHas('property', fn ($query) => $query->where('landlord_id', $user->id))
                    ->count(),
            ];
        }

        return view('admin.settings.index', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'isLandlord' => $isLandlord,
            'paymentSettings' => $paymentSettings,
            'tenantSettings' => $tenantSettings,
            'tenantSummary' => $tenantSummary,
            'globalPasskey' => config('services.mpesa.passkey'),
        ]);
    }

    public function updateAccount(Request $request)
    {
        $user = $this->currentUser();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        $firstName = trim((string) $data['first_name']);
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $fullName = trim($firstName.' '.$lastName);

        $user->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $fullName,
            'email' => trim((string) $data['email']),
            'phone_number' => trim((string) $data['phone_number']),
        ]);

        return redirect()->route('admin.settings.index', ['tab' => 'account'])
            ->with('success', 'Account details updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = $this->currentUser();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check((string) $data['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password you entered is not correct.',
            ])->withInput();
        }

        $user->update([
            'password' => (string) $data['password'],
        ]);

        return redirect()->route('admin.settings.index', ['tab' => 'security'])
            ->with('success', 'Password changed successfully.');
    }

    public function updateTenantPreferences(Request $request)
    {
        $user = $this->currentUser();
        abort_unless($user->isLandlord(), 403, 'Only landlords can manage tenant preferences.');

        $data = $request->validate([
            'default_invite_expiry_days' => ['required', 'integer', 'min:1', 'max:60'],
            'auto_assign_unit_on_accept' => ['nullable', 'boolean'],
            'require_tenant_email' => ['nullable', 'boolean'],
            'allow_multi_unit_assignment' => ['nullable', 'boolean'],
        ]);

        $existing = is_array($user->app_settings) ? $user->app_settings : [];
        $existing['tenantSettings'] = [
            'default_invite_expiry_days' => (int) $data['default_invite_expiry_days'],
            'auto_assign_unit_on_accept' => (bool) ($data['auto_assign_unit_on_accept'] ?? false),
            'require_tenant_email' => (bool) ($data['require_tenant_email'] ?? false),
            'allow_multi_unit_assignment' => (bool) ($data['allow_multi_unit_assignment'] ?? true),
        ];

        $user->update(['app_settings' => $existing]);

        return redirect()->route('admin.settings.index', ['tab' => 'tenants'])
            ->with('success', 'Tenant preferences saved successfully.');
    }

    public function updatePayment(Request $request)
    {
        $user = $this->currentUser();
        abort_unless($user->isLandlord(), 403, 'Only landlords can manage payment settings.');

        $data = $request->validate([
            'payment_type' => ['required', 'string', 'in:PAYBILL,TILL'],
            'paybill_number' => ['nullable', 'string', 'max:20'],
            'till_number' => ['nullable', 'string', 'max:20'],
            'account_reference' => ['nullable', 'string', 'max:50'],
            'business_name' => ['nullable', 'string', 'max:100'],
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
            'short_code_note' => trim((string) ($data['short_code_note'] ?? $paymentSettings['short_code_note'] ?? '')),
            'use_default_config' => (bool) ($data['use_default_config'] ?? $paymentSettings['use_default_config'] ?? false),
        ]);

        if ($paymentSettings['payment_type'] === 'PAYBILL') {
            $paymentSettings['till_number'] = '';
        } else {
            $paymentSettings['paybill_number'] = '';
        }

        $existing['paymentSettings'] = $paymentSettings;
        $user->update(['app_settings' => $existing]);

        return redirect()->route('admin.settings.index', ['tab' => 'payment'])->with('success', 'Daraja payment settings saved successfully.');
    }

    public function updatePasskey(Request $request)
    {
        $user = $this->currentUser();
        abort_unless(in_array($user->role?->name, ['SUPER_ADMIN', 'ADMIN'], true), 403, 'Only admins can update the Daraja passkey.');

        $data = $request->validate([
            'passkey' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        $newPasskey = trim((string) $data['passkey']);
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return back()->with('error', 'The environment file is missing.');
        }

        $contents = file_get_contents($envPath);
        $pattern = '/^MPESA_PASSKEY=.*$/m';
        $replacement = 'MPESA_PASSKEY="'.str_replace('"', '\\"', $newPasskey).'"';

        if (preg_match($pattern, $contents)) {
            $updated = preg_replace($pattern, $replacement, $contents);
        } else {
            $updated = $contents . PHP_EOL . $replacement . PHP_EOL;
        }

        if ($updated === null || file_put_contents($envPath, $updated) === false) {
            return back()->with('error', 'Unable to save the Daraja passkey.');
        }

        config()->set('services.mpesa.passkey', $newPasskey);

        return redirect()->route('admin.settings.index', ['tab' => 'platform'])->with('success', 'Daraja passkey updated successfully.');
    }
}
