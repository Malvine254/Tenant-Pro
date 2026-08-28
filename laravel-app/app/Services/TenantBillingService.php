<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class TenantBillingService
{
    public function __construct(
        private readonly TenantAppNotificationService $appNotificationService,
        private readonly TenantEmailService $emailService,
    ) {
    }

    public function createInitialRentInvoice(Tenant $tenant): Invoice
    {
        $tenant->loadMissing(['user', 'unit.property']);

        $moveInDate = $tenant->move_in_date
            ? Carbon::parse($tenant->move_in_date)
            : now();
        $amount = (float) ($tenant->unit?->rent_amount ?? 0);

        $rentInvoice = Invoice::firstOrCreate(
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

        $this->createRecurringUtilityInvoices($tenant, $moveInDate, $moveInDate->copy()->addDays(7), now());

        return $rentInvoice;
    }

    public function syncMonthlyRentForActiveTenancies(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?: now())->copy()->startOfDay();
        $tenants = Tenant::query()
            ->with(['user', 'unit.property'])
            ->where('is_active', true)
            ->get();

        $generated = 0;
        foreach ($tenants as $tenant) {
            $generated += $this->syncTenantMonthlyRentInvoices($tenant, $asOf);
        }

        return [
            'tenanciesProcessed' => $tenants->count(),
            'invoicesGenerated' => $generated,
        ];
    }

    public function syncMonthlyRentForTenantUnit(string $tenantUserId, string $unitId, ?Carbon $asOf = null): int
    {
        $tenant = Tenant::query()
            ->with(['user', 'unit.property'])
            ->where('is_active', true)
            ->where('user_id', $tenantUserId)
            ->where('unit_id', $unitId)
            ->first();

        if (! $tenant) {
            return 0;
        }

        return $this->syncTenantMonthlyRentInvoices($tenant, ($asOf ?: now())->copy()->startOfDay());
    }

    private function syncTenantMonthlyRentInvoices(Tenant $tenant, Carbon $asOf): int
    {
        $tenant->loadMissing(['user', 'unit.property']);

        // Backfill the current month for existing tenancies as well as new ones.
        $created = $this->createRecurringUtilityInvoices(
            $tenant,
            $asOf,
            $asOf->copy()->addDays(7),
            $asOf
        );

        $amount = (float) ($tenant->unit?->rent_amount ?? 0);

        $rentInvoices = Invoice::query()
            ->where('tenant_id', $tenant->user_id)
            ->where('unit_id', $tenant->unit_id)
            ->where('billing_type', 'RENT')
            ->where('status', '!=', 'CANCELLED');

        $latestRentInvoice = (clone $rentInvoices)
            ->orderByDesc('due_date')
            ->orderByDesc('created_at')
            ->first();

        if (! $latestRentInvoice) {
            $initial = $this->createInitialRentInvoice($tenant);
            if ($initial->wasRecentlyCreated) {
                $this->appNotificationService->invoiceCreated($initial);
                $this->emailService->invoiceCreated($initial);
                return $created + 1;
            }

            return $created;
        }

        $nextDueDate = $this->resolveNextDueDate($tenant, $latestRentInvoice);
        if (! $nextDueDate) {
            return 0;
        }

        $cutoff = $asOf->copy()->addMonth()->endOfDay();
        while ($nextDueDate->lte($cutoff)) {
            $periodMonth = (int) $nextDueDate->month;
            $periodYear = (int) $nextDueDate->year;

            $invoice = Invoice::firstOrCreate(
                [
                    'tenant_id' => $tenant->user_id,
                    'user_id' => $tenant->user_id,
                    'unit_id' => $tenant->unit_id,
                    'billing_type' => 'RENT',
                    'period_month' => $periodMonth,
                    'period_year' => $periodYear,
                ],
                [
                    'issue_date' => $asOf->toDateString(),
                    'due_date' => $nextDueDate->toDateString(),
                    'amount' => $amount,
                    'penalty_amount' => 0,
                    'total_amount' => $amount,
                    'paid_amount' => 0,
                    'status' => 'PENDING',
                    'paid_at' => null,
                ]
            )->load(['tenant', 'unit.property']);

            if ($invoice->wasRecentlyCreated) {
                $this->appNotificationService->invoiceCreated($invoice);
                $this->emailService->invoiceCreated($invoice);
                $created++;
            }

            $created += $this->createRecurringUtilityInvoices($tenant, $nextDueDate, $nextDueDate, $asOf);

            $nextDueDate = $nextDueDate->copy()->addMonth();
        }

        return $created;
    }

    private function createRecurringUtilityInvoices(Tenant $tenant, Carbon $periodDate, Carbon $dueDate, Carbon $issueDate): int
    {
        $settings = $tenant->unit?->property?->billing_settings ?? [];
        $created = 0;

        foreach ([
            'WATER' => (float) ($settings['water_monthly_fee'] ?? 0),
            'GARBAGE' => (float) ($settings['garbage_monthly_fee'] ?? 0),
        ] as $billingType => $amount) {
            $invoice = Invoice::firstOrCreate(
                [
                    'tenant_id' => $tenant->user_id,
                    'user_id' => $tenant->user_id,
                    'unit_id' => $tenant->unit_id,
                    'billing_type' => $billingType,
                    'period_month' => (int) $periodDate->month,
                    'period_year' => (int) $periodDate->year,
                ],
                [
                    'issue_date' => $issueDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'amount' => $amount,
                    'penalty_amount' => 0,
                    'total_amount' => $amount,
                    'paid_amount' => 0,
                    'status' => 'PENDING',
                    'paid_at' => null,
                ]
            )->load(['tenant', 'unit.property']);

            if ($invoice->wasRecentlyCreated) {
                $this->appNotificationService->invoiceCreated($invoice);
                $this->emailService->invoiceCreated($invoice);
                $created++;
            }
        }

        return $created;
    }

    private function resolveNextDueDate(Tenant $tenant, Invoice $latestRentInvoice): ?Carbon
    {
        $lastPaidRentInvoice = Invoice::query()
            ->where('tenant_id', $tenant->user_id)
            ->where('unit_id', $tenant->unit_id)
            ->where('billing_type', 'RENT')
            ->where('status', 'PAID')
            ->whereNotNull('paid_at')
            ->orderByDesc('paid_at')
            ->first();

        if ($lastPaidRentInvoice?->paid_at) {
            return Carbon::parse($lastPaidRentInvoice->paid_at)->startOfDay()->addMonth();
        }

        if ($latestRentInvoice->due_date) {
            return Carbon::parse($latestRentInvoice->due_date)->startOfDay()->addMonth();
        }

        if ($tenant->move_in_date) {
            return Carbon::parse($tenant->move_in_date)->startOfDay()->addDays(7);
        }

        return null;
    }
}
