/**
 * Social Interact Management system for Podcast2.0 Tag:
 * documentation: https://podcasting2.org/docs/podcast-namespace/tags/social-interact
 * Tag: <podcast:socialInteract>
 *       Supported Attributes:
 *          protocol: (required) The protocol in use for interacting with the comment root post.
 *          uri: (required) The uri/url of root post comment.
 *          accountId: (recommended) The account id (on the commenting platform) of the account that created this root post.
 *          accountUrl: (optional) The public url (on the commenting platform) of the account that created this root post.
 *          priority: (optional) When multiple socialInteract tags are present, this integer gives order of priority. A lower number means higher priority.
 */

import { showError, hideError, showLoading, hideLoading } from '../../utils/dom-utils.js';
import { cleanHTMLTags, renderSafeLink } from '../../utils/html-utils.js';

const SocialInteractManager = {
    feedSlugs: new Map(),

    getFeedSlug(feedSlug, containerElement) {
        if (!this.feedSlugs.has(feedSlug)) {
            const slug = {
                feedSlug: feedSlug,
                container: containerElement,
                formContainer: null,
                tableContainer: null,
                _tbody: null,
                currentInteractData: null
            };
            this.feedSlugs.set(feedSlug, slug);
        }
        return this.feedSlugs.get(feedSlug);
    },

    /**
     * initializer
     */
    init(feedSlug) {
        const container = document.getElementById(`social-interact-container-${feedSlug}`);
        if (!container) return;

        const slug = this.getFeedSlug(feedSlug, container);

        slug.formContainer = slug.container.querySelector(`#social-interact-form-container-${feedSlug}`);
        slug.tableContainer = slug.container.querySelector(`#social-interact-table-container-${feedSlug}`);
        slug._tbody = slug.tableContainer?.querySelector('tbody') || null;

        container.addEventListener('click', (e) => this.onClick(e, feedSlug));
        container.addEventListener('change', (e) => this.onChange(e, feedSlug));

        // form 'Enter' handling
        const formContainer = container.querySelector(`#social-interact-form-container-${feedSlug}`);
        if (formContainer) {
            formContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.checkSocialInteractUri(feedSlug);
                    return false;
                }
            });
        }

        this.updateSectionDisabledState(feedSlug);
        this.updateUriDisabledState(feedSlug);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * section click handler
     */
    onClick(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'check-uri':
                e.preventDefault();
                this.checkSocialInteractUri(feedSlug);
                break;
            case 'confirm-manual-protocol':
                e.preventDefault();
                this.confirmManualProtocol(feedSlug);
                break;
            case 'remove-social-interact':
                e.preventDefault();
                this.removeSocialInteract(el, feedSlug);
                break;
        }
    },

    /**
     * section change event
     * @param {Event} e
     * @returns
     */
    onChange(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        if (e.target.matches('select[data-field="protocol"]')) {
            this.updateUriDisabledState(feedSlug);
            return;
        }

        if (e.target.id === `disable-episode-comments-${feedSlug}`) {
            this.updateSectionDisabledState(feedSlug);
        }
    },

    /**
     * form transition function, attempts to detect root post then extract account information
     * @returns when no uri is provided
     */
    checkSocialInteractUri(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const uriInput = slug.container.querySelector(`#social-interact-uri-input-${feedSlug}`);
        const uri = uriInput?.value?.trim();

        if (!uri) {
            showError('URI is required', `social-interact-error-${feedSlug}`);
            return;
        }

        showLoading(`social-interact-loading-${feedSlug}`);
        hideError(`social-interact-error-${feedSlug}`);

        try {
            // auto-detect and extract data using new patterns
            const protocol = this.checkUri(uri);

            if (protocol === 'disabled' || !protocol) {
                hideLoading(`social-interact-loading-${feedSlug}`);
                this.showProtocolSelector(feedSlug);
                return;
            }

            const usernameData = this.extractUsername(cleanHTMLTags(uri), protocol);
            const accountId = this.buildAccountId(usernameData, protocol);
            const accountUrl = this.buildAccountUrl(usernameData, protocol);

            const interactData = {
                id: this.getNextId(feedSlug),
                uri: uri,
                protocol: protocol,
                account_id: accountId,
                accountUrl: accountUrl,
                priority: 1
            };

            this.addSocialInteractRow(interactData, feedSlug);
            this.clearForm(feedSlug);
            this.updateTableDisplay(feedSlug);
            hideLoading(`social-interact-loading-${feedSlug}`);
        } catch (error) {
            hideLoading(`social-interact-loading-${feedSlug}`);
            showError(error.message, `social-interact-error-${feedSlug}`);
        }
    },

    /**
     * populates form information to be saved in hidden inputs and on table display
     * via cloning template
     * @param {Object} formData - social interact object
     * @returns when required dom elements dont exist
     */
    addSocialInteractRow(formData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const rowTemplate = slug.container.querySelector(`#social-interact-row-template-${feedSlug}`);
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        const id = formData.id;

        node.dataset.socialInteractId = id;
        node.querySelector('[data-action="remove-social-interact"]').dataset.socialInteractId = id;
        renderSafeLink(node.querySelector('[data-field="uri-display"]'), formData.uri);

        // set values
        const protocolSelect = node.querySelector('[data-cell="protocol"] select');
        if (protocolSelect) protocolSelect.value = formData.protocol;

        const accountIdInput = node.querySelector('[data-cell="accountId"] input');
        if (accountIdInput) accountIdInput.value = formData.account_id || '';

        const accountUrlInput = node.querySelector('[data-cell="accountUrl"] input');
        if (accountUrlInput) accountUrlInput.value = formData.accountUrl || '';

        const priorityInput = node.querySelector('[data-cell="priority"] input');
        if (priorityInput) priorityInput.value = formData.priority || 1;

        // update and id
        node.querySelectorAll('input, select').forEach(input => {
            if (input.name && input.name.includes('__ID__')) {
                input.name = input.name.replace('__ID__', id);
            }

            if (input.dataset.socialInteractId !== undefined) {
                input.dataset.socialInteractId = id;
            }
        });

        // update hidden input field
        const hiddenUriInput = node.querySelector('input[type="hidden"][name*="[uri]"]');
        if (hiddenUriInput) hiddenUriInput.value = formData.uri;

        slug._tbody.appendChild(node);
        this.updateUriDisabledState(feedSlug);
        this.updateTableDisplay(feedSlug);
    },

    /**
     * display protocol select field when auto-detection fails
     */
    showProtocolSelector(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const manualProtocolDiv = slug.container.querySelector(`#social-interact-manual-protocol-${feedSlug}`);
        const protocolSelect = slug.container.querySelector(`#manual-protocol-select-${feedSlug}`);

        if (manualProtocolDiv) {
            manualProtocolDiv.style.display = 'block';
        }
        if (protocolSelect) {
            protocolSelect.value = '';
        }
    },

    confirmManualProtocol(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const uriInput = slug.container.querySelector(`#social-interact-uri-input-${feedSlug}`);
        const protocolSelect = slug.container.querySelector(`#manual-protocol-select-${feedSlug}`);

        const uri = uriInput?.value?.trim();
        const protocol = protocolSelect?.value;

        if (!protocol) {
            showError('Please select a protocol', `social-interact-error-${feedSlug}`);
            return;
        }

        const id = this.getNextId(feedSlug);

        const interact_data = {
            id: id,
            uri: uri,
            protocol: protocol,
            account_id: '',
            accountUrl: '',
            priority: id
        };

        this.addSocialInteractRow(interact_data, feedSlug);
        this.clearForm(feedSlug);
        this.updateTableDisplay(feedSlug);

        const manualProtocolDiv = slug.container.querySelector(`#social-interact-manual-protocol-${feedSlug}`);
        if (manualProtocolDiv) {
            manualProtocolDiv.style.display = 'none';
        }
    },

    /**
     * remove social interact field on click entry
     * -> get target ID from remove button data attribute value
     * @param {HTMLElement} button - remove button clicked
     */
    removeSocialInteract(button, feedSlug) {
        const row = button.closest('tr[data-social-interact-id]');
        if (row) {
            row.remove();
            this.updateTableDisplay(feedSlug);
        }
    },

    /**
     * robust pattern matching for the existing podcast-namespace for social interact
     * @param {string} url
     * @returns {string} protocol name or 'disabled'
     */
    checkUri(url) {
        const patterns = {
            activitypub: [
                // @user@instance.x
                /^@?[A-Za-z0-9_]{1,64}@[A-Za-z0-9.-]+\.[A-Za-z]{2,}(?::\d+)?$/i,

                // https://host/@user[/statusId]
                /^(?:https?:\/\/)?[^\/]+\/@[^@\/\s]+(?:@[^\/\s]+)?(?:\/\d+)?(?:[/?#].*)?$/i,

                // https://host/users/user[/statuses/id]
                /^(?:https?:\/\/)?[^\/]+\/users\/[^\/]+(?:\/statuses\/\d+)?(?:[/?#].*)?$/i,

                // https://host/path/@user/postid
                /^(?:https?:\/\/)?[^\/]+\/[^\/]*\/@[^\/\s]+(?:\/\w+)?(?:[/?#].*)?$/i,

                // ActivityPub
                /^(?:https?:\/\/)?[^\/]+\.[a-z]{2,}\/@[^\/\s]+/i,
            ],
            twitter: [
                /^(?:https?:\/\/)?(?:x\.com|twitter\.com)\/[^\/]+\/status\/\d+(?:[/?#].*)?$/i,
            ],
            atproto: [
                // Bluesky web links with handle: https://bsky.app/profile/<handle>/post/<tid>
                /^(?:https?:\/\/)?bsky\.app\/profile\/[^\/]+\.bsky\.social\/post\/[A-Za-z0-9]+(?:[/?#].*)?$/i,

                // Bluesky web links with DID: https://bsky.app/profile/did:plc:<id>/post/<tid>
                /^(?:https?:\/\/)?bsky\.app\/profile\/did:plc:[a-z0-9]+\/post\/[A-Za-z0-9]+(?:[/?#].*)?$/i,

                // Any Bluesky web link (profile root etc.)
                /^(?:https?:\/\/)?bsky\.app\/(?:[?#].*|$|profile\/)/i,

                // AT Protocol URIs with proper format
                /^at:\/\/did:plc:[a-z0-9]+\/app\.bsky\.feed\.post\/[A-Za-z0-9]+$/i,

                // General AT Protocol URI pattern
                /^at:\/\//i,

                // bsky.social domains
                /\.bsky\.social(?:\/|$)/i,
            ],
            lightning: [
                // Lightning protocol scheme
                /^lightning:/i,
                // LNURL bech32 format (uppercase, 64+ chars)
                /\bLNURL[A-Z0-9]{60,}\b/i,
                // Lowercase lnurl variants
                /\blnurl[a-z0-9]{60,}\b/i,
                // HTTPS URLs that might resolve to LNURL
                /^https:\/\/.*\?.*lightning/i,
            ],
            matrix: [
                // matrix.to share links
                /^(?:https?:\/\/)?matrix\.to\/#\/[#!@$+][^\/\s]+(?:\/[^\/\s]+)?(?:[/?#].*)?$/i,
                // Room aliases: #roomname:server.com
                /^#[^\s:]+:[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?::\d+)?$/i,
                // User IDs: @username:server.com
                /^@[^\s:]+:[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?::\d+)?$/i,
                // Event IDs: $eventid:server.com
                /^\$[^\s:]+:[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?::\d+)?$/i,
                // Group/Community IDs: +groupname:server.com
                /^\+[^\s:]+:[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?::\d+)?$/i,
            ],
            nostr: [
                // nostr identifiers based on link source
                /(?:nostr:|npub1|nprofile1|nevent1|note1|nrelay1|naddr1)[a-z0-9]+/i
            ],
            hive: [
                // Hive.blog posts: https://hive.blog/@username/permlink
                /^(?:https?:\/\/)?(?:www\.)?hive\.blog\/@[a-z0-9.-]+\/[a-z0-9-]+/i,
                // PeakD posts: https://peakd.com/@username/permlink
                /^(?:https?:\/\/)?(?:www\.)?peakd\.com\/@[a-z0-9.-]+\/[a-z0-9-]+/i,
            ],
        };

        for (const [protocol, regexes] of Object.entries(patterns)) {
            if (regexes.some(regex => regex.test(url))) return protocol;
        }
        return 'disabled'; // default fallback
    },

    /**
     * extract username from URI based on protocol
     */
    extractUsername(uri, protocol) {
        switch (protocol) {
            case 'activitypub':
                // Post URLs with ID: https://defcon.social/@raynold_/123456789
                let match = uri.match(/^(?:https?:\/\/)?([^\/]+)\/@([^\/]+)\/(\d+)/);
                if (match) return { username: match[2], server: match[1], postId: match[3] };

                // Profile pages: https://mastodon.archive.org/@internetarchive
                match = uri.match(/^(?:https?:\/\/)?([^\/]+)\/@([^\/]+)\/?$/);
                if (match) return { username: match[2], server: match[1] };

                // @user@server.domain format
                match = uri.match(/^@([^@]+)@(.+)$/);
                if (match) return { username: match[1], server: match[2] };

                // /@username pattern
                match = uri.match(/\/@([^\/]+)/);
                if (match) return { username: match[1] };

                // /users/username pattern
                match = uri.match(/\/users\/([^\/]+)/);
                if (match) return { username: match[1] };

                return null;

            case 'twitter':
                const twitterMatch = uri.match(/(?:twitter\.com|x\.com)\/([^\/]+)/);
                return twitterMatch ? { username: twitterMatch[1] } : null;

            case 'atproto':
                const bskyMatch = uri.match(/(?:bsky\.app\/profile\/|profile\/)([^\/]+)/);
                return bskyMatch ? { username: bskyMatch[1] } : null;

            default:
                return null;
        }
    },

    /**
     * build account ID from extracted username data
     */
    buildAccountId(usernameData, protocol) {
        if (!usernameData) return '';

        switch (protocol) {
            case 'activitypub':
                return usernameData.server
                    ? `@${usernameData.username}@${usernameData.server}`
                    : `@${usernameData.username}`;
            case 'twitter':
                return `@${usernameData.username}`;
            case 'atproto':
                return `@${usernameData.username}`;
            default:
                return '';
        }
    },

    /**
     * build profile URL from extracted username data
     */
    buildAccountUrl(usernameData, protocol) {
        if (!usernameData) return '';

        switch (protocol) {
            case 'activitypub':
                // e.x. https://defcon.social/@raynold_
                if (usernameData.server) {
                    return `https://${usernameData.server}/@${usernameData.username}`;
                }
                return ''; // need server info to build activitypub url

            case 'twitter':
                // e.x. https://x.com/AniTVOfficial/status/1962903164543193109
                // e.x. https://x.com/AniTVOfficial
                return `https://twitter.com/${usernameData.username}`;

            case 'atproto':
                // e.x. https://bsky.app/profile/rayo3o.bsky.social
                return `https://bsky.app/profile/${usernameData.username}`;

            default:
                return '';
        }
    },

    /**
     * toggle function for disabling state
     */
    updateSectionDisabledState(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const disabled = slug.container.querySelector(`#disable-episode-comments-${feedSlug}`)?.checked;

        if (slug.tableContainer) {
            slug.tableContainer.style.opacity = disabled ? '0.5' : '1';
            slug.tableContainer.style.pointerEvents = disabled ? 'none' : 'auto';
        }

        // update form button state
        const formButton = slug.formContainer?.querySelector('[data-action="check-uri"]');
        if (formButton) {
            formButton.disabled = disabled;
        }
        // update form input state
        const uriInput = slug.container.querySelector(`#social-interact-uri-input-${feedSlug}`);
        if (uriInput) {
            uriInput.disabled = disabled;
            uriInput.style.opacity = disabled ? '0.5' : '1';
        }
        // update message
        if (disabled) {
            showError('All Comments are disabled for this episode.', `social-interact-error-${feedSlug}`);
        } else {
            hideError(`social-interact-error-${feedSlug}`);
        }
    },

    /**
     * toggle function for disabling a specific uri on data table (avoids delete)
     */
    updateUriDisabledState(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const tableRows = slug.container.querySelectorAll('tr[data-social-interact-id]');

        tableRows.forEach(row => {
            const protocolSelect = row.querySelector('select[data-field="protocol"]');
            const isDisabled = protocolSelect && protocolSelect.value === 'disabled';

            // find input fields in row
            const inputs = row.querySelectorAll('input[data-field]:not([type="hidden"])');

            inputs.forEach(input => {
                input.disabled = isDisabled;
                input.style.opacity = isDisabled ? '0.5' : '1';
            });

            // add/remove disabled indicator
            const uriCell = row.querySelector('[data-cell="uri"]');
            let indicator = row.querySelector('.disabled-indicator');

            if (isDisabled && !indicator && uriCell) {
                indicator = document.createElement('div');
                indicator.className = 'disabled-indicator';
                indicator.style.cssText = 'font-size: 12px; color: #666; font-style: italic; margin-top: 5px;';
                indicator.textContent = 'Comments disabled for this URI';
                uriCell.appendChild(indicator);
            } else if (!isDisabled && indicator) {
                indicator.remove();
            }
        });
    },

    /**
     * change display of table based on existing row state
     */
    updateTableDisplay(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const table = slug.tableContainer || slug.container.querySelector(`#social-interact-table-container-${feedSlug}`);
        const tbody = slug._tbody || slug.tableContainer?.querySelector('tbody');
        const msg = slug.container.querySelector(`#social-interact-table-message-${feedSlug}`);

        if (tbody.querySelectorAll('tr[data-social-interact-id]').length === 0) {
            if (table) table.style.display = 'none';
            if (msg) msg.style.display = 'block';
        } else {
            if (table) table.style.display = 'block';
            if (msg) msg.style.display = 'none';
        }
    },

    /**
     * get new ID for new social interact section, goes for highest value to avoid overlapping ID
     * @returns {number}
     */
    getNextId(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        const existingInteracts = slug.container.querySelectorAll('tr[data-social-interact-id]');
        let maxId = 0;

        existingInteracts.forEach(interact => {
            const id = parseInt(interact.dataset.socialInteractId);
            if (id > maxId) maxId = id;
        });

        return maxId + 1;
    },

    /**
     * clear input form
     */
    clearForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const uriInput = slug.container.querySelector(`#social-interact-uri-input-${feedSlug}`);
        if (uriInput) uriInput.value = '';

        const manualProtocolDiv = slug.container.querySelector(`#social-interact-manual-protocol-${feedSlug}`);
        if (manualProtocolDiv) {
            manualProtocolDiv.style.display = 'none';
        }

        hideError(`social-interact-error-${feedSlug}`);
        hideLoading(`social-interact-loading-${feedSlug}`);
    },
};

export function initSocialInteractManager(feedSlug) {
    SocialInteractManager.init(feedSlug);
}

export { SocialInteractManager };
