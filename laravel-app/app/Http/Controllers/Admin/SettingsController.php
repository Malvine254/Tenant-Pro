<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\MpesaService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(private readonly PlatformSettingsService $platformSettings) {}

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
        $isSuperAdmin = $user->role?->name === 'SUPER_ADMIN';
        $isLandlord = $user->isLandlord();
        $isLandlordOwner = $user->isLandlordOwner();
        $settings = is_array($user->app_settings) ? $user->app_settings : [];
        $paymentSettings = is_array($settings['paymentSettings'] ?? null) ? $settings['paymentSettings'] : [];
        $tenantSettings = is_array($settings['tenantSettings'] ?? null) ? $settings['tenantSettings'] : [];
        $tenantSummary = [
            'properties' => 0,
            'units' => 0,
            'activeTenants' => 0,
            'pendingInvites' => 0,
        ];

        if ($isLandlordOwner) {
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

        $daraja = $this->platformSettings->daraja();
        $darajaMissingFields = collect([
            'Consumer key' => filled($daraja['consumer_key']),
            'Consumer secret' => filled($daraja['consumer_secret']),
            'Default shortcode' => filled($daraja['shortcode']),
            'Lipa na M-PESA passkey' => filled($daraja['passkey']),
            'Callback URL' => filled($daraja['callback_url']),
        ])->reject()->keys()->values()->all();

        return view('admin.settings.index', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'isSuperAdmin' => $isSuperAdmin,
            'isLandlord' => $isLandlord,
            'isLandlordOwner' => $isLandlordOwner,
            'paymentSettings' => $paymentSettings,
            'tenantSettings' => $tenantSettings,
            'tenantSummary' => $tenantSummary,
            'darajaSettings' => [
                'environment' => $daraja['environment'],
                'shortcode' => $daraja['shortcode'],
                'callback_url' => $daraja['callback_url'],
                'simulate' => $daraja['simulate'],
                'consumer_key_masked' => $this->maskSecret($daraja['consumer_key']),
                'consumer_secret_configured' => filled($daraja['consumer_secret']),
                'passkey_configured' => filled($daraja['passkey']),
                'missing_fields' => $darajaMissingFields,
                'ready' => $darajaMissingFields === [],
            ],
            'maintenanceSettings' => $this->platformSettings->maintenance(),
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
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_profile_image' => ['nullable', 'boolean'],
        ]);

        $firstName = trim((string) $data['first_name']);
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $fullName = trim($firstName.' '.$lastName);

        $attributes = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $fullName,
            'email' => trim((string) $data['email']),
            'phone_number' => trim((string) $data['phone_number']),
        ];

        if ($request->hasFile('profile_image')) {
            $this->deleteStoredProfileImage($user->profile_image_url);
            $path = $request->file('profile_image')->store('profile-images', 'public');
            // Stored relative so the avatar keeps resolving if the app domain changes.
            $attributes['profile_image_url'] = 'storage/'.$path;
        } elseif ($request->boolean('remove_profile_image')) {
            $this->deleteStoredProfileImage($user->profile_image_url);
            $attributes['profile_image_url'] = null;
        }

        $user->update($attributes);

        return redirect()->route('admin.settings.index', ['tab' => 'account'])
            ->with('success', 'Account details updated successfully.');
    }

    /** Only removes files this app stored on the public disk; ignores external URLs. */
    private function deleteStoredProfileImage(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if (! str_starts_with($path, 'storage/profile-images/')) {
            return;
        }

        Storage::disk('public')->delete(substr($path, strlen('storage/')));
    }

    public function updatePassword(Request $request)
    {
        $user = $this->currentUser();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check((string) $data['current_password'], (string) $user->password)) {
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
        abort_unless($user->isLandlordOwner(), 403, 'Only the landlord account owner can manage tenant preferences.');

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
        abort_unless($user->isLandlordOwner(), 403, 'Only the landlord account owner can manage payment settings.');

        $data = $request->validate([
            'payment_type' => ['required', 'string', 'in:PAYBILL,TILL'],
            'paybill_number' => ['nullable', 'required_if:payment_type,PAYBILL', 'string', 'max:20'],
            'till_number' => ['nullable', 'required_if:payment_type,TILL', 'string', 'max:20'],
            'account_reference' => ['nullable', 'required_if:payment_type,PAYBILL', 'string', 'max:50', 'not_in:Tenant Pro,Starmax Tenant Services'],
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
            'account_reference' => trim((string) ($data['account_reference'] ?? $paymentSettings['account_reference'] ?? '')),
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

    public function updateDaraja(Request $request)
    {
        $user = $this->currentUser();
        abort_unless($user->role?->name === 'SUPER_ADMIN', 403, 'Only super admins can update Daraja credentials.');

        $data = $request->validate([
            'environment' => ['required', 'in:sandbox,production'],
            'shortcode' => ['required', 'regex:/^\d{5,12}$/'],
            'callback_url' => ['required', 'url', 'max:500'],
            'consumer_key' => ['nullable', 'string', 'min:8', 'max:500'],
            'consumer_secret' => ['nullable', 'string', 'min:8', 'max:500'],
            'passkey' => ['nullable', 'string', 'min:10', 'max:500'],
            'simulate' => ['nullable', 'boolean'],
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }

        if ($data['environment'] === 'production' && ! str_starts_with(strtolower($data['callback_url']), 'https://')) {
            throw ValidationException::withMessages(['callback_url' => 'Production callbacks must use HTTPS.']);
        }

        $updates = [
            'daraja.environment' => $data['environment'],
            'daraja.shortcode' => trim($data['shortcode']),
            'daraja.callback_url' => trim($data['callback_url']),
            'daraja.simulate' => $data['environment'] === 'sandbox' && ($data['simulate'] ?? false) ? '1' : '0',
        ];
        foreach (['consumer_key', 'consumer_secret', 'passkey'] as $secret) {
            if (filled($data[$secret] ?? null)) {
                $updates['daraja.'.$secret] = trim($data[$secret]);
            }
        }

        $this->platformSettings->setMany($updates, $user);

        return redirect()->route('admin.settings.index', ['tab' => 'daraja'])
            ->with('success', 'Daraja configuration saved securely. Existing blank credential fields were retained.');
    }

    public function testDaraja(Request $request, MpesaService $mpesa)
    {
        $user = $this->currentUser();
        abort_unless($user->role?->name === 'SUPER_ADMIN', 403);
        $data = $request->validate(['current_password' => ['required', 'string']]);
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }

        try {
            $mpesa->testConnection();

            return redirect()->route('admin.settings.index', ['tab' => 'daraja'])
                ->with('success', 'Daraja authentication succeeded. The configured consumer credentials are valid.');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('admin.settings.index', ['tab' => 'daraja'])
                ->with('error', 'Daraja authentication failed. Recheck the environment and production credentials.');
        }
    }

    public function updateMaintenance(Request $request)
    {
        $user = $this->currentUser();
        abort_unless($user->role?->name === 'SUPER_ADMIN', 403);
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'message' => ['required', 'string', 'max:500'],
            'current_password' => ['required', 'string'],
        ]);
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }

        $enabled = (bool) ($data['enabled'] ?? false);
        $this->platformSettings->setMany([
            'maintenance.enabled' => $enabled ? '1' : '0',
            'maintenance.message' => trim($data['message']),
        ], $user);

        return redirect()->route('admin.settings.index', ['tab' => 'maintenance'])
            ->with('success', $enabled
                ? 'Customer maintenance mode is active. Admin access, health checks, and M-PESA callbacks remain available.'
                : 'Customer maintenance mode has been disabled.');
    }

    private function maskSecret(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return '******'.substr($value, -4);
    }
}
