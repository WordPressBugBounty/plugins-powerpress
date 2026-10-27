/**
 * PowerPress Network Frontend
 *
 * SECTIONS:
 *   LOAD MORE EPISODES
 *   SELECT EPISODE
 *   CAROUSEL PAGING
 */

// ==================
// LOAD MORE EPISODES
// ==================

/** append the next page of episode rows, retiring the button at the end of the archive */
async function ppnLoadMore(btn) {
    const wrap = btn.closest('.ppn-grid-header') || btn.parentElement.parentElement;
    const list = wrap ? wrap.querySelector('[data-ppn-episodes]') : null;
    if (!list) {
        return;
    }

    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = btn.dataset.loadingLabel || 'Loading...';

    const body = new FormData();
    body.append('action', 'ppn_episodes');
    body.append('program_id', btn.dataset.program);
    body.append('style', btn.dataset.style);
    body.append('limit', btn.dataset.limit);
    body.append('start', btn.dataset.offset);
    body.append('selected', btn.dataset.selected || '');

    let payload;
    try {
        const response = await fetch(btn.dataset.ajax, { method: 'POST', body: body });
        if (!response.ok) {
            throw new Error('request failed');
        }
        payload = await response.json();
    } catch (e) {
        btn.disabled = false;
        btn.textContent = original;
        return;
    }

    // only an authoritative "no more" retires the button, an error must not read as the end
    if (!payload || !payload.success || !payload.data) {
        btn.disabled = false;
        btn.textContent = original;
        return;
    }

    const data = payload.data;
    const added = data.count || 0;

    if (data.html) {
        list.insertAdjacentHTML('beforeend', data.html);
        btn.dataset.offset = String(parseInt(btn.dataset.offset, 10) + added);
        list.dataset.loaded = btn.dataset.offset;
    }

    if (data.has_more && added > 0) {
        btn.disabled = false;
        btn.textContent = original;
    } else {
        btn.remove();
    }
}

document.addEventListener('click', function (event) {
    const btn = event.target.closest('[data-ppn-loadmore]');
    if (btn) {
        event.preventDefault();
        ppnLoadMore(btn);
    }
});

// ==============
// SELECT EPISODE
// ==============

/** the live mediaelement player inside a region, if it has been enhanced */
function ppnLivePlayer(region) {
    const mejs = window.mejs;
    if (!region || !mejs || !mejs.players) {
        return null;
    }

    for (const key in mejs.players) {
        const player = mejs.players[key];
        if (player && player.node && region.contains(player.node)) {
            return player;
        }
    }

    return null;
}

/** the media url a rendered panel's player points at */
function ppnPanelSrc(scope) {
    const source = scope ? scope.querySelector('source[src], video[src], audio[src]') : null;

    return source ? source.getAttribute('src') : '';
}

/** rebuild the panel from scratch, correct but it tears down the player's chrome */
function ppnReplacePanel(panel, html) {
    panel.outerHTML = html;

    // a swapped in <video> or <audio> is bare until mediaelement enhances it
    if (window.wp && window.wp.mediaelement && typeof window.wp.mediaelement.initialize === 'function') {
        window.wp.mediaelement.initialize();
    }
}

/** swap the panel around its player, re-pointing the element so its chrome never flashes */
function ppnSwapPanel(panel, html) {
    const incoming = document.createElement('div');
    incoming.innerHTML = html;

    const live = {
        head: panel.querySelector('[data-ppn-panel-head]'),
        player: panel.querySelector('[data-ppn-panel-player]'),
        notes: panel.querySelector('[data-ppn-panel-notes]')
    };
    const next = {
        head: incoming.querySelector('[data-ppn-panel-head]'),
        player: incoming.querySelector('[data-ppn-panel-player]'),
        notes: incoming.querySelector('[data-ppn-panel-notes]')
    };

    const player = ppnLivePlayer(live.player);
    const nextSrc = ppnPanelSrc(next.player);

    if (!live.head || !live.player || !next.head || !next.player || !player || !nextSrc) {
        ppnReplacePanel(panel, html);
        return;
    }

    live.head.replaceWith(next.head);

    if (live.notes && next.notes) {
        live.notes.replaceWith(next.notes);
    } else if (live.notes) {
        live.notes.remove();
    } else if (next.notes) {
        live.player.after(next.notes);
    }

    if (nextSrc !== ppnPanelSrc(live.player)) {
        player.setSrc(nextSrc);
        player.load();
    }

    panel.removeAttribute('aria-busy');
}

/** the panel this list owns, keyed so a second playlist on the page drives its own player */
function ppnPanelFor(list) {
    const keyed = document.querySelector('[data-ppn-panel][data-ppn-panel-for="' + (list.dataset.program || '') + '"]');

    return keyed || document.querySelector('[data-ppn-panel]');
}

// sequence of the request a panel is showing, so a slow reply cannot overwrite a newer pick
const ppnSelectSeq = new WeakMap();

/** swap in the panel for a picked episode, falling back to following the link */
async function ppnSelectEpisode(link, list) {
    const slug = link.dataset.ppnSelect;
    const panel = ppnPanelFor(list);
    if (!panel) {
        window.location.href = link.href;
        return;
    }

    const seq = (ppnSelectSeq.get(list) || 0) + 1;
    ppnSelectSeq.set(list, seq);

    panel.setAttribute('aria-busy', 'true');

    const body = new FormData();
    body.append('action', 'ppn_episode_panel');

    // page size is canonical, a running loaded count mints one cache row per distinct value
    const pageSize = Math.max(1, parseInt(list.dataset.limit, 10) || 20);
    const index = Math.max(0, parseInt(link.dataset.ppnIndex, 10) || 0);

    body.append('program_id', list.dataset.program);
    body.append('limit', pageSize);
    body.append('start', Math.floor(index / pageSize) * pageSize);
    body.append('selected', slug);
    body.append('media', link.dataset.ppnMedia || '');

    let payload;
    try {
        const response = await fetch(list.dataset.ajax, { method: 'POST', body: body });
        if (!response.ok) {
            throw new Error('request failed');
        }
        payload = await response.json();
    } catch (e) {
        window.location.href = link.href;
        return;
    }

    if (!payload || !payload.success || !payload.data || !payload.data.html) {
        window.location.href = link.href;
        return;
    }

    // a click that landed after this request was sent already owns the panel
    if (ppnSelectSeq.get(list) !== seq) {
        return;
    }

    ppnSwapPanel(panel, payload.data.html);

    list.querySelectorAll('.ppn-episode-row--current').forEach(function (row) {
        row.classList.remove('ppn-episode-row--current');
        row.removeAttribute('aria-current');
    });

    const row = link.closest('.ppn-episode-row');
    if (row) {
        row.classList.add('ppn-episode-row--current');
        row.setAttribute('aria-current', 'true');
    }

    if (window.history && window.history.pushState) {
        window.history.pushState({ ppnEpisode: slug }, '', link.href);
    }
}

document.addEventListener('click', function (event) {
    const link = event.target.closest('[data-ppn-select]');
    if (!link) {
        return;
    }

    const list = link.closest('[data-ppn-episodes]');
    if (!list || !list.dataset.ajax) {
        return;
    }

    event.preventDefault();
    ppnSelectEpisode(link, list);
});

// ===============
// CAROUSEL PAGING
// ===============

const ppnCarouselAt = new WeakMap();

/** the rail a step button drives, matched on the id the button carries */
function ppnCarouselRail(button) {
    const id = button.dataset.ppnCarousel;

    return id ? document.querySelector('[data-ppn-carousel-rail="' + id + '"]') : null;
}

/** distance between the two identical passes, zero when the rail carries only one */
function ppnCarouselSpan(rail) {
    if (!rail.dataset.ppnCarouselLoop) return 0;

    const cards = rail.children;
    const second = cards[cards.length / 2];

    return second ? second.offsetLeft - cards[0].offsetLeft : 0;
}

/** where the rail is heading, which runs ahead of scrollLeft while a smooth step is still playing */
function ppnCarouselPos(rail) {
    const pending = ppnCarouselAt.get(rail);

    return typeof pending === 'number' ? pending : rail.scrollLeft;
}

/** move one page, borrowing the second pass so neither end is ever a dead stop */
function ppnCarouselStep(rail, direction) {
    const page = rail.clientWidth;
    const span = ppnCarouselSpan(rail);
    let at = ppnCarouselPos(rail);

    if (span && at >= span) {
        at -= span;
        rail.scrollTo({ left: at, behavior: 'instant' });
    }

    if (span && direction < 0 && at < page) {
        at += span;
        rail.scrollTo({ left: at, behavior: 'instant' });
    }

    let target = at + (page * direction);
    if (!span) {
        target = Math.max(0, Math.min(target, rail.scrollWidth - page));
    }

    ppnCarouselAt.set(rail, target);
    rail.scrollTo({ left: target, behavior: getComputedStyle(rail).scrollBehavior });
}

/** the buttons only earn their place once one pass of the cards outruns the rail */
function ppnCarouselSyncSteps(rail) {
    const id = rail.dataset.ppnCarouselRail;
    const overflows = (ppnCarouselSpan(rail) || rail.scrollWidth) > rail.clientWidth + 1;

    document.querySelectorAll('[data-ppn-carousel="' + id + '"]').forEach(function (button) {
        button.hidden = !overflows;
    });
}

/** every rail on the page, re-measured whenever the column they sit in changes width */
function ppnCarouselSyncAll() {
    document.querySelectorAll('[data-ppn-carousel-rail]').forEach(ppnCarouselSyncSteps);
}

document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-ppn-carousel]');
    if (!button) return;

    const rail = ppnCarouselRail(button);
    if (!rail) return;

    event.preventDefault();
    ppnCarouselStep(rail, button.dataset.ppnCarouselStep === 'prev' ? -1 : 1);
});

document.addEventListener('DOMContentLoaded', ppnCarouselSyncAll);
window.addEventListener('resize', ppnCarouselSyncAll);
