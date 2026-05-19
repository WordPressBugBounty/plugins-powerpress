/** stateless, mutates dom by id, event-delegated */

// =======
// HELPERS
// =======

function setDisplayById(id, display) {
    const el = document.getElementById(id);
    if (el) el.style.display = display;
}

// ==========
// UI ACTIONS
// ==========

export function powerpressChangeMediaFile(feedSlug) {
    const hiddenInput = document.getElementById(`powerpress_url_${feedSlug}`);
    if (hiddenInput) hiddenInput.dataset.original = hiddenInput.value;

    setDisplayById(`pp-url-input-container-${feedSlug}`, '');
    setDisplayById(`powerpress_url_show_${feedSlug}`, 'none');
    setDisplayById(`edit-media-file-${feedSlug}`, 'none');
    setDisplayById(`pp-change-media-file-${feedSlug}`, '');
    setDisplayById(`ep-box-blubrry-service-${feedSlug}`, 'block');

    const container = document.getElementById(`pp-media-blubrry-container-${feedSlug}`);
    if (container) container.setAttribute('style', 'background-color: #f1f4f9; padding: 2ch;');

    powerpressHighlightMediaButtons(feedSlug);
}

export function powerpressCancelMediaEdit(feedSlug) {
    const hiddenInput = document.getElementById(`powerpress_url_${feedSlug}`);
    const displayInput = document.getElementById(`powerpress_url_display_${feedSlug}`);

    const original = (hiddenInput && hiddenInput.dataset.original)
        || (hiddenInput && hiddenInput.value)
        || '';
    if (hiddenInput) hiddenInput.value = original;
    if (displayInput) displayInput.value = original;

    setDisplayById(`pp-url-input-container-${feedSlug}`, 'none');
    setDisplayById(`powerpress_url_show_${feedSlug}`, 'inline-block');
    setDisplayById(`edit-media-file-${feedSlug}`, 'inline-block');
    setDisplayById(`pp-change-media-file-${feedSlug}`, 'none');
    setDisplayById(`ep-box-blubrry-service-${feedSlug}`, 'none');
    setDisplayById(`file-change-warning-${feedSlug}`, 'none');

    const container = document.getElementById(`pp-media-blubrry-container-${feedSlug}`);
    if (container) container.removeAttribute('style');
}

export function powerpressHighlightMediaButtons(feedSlug) {
    const visible = document.getElementById(`powerpress_url_display_${feedSlug}`);
    const hidden = document.getElementById(`powerpress_url_${feedSlug}`);
    const value = (visible && visible.value) || (hidden && hidden.value) || '';
    const active = value.length > 0;
    [`save-media-${feedSlug}`, `continue-to-episode-settings-${feedSlug}`].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) btn.classList.toggle('pp-action-active', active);
    });
}

export function powerpressUpdateMediaInput(feedSlug, value) {
    const hidden = document.getElementById(`powerpress_url_${feedSlug}`);
    if (hidden) hidden.value = value;
}

export function powerpressSaveMediaFile(feedSlug) {
    const input = document.getElementById(`powerpress_url_display_${feedSlug}`);
    const warning = document.getElementById(`file-change-warning-${feedSlug}`);
    if (input && input.value !== '') {
        if (typeof window.powerpress_get_media_info === 'function') {
            window.powerpress_get_media_info(feedSlug);
        }
        if (warning) warning.style.display = 'none';
    } else if (warning) {
        warning.style.display = 'block';
        warning.classList.add('error');
    }
}

export function powerpressContinueToEpisodeSettings(feedSlug) {
    const input = document.getElementById(`powerpress_url_display_${feedSlug}`);
    const warning = document.getElementById(`file-select-warning-${feedSlug}`);
    if (input && input.value !== '') {
        if (typeof window.powerpress_get_media_info === 'function') {
            window.powerpress_get_media_info(feedSlug);
        }
        if (warning) warning.style.display = 'none';
    } else if (warning) {
        warning.style.display = 'block';
        warning.classList.add('error');
    }
}

export function powerpressVerifyMedia(feedSlug) {
    if (typeof window.powerpress_get_media_info === 'function') {
        window.powerpress_get_media_info(feedSlug);
    }
}

export function powerpressSkipToEpisodeSettings(feedSlug) {
    const tab = document.getElementById(`tab-container-${feedSlug}`);
    const warning = document.getElementById(`file-select-warning-${feedSlug}`);
    const details = document.getElementById(`media-file-details-${feedSlug}`);
    const bluContainer = document.getElementById(`pp-media-blubrry-container-${feedSlug}`);
    if (tab) tab.style.display = 'block';
    if (warning) warning.style.display = 'none';
    if (details) details.style.display = 'inline-block';
    if (bluContainer) bluContainer.setAttribute('style', 'background-color: #f1f4f9; padding: 2ch;');
}

// ====
// INIT
// ====

export function initMediaEdit() {
    const actionHandlers = {
        save: powerpressSaveMediaFile,
        cancel: powerpressCancelMediaEdit,
        continue: powerpressContinueToEpisodeSettings,
        edit: powerpressChangeMediaFile,
    };

    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-action][data-feed-slug]');
        if (!target) return;
        const handler = actionHandlers[target.dataset.action];
        if (!handler) return;
        e.preventDefault();
        handler(target.dataset.feedSlug);
    });

    document.addEventListener('input', function(e) {
        const target = e.target;
        if (!target || !target.id || !target.id.startsWith('powerpress_url_display_')) return;
        const feedSlug = target.dataset.feedSlug;
        if (!feedSlug) return;
        powerpressUpdateMediaInput(feedSlug, target.value);
        powerpressHighlightMediaButtons(feedSlug);
    });

    document.querySelectorAll('[id^="powerpress_url_display_"]').forEach(input => {
        const feedSlug = input.dataset.feedSlug;
        if (feedSlug) powerpressHighlightMediaButtons(feedSlug);
    });

    document.querySelectorAll('.pp-init-skip-to-episode').forEach(el => {
        powerpressSkipToEpisodeSettings(el.dataset.feedSlug);
    });

    document.querySelectorAll('.pp-init-verify-media').forEach(el => {
        powerpressVerifyMedia(el.dataset.feedSlug);
    });
}
