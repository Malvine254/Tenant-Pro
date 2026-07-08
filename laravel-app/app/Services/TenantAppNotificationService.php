<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;

class TenantAppNotificationService
{
    public function tenantAssigned(Tenant $tenant): ?Notification
    {
        $tenant->loadMissing(['user', 'unit.property']);

        return $this->notify($tenant->user, 'TENANCY_ASSIGNED', 'Apartment assigned', sprintf(
            'You have been assigned to %s, Unit %s.',
            $tenant->unit?->property?->name ?? 'your property',
            $tenant->unit?->unit_number ?? '-'
        ), [
            'tenant_id' => $tenant->id,
            'unit_id' => $tenant->unit_id,
            'property_id' => $tenant->unit?->property_id,
        ]);
    }

    public function invoiceCreated(Invoice $invoice): ?Notification
    {
        $invoice->loadMissing(['tenant', 'unit.property']);

        return $this->notify($invoice->tenant, 'INVOICE_CREATED', 'New rent invoice', sprintf(
            'A rent invoice of %s is ready for %s, Unit %s.',
            $invoice->total_amount_formatted,
            $invoice->unit?->property?->name ?? 'your property',
            $invoice->unit?->unit_number ?? '-'
        ), [
            'invoice_id' => $invoice->id,
            'unit_id' => $invoice->unit_id,
            'property_id' => $invoice->unit?->property_id,
            'amount' => (float) $invoice->total_amount,
            'due_date' => $invoice->due_date?->toDateString(),
        ]);
    }

    public function paymentReceived(Payment $payment): ?Notification
    {
        $payment->loadMissing(['invoice.tenant', 'invoice.unit.property']);
        $invoice = $payment->invoice;

        return $this->notify($invoice?->tenant, 'PAYMENT_RECEIVED', 'Payment received', sprintf(
            'Your payment of KSh %s has been recorded.',
            number_format((float) $payment->amount, 2)
        ), [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice?->id,
            'amount' => (float) $payment->amount,
        ]);
    }

    public function supportReply(User $tenant, string $topic, string $body): ?Notification
    {
        return $this->notify($tenant, 'SUPPORT_REPLY', 'New chat reply', sprintf(
            '%s: %s',
            $topic ?: 'Support',
            str($body)->limit(120)
        ));
    }

    public function notify(?User $user, string $type, string $title, string $body, array $metadata = []): ?Notification
    {
        if (!$user) {
            return null;
        }

        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'metadata' => $metadata,
        ]);
    }
}
