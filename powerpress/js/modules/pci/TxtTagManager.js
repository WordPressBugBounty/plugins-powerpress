/**
 * Txt Tag Management system for Podcast2.0 Tag:
 * documentation: https://podcasting2.org/docs/podcast-namespace/tags/txt
 * Tag: <podcast:txt>
 *      Main Node: Free Form string, not to exceed 4000 characters
 *       Supported Attributes:
 *          - Purpose (optional) A service specific string that will be used to denote what purpose this tag serves.
 *                               This could be something like "example.com" if it's a third party hosting platform needing to insert this data, or something
 *                               like "verify", "release" or any other free form bit of info that is useful to the end consumer that needs it. The free form
 *                               nature of this tag requires that this attribute is also free formed. This value should not exceed 128 characters.
 */

import { showError, hideError } from '../../utils/dom-utils.js';
import { cleanHTMLTags } from '../../utils/html-utils.js';

const TxtTagManager = {
    feedSlugs: new Map(),

    getFeedSlug(feedSlug, containerElement) {
        if (!this.feedSlugs.has(feedSlug)) {
            const slug = {
                feedSlug: feedSlug,
                container: containerElement,
                formContainer: null,
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
     */
    init(feedSlug) {
        const container = document.getElementById(`txt-tag-container-${feedSlug}`);
        if (!container) return;

        const slug = this.getFeedSlug(feedSlug, container);

        slug.formContainer = container.querySelector(`#txt-tag-form-container-${feedSlug}`);
        slug.tableContainer = container.querySelector(`#txt-tag-table-container-${feedSlug}`);
        slug._tbody = slug.tableContainer?.querySelector('tbody') || null;

        // form 'Enter' handling
        const formContainer = container.querySelector(`#txt-tag-form-container-${feedSlug}`);
        if (formContainer) {
            formContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.addTxtTag(feedSlug);
                    return false;
                }
            });
        }

        container.addEventListener('click', (e) => this.onClick(e, feedSlug));
        this.updateTableDisplay(feedSlug);
    },

    /**
     * click event handler
     * @param {Event} e - click event
     * @returns when required dom element doesnt exist
     */
    onClick(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'add-txt-tag':
                e.preventDefault();
                this.addTxtTag(feedSlug);
                break;
            case 'remove-txt-tag':
                e.preventDefault();
                this.removeTxtTag(el, feedSlug);
                break;
            case 'edit-txt-tag':
                e.preventDefault();
                this.editTxtTag(el, feedSlug);
                break;
        }
    },

    /**
     * handler for editing an existing tag
     * @param {HTMLElement} button - edit button
     * @returns when row doesnt exist
     */
    editTxtTag(button, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const row = button.closest('tr[data-txt-tag-id]');
        if (!row) return;

        const existingData = this.extractTagDataFromRow(row);
        slug.isEditMode = true;
        this.updateFormEdit(existingData, feedSlug);

        row.remove();
        this.updateTableDisplay(feedSlug);
    },

    /**
     * extracts existing tag information from row
     * @param {HTMLElement} row - row of existing tag
     * @returns {Object} tag object information
     */
    extractTagDataFromRow(row) {
        const tagId = parseInt(row.dataset.txtTagId);

        return {
            id: tagId,
            tag: row.querySelector(`input[name*="[tag]"]`)?.value || '',
            purpose: row.querySelector(`input[name*="[purpose]"]`)?.value || ''
        };
    },

    /**
     * populates form with the existingData
     * @param {Object} existingData - txt tag object from row
     */
    updateFormEdit(existingData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        slug.container.querySelector(`#txt-tag-content-input-${feedSlug}`).value = existingData.tag || '';
        slug.container.querySelector(`#txt-tag-purpose-input-${feedSlug}`).value = existingData.purpose || '';
    },

    /**
     * gets highest possible id, doesnt grab immediately available numbers to avoid potential overlaps on delete
     * @returns new id
     */
    getNextId(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        const existingTags = slug.container.querySelectorAll('tr[data-txt-tag-id]');
        let maxId = 0;

        existingTags.forEach(tag => {
            const id = parseInt(tag.dataset.txtTagId);
            if (id > maxId) maxId = id;
        });

        return maxId + 1;
    },

    /**
     * add txt tag to table
     * @returns when no tag is provided or required dom elements dont exist
     */
    addTxtTag(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const tagInput = slug.container.querySelector(`#txt-tag-content-input-${feedSlug}`);
        const purposeInput = slug.container.querySelector(`#txt-tag-purpose-input-${feedSlug}`);

        if (!tagInput) {
            showError('Tag input not found!', `txt-tag-error-${feedSlug}`);
            return;
        }

        const tag = cleanHTMLTags(tagInput.value);
        const purpose = cleanHTMLTags(purposeInput?.value) || '';

        if (!tag) {
            showError('Tag content is required', `txt-tag-error-${feedSlug}`);
            return;
        }

        const txtTagData = {
            id: this.getNextId(feedSlug),
            tag: tag,
            purpose: purpose
        };

        this.addTxtTagRow(txtTagData, feedSlug);
        if (slug.isEditMode) slug.isEditMode = false;
        this.clearForm(feedSlug);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * creates a new txt tag row via template and appends to existing table
     * @param {Object} txtTagData - new txt tag data
     * @returns when required dom elements dont exist
     */
    addTxtTagRow(txtTagData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug || !slug._tbody) return;

        const rowTemplate = slug.container.querySelector(`#txt-tag-row-template-${feedSlug}`);
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        const id = txtTagData.id;

        node.dataset.txtTagId = id;
        node.querySelector('[data-action="edit-txt-tag"]').dataset.txtTagId = id;
        node.querySelector('[data-action="remove-txt-tag"]').dataset.txtTagId = id;

        // display only first 64 chars on table, internally can be up to 4k chars
        const displayText = txtTagData.tag.length > 64 ?
            txtTagData.tag.substring(0, 61) + '...'
            : txtTagData.tag;

        node.querySelector('[data-cell="tag"] div').textContent = displayText;
        node.querySelector('[data-cell="tag"] div').title = txtTagData.tag; // show full text on hover using title

        // truncate purpose to 32 chars for display
        const purposeText = txtTagData.purpose || '-';
        const displayPurpose = purposeText.length > 32 ?
            purposeText.substring(0, 29) + '...'
            : purposeText;

        node.querySelector('[data-cell="purpose"]').textContent = displayPurpose;

        // update hidden inputs to match id for saving
        node.querySelectorAll('input[type="hidden"]').forEach(input => {
            input.name = input.name.replace('__ID__', id);
            if (/\[tag\]$/.test(input.name))
                input.value = txtTagData.tag;
            else if (/\[purpose\]$/.test(input.name))
                input.value = txtTagData.purpose || '';
        });

        slug._tbody.appendChild(node);
    },

    /**
     * extracts row from button id and removes row
     * @param {HTMLElement} button - clicked button
     */
    removeTxtTag(button, feedSlug) {
        const txtTagRow = button.closest('tr[data-txt-tag-id]');
        if (txtTagRow) {
            txtTagRow.remove();
            this.updateTableDisplay(feedSlug);
        }
    },

    /**
     * update table display based on state of existing rows
     */
    updateTableDisplay(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const table = slug.tableContainer || slug.container.querySelector(`#txt-tag-table-container-${feedSlug}`);
        const tbody = slug._tbody || slug.tableContainer.querySelector('tbody');
        const msg = slug.container.querySelector(`#txt-tag-table-message-${feedSlug}`);

        // show empty table message if no rows
        if (tbody.querySelectorAll('tr[data-txt-tag-id]').length === 0) {
            if (table) table.style.display = 'none';
            if (msg) msg.style.display = 'block';
        } else {
            if (table) table.style.display = 'block';
            if (msg) msg.style.display = 'none';
        }
    },

    /**
     * clear input form data and reset form state
     */
    clearForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        slug.container.querySelector(`#txt-tag-content-input-${feedSlug}`).value = '';
        slug.container.querySelector(`#txt-tag-purpose-input-${feedSlug}`).value = '';
        hideError(`txt-tag-error-${feedSlug}`);
    },
};

export function initTxtTagManager(feedSlug) {
    TxtTagManager.init(feedSlug);
}

export { TxtTagManager };
