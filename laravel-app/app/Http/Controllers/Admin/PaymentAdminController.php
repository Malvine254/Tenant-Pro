<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $payments = Payment::query()
            ->with(['invoice.tenant', 'invoice.unit.property'])
            ->when(
                $user?->role?->name === 'LANDLORD',
                fn ($query) => $query->whereHas(
                    'invoice.unit.property',
                    fn ($property) => $property->where('landlord_id', $user->landlordAccountId())
                )
            )
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.trim((string) $request->input('search')).'%';
                $query->where(function ($match) use ($search) {
                    $match->where('mpesa_receipt', 'like', $search)
                        ->orWhere('payment_phone', 'like', $search)
                        ->orWhere('reference', 'like', $search)
                        ->orWhereHas('invoice.tenant', fn ($tenant) => $tenant->where('name', 'like', $search));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }
}
