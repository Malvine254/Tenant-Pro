<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\TenantAppNotificationService;
use App\Services\TenantEmailService;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::with(['invoice.tenant', 'invoice.unit'])
            ->when($this->isTenant($user), fn($q) => $q->whereHas('invoice', fn($invoice) => $invoice->where('tenant_id', $user->id)))
            ->when($request->invoice_id, fn($q) => $q->where('invoice_id', $request->invoice_id));
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|string|max:50',
            'payment_phone' => 'nullable|string|max:30',
            'mpesa_receipt' => 'nullable|string|max:80',
            'status' => 'nullable|in:PENDING,SUCCESSFUL,FAILED,CANCELLED,EXPIRED,REVERSED',
            'reference' => 'nullable|string|unique:payments,reference',
        ]);
        abort_if($this->isTenant($user) && !Invoice::where('id', $data['invoice_id'])->where('tenant_id', $user->id)->exists(), 403);

        $data['status'] = $data['status'] ?? 'SUCCESSFUL';
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

        return response()->json($payment->load(['invoice.tenant', 'invoice.unit', 'transactions']));
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->json(null, 204);
    }

    public function forInvoice(Request $request, Invoice $invoice)
    {
        abort_if($this->isTenant($request->user()) && $invoice->tenant_id !== $request->user()->id, 403);
        abort_if(
            $this->isTenant($request->user()) && ! $this->hasActiveTenancy($request->user()->id, $invoice->unit_id),
            404
        );
        return response()->json($invoice->payments()->with('transactions')->latest()->get());
    }

    public function pay(Request $request, MpesaService $mpesa)
    {
        $request->merge([
            'invoice_id' => $request->input('invoice_id', $request->input('invoiceId')),
            'phone_number' => $request->input('phone_number', $request->input('phoneNumber')),
        ]);
        $data = $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'phone_number' => ['required', 'string', 'regex:/^(?:254|\\+254|0)?[17]\\d{8}$/'],
            'amount' => 'nullable|numeric|min:1',
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        abort_if($this->isTenant($request->user()) && $invoice->tenant_id !== $request->user()->id, 403);
        abort_if(
            $this->isTenant($request->user()) && ! $this->hasActiveTenancy($request->user()->id, $invoice->unit_id),
            422,
            'This invoice belongs to an inactive tenancy.'
        );
        abort_if(in_array($invoice->status, ['PAID', 'CANCELLED'], true), 422, 'This invoice cannot be paid.');

        $balance = round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2);
        $amount = isset($data['amount']) ? round((float) $data['amount'], 2) : $balance;
        abort_if($balance <= 0 || $amount > $balance, 422, 'Payment amount exceeds the invoice balance.');

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => 'MPESA',
            'payment_phone' => $data['phone_number'],
            'status' => 'PENDING',
            'paid_at' => null,
        ]);

        try {
            $result = $mpesa->stkPush(
                $data['phone_number'],
                $amount,
                $invoice->id,
                'Tenant Pro invoice payment'
            );
            $payment->update([
                'checkout_request_id' => $result['CheckoutRequestID'],
                'reference' => $result['MerchantRequestID'] ?? null,
            ]);

            return response()->json([
                'message' => $result['CustomerMessage'] ?? 'STK Push sent. Check your phone.',
                'paymentId' => $payment->id,
                'checkoutRequestId' => $result['CheckoutRequestID'],
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
                ]);
                return null;
            }

            $metadata = collect(data_get($callback, 'CallbackMetadata.Item', []))
                ->filter(fn ($item) => isset($item['Name']))
                ->mapWithKeys(fn ($item) => [$item['Name'] => $item['Value'] ?? null]);
            $receipt = (string) $metadata->get('MpesaReceiptNumber', '');
            if ($receipt === '') {
                Log::warning('Successful M-Pesa callback had no receipt', ['payment_id' => $payment->id]);
                return null;
            }

            $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
            $payment->update([
                'status' => 'SUCCESSFUL',
                'mpesa_receipt' => $receipt,
                'paid_at' => now(),
            ]);
            $payment->transactions()->create([
                'amount' => $payment->amount,
                'type' => 'MPESA_CALLBACK',
                'description' => 'M-Pesa receipt '.$receipt,
            ]);

            $invoice->paid_amount = min((float) $invoice->total_amount, (float) $invoice->paid_amount + (float) $payment->amount);
            $invoice->status = (float) $invoice->paid_amount >= (float) $invoice->total_amount ? 'PAID' : 'PARTIAL';
            $invoice->paid_at = $invoice->status === 'PAID' ? now() : null;
            $invoice->save();

            return $payment->id;
        });

        if ($notifyPaymentId) {
            $payment = Payment::with('invoice.tenant', 'invoice.unit.property')->find($notifyPaymentId);
            try {
                app(TenantEmailService::class)->paymentReceived($payment);
                app(TenantAppNotificationService::class)->paymentReceived($payment);
            } catch (\Throwable $error) {
                // Payment settlement must remain successful even when a secondary
                // email or push provider is temporarily unavailable.
                Log::error('Payment notification failed', [
                    'payment_id' => $notifyPaymentId,
                    'error' => $error->getMessage(),
                ]);
            }
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
}
