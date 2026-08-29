<?php

namespace App\Services;

use App\Mail\TenantProUpdateMail;
use App\Models\Invoice;
use App\Models\Invitation;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\SupportMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TenantEmailService
{
    public function sendSubscriptionDueReminder(User $landlord, \Illuminate\Support\Carbon $dueDate, int $daysUntilDue): bool
    {
        $dueText = $dueDate->format('d M Y');

        return $this->send($landlord, [
            'subjectLine' => $daysUntilDue === 0
                ? 'TenantPro subscription due today'
                : 'TenantPro subscription renewal reminder',
            'preheader' => $daysUntilDue === 0
                ? 'Your TenantPro subscription is due today.'
                : 'Your TenantPro subscription renewal date is approaching.',
            'title' => $daysUntilDue === 0 ? 'Subscription due today' : 'Subscription renewal reminder',
            'introLines' => [
                'Hi '.$this->firstName($landlord).',',
                $daysUntilDue === 0
                    ? 'Your TenantPro service subscription is due today. Please renew to avoid an interruption to portal access.'
                    : 'Your TenantPro service subscription is due in '.$daysUntilDue.' day'.($daysUntilDue === 1 ? '' : 's').'.',
            ],
            'details' => [
                'Renewal date' => $dueText,
                'Monthly service fee' => 'KES '.number_format((float) ($landlord->monthly_service_fee ?? 0), 2),
                'Subscription status' => ucfirst(str_replace('_', ' ', (string) $landlord->billing_status)),
            ],
            'actionLabel' => 'Open TenantPro portal',
            'actionUrl' => rtrim(config('app.url'), '/').'/admin/dashboard',
            'footerText' => 'You will receive one reminder for each scheduled interval before your renewal date.',
        ]);
    }

    public function sendRentDueReminder(Invoice $invoice, int $daysUntilDue): bool
    {
        $invoice->loadMissing(['tenant', 'unit.property']);

        return $this->send($invoice->tenant, [
            'subjectLine' => $daysUntilDue === 0 ? 'Rent due today' : 'Upcoming rent due reminder',
            'preheader' => $daysUntilDue === 0
                ? 'Your rent payment is due today.'
                : 'Your rent payment due date is approaching.',
            'title' => $daysUntilDue === 0 ? 'Rent due today' : 'Upcoming rent reminder',
            'introLines' => [
                'Hi '.$this->firstName($invoice->tenant).',',
                $daysUntilDue === 0
                    ? 'This is a reminder that your rent payment is due today.'
                    : 'This is a reminder that your rent payment is due in '.$daysUntilDue.' day'.($daysUntilDue === 1 ? '' : 's').'.',
            ],
            'details' => [
                'Property' => $invoice->unit?->property?->name ?? 'Not specified',
                'Unit' => $invoice->unit?->unit_number ?? 'Not specified',
                'Due date' => $invoice->due_date ? date('d M Y', strtotime((string) $invoice->due_date)) : 'Not specified',
                'Amount due' => $invoice->balance_amount_formatted,
                'Billing period' => $this->invoicePeriod($invoice),
            ],
            'actionLabel' => 'Open TenantPro app',
            'actionUrl' => $this->tenantAppUrl(),
            'footerText' => 'This is a periodic reminder. You will not receive duplicate reminders for the same reminder interval.',
            'eyebrow' => 'Invoice reminder',
            'document' => $this->invoiceDocument($invoice),
        ]);
    }

    public function sendRentOverdueReminder(Invoice $invoice, int $daysOverdue): bool
    {
        $invoice->loadMissing(['tenant', 'unit.property']);

        return $this->send($invoice->tenant, [
            'subjectLine' => 'Overdue rent reminder',
            'preheader' => 'Your rent payment is overdue.',
            'title' => 'Overdue rent reminder',
            'introLines' => [
                'Hi '.$this->firstName($invoice->tenant).',',
                'Your rent payment is currently overdue by '.$daysOverdue.' day'.($daysOverdue === 1 ? '' : 's').'.',
                'Please clear the balance to keep your account in good standing.',
            ],
            'details' => [
                'Property' => $invoice->unit?->property?->name ?? 'Not specified',
                'Unit' => $invoice->unit?->unit_number ?? 'Not specified',
                'Original due date' => $invoice->due_date ? date('d M Y', strtotime((string) $invoice->due_date)) : 'Not specified',
                'Days overdue' => (string) $daysOverdue,
                'Outstanding balance' => $invoice->balance_amount_formatted,
                'Billing period' => $this->invoicePeriod($invoice),
            ],
            'actionLabel' => 'Open TenantPro app',
            'actionUrl' => $this->tenantAppUrl(),
            'footerText' => 'This is a periodic reminder to help avoid too-frequent notifications while keeping you updated.',
            'eyebrow' => 'Overdue invoice',
            'document' => $this->invoiceDocument($invoice),
        ]);
    }

    public function tenantAccountCreated(Tenant $tenant): bool
    {
        $tenant->loadMissing(['user', 'unit.property']);

        return $this->send($tenant->user, [
            'subjectLine' => 'Your TenantPro account is ready',
            'preheader' => 'Your TenantPro account and apartment assignment are ready.',
            'title' => 'Welcome to TenantPro',
            'introLines' => [
                'Hi '.$this->firstName($tenant->user).',',
                'Your TenantPro account has been created and linked to your apartment. You can now sign in to view invoices, payments, maintenance, and property updates.',
                'For security, your M-Pesa payment details are managed only by you from the TenantPro Android app. Your landlord cannot edit them from the admin portal.',
            ],
            'details' => [
                'Login email' => $tenant->user?->email ?? 'Not specified',
                'Property' => $tenant->unit?->property?->name ?? 'Not specified',
                'Unit' => $tenant->unit?->unit_number ?? 'Not specified',
                'Monthly rent' => $tenant->unit?->rent_amount_formatted ?? 'Not specified',
                'Move-in date' => $tenant->move_in_date
                    ? date('d M Y', strtotime((string) $tenant->move_in_date))
                    : 'Not specified',
            ],
            'actionLabel' => 'Open TenantPro',
            'actionUrl' => $this->tenantAppUrl(),
            'footerText' => 'If you do not know your password, use Reset Password on the login screen to create a new one.',
        ]);
    }

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

    public function tenantInvitation(Invitation $invitation, array $options = []): bool
    {
        $invitation->loadMissing(['property', 'unit']);

        $loginEmail = strtolower(trim((string) ($options['loginEmail'] ?? $invitation->email ?? '')));
        $temporaryPassword = (string) ($options['temporaryPassword'] ?? '');
        $firstTimeSetup = (bool) ($options['firstTimeSetup'] ?? false);
        $downloadUrl = $this->tenantAppDownloadUrl();

        $details = array_filter([
            'Property' => $invitation->property?->name,
            'Unit' => $invitation->unit?->unit_number,
            'Invitation code' => $invitation->code,
            'Login email' => $loginEmail ?: null,
            'Temporary password' => $temporaryPassword !== '' ? $temporaryPassword : null,
            'Move-in date' => data_get($invitation->metadata, 'move_in_date'),
            'Monthly rent' => data_get($invitation->metadata, 'rent_amount')
                ? 'KSh '.number_format((float) data_get($invitation->metadata, 'rent_amount'), 2)
                : null,
            'Deposit' => data_get($invitation->metadata, 'deposit_amount')
                ? 'KSh '.number_format((float) data_get($invitation->metadata, 'deposit_amount'), 2)
                : null,
            'Expires on' => $invitation->expires_at?->format('d M Y'),
            'Android app download' => $downloadUrl,
        ]);

        $introLines = [
            'Hello '.$this->displayName($invitation->invitee_name).',',
            'You have been invited to join TenantPro for '.$invitation->property?->name.', Unit '.$invitation->unit?->unit_number.'.',
            $firstTimeSetup
                ? 'Your tenant login has been prepared. Use the login email and temporary password below, then change your password after your first successful sign-in.'
                : 'Use your existing TenantPro account. If you do not remember your password, use Reset Password on the app login screen.',
            'Open the invitation instructions link, install the Android app if needed, then sign in and accept your invitation code from Account -> Accept Invitation.',
        ];

        $footerText = $firstTimeSetup
            ? 'For security, change your temporary password after first login. Your landlord cannot edit your M-Pesa details.'
            : 'If you cannot sign in, use Reset Password in the app. Your landlord cannot edit your M-Pesa details.';

        return $this->sendToAddress($invitation->email, $invitation->invitee_name, [
            'subjectLine' => 'You have been invited to join TenantPro',
            'preheader' => 'Open app onboarding instructions and accept your TenantPro invitation.',
            'title' => 'You have a tenant invitation',
            'introLines' => $introLines,
            'details' => $details,
            'actionLabel' => 'Open invitation instructions',
            'actionUrl' => $this->tenantInviteUrl($invitation),
            'footerText' => $footerText,
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
                'Move-in date' => $tenant->move_in_date
                    ? date('d M Y', strtotime((string) $tenant->move_in_date))
                    : 'Not specified',
            ],
            'actionLabel' => 'Open TenantPro',
            'actionUrl' => $this->tenantAppUrl(),
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
                'Closing date' => $tenant->move_out_date
                    ? date('d M Y', strtotime((string) $tenant->move_out_date))
                    : now()->format('d M Y'),
            ],
            'footerText' => 'Thank you for using TenantPro.',
        ]);
    }

    public function invoiceCreated(Invoice $invoice): bool
    {
        $invoice->loadMissing(['tenant', 'unit.property']);

        return $this->send($invoice->tenant, [
            'subjectLine' => 'New '.strtolower((string) $invoice->billing_type).' invoice issued',
            'preheader' => 'A new invoice is available in your TenantPro account.',
            'title' => 'New invoice available',
            'introLines' => [
                'Hi '.$this->firstName($invoice->tenant).',',
                'A new '.strtolower((string) $invoice->billing_type).' invoice has been issued for your unit.',
            ],
            'details' => [
                'Property' => $invoice->unit?->property?->name ?? 'Not specified',
                'Unit' => $invoice->unit?->unit_number ?? 'Not specified',
                'Billing period' => $this->invoicePeriod($invoice),
                'Amount due' => $invoice->total_amount_formatted,
                'Due date' => $invoice->due_date
                    ? date('d M Y', strtotime((string) $invoice->due_date))
                    : 'Not specified',
            ],
            'actionLabel' => 'View invoice',
            'actionUrl' => $this->tenantAppUrl(),
            'footerText' => 'Please pay before the due date to keep your account in good standing.',
            'eyebrow' => 'Invoice issued',
            'document' => $this->invoiceDocument($invoice),
        ]);
    }

    public function paymentReceived(Payment $payment): bool
    {
        $payment->loadMissing(['invoice.tenant', 'invoice.unit.property.landlord']);
        $invoice = $payment->invoice;

        $details = [
            'Property' => $invoice?->unit?->property?->name ?? 'Not specified',
            'Unit' => $invoice?->unit?->unit_number ?? 'Not specified',
            'Amount paid' => 'KSh '.number_format((float) $payment->amount, 2),
            'Payment method' => $payment->method ?: 'Not specified',
            'Payment phone' => $payment->payment_phone ?: 'Not captured',
            'M-Pesa receipt' => $payment->mpesa_receipt ?: 'Not captured',
            'Reference' => $payment->reference ?: 'Not specified',
            'Invoice balance' => $invoice?->balance_amount_formatted ?? 'Not specified',
            'Paid at' => $payment->paid_at?->format('d M Y H:i') ?? now()->format('d M Y H:i'),
        ];

        $tenantSent = $this->send($invoice?->tenant, [
            'subjectLine' => 'Payment received',
            'preheader' => 'Your rent payment has been recorded.',
            'title' => 'Payment received successfully',
            'introLines' => [
                'Hi '.$this->firstName($invoice?->tenant).',',
                'We have recorded your payment. Here are the details.',
            ],
            'details' => $details,
            'actionLabel' => 'View payment',
            'actionUrl' => $this->tenantAppUrl(),
            'footerText' => 'Keep this email as your payment confirmation.',
        ]);

        $stakeholders = collect([$invoice?->unit?->property?->landlord])
            ->merge(User::query()
                ->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->whereIn('name', ['ADMIN', 'SUPER_ADMIN']))
                ->get())
            ->filter(fn ($user) => $user?->email)
            ->reject(fn ($user) => $user->id === $invoice?->tenant?->id)
            ->unique('email');

        foreach ($stakeholders as $recipient) {
            $this->send($recipient, [
                'subjectLine' => 'Tenant payment confirmed',
                'preheader' => 'A tenant payment has been completed and recorded.',
                'title' => 'Payment confirmation',
                'introLines' => [
                    'Hi '.$this->firstName($recipient).',',
                    ($invoice?->tenant?->name ?? 'A tenant').' has completed a payment. The invoice and receipt details are below.',
                ],
                'details' => ['Tenant' => $invoice?->tenant?->name ?? 'Not specified'] + $details,
                'actionLabel' => 'View invoices',
                'actionUrl' => rtrim(config('app.url'), '/').'/admin/invoices',
                'footerText' => 'This confirmation was generated automatically by TenantPro.',
            ]);
        }

        return $tenantSent;
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
            'actionUrl' => $this->tenantAppUrl(),
            'footerText' => 'We will keep you updated as the request progresses.',
        ]);
    }

    public function supportMessageReceived(SupportMessage $message): int
    {
        $message->loadMissing([
            'conversation.tenant.tenant.unit.property.landlord',
            'sender',
        ]);

        $tenant = $message->conversation?->tenant;
        $tenancy = $tenant?->tenant;
        $unit = $tenancy?->unit;
        $property = $unit?->property;

        $recipients = collect();

        if ($property?->landlord?->email) {
            $recipients->push($property->landlord);
        }

        User::whereHas('role', fn($role) => $role->whereIn('name', ['SUPER_ADMIN', 'ADMIN']))
            ->whereNotNull('email')
            ->get()
            ->each(fn(User $user) => $recipients->push($user));

        $sent = 0;
        foreach ($recipients->unique('email') as $recipient) {
            $emailSent = $this->send($recipient, [
                'subjectLine' => 'New tenant chat message',
                'preheader' => 'A tenant has sent a new message from the TenantPro app.',
                'title' => 'New tenant message',
                'introLines' => [
                    'Hello '.$this->firstName($recipient).',',
                    'A tenant has sent a new chat message from the TenantPro Android app. The full tenant and apartment details are below.',
                ],
                'details' => [
                    'Tenant name' => $tenant?->name ?? 'Unknown tenant',
                    'Tenant email' => $tenant?->email ?? 'No email',
                    'Tenant phone' => $tenant?->phone_number ?? 'No phone',
                    'Apartment / Property' => $property?->name ?? 'Not linked',
                    'Room / Unit' => $unit?->unit_number ? 'Unit '.$unit->unit_number : 'Not linked',
                    'Floor' => $unit?->floor === null ? 'Not specified' : 'Floor '.$unit->floor,
                    'Topic' => $message->topic,
                    'Message' => $message->body,
                    'Sent at' => $message->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i'),
                ],
                'actionLabel' => 'Open admin chat',
                'actionUrl' => rtrim(config('app.url'), '/').'/admin/chats?conversation_id='.$message->conversation_id,
                'footerText' => 'Reply from the TenantPro admin portal so the tenant can see your response in the app.',
            ]);

            if ($emailSent) {
                $sent++;
            }
        }

        return $sent;
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
            Log::info('TenantPro email update sent', [
                'user_id' => $userId,
                'email' => $email,
                'subject' => $payload['subjectLine'] ?? null,
            ]);
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
            return rtrim(config('app.url'), '/').'/invite/'.$invitation->code;
        }

    private function tenantInviteUrl(Invitation $invitation): string
    {
        $template = env(
            'TENANT_APP_INVITE_URL',
            rtrim(config('app.url'), '/').'/invite?code={code}'
        );

        return str_replace('{code}', urlencode($invitation->code), $template);
    }

    private function tenantAppUrl(): string
    {
        return env('TENANT_APP_URL', 'tenantpro://open');
    }

    private function tenantAppDownloadUrl(): string
    {
        return env('TENANT_APP_DOWNLOAD_URL', rtrim(config('app.url'), '/').'/download/apk');
    }

    private function invoicePeriod(Invoice $invoice): string
    {
        if (!$invoice->period_month || !$invoice->period_year) {
            return 'Not specified';
        }

        return date('F Y', mktime(0, 0, 0, (int) $invoice->period_month, 1, (int) $invoice->period_year));
    }

    private function invoiceDocument(Invoice $invoice): array
    {
        $type = ucfirst(strtolower(str_replace('_', ' ', (string) $invoice->billing_type)));
        $lineItems = [[
            'description' => $type.' charge',
            'period' => $this->invoicePeriod($invoice),
            'amount' => $invoice->amount_formatted,
        ]];
        if ((float) $invoice->penalty_amount > 0) {
            $lineItems[] = [
                'description' => 'Penalty / late fee',
                'period' => $this->invoicePeriod($invoice),
                'amount' => $invoice->penalty_amount_formatted,
            ];
        }

        return [
            'number' => '#'.strtoupper(substr(str_replace('-', '', (string) $invoice->id), -10)),
            'status' => ucfirst(strtolower((string) $invoice->status)),
            'billTo' => $invoice->tenant?->name ?? 'Tenant',
            'property' => $invoice->unit?->property?->name ?? 'Property not specified',
            'unit' => $invoice->unit?->unit_number ? 'Unit '.$invoice->unit->unit_number : null,
            'issueDate' => $invoice->issue_date?->format('d M Y') ?? 'Not specified',
            'dueDate' => $invoice->due_date?->format('d M Y') ?? 'Not specified',
            'lineItems' => $lineItems,
            'totals' => array_filter([
                'Subtotal' => $invoice->amount_formatted,
                'Penalty' => (float) $invoice->penalty_amount > 0 ? $invoice->penalty_amount_formatted : null,
                'Invoice total' => $invoice->total_amount_formatted,
                'Amount paid' => $invoice->paid_amount_formatted,
            ], fn ($value) => $value !== null),
            'balance' => $invoice->balance_amount_formatted,
        ];
    }
}
