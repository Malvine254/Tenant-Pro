<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_properties' => Property::count(),
            'total_units' => Unit::count(),
            'occupied_units' => Unit::where('status', 'OCCUPIED')->count(),
            'total_tenants' => Tenant::where('is_active', true)->count(),
            'pending_invoices' => Invoice::where('status', 'PENDING')->count(),
            'open_maintenance' => MaintenanceRequest::whereIn('status', ['OPEN', 'IN_PROGRESS'])->count(),
            'total_users' => User::count(),
        ];
        return view('admin.dashboard', compact('stats'));
    }
}
