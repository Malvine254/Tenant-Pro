@extends('admin.layout')
@section('page-title', 'Listing reports')
@section('content')
<div class="card"><h2>Listing accuracy reports</h2><p>Review the claim and check the listing before resolving it. Reports do not automatically hide homes.</p>
@forelse($reports as $report)
    <article style="border-top:1px solid #cbd5e1;padding:20px 0;">
        <h3>{{ $report->property->name }} · {{ \App\Models\ListingReport::REASONS[$report->reason] ?? $report->reason }}</h3>
        <p>{{ $report->created_at->format('j M Y, H:i') }} · {{ $report->status }}</p>
        <p style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $report->details }}</p>
        @if($report->email)<p>Reply contact: {{ $report->email }}</p>@endif
        <a href="{{ route('admin.properties.edit', $report->property) }}">Review property</a>
        <form method="POST" action="{{ route('admin.listing-reports.resolve', $report) }}" data-ajax-form style="margin-top:12px;">@csrf @method('PATCH')
            <input type="hidden" name="status" value="{{ $report->status === 'OPEN' ? 'RESOLVED' : 'OPEN' }}">
            <button class="btn btn-primary">{{ $report->status === 'OPEN' ? 'Mark reviewed and resolved' : 'Reopen report' }}</button>
        </form>
    </article>
@empty<p>No reports have been submitted.</p>@endforelse
{{ $reports->links() }}</div>
@endsection
