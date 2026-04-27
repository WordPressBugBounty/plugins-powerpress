/**
 * PowerPress Network Admin
 *
 * TODO: convert var -> const/let
 *
 * SECTIONS:
 *   LOCALIZATION
 *   DIALOG MODAL
 *   PAGE TYPE CONFIG
 *   FORM SUBMISSION
 *   TAB NAVIGATION
 *   PAGE HANDLING
 *   LIST MANAGEMENT
 *   SEARCH / FILTER
 *   APPLICATION APPROVAL
 *   PROGRAM MANAGEMENT
 *   SETTINGS
 *   INIT
 *   EVENT DELEGATION
 */
import { ppCopyText, showToast } from './utils/dom-utils.js';
import { escapeHtml } from './utils/sanitize.js';

// ============
// LOCALIZATION
// ============

// wp.i18n wrapper w/ graceful fallback
var __ = (typeof wp !== 'undefined' && wp.i18n && wp.i18n.__)
    ? wp.i18n.__
    : function(s) { return s; };

// DIALOG MODAL

function ppnDialog(el) {
    var dialogId = el.dataset.ppnDialog || el.getAttribute('data-ppn-dialog');
    if (!dialogId) return;
    var dialog = document.getElementById(dialogId);
    if (dialog && typeof dialog.showModal === 'function') {
        dialog.showModal();
    }
}

function ppnDialogClose(el) {
    var dialog = el.closest('dialog');
    if (dialog) dialog.close();
}


// ================
// PAGE TYPE CONFIG
// ================

/** @type {Object<string, {shortcode: function, defaultTitle: function, hasTargetId: boolean}>} */
var PAGE_TYPES = {
    Program: {
        shortcode: function(id) { return `[ppn-program id = ${id}]`; },
        defaultTitle: function(id) { return `program id = ${id}`; },
        hasTargetId: true
    },
    List: {
        shortcode: function(id) { return `[ppn-gridview id="${id}" rows="100" cols="3"]`; },
        defaultTitle: function(id) { return `list id = ${id}`; },
        hasTargetId: true
    },
    Application: {
        shortcode: function() { return '[ppn-application]'; },
        defaultTitle: function() { return 'Application Page'; },
        hasTargetId: false
    },
    Homepage: {
        shortcode: function() { return '[ppn-gridview id="all" rows="100" cols="3"]\n\n[ppn-list id="all" style="detailed"]'; },
        defaultTitle: function() { return 'Network Page' },
        hasTargetId: false
    }
};


// ===============
// FORM SUBMISSION
// ===============

/** post formdata to current page, returns true on success */
async function _ppnPost(body) {
    try {
        var resp = await fetch(window.location.href, { method: 'POST', body: body });
        if (!resp.ok) throw new Error('request failed');
        return true;
    } catch (e) {
        alert(__('Something went wrong. Please refresh and try again.'));
        return false;
    }
}

function _appendHidden(form, name, value) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    if (value !== undefined) input.value = value;
    form.appendChild(input);
}

/** generic action: ajax mode (fetch + reload tab) or navigate mode (form submit) */
async function _ppnAction(el) {
    // 1) RESOLVE FORM
    var formId = el.dataset.form;
    var form = formId ? document.getElementById(formId) : el.closest('form');
    if (!form) return;

    // 2) CONFIRM
    if (el.hasAttribute('data-confirm')) {
        var msg = el.dataset.confirm || __('Are you sure?');
        if (!confirm(msg)) return;
    }

    // 3) SET FIELD VALUES
    if (el.dataset.setField) {
        var target = document.getElementById(el.dataset.setField);
        if (target) target.value = el.dataset.setValue || '';
    }

    // 4) APPEND DATA-FIELDS ("key:val,key:val")
    if (el.dataset.fields) {
        el.dataset.fields.split(',').forEach(function(pair) {
            var parts = pair.split(':');
            var existing = form.querySelector('[name="' + parts[0] + '"]');
            if (existing) {
                existing.value = parts[1] || '';
            } else {
                _appendHidden(form, parts[0], parts[1] || '');
            }
        });
    }

    // 5) APPEND FLAGS + NONCE
    if (el.dataset.change === 'true' && !form.querySelector('[name="changeOrCreate"]')) {
        _appendHidden(form, 'changeOrCreate', 'true');
    }
    if (typeof ppnNonce !== 'undefined' && !form.querySelector('[name="_ppn_nonce"]')) {
        _appendHidden(form, '_ppn_nonce', ppnNonce);
    }

    // 6) NAVIGATE MODE: set action url + submit
    if (el.dataset.navigate) {
        var tab = el.dataset.tab ? '&tab=' + el.dataset.tab : '';
        form.setAttribute('action', `?page=network-plugin&status=${el.dataset.navigate}${tab}`);
        form.submit();
        return;
    }

    // 7) AJAX MODE: post + reload tab
    var ok = await _ppnPost(new FormData(form));
    if (ok && el.dataset.tab) _reloadTab(el.dataset.tab);
}


// ==============
// TAB NAVIGATION
// ==============

function showPPNTab(application) {
    var x = document.getElementsByClassName("tabContent");
    for (var i = 0; i < x.length; i++) {
        x[i].style.display = "none";
    }
    var tabContent = document.getElementById(application);
    if (!tabContent) return;
    tabContent.style.display = "block";
    document.querySelectorAll('.tabActive').forEach(function(el) {
        el.classList.add('tabInactive');
        el.classList.remove('tabActive');
    });
    var tab = document.getElementById(`${application}Tab`);
    if (tab) {
        tab.classList.remove('tabInactive');
        tab.classList.add('tabActive');
    }
}


// =============
// PAGE HANDLING
// =============

/** ajax: link or create a page via ppn_page_action */
async function _ppnPageAction(el) {
    const mode = el.dataset.mode;
    const target = el.dataset.target || '';
    const body = new FormData();
    body.append('action', 'ppn_page_action');
    body.append('nonce', ppnNonce);

    // 1) BUILD REQUEST
    if (mode === 'link') {
        const form = el.dataset.form ? document.getElementById(el.dataset.form) : el.closest('form');
        if (!form) return;
        for (const [key, val] of new FormData(form)) body.append(key, val);
        body.append('mode', 'link');
    } else {
        const config = PAGE_TYPES[target];
        if (!config) return;
        body.append('mode', 'create');
        body.append('target', target);
        body.append('content', config.shortcode(el.dataset.id));
        body.append('pageTitle', el.dataset.title || config.defaultTitle(el.dataset.id));
        if (config.hasTargetId && el.dataset.id) body.append('targetId', el.dataset.id);
    }

    // 2) FETCH
    let result;
    try {
        const resp = await fetch(ajaxurl, { method: 'POST', body: body });
        const data = await resp.json();
        if (!data.success) throw new Error();
        result = data.data;
    } catch (e) {
        alert(__('Something went wrong. Please refresh and try again.'));
        return;
    }

    const dialog = document.querySelector('dialog[open]');
    if (dialog) dialog.close();

    // 3) UPDATE UI
    switch (mode) {
        case 'link':
        case 'dialog': {
            const linkRow = document.getElementById('ppn-page-link-row');
            if (!linkRow) {
                const tabMap = { Program: 'programs', List: 'groups', Application: 'applications' };
                _reloadTab(tabMap[target || body.get('target')]);
                break;
            }

            const input = document.getElementById('ppn-page-link-input');
            if (input) input.value = result.permalink;

            linkRow.querySelectorAll('.ppn-page-link-view').forEach(el => el.remove());

            const viewLink = document.createElement('a');
            viewLink.href = result.permalink;
            viewLink.target = '_blank';
            viewLink.className = 'ppn-page-link-view';
            viewLink.title = __('View Page');
            viewLink.innerHTML = '<i class="material-icons-outlined">open_in_new</i>';
            linkRow.appendChild(viewLink);

            const editLink = document.createElement('a');
            editLink.href = result.edit_url;
            editLink.target = '_blank';
            editLink.className = 'ppn-page-link-view';
            editLink.title = __('Edit Page');
            editLink.innerHTML = '<i class="material-icons-outlined">edit</i>';
            linkRow.appendChild(editLink);

            const linkBtn = document.getElementById('ppn-link-page-btn');
            if (linkBtn) linkBtn.textContent = __('Change Page');
            break;
        }

        case 'list':
            _reloadTab({ Program: 'programs', List: 'groups', Application: 'applications' }[target]);
            break;

        case 'group': {
            const status = document.getElementById(`ppn-show-status-${el.dataset.id}`);
            if (status) {
                status.className = 'ppn-page-status';
                status.innerHTML = `<a class="ppn-page-status--linked" target="_blank" href="${result.permalink}">${__('View Page')}</a>`;
            }
            el.remove();
            break;
        }

        case 'singleton': {
            const link = document.createElement('a');
            link.className = 'button';
            link.href = result.edit_url;
            link.target = '_blank';
            link.textContent = el.dataset.editLabel || __('Edit Page');
            el.replaceWith(link);
            break;
        }
    }
}


// ===============
// LIST MANAGEMENT
// ===============

/** toggle show in/out of group list, update sidebar preview */
function updateListOfShows(el) {
    var inList = el.checked;
    var title = el.dataset.title;
    var list = document.getElementById('shows-in-group');
    if (!list) return;

    ppnMarkUnsaved();

    if (inList) {
        var empty = list.querySelector('.ppn-manage__show-item--empty');
        if (empty) empty.remove();

        const programId = el.dataset.programId;
        var li = document.createElement('li');
        li.className = 'ppn-manage__show-item';
        li.dataset.title = title;
        li.textContent = title;

        if (el.dataset.hasPage === '0') {
            const status = document.createElement('span');
            status.id = `ppn-show-status-${programId}`;
            status.className = 'ppn-page-status ppn-page-status--missing';
            status.textContent = __('Not Linked');
            li.appendChild(status);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ppn-icon-btn';
            btn.dataset.ppnAction = 'ppnPageAction';
            btn.dataset.mode = 'group';
            btn.dataset.target = 'Program';
            btn.dataset.id = el.dataset.programId;
            btn.dataset.title = title;
            btn.innerHTML = '<i class="material-icons-outlined">note_add</i>';
            li.appendChild(btn);
        }

        list.appendChild(li);
    } else {
        list.querySelectorAll('.ppn-manage__show-item').forEach(function(item) {
            if (item.dataset.title === title) item.remove();
        });
        if (!list.querySelector('.ppn-manage__show-item')) {
            var emptyLi = document.createElement('li');
            emptyLi.className = 'ppn-manage__show-item--empty';
            emptyLi.textContent = 'No shows in this group yet';
            list.appendChild(emptyLi);
        }
    }
}

function ppnMarkUnsaved() {
    var indicator = document.getElementById('ppn-unsaved-indicator');
    if (indicator) indicator.style.display = '';
}


// ===============
// SEARCH / FILTER
// ===============

/** filter show checkboxes by title (managelist.php) */
function filterShows(el) {
    var searchText = el.value.toLowerCase();
    var rows = document.querySelectorAll('.show-row');
    rows.forEach(function(row) {
        var title = (row.getAttribute('data-title') || '').toLowerCase();
        row.style.display = title.includes(searchText) ? '' : 'none';
    });
}

/** filter list rows by data-title within target container, skip dividers */
function filterList(el) {
    var searchText = el.value.toLowerCase();
    var container = document.getElementById(el.dataset.ppnTarget);
    if (!container) return;

    // skip empty rows + dividers
    var rows = container.querySelectorAll('.ppn-list__row:not(.ppn-list__divider):not(.ppn-list__empty)');
    rows.forEach(function(row) {
        var title = (row.getAttribute('data-title') || '').toLowerCase();
        row.style.display = title.includes(searchText) ? '' : 'none';
    });
}


// ====================
// APPLICATION APPROVAL
// ====================

function _appStatusSelectHtml(applicantId, selectedValue) {
    var safeId = escapeHtml(String(applicantId));
    var attrs = `data-ppn-action="approveProgram" data-applicant-id="${safeId}"`;
    var pSel = selectedValue === '0' ? ' selected' : '';
    var aSel = selectedValue === '1' ? ' selected' : '';
    var rSel = selectedValue === '-1' ? ' selected' : '';
    return `<select ${attrs} class="application-dropdown"><option value="0"${pSel}>${__('Pending')}</option><option value="1"${aSel}>${__('Approve')}</option><option value="-1"${rSel}>${__('Reject')}</option></select>`;
}

function _appStatusLabelHtml(selectedValue) {
    var label = selectedValue === '1' ? __('Approved') : (selectedValue === '-1' ? __('Rejected') : __('Pending'));
    var mod = selectedValue === '1' ? 'approved' : (selectedValue === '-1' ? 'rejected' : 'pending');
    return `<span class="ppn-app-status-label ppn-app-status-label--${mod}">${label}</span>`;
}

/** approve/reject/undo/delete applicant via ajax, sync table + modal */
async function approveProgram(el) {
    var applicantId = el.dataset.applicantId;
    var isDelete = el.dataset.delete === 'true';

    // 1) DELETE VIA BUTTON
    if (isDelete) {
        if (!confirm(__('Are you sure you want to delete this application?'))) return;

        var body = new FormData();
        body.append('appAction', 'delete');
        body.append('applicantId', applicantId);
        body.append('changeOrCreate', 'true');
        if (typeof ppnNonce !== 'undefined') body.append('_ppn_nonce', ppnNonce);

        if (!await _ppnPost(body)) return;

        // remove row + close/remove dialog
        var tableCell = document.getElementById(`app-status-${applicantId}`);
        if (tableCell) {
            var row = tableCell.closest('.ppn-list__row');
            if (row) row.remove();
        }
        var openDialog = document.querySelector(`dialog#ppn-app-${applicantId}`);
        if (openDialog && openDialog.open) openDialog.close();
        var modalWrap = document.getElementById(`ppn-app-${applicantId}`);
        if (modalWrap) modalWrap.remove();
        return;
    }

    // 2) STATUS CHANGE VIA SELECT
    if (el.tagName !== 'SELECT') return;
    var selectedValue = el.value;
    var action;
    if (selectedValue === '1') action = 'approve';
    else if (selectedValue === '-1') action = 'disapprove';
    else action = 'undo';

    var body = new FormData();
    body.append('appAction', action);
    body.append('applicantId', applicantId);
    body.append('changeOrCreate', 'true');
    if (typeof ppnNonce !== 'undefined') body.append('_ppn_nonce', ppnNonce);

    if (!await _ppnPost(body)) return;

    // 3) SYNC UI: table label + modal select, reload shows tab
    var tableCell = document.getElementById(`app-status-${applicantId}`);
    var modalCell = document.getElementById(`app-modal-status-${applicantId}`);
    if (tableCell) tableCell.innerHTML = _appStatusLabelHtml(selectedValue);
    if (modalCell) modalCell.innerHTML = _appStatusSelectHtml(applicantId, selectedValue);
    _reloadTab('programs');
}

/** refetch page html and swap tab content by id */
async function _reloadTab(tabId) {
    try {
        // 1) BUILD URL w/ tab param
        var tabParam = tabId === 'programs' ? 'shows' : tabId;
        var url = window.location.href.split('#')[0];
        if (url.indexOf('tab=') === -1) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'tab=' + tabParam;
        }

        // 2) FETCH + PARSE response html
        var resp = await fetch(url, { credentials: 'same-origin' });
        if (!resp.ok) return;
        var doc = new DOMParser().parseFromString(await resp.text(), 'text/html');

        // 3) SWAP tab content or full reload as fallback
        var fresh = doc.getElementById(tabId);
        var current = document.getElementById(tabId);
        if (fresh && current) {
            current.querySelectorAll('dialog[open]').forEach(function(d) { d.close(); });
            current.innerHTML = fresh.innerHTML;
        } else {
            location.reload();
        }
    } catch (e) {
        // silent fail, updates on next page load
    }
}


// ==================
// PROGRAM MANAGEMENT
// ==================

/** open page-select dialog pre-filled w/ program shortcode */
function editPageForProgram(el) {
    var programId = el.dataset.programId;
    var programTitle = el.dataset.programTitle || '';
    document.getElementById('select-page-target-id').value = programId;
    document.getElementById('page-select-ppn').value = el.dataset.linkPage;
    document.getElementById('ppn-program-shortcode').value = `[ppn-program id="${programId}"]`;
    // populate create-new-page btn w/ program context
    var createBtn = document.getElementById('ppn-create-page-btn');
    if (createBtn) {
        createBtn.dataset.id = programId;
        createBtn.dataset.title = programTitle;
    }

    const editLink = document.getElementById('ppn-edit-page-link');
    if (editLink) {
        if (el.dataset.linkPage) {
            editLink.href = `post.php?post=${el.dataset.linkPage}&action=edit`;
            editLink.style.display = '';
        } else {
            editLink.style.display = 'none';
        }
    }

    var dialog = document.getElementById('selectPageBox');
    if (dialog) dialog.showModal();
}

function addToGroup(el) {
    var programId = el.dataset.programId;
    var input = document.getElementById('add-program-to-group');
    if (input) input.value = programId;
    var dialog = document.getElementById('addToGroup');
    if (dialog) dialog.showModal();
}


// ========
// SETTINGS
// ========

/** save tos url via fetch, show saved/error feedback */
function saveTosUrl(el) {
    const form = document.getElementById(el.dataset.formId);
    if (!form) return;
    const btn = form.querySelector('button[data-ppn-action="saveTosUrl"]');
    if (!btn) return;
    const originalText = btn.textContent;

    btn.textContent = 'Saving...';
    btn.disabled = true;

    fetch(window.location.href, {
        method: 'POST',
        body: new FormData(form)
    }).then(function(response) {
        if (response.ok) {
            btn.textContent = 'Saved!';

            // clear error message if existing
            const existing = document.getElementById('ppn-tos-error');
            if (existing) existing.remove();

            setTimeout(function() {
                btn.textContent = originalText;
                btn.disabled = false;
            }, 2000);
        } else {
            btn.textContent = 'Error';
            btn.disabled = false;
            const existing = document.getElementById('ppn-tos-error');
            if (existing) existing.remove();
            const errDiv = document.createElement('div');
            errDiv.id = 'ppn-tos-error';
            errDiv.className = 'error powerpress-error inline';
            errDiv.textContent = 'Please enter a valid URL for your Terms of Service';
            form.parentNode.insertBefore(errDiv, form);

            setTimeout(function() {
                btn.textContent = originalText;
                btn.disabled = false;
            }, 2000); 
        }
    }).catch(function() {
        btn.textContent = 'Error';
        btn.disabled = false;
    });
}


// ====
// INIT
// ====

// strip irrelevant fields from submit application form
function ppnInitSubmitApp() {
    var feedUrl = document.getElementById('feedUrl');
    if (!feedUrl) return;
    if (!feedUrl.hasAttribute('readonly')) {
        var addInfo = document.getElementById('addInfo');
        if (addInfo) addInfo.remove();
    } else {
        var find = document.getElementById('findProgram');
        var back = document.getElementById('backProgram');
        if (find) find.remove();
        if (back) back.remove();
    }
}

async function addProgramToNetwork(el) {
    const icon = document.getElementById(el.id);

    // ignore clicks on shows that have already been added
    if(!icon.src.includes('circlecheck_blue')){
        const body = new FormData();
        body.append('action', 'add_program_to_network');
        body.append('nonce', ppnNonce);
        body.append('program_id', el.dataset.programId);
        body.append('network_id', el.dataset.networkId);
        body.append('program_title', el.dataset.title || '');

        const resp = await fetch(ajaxurl, { method: 'POST', body: body });
        const data = await resp.json();

        if(data.success === true){
            icon.src = el.dataset.updatedSrc;
            if(data.data && data.data.message) showToast(data.data.message, { id: 'ppn-add-toast' });
        } else {
            // do we need any error messaging?
        }
    }
}


// ================
// EVENT DELEGATION
// ================

document.addEventListener('DOMContentLoaded', function() {
    // data-ppn-action -> handler map
    var handlers = {
        ppnAction: _ppnAction,
        ppnPageAction: _ppnPageAction,
        showPPNTab: function(el) { showPPNTab(el.dataset.tab); },
        approveProgram: approveProgram,
        editPageForProgram: editPageForProgram,
        addToGroup: addToGroup,
        ppCopyText: function(el) { ppCopyText(el.dataset.inputId, el); },
        ppnDialog: ppnDialog,
        ppnDialogClose: ppnDialogClose,
        saveTosUrl: saveTosUrl,
        updateListOfShows: updateListOfShows,
        filterShows: filterShows,
        filterList: filterList,
        addProgramToNetwork: addProgramToNetwork
    };

    function dispatch(action, el) {
        if (handlers[action]) handlers[action](el);
    }

    // CLICK DELEGATION (backdrop close, toggle, action dispatch)
    document.addEventListener('click', function(e) {
        // backdrop click closes open dialogs
        if (e.target.tagName === 'DIALOG' && e.target.open) {
            e.target.close();
            return;
        }

        // collapse/expand toggle
        var toggle = e.target.closest('.ppn-toggle');
        if (toggle) {
            var collapsed = toggle.classList.toggle('ppn-toggle--collapsed');
            var chevron = toggle.querySelector('.ppn-toggle__chevron');
            if (chevron) chevron.textContent = collapsed ? 'expand_more' : 'expand_less';
            return;
        }

        // action dispatch
        var el = e.target.closest('[data-ppn-action]');
        if (!el) return;
        var action = el.dataset.ppnAction;
        // skip change/keyup only handlers
        if (action === 'updateListOfShows' || action === 'filterShows' || action === 'filterList') return;
        // selects handle via change event
        if (el.tagName === 'SELECT') return;
        e.preventDefault();
        dispatch(action, el);
    });

    // CHANGE DELEGATION
    document.addEventListener('change', function(e) {
        var el = e.target.closest('[data-ppn-action]');
        if (!el) return;
        dispatch(el.dataset.ppnAction, el);
    });

    // KEYUP DELEGATION
    document.addEventListener('keyup', function(e) {
        var el = e.target.closest('[data-ppn-action]');
        if (!el) return;
        dispatch(el.dataset.ppnAction, el);
    });

    // NETWORK SHOW SEARCH ENTER KEY
    var searchInput = document.getElementById('network-show-search-input');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (typeof submitNetworkShowSearch === 'function') {
                    submitNetworkShowSearch();
                }
            }
        });
    }

    // TOS URL ENTER KEY
    const tosForm = document.getElementById('tosUrlForm');
    if (tosForm) {
        tosForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = tosForm.querySelector('[data-ppn-action="saveTosUrl"]');
            if (btn) btn.click();
        });
    }

    // AUTO-INIT
    ppnInitSubmitApp();

    var editForm = document.getElementById('editForm') || document.getElementById('createForm');
    if (editForm) {
        editForm.addEventListener('input', ppnMarkUnsaved);
    }
});
