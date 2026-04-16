<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class TenantAdminController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with(['user', 'unit.property'])
            ->when($request->search, fn($q) => $q->whereHas('user', function ($u) use ($request) {
                $u->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->latest()->paginate(15);
        return view('admin.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['user', 'unit.property', 'unit.invoices', 'unit.maintenanceRequests']);
        return view('admin.tenants.show', compact('tenant'));
    }
}
