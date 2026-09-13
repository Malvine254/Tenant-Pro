<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\TenantAppNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendRentRemindersCommand extends Command
{
    protected $signature = 'rent:send-reminders {--force : Send reminders regardless of recent notification timestamp}';
    protected $description = 'Send automated push and email reminders to tenants with upcoming or overdue invoices';

    public function handle(TenantAppNotificationService $notificationService): int
    {
        $today = Carbon::today();
        $in3Days = Carbon::today()->addDays(3);
        $force = $this->option('force');

        $invoices = Invoice::query()
            ->with(['tenant', 'unit.property'])
            ->whereIn('status', ['PENDING', 'OVERDUE'])
            ->where('total_amount', '>', 0)
            ->whereRaw('total_amount > paid_amount')
            ->whereNotNull('tenant_id')
            ->where(function ($q) use ($in3Days, $today) {
                $q->whereDate('due_date', '<=', $in3Days);
            })
            ->when(! $force, function ($q) {
                // Do not spam if a reminder was already sent in the last 20 hours
                $q->where(function ($sub) {
                    $sub->whereNull('last_reminder_sent_at')
                        ->orWhere('last_reminder_sent_at', '<', Carbon::now()->subHours(20));
                });
            })
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date) : null;
            if (! $dueDate) continue;

            $urgency = match (true) {
                $dueDate->isPast() && ! $dueDate->isToday() => 'OVERDUE',
                $dueDate->isToday() => 'DUE_TODAY',
                default => 'DUE_SOON',
            };

            $notificationService->rentReminder($invoice, $urgency);
            $invoice->update(['last_reminder_sent_at' => now()]);
            $count++;
        }

        $this->info("Successfully dispatched rent reminders for {$count} invoices.");
        return Command::SUCCESS;
    }
}
