/**
 * DOM Utility Functions for PowerPress Metabox Managers
 * shared functions for displaying errors, loading states, and UI toggles
 */

// wp.i18n wrapper w/ graceful fallback when dep not loaded
export const __ = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__)
    ? wp.i18n.__
    : function(s) { return s; };

/**
 * show info to user regarding status changes
 * @param {string} message - message to display
 * @param {string} elementId - id of the element to display the message in
 */
export function showInfo(message, elementId) {
    const errorDiv = document.getElementById(elementId);
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.style.backgroundColor = '#d1ecf1';
        errorDiv.style.borderColor = '#bee5eb';
        errorDiv.style.color = '#0c5460';
    }
}

/**
 * display error messages
 * @param {string} message - error message to display
 * @param {string} elementId - id of the error element
 */
export function showError(message, elementId) {
    const errorDiv = document.getElementById(elementId);
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.style.backgroundColor = '#f8d7da';
        errorDiv.style.borderColor = '#f5c6cb';
        errorDiv.style.color = '#721c24';
    }
}

/**
 * display loading status
 * @param {string} elementId - id of the loading element
 */
export function showLoading(elementId) {
    const loadingDiv = document.getElementById(elementId);
    if (loadingDiv) {
        loadingDiv.style.display = 'block';
    }
}

/**
 * hide loading status
 * @param {string} elementId - id of the loading element
 */
export function hideLoading(elementId) {
    const loadingDiv = document.getElementById(elementId);
    if (loadingDiv) {
        loadingDiv.style.display = 'none';
    }
}

/**
 * hide error status
 * @param {string} elementId - id of the error element
 */
export function hideError(elementId) {
    const errorDiv = document.getElementById(elementId);
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

/**
 * collapse section and update display
 * @param {HTMLElement} toggle - the toggle button element
 * @param {string} collapseSelector - the id of the collapsible container
 */
export function toggleVisibility(toggle, collapseSelector) {
    const collapse = document.getElementById(collapseSelector);
    const triangle = toggle.querySelector('button');
    if (!collapse || !triangle) return;

    const isHidden = collapse.dataset.state === 'hidden';
    collapse.dataset.state = isHidden ? 'visible' : 'hidden';
    triangle.title = isHidden ? "Collapse Form" : "Expand Form";
    triangle.textContent = isHidden ? "▲" : "▼";

    // smooth transition w/jquery
    if (window.jQuery) {
        isHidden ? jQuery(collapse).slideDown(200) : jQuery(collapse).slideUp(200);
    } else {
        collapse.style.display = isHidden ? '' : 'none';
    }
}

/**
 * initialize a live character counter for a textarea or input
 * @param {string} inputId - id of the textarea/input element
 * @param {string} counterId - id of the counter display element
 * @param {number|null} warnAt - character count at which to show warning color (null = no warning)
 */
export function initCharCounter(inputId, counterId, warnAt = null) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    if (!input || !counter) return;

    function update() {
        const len = input.value.length;
        counter.textContent = len;
        if (warnAt) {
            counter.style.color = (len > warnAt) ? '#d32f2f' : '#666';
        }
    }

    input.addEventListener('input', update);
    update();
}

/**
 * copy text to clipboard w/ fallback for local/http
 * @param {string} inputId - id of input element to copy from
 * @param {HTMLElement} btn - button that triggered the copy (text swaps to "Copied!")
 */
export function ppCopyText(inputId, btn) {
    const text = document.getElementById(inputId).value;
    const original = btn.textContent;

    function onSuccess() {
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = original; }, 1500);
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(fallback);
    } else {
        fallback();
    }

    // execCommand fallback for http/localdev
    function fallback() {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        onSuccess();
    }
}

/**
 * show a floating toast notification, auto-dismisses
 * @param {string} message
 * @param {Object} [options]
 * @param {string} [options.type='success'] - 'success' or 'error'
 * @param {number} [options.duration=3000] - ms before auto-dismiss
 * @param {string} [options.id='pp-toast'] - element id for replacement
 */
export function showToast(message, options = {}) {
    const type = options.type || 'success';
    const duration = options.duration || 3000;
    const id = options.id || 'pp-toast';

    const existing = document.getElementById(id);
    if (existing) existing.remove();

    const isError = type === 'error';
    const bg = isError ? '#EF5350' : '#fff';
    const fg = isError ? '#fff' : '#333';
    const border = isError ? '#EF5350' : '#1976D2';

    const toast = document.createElement('div');
    toast.id = id;
    toast.style.cssText = `position: fixed; top: 50px; right: 24px; left: auto; max-width: calc(100vw - 48px);
                           background: ${bg}; color: ${fg}; border-left: 4px solid ${border};
                           padding: 12px 24px; border-radius: 4px; font-size: 14px;
                           box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 10000; opacity: 0;
                           transform: translateY(10px); transition: opacity 0.2s ease, transform 0.2s ease;`;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(function() {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(function() { toast.remove(); }, 200);
    }, duration);
}
