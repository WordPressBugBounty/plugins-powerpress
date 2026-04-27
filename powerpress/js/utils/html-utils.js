/**
 * HTML Utility Functions for PowerPress Metabox Managers
 * shared functions for cleaning and rendering HTML content
 */

/**
 * clean HTML tags from input string
 * @param {string} input - string that may contain html tags
 * @returns {string} cleaned string without html tags
 */
export function cleanHTMLTags(input) {
    if (!input) return '';
    return input.replace(/<[^>]*>/g, '').trim();
}

/**
 * checks if URL is qualified and renders a truncated text display or placeholder
 * @param {HTMLElement} cell - element to store the URL text
 * @param {string} raw - raw url passed by user
 * @param {boolean} strict - if true, only accept valid http(s)/at: URLs
 * @param {boolean} truncate - if true, truncate long URLs for display
 */
export function renderSafeLink(cell, raw, strict = false, truncate = true) {
    if (!cell) return;

    // default placeholder
    cell.textContent = '-';

    if (!raw) return;
    raw = String(raw).trim();
    if (!raw) return;

    if (strict) {
        // only accept valid http(s) URLs
        try {
            const u = new URL(raw);
            if (u.protocol !== 'http:' && u.protocol !== 'https:' && u.protocol !== 'at:') {
                cell.textContent = 'Unresolved URL';
                return;
            }
        } catch {
            cell.textContent = 'Unresolved URL';
            return;
        }
    }

    let display = raw;
    // strip protocol and truncate if needed
    if (truncate) {
        display = raw.replace(/^https?:\/\//i, '').replace(/^at:\/\//i, '');
        if (display.length > 32) display = display.slice(0, 29) + '...';
    }

    const div = document.createElement('div');
    div.textContent = display;
    div.title = raw;
    div.style.fontSize = 'clamp(10px, 2.5vw, 14px)';
    div.style.display = 'inline-block';
    div.style.maxWidth = '100%';
    div.style.overflow = 'hidden';
    div.style.textOverflow = 'ellipsis';
    div.style.whiteSpace = 'nowrap';

    cell.replaceChildren(div);
}
