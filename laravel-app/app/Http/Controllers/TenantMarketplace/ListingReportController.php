<?php

namespace App\Http\Controllers\TenantMarketplace;

use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingReportController extends Controller
{
    public function store(Request $request, Property $property)
    {
        abort_unless(Property::query()->publiclyAvailable()->whereKey($property->id)->exists(), 404);
        $data = $request->validateWithBag('report', [
            'reason' => ['required', Rule::in(array_keys(ListingReport::REASONS))],
            'details' => 'required|string|min:10|max:2000',
            'report_email' => 'nullable|email|max:255',
            'report_website' => 'nullable|string|max:0',
        ]);
        ListingReport::create(['property_id' => $property->id, 'reason' => $data['reason'], 'details' => $data['details'], 'email' => $data['report_email'] ?? null]);
        return redirect()->to(route('marketplace.show', $property).'#report-listing')->with('report_success', 'Your report has been received for review. Thank you for helping keep listing details accurate.');
    }

    public function index()
    {
        $reports = ListingReport::with('property')->orderByRaw("CASE WHEN status = 'OPEN' THEN 0 ELSE 1 END")->latest()->paginate(25);
        return view('admin.listing-reports.index', compact('reports'));
    }

    public function resolve(Request $request, ListingReport $report)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['OPEN', 'RESOLVED'])]]);
        $report->update(['status' => $data['status'], 'resolved_at' => $data['status'] === 'RESOLVED' ? now() : null, 'resolved_by' => $data['status'] === 'RESOLVED' ? $request->user()->id : null]);
        return back()->with('success', 'Report review status updated.');
    }
}
