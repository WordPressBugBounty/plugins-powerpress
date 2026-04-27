/**
 * Alternate Enclosure Management system for Podcast2.0 Tag:
 * documentation: https://podcasting2.org/docs/podcast-namespace/tags/alternate-enclosure
 * Tag: <podcast:alternateEnclosure>
 *      Main Node: <podcast:source> tags which define a uri where the media file can be downloaded or streamed
 *                 (OPTIONAL) <podcast:integrity> to allow for file integrity checking // TODO!!!
 *       Supported Attributes:
 *          - type: (required) Mime type of the media asset.
 *          - length: (recommended) Length of the file in bytes.
 *          - bitrate: (optional) Average encoding bitrate of the media asset, expressed in bits per second.
 *          - height: (optional) Height of the media asset for video formats.
 *          - lang: (optional) An IETF language tag (BCP 47) code identifying the language of this media.
 *          - title: (optional) A human-readable string identifying the name of the media asset. Should be limited to 32 characters for UX.
 *          - rel: (optional) Provides a method of offering and/or grouping together different media elements. If not set, or set to "default",
 *                  the media will be grouped with the enclosure and assumed to be an alternative to the enclosure's encoding/transport. This attribute can
 *                  and should be the same for items with the same content encoded by different means. Should be limited to 32 characters for UX.
 *          - codecs: (optional) An RFC 6381 string specifying the codecs available in this media.
 *          - default: (optional) Boolean specifying whether or not the given media is the same as the file from the enclosure element and should be the
 *                     preferred media element. The primary reason to set this is to offer alternative transports for the enclosure. If not set, this should be
 *                     assumed to be false.
 */

import { showError, hideError } from '../../utils/dom-utils.js';
import { cleanHTMLTags, renderSafeLink } from '../../utils/html-utils.js';

const AlternateEnclosureManager = {
    feedSlugs: new Map(),
    hasHosting: false,
    adminUrl: '',

    getFeedSlug(feedSlug, containerElement) {
        if (!this.feedSlugs.has(feedSlug)) {
            const slug = {
                feedSlug: feedSlug,
                container: containerElement,
                formContainer: null,
                tableContainer: null,
                _tbody: null,
                currentEnclosureData: null,
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
        const container = document.getElementById(`alternate-enclosure-container-${feedSlug}`);
        if (!container) return;

        const slug = this.getFeedSlug(feedSlug, container);

        slug.formContainer = container.querySelector(`#alternate-enclosure-form-container-${feedSlug}`);
        slug.tableContainer = container.querySelector(`#alternate-enclosure-table-container-${feedSlug}`);
        slug._tbody = slug.tableContainer?.querySelector('tbody') || null;

        // check hosting + admin url
        this.hasHosting = container?.dataset?.hasHosting === '1';
        this.adminUrl = container?.dataset?.adminUrl || '';

        container.addEventListener('click', (e) => this.onClick(e, feedSlug));

        // form 'Enter' handling
        const initializerContainer = container.querySelector(`#alt-enclosure-url-form-${feedSlug}`);
        const confirmationContainer = container.querySelector(`#alt-enclosure-details-form-${feedSlug}`);
        if (initializerContainer) {
            initializerContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.checkAlternateEnclosure(feedSlug);
                    return false;
                }
            });
        }

        if (confirmationContainer) {
            confirmationContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.confirmAlternateEnclosure(feedSlug);
                    return false;
                }
            });
        }

        this.updateTableDisplay(feedSlug);
    },

    /**
     * click handler for alternate enclosure container
     * @param {Event} e - click event
     * @returns when dom element doesnt exist
     */
    onClick(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'check-alt-enclosure':
                e.preventDefault();
                this.checkAlternateEnclosure(feedSlug);
                break;
            case 'confirm-alt-enclosure':
                e.preventDefault();
                this.confirmAlternateEnclosure(feedSlug);
                break;
            case 'cancel-alt-enclosure':
                e.preventDefault();
                this.cancelAlternateEnclosure(feedSlug);
                break;
            case 'remove-alt-enclosure':
                e.preventDefault();
                this.removeAlternateEnclosure(el, feedSlug);
                break;
            case 'toggle-enclosure-details':
                e.preventDefault();
                this.toggleEnclosureDetails(el, feedSlug);
                break;
            case 'add-uri-input':
                e.preventDefault();
                this.addUriInput('', feedSlug);
                break;
            case 'remove-uri-input':
                e.preventDefault();
                this.removeUriInput(el);
                break;
            case 'edit-alt-enclosure':
                e.preventDefault();
                this.editAlternateEnclosure(el, feedSlug);
                break;
        }
    },

    /**
     * sets up detailed form after checking initial uri input
     * @returns when required DOM element doesnt exist
     */
    checkAlternateEnclosure(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const urlInput = slug.container.querySelector(`#alt-enclosure-url-input-${feedSlug}`);
        if (!urlInput) return;

        const url = cleanHTMLTags(urlInput.value);
        // fail when no url provided
        if (!url) {
            showError('Media URL is required', `alternate-enclosure-error-${feedSlug}`);
            return;
        }

        const enclosureData = {
            id: slug.isEditMode ? slug.currentEnclosureData.id : this.getNextId(feedSlug),
            url: url,
            title: '',
            bitrate: '',
            height: '',
            length: '',
            rel: '',
            lang: '',
            codecs: '',
            is_default: false,
            uris: [],
            hosting: slug.container.querySelector(`#alt-enclosure-url-input-${feedSlug}`)?.dataset.hosting || '0',
            program_keyword: slug.container.querySelector(`#alt-enclosure-url-input-${feedSlug}`)?.dataset.programKeyword || ''
        };
        slug.currentEnclosureData = enclosureData;
        this.showDetailsForm(slug.currentEnclosureData, feedSlug);
    },

    /**
     * accepts detailed form data and add alternate enclosure data to table
     * @returns when required dom element dont exist
     */
    confirmAlternateEnclosure(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const formData = this.extractConfirmationFormData(feedSlug);
        if (!formData) return;

        if (slug.isEditMode) {
            slug.isEditMode = false;
        }

        this.addAlternateEnclosureRow(formData, feedSlug);
        this.showUrlForm(feedSlug);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * cancels detailed form data and reverts to uri input form
     */
    cancelAlternateEnclosure(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        if (slug.isEditMode) {
            this.addAlternateEnclosureRow(slug.currentEnclosureData, feedSlug);
            slug.isEditMode = false;
        }
        this.showUrlForm(feedSlug);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * setup for extracting data from row and populating detailed form with existing data
     * @param {HTMLElement} button - edit button clicked
     * @returns when required DOM elements dont exist
     */
    editAlternateEnclosure(button, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const row = button.closest('tr[data-alt-enclosure-id]');
        if (!row) return;

        const existingData = this.extractEnclosureDataFromRow(row);
        slug.isEditMode = true;
        slug.currentEnclosureData = existingData;
        this.showDetailsForm(existingData, feedSlug);
        this.removeAlternateEnclosure(button, feedSlug);
    },

    /**
     * extracts data from hidden inputs of row
     * @param {HTMLElement} row - existing alternate enclosure row with data
     * @returns alternate enclosure row data
     */
    extractEnclosureDataFromRow(row) {
        const enclosureId = row.dataset.altEnclosureId;

        return {
            id: parseInt(enclosureId),
            url: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][url]"]`)?.value || '',
            title: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][title]"]`)?.value || '',
            bitrate: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][bitrate]"]`)?.value || '',
            height: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][height]"]`)?.value || '',
            length: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][length]"]`)?.value || '',
            rel: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][rel]"]`)?.value || '',
            lang: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][lang]"]`)?.value || '',
            codecs: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][codecs]"]`)?.value || '',
            is_default: row.querySelector(`input[name*="[alternate_enclosure][${enclosureId}][default]"]`)?.value === '1',
            uris: this.extractUrisFromRow(row)
        };
    },

    /**
     * extracts data from uri hidden input elements
     * @param {HTMLElement} row - existing alternate enclosure row with data
     * @returns URI data
     */
    extractUrisFromRow(row) {
        const uris = [];
        const uriInputs = row.querySelectorAll(`input[data-field="alt-enc-save-uri"]`);
        uriInputs.forEach(input => {
            const v = input.value.trim();
            if (v) {
                const hostingInput = row.querySelector(`input[data-field="alt-enc-uri-hosting"][data-uri-index="${input.dataset.uriIndex}"]`);
                uris.push({
                    uri: v,
                    hosting: hostingInput ? hostingInput.value : ''
                });
            }
        });
        return uris;
    },

    /**
     * extracts existing detailed form data and returns as object
     * @returns nothing when data doesnt exist, updated data when it does
     */
    extractConfirmationFormData(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug || !slug.currentEnclosureData) return null;

        const title = cleanHTMLTags(document.getElementById(`confirm-alt-title-${feedSlug}`)?.value) || '';
        const bitrate = cleanHTMLTags(document.getElementById(`confirm-alt-bitrate-${feedSlug}`)?.value) || '';
        const height = cleanHTMLTags(document.getElementById(`confirm-alt-height-${feedSlug}`)?.value) || '';
        const lang = cleanHTMLTags(document.getElementById(`confirm-alt-lang-${feedSlug}`)?.value) || '';
        const rel = cleanHTMLTags(document.getElementById(`confirm-alt-rel-${feedSlug}`)?.value) || '';
        const codecs = cleanHTMLTags(document.getElementById(`confirm-alt-codecs-${feedSlug}`)?.value) || '';
        const is_default = document.getElementById(`confirm-alt-default-${feedSlug}`)?.checked || false;

        const uris = [];
        const uriInputs = slug.container.querySelectorAll('[data-field="alt-enc-uri-input"]');
        uriInputs.forEach(input => {
            if (input.value.trim()) {
                const hostingField = slug.container.querySelector(`#${input.id}-hosting`);
                uris.push({
                    uri: cleanHTMLTags(input.value),
                    hosting: hostingField ? hostingField.value : ''
                });
            }
        });

        const hosting = slug.container.querySelector(`#alt-enclosure-details-form-${feedSlug}`).dataset.hosting;
        const program_keyword = slug.container.querySelector(`#alt-enclosure-details-form-${feedSlug}`).dataset.programKeyword;

        return {
            ...slug.currentEnclosureData,
            title,
            bitrate,
            height,
            lang,
            rel,
            codecs,
            is_default,
            uris,
            hosting,
            program_keyword
        };
    },

    /**
     * reset main form and display
     */
    showUrlForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        slug.container.querySelector(`#alt-enclosure-url-form-${feedSlug}`).style.display = 'block';
        slug.container.querySelector(`#alt-enclosure-details-form-${feedSlug}`).style.display = 'none';
        this.clearUrlForm(feedSlug);
        hideError(`alternate-enclosure-error-${feedSlug}`);
        slug.currentEnclosureData = null;
        slug.isEditMode = false;
    },

    /**
     * display detailed form with provided enclosureData
     * @param {Object} enclosureData - alternate enclosure data
     */
    showDetailsForm(enclosureData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        slug.container.querySelector(`#confirm-alt-title-${feedSlug}`).value = enclosureData.title || '';
        slug.container.querySelector(`#confirm-alt-bitrate-${feedSlug}`).value = enclosureData.bitrate || '';
        slug.container.querySelector(`#confirm-alt-height-${feedSlug}`).value = enclosureData.height || '';
        slug.container.querySelector(`#confirm-alt-lang-${feedSlug}`).value = enclosureData.lang || '';
        slug.container.querySelector(`#confirm-alt-rel-${feedSlug}`).value = enclosureData.rel || '';
        slug.container.querySelector(`#confirm-alt-codecs-${feedSlug}`).value = enclosureData.codecs || '';

        const urlDisplay = slug.container.querySelector(`#alt-enclosure-url-display-${feedSlug}`);
        if (urlDisplay) {
            urlDisplay.textContent = `URL: ${enclosureData.url.trim()}`;
        }

        this.clearUriInputs(feedSlug);
        if (enclosureData.uris && enclosureData.uris.length > 0) {
            enclosureData.uris.forEach(uriArr => {
                if (uriArr.uri.trim() !== '')
                    this.addUriInput(uriArr.uri, feedSlug);
            });
        }

        slug.container.querySelector(`#alt-enclosure-details-form-${feedSlug}`).dataset.hosting = enclosureData.hosting || '0';
        slug.container.querySelector(`#alt-enclosure-details-form-${feedSlug}`).dataset.programKeyword = enclosureData.program_keyword || '';

        slug.container.querySelector(`#alt-enclosure-url-form-${feedSlug}`).style.display = 'none';
        slug.container.querySelector(`#alt-enclosure-details-form-${feedSlug}`).style.display = 'block';
        hideError(`alternate-enclosure-error-${feedSlug}`);
    },

    /**
     * adds uri to detailed form, also used for populating on edit
     * @param {string} presetValue - existing value for existing uri
     * @returns
     */
    addUriInput(presetValue = '', feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const container = slug.container.querySelector(`#uri-inputs-container-${feedSlug}`);
        const template = slug.container.querySelector(`#uri-input-template-${feedSlug}`);
        if (!container || !template) return;

        const node = template.content.cloneNode(true);
        const input = node.querySelector('[data-field="alt-enc-uri-input"]');
        if (!input) return;

        const enclosureId = slug.currentEnclosureData?.id || this.getNextId(feedSlug);
        const nextIdx = this.getNextUriIndex(enclosureId, feedSlug);

        // setup for powerpress-jquery-media action
        const targetId = `alt-enc-uri-${enclosureId}-${nextIdx}`;
        input.id = targetId;
        if (presetValue) input.value = presetValue;

        const hostingField = node.querySelector('[data-field="alt-enc-uri-hosting"]');
        if (hostingField) {
            hostingField.id = `${targetId}-hosting`;
        }
        const programKeywordField = node.querySelector('[data-field="alt-enc-uri-program-keyword"]');
        if (programKeywordField) {
            programKeywordField.id = `${targetId}-program-keyword`
        }

        // URI Choose File from Blubrry
        const pickLink = node.querySelector('[data-action="pick-blubrry-uri"]');
        if (this.hasHosting && pickLink) {
            const href = `${this.adminUrl}?action=powerpress-jquery-media` +
                `&podcast-feed=${encodeURIComponent(feedSlug)}` +
                `&target_field=${encodeURIComponent(targetId)}` +
                `&KeepThis=true&TB_iframe=true&modal=false`;
            pickLink.href = href;
            pickLink.style.display = 'inline-block'
        }

        container.appendChild(node);
    },

    /**
     * extract row data from pressed button and remove row
     * @param {HTMLElement} button - remove button pressed
     */
    removeUriInput(button) {
        const uriRow = button.closest('.uri-input-row');
        if (uriRow) {
            uriRow.remove();
        }
    },

    /**
     * clear uri input by overwriting innerHTML
     */
    clearUriInputs(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const uriInput = slug.container.querySelector(`#uri-inputs-container-${feedSlug}`);
        if (uriInput) {
            uriInput.innerHTML = '';
        }
    },

    /**
     * toggle row details element and button appearance
     * @param {HTMLElement} button - detailed view button
     */
    toggleEnclosureDetails(button, feedSlug) {
        const enclosureId = button.dataset.altEnclosureId;
        const detailRow = document.querySelector(`tr[data-detail-for="${enclosureId}"][data-feed-slug="${feedSlug}"]`);
        const expandBtn = button;

        if (detailRow) {
            const isVisible = detailRow.style.display !== 'none';
            detailRow.style.display = isVisible ? 'none' : 'table-row';
            expandBtn.textContent = isVisible ? '▼' : '▲';
        }
    },

    /**
     * generate new HTML row from template, populate data and append new element to table
     * @param {Object} enclosureData - alternate enclosure object
     * @returns
     */
    addAlternateEnclosureRow(enclosureData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug || !slug._tbody) return;

        const mainTemplate = slug.container.querySelector(`#alt-enclosure-row-template-${feedSlug}`);
        const detailTemplate = slug.container.querySelector(`#alt-enclosure-detail-template-${feedSlug}`);

        const mainNode = mainTemplate.content.firstElementChild.cloneNode(true);
        const detailNode = detailTemplate.content.firstElementChild.cloneNode(true);

        const id = enclosureData.id;

        // setup main row data attributes
        mainNode.dataset.altEnclosureId = id;
        mainNode.querySelector('[data-action="toggle-enclosure-details"]').dataset.altEnclosureId = id;
        mainNode.querySelector('[data-action="edit-alt-enclosure"]').dataset.altEnclosureId = id;
        mainNode.querySelector('[data-action="remove-alt-enclosure"]').dataset.altEnclosureId = id;

        // populate main row cells
        renderSafeLink(mainNode.querySelector('[data-cell="url"]'), enclosureData.url, true);

        mainNode.querySelector('[data-cell="title"]').textContent = cleanHTMLTags(enclosureData.title) || '—';
        mainNode.querySelector('[data-cell="bitrate"]').textContent = (enclosureData.bitrate && enclosureData.bitrate !== '0') ? cleanHTMLTags(enclosureData.bitrate) : '—';
        mainNode.querySelector('[data-cell="height"]').textContent = (enclosureData.height && enclosureData.height !== '0') ? cleanHTMLTags(enclosureData.height) : '—';
        mainNode.querySelector('[data-cell="lang"]').textContent = cleanHTMLTags(enclosureData.lang) || '—';
        mainNode.querySelector('[data-cell="default"]').textContent = cleanHTMLTags(enclosureData.is_default) ? '✓' : '—';

        // update hidden inputs w ID replacements
        mainNode.querySelectorAll('input[type="hidden"][name*="Powerpress["]').forEach(input => {
            if (input.name.includes('[__ID__]')) {
                input.name = input.name.replace('__ID__', id);
            }
            if (input.id) {
                input.id = input.id.replace('__ID__', id);
            }

            let val = '';
            if (/url\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.url);
            else if (/title\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.title) || '';
            else if (/bitrate\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.bitrate) || '';
            else if (/height\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.height) || '';
            else if (/length\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.length) || '';
            else if (/rel\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.rel) || '';
            else if (/lang\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.lang) || '';
            else if (/codecs\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.codecs) || '';
            else if (/hosting\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.hosting) || '';
            else if (/program_keyword\]$/.test(input.name))
                val = cleanHTMLTags(enclosureData.program_keyword) || '';

            input.value = val;
            input.setAttribute('value', val);
        });

        if (enclosureData.is_default) {
            const hiddenCell = slug.container.querySelector(`[data-field="save-data"]`);
            const def = document.createElement('input');
            def.type = 'hidden';
            def.name = `Powerpress[${feedSlug}][alternate_enclosure][${id}][default]`;
            def.value = '1';
            def.setAttribute('value', '1');
            hiddenCell.appendChild(def);
        }

        // setup detail row
        detailNode.dataset.detailFor = id;
        detailNode.querySelector('[data-cell="rel"]').textContent = cleanHTMLTags(enclosureData.rel) || 'N/A';
        detailNode.querySelector('[data-cell="codecs"]').textContent = cleanHTMLTags(enclosureData.codecs) || 'N/A';
        detailNode.querySelector('[data-cell="length"]').textContent = cleanHTMLTags(enclosureData.length) || 'N/A';
        detailNode.querySelector('[data-cell="url-display"]').textContent = cleanHTMLTags(enclosureData.url) || '—';

        // handle URIs
        const urisContainer = detailNode.querySelector('[data-cell="uris"]');
        urisContainer.replaceChildren();

        if (enclosureData.uris && enclosureData.uris.length > 0) {
            for (const uriData of enclosureData.uris) {
                const uri = (typeof uriData === 'string') ? uriData : uriData.uri;
                const row = document.createElement('div');
                row.style.fontSize = '12px';
                row.style.color = '#666';
                row.style.marginBottom = '4px';

                renderSafeLink(row, uri, true, false);
                urisContainer.appendChild(row);
            }

            // add hidden URI inputs
            enclosureData.uris.forEach((uriData, index) => {
                const uri = (typeof uriData === 'string') ? uriData : uriData.uri;
                const hosting = (typeof uriData === 'string') ? '' : uriData.hosting;
                const uriIndex = index + 1;
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.setAttribute('data-field', 'alt-enc-save-uri');
                hiddenInput.setAttribute('data-alt-enc-idx', id);
                hiddenInput.setAttribute('data-uri-index', uriIndex);
                hiddenInput.name = `Powerpress[${feedSlug}][alternate_enclosure][${id}][uris][${uriIndex}][uri]`;
                hiddenInput.value = cleanHTMLTags(uri);
                hiddenInput.setAttribute('value', uri);

                const hiddenHosting = document.createElement('input');
                hiddenHosting.type = 'hidden';
                hiddenHosting.setAttribute('data-field', 'alt-enc-uri-hosting');
                hiddenHosting.setAttribute('data-alt-enc-idx', id);
                hiddenHosting.setAttribute('data-uri-index', uriIndex);
                hiddenHosting.name = `Powerpress[${feedSlug}][alternate_enclosure][${id}][uris][${uriIndex}][hosting]`;

                hiddenHosting.value = hosting;
                hiddenHosting.setAttribute('value', hosting);

                mainNode.querySelector('td[data-field="save-data"]').appendChild(hiddenInput);
                mainNode.querySelector('td[data-field="save-data"]').appendChild(hiddenHosting);
            });
        }

        slug._tbody.appendChild(mainNode);
        slug._tbody.appendChild(detailNode);
    },

    /**
     * remove alternate enclosure row using data extracted from remove button
     * @param {HTMLElement} button - remove button
     */
    removeAlternateEnclosure(button, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const enclosureId = button.dataset.altEnclosureId;
        const mainRow = button.closest('tr[data-alt-enclosure-id]');
        const detailRow = slug.container.querySelector(`tr[data-detail-for="${enclosureId}"][data-feed-slug="${feedSlug}"]`);

        if (mainRow) mainRow.remove();
        if (detailRow) detailRow.remove();

        this.updateTableDisplay(feedSlug);
    },

    /**
     * update table display based on existing row state
     */
    updateTableDisplay(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const table = slug.tableContainer || slug.container.querySelector(`#alternate-enclosure-table-container-${feedSlug}`);
        const tbody = slug._tbody || slug.tableContainer?.querySelector('tbody');
        const msg = slug.container.querySelector(`#alternate-enclosure-table-message-${feedSlug}`);

        if (tbody.querySelectorAll('tr[data-alt-enclosure-id]').length === 0) {
            if (table) table.style.display = 'none';
            if (msg) msg.style.display = 'block';
        } else {
            if (table) table.style.display = 'block';
            if (msg) msg.style.display = 'none';
        }
    },

    /**
     * clear data from url form
     */
    clearUrlForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const urlInput = slug.container.querySelector(`#alt-enclosure-url-input-${feedSlug}`);
        if (urlInput) {
            urlInput.value = '';
        }
        hideError(`alternate-enclosure-error-${feedSlug}`);
    },

    /**
     * get next id by finding next max id value, prevents overlap on delete or when using length
     * @returns next max id
     */
    getNextId(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        const existingEnclosures = slug.container.querySelectorAll(`tr[data-alt-enclosure-id]`);
        let maxId = 0;

        existingEnclosures.forEach(enclosure => {
            const id = parseInt(enclosure.dataset.altEnclosureId);
            if (id > maxId) maxId = id;
        });
        return maxId + 1;
    },

    /**
     * find all matching ids and try to create new maxid based on the existing values
     * @param {string} enclosureId
     * @returns {number}
     */
    getNextUriIndex(enclosureId, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        const container = slug.container.querySelector(`#uri-inputs-container-${feedSlug}`);
        if (!container) return 1;

        const pattern = new RegExp(`^alt-enc-uri-${enclosureId}-(\\d+)$`);
        let maxIdx = 0;

        container.querySelectorAll('[data-field="alt-enc-uri-input"][id]').forEach(input => {
            const match = input.id.match(pattern);
            if (match) {
                const num = parseInt(match[1], 10);
                if (num > maxIdx) maxIdx = num;
            }
        });

        return maxIdx + 1;
    }
};

export function initAlternateEnclosureManager(feedSlug) {
    AlternateEnclosureManager.init(feedSlug);
}

export { AlternateEnclosureManager };
