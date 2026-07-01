<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';

        $propertiesQuery = Property::query()
            ->when($isLandlord, fn($q) => $q->where('landlord_id', $user->id));
        $unitsQuery = Unit::query()
            ->when($isLandlord, fn($q) => $q->whereHas('property', fn($property) => $property->where('landlord_id', $user->id)));
        $tenantsQuery = Tenant::query()
            ->when($isLandlord, fn($q) => $q->whereHas('unit.property', fn($property) => $property->where('landlord_id', $user->id)));
        $invoicesQuery = Invoice::query()
            ->when($isLandlord, fn($q) => $q->whereHas('unit.property', fn($property) => $property->where('landlord_id', $user->id)));
        $maintenanceQuery = MaintenanceRequest::query()
            ->when($isLandlord, fn($q) => $q->whereHas('unit.property', fn($property) => $property->where('landlord_id', $user->id)));
        $paymentsQuery = Payment::query()
            ->when($isLandlord, fn($q) => $q->whereHas('invoice.unit.property', fn($property) => $property->where('landlord_id', $user->id)));

        $totalUnits = (clone $unitsQuery)->count();
        $occupiedUnits = (clone $unitsQuery)->where('status', 'OCCUPIED')->count();
        $totalBilled = (float) (clone $invoicesQuery)->sum('total_amount');
        $totalPaid = (float) (clone $invoicesQuery)->sum('paid_amount');
        $outstanding = max(0, $totalBilled - $totalPaid);

        $stats = [
            'total_properties' => (clone $propertiesQuery)->count(),
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'occupancy_rate' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0,
            'total_tenants' => (clone $tenantsQuery)->where('is_active', true)->count(),
            'pending_invoices' => (clone $invoicesQuery)->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])->count(),
            'open_maintenance' => (clone $maintenanceQuery)->whereIn('status', ['OPEN', 'IN_PROGRESS'])->count(),
            'total_users' => $isLandlord
                ? (clone $tenantsQuery)->where('is_active', true)->count()
                : User::count(),
            'total_paid' => $totalPaid,
            'outstanding' => $outstanding,
        ];

        $monthlyRevenue = collect(range(5, 0))->map(function ($monthsAgo) use ($paymentsQuery) {
            $month = now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'amount' => (float) (clone $paymentsQuery)
                    ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('amount'),
            ];
        });

        $invoiceStatus = collect(['PENDING', 'PARTIAL', 'PAID', 'OVERDUE'])->map(fn($status) => [
            'label' => $status,
            'count' => (clone $invoicesQuery)->where('status', $status)->count(),
        ]);

        $maintenanceStatus = collect(['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])->map(fn($status) => [
            'label' => str_replace('_', ' ', $status),
            'count' => (clone $maintenanceQuery)->where('status', $status)->count(),
        ]);

        $recentInvoices = (clone $invoicesQuery)->with(['tenant', 'unit.property'])->latest()->limit(5)->get();
        $recentMaintenance = (clone $maintenanceQuery)->with(['unit.property', 'reportedBy'])->latest()->limit(5)->get();

        return view('admin.dashboard', compact(
            'stats',
            'monthlyRevenue',
            'invoiceStatus',
            'maintenanceStatus',
            'recentInvoices',
            'recentMaintenance',
            'isLandlord'
        ));
    }
}
