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

        // Advertise submissions use the listing topic implicitly.
        return redirect()
            ->route('marketplace.contact')
            ->withInput()
            ->with('success', 'Thanks, '.$data['name'].'. Starmax support has received your message.');
    }
}
