(() => {
    'use strict';
    const key = 'starmax_saved_units_v1';
    const maxAge = 180 * 24 * 60 * 60 * 1000;
    const validId = id => typeof id === 'string' && /^[0-9a-f-]{36}$/i.test(id);
    const readSaved = () => {
        try {
            const stored = JSON.parse(localStorage.getItem(key));
            if (stored && Number.isFinite(stored.savedAt) && stored.savedAt <= Date.now() && Date.now() - stored.savedAt < maxAge && Array.isArray(stored.ids)) return [...new Set(stored.ids.filter(validId))].slice(0, 12);
            localStorage.removeItem(key);
        } catch (_) { /* Saving has an explicit fallback below. */ }
        return [];
    };
    let saved = readSaved();
    const savedPath = document.querySelector('[data-saved-link]')?.getAttribute('href')?.split('?')[0] || '/saved-homes';
    const buildUrl = () => {
        const url = new URL(savedPath, location.origin);
        saved.forEach(id => url.searchParams.append('units[]', id));
        return url.href;
    };
    const refresh = () => {
        document.querySelectorAll('[data-saved-link]').forEach(link => {link.href = buildUrl();});
        document.querySelectorAll('[data-save-unit]').forEach(button => {
            button.hidden = false;
            const active = saved.includes(button.dataset.saveUnit);
            button.setAttribute('aria-pressed', String(active));
            button.textContent = active ? 'Saved — remove' : 'Save this home';
        });
    };
    document.querySelectorAll('[data-save-unit]').forEach(button => {
        button.addEventListener('click', () => {
            const status = button.parentElement.querySelector('[data-save-status]');
            const id = button.dataset.saveUnit;
            saved = readSaved();
            const active = saved.includes(id);
            if (!validId(id)) return;
            if (!active && saved.length >= 12) {
                if (status) status.textContent = 'You can save 12 homes. Remove one from your shortlist first.';
                return;
            }
            const next = active ? saved.filter(value => value !== id) : [...saved, id];
            try {
                localStorage.setItem(key, JSON.stringify({savedAt: Date.now(), ids: next}));
                saved = next;
                refresh();
                if (status) status.textContent = active ? 'Removed from this browser’s saved homes.' : 'Saved on this browser. Open Saved homes to compare.';
            } catch (_) {
                if (status) status.textContent = 'Your browser blocked saving. Bookmark the comparison link instead.';
            }
        });
    });
    document.querySelectorAll('[data-clear-saved]').forEach(button => {
        button.hidden = false;
        button.addEventListener('click', () => {
            try {
                localStorage.removeItem(key);
                saved = [];
                refresh();
                location.assign(savedPath);
            } catch (_) {
                document.querySelector('[data-shortlist-status]').textContent = 'Your browser blocked this change. Clear saved site data in browser settings.';
            }
        });
    });
    window.addEventListener('storage', event => {
        if (event.key === key || event.key === null) {saved = readSaved(); refresh();}
    });
    refresh();
    const page = document.querySelector('[data-saved-page]');
    if (page && page.dataset.hasSelection !== 'true' && saved.length) location.replace(buildUrl());
    const compare = document.querySelector('.comparison-picker');
    compare?.addEventListener('submit', event => {
        const count = compare.querySelectorAll('input[name="compare[]"]:checked').length;
        if (count < 1 || count > 4) {
            event.preventDefault();
            document.querySelector('[data-shortlist-status]').textContent = 'Select between one and four homes for comparison.';
        }
    });
})();
