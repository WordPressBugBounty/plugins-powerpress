/**
 * ProgramCardManager
 * coordinates the program selector, ShowInfoCard, and StatsWidget modules
 * owns the program selector and dispatches updates to child modules
 */

import { ShowInfoCard } from './ShowInfoCard.js';
import { StatsWidget } from './StatsWidget.js';

// ============================================
//          PROGRAM CARD MANAGER
// ============================================

const ProgramCardManager = {

    // ==========================================
    //              CONFIGURATION
    // ==========================================

    /** @type {{nonce: string, ajaxUrl: string, currentProgram: string, defaultProgram: string}} */
    config: {
        nonce: '',
        ajaxUrl: '',
        currentProgram: '',
        defaultProgram: ''
    },

    /** @type {{showInfoCard: Object, statsWidget: Object}} */
    modules: {
        showInfoCard: ShowInfoCard,
        statsWidget: StatsWidget
    },

    // ==========================================
    //              INITIALIZATION
    // ==========================================

    init() {
        const programCard = document.getElementById('pp-program-card');
        const statsWidget = document.getElementById('pp-stats-widget');

        // 1) READ CONFIG FROM DOM
        if (programCard) {
            this.config.currentProgram = programCard.dataset.programKeyword || '';
            this.config.defaultProgram = programCard.dataset.defaultProgram || '';
        }

        if (statsWidget) {
            this.config.nonce = statsWidget.dataset.nonce || '';
            this.config.ajaxUrl = statsWidget.dataset.ajaxUrl || window.ajaxurl || '';

            // 2) CHECK FOR DEFERRED LOADING
            if (statsWidget.dataset.deferred === '1') {
                this.loadDeferredStats();
                return;
            }
        }

        // 3) INIT CHART AND EVENT LISTENERS
        this.modules.statsWidget.init();
        document.body.addEventListener('change', (e) => this.onSelectorChange(e));
        document.body.addEventListener('click', (e) => this.onInlineSelectorClick(e));
    },

    /** loads stats via ajax (skeleton renders first for faster initial paint) */
    async loadDeferredStats() {
        // 1) BUILD REQUEST
        const statsWidget = document.getElementById('pp-stats-widget');
        const formData = new FormData();
        formData.append('action', 'powerpress_load_stats');
        formData.append('nonce', this.config.nonce);
        if (this.config.currentProgram) {
            formData.append('program_keyword', this.config.currentProgram);
        }
        if (statsWidget?.dataset.stacked === '1') {
            formData.append('stacked', '1');
        }

        try {
            // 2) FETCH DATA
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                console.error('Deferred stats HTTP error:', response.status);
                this._showDeferredError();
                return;
            }

            // 3) PARSE RESPONSE
            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Deferred stats JSON parse error:', responseText.substring(0, 500));
                this._showDeferredError();
                return;
            }

            // 4) UPDATE UI OR SHOW ERROR
            if (data.success) {
                this.updateModules(data.data);
                this.modules.statsWidget.init();
            } else {
                console.error('Deferred stats load failed:', data.data);
                this._showDeferredError();
            }
        } catch (error) {
            console.error('Deferred stats load error:', error);
            this._showDeferredError();
        }

        // 5) SETUP EVENT LISTENERS
        document.body.addEventListener('change', (e) => this.onSelectorChange(e));
        document.body.addEventListener('click', (e) => this.onInlineSelectorClick(e));
    },

    /** helper for deferred load errors */
    _showDeferredError() {
        this.modules.statsWidget.hideSpinner();
        this.modules.statsWidget.showError('Unable to load stats. Please refresh the page.');
    },

    // ==========================================
    //              EVENT HANDLERS
    // ==========================================

    /**
     * @param {Event} e
     */
    onSelectorChange(e) {
        const selector = e.target.closest('#pp-stats-program-select');
        if (!selector) return;

        const programKeyword = selector.value;
        if (!programKeyword || programKeyword === this.config.currentProgram) return;

        this.switchProgram(programKeyword);
    },

    /**
     * @param {Event} e
     */
    onInlineSelectorClick(e) {
        const item = e.target.closest('.pp-program-selector-item:not(.pp-program-selector-item--disabled)');
        if (!item) return;

        const keyword = item.dataset.keyword;
        if (!keyword || keyword === this.config.currentProgram) return;

        // update UI immediately
        const trigger = document.getElementById('pp-program-selector-trigger');
        const dropdown = document.getElementById('pp-program-selector-dropdown');
        if (trigger && dropdown) {
            const nameSpan = trigger.querySelector('.pp-program-selector-name');
            if (nameSpan) nameSpan.textContent = item.textContent.trim();
            dropdown.classList.remove('pp-open');
            trigger.classList.remove('pp-open');
            dropdown.querySelectorAll('.pp-program-selector-item').forEach((i) => {
                i.classList.toggle('pp-program-selector-item--active', i === item);
            });
        }

        this.switchProgram(keyword);
    },

    // ==========================================
    //              PROGRAM SWITCHING
    // ==========================================

    /**
     * @param {string} programKeyword
     */
    async switchProgram(programKeyword) {
        const previousProgram = this.config.currentProgram;
        this.modules.statsWidget.showLoading();

        // 1) BUILD REQUEST
        const formData = new FormData();
        formData.append('action', 'powerpress_switch_program');
        formData.append('program_keyword', programKeyword);
        formData.append('nonce', this.config.nonce);

        const container = this.modules.statsWidget.getContainer();
        if (container && container.dataset.stacked === '1') {
            formData.append('stacked', '1');
        }

        try {
            // 2) FETCH AND PARSE
            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            // 3) UPDATE OR ROLLBACK
            if (data.success) {
                this.config.currentProgram = programKeyword;
                this.updateModules(data.data);
            } else {
                this._handleSwitchError(previousProgram, data.data);
            }
        } catch (error) {
            this._handleSwitchError(previousProgram, error);
        }
    },

    /**
     * @param {string} previousProgram
     * @param {*} error
     */
    _handleSwitchError(previousProgram, error) {
        console.error('Program switch failed:', error);
        this.modules.statsWidget.hideLoading();
        this.rollbackSelector(previousProgram);
        this.modules.statsWidget.showError('Unable to switch programs. Please try again.');
    },

    /**
     * @param {string} programKeyword
     */
    rollbackSelector(programKeyword) {
        const selector = document.getElementById('pp-stats-program-select');
        if (selector && programKeyword) {
            selector.value = programKeyword;
        }
    },

    /**
     * @param {string} programKeyword
     * @returns {boolean}
     */
    isDefaultProgram(programKeyword) {
        return programKeyword === this.config.defaultProgram;
    },

    // ==========================================
    //              MODULE UPDATES
    // ==========================================

    /**
     * @param {Object} responseData - ajax response with html and/or program_info
     */
    updateModules(responseData) {
        const isDefault = this.isDefaultProgram(this.config.currentProgram);

        // 1) UPDATE STATS WIDGET
        if (responseData.stats_widget_html) {
            this.modules.statsWidget.replaceHtml(responseData.stats_widget_html, {
                nonce: this.config.nonce,
                ajaxUrl: this.config.ajaxUrl
            });
        }

        // 2) UPDATE SHOW INFO CARD
        if (responseData.show_card_html) {
            this.modules.showInfoCard.replaceHtml(responseData.show_card_html);
        } else if (responseData.program_info) {
            if (isDefault) {
                // default program: prefer form values over api data
                const formData = this.modules.showInfoCard.getFormData();
                const mergedInfo = { ...responseData.program_info };
                if (formData.title) mergedInfo.title = formData.title;
                if (formData.author) mergedInfo.author = formData.author;
                if (formData.description) mergedInfo.description = formData.description;
                if (formData.image) mergedInfo.image = formData.image;
                this.modules.showInfoCard.update(mergedInfo);
            } else {
                this.modules.showInfoCard.update(responseData.program_info);
            }
        }

        // 3) SYNC DATA ATTRIBUTE
        const programCard = document.getElementById('pp-program-card');
        if (programCard) {
            programCard.dataset.programKeyword = this.config.currentProgram;
        }
    },

    /** @returns {string} */
    getCurrentProgram() {
        return this.config.currentProgram;
    }
};

export function initProgramCardManager() {
    ProgramCardManager.init();
}

export { ProgramCardManager };
