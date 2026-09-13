@extends('admin.layout')

@section('content')
<div class="admin-page-header">
    <div>
        <h2>{{ $inspection->type_label }} - Unit {{ $inspection->unit?->unit_number }}</h2>
        <p>{{ $inspection->property?->name }} • Inspected on {{ $inspection->inspection_date?->format('d M Y') }} by {{ $inspection->inspector_name }}</p>
    </div>
    <div class="admin-actions">
        <a href="{{ route('admin.inspections.index') }}" class="btn btn-secondary">Back to Inspections</a>
        <a href="{{ route('admin.inspections.pdf', $inspection) }}" target="_blank" class="btn btn-primary">
            Download PDF Certificate
        </a>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="section-heading">Premises &amp; Handover Details</div>
    <div class="form-grid" style="margin-bottom: 16px;">
        <div>
            <span class="muted" style="font-size: 11px;">PROPERTY</span>
            <div style="font-weight: 800; font-size: 14px; color: #f8fafc;">{{ $inspection->property?->name }}</div>
        </div>
        <div>
            <span class="muted" style="font-size: 11px;">UNIT</span>
            <div style="font-weight: 800; font-size: 14px; color: #f8fafc;">Unit {{ $inspection->unit?->unit_number }}</div>
        </div>
        <div>
            <span class="muted" style="font-size: 11px;">TENANT</span>
            <div style="font-weight: 800; font-size: 14px; color: #f8fafc;">{{ $inspection->tenant?->name ?? 'Unassigned' }}</div>
        </div>
        <div>
            <span class="muted" style="font-size: 11px;">INSPECTOR</span>
            <div style="font-weight: 800; font-size: 14px; color: #f8fafc;">{{ $inspection->inspector_name }}</div>
        </div>
    </div>

    <!-- Meter Readings -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 14px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.25); border-radius: 12px; margin-bottom: 20px;">
        <div>
            <span style="font-size: 12px; font-weight: 700; color: #93c5fd;">💧 Water Sub-Meter:</span>
            <strong style="font-size: 15px; color: #f8fafc; margin-left: 6px;">{{ $inspection->meter_reading_water !== null ? number_format((float) $inspection->meter_reading_water, 2) . ' Units' : 'Not Recorded' }}</strong>
        </div>
        <div>
            <span style="font-size: 12px; font-weight: 700; color: #93c5fd;">⚡ Electricity Balance:</span>
            <strong style="font-size: 15px; color: #f8fafc; margin-left: 6px;">{{ $inspection->meter_reading_electricity !== null ? number_format((float) $inspection->meter_reading_electricity, 2) . ' Units' : 'Not Recorded' }}</strong>
        </div>
    </div>

    <!-- Checklist Table -->
    <div class="section-heading">Room-by-Room Condition Status</div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Room / Area</th>
                    <th>Inspected Item</th>
                    <th>Condition</th>
                    <th>Notes / Observations</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inspection->checklist_data ?? [] as $item)
                <tr>
                    <td><strong>{{ $item['category'] ?? '-' }}</strong></td>
                    <td>{{ $item['item_name'] ?? '-' }}</td>
                    <td>
                        @php $c = strtoupper($item['condition'] ?? 'GOOD'); @endphp
                        <span class="badge {{ $c === 'GOOD' ? 'badge-green' : ($c === 'FAIR' ? 'badge-yellow' : 'badge-red') }}">
                            {{ $c }}
                        </span>
                    </td>
                    <td class="muted">{{ $item['notes'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($inspection->general_notes)
    <div style="margin-top: 18px; padding: 14px; background: rgba(15,23,42,0.6); border: 1px solid rgba(148,163,184,0.14); border-radius: 10px;">
        <span class="muted" style="font-size: 11px; display: block; margin-bottom: 4px;">GENERAL NOTES</span>
        <div style="color: #cbd5e1; font-size: 13px;">{{ $inspection->general_notes }}</div>
    </div>
    @endif
</div>
@endsection
