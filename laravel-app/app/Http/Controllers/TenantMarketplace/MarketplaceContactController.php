<?php

namespace App\Http\Controllers\TenantMarketplace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        return redirect()
            ->route($isListingRequest ? 'marketplace.advertise' : 'marketplace.contact')
            ->with('success', $isListingRequest
                ? 'Thanks, '.$data['name'].'. Your listing setup request has been submitted.'
                : 'Thanks, '.$data['name'].'. Your support request has been submitted.');
    }
}
