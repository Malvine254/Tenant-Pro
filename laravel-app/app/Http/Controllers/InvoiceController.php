<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['tenant', 'unit.property'])
            ->when($request->tenant_id, fn($q) => $q->where('tenant_id', $request->tenant_id))
            ->when($request->unit_id, fn($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status));
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required|uuid|exists:users,id',
            'user_id' => 'required|uuid|exists:users,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'billing_type' => 'required|string',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2000',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'amount' => 'required|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);
        $data['paid_amount'] = 0;
        $data['status'] = 'PENDING';
        return response()->json(Invoice::create($data)->load(['tenant', 'unit']), 201);
    }

    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load(['tenant', 'unit.property', 'payments']));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:PENDING,PARTIAL,PAID,OVERDUE,CANCELLED',
            'paid_amount' => 'sometimes|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'paid_at' => 'nullable|date',
        ]);
        $invoice->update($data);
        return response()->json($invoice->load(['tenant', 'unit']));
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return response()->json(null, 204);
    }
}
