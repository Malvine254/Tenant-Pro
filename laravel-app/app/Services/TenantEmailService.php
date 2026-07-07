<?php

namespace App\Services;

use App\Mail\TenantProUpdateMail;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TenantEmailService
{
    public function tenantAssigned(Tenant $tenant): bool
    {
        $tenant->loadMissing(['user', 'unit.property']);

        return $this->send($tenant->user, [
            'subjectLine' => 'Your apartment has been assigned',
            'preheader' => 'Your TenantPro unit assignment is now active.',
            'title' => 'Your apartment is ready in TenantPro',
            'introLines' => [
                'Hi '.$this->firstName($tenant->user).',',
                'Your landlord has assigned you to a unit. You can now view rent bills, payments, maintenance, and property updates from your TenantPro app.',
            ],
            'details' => [
                'Property' => $tenant->unit?->property?->name ?? 'Not specified',
                'Unit' => $tenant->unit?->unit_number ?? 'Not specified',
                'Floor' => $tenant->unit?->floor === null ? 'Not specified' : 'Floor '.$tenant->unit->floor,
                'Monthly rent' => $tenant->unit?->rent_amount_formatted ?? 'Not specified',
                'Move-in date' => $tenant->move_in_date?->format('d M Y') ?? 'Not specified',
            ],
            'actionLabel' => 'Open TenantPro',
            'actionUrl' => config('app.url'),
            'footerText' => 'If anything looks wrong, please contact your landlord or property manager.',
        ]);
    }

    public function tenancyClosed(Tenant $tenant): bool
    {
        $tenant->loadMissing(['user', 'unit.property']);

        return $this->send($tenant->user, [
            'subjectLine' => 'Your tenancy has been closed',
            'preheader' => 'Your unit has been marked vacant in TenantPro.',
            'title' => 'Tenancy closed',
            'introLines' => [
                'Hi '.$this->firstName($tenant->user).',',
                'Your tenancy record has been closed and the unit is now marked as vacant.',
            ],
            'details' => [
                'Property' => $tenant->unit?->property?->name ?? 'Not specified',
                'Unit' => $tenant->unit?->unit_number ?? 'Not specified',
                'Closing date' => $tenant->move_out_date?->format('d M Y') ?? now()->format('d M Y'),
            ],
            'footerText' => 'Thank you for using TenantPro.',
        ]);
    }

    public function invoiceCreated(Invoice $invoice): bool
    {
        $invoice->loadMissing(['tenant', 'unit.property']);

        return $this->send($invoice->tenant, [
            'subjectLine' => 'New rent invoice issued',
            'preheader' => 'A new invoice is available in your TenantPro account.',
            'title' => 'New invoice available',
            'introLines' => [
                'Hi '.$this->firstName($invoice->tenant).',',
                'A new invoice has been issued for your unit.',
            ],
            'details' => [
                'Property' => $invoice->unit?->property?->name ?? 'Not specified',
                'Unit' => $invoice->unit?->unit_number ?? 'Not specified',
                'Billing period' => $this->invoicePeriod($invoice),
                'Amount due' => $invoice->total_amount_formatted,
                'Due date' => $invoice->due_date?->format('d M Y') ?? 'Not specified',
            ],
            'actionLabel' => 'View invoice',
            'actionUrl' => config('app.url'),
            'footerText' => 'Please pay before the due date to keep your account in good standing.',
        ]);
    }

    public function paymentReceived(Payment $payment): bool
    {
        $payment->loadMissing(['invoice.tenant', 'invoice.unit.property']);
        $invoice = $payment->invoice;

        return $this->send($invoice?->tenant, [
            'subjectLine' => 'Payment received',
            'preheader' => 'Your rent payment has been recorded.',
            'title' => 'Payment received successfully',
            'introLines' => [
                'Hi '.$this->firstName($invoice?->tenant).',',
                'We have recorded your payment. Here are the details.',
            ],
            'details' => [
                'Property' => $invoice?->unit?->property?->name ?? 'Not specified',
                'Unit' => $invoice?->unit?->unit_number ?? 'Not specified',
                'Amount paid' => 'KSh '.number_format((float) $payment->amount, 2),
                'Payment method' => $payment->method ?: 'Not specified',
                'Reference' => $payment->reference ?: 'Not specified',
                'Invoice balance' => $invoice?->balance_amount_formatted ?? 'Not specified',
            ],
            'actionLabel' => 'View payment',
            'actionUrl' => config('app.url'),
            'footerText' => 'Keep this email as your payment confirmation.',
        ]);
    }

    public function maintenanceUpdated(MaintenanceRequest $maintenanceRequest, ?string $previousStatus = null): bool
    {
        $maintenanceRequest->loadMissing(['tenant', 'unit.property', 'assignedTo']);

        return $this->send($maintenanceRequest->tenant, [
            'subjectLine' => 'Maintenance request updated',
            'preheader' => 'There is an update on your maintenance request.',
            'title' => 'Maintenance update',
            'introLines' => [
                'Hi '.$this->firstName($maintenanceRequest->tenant).',',
                'Your maintenance request has been updated.',
            ],
            'details' => array_filter([
                'Request' => $maintenanceRequest->title,
                'Property' => $maintenanceRequest->unit?->property?->name ?? 'Not specified',
                'Unit' => $maintenanceRequest->unit?->unit_number ?? 'Not specified',
                'Previous status' => $previousStatus,
                'Current status' => $maintenanceRequest->status,
                'Assigned to' => $maintenanceRequest->assignedTo?->name,
                'Resolved on' => $maintenanceRequest->resolved_at?->format('d M Y H:i'),
            ], fn($value) => $value !== null && $value !== ''),
            'actionLabel' => 'Open request',
            'actionUrl' => config('app.url'),
            'footerText' => 'We will keep you updated as the request progresses.',
        ]);
    }

    private function send(?User $user, array $payload): bool
    {
        if (!$user?->email) {
            return false;
        }

        try {
            Mail::to($user->email, $user->name)->send(new TenantProUpdateMail(...$payload));
            return true;
        } catch (Throwable $exception) {
            Log::error('TenantPro email update failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subject' => $payload['subjectLine'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function firstName(?User $user): string
    {
        return $user?->first_name ?: strtok((string) $user?->name, ' ') ?: 'there';
    }

    private function invoicePeriod(Invoice $invoice): string
    {
        if (!$invoice->period_month || !$invoice->period_year) {
            return 'Not specified';
        }

        return date('F Y', mktime(0, 0, 0, (int) $invoice->period_month, 1, (int) $invoice->period_year));
    }
}
