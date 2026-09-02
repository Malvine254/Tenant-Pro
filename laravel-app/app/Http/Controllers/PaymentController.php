<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\MpesaService;
use App\Services\TenantAppNotificationService;
use App\Services\TenantEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::with(['invoice.tenant', 'invoice.unit', 'transactions'])
            ->when($this->isTenant($user), fn ($q) => $q->whereHas('invoice', fn ($invoice) => $invoice->where('tenant_id', $user->id)))
            ->when($user?->role?->name === 'LANDLORD', fn ($q) => $q->whereHas('invoice.unit.property', fn ($property) => $property->where('landlord_id', $user->id)))
            ->when($request->invoice_id, fn ($q) => $q->where('invoice_id', $request->invoice_id));

        return response()->json($query->latest()->paginate(min(max($request->integer('per_page', 15), 1), 100)));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->requireManager($user);
        $data = $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|string|max:50',
            'payment_phone' => 'nullable|string|max:30',
            'mpesa_receipt' => 'nullable|string|max:80',
            'status' => 'nullable|in:PENDING,SUCCESSFUL,FAILED,CANCELLED,EXPIRED,REVERSED',
            'reference' => 'nullable|string|unique:payments,reference',
        ]);
        abort_if($this->isTenant($user) && ! Invoice::where('id', $data['invoice_id'])->where('tenant_id', $user->id)->exists(), 403);
        $this->requireUnitManager($user, Invoice::with('unit.property')->findOrFail($data['invoice_id'])->unit);

        $data['status'] = $data['status'] ?? 'SUCCESSFUL';
        if ($data['status'] === 'SUCCESSFUL') {
            $data['paid_at'] = now();
        }
        $payment = Payment::create($data);

        $invoice = Invoice::find($data['invoice_id']);
        if ($payment->status === 'SUCCESSFUL') {
            $invoice->paid_amount = $invoice->paid_amount + $data['amount'];
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->status = 'PAID';
                $invoice->paid_at = now();
            } else {
                $invoice->status = 'PARTIAL';
            }
            $invoice->save();

            if (filled($payment->mpesa_receipt)) {
                $payment->transactions()->create([
                    'amount' => $payment->amount,
                    'type' => 'MPESA_MANUAL',
                    'description' => 'Manual M-Pesa payment verified by property management.',
                    'external_reference' => $payment->mpesa_receipt,
                    'processed_at' => $payment->paid_at ?? now(),
                    'raw_payload' => ['verified_manually' => true],
                ]);
            }
        }

        if ($payment->status === 'SUCCESSFUL') {
            app(TenantEmailService::class)->paymentReceived($payment->load('invoice.tenant', 'invoice.unit.property'));
            app(TenantAppNotificationService::class)->paymentReceived($payment);
        }

        return response()->json($payment->load('invoice'), 201);
    }

    public function show(Payment $payment)
    {
        $user = request()->user();
        abort_if($this->isTenant($user) && $payment->invoice()->where('tenant_id', $user->id)->doesntExist(), 403);
        if ($user?->role?->name === 'LANDLORD') {
            $this->requireUnitManager($user, $payment->invoice->unit);
        }

        return response()->json($payment->load(['invoice.tenant', 'invoice.unit', 'transactions']));
    }

    public function destroy(Payment $payment)
    {
        abort(405, 'Payment records are immutable and cannot be deleted.');
    }

    public function forInvoice(Request $request, Invoice $invoice)
    {
        abort_if($this->isTenant($request->user()) && $invoice->tenant_id !== $request->user()->id, 403);
        abort_if(
            $this->isTenant($request->user()) && ! $this->hasActiveTenancy($request->user()->id, $invoice->unit_id),
            404
        );
        if ($request->user()?->role?->name === 'LANDLORD') {
            $this->requireUnitManager($request->user(), $invoice->unit);
        }

        return response()->json($invoice->payments()->with('transactions')->latest()->get());
    }

    public function manualInstructions(Request $request, MpesaService $mpesa)
    {
        abort_unless($this->isTenant($request->user()), 403, 'Only tenant accounts can view payment instructions.');
        $data = $request->validate([
            'invoiceIds' => 'required|array|min:1',
            'invoiceIds.*' => 'required|uuid|distinct|exists:invoices,id',
        ]);

        $invoices = Invoice::with('unit.property.landlord')
            ->whereIn('id', $data['invoiceIds'])
            ->get();
        abort_if($invoices->count() !== count($data['invoiceIds']), 422, 'One or more selected invoices are unavailable.');

        foreach ($invoices as $invoice) {
            abort_if($invoice->tenant_id !== $request->user()->id, 403);
            abort_if(! $this->hasActiveTenancy($request->user()->id, $invoice->unit_id), 422, 'An invoice belongs to an inactive tenancy.');
        }
        abort_if(
            $invoices->pluck('unit.property.landlord_id')->filter()->unique()->count() > 1,
            422,
            'Bills managed by different landlords require separate payment instructions.'
        );

        $landlord = $invoices->first()?->unit?->property?->landlord;
        $settings = $landlord && is_array($landlord->app_settings)
            ? ($landlord->app_settings['paymentSettings'] ?? [])
            : [];
        $type = strtoupper((string) ($settings['payment_type'] ?? 'PAYBILL')) === 'TILL' ? 'TILL' : 'PAYBILL';
        $configuredNumber = trim((string) ($type === 'TILL'
            ? ($settings['till_number'] ?? '')
            : ($settings['paybill_number'] ?? '')));
        $number = $configuredNumber;
        $reference = trim((string) ($settings['account_reference'] ?? ''));
        $isProduction = $mpesa->environment() === 'production';
        $hasRequiredDetails = $number !== '' && (
            $type === 'TILL' || ($reference !== '' && ! in_array(strtolower($reference), ['tenant pro', 'starmax tenant services'], true))
        );
        $stkConfigured = $mpesa->isConfigured();

        return response()->json([
            'available' => $isProduction && $hasRequiredDetails,
            'stkAvailable' => $hasRequiredDetails && $stkConfigured,
            'stkMessage' => ! $stkConfigured
                ? 'STK Push is temporarily unavailable because the platform payment setup is incomplete. Use the manual payment details below.'
                : null,
            'paymentType' => $type,
            'businessNumber' => $number,
            'accountReference' => $type === 'PAYBILL' ? $reference : null,
            'businessName' => trim((string) ($settings['business_name'] ?? '')) ?: null,
            'note' => trim((string) ($settings['short_code_note'] ?? '')) ?: null,
            'verificationRequired' => true,
            'message' => ! $isProduction
                ? 'Manual M-Pesa payment is unavailable in test mode.'
                : (! $hasRequiredDetails ? 'Your landlord has not completed the M-Pesa payment setup. Contact your property manager in Chat.' : null),
        ]);
    }

    public function pay(Request $request, MpesaService $mpesa)
    {
        abort_unless($this->isTenant($request->user()), 403, 'Only tenant accounts can initiate invoice payments.');
        $request->merge([
            'invoice_id' => $request->input('invoice_id', $request->input('invoiceId')),
            'invoice_ids' => $request->input('invoice_ids', $request->input('invoiceIds')),
            'phone_number' => $request->input('phone_number', $request->input('phoneNumber')),
        ]);
        $data = $request->validate([
            'invoice_id' => 'nullable|required_without:invoice_ids|uuid|exists:invoices,id',
            'invoice_ids' => 'nullable|required_without:invoice_id|array|min:1',
            'invoice_ids.*' => 'required|uuid|distinct|exists:invoices,id',
            'phone_number' => ['required', 'string', 'regex:/^(?:254|\\+254|0)?[17]\\d{8}$/'],
            'amount' => 'nullable|numeric|min:1',
        ]);

        abort_unless(
            $mpesa->isConfigured(),
            503,
            'M-Pesa STK payments are temporarily unavailable because the platform setup is incomplete. Use the manual payment instructions or contact support.'
        );

        $invoiceIds = collect($data['invoice_ids'] ?? [])->filter()->unique()->values();
        if ($invoiceIds->isEmpty() && filled($data['invoice_id'] ?? null)) {
            $invoiceIds->push($data['invoice_id']);
        }
        $invoices = Invoice::with('unit.property.landlord')->whereIn('id', $invoiceIds)->get()->keyBy('id');
        abort_if($invoices->count() !== $invoiceIds->count(), 422, 'One or more selected invoices are unavailable.');

        $orderedInvoices = $invoiceIds->map(fn ($id) => $invoices->get($id));
        foreach ($orderedInvoices as $selectedInvoice) {
            abort_if($this->isTenant($request->user()) && $selectedInvoice->tenant_id !== $request->user()->id, 403);
            abort_if(
                $this->isTenant($request->user()) && ! $this->hasActiveTenancy($request->user()->id, $selectedInvoice->unit_id),
                422,
                'An invoice belongs to an inactive tenancy.'
            );
            abort_if(
                strtoupper(trim((string) $selectedInvoice->status)) === 'CANCELLED',
                422,
                'A cancelled invoice cannot be paid.'
            );
        }

        // Monetary fields are authoritative. Historical data can contain a
        // stale PAID status while paid_amount is still below total_amount.
        // Such an invoice remains payable; genuinely settled invoices are
        // simply removed from a combined selection.
        $orderedInvoices = $orderedInvoices
            ->filter(fn ($item) => round(
                max(0, (float) $item->total_amount - (float) $item->paid_amount),
                2
            ) > 0)
            ->values();
        abort_if($orderedInvoices->isEmpty(), 422, 'The selected invoice is already fully paid.');

        abort_if(
            $orderedInvoices->pluck('unit.property.landlord_id')->filter()->unique()->count() > 1,
            422,
            'Bills managed by different landlords must be paid separately.'
        );

        $balances = $orderedInvoices->mapWithKeys(fn ($item) => [
            $item->id => round(max(0, (float) $item->total_amount - (float) $item->paid_amount), 2),
        ]);
        $totalBalance = round((float) $balances->sum(), 2);
        $amount = isset($data['amount']) ? round((float) $data['amount'], 2) : $totalBalance;
        abort_if($totalBalance <= 0 || $amount > $totalBalance, 422, 'Payment amount exceeds the selected invoice balance.');

        $remaining = $amount;
        $allocations = [];
        foreach ($orderedInvoices as $selectedInvoice) {
            if ($remaining <= 0) {
                break;
            }
            $allocated = min($remaining, (float) $balances[$selectedInvoice->id]);
            if ($allocated > 0) {
                $allocations[] = ['invoice_id' => $selectedInvoice->id, 'amount' => round($allocated, 2)];
            }
            $remaining = round($remaining - $allocated, 2);
        }
        $invoice = $orderedInvoices->first();
        $landlordSettings = $this->landlordPaymentSettingsOrFail($invoice);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => 'MPESA',
            'payment_phone' => $data['phone_number'],
            'status' => 'PENDING',
            'paid_at' => null,
            'metadata' => ['invoice_allocations' => $allocations],
        ]);

        if ($mpesa->simulationEnabled()) {
            $checkoutId = 'SIM-'.strtoupper((string) Str::uuid());
            $receipt = 'SIM'.strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 10));

            DB::transaction(function () use ($payment, $checkoutId, $receipt) {
                $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

                $lockedPayment->update([
                    'status' => 'SUCCESSFUL',
                    'checkout_request_id' => $checkoutId,
                    'reference' => $checkoutId,
                    'mpesa_receipt' => $receipt,
                    'paid_at' => now(),
                ]);
                $lockedPayment->transactions()->create([
                    'amount' => $lockedPayment->amount,
                    'type' => 'MPESA_SIMULATION',
                    'description' => 'Sandbox simulation receipt '.$receipt,
                    'external_reference' => $receipt,
                    'checkout_request_id' => $checkoutId,
                    'merchant_request_id' => $checkoutId,
                    'processed_at' => now(),
                    'raw_payload' => [
                        'simulated' => true,
                        'receipt' => $receipt,
                        'checkout_request_id' => $checkoutId,
                    ],
                ]);

                $this->applyPaymentToInvoices($lockedPayment);
            });

            $this->sendPaymentNotifications($payment->id);

            return response()->json([
                'message' => 'Sandbox payment simulation completed successfully.',
                'paymentId' => $payment->id,
                'checkoutRequestId' => $checkoutId,
                'customerMessage' => 'Simulated payment completed.',
                'simulated' => true,
            ]);
        }

        try {
            $result = $mpesa->stkPush(
                $data['phone_number'],
                $amount,
                $invoice->id,
                'Starmax Tenant Services invoice payment',
                $landlordSettings
            );
            $payment->update([
                'checkout_request_id' => $result['CheckoutRequestID'],
                'reference' => $result['MerchantRequestID'] ?? null,
            ]);
            $payment->transactions()->create([
                'amount' => $payment->amount,
                'type' => 'MPESA_REQUEST',
                'description' => $result['ResponseDescription'] ?? 'STK Push accepted by M-Pesa.',
                'checkout_request_id' => $result['CheckoutRequestID'],
                'merchant_request_id' => $result['MerchantRequestID'] ?? null,
                'processed_at' => now(),
                'raw_payload' => $result,
            ]);

            return response()->json([
                'message' => $result['CustomerMessage'] ?? 'STK Push sent. Check your phone.',
                'paymentId' => $payment->id,
                'checkoutRequestId' => $result['CheckoutRequestID'],
                'merchantRequestId' => $result['MerchantRequestID'] ?? null,
                'customerMessage' => $result['CustomerMessage'] ?? null,
            ]);
        } catch (\Throwable $error) {
            $payment->update(['status' => 'FAILED']);
            Log::error('M-Pesa STK Push failed', ['payment_id' => $payment->id, 'error' => $error->getMessage()]);

            return response()->json(['message' => $error->getMessage()], 502);
        }
    }

    public function mpesaCallback(Request $request)
    {
        $callback = $request->input('Body.stkCallback');
        if (! is_array($callback) || empty($callback['CheckoutRequestID']) || ! array_key_exists('ResultCode', $callback)) {
            return response()->json(['message' => 'Invalid M-Pesa callback payload.'], 400);
        }

        $notifyPaymentId = DB::transaction(function () use ($callback) {
            $payment = Payment::where('checkout_request_id', $callback['CheckoutRequestID'])
                ->lockForUpdate()
                ->first();
            if (! $payment || $payment->status === 'SUCCESSFUL') {
                return null;
            }

            $resultCode = (int) $callback['ResultCode'];
            if ($resultCode !== 0) {
                $payment->update(['status' => match ($resultCode) {
                    1032 => 'CANCELLED',
                    1037 => 'EXPIRED',
                    default => 'FAILED',
                }]);
                $payment->transactions()->create([
                    'amount' => $payment->amount,
                    'type' => 'MPESA_CALLBACK',
                    'description' => substr((string) ($callback['ResultDesc'] ?? 'Payment failed'), 0, 255),
                    'checkout_request_id' => $callback['CheckoutRequestID'],
                    'merchant_request_id' => $callback['MerchantRequestID'] ?? null,
                    'processed_at' => now(),
                    'raw_payload' => $callback,
                ]);

                return null;
            }

            $metadata = collect(data_get($callback, 'CallbackMetadata.Item', []))
                ->filter(fn ($item) => isset($item['Name']))
                ->mapWithKeys(fn ($item) => [$item['Name'] => $item['Value'] ?? null]);
            $receipt = (string) $metadata->get('MpesaReceiptNumber', '');
            if ($receipt === '') {
                $payment->transactions()->create([
                    'amount' => $payment->amount,
                    'type' => 'MPESA_CALLBACK_INVALID',
                    'description' => 'Successful callback did not include an M-Pesa receipt.',
                    'checkout_request_id' => $callback['CheckoutRequestID'],
                    'merchant_request_id' => $callback['MerchantRequestID'] ?? null,
                    'processed_at' => now(),
                    'raw_payload' => $callback,
                ]);
                Log::warning('Successful M-Pesa callback had no receipt', ['payment_id' => $payment->id]);

                return null;
            }

            $callbackAmount = round((float) $metadata->get('Amount', 0), 2);
            if (abs($callbackAmount - round((float) $payment->amount, 2)) > 0.009) {
                $payment->update(['status' => 'FAILED']);
                $payment->transactions()->create([
                    'amount' => $callbackAmount,
                    'type' => 'MPESA_AMOUNT_MISMATCH',
                    'description' => 'Callback amount did not match the initiated payment.',
                    'external_reference' => $receipt,
                    'checkout_request_id' => $callback['CheckoutRequestID'],
                    'merchant_request_id' => $callback['MerchantRequestID'] ?? null,
                    'processed_at' => now(),
                    'raw_payload' => $callback,
                ]);
                Log::warning('M-Pesa callback amount mismatch', [
                    'payment_id' => $payment->id,
                    'expected' => (float) $payment->amount,
                    'received' => $callbackAmount,
                ]);

                return null;
            }

            if (Payment::where('mpesa_receipt', $receipt)->whereKeyNot($payment->id)->exists()) {
                $payment->update(['status' => 'FAILED']);
                $payment->transactions()->create([
                    'amount' => $payment->amount,
                    'type' => 'MPESA_DUPLICATE_RECEIPT',
                    'description' => 'Duplicate M-Pesa receipt rejected.',
                    'external_reference' => $receipt,
                    'checkout_request_id' => $callback['CheckoutRequestID'],
                    'merchant_request_id' => $callback['MerchantRequestID'] ?? null,
                    'processed_at' => now(),
                    'raw_payload' => $callback,
                ]);
                Log::warning('Duplicate M-Pesa receipt rejected', ['payment_id' => $payment->id]);

                return null;
            }

            $payment->update([
                'status' => 'SUCCESSFUL',
                'mpesa_receipt' => $receipt,
                'paid_at' => now(),
            ]);
            $payment->transactions()->create([
                'amount' => $payment->amount,
                'type' => 'MPESA_CALLBACK',
                'description' => 'M-Pesa receipt '.$receipt,
                'external_reference' => $receipt,
                'checkout_request_id' => $callback['CheckoutRequestID'],
                'merchant_request_id' => $callback['MerchantRequestID'] ?? null,
                'processed_at' => now(),
                'raw_payload' => $callback,
            ]);

            $this->applyPaymentToInvoices($payment);

            return $payment->id;
        });

        if ($notifyPaymentId) {
            $this->sendPaymentNotifications($notifyPaymentId);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }

    private function hasActiveTenancy(string $userId, string $unitId): bool
    {
        return Tenant::where('user_id', $userId)
            ->where('unit_id', $unitId)
            ->where('is_active', true)
            ->exists();
    }

    private function landlordPaymentSettingsOrFail(Invoice $invoice): array
    {
        $landlord = $invoice->unit?->property?->landlord;
        $settings = $landlord && is_array($landlord->app_settings)
            ? ($landlord->app_settings['paymentSettings'] ?? [])
            : [];
        abort_if(! is_array($settings) || $settings === [], 422, 'Your landlord has not configured M-Pesa payments yet.');

        $type = strtoupper(trim((string) ($settings['payment_type'] ?? '')));
        abort_if(! in_array($type, ['PAYBILL', 'TILL'], true), 422, 'Your landlord must select a Paybill or Till payment method.');

        if ($type === 'TILL') {
            abort_if(trim((string) ($settings['till_number'] ?? '')) === '', 422, 'Your landlord must configure a Till number before STK payments can be used.');
        } else {
            abort_if(trim((string) ($settings['paybill_number'] ?? '')) === '', 422, 'Your landlord must configure a Paybill number before STK payments can be used.');
            $reference = trim((string) ($settings['account_reference'] ?? ''));
            abort_if($reference === '' || in_array(strtolower($reference), ['tenant pro', 'starmax tenant services'], true), 422, 'Your landlord must configure the Paybill account number before STK payments can be used.');
        }

        return $settings;
    }

    private function applyPaymentToInvoices(Payment $payment): void
    {
        $allocations = collect($payment->metadata['invoice_allocations'] ?? [
            ['invoice_id' => $payment->invoice_id, 'amount' => (float) $payment->amount],
        ]);

        foreach ($allocations as $allocation) {
            $invoice = Invoice::whereKey($allocation['invoice_id'])->lockForUpdate()->firstOrFail();
            $invoice->paid_amount = min(
                (float) $invoice->total_amount,
                (float) $invoice->paid_amount + (float) $allocation['amount']
            );
            $invoice->status = (float) $invoice->paid_amount >= (float) $invoice->total_amount ? 'PAID' : 'PARTIAL';
            $invoice->paid_at = $invoice->status === 'PAID' ? now() : null;
            $invoice->save();
        }
    }

    private function sendPaymentNotifications(string $paymentId): void
    {
        $payment = Payment::with('invoice.tenant', 'invoice.unit.property')->find($paymentId);
        if (! $payment) {
            return;
        }

        try {
            app(TenantEmailService::class)->paymentReceived($payment);
            app(TenantAppNotificationService::class)->paymentReceived($payment);
        } catch (\Throwable $error) {
            Log::error('Payment notification failed', [
                'payment_id' => $paymentId,
                'error' => $error->getMessage(),
            ]);
        }
    }
}
