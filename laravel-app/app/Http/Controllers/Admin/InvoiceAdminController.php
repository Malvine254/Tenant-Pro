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
        $invoices = Invoice::with(['tenant', 'unit.property'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate(15);
        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['tenant', 'unit.property', 'payments']);
        return view('admin.invoices.show', compact('invoice'));
    }
}
