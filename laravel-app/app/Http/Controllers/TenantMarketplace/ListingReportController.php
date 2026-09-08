<?php

namespace App\Http\Controllers\TenantMarketplace;

use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

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
        $report = ListingReport::create(['property_id' => $property->id, 'reason' => $data['reason'], 'details' => $data['details'], 'email' => $data['report_email'] ?? null]);
        try {
            $subject = 'New marketplace listing report: '.$property->name;
            Mail::send('emails.tenant-pro-update', [
                'subjectLine' => $subject,
                'preheader' => 'A public marketplace listing was reported for review.',
                'eyebrow' => 'Listing report',
                'title' => 'A marketplace listing needs review',
                'introLines' => ['A visitor reported a public Starmax Homes listing.'],
                'highlightLabel' => null,
                'highlightValue' => null,
                'document' => null,
                'details' => array_filter([
                    'Property' => $property->name,
                    'Reason' => $data['reason'],
                    'Details' => $data['details'],
                    'Reporter email' => $data['report_email'] ?? null,
                    'Report ID' => $report->id,
                ]),
                'actionLabel' => 'Review reports',
                'actionUrl' => route('admin.listing-reports.index'),
                'footerText' => 'This report is stored in the Starmax admin listing-reports queue.',
            ], function ($message) use ($subject) {
                $recipients = config('mail.marketplace_recipients');
                $message->to($recipients[0])->cc(array_slice($recipients, 1))->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::warning('Marketplace listing report email could not be sent.', [
                'report_id' => $report->id,
                'property_id' => $property->id,
                'exception' => $exception->getMessage(),
            ]);
        }
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
