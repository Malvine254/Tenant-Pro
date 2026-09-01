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
        $filters = $request->validate([
            'search' => 'nullable|string|max:120',
            'status' => 'nullable|in:SUCCESSFUL,PENDING,FAILED,CANCELLED,EXPIRED,REVERSED',
            'method' => 'nullable|string|max:50',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $baseQuery = Payment::query()
            ->with(['invoice.tenant', 'invoice.unit.property'])
            ->when(
                $user?->role?->name === 'LANDLORD',
                fn ($query) => $query->whereHas(
                    'invoice.unit.property',
                    fn ($property) => $property->where('landlord_id', $user->landlordAccountId())
                )
            );

        $paymentMethods = (clone $baseQuery)
            ->whereNotNull('method')
            ->where('method', '!=', '')
            ->distinct()
            ->orderBy('method')
            ->pluck('method');

        $filteredQuery = (clone $baseQuery)
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['method']), fn ($query) => $query->where('method', $filters['method']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('paid_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('paid_at', '<=', $filters['date_to']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = '%'.trim($filters['search']).'%';
                $query->where(function ($match) use ($search) {
                    $match->where('mpesa_receipt', 'like', $search)
                        ->orWhere('payment_phone', 'like', $search)
                        ->orWhere('reference', 'like', $search)
                        ->orWhere('checkout_request_id', 'like', $search)
                        ->orWhereHas('invoice.tenant', fn ($tenant) => $tenant->where('name', 'like', $search));
                });
            });

        $paymentSummary = [
            'total' => (clone $filteredQuery)->count(),
            'successful_amount' => (float) (clone $filteredQuery)->where('status', 'SUCCESSFUL')->sum('amount'),
            'pending' => (clone $filteredQuery)->where('status', 'PENDING')->count(),
        ];

        $payments = $filteredQuery
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments.index', compact('payments', 'paymentMethods', 'paymentSummary'));
    }
}
