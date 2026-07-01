<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json([
            'message' => 'Account created successfully.',
            'email' => $user->email,
            'user' => $this->userPayload($user->load('role')),
            'token' => $token,
            'accessToken' => $token,
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

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json([
            'user' => $this->userPayload($user->load('role')),
            'token' => $token,
            'accessToken' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($this->userPayload($request->user()->load(['role', 'tenant.unit.property'])));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    private function userPayload(User $user): array
    {
        $tenant = $user->relationLoaded('tenant') ? $user->tenant : null;
        $unit = $tenant?->relationLoaded('unit') ? $tenant->unit : null;
        $property = $unit?->relationLoaded('property') ? $unit->property : null;

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
            'tenant' => $tenant ? [
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
            ] : null,
        ];
    }
}
