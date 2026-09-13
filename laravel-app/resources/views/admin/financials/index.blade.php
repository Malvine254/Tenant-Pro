@extends('admin.layout')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>Financials &amp; Expense Tracker</h2>
        <p>Monitor gross rental collections, property expenses, Net Operating Income (NOI), and estimated KRA Residential Rental Income Tax.</p>
    </div>
    <div class="admin-actions">
        <button type="button" class="btn btn-primary" onclick="document.getElementById('modalNewExpense').style.display='flex'">
            + Record Expense
        </button>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="metric-row" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <div class="metric-card" style="border-top: 3px solid #10b981;">
        <span>Gross Rent Collected</span>
        <div style="font-size: 22px; font-weight: 800; color: #10b981;">KSh {{ number_format($totalRevenue, 2) }}</div>
        <small class="muted" style="font-size: 11px; margin-top: 4px; display: block;">Settled via M-Pesa &amp; verified receipts</small>
    </div>
    <div class="metric-card" style="border-top: 3px solid #ef4444;">
        <span>Total Expenses</span>
        <div style="font-size: 22px; font-weight: 800; color: #ef4444;">KSh {{ number_format($totalExpenses, 2) }}</div>
        <small class="muted" style="font-size: 11px; margin-top: 4px; display: block;">Repairs, utilities, rates, salaries</small>
    </div>
    <div class="metric-card" style="border-top: 3px solid #3b82f6;">
        <span>Net Operating Income (NOI)</span>
        <div style="font-size: 22px; font-weight: 800; color: {{ $netOperatingIncome >= 0 ? '#60a5fa' : '#f87171' }};">
            KSh {{ number_format($netOperatingIncome, 2) }}
        </div>
        <small class="muted" style="font-size: 11px; margin-top: 4px; display: block;">Revenue minus operating costs</small>
    </div>
    <div class="metric-card" style="border-top: 3px solid #f59e0b;">
        <span>KRA Rental Tax (7.5% MRI)</span>
        <div style="font-size: 22px; font-weight: 800; color: #fbbf24;">KSh {{ number_format($kraMriTaxEstimate, 2) }}</div>
        <small class="muted" style="font-size: 11px; margin-top: 4px; display: block;">Estimated gross monthly tax liability</small>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px; padding: 14px;">
    <form method="GET" action="{{ route('admin.financials.index') }}" class="admin-filter">
        <select name="property_id" onchange="this.form.submit()">
            <option value="">All Properties</option>
            @foreach($properties as $prop)
                <option value="{{ $prop->id }}" {{ $selectedPropertyId == $prop->id ? 'selected' : '' }}>{{ $prop->name }}</option>
            @endforeach
        </select>
        <select name="year" onchange="this.form.submit()">
            @for($y = now()->year; $y >= 2024; $y--)
                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <select name="month" onchange="this.form.submit()">
            <option value="">All Months</option>
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
            @endfor
        </select>
        @if($selectedPropertyId || $selectedMonth || $selectedYear != now()->year)
            <a href="{{ route('admin.financials.index') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>
</div>

<!-- Expenses Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h3 style="font-size: 15px; font-weight: 800; color: var(--text);">Recorded Expenses &amp; Outflows</h3>
        <span class="badge badge-gray">{{ $expenses->total() }} Records</span>
    </div>

    @if($expenses->isEmpty())
        <div class="empty-state">
            <strong>No expenses logged yet</strong>
            <span>Click "+ Record Expense" to track maintenance costs, county rates, caretaker wages, and utility bills.</span>
        </div>
    @else
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Property / Unit</th>
                        <th>Category</th>
                        <th>Description / Title</th>
                        <th>Amount</th>
                        <th>Receipt</th>
                        <th>Logged By</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $exp)
                    <tr>
                        <td style="white-space: nowrap; font-weight: 700;">{{ $exp->expense_date?->format('d M Y') }}</td>
                        <td>
                            <strong>{{ $exp->property?->name ?? '-' }}</strong>
                            @if($exp->unit)
                                <div style="font-size: 11px; color: var(--muted);">Unit {{ $exp->unit->unit_number }}</div>
                            @endif
                        </td>
                        <td><span class="badge badge-blue">{{ $exp->category_label }}</span></td>
                        <td>
                            <div style="font-weight: 700; color: #f8fafc;">{{ $exp->title }}</div>
                            @if($exp->notes)
                                <small class="muted" style="font-size: 11px;">{{ $exp->notes }}</small>
                            @endif
                        </td>
                        <td style="font-weight: 800; color: #f87171; white-space: nowrap;">{{ $exp->amount_formatted }}</td>
                        <td>
                            @if($exp->receipt_photo_path)
                                <a href="{{ asset('storage/' . $exp->receipt_photo_path) }}" target="_blank" class="btn btn-secondary" style="min-height: 28px; padding: 3px 8px; font-size: 11px;">
                                    View Receipt
                                </a>
                            @else
                                <span class="muted" style="font-size: 11px;">No photo</span>
                            @endif
                        </td>
                        <td class="muted" style="font-size: 11px;">{{ $exp->recordedBy?->name ?? 'Admin' }}</td>
                        <td style="text-align: right;">
                            <form method="POST" action="{{ route('admin.expenses.destroy', $exp) }}" onsubmit="return confirm('Delete this expense record?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="min-height: 28px; padding: 3px 8px; font-size: 11px;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $expenses->links() }}</div>
    @endif
</div>

<!-- Modal: Record Expense -->
<div id="modalNewExpense" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #0f172a; border: 1px solid rgba(148,163,184,0.25); border-radius: 16px; width: 100%; max-width: 520px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 17px; font-weight: 800; color: #f8fafc; margin: 0;">Record Property Expense</h3>
            <button type="button" onclick="document.getElementById('modalNewExpense').style.display='none'" style="background: transparent; border: none; color: #94a3b8; font-size: 20px; cursor: pointer;">×</button>
        </div>
        <form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Property <span style="color:#f87171;">*</span></label>
                <select name="property_id" required>
                    <option value="">Select property</option>
                    @foreach($properties as $prop)
                        <option value="{{ $prop->id }}">{{ $prop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Category <span style="color:#f87171;">*</span></label>
                    <select name="category" required>
                        <option value="MAINTENANCE">Maintenance &amp; Repairs</option>
                        <option value="UTILITIES">Bulk Utilities (Water/Power)</option>
                        <option value="REPAIR">Structural Repairs</option>
                        <option value="COUNTY_RATES">County Land Rates</option>
                        <option value="SALARY">Caretaker / Staff Salary</option>
                        <option value="SECURITY">Security &amp; Guarding</option>
                        <option value="OTHER">Other Expense</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Expense Date <span style="color:#f87171;">*</span></label>
                    <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required />
                </div>
            </div>
            <div class="form-group">
                <label>Description / Expense Title <span style="color:#f87171;">*</span></label>
                <input type="text" name="title" placeholder="e.g. Plumber pipe repair, March bulk water bill" required />
            </div>
            <div class="form-group">
                <label>Amount (KSh) <span style="color:#f87171;">*</span></label>
                <input type="number" step="0.01" name="amount" placeholder="e.g. 4500.00" required />
            </div>
            <div class="form-group">
                <label>Receipt Photo / Invoice Document (Optional)</label>
                <input type="file" name="receipt_photo" accept="image/*" />
            </div>
            <div class="form-group">
                <label>Additional Notes</label>
                <textarea name="notes" rows="2" placeholder="Notes, vendor contact, voucher reference..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalNewExpense').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Expense</button>
            </div>
        </form>
    </div>
</div>
@endsection
