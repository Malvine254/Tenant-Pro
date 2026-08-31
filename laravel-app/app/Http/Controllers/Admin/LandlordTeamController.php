<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LandlordTeamController extends Controller
{
    public function index(Request $request)
    {
        $owner = $this->owner($request);
        $members = $owner->landlordTeamMembers()
            ->with('role')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.team.index', compact('owner', 'members'));
    }

    public function store(Request $request)
    {
        $owner = $this->owner($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:30', 'unique:users,phone_number'],
            'current_password' => ['required', 'string'],
        ]);
        if (! Hash::check($data['current_password'], $owner->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }

        $role = Role::firstOrCreate(
            ['name' => 'LANDLORD'],
            ['description' => 'Property owner/manager'],
        );
        $member = User::create([
            'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'first_name' => trim($data['first_name']),
            'last_name' => trim((string) ($data['last_name'] ?? '')) ?: null,
            'email' => strtolower(trim($data['email'])),
            'phone_number' => filled($data['phone_number'] ?? null) ? trim($data['phone_number']) : null,
            'password' => Str::random(64),
            'role_id' => $role->id,
            'managed_landlord_id' => $owner->id,
            'team_invited_at' => now(),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $status = Password::sendResetLink(['email' => $member->email]);

        return back()->with(
            $status === Password::RESET_LINK_SENT ? 'success' : 'error',
            $status === Password::RESET_LINK_SENT
                ? 'Team member added. A secure password-setup link was emailed to '.$member->email.'.'
                : 'Team member added, but the password-setup email could not be sent. Use “Send setup link” to retry.',
        );
    }

    public function update(Request $request, User $member)
    {
        $owner = $this->owner($request);
        $this->authorizeMember($owner, $member);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'phone_number' => ['nullable', 'string', 'max:30', 'unique:users,phone_number,'.$member->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $member->update([
            'first_name' => trim($data['first_name']),
            'last_name' => trim((string) ($data['last_name'] ?? '')) ?: null,
            'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'phone_number' => filled($data['phone_number'] ?? null) ? trim($data['phone_number']) : null,
            'is_active' => $request->boolean('is_active'),
        ]);
        if (! $member->is_active) {
            $member->tokens()->delete();
        }

        return back()->with('success', 'Team member access updated.');
    }

    public function sendSetupLink(Request $request, User $member)
    {
        $owner = $this->owner($request);
        $this->authorizeMember($owner, $member);
        $status = Password::sendResetLink(['email' => $member->email]);

        return back()->with(
            $status === Password::RESET_LINK_SENT ? 'success' : 'error',
            $status === Password::RESET_LINK_SENT
                ? 'A fresh password-setup link was sent to '.$member->email.'.'
                : 'The setup email could not be sent. Check the mail configuration and try again.',
        );
    }

    private function owner(Request $request): User
    {
        $user = $request->user();
        abort_unless($user?->isLandlordOwner(), 403, 'Only the landlord account owner can manage team access.');

        return $user;
    }

    private function authorizeMember(User $owner, User $member): void
    {
        abort_unless($member->managed_landlord_id === $owner->id, 404);
    }
}
