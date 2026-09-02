<?php

namespace App\Http\Controllers\TenantMarketplace;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class MarketplaceEnquiryController extends Controller
{
    public function store(Request $request, Property $property)
    {
        abort_unless(Property::query()->publiclyAvailable()->whereKey($property->id)->exists(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:255', 'required_without:phone_number'],
            'phone_number' => ['nullable', 'string', 'max:32', 'required_without:email'],
            'unit_id' => ['nullable', 'uuid'],
            'message' => ['nullable', 'string', 'max:1500'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        if (filled($data['unit_id'] ?? null) && ! $property->units()
            ->whereKey($data['unit_id'])->where('status', 'AVAILABLE')->exists()) {
            throw ValidationException::withMessages(['unit_id' => 'The selected home is no longer available.']);
        }

        $enquiry = PropertyEnquiry::create([
            'property_id' => $property->id,
            'unit_id' => $data['unit_id'] ?? null,
            'name' => trim($data['name']),
            'email' => filled($data['email'] ?? null) ? strtolower(trim($data['email'])) : null,
            'phone_number' => filled($data['phone_number'] ?? null) ? trim($data['phone_number']) : null,
            'message' => filled($data['message'] ?? null) ? trim($data['message']) : null,
            'source_ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
        ]);

        $property->loadMissing('landlord');
        if (filled($property->landlord?->email)) {
            try {
                Mail::send('emails.tenant-pro-update', [
                    'subjectLine' => 'New viewing request for '.$property->name,
                    'preheader' => 'A prospective tenant has asked about one of your available homes.',
                    'eyebrow' => 'Marketplace enquiry',
                    'title' => 'A tenant is interested in '.$property->name,
                    'introLines' => ['A prospective tenant submitted a viewing request through the public Starmax homes marketplace. Contact them directly to confirm availability and arrange the next step.'],
                    'highlightLabel' => null,
                    'highlightValue' => null,
                    'document' => null,
                    'details' => array_filter([
                        'Prospective tenant' => $enquiry->name,
                        'Phone' => $enquiry->phone_number,
                        'Email' => $enquiry->email,
                        'Message' => $enquiry->message,
                    ]),
                    'actionLabel' => 'Open property',
                    'actionUrl' => route('admin.properties.show', $property),
                    'footerText' => 'This enquiry is stored securely in Starmax. Confirm the property is still available before arranging a viewing.',
                ], fn ($message) => $message
                    ->to($property->landlord->email, $property->landlord->name)
                    ->subject('New viewing request: '.$property->name));
            } catch (Throwable $exception) {
                Log::warning('Marketplace enquiry email could not be sent.', [
                    'enquiry_id' => $enquiry->id,
                    'property_id' => $property->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('marketplace_success', 'Your viewing request has been sent. The property manager will contact you using the details provided.');
    }
}
