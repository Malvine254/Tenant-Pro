<?php

namespace App\Http\Controllers\TenantMarketplace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MarketplaceContactController extends Controller
{
    public function index()
    {
        return view('tenant-marketplace.contact');
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'topic' => ['nullable', 'in:listing,viewing,account,privacy,other'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $isListingRequest = $request->input('service') === 'tenant' || $request->input('topic') === 'listing';

        try {
            $subject = $isListingRequest ? 'New marketplace listing setup request' : 'New marketplace support request';
            Mail::send('emails.tenant-pro-update', [
                'subjectLine' => $subject,
                'preheader' => 'A new request was submitted through Starmax Homes.',
                'eyebrow' => $isListingRequest ? 'Listing setup request' : 'Marketplace support',
                'title' => $isListingRequest ? 'A property owner wants listing support' : 'A marketplace visitor needs support',
                'introLines' => [$isListingRequest
                    ? 'A property owner submitted details to request help preparing a Starmax marketplace listing.'
                    : 'A visitor submitted a support request through the Starmax homes marketplace.'],
                'highlightLabel' => null,
                'highlightValue' => null,
                'document' => null,
                'details' => array_filter([
                    'Name' => $data['name'],
                    'Email' => $data['email'],
                    'Phone' => $data['phone'] ?? null,
                    'Topic' => $data['topic'] ?? ($isListingRequest ? 'listing' : 'other'),
                    'Message' => $data['message'],
                ]),
                'actionLabel' => null,
                'actionUrl' => null,
                'footerText' => 'This message was submitted through the Starmax Homes marketplace.',
            ], function ($message) use ($subject) {
                $recipients = config('mail.marketplace_recipients');
                $message->to($recipients[0])->cc(array_slice($recipients, 1))->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::warning('Marketplace contact email could not be sent.', [
                'email' => $data['email'],
                'exception' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route($isListingRequest ? 'marketplace.advertise' : 'marketplace.contact')
            ->with('success', $isListingRequest
                ? 'Thanks, '.$data['name'].'. Your listing setup request has been submitted.'
                : 'Thanks, '.$data['name'].'. Your support request has been submitted.');
    }
}
