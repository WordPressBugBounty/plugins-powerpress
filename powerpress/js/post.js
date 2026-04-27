/**
 * PowerPress Post Entry Point
 * unified ES6 module for episode editing (replaces metabox-episode.js, metabox-channel.js)
 */

// ============================================
//          PCI MANAGER IMPORTS
// ============================================

import { initLocationManager } from './modules/pci/LocationManager.js';
import { initCreditsManager } from './modules/pci/CreditsManager.js';
import { initValueRecipientManager } from './modules/pci/ValueRecipientsManager.js';
import { initSoundbitesManager } from './modules/pci/SoundbitesManager.js';
import { initSocialInteractManager } from './modules/pci/SocialInteractManager.js';
import { initTxtTagManager } from './modules/pci/TxtTagManager.js';
import { initAlternateEnclosureManager } from './modules/pci/AlternateEnclosureManager.js';
import { initContentLinksManager } from './modules/pci/ContentLinksManager.js';

// ============================================
//          UTILITY IMPORTS
// ============================================

import { toggleVisibility, initCharCounter } from './utils/dom-utils.js';

// ============================================
//          GLOBAL EXPORTS
// ============================================

// pci managers (for PHP template init calls)
window.initLocationManager = initLocationManager;
window.initCreditsManager = initCreditsManager;
window.initValueRecipientManager = initValueRecipientManager;
window.initSoundbitesManager = initSoundbitesManager;
window.initSocialInteractManager = initSocialInteractManager;
window.initTxtTagManager = initTxtTagManager;
window.initAlternateEnclosureManager = initAlternateEnclosureManager;
window.initContentLinksManager = initContentLinksManager;

// utilities
window.toggleVisibility = toggleVisibility;
window.initCharCounter = initCharCounter;

// ============================================
//          AUTO-INITIALIZATION
// ============================================

// initialize character counters for any element with data-char-counter attribute
// usage: <textarea data-char-counter="counterId" data-char-warn="250">
// data-char-warn is optional; omit for no warning color
document.querySelectorAll('[data-char-counter]').forEach(input => {
    const counterId = input.dataset.charCounter;
    const warnAt = input.dataset.charWarn ? parseInt(input.dataset.charWarn, 10) : null;
    initCharCounter(input.id, counterId, warnAt);
});

// Auto-Grow Text area for Show Notes in the post editor instead of allowing scroll
document.querySelectorAll('[id^="powerpress_shownotes_"]').forEach(ta => {
    ta.style.overflow = 'hidden';
    const grow = () => {
        ta.style.height = 'auto';
        const lineHeight = parseFloat(getComputedStyle(ta).lineHeight) || 20;
        ta.style.height = (ta.scrollHeight + lineHeight) + 'px';
    };
    ta.addEventListener('input', grow);
    grow();
});
