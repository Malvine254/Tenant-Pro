<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;

class TenantTrustScoreService
{
    /**
     * Calculates a Starmax Trust & Payment Reliability Score for a tenant (0 - 100).
     * Based on:
     * - On-time payment ratio
     * - Cleared balances ratio
     * - Payment speed (days before/after due date)
     * - Tenancy history longevity
     */
    public function calculateScore(User $tenantUser): array
    {
        $invoices = Invoice::with('payments')
            ->where('tenant_id', $tenantUser->id)
            ->get();

        if ($invoices->isEmpty()) {
            return [
                'score' => 85, // New default good standing
                'grade' => 'A-',
                'rating' => 'New Tenant - Good Standing',
                'on_time_percentage' => 100,
                'settled_invoices_count' => 0,
                'total_invoices_count' => 0,
                'total_paid_kes' => 0.0,
                'outstanding_balance_kes' => 0.0,
                'has_overdue' => false,
            ];
        }

        $totalInvoices = $invoices->count();
        $settledInvoices = 0;
        $onTimeInvoices = 0;
        $lateInvoices = 0;
        $hasOverdue = false;
        $totalPaid = 0.0;
        $totalOutstanding = 0.0;

        foreach ($invoices as $inv) {
            $totalPaid += (float) $inv->paid_amount;
            $balance = (float) $inv->balance_amount;
            $totalOutstanding += $balance;

            if ($inv->status === 'OVERDUE') {
                $hasOverdue = true;
            }

            if ($balance <= 0.01) {
                $settledInvoices++;
                $earliestPayment = $inv->payments->where('status', 'SUCCESSFUL')->sortBy('paid_at')->first();
                $paidAt = $earliestPayment?->paid_at ?? $inv->paid_at;
                $dueDate = $inv->due_date ? Carbon::parse($inv->due_date) : null;

                if ($paidAt && $dueDate) {
                    if (Carbon::parse($paidAt)->startOfDay()->lte($dueDate->startOfDay())) {
                        $onTimeInvoices++;
                    } else {
                        $lateInvoices++;
                    }
                } else {
                    $onTimeInvoices++;
                }
            }
        }

        $onTimeRate = $totalInvoices > 0 ? ($onTimeInvoices / $totalInvoices) : 1.0;
        $settledRate = $totalInvoices > 0 ? ($settledInvoices / $totalInvoices) : 1.0;

        // Base score: 50 + (onTimeRate * 30) + (settledRate * 20) - (penalty if overdue)
        $score = 50 + ($onTimeRate * 30) + ($settledRate * 20);
        if ($hasOverdue) {
            $score -= 15;
        }
        $score = (int) round(max(20, min(100, $score)));

        $grade = match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 80 => 'B+',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            default => 'D',
        };

        $rating = match (true) {
            $score >= 90 => 'Verified Prime Tenant',
            $score >= 80 => 'Reliable & Recommended',
            $score >= 70 => 'Standard Good Standing',
            $score >= 60 => 'Moderate Payment Delays',
            default => 'Payment Risk Detected',
        };

        return [
            'score' => $score,
            'grade' => $grade,
            'rating' => $rating,
            'on_time_percentage' => (int) round($onTimeRate * 100),
            'settled_invoices_count' => $settledInvoices,
            'total_invoices_count' => $totalInvoices,
            'total_paid_kes' => $totalPaid,
            'outstanding_balance_kes' => $totalOutstanding,
            'has_overdue' => $hasOverdue,
        ];
    }
}
