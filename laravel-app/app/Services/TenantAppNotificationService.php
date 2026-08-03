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
            'due_date' => $invoice->due_date ? date('Y-m-d', strtotime((string) $invoice->due_date)) : null,
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

    public function supportReply(User $tenant, string $topic, string $body, ?string $conversationId = null): ?Notification
    {
        return $this->notify($tenant, 'SUPPORT_REPLY', 'New chat reply', sprintf(
            '%s: %s',
            $topic ?: 'Support',
            str($body)->limit(120)
        ), ['conversation_id' => $conversationId]);
    }

    public function notify(?User $user, string $type, string $title, string $body, array $metadata = []): ?Notification
    {
        if (!$user) {
            return null;
        }

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'metadata' => $metadata,
        ]);

        if ($user->fcm_token) {
            $highPriority = $this->isHighPriority($type, $title, $body);
            app(FirebasePushService::class)->send($user->fcm_token, $title, $body, [
                'type' => $type,
                'notification_id' => $notification->id,
                'notification_destination' => $this->destination($type),
                'destination' => $this->destination($type),
                ...collect($metadata)->mapWithKeys(fn ($value, $key) => [$key => is_scalar($value) ? (string) $value : json_encode($value)])->all(),
            ], $highPriority, 'tenantpro_default');
        }

        return $notification;
    }

    private function destination(string $type): string
    {
        return match ($type) {
            'SUPPORT_REPLY' => 'CHAT',
            'INVOICE_CREATED' => 'INVOICES',
            'PAYMENT_RECEIVED' => 'PAYMENTS',
            'MAINTENANCE' => 'MAINTENANCE',
            'TENANCY_ASSIGNED' => 'RENTAL',
            default => 'NOTIFICATIONS',
        };
    }

    private function isHighPriority(string $type, string $title, string $body): bool
    {
        $normalizedType = strtoupper($type);
        $combined = strtolower($title.' '.$body);

        if (in_array($normalizedType, ['SUPPORT_REPLY', 'MAINTENANCE', 'PAYMENT_RECEIVED'], true)) {
            return true;
        }

        return str_contains($combined, 'overdue')
            || str_contains($combined, 'urgent')
            || str_contains($combined, 'failed');
    }
}
