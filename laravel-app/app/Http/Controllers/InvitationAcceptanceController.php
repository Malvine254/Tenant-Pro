<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InvitationAcceptanceController extends Controller
{
    public function show(string $code)
    {
        $invitation = Invitation::where('code', $code)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return view('invitations.accept', compact('invitation'));
    }


    public function accept(Request $request, string $code)
    {
        $invitation = Invitation::where('code', $code)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $rules = [
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
        ];
        if ($invitation->invite_type === 'LANDLORD') {
            $rules += [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'phone_number' => ['required', 'string', 'max:30', 'unique:users,phone_number'],
            ];
        }
        $data = $request->validate($rules);


        $role = Role::where('name', $invitation->invite_type)
            ->firstOrFail();


        $user = User::create([
            'name' => $invitation->invite_type === 'LANDLORD'
                ? trim($data['first_name'].' '.$data['last_name'])
                : $invitation->invitee_name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
        ]);


        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);


        Auth::login($user);

        return redirect()->route('admin.dashboard');
    }
}
