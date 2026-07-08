<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class TenantBillingService
{
    public function createInitialRentInvoice(Tenant $tenant): Invoice
    {
        $tenant->loadMissing(['user', 'unit.property']);

        $moveInDate = $tenant->move_in_date
            ? Carbon::parse($tenant->move_in_date)
            : now();
        $amount = (float) ($tenant->unit?->rent_amount ?? 0);

        return Invoice::firstOrCreate(
            [
                'tenant_id' => $tenant->user_id,
                'user_id' => $tenant->user_id,
                'unit_id' => $tenant->unit_id,
                'billing_type' => 'RENT',
                'period_month' => (int) $moveInDate->month,
                'period_year' => (int) $moveInDate->year,
            ],
            [
                'issue_date' => now()->toDateString(),
                'due_date' => $moveInDate->copy()->addDays(7)->toDateString(),
                'amount' => $amount,
                'penalty_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => 0,
                'status' => 'PENDING',
                'paid_at' => null,
            ]
        )->load(['tenant', 'unit.property']);
    }
}
