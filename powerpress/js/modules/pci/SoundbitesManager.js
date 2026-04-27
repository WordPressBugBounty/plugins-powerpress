/**
 * Soundbite Management system for Podcast2.0 Tag:
 * documentation: https://podcasting2.org/docs/podcast-namespace/tags/soundbite
 * Tag: <podcast:soundbite>
 * Main Content: Title for soundbite (free-form string 128chars or less)
 *       Supported Attributes:
 *          - startTime (required): The time where the soundbite begins
 *          - duration (required): How long is the soundbite (recommended between 15 and 120 seconds)
 */

import { showError, hideError } from '../../utils/dom-utils.js';
import { cleanHTMLTags } from '../../utils/html-utils.js';

const SoundbitesManager = {
    feedSlugs: new Map(),

    getFeedSlug(feedSlug, containerElement) {
        if (!this.feedSlugs.get(feedSlug)) {
            const slug = {
                feedSlug: feedSlug,
                container: containerElement,
                tableContainer: null,
                _tbody: null,
                isEditMode: false
            };

            this.feedSlugs.set(feedSlug, slug);
        }
        return this.feedSlugs.get(feedSlug);
    },

    /**
     * initializer
     * -> setup event handlers
     * -> render pre-loaded soundbites from hidden containers
     * -> setup input form and display table
     */
    init(feedSlug) {
        const container = document.getElementById(`soundbite-container-${feedSlug}`)
        if (!container) return;

        const slug = this.getFeedSlug(feedSlug, container);

        slug.tableContainer = container.querySelector(`#soundbite-table-container-${feedSlug}`);
        slug._tbody = slug.tableContainer?.querySelector('tbody') || null;

        container.addEventListener('click', (e) => this.onClick(e, feedSlug));

        // form 'Enter' handling
        const formContainer = container.querySelector(`#soundbite-form-container-${feedSlug}`);
        if (formContainer) {
            formContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.addSoundbite(feedSlug);
                    return false;
                }
            });
        }

        this.updateTableDisplay(feedSlug);
    },

    /**
     * click event handler for the soundbite container
     * @param {Event} e - click event handler
     * @returns when the element clicked is not of this container
     */
    onClick(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'add-soundbite':
                e.preventDefault();
                this.addSoundbite(feedSlug);
                break;
            case 'remove-soundbite':
                e.preventDefault();
                this.removeSoundbite(el, feedSlug);
                break;
            case 'edit-soundbite':
                e.preventDefault();
                this.editSoundbite(el, feedSlug);
                break;
        }
    },

    /**
     * initiates editing process for soundbite and updates table view
     * @param {HTMLElement} button - edit button clicked
     * @returns when no row found
     */
    editSoundbite(button, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const row = button.closest('tr[data-soundbite-id]');
        if (!row) return;

        const existingData = this.extractSoundbiteDataFromRow(row);
        slug.isEditMode = true;
        this.updateFormEdit(existingData, feedSlug);

        row.remove();
        this.updateTableDisplay(feedSlug);
    },

    /**
     * extract soundbite data from row in table
     * @param {HTMLElement} row - row to extract data from
     * @returns soundbite object - {id, start, duration, title}
     */
    extractSoundbiteDataFromRow(row) {
        const soundbiteId = parseInt(row.dataset.soundbiteId);

        return {
            id: soundbiteId,
            start: row.querySelector(`input[name$="start]"]`)?.value || '',
            duration: row.querySelector(`input[name$="duration]"]`)?.value || '',
            title: row.querySelector(`input[name$="title]"]`)?.value || ''
        };
    },

    /**
     * modifies input with data to edit
     * @param {Object} existingData - soundbite object {id, start, duration, title}
     */
    updateFormEdit(existingData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const startStr = this.secondsToTimeStr(existingData.start);
        const durationStr = this.secondsToTimeStr(existingData.duration);

        slug.container.querySelector(`#soundbite-start-input-${feedSlug}`).value = startStr;
        slug.container.querySelector(`#soundbite-duration-input-${feedSlug}`).value = durationStr;
        slug.container.querySelector(`#soundbite-title-input-${feedSlug}`).value = existingData.title || '';
    },

    /**
     * converts raw seconds -> hh:mm:ss time format
     * @param {number|string} seconds - seconds input value, can convert both string and int
     * @returns {string} formatted time string
     */
    secondsToTimeStr(seconds) {
        if (typeof seconds === 'string' && seconds.includes(':')) {
            const [h = '0', m = '0', s = '0'] = seconds.split(':');
            const hh = String(parseInt(h, 10) || 0).padStart(2, '0');
            const mm = String(parseInt(m, 10) || 0).padStart(2, '0');
            const ss = String(parseInt(s, 10) || 0).padStart(2, '0');
            return `${hh}:${mm}:${ss}`;
        }
        const secs = Math.max(0, parseInt(seconds, 10) || 0);
        const h = Math.floor(secs / 3600);
        const m = Math.floor((secs % 3600) / 60);
        const s = secs % 60;
        const hh = String(h).padStart(2, '0');
        const mm = String(m).padStart(2, '0');
        const ss = String(s).padStart(2, '0');
        return `${hh}:${mm}:${ss}`;
    },

    /**
     * regex based time validation and value extract
     * @param {string} timeStr
     * @returns {boolean}
     */
    validateTime(timeStr) {
        const timeRegex = /^(\d{1,2}):([0-5]?\d):([0-5]?\d)$/;

        if (timeStr === "00:00:00") return false;

        return timeRegex.test(timeStr);
    },

    /**
     * add soundbite on click
     */
    addSoundbite(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // grab and validate inputs
        const startInput = slug.container.querySelector(`#soundbite-start-input-${feedSlug}`);
        const durationInput = slug.container.querySelector(`#soundbite-duration-input-${feedSlug}`);
        const titleInput = slug.container.querySelector(`#soundbite-title-input-${feedSlug}`);

        if (!titleInput || !startInput || !durationInput) {
            showError('Form inputs not found', `soundbite-error-${feedSlug}`);
            return;
        }

        // extract + clean form data
        const startTimeStr = cleanHTMLTags(startInput.value) || '';
        const durationStr = cleanHTMLTags(durationInput.value) || '';
        const title = cleanHTMLTags(titleInput.value) || '';

        const startTimeValid = this.validateTime(startTimeStr);
        const durationValid = this.validateTime(durationStr);

        if (startTimeValid === false && durationValid === false) {
            showError('Start Time and Duration are required', `soundbite-error-${feedSlug}`);
            return;
        }

        if (startTimeValid === false) {
            showError('Start Time is required', `soundbite-error-${feedSlug}`);
            return;
        }

        if (durationValid === false) {
            showError('Duration is required', `soundbite-error-${feedSlug}`);
            return;
        }

        // prep new soundbite object
        const soundbiteData = {
            id: this.getNextId(feedSlug),
            startTime: startTimeStr,
            duration: durationStr,
            title: title
        };

        this.addSoundbiteRow(soundbiteData, feedSlug);
        this.clearForm(feedSlug);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * setup for new row, checks if table was rendered by pre-load
     * cache _tbody so we dont check everytime after first render
     * @param {Object} soundbiteData
     */
    addSoundbiteRow(soundbiteData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;
        if (!slug._tbody) return;

        const rowTemplate = slug.container.querySelector(`#soundbite-row-template-${feedSlug}`);
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        const id = soundbiteData.id;

        node.dataset.soundbiteId = id;
        node.querySelector('[data-action="remove-soundbite"]').dataset.soundbiteId = id;

        node.querySelector('[data-cell="title"]').textContent = soundbiteData.title;
        node.querySelector('[data-cell="startTime"]').textContent = soundbiteData.startTime;
        node.querySelector('[data-cell="duration"]').textContent = soundbiteData.duration;

        node.querySelectorAll('input[type="hidden"]').forEach(input => {
            input.name = input.name.replace('__ID__', id);
            if (/title]$/.test(input.name)) input.value = soundbiteData.title;
            else if (/start]$/.test(input.name)) input.value = soundbiteData.startTime;
            else if (/duration]$/.test(input.name)) input.value = soundbiteData.duration;
        });

        slug._tbody.appendChild(node);
    },

    /**
     * remove soundbite on click
     * @param {HTMLElement} button - remove button clicked
     */
    removeSoundbite(button, feedSlug) {
        const soundbiteRow = button.closest('tr[data-soundbite-id]');
        if (soundbiteRow) {
            soundbiteRow.remove();
            this.updateTableDisplay(feedSlug);
        }
    },

    /**
     * grab next highest id to avoid overlap
     * @returns {number}
     */
    getNextId(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        const existingSoundbites = slug.container.querySelectorAll(`[data-soundbite-id]`);
        let maxId = 0;

        existingSoundbites.forEach(soundbite => {
            const id = parseInt(soundbite.dataset.soundbiteId);
            if (id > maxId) maxId = id;
        });

        return maxId + 1;
    },

    /**
     * clear soundbite input form
     */
    clearForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        slug.container.querySelector(`#soundbite-start-input-${feedSlug}`).value = '00:00:00';
        slug.container.querySelector(`#soundbite-duration-input-${feedSlug}`).value = '00:00:00';
        slug.container.querySelector(`#soundbite-title-input-${feedSlug}`).value = '';
        hideError(`soundbite-error-${feedSlug}`);
    },

    /**
     * update table display to show empty message or existing rows depending on state
     */
    updateTableDisplay(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const table = slug.tableContainer || slug.container.querySelector(`#soundbite-table-container-${feedSlug}`);
        const tbody = slug._tbody || slug.tableContainer?.querySelector('tbody');
        const msg = slug.container.querySelector(`#soundbite-table-message-${feedSlug}`);

        if (tbody.querySelectorAll('tr[data-soundbite-id]').length === 0) {
            if (table) table.style.display = 'none';
            if (msg) msg.style.display = 'block';
        } else {
            if (table) table.style.display = 'block';
            if (msg) msg.style.display = 'none';
        }
    }
};

export function initSoundbitesManager(feedSlug) {
    SoundbitesManager.init(feedSlug);
}

export { SoundbitesManager };
