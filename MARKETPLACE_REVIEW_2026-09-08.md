# Marketplace review — 8 September 2026

> Follow-up: the next-priority features and local browser checks are now implemented. See [Marketplace enhancements](MARKETPLACE_ENHANCEMENTS_2026-09-08.md) for the current status. The notes below record the initial review.

## Direction

Keep the existing /homes visual design as the main entry point. It already combines a rental-search hero, actual vacancies, price comparisons, filters and safety guidance. The previous / route rendered a separate promotional template with six featured properties; /homes rendered the searchable catalogue. Both were intentional but created an extra step for renters and two competing entry experiences. The root now permanently redirects to /homes, preserving search parameters; the brand link goes directly to /homes.

This review inspected the local Laravel implementation; it is not a live-site visual or performance audit. Existing uncommitted marketplace work was preserved.

## Changes made locally

- Preserve the /homes composition. Expand rent/bedroom filters within the form, add Apply filters, keep selected bedrooms expanded, and expose all supported bedroom counts. Require rent and bedroom filters to match the same available unit.
- Put starting monthly rent next to the property title. Add native sharing, copy-link fallback, WhatsApp and email actions. Sharing is user initiated.
- Render Open Graph and Twitter metadata on the server, including property name, city, starting rent, known bedroom types, available-unit count and absolute cover-photo URL. Use the existing brand image if no cover exists. Private enquiry and manager contact data are excluded. Harden existing breadcrumb JSON against script injection.
- Preserve pagination in canonical URLs; mark ad hoc searches/filters noindex,follow. Add a streamed sitemap restricted to publicly available properties and a dynamic robots.txt with an absolute sitemap URL. The deleted static robots.txt must also be removed during deployment, otherwise the web server can serve its stale contents instead of the route.
- Add Essential only / Allow preferences controls, 180-day versioned choice storage and a permanent footer setting to change the choice. Optional local storage remembers expanded filters only after acceptance, and is removed on rejection. There are no marketplace analytics or advertising scripts to enable. Future tracking needs its own consent design and must not treat preference acceptance as analytics permission.
- Add a cookies/privacy explanation and remove the external Google Fonts import from the marketplace; existing system fallbacks are used. External listing images may still make third-party requests.

## Recommended next priorities

1. **Complete listing facts:** deposit, service charge, utilities, bathrooms, size, parking, water supply, internet, accessibility, pet policy and earliest move-in date. Show unknown values as “Ask manager”. Show monthly rent separately from total move-in cost. This needs model/admin changes and real manager-supplied data.
2. **Better photography:** require current interior/exterior images, provide responsive WebP/AVIF sizes and a properly sized branded social fallback. Avoid invented property photos. Label property-level photos when a particular unit's photos are missing.
3. **Trust and freshness:** add manager-confirmed availability dates and reporting of inaccurate listings. Only show a verification badge once there is an actual verification process.
4. **Shortlist and compare:** let renters compare a few homes and return to saved choices; design anonymous storage choices deliberately. Add alerts only with a separate notification opt-in.
5. **Location discovery:** build useful neighbourhood pages with actual listings, transport, amenities and original area information. Current arbitrary search combinations remain excluded from indexing; future editorial location pages can be indexable.
6. **Mobile enquiries:** add a compact sticky viewing action after measuring overlap with cookie controls; show expected response time only when supported by measured data.

## Research and rationale

- [NN/g: ecommerce search](https://www.nngroup.com/articles/state-ecommerce-search/) supports making relevant results and useful attribute filters central. Applied here to rent/location/bedrooms; rental-specific recommendations above are product judgments.
- [Google: consolidate duplicate URLs](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls) supports consistent permanent redirects, canonical URLs and internal linking.
- [Google: sitemap guidance](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap) supports listing canonical public URLs. Split into a sitemap index before exceeding 50,000 URLs or the sitemap size limit.
- [Open Graph protocol](https://ogp.me/) defines title, image, URL and description metadata used for previews. The receiving communication app decides which fields it displays and can cache old previews.
- [MDN: native sharing](https://developer.mozilla.org/en-US/docs/Web/API/Navigator/share) documents HTTPS/browser requirements, so explicit WhatsApp/email and copy fallbacks are provided.
- [Kenya ODPC consent guidance](https://www.odpc.go.ke/wp-content/uploads/2024/02/ODPC-Guidance-Notes-on-Consent.pdf) informs clear choices and withdrawal. This implementation is not a certification of legal compliance; a complete organisation-wide privacy notice still needs actual retention periods, controller details and operational procedures.

## Release checks

- Deploy through the usual workflow; no live deployment was performed. Ensure the public origin uses HTTPS and APP_URL is the correct public domain. Confirm social crawlers can fetch cover images without login, expiring tokens or hotlink restrictions.
- Check a live property URL in WhatsApp, Facebook/LinkedIn preview tools and the actual target channels. Metadata supplies the details but cannot force every channel to display them.
- Submit /sitemap.xml in Search Console and inspect /homes plus representative property pages. Confirm the deployed /robots.txt reaches the dynamic route.
- Perform desktop/mobile browser checks of the filter expansion, share/copy actions, essential-only choice, opt-in, reload persistence and withdrawal. No visual browser verification was available in this session.
- PHP syntax, JavaScript syntax and Blade compilation were checked. Database-backed marketplace tests hit existing MySQL-specific support-conversation backfill migrations when run on SQLite; those unrelated migrations were not changed. Additional database-free presentation tests cover routes and rendered previews.
