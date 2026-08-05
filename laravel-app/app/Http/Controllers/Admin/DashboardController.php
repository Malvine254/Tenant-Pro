<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Invitation;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\LandlordSubscriptionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly LandlordSubscriptionService $subscriptionService,
    ) {
    }

    public function index()
    {
        $user = request()->user();
        $roleName = $user?->role?->name;
        $isLandlord = $roleName === 'LANDLORD';
        $isSuperAdmin = $roleName === 'SUPER_ADMIN';
        $landlordAccess = $isLandlord
            ? $this->subscriptionService->evaluate($user)
            : ['allowed' => true, 'status' => 'not_required', 'message' => null];

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
        $invitationsQuery = Invitation::query()
            ->when($isLandlord, fn($q) => $q->where('sent_by_id', $user->id));
        $landlordsQuery = User::query()->whereHas('role', fn($role) => $role->where('name', 'LANDLORD'));

        $totalUnits = (clone $unitsQuery)->count();
        $occupiedUnits = (clone $unitsQuery)->where('status', 'OCCUPIED')->count();
        $vacantUnits = max(0, $totalUnits - $occupiedUnits);
        $totalBilled = (float) (clone $invoicesQuery)->sum('total_amount');
        $totalPaid = (float) (clone $invoicesQuery)->sum('paid_amount');
        $collectedThisMonth = (float) (clone $paymentsQuery)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
        $outstanding = max(0, $totalBilled - $totalPaid);

        $stats = [
            'total_landlords' => $isLandlord ? null : (clone $landlordsQuery)->count(),
            'active_landlords' => $isLandlord ? null : (clone $landlordsQuery)->where('is_active', true)->count(),
            'suspended_landlords' => $isLandlord ? null : (clone $landlordsQuery)->where('is_active', false)->count(),
            'total_properties' => (clone $propertiesQuery)->count(),
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $vacantUnits,
            'occupancy_rate' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0,
            'total_tenants' => (clone $tenantsQuery)->where('is_active', true)->count(),
            'pending_invoices' => (clone $invoicesQuery)->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])->count(),
            'paid_invoices' => (clone $invoicesQuery)->where('status', 'PAID')->count(),
            'overdue_invoices' => (clone $invoicesQuery)->where('status', 'OVERDUE')->count(),
            'open_maintenance' => (clone $maintenanceQuery)->whereIn('status', ['OPEN', 'IN_PROGRESS'])->count(),
            'pending_invites' => (clone $invitationsQuery)->where('status', 'PENDING')->count(),
            'pending_landlord_invites' => $isLandlord ? null : (clone $invitationsQuery)->where('invite_type', 'LANDLORD')->where('status', 'PENDING')->count(),
            'pending_tenant_invites' => (clone $invitationsQuery)->where('invite_type', 'TENANT')->where('status', 'PENDING')->count(),
            'total_users' => $isLandlord
                ? (clone $tenantsQuery)->where('is_active', true)->count()
                : User::count(),
            'total_paid' => $totalPaid,
            'collected_this_month' => $collectedThisMonth,
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
        $recentPayments = (clone $paymentsQuery)->with(['invoice.tenant', 'invoice.unit.property'])->latest()->limit(5)->get();
        $recentInvitations = (clone $invitationsQuery)->with(['property', 'unit', 'sentBy'])->latest()->limit(5)->get();

        $landlordSubscription = [
            'trial' => 0,
            'active' => 0,
            'past_due' => 0,
            'not_required' => 0,
        ];

        $superAdminLandlordStats = [
            'total_landlords' => 0,
            'active_paid_landlords' => 0,
            'past_due_landlords' => 0,
            'landlords_with_overdue_invoices' => 0,
            'avg_monthly_collection_per_landlord' => 0,
        ];

        $landlordPerformance = collect();

        if (!$isLandlord) {
            $landlordSubscription = [
                'trial' => (clone $landlordsQuery)->where('billing_status', 'trial')->count(),
                'active' => (clone $landlordsQuery)->where('billing_status', 'active')->count(),
                'past_due' => (clone $landlordsQuery)->where('billing_status', 'past_due')->count(),
                'not_required' => (clone $landlordsQuery)->where(function ($q) {
                    $q->whereNull('billing_status')->orWhere('billing_status', 'not_required');
                })->count(),
            ];

            if ($isSuperAdmin) {
                $landlords = (clone $landlordsQuery)
                    ->select(['id', 'name', 'billing_status', 'is_active'])
                    ->get();

                $landlordPerformance = $landlords->map(function ($landlord) {
                    $unitsCount = Unit::query()
                        ->whereHas('property', fn($q) => $q->where('landlord_id', $landlord->id))
                        ->count();

                    $activeTenants = Tenant::query()
                        ->where('is_active', true)
                        ->whereHas('unit.property', fn($q) => $q->where('landlord_id', $landlord->id))
                        ->count();

                    $billed = (float) Invoice::query()
                        ->whereHas('unit.property', fn($q) => $q->where('landlord_id', $landlord->id))
                        ->sum('total_amount');

                    $paid = (float) Invoice::query()
                        ->whereHas('unit.property', fn($q) => $q->where('landlord_id', $landlord->id))
                        ->sum('paid_amount');

                    $monthlyCollection = (float) Payment::query()
                        ->whereHas('invoice.unit.property', fn($q) => $q->where('landlord_id', $landlord->id))
                        ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                        ->sum('amount');

                    $overdueInvoices = Invoice::query()
                        ->where('status', 'OVERDUE')
                        ->whereHas('unit.property', fn($q) => $q->where('landlord_id', $landlord->id))
                        ->count();

                    $outstanding = max(0, $billed - $paid);
                    $occupancyRate = $unitsCount > 0 ? round(($activeTenants / $unitsCount) * 100) : 0;

                    $health = 'healthy';
                    if ($overdueInvoices > 0 || $outstanding > 0) {
                        $health = 'attention';
                    }
                    if (($landlord->billing_status ?? null) === 'past_due' || $overdueInvoices >= 3) {
                        $health = 'risk';
                    }

                    return [
                        'id' => $landlord->id,
                        'name' => $landlord->name,
                        'status' => $landlord->is_active ? 'active' : 'inactive',
                        'billing_status' => $landlord->billing_status ?? 'not_required',
                        'units' => $unitsCount,
                        'active_tenants' => $activeTenants,
                        'occupancy_rate' => $occupancyRate,
                        'monthly_collection' => $monthlyCollection,
                        'outstanding' => $outstanding,
                        'overdue_invoices' => $overdueInvoices,
                        'health' => $health,
                    ];
                })->sortByDesc('monthly_collection')->values();

                $superAdminLandlordStats = [
                    'total_landlords' => $landlordPerformance->count(),
                    'active_paid_landlords' => $landlordPerformance->where('billing_status', 'active')->count(),
                    'past_due_landlords' => $landlordPerformance->where('billing_status', 'past_due')->count(),
                    'landlords_with_overdue_invoices' => $landlordPerformance->where('overdue_invoices', '>', 0)->count(),
                    'avg_monthly_collection_per_landlord' => round((float) $landlordPerformance->avg('monthly_collection'), 2),
                ];
            }
        }

        $chartSeries = [
            'monthlyRevenueLabels' => $monthlyRevenue->pluck('label')->values()->all(),
            'monthlyRevenueValues' => $monthlyRevenue->pluck('amount')->values()->all(),
            'invoiceStatusLabels' => $invoiceStatus->pluck('label')->values()->all(),
            'invoiceStatusValues' => $invoiceStatus->pluck('count')->values()->all(),
            'maintenanceStatusLabels' => $maintenanceStatus->pluck('label')->values()->all(),
            'maintenanceStatusValues' => $maintenanceStatus->pluck('count')->values()->all(),
            'landlordPerformanceLabels' => $landlordPerformance->take(8)->map(fn($item) => $item['name'])->values()->all(),
            'landlordPerformanceCollectionValues' => $landlordPerformance->take(8)->map(fn($item) => (float) $item['monthly_collection'])->values()->all(),
            'landlordPerformanceOutstandingValues' => $landlordPerformance->take(8)->map(fn($item) => (float) $item['outstanding'])->values()->all(),
            'landlordHealthLabels' => ['Healthy', 'Needs attention', 'At risk'],
            'landlordHealthValues' => [
                $landlordPerformance->where('health', 'healthy')->count(),
                $landlordPerformance->where('health', 'attention')->count(),
                $landlordPerformance->where('health', 'risk')->count(),
            ],
        ];

        return view('admin.dashboard', compact(
            'stats',
            'monthlyRevenue',
            'invoiceStatus',
            'maintenanceStatus',
            'recentInvoices',
            'recentMaintenance',
            'recentPayments',
            'recentInvitations',
            'isLandlord',
            'isSuperAdmin',
            'landlordSubscription',
            'superAdminLandlordStats',
            'landlordPerformance',
            'chartSeries',
            'landlordAccess'
        ));
    }
}
