/**
 * Content Link Management system for Podcast2.0 Tag:
 * documentation: https://podcasting2.org/docs/podcast-namespace/tags/content-link
 * Tag: <podcast:contentLink>
 *      Main Node: Free-form string that explains to the user where this content link points and or the nature of its purpose
 *       Supported Attributes:
 *          - href (required) A string that is the uri pointing to content outside of the applications
 */

import { showError, hideError } from '../../utils/dom-utils.js';
import { cleanHTMLTags, renderSafeLink } from '../../utils/html-utils.js';

const ContentLinksManager = {
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
        const container = document.getElementById(`content-link-container-${feedSlug}`);
        if (!container) return;

        const slug = this.getFeedSlug(feedSlug, container);

        slug.formContainer = container.querySelector(`#content-link-form-${feedSlug}`);
        slug.tableContainer = container.querySelector(`#content-link-table-container-${feedSlug}`);
        slug._tbody = slug.tableContainer?.querySelector('tbody') || null;

        // event handler binding
        container.addEventListener('click', (e) => this.onClick(e, feedSlug));

        // form 'Enter' handling
        const formContainer = container.querySelector(`#content-link-form-container-${feedSlug}`);
        if (formContainer) {
            formContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.addContentLink(feedSlug);
                    return false;
                }
            });
        }

        this.updateTableDisplay(feedSlug);
    },

    /**
     * click event handler for content link container
     * @param {Event} e - click event
     * @returns when required elements dont exist
     */
    onClick(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'add-content-link':
                e.preventDefault();
                this.addContentLink(feedSlug);
                break;
            case 'remove-content-link':
                e.preventDefault();
                this.removeContentLink(el, feedSlug);
                break;
            case 'edit-content-link':
                e.preventDefault();
                this.editContentLink(el, feedSlug);
                break;
        }
    },

    editContentLink(button, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const row = button.closest('tr[data-content-link-id]');
        if (!row) return;

        const existingData = this.extractContentLinkDataFromRow(row);
        slug.isEditMode = true;
        this.updateFormEdit(existingData, feedSlug);

        row.remove();
        this.updateTableDisplay(feedSlug);
    },

    extractContentLinkDataFromRow(row) {
        const tagId = parseInt(row.dataset.contentLinkId);

        return {
            id: tagId,
            url: cleanHTMLTags(row.querySelector(`input[name*="[url]"]`)?.value) || '',
            label: cleanHTMLTags(row.querySelector(`input[name*="[label]"]`)?.value) || ''
        };
    },

    updateFormEdit(existingData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        slug.container.querySelector(`#content-link-url-input-${feedSlug}`).value = existingData.url || '';
        slug.container.querySelector(`#content-link-label-input-${feedSlug}`).value = existingData.label || '';
    },

    /**
     * creates a new content link object linkData and gets a new row added from it
     * @returns when required dom elements dont exist
     */
    addContentLink(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const urlInput = slug.container.querySelector(`#content-link-url-input-${feedSlug}`);
        const labelInput = slug.container.querySelector(`#content-link-label-input-${feedSlug}`);

        if (!urlInput) {
            showError('URL input not found!', `content-link-error-${feedSlug}`);
            return;
        }

        const url = cleanHTMLTags(urlInput.value);
        const label = cleanHTMLTags(labelInput?.value) || '';

        if (!url) {
            showError('URL is required', `content-link-error-${feedSlug}`);
            return;
        }

        try {
            const parsed = new URL(url);
            if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:' && parsed.protocol !== 'at:') {
                showError('URL must use http:// or https:// protocol', `content-link-error-${feedSlug}`);
                return;
            }
        } catch (e) {
            showError('Invalid URL format. Please enter a valid URL. Ensure you include the http(s): protocol.', `content-link-error-${feedSlug}`);
            return;
        }

        const linkData = {
            id: this.getNextId(feedSlug),
            url: url,
            label: label
        };

        this.addContentLinkRow(linkData, feedSlug);
        this.clearForm(feedSlug);
        if (slug.isEditMode) slug.isEditMode = false;
        this.updateTableDisplay(feedSlug);
    },

    /**
     * creates a new content link row from the data provided and a template before appending it to the table container
     * @param {Object} linkData - content link data {id, url, label}
     * @returns when required dom elements dont exist
     */
    addContentLinkRow(linkData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug || !slug._tbody) return;

        // clone node + new node setup
        const rowTemplate = slug.container.querySelector(`#content-link-row-template-${feedSlug}`);
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        const id = linkData.id;

        node.dataset.contentLinkId = id;
        node.querySelector('[data-action="remove-content-link"]').dataset.contentLinkId = id;

        const urlLink = node.querySelector('[data-cell="url"]');
        renderSafeLink(urlLink, linkData.url, false);

        node.querySelector('[data-cell="label"]').textContent = linkData.label || '-';

        node.querySelectorAll('input[type="hidden"]').forEach(input => {
            input.name = input.name.replace('__ID__', id);
            if (/\[url\]$/.test(input.name))
                input.value = linkData.url;
            else if (/\[label\]$/.test(input.name))
                input.value = linkData.label || '';
        });

        slug._tbody.appendChild(node);
    },

    /**
     * removes row based on information derived from button clicked
     * @param {HTMLElement} button - remove button
     */
    removeContentLink(button, feedSlug) {
        const linkRow = button.closest('tr[data-content-link-id]');
        if (linkRow) {
            linkRow.remove();
            this.updateTableDisplay(feedSlug);
        }
    },

    /**
     * update the display of the table based on the state of rows
     */
    updateTableDisplay(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const tableWrap = slug.tableContainer;
        const tbody = slug._tbody;
        const msg = slug.container.querySelector(`#content-link-table-message-${feedSlug}`);

        // display empty table message when no rows
        if (tbody.querySelectorAll('tr[data-content-link-id]').length === 0) {
            if (tableWrap) tableWrap.style.display = 'none';
            if (msg) msg.style.display = 'block';
        } else {
            if (msg) msg.style.display = 'none';
            if (tableWrap) tableWrap.style.display = 'block';
        }
    },

    /**
     * clear main input form of data
     */
    clearForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        slug.container.querySelector(`#content-link-url-input-${feedSlug}`).value = '';
        slug.container.querySelector(`#content-link-label-input-${feedSlug}`).value = '';
        hideError(`content-link-error-${feedSlug}`);
    },

    /**
     * gets next highest id, prevents overlapping issues on delete or when using length
     * @returns next highest id
     */
    getNextId(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        const existingLinks = slug.container.querySelectorAll(`tr[data-content-link-id]`);
        let maxId = 0;

        existingLinks.forEach(link => {
            const id = parseInt(link.dataset.contentLinkId);
            if (id > maxId) maxId = id;
        });

        return maxId + 1;
    },
};

export function initContentLinksManager(feedSlug) {
    ContentLinksManager.init(feedSlug);
}

export { ContentLinksManager };
