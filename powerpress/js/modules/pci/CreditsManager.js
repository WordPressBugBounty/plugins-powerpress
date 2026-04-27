/**
 * Credit Management system for Podcast2.0 Tag:
 * documentation: https://podcasting2.org/docs/podcast-namespace/tags/person
 * Tag: <podcast:person>
 * Main Content: Name
 *       Supported Attributes:
 *          - role: (optional) Used to identify what role the person serves on the show or episode.
 *                             This should be a reference to an official role within the Podcast Taxonomy Project list (see below).
 *                             - If role is missing then "host" is assumed. -
 *          - img: (optional) This is the url of a picture or avatar of the person.
 *          - href: (optional) The url to a relevant resource of information about the person, such as a homepage or third-party profile platform.
 */

import { showError, hideError } from '../../utils/dom-utils.js';
import { cleanHTMLTags, renderSafeLink } from '../../utils/html-utils.js';

const CreditsManager = {
    feedSlugs: new Map(),

    getFeedSlug(feedSlug, containerElement) {
        if (!this.feedSlugs.has(feedSlug)) {
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
     * -> setup credits containers
     * -> setup event handling on the container
     * -> update display
     */
    init(feedSlug) {
        const container = document.getElementById(`credit-role-container-${feedSlug}`);
        if (!container) return;

        const slug = this.getFeedSlug(feedSlug, container);

        slug.tableContainer = container.querySelector(`#credit-table-container-${feedSlug}`);
        slug._tbody = slug.tableContainer?.querySelector('tbody') || null;

        container.addEventListener('click', (e) => this.onClick(e, feedSlug));
        container.addEventListener('change', (e) => this.onChange(e, feedSlug));

        // form 'Enter' handling
        const formContainer = container.querySelector(`#credit-form-container-${feedSlug}`);
        if (formContainer) {
            formContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.addCredit(feedSlug);
                    return false;
                }
            });
        }

        this.updateTableDisplay(feedSlug);
    },

    /**
     * click event handler for the Credit Manager container
     * @param {Event} e - click event
     * @returns early failure when the element that clicked wasnt from this container
     */
    onClick(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'add-credit':
                e.preventDefault();
                this.addCredit(feedSlug);
                break;
            case 'remove-credit':
                e.preventDefault();
                this.removeCredit(el, feedSlug);
                break;
            case 'edit-credit':
                e.preventDefault();
                this.editCredit(el, feedSlug);
                break;
        }
    },

    /**
     * change handler for Credit Manager
     * @param {Event} e - change event
     */
    onChange(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'inherit-credits':
                e.preventDefault();
                this.toggleInheritedCreditsPreview(feedSlug);
                break;
        }
    },

    /**
     * handler for editing an existing tag
     * @param {HTMLElement} button - edit button
     * @returns when row doesnt exist
     */
    editCredit(button, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const row = button.closest('tr[data-credit-id]');
        if (!row) return;

        const existingData = this.extractCreditDataFromRow(row);
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
    extractCreditDataFromRow(row) {
        const creditId = parseInt(row.dataset.creditId);

        return {
            id: creditId,
            name: row.querySelector(`input[name*="[name]"]`)?.value,
            role: row.querySelector(`input[name*="[role]"]`)?.value || '',
            person_url: row.querySelector(`input[name*="[person_url]"]`)?.value || '',
            link_url: row.querySelector(`input[name*="[link_url]"]`)?.value || '',
        };
    },

    /**
     * populates form with the existingData
     * @param {Object} existingData - txt tag object from row
     */
    updateFormEdit(existingData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        slug.container.querySelector(`#credit-name-input-${feedSlug}`).value = existingData.name || '';
        slug.container.querySelector(`#credit-role-select-${feedSlug}`).value = existingData.role || '';
        slug.container.querySelector(`#credit-person-url-input-${feedSlug}`).value = existingData.person_url || '';
        slug.container.querySelector(`#credit-link-url-input-${feedSlug}`).value = existingData.link_url || '';
    },

    /**
     * add credit to collection on click then call render
     * -> grab form information
     * -> clean input
     * -> add credit to Manager's Credits array and re-render
     */
    addCredit(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // grab form inputs
        const nameInput = slug.container.querySelector(`#credit-name-input-${feedSlug}`);
        const roleSelect = slug.container.querySelector(`#credit-role-select-${feedSlug}`);
        const personUrlInput = slug.container.querySelector(`#credit-person-url-input-${feedSlug}`);
        const linkUrlInput = slug.container.querySelector(`#credit-link-url-input-${feedSlug}`);

        if (!nameInput || !roleSelect) {
            showError('Form inputs not found', `credit-error-${feedSlug}`);
            return;
        }

        // clean input
        const name = cleanHTMLTags(nameInput.value);
        const role = cleanHTMLTags(roleSelect.value);
        const personUrl = cleanHTMLTags(personUrlInput.value) || '';
        const linkUrl = cleanHTMLTags(linkUrlInput.value) || '';

        // verify required field
        if (!name) {
            showError('Name is required', `credit-error-${feedSlug}`);
            return;
        }

        // setup new credit
        const newCredit = {
            id: this.getNextId(feedSlug),
            name: name,
            role: role,
            personUrl: personUrl,
            linkUrl: linkUrl
        };

        // add to credits array + reset + rerender
        this.addCreditRow(newCredit, feedSlug);
        this.clearForm(feedSlug);
        if (slug.isEditMode) slug.isEditMode = false;
        this.updateTableDisplay(feedSlug);
    },

    /**
     * append new row into table container
     */
    addCreditRow(creditData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // setup template
        const rowTemplate = slug.container.querySelector(`#credit-row-template-${feedSlug}`);
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        const id = creditData.id;

        node.dataset.creditId = id;
        node.querySelector('[data-action="remove-credit"]').dataset.creditId = id;

        node.querySelector('[data-cell="name"]').textContent = creditData.name;
        node.querySelector('[data-cell="role"]').textContent = creditData.role;

        // setup urls for display
        renderSafeLink(node.querySelector('[data-cell="personUrl"]'), creditData.personUrl);
        renderSafeLink(node.querySelector('[data-cell="linkUrl"]'), creditData.linkUrl);

        // update hidden inputs for saving
        node.querySelectorAll('input[type="hidden"]').forEach(input => {
            input.name = input.name.replace('__ID__', id);
            if (/name]$/.test(input.name))
                input.value = creditData.name;
            else if (/role]$/.test(input.name))
                input.value = creditData.role;
            else if (/person_url]$/.test(input.name))
                input.value = creditData.personUrl || '';
            else if (/link_url]$/.test(input.name))
                input.value = creditData.linkUrl || '';
        });

        slug._tbody.appendChild(node);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * remove credit row on click
     * @param {HTMLElement} button - remove button
     */
    removeCredit(button, feedSlug) {
        const creditRow = button.closest('tr[data-credit-id]');
        if (!creditRow) return;
        creditRow.remove();
        this.updateTableDisplay(feedSlug);
    },

    /**
     * parses the highest current id and increments on that to avoid overlap
     * @returns {number}
     */
    getNextId(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        const existingCredits = slug.container.querySelectorAll(`tr[data-credit-id]`);
        let maxId = 0;

        existingCredits.forEach(credit => {
            const id = parseInt(credit.dataset.creditId);
            if (id > maxId) maxId = id;
        });

        return maxId + 1;
    },

    /**
     * show status message based on the length of rows
     */
    updateTableDisplay(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const table = slug.tableContainer || slug.container.querySelector(`#credit-table-container-${feedSlug}`);
        const tbody = slug._tbody || slug.tableContainer?.querySelector('tbody');
        const msg = slug.container.querySelector(`#credit-table-message-${feedSlug}`);

        // if table is empty show empty table message
        if (tbody.querySelectorAll('tr[data-credit-id]').length === 0) {
            if (table) table.style.display = 'none';
            if (msg) msg.style.display = 'block';
        } else {
            if (msg) msg.style.display = 'none';
            if (table) table.style.display = 'block';
        }
    },

    /**
     * toggle visibility of show level credits based on checkbox state
     * @returns if the dom elements dont exist
     */
    toggleInheritedCreditsPreview(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const inheritCheckbox = slug.container.querySelector(`#inherit-channel-credits-${feedSlug}`);
        const previewDiv = slug.container.querySelector(`#inherited-credits-preview-${feedSlug}`);

        if (!inheritCheckbox || !previewDiv) return;

        // show/hide based on checkbox state
        previewDiv.style.display = inheritCheckbox.checked ? 'block' : 'none';
    },

    /**
     * clear data from input form
     */
    clearForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const nameInput = slug.container.querySelector(`#credit-name-input-${feedSlug}`);
        const roleSelect = slug.container.querySelector(`#credit-role-select-${feedSlug}`);
        const personUrlInput = slug.container.querySelector(`#credit-person-url-input-${feedSlug}`);
        const linkUrlInput = slug.container.querySelector(`#credit-link-url-input-${feedSlug}`);

        if (nameInput) nameInput.value = '';
        if (roleSelect) roleSelect.value = 'Guest';
        if (personUrlInput) personUrlInput.value = '';
        if (linkUrlInput) linkUrlInput.value = '';

        hideError(`credit-error-${feedSlug}`);
    },
};

export function initCreditsManager(feedSlug) {
    CreditsManager.init(feedSlug);
}

export { CreditsManager };
