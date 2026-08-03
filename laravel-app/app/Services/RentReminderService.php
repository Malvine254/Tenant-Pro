<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RentReminderService
{
    public function __construct(
        private readonly TenantEmailService $emailService,
        private readonly TenantAppNotificationService $appNotificationService,
    ) {
    }

    public function runDailyReminders(): array
    {
        $today = Carbon::today();
        /** @var \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices */
        $invoices = Invoice::query()
            ->with(['tenant', 'unit.property'])
            ->whereIn('status', ['PENDING', 'PARTIAL', 'OVERDUE'])
            ->whereDate('due_date', '<=', $today->copy()->addDays(45))
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($invoices as $invoice) {
            if (!$invoice instanceof Invoice) {
                $skipped++;
                continue;
            }

            $tenant = $invoice->tenant;
            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->startOfDay() : null;
            if (!$tenant || !$dueDate) {
                $skipped++;
                continue;
            }

            $daysUntilDue = $today->diffInDays($dueDate, false);
            if ($daysUntilDue >= 0) {
                if (!in_array($daysUntilDue, [7, 3, 1, 0], true)) {
                    $skipped++;
                    continue;
                }

                $reminderKey = sprintf('due-%s', $daysUntilDue);
                if ($this->alreadySentToday($tenant->id, $invoice->id, $reminderKey, $today)) {
                    $skipped++;
                    continue;
                }

                $body = $daysUntilDue === 0
                    ? sprintf(
                        'Rent for %s Unit %s is due today. Balance: %s.',
                        $invoice->unit?->property?->name ?? 'your property',
                        $invoice->unit?->unit_number ?? '-',
                        $invoice->balance_amount_formatted
                    )
                    : sprintf(
                        'Rent for %s Unit %s is due in %d day%s. Balance: %s.',
                        $invoice->unit?->property?->name ?? 'your property',
                        $invoice->unit?->unit_number ?? '-',
                        $daysUntilDue,
                        $daysUntilDue === 1 ? '' : 's',
                        $invoice->balance_amount_formatted
                    );

                $this->appNotificationService->notify(
                    $tenant,
                    'RENT_REMINDER',
                    $daysUntilDue === 0 ? 'Rent due today' : 'Upcoming rent reminder',
                    $body,
                    [
                        'invoice_id' => $invoice->id,
                        'unit_id' => $invoice->unit_id,
                        'property_id' => $invoice->unit?->property_id,
                        'due_date' => $dueDate->toDateString(),
                        'reminder_key' => $reminderKey,
                        'reminder_kind' => 'due',
                    ]
                );

                $this->emailService->sendRentDueReminder($invoice, $daysUntilDue);
                $sent++;
                continue;
            }

            $daysOverdue = abs($daysUntilDue);
            if (!$this->shouldSendOverdueReminder($daysOverdue)) {
                $skipped++;
                continue;
            }

            $reminderKey = $daysOverdue <= 14
                ? sprintf('overdue-%s', $daysOverdue)
                : sprintf('overdue-week-%s', intdiv($daysOverdue, 7));

            if ($this->alreadySentToday($tenant->id, $invoice->id, $reminderKey, $today)) {
                $skipped++;
                continue;
            }

            $body = sprintf(
                'Rent for %s Unit %s is overdue by %d day%s. Balance: %s.',
                $invoice->unit?->property?->name ?? 'your property',
                $invoice->unit?->unit_number ?? '-',
                $daysOverdue,
                $daysOverdue === 1 ? '' : 's',
                $invoice->balance_amount_formatted
            );

            $this->appNotificationService->notify(
                $tenant,
                'RENT_OVERDUE',
                'Overdue rent reminder',
                $body,
                [
                    'invoice_id' => $invoice->id,
                    'unit_id' => $invoice->unit_id,
                    'property_id' => $invoice->unit?->property_id,
                    'due_date' => $dueDate->toDateString(),
                    'days_overdue' => $daysOverdue,
                    'reminder_key' => $reminderKey,
                    'reminder_kind' => 'overdue',
                ]
            );

            $this->emailService->sendRentOverdueReminder($invoice, $daysOverdue);
            $sent++;
        }

        Log::info('Rent reminder job completed', [
            'invoices_considered' => $invoices->count(),
            'sent' => $sent,
            'skipped' => $skipped,
        ]);

        return [
            'invoicesConsidered' => $invoices->count(),
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }

    private function alreadySentToday(string $userId, string $invoiceId, string $reminderKey, Carbon $today): bool
    {
        return \App\Models\Notification::query()
            ->where('user_id', $userId)
            ->where('type', 'like', 'RENT_%')
            ->whereDate('created_at', $today)
            ->where('metadata->invoice_id', $invoiceId)
            ->where('metadata->reminder_key', $reminderKey)
            ->exists();
    }

    private function shouldSendOverdueReminder(int $daysOverdue): bool
    {
        if (in_array($daysOverdue, [1, 3, 7, 14], true)) {
            return true;
        }

        return $daysOverdue > 14 && $daysOverdue % 7 === 0;
    }
}
