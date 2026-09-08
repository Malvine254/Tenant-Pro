(() => {
    'use strict';
    const checklist = document.querySelector('.viewing-checklist');
    const checklistProgress = document.querySelector('[data-checklist-progress]');
    if (checklist && checklistProgress) {
        const items = [...checklist.querySelectorAll('input[type="checkbox"]')];
        const count = checklistProgress.querySelector('[data-checklist-count]');
        const meter = checklistProgress.querySelector('[data-checklist-meter]');
        const reset = checklistProgress.querySelector('[data-checklist-reset]');
        const updateChecklist = () => {
            const completed = items.filter(item => item.checked).length;
            count.textContent = `${completed} of ${items.length} checks completed${completed === items.length ? ' — ready to review the agreement and costs.' : ''}`;
            meter.max = items.length;
            meter.value = completed;
            reset.disabled = completed === 0;
        };
        checklist.addEventListener('change', updateChecklist);
        reset.addEventListener('click', () => {
            items.forEach(item => { item.checked = false; });
            updateChecklist();
            items[0]?.focus({preventScroll: true});
        });
        // Do not restore an earlier viewing's checks on reload or history navigation.
        window.addEventListener('pageshow', () => {
            items.forEach(item => { item.checked = false; });
            updateChecklist();
        });
        updateChecklist();
        checklistProgress.hidden = false;
    }
    const banner = document.querySelector('[data-cookie-banner]');
    const filters = document.querySelector('.advanced-filters');
    const key = 'starmax_filters_open';
    const readChoice = () => {
        const value = document.cookie.split('; ').find(item => item.startsWith('starmax_cookie_choice='))?.split('=')[1];
        return ['v1-essential', 'v1-preferences'].includes(value) ? value : null;
    };
    const applyChoice = () => {
        try {
            if (readChoice() !== 'v1-preferences') localStorage.removeItem(key);
            else if (filters && localStorage.getItem(key) === 'true') filters.open = true;
        } catch (_) { /* Browsing remains available when storage is blocked. */ }
    };
    if (banner) {
        banner.hidden = readChoice() !== null;
        applyChoice();
        document.querySelectorAll('[data-cookie-settings]').forEach(button => {
            button.hidden = false;
            button.addEventListener('click', () => {
                banner.hidden = false;
                banner.querySelector('button').focus({preventScroll: true});
            });
        });
        banner.querySelectorAll('[data-cookie-choice]').forEach(button => {
            button.addEventListener('click', () => {
                const choice = 'v1-' + button.dataset.cookieChoice;
                document.cookie = `starmax_cookie_choice=${choice}; Max-Age=15552000; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
                applyChoice();
                if (readChoice() === choice) {
                    banner.hidden = true;
                    document.querySelector('[data-cookie-settings]')?.focus({preventScroll: true});
                } else {
                    banner.querySelector('[data-cookie-status]').textContent = 'Your browser blocked saving this choice. Optional preferences remain off.';
                }
            });
        });
        filters?.addEventListener('toggle', () => {
            if (readChoice() === 'v1-preferences') {
                try { localStorage.setItem(key, String(filters.open)); } catch (_) {}
            }
        });
    }
    document.querySelectorAll('.share-actions').forEach(group => {
        const status = group.querySelector('[data-share-status]');
        const copy = group.querySelector('[data-share-copy]');
        const share = group.querySelector('[data-share-native]');
        copy.hidden = false;
        const copyLink = async () => {
            try {
                await navigator.clipboard.writeText(group.dataset.shareUrl);
                status.textContent = 'Link copied.';
            } catch (_) {
                status.textContent = 'Copy this link: ' + group.dataset.shareUrl;
            }
        };
        copy.addEventListener('click', copyLink);
        if (navigator.share) {
            share.hidden = false;
            share.addEventListener('click', async () => {
                try {
                    await navigator.share({title: group.dataset.shareTitle, text: group.dataset.shareText, url: group.dataset.shareUrl});
                } catch (error) {
                    if (error.name !== 'AbortError') await copyLink();
                }
            });
        }
    });
})();
