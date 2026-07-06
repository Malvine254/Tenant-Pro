<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\Request;

class InvitationAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $invitations = Invitation::with(['property', 'unit', 'sentBy'])
            ->when(
                $user?->role?->name === 'LANDLORD',
                fn($query) => $query->where('sent_by_id', $user->id)
            )
            ->when($request->status, fn($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('admin.invitations.index', compact('invitations'));
    }
}
