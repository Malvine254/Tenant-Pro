<?php

namespace App\Services\AI\Tools;

use App\Models\Invoice;
use App\Models\User;

class GetInvoicesTool implements ChatTool
{
    public function name(): string
    {
        return 'get_invoices';
    }

    public function description(): string
    {
        return "List the authenticated tenant's own invoices (rent, water, electricity, garbage, etc.) "
            .'with amounts, balances, due dates and status. Use this for questions about bills, balances, '
            .'or what is owed. Never returns invoices belonging to another tenant.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['PENDING', 'PARTIAL', 'PAID', 'OVERDUE', 'CANCELLED'],
                    'description' => 'Optional filter by invoice status.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of invoices to return (default 10, max 25).',
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

        // Tenant identity always comes from the authenticated user, never from tool arguments.
        $invoices = Invoice::with(['unit.property'])
            ->where('tenant_id', $user->id)
            ->when($arguments['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('due_date')
            ->limit($limit)
            ->get();

        return [
            'ok' => true,
            'data' => [
                'invoices' => $invoices->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'billing_type' => $invoice->billing_type,
                    'period' => sprintf('%04d-%02d', $invoice->period_year, $invoice->period_month),
                    'total_amount_formatted' => $invoice->total_amount_formatted,
                    'paid_amount_formatted' => $invoice->paid_amount_formatted,
                    'balance_amount_formatted' => $invoice->balance_amount_formatted,
                    'status' => $invoice->status,
                    'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                    'property_unit' => trim(($invoice->unit?->property?->name ?? '').' Unit '.($invoice->unit?->unit_number ?? '')),
                ])->all(),
            ],
        ];
    }
}
