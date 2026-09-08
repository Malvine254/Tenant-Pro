# Marketplace enhancements — 8 September 2026

Implemented following the initial marketplace review. Changes are available on the local application; no production deployment was performed.

## Rental details and costs

Managers can edit bathrooms, deposit, monthly service charge, other one-time charges and their description, amenities, earliest move-in date and an explicit availability confirmation. The fields appear on Add Unit and Edit Unit; bulk creation applies shared details to the created units. Individual photo management is on Edit Unit.

The unit detail panel shows a line-by-line move-in breakdown: first month rent, deposit, first month service charge, water, garbage and other one-time charges. Unit utility overrides take precedence over property defaults. Blank fees remain unknown; zero means no charge. Incomplete figures are labelled a known subtotal. Electricity, usage-based bills and personal moving expenses are excluded.

These are advertised listing amounts. Existing invoice-generation rules were not changed; managers must reconcile the agreed charges with their billing workflow. In particular, the existing onboarding billing service defaults the deposit invoice to one month's rent.

Unit uploads accept up to 12 JPG/PNG/WebP photos of up to 5 MB each, allow removal and retain existing photos when adding new ones. Failed saves clean up newly stored uploads. Removed photos disappear from the listing; existing stored files are retained to avoid deleting potentially reused assets. No property photographs or factual amenities were invented.

Availability confirmations are manager-entered, not independent verification. Changing a unit out of Available clears the confirmation; changing it back requires confirmation again. Existing units show “Not yet confirmed” until a manager checks them.

## Discovery and reporting

- Saved homes: explicitly save individual units in browser storage; retain up to 12 for 180 days after the last change. Remove a home, clear the shortlist, or compare up to four. Cross-tab changes are reflected. Blocked storage has a bookmark fallback; shared comparison URLs work without browser storage.
- Comparisons display specific unit details and costs. Server-side checks exclude occupied, private and otherwise ineligible listings even if their identifiers remain in an old saved link.
- Neighbourhoods: property editors can add an area and local notes. Public pages combine actual vacancies, attributed manager notes and a practical area-visiting checklist. Area pages without local notes are noindex; eligible pages enter the sitemap. No invented transport times, amenities or safety ratings.
- Listing reports: a CSRF-protected, throttled public form captures a reason, details and optional reply email. Reports are private and appear in an admin-only queue. Reviewers can inspect the property, resolve reports and reopen them. Reports do not automatically remove listings or send external messages.

## Design and safety guide

The Rent safely page now covers preparation, a viewing checklist, agreement/payment checks, warning signs, move-in handover, reporting and FAQs. The checklist is temporary page state. Advice to verify the actual home and representative follows the general fraud-prevention principles in [Report Fraud's rental fraud guidance](https://www.reportfraud.police.uk/rental-fraud/); no foreign legal rules were applied to Kenyan tenancies.

Informational heroes now use a light background, 42 px maximum desktop headings, 30 px mobile headings, compact spacing and readable action links. The safety hero includes a short reminder card. Property-manager sign-in links in the marketplace open in a new tab with appropriate rel attributes. Cookie dismissal no longer scrolls to the footer.

## Database and validation

- Applied the new `2026_09_08_150000_enrich_marketplace_listings` migration to the verified local MySQL connection. Production still needs this migration during deployment.
- Replaced MySQL-only SQL in two historical support-conversation backfill migrations with portable correlated subqueries. Existing deployed databases do not rerun those migrations. Fresh SQLite test databases now initialise successfully.
- Marketplace presentation, routing, privacy, costs, permissions, photos, reports, neighbourhood and comparison tests pass. The wider suite ran 47 tests: 42 passed, with 5 remaining failures/errors in M-Pesa configuration/settings, landlord suspension, team creation and invitation markup expectations. Those workflows were not changed to accommodate the failures.
- Automated local Edge checks covered desktop/mobile pages, no horizontal page overflow, manager sign-in targets, native unit dialogs, saving/comparison navigation, essential-only consent, preference acceptance and withdrawal. Visual inspection caught and fixed low-contrast links and consent-triggered scrolling. Screenshots are in `.marketplace-preview/` (ignored local artifacts).
- External WhatsApp/Facebook/LinkedIn preview rendering remains a production-domain check: local URLs cannot be fetched by those platforms. Existing server-rendered Open Graph and Twitter metadata remains in place.

## Where managers work

Property → Edit: neighbourhood and local area notes.

Property → unit → Edit: listing facts, advertised fees, availability confirmation and photos. New values stay unknown until managers supply them.

Platform admin → Listing reports: review and resolve submitted concerns.
