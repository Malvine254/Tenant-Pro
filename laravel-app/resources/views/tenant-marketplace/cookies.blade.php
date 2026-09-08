@extends('tenant-marketplace.layout')
@section('title', 'Cookies and privacy | Starmax Homes')
@section('meta_description', 'Learn how Starmax Homes uses essential cookies, optional browser preferences and the contact details you submit in viewing enquiries.')
@section('content')
<section class="market-shell editorial-section privacy-copy">
    <h1>Cookies and privacy</h1>
    <h2>Essential cookies</h2>
    <p>Session and security cookies keep sign-in, form validation and protection against forged requests working. They are necessary to provide these features and are not used for advertising.</p>
    <h2>Your choice</h2>
    <p>We store your choice in a first-party cookie named starmax_cookie_choice for up to 180 days. Choose Essential only to decline optional storage, or Allow preferences to let this browser remember whether you expanded the home-search filters. You can change your choice at any time using Cookie settings in the footer. Declining removes the saved filter preference.</p>
    <h2>Optional preferences</h2>
    <p>If allowed, the expanded-filter preference is stored in this browser's local storage under starmax_filters_open. We do not currently load analytics or advertising trackers. Your cookie choice does not give permission for future analytics or marketing.</p>
    <h2>Saved homes and listing reports</h2>
    <p>When you choose Save this home, we store unit identifiers in this browser under starmax_saved_units_v1 for up to 180 days after your last save or removal. This storage provides the shortlist you requested, independently of optional search preferences. Remove individual homes or use Clear saved homes on the shortlist page. Comparisons use identifiers in the page URL; anyone you share that URL with can see the selected public listings.</p>
    <p>Listing reports store your reason, details and optional email for the Starmax review team. Reports are not published. Do not include identity documents or payment credentials.</p>
    <h2>Viewing enquiries</h2>
    <p>The name, phone number or email, selected home and message you submit are stored to handle your enquiry and shared with the relevant property manager to arrange a response. These details are not displayed in public listings or sharing previews.</p>
    <h2>Photos and sharing</h2>
    <p>Listing photos may be hosted by external providers, which receive normal connection information when an image loads. WhatsApp and email links open the service you choose; that service handles the information you decide to share.</p>
    <h2>Contact us</h2>
    <p>For questions about your information or a request to access, correct or delete enquiry details, <a href="{{ route('marketplace.contact') }}">contact Starmax support</a>. Avoid including sensitive documents in your enquiry.</p>
</section>
@endsection
