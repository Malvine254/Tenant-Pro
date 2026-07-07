<?php

namespace App\Services;

use App\Mail\TenantProUpdateMail;
use App\Models\Invoice;
use App\Models\Invitation;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TenantEmailService
{
    public function landlordInvitation(Invitation $invitation): bool
    {
        $inviteUrl = $this->inviteUrl($invitation);

        return $this->sendToAddress($invitation->email, $invitation->invitee_name, [
            'subjectLine' => 'You have been invited to TenantPro',
            'preheader' => 'Accept your Starmax TenantPro landlord invitation.',
            'title' => 'Welcome to Starmax TenantPro',
            'introLines' => [
                'Hello '.$this->displayName($invitation->invitee_name).',',
                'You have been invited to join TenantPro as a landlord. Accept the invitation to set up your access and start managing properties, units, rent, and maintenance.',
                $invitation->message ?: 'TenantPro helps you manage property operations from one clean dashboard.',
            ],
            'details' => array_filter([
                'Business name' => $invitation->business_name,
                'Invitation code' => $invitation->code,
                'Expires on' => $invitation->expires_at?->format('d M Y'),
            ]),
            'actionLabel' => 'Accept invitation',
            'actionUrl' => $inviteUrl,
            'footerText' => 'If you were not expecting this invitation, you can ignore this email.',
        ]);
    }

    public function tenantInvitation(Invitation $invitation): bool
    {
        $invitation->loadMissing(['property', 'unit']);

        return $this->sendToAddress($invitation->email, $invitation->invitee_name, [
            'subjectLine' => 'You have been invited to join TenantPro',
            'preheader' => 'Accept your TenantPro invitation and link your unit.',
            'title' => 'You have a tenant invitation',
            'introLines' => [
                'Hello '.$this->displayName($invitation->invitee_name).',',
                'You have been invited to join TenantPro for '.$invitation->property?->name.', Unit '.$invitation->unit?->unit_number.'.',
                'Please accept the invitation, create your account or sign in, and complete your tenant profile from the TenantPro Android app. Your M-Pesa payment details remain yours to manage.',
            ],
            'details' => array_filter([
                'Property' => $invitation->property?->name,
                'Unit' => $invitation->unit?->unit_number,
                'Invitation code' => $invitation->code,
                'Move-in date' => data_get($invitation->metadata, 'move_in_date'),
                'Monthly rent' => data_get($invitation->metadata, 'rent_amount')
                    ? 'KSh '.number_format((float) data_get($invitation->metadata, 'rent_amount'), 2)
                    : null,
                'Deposit' => data_get($invitation->metadata, 'deposit_amount')
                    ? 'KSh '.number_format((float) data_get($invitation->metadata, 'deposit_amount'), 2)
                    : null,
                'Expires on' => $invitation->expires_at?->format('d M Y'),
            ]),
            'actionLabel' => 'Accept invitation',
            'actionUrl' => $this->inviteUrl($invitation),
            'footerText' => 'Your landlord cannot edit your M-Pesa details. Add or update them yourself in the TenantPro app.',
        ]);
    }

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
                'Payment phone' => $payment->payment_phone ?: 'Not captured',
                'M-Pesa receipt' => $payment->mpesa_receipt ?: 'Not captured',
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

        return $this->sendToAddress($user->email, $user->name, $payload, $user->id);
    }

    private function sendToAddress(?string $email, ?string $name, array $payload, ?string $userId = null): bool
    {
        if (!$email) {
            return false;
        }

        try {
            Mail::to($email, $name)->send(new TenantProUpdateMail(...$payload));
            return true;
        } catch (Throwable $exception) {
            Log::error('TenantPro email update failed', [
                'user_id' => $userId,
                'email' => $email,
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

    private function displayName(?string $name): string
    {
        return trim((string) $name) !== '' ? trim((string) $name) : 'there';
    }

    private function inviteUrl(Invitation $invitation): string
    {
        return rtrim(config('app.url'), '/').'/admin/login?invite='.$invitation->code;
    }

    private function invoicePeriod(Invoice $invoice): string
    {
        if (!$invoice->period_month || !$invoice->period_year) {
            return 'Not specified';
        }

        return date('F Y', mktime(0, 0, 0, (int) $invoice->period_month, 1, (int) $invoice->period_year));
    }
}
