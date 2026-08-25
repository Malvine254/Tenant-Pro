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
        $request->validate([
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
        ]);

        $invitation = Invitation::where('code', $code)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();


        $role = Role::where('name', $invitation->invite_type)
            ->firstOrFail();


        $user = User::create([
            'name' => $invitation->invitee_name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'is_active' => true,
        ]);


        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);


        Auth::login($user);

        return redirect()->route('admin.dashboard');
    }
}