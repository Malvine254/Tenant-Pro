<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Services\TenantEmailService;
use Illuminate\Http\Request;

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
            'reference' => 'nullable|string|unique:payments,reference',
        ]);
        abort_if($this->isTenant($user) && !Invoice::where('id', $data['invoice_id'])->where('tenant_id', $user->id)->exists(), 403);

        $payment = Payment::create($data);

        // Update invoice paid_amount and status
        $invoice = Invoice::find($data['invoice_id']);
        $invoice->paid_amount = $invoice->paid_amount + $data['amount'];
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $invoice->status = 'PAID';
            $invoice->paid_at = now();
        } else {
            $invoice->status = 'PARTIAL';
        }
        $invoice->save();

        app(TenantEmailService::class)->paymentReceived($payment->load('invoice.tenant', 'invoice.unit.property'));

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
        return response()->json($invoice->payments()->with('transactions')->latest()->get());
    }

    public function pay(Request $request)
    {
        $request->merge([
            'invoice_id' => $request->input('invoice_id', $request->input('invoiceId')),
            'phone_number' => $request->input('phone_number', $request->input('phoneNumber')),
        ]);
        $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'phone_number' => 'required|string',
            'amount' => 'nullable|numeric|min:1',
        ]);

        return response()->json([
            'message' => 'M-Pesa STK Push is not configured on this Laravel server yet.',
        ], 503);
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }
}
