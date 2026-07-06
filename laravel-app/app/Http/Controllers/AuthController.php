<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $firstName = (string) $request->input('first_name', $request->input('firstName', ''));
        $lastName = (string) $request->input('last_name', $request->input('lastName', ''));
        $name = (string) $request->input('name', trim($firstName . ' ' . $lastName));
        $phoneNumber = $request->input('phone_number', $request->input('phoneNumber'));
        $roleName = $request->input('role_name', $request->input('role', 'TENANT'));

        $request->merge([
            'name' => $name,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'phone_number' => $phoneNumber,
            'role_name' => $roleName,
            'password_confirmation' => $request->input('password_confirmation', $request->input('password')),
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|unique:users,phone_number',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'role_name' => 'nullable|string|exists:roles,name',
        ]);

        $roleName = $data['role_name'] ?? 'TENANT';
        $role = Role::where('name', $roleName)->first();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone_number' => $data['phone_number'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'role_id' => $role?->id,
            'is_active' => true,
        ]);

        $this->sendEmailOtp($user);

        return response()->json([
            'message' => 'Account created. A verification code has been sent to your email.',
            'email' => $user->email,
            'user' => $this->userPayload($user->load(['role', 'tenant.unit.property'])),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is inactive.'], 403);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email before signing in.',
                'email' => $user->email,
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json([
            'user' => $this->userPayload($user->load(['role', 'tenant.unit.property'])),
            'token' => $token,
            'accessToken' => $token,
        ]);
    }

    public function requestEmailOtp(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            return response()->json([
                'message' => 'If the account exists, a verification code has been sent.',
                'email' => $email,
            ]);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'This email address is already verified.',
                'email' => $email,
            ]);
        }

        $rateKey = 'email-otp-resend:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            return response()->json([
                'message' => 'Please wait '.RateLimiter::availableIn($rateKey).' seconds before requesting another code.',
            ], 429);
        }

        RateLimiter::hit($rateKey, 60);
        try {
            $this->sendEmailOtp($user);
        } catch (\Throwable $error) {
            RateLimiter::clear($rateKey);
            throw $error;
        }

        return response()->json([
            'message' => 'Verification code sent to your email.',
            'email' => $user->email,
            'expiresAt' => now()->addMinutes(10)->toISOString(),
        ]);
    }

    public function requestPhoneOtp(Request $request)
    {
        $request->merge([
            'phone_number' => $request->input('phone_number', $request->input('phoneNumber')),
        ]);
        $data = $request->validate(['phone_number' => 'required|string']);
        $user = User::where('phone_number', $data['phone_number'])->first();

        if (!$user || !$user->email) {
            return response()->json(['message' => 'No account with an email address was found for this phone number.'], 404);
        }

        $rateKey = 'phone-otp-resend:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            return response()->json([
                'message' => 'Please wait '.RateLimiter::availableIn($rateKey).' seconds before requesting another code.',
            ], 429);
        }

        RateLimiter::hit($rateKey, 60);
        try {
            $this->sendEmailOtp($user);
        } catch (\Throwable $error) {
            RateLimiter::clear($rateKey);
            throw $error;
        }

        return response()->json([
            'message' => 'Login code sent to the email address on your account.',
            'expiresAt' => now()->addMinutes(10)->toISOString(),
        ]);
    }

    public function verifyPhoneOtp(Request $request)
    {
        $request->merge([
            'phone_number' => $request->input('phone_number', $request->input('phoneNumber')),
        ]);
        $data = $request->validate([
            'phone_number' => 'required|string',
            'code' => 'required|digits:6',
        ]);
        $user = User::where('phone_number', $data['phone_number'])->first();

        if (!$user || !$user->email) {
            throw ValidationException::withMessages(['code' => ['The login code is invalid or expired.']]);
        }

        $request->merge(['email' => $user->email]);
        return $this->verifyEmailOtp($request);
    }

    public function verifyEmailOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $email = strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $record = Cache::get($this->otpCacheKey($email));

        if (!$user || !$record || !Hash::check($data['code'], $record['hash'])) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        Cache::forget($this->otpCacheKey($email));
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => $this->userPayload($user->load(['role', 'tenant.unit.property'])),
            'token' => $token,
            'accessToken' => $token,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            $rateKey = 'password-reset-resend:'.$user->id;
            if (RateLimiter::tooManyAttempts($rateKey, 1)) {
                return response()->json([
                    'message' => 'Please wait '.RateLimiter::availableIn($rateKey).' seconds before requesting another code.',
                ], 429);
            }

            RateLimiter::hit($rateKey, 60);
            try {
                $this->sendPasswordResetOtp($user);
            } catch (\Throwable $error) {
                RateLimiter::clear($rateKey);
                throw $error;
            }
        }

        return response()->json([
            'message' => 'If the account exists, a password reset code has been sent.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->merge([
            'new_password' => $request->input('new_password', $request->input('newPassword')),
        ]);

        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'new_password' => 'required|string|min:8',
        ]);

        $email = strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $record = Cache::get($this->passwordResetCacheKey($email));

        if (!$user || !$record || !Hash::check($data['code'], $record['hash'])) {
            throw ValidationException::withMessages([
                'code' => ['The password reset code is invalid or has expired.'],
            ]);
        }

        Cache::forget($this->passwordResetCacheKey($email));
        // Possession of the emailed reset code also proves ownership of the email.
        $user->forceFill([
            'password' => Hash::make($data['new_password']),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully. You can now sign in.',
        ]);
    }

    private function sendEmailOtp(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $email = strtolower(trim($user->email));

        Cache::put($this->otpCacheKey($email), [
            'hash' => Hash::make($code),
        ], now()->addMinutes(10));

        Mail::html(
            '<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:28px">'
            .'<h2 style="color:#1a2744">Verify your email</h2>'
            .'<p>Hello '.e($user->first_name ?: $user->name ?: 'there').',</p>'
            .'<p>Use this code to finish setting up your TenantPro account:</p>'
            .'<div style="font-size:32px;font-weight:700;letter-spacing:8px;color:#274690;padding:20px 0">'
            .e($code).'</div>'
            .'<p>This code expires in 10 minutes. If you did not request it, you can ignore this email.</p>'
            .'</div>',
            function ($message) use ($user) {
                $message->to($user->email, $user->name)->subject('Your TenantPro verification code');
            }
        );
    }

    private function otpCacheKey(string $email): string
    {
        return 'email-verification-otp:'.hash('sha256', strtolower(trim($email)));
    }

    private function sendPasswordResetOtp(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $email = strtolower(trim($user->email));

        Cache::put($this->passwordResetCacheKey($email), [
            'hash' => Hash::make($code),
        ], now()->addMinutes(10));

        Mail::html(
            '<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:28px">'
            .'<h2 style="color:#1a2744">Reset your password</h2>'
            .'<p>Hello '.e($user->first_name ?: $user->name ?: 'there').',</p>'
            .'<p>Use this code to reset your TenantPro password:</p>'
            .'<div style="font-size:32px;font-weight:700;letter-spacing:8px;color:#274690;padding:20px 0">'
            .e($code).'</div>'
            .'<p>This code expires in 10 minutes. If you did not request it, you can ignore this email.</p>'
            .'</div>',
            function ($message) use ($user) {
                $message->to($user->email, $user->name)->subject('Your TenantPro password reset code');
            }
        );
    }

    private function passwordResetCacheKey(string $email): string
    {
        return 'password-reset-otp:'.hash('sha256', strtolower(trim($email)));
    }

    public function me(Request $request)
    {
        return response()->json($this->userPayload($request->user()->load(['role', 'tenant.unit.property'])));
    }

    public function profile(Request $request)
    {
        return response()->json($this->userPayload($request->user()->load(['role', 'tenant.unit.property'])));
    }

    public function updateProfile(Request $request)
    {
        $request->merge([
            'first_name' => $request->input('first_name', $request->input('firstName')),
            'last_name' => $request->input('last_name', $request->input('lastName')),
            'phone_number' => $request->input('phone_number', $request->input('phoneNumber')),
            'profile_image_url' => $request->input('profile_image_url', $request->input('profileImageUrl')),
            'emergency_contact_name' => $request->input('emergency_contact_name', $request->input('emergencyContactName')),
            'emergency_contact_phone' => $request->input('emergency_contact_phone', $request->input('emergencyContactPhone')),
        ]);

        $user = $request->user();
        $data = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:30|unique:users,phone_number,'.$user->id,
            'profile_image_url' => 'nullable|string|max:2048',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:5000',
        ]);

        if (array_key_exists('first_name', $data) || array_key_exists('last_name', $data)) {
            $data['name'] = trim(($data['first_name'] ?? $user->first_name ?? '').' '.($data['last_name'] ?? $user->last_name ?? ''));
        }

        $user->update($data);
        return response()->json($this->userPayload($user->fresh()->load(['role', 'tenant.unit.property'])));
    }

    public function uploadProfileImage(Request $request)
    {
        $data = $request->validate(['file' => 'required|image|max:5120']);
        $path = $data['file']->store('profile-images', 'public');
        $url = Storage::disk('public')->url($path);
        $request->user()->update(['profile_image_url' => $url]);

        return response()->json($this->userPayload($request->user()->fresh()->load(['role', 'tenant.unit.property'])));
    }

    public function saveDeviceToken(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:4096']);
        $request->user()->update(['fcm_token' => $data['token']]);
        return response()->json(['message' => 'Device token saved.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    private function userPayload(User $user): array
    {
        $tenant = $user->relationLoaded('tenant') ? $user->tenant : null;
        if ($tenant && !$tenant->is_active) {
            $tenant = null;
        }
        $unit = $tenant?->relationLoaded('unit') ? $tenant->unit : null;
        $property = $unit?->relationLoaded('property') ? $unit->property : null;

        $tenantProfile = $tenant ? [
            'id' => $tenant->id,
            'userId' => $tenant->user_id,
            'unitId' => $tenant->unit_id,
            'moveInDate' => $tenant->move_in_date?->toDateString(),
            'moveOutDate' => $tenant->move_out_date?->toDateString(),
            'isActive' => $tenant->is_active,
            'unit' => $unit ? [
                'id' => $unit->id,
                'unitNumber' => $unit->unit_number,
                'floor' => $unit->floor,
                'rentAmount' => $unit->rent_amount,
                'rentAmountFormatted' => $unit->rent_amount_formatted,
                'currency' => $unit->currency,
                'currencySymbol' => $unit->currency_symbol,
                'status' => $unit->status,
                'property' => $property ? [
                    'id' => $property->id,
                    'name' => $property->name,
                    'addressLine' => $property->address_line,
                    'city' => $property->city,
                    'state' => $property->state,
                    'country' => $property->country,
                ] : null,
            ] : null,
        ] : null;

        return [
            'id' => $user->id,
            'userId' => $user->id,
            'phoneNumber' => $user->phone_number ?? '',
            'email' => $user->email,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'fullName' => $user->name,
            'profileImageUrl' => $user->profile_image_url,
            'emergencyContactName' => $user->emergency_contact_name,
            'emergencyContactPhone' => $user->emergency_contact_phone,
            'bio' => $user->bio,
            'role' => $user->role?->name ?? 'TENANT',
            'currency' => 'KES',
            'currencySymbol' => 'KSh',
            'tenant' => $tenantProfile,
            'tenantProfile' => $tenantProfile,
            'tenantProfiles' => $tenantProfile ? [$tenantProfile] : [],
        ];
    }
}
