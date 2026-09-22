<?php

namespace App\Services\AI\Tools;

use App\Models\Payment;
use App\Models\User;

class GetPaymentHistoryTool implements ChatTool
{
    public function name(): string
    {
        return 'get_payment_history';
    }

    public function description(): string
    {
        return "List the authenticated tenant's own recent payments (amount, method, date, receipt "
            .'reference). Use this for questions about past payments or receipts. Never returns '
            .'payments belonging to another tenant.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of payments to return (default 10, max 25).',
                ],
            ],
            'required' => [],
        ];
    }

    public function authorize(User $user): bool
    {
        return $user->role?->name === 'TENANT';
    }

    public function execute(array $arguments, User $user): array
    {
        $limit = min(max((int) ($arguments['limit'] ?? 10), 1), 25);

        $payments = Payment::with('invoice')
            ->whereHas('invoice', fn ($q) => $q->where('tenant_id', $user->id))
            ->latest('paid_at')
            ->limit($limit)
            ->get();

        return [
            'ok' => true,
            'data' => [
                'payments' => $payments->map(fn (Payment $payment) => [
                    'amount_formatted' => $payment->amount_formatted,
                    'method' => $payment->method,
                    'status' => $payment->status,
                    'mpesa_receipt' => $payment->mpesa_receipt,
                    'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i'),
                    'invoice_billing_type' => $payment->invoice?->billing_type,
                ])->all(),
            ],
        ];
    }
}
