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
    const modal = document.querySelector('[data-cookie-modal]');
    const modalDialog = modal?.querySelector('.cookie-modal-dialog');
    const preferenceToggle = modal?.querySelector('[data-cookie-preference-toggle]');
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
    const saveChoice = (choice) => {
        document.cookie = `starmax_cookie_choice=${choice}; Max-Age=15552000; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
        applyChoice();
        return readChoice() === choice;
    };
    const closeModal = () => {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('cookie-modal-open');
    };
    const openModal = () => {
        if (!modal) return;
        preferenceToggle.checked = readChoice() === 'v1-preferences';
        modal.hidden = false;
        document.body.classList.add('cookie-modal-open');
        modalDialog?.focus({preventScroll: true});
    };
    if (banner) {
        banner.hidden = readChoice() !== null;
        applyChoice();
        document.querySelectorAll('[data-cookie-settings]').forEach(button => {
            button.hidden = false;
            button.addEventListener('click', () => {
                openModal();
            });
        });
        banner.querySelector('[data-cookie-preferences]')?.addEventListener('click', openModal);
        banner.querySelectorAll('[data-cookie-choice]').forEach(button => {
            button.addEventListener('click', () => {
                const choice = 'v1-' + button.dataset.cookieChoice;
                if (saveChoice(choice)) {
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
    modal?.querySelectorAll('[data-cookie-close]').forEach(button => button.addEventListener('click', closeModal));
    modal?.querySelector('[data-cookie-save]')?.addEventListener('click', () => {
        const choice = preferenceToggle?.checked ? 'v1-preferences' : 'v1-essential';
        const status = modal.querySelector('[data-cookie-modal-status]');
        if (saveChoice(choice)) {
            closeModal();
            if (banner) banner.hidden = true;
            document.querySelector('[data-cookie-settings]')?.focus({preventScroll: true});
        } else if (status) {
            status.textContent = 'Your browser blocked saving this choice. Essential cookies remain on.';
        }
    });
    modal?.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeModal();
    });
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
