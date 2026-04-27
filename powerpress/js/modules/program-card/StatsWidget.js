/**
 * StatsWidget Module
 * thin wrapper for stats API calls and state management
 * delegates all rendering to PowerpressChart
 */

import { PowerpressChart } from './PowerpressChart.js';

// ============================================
//              STATS WIDGET
// ============================================

const StatsWidget = {

    // ==========================================
    //              CONFIGURATION
    // ==========================================

    containerSelector: "#pp-stats-widget",
    canvasSelector: "#pp-stats-widget__canvas",
    cogBtnSelector: "#pp-stats-cog-btn",
    programSelectorTrigger: "#pp-program-selector-trigger",
    programSelectorDropdown: "#pp-program-selector-dropdown",

    refreshCooldown: 5000,
    lastRefreshTime: 0,

    chartOptions: {
        showTrendline: true,
        showAvgLine: true,
        showValues: true,
        defaultView: "week",
        defaultScale: "linear",
    },

    // ==========================================
    //              STATE
    // ==========================================

    chartInstance: null,
    currentView: "week",
    currentScale: "linear",
    weekData: null,
    monthData: null,
    weekAverage: null,
    monthAverage: null,
    statsTier: "standard",
    hasAuth: true,
    upgradeUrl: "",
    _previousFocus: null,

    // ==========================================
    //              GETTERS
    // ==========================================

    getContainer() {
        return document.querySelector(this.containerSelector);
    },

    getCanvas() {
        return document.querySelector(this.canvasSelector);
    },

    getAjaxConfig() {
        const container = this.getContainer();
        if (!container) return null;

        const nonce = container.dataset.nonce;
        const ajaxUrl = container.dataset.ajaxUrl;
        if (!nonce || !ajaxUrl) return null;

        return {
            nonce,
            ajaxUrl,
            programKeyword: container.dataset.programKeyword || null,
        };
    },

    // ==========================================
    //              INITIALIZATION
    // ==========================================

    init() {
        const container = this.getContainer();
        if (!container) {
            console.log("StatsWidget: container not found");
            return;
        }

        this.loadChartOptions();

        const chartData = container.dataset.chart;
        if (!chartData) {
            console.log("StatsWidget: no chart data attribute");
            return;
        }

        try {
            const data = JSON.parse(chartData);
            this._setData(data, "week");
            this._applyTierRestrictions();

            if (this.currentView === "month" && this.canAccessFeature("month")) {
                this.fetchMonthData();
            } else {
                this.currentView = "week";
                this.renderChart(data);
            }

            this.initSettingsModal();
            this.initProgramSelector();
        } catch (e) {
            console.error("StatsWidget: failed to parse chart data", e);
        }
    },

    // ==========================================
    //              DATA MANAGEMENT
    // ==========================================

    _setData(data, view) {
        if (view === "week") {
            this.weekData = data;
            this.weekAverage = data.display_average || null;
        } else {
            this.monthData = data;
            this.monthAverage = data.display_average || null;
        }
        this.statsTier = data.tier || "standard";
        this.hasAuth = data.has_auth !== undefined ? data.has_auth : true;
        this.upgradeUrl = data.upgrade_url || "";
    },

    _applyTierRestrictions() {
        this.currentScale = this.statsTier === "basic" ? "linear" : this.chartOptions.defaultScale;
        this.currentView = this.statsTier === "basic" ? "week" : this.chartOptions.defaultView;
    },

    _clearCache() {
        this.weekData = null;
        this.monthData = null;
        this.weekAverage = null;
        this.monthAverage = null;
        this.currentView = "week";
    },

    // ==========================================
    //              RENDERING (delegates to PowerpressChart)
    // ==========================================

    renderChart(data) {
        if (this.chartInstance) {
            PowerpressChart.destroy(this.chartInstance);
            this.chartInstance = null;
        }

        const canvas = this.getCanvas();
        if (!canvas) {
            console.log("StatsWidget: canvas not found");
            return;
        }

        this.chartInstance = PowerpressChart.render(canvas, data, {
            tier: this.statsTier,
            scale: this.currentScale,
            showTrendline: this.chartOptions.showTrendline,
            showAvgLine: this.chartOptions.showAvgLine,
            showValues: this.chartOptions.showValues,
        });

        this.updateChartDescription(data);
    },

    // ==========================================
    //              SETTINGS PERSISTENCE
    // ==========================================

    loadChartOptions() {
        try {
            const saved = localStorage.getItem("pp_stats_chart_options");
            if (saved) {
                this.chartOptions = { ...this.chartOptions, ...JSON.parse(saved) };
            }
        } catch (e) {
            console.log("StatsWidget: could not load chart options");
        }
    },

    saveChartOptions() {
        try {
            localStorage.setItem("pp_stats_chart_options", JSON.stringify(this.chartOptions));
        } catch (e) {
            console.log("StatsWidget: could not save chart options");
        }
    },

    // ==========================================
    //              TIER GATING
    // ==========================================

    canAccessFeature(feature) {
        const tierOrder = { basic: 0, standard: 1, advanced: 2 };
        const featureRequirements = { month: 1, hourly: 2, episodes: 2 };
        return (tierOrder[this.statsTier] || 0) >= (featureRequirements[feature] || 0);
    },

    // ==========================================
    //              SETTINGS MODAL
    // ==========================================

    initSettingsModal() {
        const cogBtn = document.querySelector(this.cogBtnSelector);
        const settingsRow = document.getElementById("pp-stats-settings-row");
        const refreshBtn = document.getElementById("pp-stats-refresh-btn");

        if (cogBtn && !cogBtn.dataset.listenerAttached) {
            cogBtn.dataset.listenerAttached = "1";
            cogBtn.addEventListener("click", () => {
                if (cogBtn.dataset.tier === "basic") {
                    this.showUpgradePrompt("settings");
                    return;
                }
                this.toggleSettingsRow();
            });
        }

        if (refreshBtn && !refreshBtn.dataset.listenerAttached) {
            refreshBtn.dataset.listenerAttached = "1";
            refreshBtn.addEventListener("click", () => this.handleRefresh());
        }

        if (settingsRow) {
            this.syncSettingsCheckboxes();
            settingsRow.querySelectorAll("input[type='checkbox'], input[type='radio']").forEach((input) => {
                if (!input.dataset.listenerAttached) {
                    input.dataset.listenerAttached = "1";
                    input.addEventListener("change", () => this.handleSettingChange());
                }
            });
        }
    },

    syncSettingsCheckboxes() {
        const trendline = document.getElementById("pp-stats-opt-trendline");
        const avgline = document.getElementById("pp-stats-opt-avgline");
        const values = document.getElementById("pp-stats-opt-values");

        if (trendline) trendline.checked = this.chartOptions.showTrendline;
        if (avgline) avgline.checked = this.chartOptions.showAvgLine;
        if (values) values.checked = this.chartOptions.showValues;

        document.querySelectorAll('input[name="pp_stats_scale"]').forEach((radio) => {
            radio.checked = radio.value === this.chartOptions.defaultScale;
        });
        document.querySelectorAll('input[name="pp_stats_view"]').forEach((radio) => {
            radio.checked = radio.value === this.chartOptions.defaultView;
        });
    },

    handleSettingChange() {
        const trendline = document.getElementById("pp-stats-opt-trendline");
        const avgline = document.getElementById("pp-stats-opt-avgline");
        const values = document.getElementById("pp-stats-opt-values");
        const selectedScale = document.querySelector('input[name="pp_stats_scale"]:checked');
        const selectedView = document.querySelector('input[name="pp_stats_view"]:checked');

        this.chartOptions.showTrendline = trendline ? trendline.checked : true;
        this.chartOptions.showAvgLine = avgline ? avgline.checked : true;
        this.chartOptions.showValues = values ? values.checked : true;

        if (selectedScale) {
            this.chartOptions.defaultScale = selectedScale.value;
            this.currentScale = selectedScale.value;
        }
        if (selectedView) {
            this.chartOptions.defaultView = selectedView.value;
        }
    },

    toggleSettingsRow() {
        const cogBtn = document.querySelector(this.cogBtnSelector);
        const settingsRow = document.getElementById("pp-stats-settings-row");
        if (!cogBtn || !settingsRow) return;

        const isOpen = cogBtn.classList.contains("pp-open");

        if (isOpen) {
            const previousView = this.currentView;
            this.saveChartOptions();
            cogBtn.classList.remove("pp-open");
            cogBtn.setAttribute("aria-expanded", "false");
            cogBtn.setAttribute("aria-label", "Open chart options");
            cogBtn.querySelector(".pp-cog-icon").style.display = "";
            cogBtn.querySelector(".pp-save-icon").style.display = "none";
            settingsRow.style.display = "none";

            const newView = this.chartOptions.defaultView;
            if (newView !== previousView) {
                this.currentView = newView;
                if (newView === "month" && this.canAccessFeature("month")) {
                    this.monthData ? this.renderChart(this.monthData) : this.fetchMonthData();
                    this.updateStatsCardAverage("month");
                } else {
                    this.currentView = "week";
                    if (this.weekData) this.renderChart(this.weekData);
                    this.updateStatsCardAverage("week");
                }
            } else {
                const data = this.currentView === "month" && this.monthData ? this.monthData : this.weekData;
                if (data) this.renderChart(data);
            }
        } else {
            cogBtn.classList.add("pp-open");
            cogBtn.setAttribute("aria-expanded", "true");
            cogBtn.setAttribute("aria-label", "Save chart options and close settings");
            cogBtn.querySelector(".pp-cog-icon").style.display = "none";
            cogBtn.querySelector(".pp-save-icon").style.display = "";
            settingsRow.style.display = "flex";
        }
    },

    // ==========================================
    //              PROGRAM SELECTOR
    // ==========================================

    initProgramSelector() {
        const trigger = document.querySelector(this.programSelectorTrigger);
        const dropdown = document.querySelector(this.programSelectorDropdown);
        if (!trigger || !dropdown || trigger.dataset.listenerAttached) return;

        trigger.dataset.listenerAttached = "1";

        trigger.addEventListener("click", (e) => {
            e.stopPropagation();
            const isExpanded = dropdown.classList.toggle("pp-open");
            trigger.classList.toggle("pp-open");
            trigger.setAttribute("aria-expanded", isExpanded ? "true" : "false");
        });

        document.addEventListener("click", (e) => {
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove("pp-open");
                trigger.classList.remove("pp-open");
                trigger.setAttribute("aria-expanded", "false");
            }
        });
    },

    switchProgram(keyword) {
        const config = this.getAjaxConfig();
        if (!config) return;

        PowerpressChart.showLoading(this.getContainer(), 'pp-stats-loading');

        const formData = new FormData();
        formData.append("action", "powerpress_switch_program");
        formData.append("nonce", config.nonce);
        formData.append("program_keyword", keyword);

        fetch(config.ajaxUrl, { method: "POST", body: formData })
            .then((r) => r.json())
            .then((result) => {
                PowerpressChart.hideLoading(this.getContainer(), 'pp-stats-loading');
                if (result.success && result.data) {
                    const container = this.getContainer();
                    if (container) container.dataset.programKeyword = keyword;

                    this.announceToScreenReader(`Now showing statistics for ${result.data.program_info?.title || keyword}`);

                    if (result.data.stats_widget_html) {
                        this.replaceHtml(result.data.stats_widget_html, config);
                    } else if (result.data.chart) {
                        this._clearCache();
                        this._setData(result.data.chart, "week");
                        this._applyTierRestrictions();
                        this._syncCogTier();
                        this.renderChart(this.weekData);
                    }
                }
            })
            .catch(() => {
                PowerpressChart.hideLoading(this.getContainer(), 'pp-stats-loading');
                PowerpressChart.showError("Failed to switch program", "pp-stats-toast");
                this.announceToScreenReader("Failed to switch program");
            });
    },

    // ==========================================
    //              REFRESH
    // ==========================================

    handleRefresh() {
        const now = Date.now();
        if (now - this.lastRefreshTime < this.refreshCooldown) {
            console.log(`StatsWidget: refresh cooldown`);
            return;
        }
        this.lastRefreshTime = now;
        this.refreshStats();
    },

    refreshStats() {
        const config = this.getAjaxConfig();
        const refreshBtn = document.getElementById("pp-stats-refresh-btn");
        if (!config) return;

        const viewBeforeRefresh = this.currentView;

        if (refreshBtn) {
            refreshBtn.classList.add("pp-refreshing");
            refreshBtn.disabled = true;
        }
        PowerpressChart.showLoading(this.getContainer(), 'pp-stats-loading');

        const formData = new FormData();
        formData.append("action", "powerpress_refresh_stats");
        formData.append("nonce", config.nonce);

        const container = this.getContainer();
        if (container?.dataset.stacked === "1") formData.append("stacked", "1");

        const programKeyword = config.programKeyword || document.querySelector("#pp-program-card")?.dataset.programKeyword;
        if (programKeyword) formData.append("program_keyword", programKeyword);

        fetch(config.ajaxUrl, { method: "POST", body: formData })
            .then((r) => r.json())
            .then((result) => {
                PowerpressChart.hideLoading(this.getContainer(), 'pp-stats-loading');

                if (result.success && result.data) {
                    this._clearCache();

                    if (result.data.stats_widget_html) {
                        this.replaceHtml(result.data.stats_widget_html, config);
                    } else if (result.data.chart) {
                        this._setData(result.data.chart, "week");
                        this._syncCogTier();
                    }

                    this._restoreViewAfterRefresh(viewBeforeRefresh);
                    this.announceToScreenReader("Statistics updated successfully");
                } else {
                    PowerpressChart.showError("Unable to refresh stats. Please try again.", "pp-stats-toast");
                    this.announceToScreenReader("Unable to refresh statistics");
                }

                this._applyCooldown();
            })
            .catch(() => {
                PowerpressChart.hideLoading(this.getContainer(), 'pp-stats-loading');
                PowerpressChart.showError("Unable to refresh stats. Please try again.", "pp-stats-toast");
                this._applyCooldown();
            });
    },

    _applyCooldown() {
        const btn = document.getElementById("pp-stats-refresh-btn");
        if (!btn) return;

        btn.classList.remove("pp-refreshing");

        const elapsed = Date.now() - this.lastRefreshTime;
        const remaining = this.refreshCooldown - elapsed;
        if (remaining <= 0) {
            btn.disabled = false;
            return;
        }

        const label = btn.querySelector("span:last-child");
        const originalText = label ? label.textContent : '';
        btn.disabled = true;
        if (label) label.textContent = "Please wait...";

        clearTimeout(this._cooldownTimer);
        this._cooldownTimer = setTimeout(() => {
            btn.disabled = false;
            if (label) label.textContent = originalText;
        }, remaining);
    },

    _restoreViewAfterRefresh(viewBeforeRefresh) {
        if (viewBeforeRefresh === "month" && this.canAccessFeature("month")) {
            this.currentView = "month";
            this.fetchMonthData();
        } else {
            this.currentView = "week";
            if (this.weekData) this.renderChart(this.weekData);
            this.updateStatsCardAverage("week");
        }
    },

    _syncCogTier() {
        const cogBtn = document.querySelector(this.cogBtnSelector);
        if (cogBtn) cogBtn.dataset.tier = this.statsTier;
    },

    // ==========================================
    //              VIEW SWITCHING
    // ==========================================

    fetchMonthData() {
        const config = this.getAjaxConfig();
        if (!config) return;

        PowerpressChart.showLoading(this.getContainer(), 'pp-stats-loading');

        const formData = new FormData();
        formData.append("action", "powerpress_stats_month");
        formData.append("nonce", config.nonce);

        const programKeyword = config.programKeyword || document.querySelector("#pp-program-card")?.dataset.programKeyword;
        if (programKeyword) formData.append("program_keyword", programKeyword);

        fetch(config.ajaxUrl, { method: "POST", body: formData })
            .then((r) => r.json())
            .then((result) => {
                PowerpressChart.hideLoading(this.getContainer(), 'pp-stats-loading');
                if (result.success && result.data?.chart) {
                    this._setData(result.data.chart, "month");
                    this.renderChart(this.monthData);
                    this.updateStatsCardAverage("month");
                } else {
                    this.resetToWeekView();
                    PowerpressChart.showError("Unable to load 30-day stats. Showing weekly view.", "pp-stats-toast");
                }
            })
            .catch(() => {
                PowerpressChart.hideLoading(this.getContainer(), 'pp-stats-loading');
                this.resetToWeekView();
                PowerpressChart.showError("Unable to load 30-day stats. Showing weekly view.", "pp-stats-toast");
            });
    },

    resetToWeekView() {
        this.currentView = "week";
        if (this.weekData) this.renderChart(this.weekData);
    },

    updateStatsCardAverage(view) {
        const label = document.getElementById("pp-avg-label");
        const valueCell = document.getElementById("pp-avg-value");
        if (!label || !valueCell) return;

        if (view === "month" && this.monthAverage !== null) {
            label.textContent = "30 Day Average";
            valueCell.childNodes[0].textContent = PowerpressChart.formatNumber(this.monthAverage);
        } else if (this.weekAverage !== null) {
            label.textContent = "7 Day Average";
            valueCell.childNodes[0].textContent = PowerpressChart.formatNumber(this.weekAverage);
        }
    },

    // ==========================================
    //              HTML REPLACEMENT
    // ==========================================

    replaceHtml(html, config = {}) {
        const container = this.getContainer();
        if (!container || !html) return;

        if (this.chartInstance) {
            PowerpressChart.destroy(this.chartInstance);
            this.chartInstance = null;
        }
        this._clearCache();

        const nonce = config.nonce || container.dataset.nonce;
        const ajaxUrl = config.ajaxUrl || container.dataset.ajaxUrl;

        const temp = document.createElement("div");
        temp.innerHTML = html;

        let chartData = null;
        const newWidget = temp.querySelector("#pp-stats-widget");
        if (newWidget?.dataset.chart) {
            try {
                chartData = JSON.parse(newWidget.dataset.chart);
            } catch (e) {
                console.error("StatsWidget: failed to parse chart data", e);
            }
        }

        container.outerHTML = temp.innerHTML;

        const newContainer = this.getContainer();
        if (newContainer) {
            newContainer.dataset.nonce = nonce;
            newContainer.dataset.ajaxUrl = ajaxUrl;
        }

        if (chartData) {
            this._setData(chartData, "week");
            this._applyTierRestrictions();

            if (this.currentView === "month" && this.canAccessFeature("month")) {
                this.fetchMonthData();
            } else {
                this.currentView = "week";
                this.renderChart(chartData);
                this.updateStatsCardAverage("week");
            }

            this.initSettingsModal();
            this.initProgramSelector();
        }
    },

    // ==========================================
    //              UPGRADE PROMPTS
    // ==========================================

    showUpgradePrompt(feature) {
        const hasAuth = this.hasAuth;
        const isBasicTier = this.statsTier === "basic";

        const messages = this._getUpgradeMessages(hasAuth, isBasicTier);
        const msg = messages[feature] || messages.month;
        const btnText = !hasAuth ? "Sign Up for Stats" : (isBasicTier ? "Upgrade Hosting" : "View Full Stats");
        const linkTarget = hasAuth ? "_blank" : "_self";

        let prompt = document.getElementById("pp-upgrade-prompt");
        if (!prompt) {
            prompt = this._createUpgradePrompt();
        }

        prompt.innerHTML = `
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px">${msg.tier} Feature</div>
            <div id="pp-upgrade-title" style="font-size:18px;font-weight:600;margin-bottom:12px;color:#1e1e1e">${msg.title}</div>
            <div style="font-size:13px;color:#6b7280;line-height:1.5;margin-bottom:20px">${msg.text}</div>
            <a href="${this.upgradeUrl}" target="${linkTarget}" id="pp-upgrade-action-btn"
                  style="display:inline-block;background:#2d5fea;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:500;font-size:13px">
                ${btnText}
            </a>
            <button id="pp-upgrade-dismiss-btn"
                            style="display:block;margin:12px auto 0;background:none;border:none;color:#6b7280;cursor:pointer;font-size:12px">
                Maybe Later
            </button>
        `;

        prompt.querySelector("#pp-upgrade-dismiss-btn")?.addEventListener("click", () => this.hideUpgradePrompt());

        this._previousFocus = document.activeElement;
        prompt.style.display = "block";
        document.getElementById("pp-upgrade-backdrop").style.display = "block";
        prompt.querySelector("#pp-upgrade-action-btn")?.focus();
    },

    _getUpgradeMessages(hasAuth, isBasicTier) {
        if (!hasAuth) {
            return {
                month: { title: "Free Podcast Statistics", text: "Sign up for Blubrry Stats to get 30-day charts, trend analysis, and IAB-certified download tracking.", tier: "Stats" },
                settings: { title: "Customizable Charts", text: "Sign up for Blubrry Stats to customize views and access extended date ranges.", tier: "Stats" }
            };
        }
        if (isBasicTier) {
            return {
                month: { title: "30-Day Stats", text: "Upgrade to Blubrry Hosting to unlock 30-day charts and detailed episode tracking.", tier: "Hosting" },
                settings: { title: "Customizable Charts", text: "Upgrade to Blubrry Hosting to customize views and enable trend analysis.", tier: "Hosting" }
            };
        }
        return {
            month: { title: "30-Day Stats", text: "View 30-day charts and detailed episode tracking in your stats dashboard.", tier: "Stats" },
            settings: { title: "Customizable Charts", text: "Access your full stats dashboard for customizable views and trend analysis.", tier: "Stats" }
        };
    },

    _createUpgradePrompt() {
        const prompt = document.createElement("div");
        prompt.id = "pp-upgrade-prompt";
        prompt.setAttribute("role", "dialog");
        prompt.setAttribute("aria-modal", "true");
        prompt.setAttribute("aria-labelledby", "pp-upgrade-title");
        prompt.style.cssText = `
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: #f8f9fa; color: #1e1e1e; padding: 28px 32px; border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.15); z-index: 10001; width: 90%; max-width: 380px;
            text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `.replace(/\s+/g, ' ');
        document.body.appendChild(prompt);

        const backdrop = document.createElement("div");
        backdrop.id = "pp-upgrade-backdrop";
        backdrop.style.cssText = "position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.35);z-index:10000;";
        backdrop.addEventListener("click", () => this.hideUpgradePrompt());
        document.body.appendChild(backdrop);

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && prompt.style.display !== "none") this.hideUpgradePrompt();
        });

        return prompt;
    },

    hideUpgradePrompt() {
        const prompt = document.getElementById("pp-upgrade-prompt");
        const backdrop = document.getElementById("pp-upgrade-backdrop");
        if (prompt) prompt.style.display = "none";
        if (backdrop) backdrop.style.display = "none";

        if (this._previousFocus?.focus) {
            this._previousFocus.focus();
            this._previousFocus = null;
        }
    },

    // ==========================================
    //              ACCESSIBILITY
    // ==========================================

    announceToScreenReader(message) {
        const liveRegion = document.getElementById("pp-stats-live-region");
        if (!liveRegion) return;
        liveRegion.textContent = "";
        setTimeout(() => { liveRegion.textContent = message; }, 100);
    },

    updateChartDescription(data) {
        const descEl = document.getElementById("pp-stats-chart-description");
        if (!descEl || !data?.days) return;

        const values = data.days.map(d => d.total);
        const n = values.length;
        if (n === 0) return;

        const todayTotal = values[n - 1] || 0;
        const avg = Math.round(values.reduce((a, b) => a + b, 0) / n);
        const velocity = PowerpressChart.calculateVelocity(values);
        const velocityPct = avg > 0 ? Math.round((velocity.slope / avg) * 100 * n) : 0;

        let sentiment = "Your podcast is holding steady.";
        if (velocityPct > 5) sentiment = "Your podcast is growing - keep posting!";
        else if (velocityPct < -5) sentiment = "Downloads are slowing down. Time for fresh content!";

        const parts = [];
        const periodLabel = n > 7 ? "30-day" : "7-day";

        parts.push(todayTotal === 0 ? "No downloads today" : `You had ${PowerpressChart.formatNumber(todayTotal)} downloads today`);
        parts.push(`with a ${periodLabel} daily average of ${PowerpressChart.formatNumber(avg)}.`);

        if (this.statsTier !== "basic" && velocityPct !== 0) {
            parts.push(`Trend is ${velocityPct > 0 ? "up" : "down"} ${Math.abs(velocityPct)} percent.`);
        }
        parts.push(sentiment);

        descEl.textContent = parts.join(" ");
    },

    // ==========================================
    //     WRAPPER METHODS FOR PROGRAMCARDMANAGER
    // ==========================================

    showLoading() {
        PowerpressChart.showLoading(this.getContainer(), 'pp-stats-loading');
    },

    hideLoading() {
        PowerpressChart.hideLoading(this.getContainer(), 'pp-stats-loading');
    },

    hideSpinner() {
        PowerpressChart.hideLoading(this.getContainer(), 'pp-stats-loading');
    },

    showError(message) {
        PowerpressChart.showError(message, 'pp-stats-toast');
    },
};

export { StatsWidget };
