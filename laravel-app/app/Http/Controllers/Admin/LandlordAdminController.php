<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LandlordAdminController extends Controller
{
    public function index(Request $request)
    {
        $landlords = User::withCount('properties')
            ->whereHas('role', fn ($query) => $query->where('name', 'LANDLORD'))
            ->when($request->search, fn ($query) => $query->where(function ($userQuery) use ($request) {
                $userQuery->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone_number', 'like', "%{$request->search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.landlords.index', compact('landlords'));
    }

    public function create()
    {
        return view('admin.landlords.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|unique:users,phone_number',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'LANDLORD'],
            ['description' => 'Property owner/manager']
        );

        $user = User::create([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.properties.create', ['landlord_id' => $user->id])
            ->with('success', 'Landlord created. You can now assign a property to them.');
    }
}
