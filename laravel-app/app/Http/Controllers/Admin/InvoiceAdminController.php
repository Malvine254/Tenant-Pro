<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class InvoiceAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $invoices = Invoice::with(['tenant', 'unit.property'])
            ->when($user?->role?->name === 'LANDLORD', fn($q) => $q->whereHas('unit.property', fn($property) => $property->where('landlord_id', $user->landlordAccountId())))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(15);
        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $user = request()->user();
        abort_if($user?->role?->name === 'LANDLORD' && $invoice->unit?->property?->landlord_id !== $user->landlordAccountId(), 403);

        $invoice->load(['tenant', 'unit.property', 'payments']);
        return view('admin.invoices.show', compact('invoice'));
    }
}
