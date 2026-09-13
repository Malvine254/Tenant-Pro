<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyExpenseAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';
        $landlordId = $user->landlordAccountId();

        $selectedPropertyId = $request->query('property_id');
        $selectedYear = (int) $request->query('year', now()->year);
        $selectedMonth = $request->query('month');

        $properties = Property::query()
            ->when($isLandlord, fn ($q) => $q->where('landlord_id', $landlordId))
            ->orderBy('name')
            ->get(['id', 'name', 'landlord_id']);

        $expensesQuery = PropertyExpense::with(['property', 'unit', 'recordedBy'])
            ->when($isLandlord, fn ($q) => $q->whereHas('property', fn ($p) => $p->where('landlord_id', $landlordId)))
            ->when($selectedPropertyId, fn ($q) => $q->where('property_id', $selectedPropertyId))
            ->when($selectedYear, fn ($q) => $q->whereYear('expense_date', $selectedYear))
            ->when($selectedMonth, fn ($q) => $q->whereMonth('expense_date', $selectedMonth));

        $paymentsQuery = Payment::query()
            ->where('status', 'SUCCESSFUL')
            ->when($isLandlord, fn ($q) => $q->whereHas('invoice.unit.property', fn ($p) => $p->where('landlord_id', $landlordId)))
            ->when($selectedPropertyId, fn ($q) => $q->whereHas('invoice.unit', fn ($u) => $u->where('property_id', $selectedPropertyId)))
            ->when($selectedYear, fn ($q) => $q->whereYear('paid_at', $selectedYear))
            ->when($selectedMonth, fn ($q) => $q->whereMonth('paid_at', $selectedMonth));

        $totalRevenue = (float) $paymentsQuery->sum('amount');
        $totalExpenses = (float) (clone $expensesQuery)->sum('amount');
        $netOperatingIncome = $totalRevenue - $totalExpenses;
        $kraMriTaxEstimate = max(0, $totalRevenue * 0.075); // KRA 7.5% gross residential rent tax

        // Category breakdown
        $expensesByCategory = (clone $expensesQuery)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $expenses = $expensesQuery
            ->orderByDesc('expense_date')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.financials.index', compact(
            'expenses',
            'properties',
            'totalRevenue',
            'totalExpenses',
            'netOperatingIncome',
            'kraMriTaxEstimate',
            'expensesByCategory',
            'selectedPropertyId',
            'selectedYear',
            'selectedMonth'
        ));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';
        $landlordId = $user->landlordAccountId();

        $data = $request->validate([
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'nullable|uuid|exists:units,id',
            'category' => 'required|in:MAINTENANCE,UTILITIES,REPAIR,COUNTY_RATES,SALARY,SECURITY,OTHER',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date',
            'receipt_photo' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($isLandlord) {
            $property = Property::where('id', $data['property_id'])->where('landlord_id', $landlordId)->firstOrFail();
        }

        $receiptPath = null;
        if ($request->hasFile('receipt_photo')) {
            $receiptPath = $request->file('receipt_photo')->store('expenses', 'public');
        }

        PropertyExpense::create([
            'property_id' => $data['property_id'],
            'unit_id' => $data['unit_id'] ?? null,
            'category' => $data['category'],
            'title' => $data['title'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'receipt_photo_path' => $receiptPath,
            'notes' => $data['notes'] ?? null,
            'recorded_by_id' => $user->id,
        ]);

        return back()->with('success', 'Expense recorded successfully.');
    }

    public function destroy(PropertyExpense $expense, Request $request)
    {
        $user = $request->user();
        if ($user?->role?->name === 'LANDLORD') {
            abort_if($expense->property?->landlord_id !== $user->landlordAccountId(), 403);
        }

        if ($expense->receipt_photo_path) {
            Storage::disk('public')->delete($expense->receipt_photo_path);
        }

        $expense->delete();
        return back()->with('success', 'Expense record removed.');
    }
}
