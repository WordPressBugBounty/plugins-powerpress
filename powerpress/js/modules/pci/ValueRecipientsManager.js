/**
 * Value Recipient Management system for Podcast2.0 Tag:
 * documentation: https://podcasting2.org/docs/podcast-namespace/tags/value-recipient
 * Tag: <podcast:valueRecipient>
 * Supported Attributes:
 *     - name: (recommended) A free-form string that designates who or what this recipient is.
 *     - customKey: (optional) The name of a custom record key to send along with the payment.
 *     - customValue: (optional) A custom value to pass along with the payment. This is considered the value that belongs to the customKey.
 *     - type: (required) A slug that represents the type of receiving address that will receive the payment.
 *     - address: (required) This denotes the receiving address of the payee.
 *     - split: (required) The number of shares of the payment this recipient will receive.
 *     - fee: (optional) If this attribute is not specified, it is assumed to be false.
 */

import { showError, hideError, showLoading, hideLoading, showInfo } from '../../utils/dom-utils.js';
import { cleanHTMLTags } from '../../utils/html-utils.js';

const ValueRecipientsManager = {
    feedSlugs: new Map(),

    lastApiCall: 0,
    API_RATE_LIMIT_MS: 1000,

    getFeedSlug(feedSlug, containerElement) {
        if (!this.feedSlugs.has(feedSlug)) {
            const slug = {
                feedSlug: feedSlug,
                container: containerElement,
                tableContainer: null,
                _tbody: null,
                pubkeySet: new Set(),
                currentRecipientData: null
            };

            this.feedSlugs.set(feedSlug, slug);
        }
        return this.feedSlugs.get(feedSlug);
    },

    /**
     * initializer for unique feedSlugs
     * -> bind event handlers
     * -> render pre-loaded recipients
     */
    init(feedSlug) {
        const container = document.getElementById(`value-recipient-container-${feedSlug}`)
        if (!container) return;

        const slug = this.getFeedSlug(feedSlug, container);

        slug.tableContainer = container.querySelector(`#value-recipient-table-container-${feedSlug}`);
        slug._tbody = slug.tableContainer?.querySelector('tbody') || null;

        // event handlers
        container.addEventListener('click', (e) => this.onClick(e, feedSlug));
        container.addEventListener('input', (e) => this.onInput(e, feedSlug));
        container.addEventListener('change', (e) => this.onChange(e, feedSlug));

        // form 'Enter' handling
        const initializerContainer = container.querySelector(`#lightning-address-form-${feedSlug}`);
        const confirmationContainer = container.querySelector(`#recipient-confirmation-form-${feedSlug}`);
        if (initializerContainer) {
            initializerContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.checkLightningAddress(feedSlug);
                    return false;
                }
            });
        }
        if (confirmationContainer) {
            confirmationContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.confirmRecipient(feedSlug);
                    return false;
                }
            });
        }

        this.hydratePubkeySet(feedSlug);
        this.updateSplitTotalIndicators(feedSlug);
        this.updateTableDisplay(feedSlug);
        this.toggleInheritedRecipientsPreview(feedSlug);
    },

    /**
     * click handler for Value Recipients Container
     */
    onClick(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'check-lightning':
                e.preventDefault();
                this.checkLightningAddress(feedSlug);
                break;
            case 'confirm-recipient':
                e.preventDefault();
                this.confirmRecipient(feedSlug);
                break;
            case 'cancel-recipient':
                e.preventDefault();
                this.cancelRecipientForm(feedSlug);
                break;
            case 'remove-recipient':
                e.preventDefault();
                this.removeRecipient(el, feedSlug);
                break;
        }
    },

    /**
     * event handler for inputs in Value Recipients Container
     * @param {Event} e - input event
     * @returns
     */
    onInput(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // handle split changes on table
        if (e.target.matches('input[data-field="split"]')) {
            const row = e.target.closest('tr[data-recipient-id]');
            if (!row) return;

            const hiddenSplit = row.querySelector('input[type="hidden"][name$="split]"]');
            if (hiddenSplit) hiddenSplit.value = e.target.value || '';
            this.updateSplitTotalIndicators(feedSlug);
        }
        if (e.target.matches('input[data-field="split-inherited"]')) {
            const row = e.target.closest('tr[data-origin="inherited"]');
            if (!row) return;

            const hiddenSplit = row.querySelector('input[type="hidden"][name$="[split]"]');
            if (hiddenSplit) hiddenSplit.value = e.target.value || '';
            this.updateSplitTotalIndicators(feedSlug);
        }
    },

    /**
     * change handler for Value Recipients Manager
     * @param {Event} e - change event
     * @returns
     */
    onChange(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'inherit-recipients':
                this.toggleInheritedRecipientsPreview(feedSlug);
                break;
        }
    },

    /**
     * toggle visibility of show level credits based on checkbox state
     * @returns if the dom elements dont exist
     */
    toggleInheritedRecipientsPreview(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const inheritCheckbox = slug.container.querySelector(`#inherit-channel-recipients-${feedSlug}`);
        const previewDiv = slug.container.querySelector(`#inherited-recipients-preview-${feedSlug}`);
        if (!inheritCheckbox || !previewDiv) return;

        // show/hide based on checkbox state
        const show = inheritCheckbox.checked;
        previewDiv.style.display = show ? 'block' : 'none';

        const inputs = previewDiv.querySelectorAll('input');
        inputs.forEach(input => {
            if (show) {
                input.disabled = false;
                input.removeAttribute('disabled');
            } else {
                input.disabled = true;
                input.setAttribute('disabled', '');
            }
        });

        this.updateSplitTotalIndicators(feedSlug);
    },

    /**
     * preload pubkeySet to avoid duplicate valueRecipients
     */
    hydratePubkeySet(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const inputs = slug.container.querySelectorAll('input[type="hidden"][name$="pubkey]"]');
        inputs.forEach(input => {
            const val = (input.value || '').trim().toLowerCase();
            if (val) slug.pubkeySet.add(val);
        });
    },

    /**
     * creates recipient form based on detected service,
     * will always allow you to populate the table regardless of search success
     * -> extract lightning address
     * -> check if we support the end point of the provided address
     * -> if supported request from api, else manual entry
     * -> generate form
     */
    async checkLightningAddress(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // extract + clean lightning address to search
        const lightningInput = slug.container.querySelector(`#lightning-address-input-${feedSlug}`);
        if (!lightningInput) return;

        const lightningAddress = cleanHTMLTags(lightningInput.value.trim());
        if (!lightningAddress) {
            showError('Lightning address or name is required', `value-recipient-error-${feedSlug}`);
            return;
        }

        // display loading status
        showLoading(`value-recipient-loading-${feedSlug}`);
        hideError(`value-recipient-error-${feedSlug}`);

        try {
            // check if we support the endpoint
            const detection = this.detectWalletService(lightningAddress);

            // MANUAL FALLBACK FOR ANY UNSUPPORTED ENDPOINT
            if (!detection.supported) {
                showInfo(`${detection.message} Adding as manual entry.`, `value-recipient-error-${feedSlug}`);
            }

            // attempt to request lightning address data from detected api endpoint
            // manual will be bypassed in validation but we can add an if to skip validation for manual if we wanted
            const walletData = await this.validateWalletAddress(detection.service, lightningAddress);

            // create new recipient object
            const newRecipient = {
                id: this.getNextId(feedSlug),
                lightningAddress,
                pubkey: walletData.pubkey,
                customKey: walletData.customKey,
                customValue: walletData.customValue,
                split: 0,
                fee: false
            };

            slug.currentRecipientData = newRecipient;
            this.showConfirmationForm(slug.currentRecipientData, feedSlug);
        } catch (error) {
            hideLoading(`value-recipient-loading-${feedSlug}`);
            showError(error.message, `value-recipient-error-${feedSlug}`);
        }
    },

    /**
     * handler when user submits detailed form
     */
    confirmRecipient(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // grab form data
        const formData = this.extractFormData(feedSlug);
        if (!formData) return;

        // check pubkeySet for duplicate
        const key = (formData.pubkey || '').trim().toLowerCase();
        if (key && slug.pubkeySet.has(key)) {
            showError('That pubkey is already in the list. If you can\'t find it, check the channel recipients table.', `value-recipient-error-${feedSlug}`);
            return;
        }

        // add new row, add pubkey to set
        this.addRecipientRow(formData, feedSlug);
        if (key) slug.pubkeySet.add(key);

        // update form + table display
        this.showLightningAddressForm(feedSlug);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * handler for cancelling detailed form input, resets back to lightning input form
     */
    cancelRecipientForm(feedSlug) {
        this.showLightningAddressForm(feedSlug);
    },

    /**
     * attempts to pull data from form inputs
     */
    extractFormData(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        if (!slug.currentRecipientData) return null;

        // clean input fields of extra spaces
        const pubkey = cleanHTMLTags(slug.container.querySelector(`#confirm-pubkey-${feedSlug}`)?.value);
        const customKey = cleanHTMLTags(slug.container.querySelector(`#confirm-custom-key-${feedSlug}`)?.value) || '';
        const customValue = cleanHTMLTags(slug.container.querySelector(`#confirm-custom-value-${feedSlug}`)?.value) || '';
        const split = parseFloat(cleanHTMLTags(slug.container.querySelector(`#confirm-split-${feedSlug}`)?.value)) || 0;
        const fee = !!slug.container.querySelector(`#confirm-fee-${feedSlug}`)?.checked;

        if (!pubkey) {
            showError('Pubkey required', `value-recipient-error-${feedSlug}`);
            return null;
        }

        return {
            ...slug.currentRecipientData,
            pubkey,
            customKey,
            customValue,
            split,
            fee
        };
    },

    /**
     * sets up new row from recipientData and inserts into table and hidden
     * inputs to ensure data is saved
     * @param {Object} recipientData
     */
    addRecipientRow(recipientData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // grab template and copy
        const rowTemplate = slug.container.querySelector(`#recipient-row-template-${feedSlug}`);
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        const id = recipientData.id;

        // setup new row data
        node.dataset.recipientId = id;
        node.querySelector('[data-action="remove-recipient"]').dataset.recipientId = id;

        // setup data for each cell / data piece
        node.querySelector('[data-cell="lightningAddress"]').textContent = recipientData.lightningAddress;
        node.querySelector('[data-cell="pubkey"]').textContent = recipientData.pubkey ? recipientData.pubkey.substring(0, 20) + '...' : '-';
        node.querySelector('[data-cell="customKey"]').textContent = recipientData.customKey || '-';
        node.querySelector('[data-cell="customValue"]').textContent = recipientData.customValue || '-';
        node.querySelector('[data-cell="split"] input').value = recipientData.split || 0;
        node.querySelector('[data-cell="fee"]').textContent = recipientData.fee ? 'Yes' : 'No';

        // update hidden inputs with form data
        node.querySelectorAll('input[type=hidden]').forEach(input => {
            input.name = input.name.replace('__ID__', id);
            if (/lightning]$/.test(input.name)) input.value = recipientData.lightningAddress;
            else if (/custom_key]$/.test(input.name)) input.value = recipientData.customKey || '';
            else if (/custom_value]$/.test(input.name)) input.value = recipientData.customValue || '';
            else if (/pubkey]$/.test(input.name)) input.value = recipientData.pubkey || '';
            else if (/split]$/.test(input.name)) {
                input.value = recipientData.split || '';
                const tableInput = input.closest('tr').querySelector('[data-field="split"]');
                if (tableInput) tableInput.value = recipientData.split || 0;
            }
            else if (/fee]$/.test(input.name)) input.value = recipientData.fee ? 'true' : 'false';
        });

        slug._tbody.appendChild(node);
        this.updateSplitTotalIndicators(feedSlug);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * remove container by detected Id
     * @param {HTMLElement} button - remove button clicked
     */
    removeRecipient(button, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const recipientContainer = button.closest('tr[data-recipient-id]');
        if (recipientContainer) {
            // remove pubkey from set
            const pkInput = recipientContainer.querySelector('[name$="pubkey]"]');
            const pk = (pkInput?.value || '').trim().toLowerCase();
            if (pk) slug.pubkeySet?.delete(pk);

            console.log('Removing container:', recipientContainer);
            console.log('Hidden inputs in container:', recipientContainer.querySelectorAll('input[type="hidden"]'));

            recipientContainer.remove();
            this.updateSplitTotalIndicators(feedSlug);
            this.updateTableDisplay(feedSlug);
        }
    },

    /**
     * safely parse a new highest value so we have no accidental overlap
     * @returns {number}
     */
    getNextId(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        const existingRecipients = slug.container.querySelectorAll(`tr[data-recipient-id]`);
        let maxId = 0;

        existingRecipients.forEach(recipient => {
            const id = parseInt(recipient.dataset.recipientId);
            if (id > maxId) maxId = id;
        });

        return maxId + 1;
    },

    /**
     * detect wallet service based on input value
     * -> validation checks for name vs lightning address
     * -> if name, return manual
     * -> if lightning, search supported endpoints in WALLET_SERVICES
     * -> if unsupported, return with an additional message
     * @param {string} lightningAddress
     * @returns {{service: string, supported: boolean, message?: string}}
     */
    detectWalletService(lightningAddress) {
        const WALLET_SERVICES = {
            manual: { name: 'manual', displayName: 'Manual Entry' },
            getalby: {
                name: 'alby',
                displayName: 'Alby',
                endpoint: 'https://getalby.com/.well-known/keysend/',
                domains: ['getalby.com', 'alby.com']
            },
            fountain: {
                name: 'fountain',
                displayName: 'Fountain',
                endpoint: 'https://api.fountain.fm/v1/lnurlp/',
                domains: ['fountain.fm']
            }
        };

        // no @ means its a name, return manual
        if (!lightningAddress || !lightningAddress.includes('@')) {
            return { service: 'manual', supported: true };
        }
        // provided an input like @johnnyDoe, return manual
        const domain = lightningAddress.split('@')[1]?.toLowerCase();
        if (!domain) {
            return { service: 'manual', supported: true };
        }
        // search existing wallet services for the specified domain
        for (const [serviceKey, serviceConfig] of Object.entries(WALLET_SERVICES)) {
            if (serviceConfig.domains && serviceConfig.domains.includes(domain)) {
                return { service: serviceKey, supported: true };
            }
        }

        // failed to match to supported endpoint, return manual with message
        return {
            service: 'manual',
            supported: false,
            message: `We don't support @${domain} yet, but if you message support, we can get right on that! For now, you can add this manually.`
        };
    },

    /**
     * called when supported service detected, attempts to get user information from specified domain
     * -> validation checks, auto-return empty object for manual entries
     * -> extract username and search based on serviceType
     * @param {string} serviceType
     * @param {string} lightningAddress
     * @returns {Promise<{pubkey: string, customKey: string, customValue: string}>}
     */
    async validateWalletAddress(serviceType, lightningAddress) {
        // bypass and instant return for manual
        if (serviceType === 'manual') {
            return { pubkey: '', customKey: '', customValue: '' };
        }

        // check if we can even request api data, removes risk of spamming
        if (!this.canMakeApiCall()) {
            throw new Error('Please wait a moment before trying again.');
        }

        // check valid username
        const username = lightningAddress.substring(0, lightningAddress.indexOf('@'));
        if (!username) {
            throw new Error('Invalid address format');
        }

        // SUPPORTED API ENDPOINTS
        const WALLET_SERVICES = {
            getalby: {
                endpoint: 'https://getalby.com/.well-known/keysend/'
            },
            fountain: {
                endpoint: 'https://api.fountain.fm/v1/lnurlp/'
            }
        };

        // some last sanity checks
        const service = WALLET_SERVICES[serviceType];
        if (!service) {
            throw new Error(`Unknown service type: ${serviceType}`);
        }

        // attempt to get data from specified service
        // returns empty data object on failure
        try {
            let url, response, data;

            // alby endpoint
            if (serviceType === 'getalby') {
                url = `${service.endpoint}${encodeURIComponent(username)}`;
                response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`Alby wallet not found for ${username}`);
                }
                data = await response.json();
                return {
                    pubkey: data.pubkey || '',
                    customKey: data.customData?.[0]?.customKey || '',
                    customValue: data.customData?.[0]?.customValue || ''
                };
            }

            // fountain endpoint
            else if (serviceType === 'fountain') {
                url = `${service.endpoint}${encodeURIComponent(username)}/keysend`;
                response = await fetch(url);
                data = await response.json();
                if (!response.ok || data.status === 'Not Found') {
                    throw new Error(`Fountain wallet not found for ${username}`);
                }
                return {
                    pubkey: data.pubkey || '',
                    customKey: data.customData?.[0]?.customKey || '',
                    customValue: data.customData?.[0]?.customValue || ''
                };
            }

            // default empty obj
            return { pubkey: '', customKey: '', customValue: '' };
        } catch (error) {
            throw new Error(`Wallet validation failed: ${error.message}`);
        }
    },

    /**
     * compare current time to last API call time, weak rate limiter
     * @returns {boolean}
     */
    canMakeApiCall() {
        const now = Date.now();
        if (now - this.lastApiCall < this.API_RATE_LIMIT_MS) {
            return false;
        }
        this.lastApiCall = now;
        return true;
    },

    /**
     * changes table appearance depending on if the table is empty or not
     */
    updateTableDisplay(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const table = slug.tableContainer || slug.container.querySelector(`#value-recipient-table-container-${feedSlug}`);
        const tbody = slug._tbody || slug.tableContainer?.querySelector('tbody');
        const msg = slug.container.querySelector(`#value-recipient-table-message-${feedSlug}`);

        // if table empty, display empty table message and hide table frame
        if (tbody.querySelectorAll('tr[data-recipient-id]').length === 0) {
            if (table) table.style.display = 'none';
            if (msg) msg.style.display = 'block';
        } else {
            if (msg) msg.style.display = 'none';
            if (table) table.style.display = 'block';
        }
    },

    /**
     * updates UI split data based on existing splits and fees
     * -> extract split data, separate fees
     * -> display total
     * -> render status styling based on totals
     */
    updateSplitTotalIndicators(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // change selector based on status of inherit checkbox
        const inheritCheckbox = !!slug.container.querySelector(`#inherit-channel-recipients-${feedSlug}`)?.checked;
        const selector = inheritCheckbox ? `tr[data-recipient-id], tr[data-origin="inherited"]` : `tr[data-recipient-id]`;

        const recipientForms = slug.container.querySelectorAll(selector);
        let feeTotal = 0;
        let regularTotal = 0;
        // extract split data, separate fees
        recipientForms.forEach(form => {
            const splitInput = form.querySelector('input[data-field="split"]') || form.querySelector('input[data-field="split-inherited"]');
            const split = parseFloat(splitInput?.value) || 0;

            const isFee = !!form.querySelector('input[type="hidden"][name*="[fee]"]')?.checked;

            if (isFee) {
                feeTotal += split;
            } else {
                regularTotal += split;
            }
        });

        // display calculated total
        slug.container.querySelectorAll('.total-percentage').forEach(span => {
            span.textContent = `Regular: ${regularTotal.toFixed(1)}% | Fees: ${feeTotal.toFixed(1)}%`;
        });

        // render status based on input amount
        slug.container.querySelectorAll('.total-status').forEach(span => {
            // remove old status class
            span.className = span.className.replace(/\b(status-good|status-over|status-under)\b/g, '').trim();

            if (regularTotal === 100) {
                span.textContent = '✓ Good to go!';
            } else if (regularTotal > 100) {
                span.textContent = '⚠ Over 100%';
            } else if (regularTotal > 0) {
                span.textContent = '⚠ Under 100%';
            } else {
                span.textContent = '';
            }
        });
    },

    /**
     * clear input form after verification
     */
    clearForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const lightningInput = slug.container.querySelector(`#lightning-address-input-${feedSlug}`);
        if (lightningInput) lightningInput.value = '';
        hideError(`value-recipient-error-${feedSlug}`);
        hideLoading(`value-recipient-loading-${feedSlug}`);
    },

    /**
     * handler to display pre-form content, allows user to start by entering a lightning url
     */
    showLightningAddressForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return slug;

        slug.container.querySelector(`#lightning-address-form-${feedSlug}`).style.display = 'block';
        slug.container.querySelector(`#recipient-confirmation-form-${feedSlug}`).style.display = 'none';
        this.clearForm(feedSlug);
        hideError(`value-recipient-error-${feedSlug}`);
        hideLoading(`value-recipient-loading-${feedSlug}`);
        slug.currentRecipientData = null;
    },

    /**
     * opens secondary form with potentially prepopulated data based on the endpoint
     * @param {Object} recipientData - data returned from api if validated, otherwise empty recipient object
     */
    showConfirmationForm(recipientData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        // set values in new form based on the data received
        slug.container.querySelector(`#confirm-lightning-address-${feedSlug}`).value = recipientData.lightningAddress;
        slug.container.querySelector(`#confirm-pubkey-${feedSlug}`).value = recipientData.pubkey || '';
        slug.container.querySelector(`#confirm-custom-value-${feedSlug}`).value = recipientData.customValue || '';
        slug.container.querySelector(`#confirm-custom-key-${feedSlug}`).value = recipientData.customKey || '';
        slug.container.querySelector(`#confirm-split-${feedSlug}`).value = recipientData.split || 0;
        slug.container.querySelector(`#confirm-fee-${feedSlug}`).checked = !!recipientData.fee;

        slug.container.querySelector(`#lightning-address-form-${feedSlug}`).style.display = 'none';
        slug.container.querySelector(`#recipient-confirmation-form-${feedSlug}`).style.display = 'block';
        hideError(`value-recipient-error-${feedSlug}`);
        hideLoading(`value-recipient-loading-${feedSlug}`);
    }
};

export function initValueRecipientManager(feedSlug) {
    ValueRecipientsManager.init(feedSlug);
}

export { ValueRecipientsManager };
