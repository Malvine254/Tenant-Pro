<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function contact()
    {
        return view('site.contact');
    }

    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:3000',
        ]);

        // Contact handling (mail/logging) can be wired here.
        return back()->with('success', 'Thank you! Your message has been received.');
    }
}
