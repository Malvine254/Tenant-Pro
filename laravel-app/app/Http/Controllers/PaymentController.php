<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
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

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }
}
