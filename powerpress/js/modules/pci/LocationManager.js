/**
 * Location Management system for Podcast2.0 Tag:
 * documentation: https://podcasting2.org/docs/podcast-namespace/tags/location
 * Tag: <podcast:location>
 * Main Content: Human Readable Location (Address)
 *      - rel: (recommended) The rel attribute can contain one of the following possible values:
 *          "subject" (default) - The location refers to what/where the content is about.
 *          "creator" - The location refers to where the content was recorded or produced.
 *      - geo: (recommended) A latitude and longitude in geoURI form, following RFC5870 (i.e. "geo:30.2672,97.7431").
 *      - osm: (recommended) The OpenStreetMap identifier of this place. Made by taking the first character of the OSM object type
 *          -> (Node, Way, Relation), followed by the ID. (i.e. "R113314")
 *      - country: (recommended) A two-letter code for the country, following ISO 3166-1 alpha-2
 */

import { showError, hideError, showLoading, hideLoading } from '../../utils/dom-utils.js';
import { cleanHTMLTags } from '../../utils/html-utils.js';

const LocationManager = {
    lastApiCall: 0,
    API_RATE_LIMIT_MS: 1100,
    feedSlugs: new Map(),

    getFeedSlug(feedSlug, containerElement) {
        if (!this.feedSlugs.has(feedSlug)) {
            const slug = {
                feedSlug: feedSlug,
                container: containerElement,
                tableContainer: null,
                _tbody: null,
                addressBook: new Set(),
            };

            this.feedSlugs.set(feedSlug, slug);
        }
        return this.feedSlugs.get(feedSlug);
    },

    /**
     * main initializer
     * -> init content containers
     * -> init event handlers
     */
    init(feedSlug) {
        const container = document.getElementById(`location-container-${feedSlug}`);
        if (!container) return;

        const slug = this.getFeedSlug(feedSlug, container);
        slug.tableContainer = container.querySelector(`#location-table-container-${feedSlug}`);
        slug._tbody = slug.tableContainer?.querySelector('tbody') || null;

        container.addEventListener('click', (e) => this.onClick(e, feedSlug));

        slug._tbody?.querySelectorAll('tr[data-location-id]').forEach(tr => {
            const addr = tr.querySelector('input[name*="[address]"]')?.value?.trim();
            if (addr) {
                tr.dataset.addressKey = addr;
                slug.addressBook.add(addr);
            }
        });

        // form 'Enter' handling
        const formContainer = container.querySelector(`#location-form-container-${feedSlug}`);
        if (formContainer) {
            formContainer.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.target.matches('input') || e.target.matches('select'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.addLocation(feedSlug);
                    return false;
                }
            });
        }

        this.updateTableDisplay(feedSlug);
    },

    /**
     * sets up event handling within the location section
     * @param {Event} e - click event
     * @returns when the element is not of this root container
     */
    onClick(e, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const el = e.target.closest('[data-action]');
        if (!el || !slug.container.contains(el)) return;

        switch (el.dataset.action) {
            case 'add-location':
                e.preventDefault();
                this.addLocation(feedSlug);
                break;
            case 'remove-location':
                e.preventDefault();
                this.removeLocation(el, feedSlug);
                break;
        }
    },

    /**
     * async function to add new location
     * -> clean input whitespace
     * -> attempt to search api, bail early if custom location is checked
     * @returns on error, missing input / data
     */
    async addLocation(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const addressInput = slug.container.querySelector(`#location-search-input-${feedSlug}`);
        const countrySelect = slug.container.querySelector(`#location-search-country-${feedSlug}`);
        const customLocation = slug.container.querySelector('input[data-field="custom-location"]');

        // check for content input container
        if (!addressInput) {
            showError('Address input not found', `location-error-${feedSlug}`);
            return;
        }

        // clean input for search
        const address = cleanHTMLTags(addressInput.value);
        const country = countrySelect?.value || '';

        if (!address) {
            showError('Address is required', `location-error-${feedSlug}`);
            return;
        }

        // disable add button to prevent multiple clicks on the same input
        const addButton = slug.container.querySelector('[data-action="add-location"]');
        if (addButton) {
            addButton.disabled = true;
            addButton.style.opacity = 0.5;
        }

        showLoading(`location-loading-${feedSlug}`);
        hideError(`location-error-${feedSlug}`);

        try {
            // check if custom location checkbox is checked to bypass API validation
            let locationData;
            if (customLocation?.checked) {
                locationData = {
                    address: address,
                    country: '',
                    geo: '',
                    osm: ''
                };
            } else {
                // api validation
                locationData = await this.validateLocationAddress(address, country);
            }

            // fail if the address is already in our addressBook
            if (slug.addressBook.has(locationData.address.trim())) {
                showError('Address already exists.', `location-error-${feedSlug}`);
                return;
            }
            // fill data with api returns
            const newLocation = {
                id: this.getNextId(feedSlug),
                rel: '1',
                address: locationData.address,
                country: customLocation?.checked ? '' : country || '',
                geo: locationData.geo || '',
                osm: locationData.osm || ''
            };
            // failed searches still added as custom locations ( may not need now that the checkbox is added (?) )
            if (!locationData.geo) {
                this.showSuccess(`Added "${locationData.address}" as a custom location.`, feedSlug);
            }

            // update state of form and table
            this.addLocationRow(newLocation, feedSlug);
            slug.addressBook.add(locationData.address);
            this.clearForm(feedSlug);
            this.updateTableDisplay(feedSlug);
        } catch (error) {
            showError("Failed to add location: " + error.message, `location-error-${feedSlug}`);
        } finally {
            hideLoading(`location-loading-${feedSlug}`);
            if (addButton) {
                // re-enable add button
                addButton.disabled = false;
                addButton.style.opacity = 1;
            }
        }
    },

    /**
     * adds new location to table using template
     * @param {Object} locationData - locationData to add to table {id, rel, address, country, geo, osm}
     * @returns
     */
    addLocationRow(locationData, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const rowTemplate = slug.container.querySelector(`#location-row-template-${feedSlug}`);
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        const id = locationData.id;

        // setup new template clone w proper id
        node.dataset.locationId = id;
        node.querySelector('[data-action="remove-location"]').dataset.locationId = id;

        const relSelect = node.querySelector('[data-cell="rel"] select');
        if (relSelect) relSelect.value = locationData.rel;

        const addressDiv = node.querySelector('[data-cell="address"] div');
        if (addressDiv) addressDiv.textContent = locationData.address || 'Unknown location';

        const geoCode = node.querySelector('[data-cell="geo"] code');
        if (geoCode) geoCode.textContent = locationData.geo ? '✓' : '×';

        const osmCode = node.querySelector('[data-cell="osm"] code');
        if (osmCode) osmCode.textContent = locationData.osm ? '✓' : '×';

        // setup template with proper id
        node.querySelectorAll('input, select').forEach(input => {
            if (input.name && input.name.includes('__ID__')) {
                input.name = input.name.replace('__ID__', id);
            }
        });

        // set hidden input with data to save
        node.querySelectorAll('input[type="hidden"]').forEach(input => {
            const field = input.dataset.field;

            if (field === 'address')
                input.value = locationData.address || '';
            else if (field === 'country')
                input.value = locationData.country || '';
            else if (field === 'geo')
                input.value = locationData.geo || '';
            else if (field === 'osm')
                input.value = locationData.osm || '';
        });

        slug._tbody.appendChild(node);
    },

    /**
     * location removal wrapper
     * -> removes row
     * -> updates display
     * @param {HTMLElement} button - remove button
     */
    removeLocation(button, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const row = button.closest('tr[data-location-id]');
        if (row) {
            const address = row.querySelector('[data-cell="address"]')?.textContent?.trim() || row.querySelector('input[name*="[address]"]')?.value?.trim();

            if (address) slug.addressBook.delete(address);
            row.remove();
            this.updateTableDisplay(feedSlug);
        }
    },

    /**
     * checks if table is empty, changes display to show empty table state
     */
    updateTableDisplay(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const table = slug.container.querySelector('.table-wrap');
        const tbody = slug._tbody || slug.tableContainer?.querySelector('tbody');
        const msg = slug.container.querySelector(`#location-table-message-${feedSlug}`);

        if (tbody.querySelectorAll('tr[data-location-id]').length === 0) {
            if (table) table.style.display = 'none';
            if (msg) msg.style.display = 'block';
        } else {
            if (table) table.style.display = 'block';
            if (msg) msg.style.display = 'none';
        }
    },

    /**
     * checks for highest available id to prevent overlap
     * @returns next available id
     */
    getNextId(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return 1;

        // grab existing rows
        const existingLocations = slug.container.querySelectorAll(`tr[data-location-id]`);
        let maxId = 0;
        // find highest data-location-id
        existingLocations.forEach(location => {
            const id = parseInt(location.dataset.locationId);
            if (id > maxId) maxId = id;
        });
        return maxId + 1;
    },

    /**
     * check internal counter to make sure we dont over-request from api
     * @returns true if we've waited long enough
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
     * try to get location from nominatim / open street map (OSM)
     * @param {string} address - address input string
     * @param {string} country - country selection
     * @returns newLocation object {address, geo, osm, country}
     */
    async validateLocationAddress(address, country = '') {
        if (!this.canMakeApiCall()) {
            throw new Error('Please wait a moment before trying again.');
        }
        // remove html
        address = cleanHTMLTags(address);
        if (!address || address.trim() === '') {
            throw new Error('Address is required for validation');
        }

        // init so we always have a return regardless of api request failure
        const newLocation = {
            address: address,
            geo: '',
            osm: '',
            country: country
        };

        try {
            // request 1 result from OSM endpoint
            const searchQuery = country ? `${address}, ${country}` : address;
            const nominatimURL = `https://nominatim.openstreetmap.org/search?${new URLSearchParams({
                format: 'json',
                q: searchQuery,
                addressdetails: '1',
                limit: '1'
            }).toString()}`;

            const response = await fetch(nominatimURL);

            if (response.ok) {
                const data = await response.json();

                if (data.length > 0) {
                    // update newLocation object
                    const location = data[0];
                    newLocation.address = location.display_name;
                    newLocation.geo = `geo:${location.lat},${location.lon}`;
                    // if no country was originally provided we can extract from api data
                    if (!country && location.address && location.address.country) {
                        newLocation.country = location.address.country;
                    }

                    if (location.osm_id) {
                        const osmPrefix = location.osm_type === 'way' ? 'W' : location.osm_type === 'relation' ? 'R' : 'N';
                        newLocation.osm = `${osmPrefix}${location.osm_id}`;
                    }
                }
            }
        } catch (error) {
            console.warn('Location API failed:', error.message);
        }
        // we return newLocation object regardless of api failure
        return newLocation;
    },

    /**
     * update error display to show a success message to user, clears itself after 3 seconds
     * @param {string} message
     */
    showSuccess(message, feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const errorDiv = slug.container.querySelector(`#location-error-${feedSlug}`);
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            errorDiv.style.backgroundColor = '#d1edff';
            errorDiv.style.borderColor = '#bee5eb';
            errorDiv.style.color = '#0c5460';

            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
        }
    },

    /**
     * clear form inputs, reset loading and error state
     */
    clearForm(feedSlug) {
        const slug = this.feedSlugs.get(feedSlug);
        if (!slug) return;

        const searchInput = slug.container.querySelector(`[data-purpose="search-input"]`);
        if (searchInput) searchInput.value = '';

        hideLoading(`location-loading-${feedSlug}`);
        hideError(`location-error-${feedSlug}`);
    }
};

export function initLocationManager(feedSlug) {
    LocationManager.init(feedSlug);
}

export { LocationManager };
