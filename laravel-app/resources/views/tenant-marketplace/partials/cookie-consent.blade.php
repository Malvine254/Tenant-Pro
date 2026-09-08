<section class="cookie-banner" data-cookie-banner aria-labelledby="cookie-title" hidden>
    <h2 id="cookie-title">Your privacy choices</h2>
    <p>We use essential cookies to keep forms and sign-in working. You can also allow this browser to remember optional preferences. No advertising or analytics cookies are currently used.</p>
    <a href="{{ route('marketplace.cookies') }}">Read about cookies and privacy</a>
    <div class="cookie-actions">
        <button type="button" data-cookie-choice="essential">Essential only</button>
        <button type="button" data-cookie-preferences>Allow preferences</button>
    </div>
    <p data-cookie-status role="status"></p>
</section>
<div class="cookie-modal" data-cookie-modal hidden>
    <div class="cookie-modal-backdrop" data-cookie-close></div>
    <section class="cookie-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cookie-modal-title" tabindex="-1">
        <button type="button" class="cookie-modal-close" data-cookie-close aria-label="Close cookie preferences">&times;</button>
        <h2 id="cookie-modal-title">Cookie preferences</h2>
        <p>Choose which optional browser preferences you want to allow. Essential cookies are always on because the marketplace needs them for security and form submissions.</p>
        <div class="cookie-options">
            <label class="cookie-option cookie-option-locked">
                <input type="checkbox" checked disabled>
                <span><strong>Essential cookies</strong><small>Required for security, sessions, and form validation.</small></span>
                <b>Always on</b>
            </label>
            <label class="cookie-option">
                <input type="checkbox" data-cookie-preference-toggle>
                <span><strong>Optional preferences</strong><small>Remember choices such as expanded search filters in this browser.</small></span>
            </label>
        </div>
        <div class="cookie-modal-actions">
            <button type="button" class="cookie-modal-secondary" data-cookie-close>Cancel</button>
            <button type="button" class="cookie-modal-primary" data-cookie-save>Save preferences</button>
        </div>
        <p class="cookie-modal-status" data-cookie-modal-status role="status"></p>
    </section>
</div>
<noscript><p class="market-shell">Only essential cookies are used when JavaScript is disabled. <a href="{{ route('marketplace.cookies') }}">Cookie details</a>.</p></noscript>
