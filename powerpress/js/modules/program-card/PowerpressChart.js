/**
 * PowerpressChart
 * unified chart rendering engine for PowerPress
 *
 * handles all Chart.js rendering, plugins, tooltips, and styling
 * pass data and options, this class handles the rest
 */

// ============================================
//         POWERPRESS CHART ENGINE
// ============================================

const PowerpressChart = {

    // ==========================================
    //              COLORS
    // ==========================================

    COLORS: {
        barGradientTop: "#C26BB5",
        barGradientBottom: "#86357A",
        todayHighlight: "#B347A3",
        gridLine: "#E0E0E0",
        labelColor: "#444444",
        titleColor: "#252733",
        trendLine: "#0B43A4",
        growthRed: "#EF5350",
        growthOrange: "#FFA726",
        growthYellow: "#FFEE58",
        growthGreen: "#66BB6A",
        growthBlue: "#42A5F5",
        growthViolet: "#AB47BC",
    },

    VELOCITY_COLORS: {
        growing: '#2E7D32',
        stagnant: '#F57C00',
        shrinking: '#C62828'
    },

    // ==========================================
    //              FORMATTERS
    // ==========================================

    /**
     * format number with locale-aware thousands separators
     * @param {number} num
     * @returns {string}
     */
    formatNumber(num) {
        return Number(num).toLocaleString();
    },

    /**
     * format number compactly (1500 -> 1.5K, 1500000 -> 1.5M)
     * @param {number} num
     * @returns {string}
     */
    formatCompact(num) {
        if (num >= 1000000) {
            const val = num / 1000000;
            return (val % 1 === 0 ? val : val.toFixed(1)) + 'M';
        }
        if (num >= 1000) {
            const val = num / 1000;
            return (val % 1 === 0 ? val : val.toFixed(1)) + 'K';
        }
        return num.toString();
    },

    /**
     * get color for growth percentage display
     * @param {number} pct - growth percentage
     * @returns {string} hex color
     */
    getGrowthColor(pct) {
        if (pct <= -200) return this.COLORS.growthRed;
        if (pct <= -100) return this.COLORS.growthOrange;
        if (pct < 0) return this.COLORS.growthYellow;
        if (pct <= 100) return this.COLORS.growthGreen;
        if (pct < 200) return this.COLORS.growthBlue;
        return this.COLORS.growthViolet;
    },

    // ==========================================
    //              UI HELPERS
    // ==========================================

    /**
     * add loading state to container
     * @param {HTMLElement|null} container
     * @param {string} [className='pp-loading']
     */
    showLoading(container, className = 'pp-loading') {
        if (!container) return;
        container.classList.add(className);
    },

    /**
     * remove loading state from container
     * @param {HTMLElement|null} container
     * @param {string} [className='pp-loading']
     */
    hideLoading(container, className = 'pp-loading') {
        if (!container) return;
        container.classList.remove(className);
    },

    /**
     * show toast error message
     * @param {string} message
     * @param {string} [toastId='pp-toast']
     */
    showError(message, toastId = 'pp-toast') {
        const existing = document.getElementById(toastId);
        if (existing) existing.remove();

        const toast = document.createElement("div");
        toast.id = toastId;
        toast.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #EF5350;
            color: #fff;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        `.replace(/\s+/g, ' ');
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = "1";
            toast.style.transform = "translateY(0)";
        });

        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(10px)";
            setTimeout(() => toast.remove(), 200);
        }, 4000);
    },

    // ==========================================
    //              CHART HELPERS
    // ==========================================

    /**
     * create vertical gradient for bar charts
     * @param {CanvasRenderingContext2D|null} ctx
     * @param {string} colorTop
     * @param {string} colorBottom
     * @returns {CanvasGradient|string}
     */
    createGradient(ctx, colorTop, colorBottom) {
        if (!ctx || !ctx.createLinearGradient) {
            return colorBottom;
        }
        const height = ctx.canvas.height;
        const gradient = ctx.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, colorTop);
        gradient.addColorStop(1, colorBottom);
        return gradient;
    },

    /**
     * calculate linear regression for velocity/trend
     * @param {number[]} values
     * @returns {{slope: number, intercept: number, start: number, end: number}}
     */
    calculateVelocity(values) {
        const n = values.length;
        if (n < 2) {
            return { slope: 0, intercept: 0, start: 0, end: 0 };
        }

        let sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
        for (let i = 0; i < n; i++) {
            sumX += i;
            sumY += values[i];
            sumXY += i * values[i];
            sumX2 += i * i;
        }

        const denominator = n * sumX2 - sumX * sumX;
        if (denominator === 0) {
            return { slope: 0, intercept: 0, start: 0, end: 0 };
        }

        const slope = (n * sumXY - sumX * sumY) / denominator;
        const intercept = (sumY - slope * sumX) / n;

        return {
            slope,
            intercept,
            start: intercept,
            end: slope * (n - 1) + intercept
        };
    },

    /**
     * format date for chart display
     * @param {string} dateStr - format "Mon DD"
     * @param {boolean} short - use short weekday
     * @returns {string}
     */
    formatDate(dateStr, short = false) {
        const parts = dateStr.split(' ');
        if (parts.length !== 2) return dateStr;

        const monthStr = parts[0];
        const dayNum = parseInt(parts[1], 10);
        const monthMap = {
            'Jan': 1, 'Feb': 2, 'Mar': 3, 'Apr': 4, 'May': 5, 'Jun': 6,
            'Jul': 7, 'Aug': 8, 'Sep': 9, 'Oct': 10, 'Nov': 11, 'Dec': 12
        };
        const monthNum = monthMap[monthStr] || 1;
        const year = new Date().getFullYear();
        const date = new Date(year, monthNum - 1, dayNum);

        const weekday = date.toLocaleDateString('en-US', { weekday: short ? 'short' : 'long' });
        return `${weekday} ${monthNum}/${dayNum.toString().padStart(2, '0')}`;
    },

    // ==========================================
    //              MAIN RENDER METHOD
    // ==========================================

    /**
     * render a bar chart with the PowerPress style
     *
     * @param {HTMLCanvasElement} canvas - canvas element to render to
     * @param {Object} data - chart data with days array
     * @param {Object} options - rendering options
     * @param {string} [options.tier='standard'] - stats tier (basic, standard, advanced)
     * @param {string} [options.scale='linear'] - y-axis scale (linear, log)
     * @param {boolean} [options.showTrendline=true] - show velocity trend line
     * @param {boolean} [options.showAvgLine=true] - show average line
     * @param {boolean} [options.showValues=true] - show values above bars
     * @param {boolean} [options.isNarrowScreen=false] - mobile/narrow layout
     * @returns {Chart|null} - Chart.js instance or null on error
     */
    render(canvas, data, options = {}) {
        // 1) VALIDATE INPUTS
        if (!canvas) {
            console.log("PowerpressChart: canvas not provided");
            return null;
        }
        if (typeof Chart === "undefined") {
            console.error("PowerpressChart: Chart.js not loaded");
            return null;
        }
        if (!data || !data.days || !data.days.length) {
            console.log("PowerpressChart: no chart data available");
            return null;
        }

        // 2) MERGE OPTIONS WITH DEFAULTS
        const config = {
            tier: options.tier || 'standard',
            scale: options.scale || 'linear',
            showTrendline: options.showTrendline !== false,
            showAvgLine: options.showAvgLine !== false,
            showValues: options.showValues !== false,
            isNarrowScreen: options.isNarrowScreen || false,
            isDashboardWidget: !!canvas.closest('#powerpress_dashboard_stats')
        };

        const isNarrowScreen = config.isNarrowScreen || config.isDashboardWidget || window.innerWidth <= 782;

        const ctx = canvas.getContext("2d");
        const COLORS = this.COLORS;

        // 3) CREATE BAR GRADIENT
        const gradient = this.createGradient(ctx, COLORS.barGradientTop, COLORS.barGradientBottom);

        // 4) PREPARE DATA ARRAYS
        const labels = data.days.map((day) => day.date);
        const values = data.days.map((day) => day.total);
        const todayIndex = data.days.length - 1;
        const numDays = data.days.length;
        const isMonthView = numDays > 10;

        // 5) CALCULATE BAR SIZING
        let barThickness, maxBarThickness, borderRadius;
        if (isNarrowScreen) {
            barThickness = isMonthView ? 5 : 12;
            maxBarThickness = isMonthView ? 8 : 16;
            borderRadius = isMonthView ? 3 : 6;
        } else {
            barThickness = isMonthView ? 8 : 20;
            maxBarThickness = isMonthView ? 12 : 25;
            borderRadius = isMonthView ? 4 : 10;
        }

        // 6) CALCULATE STATISTICS
        const average = data.display_average !== undefined
            ? data.display_average
            : values.reduce((sum, val) => sum + val, 0) / values.length;

        const maxValue = Math.max(...values);
        const nonZeroValues = values.filter(v => v > 0);
        const minNonZero = nonZeroValues.length > 0 ? Math.min(...nonZeroValues) : 1;

        // 7) CALCULATE SCALE BOUNDS
        const scaleMin = Math.pow(10, Math.floor(Math.log10(minNonZero)));
        const magnitude = Math.pow(10, Math.floor(Math.log10(maxValue)));
        const normalized = maxValue / magnitude;
        const niceMultiplier = normalized <= 1.2 ? 1.5 : normalized <= 2 ? 2.5 : normalized <= 4 ? 5 : 10;
        const scaleMax = magnitude * niceMultiplier;

        // 8) CALCULATE VELOCITY
        const velocity = this.calculateVelocity(values);

        // 9) PREPARE BAR COLORS
        const backgroundColors = data.days.map((_, index) =>
            index === todayIndex ? COLORS.todayHighlight : gradient
        );

        // 10) BUILD PLUGINS
        const plugins = this._buildPlugins({
            data, labels, values, average, velocity, isMonthView, isNarrowScreen, config, COLORS
        });

        // 11) BUILD CHART OPTIONS
        const chartOptions = this._buildChartOptions({
            data, labels, values, average, isMonthView, isNarrowScreen, config, scaleMin, scaleMax, COLORS
        });

        // 12) CREATE CHART INSTANCE
        return new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    type: "bar",
                    data: values,
                    backgroundColor: backgroundColors,
                    borderRadius: borderRadius,
                    borderSkipped: 'bottom',
                    barThickness: barThickness,
                    maxBarThickness: maxBarThickness,
                }],
            },
            plugins: plugins,
            options: chartOptions,
        });
    },

    // ==========================================
    //              PLUGIN BUILDERS
    // ==========================================

    /**
     * build Chart.js plugins array
     * @private
     */
    _buildPlugins({ data, labels, values, average, velocity, isMonthView, isNarrowScreen, config, COLORS }) {
        const self = this;
        const n = values.length;

        return [
            // PLUGIN: CHART SUBTITLE
            {
                id: "chartSubtitle",
                beforeDraw: (chart) => {
                    const ctx = chart.ctx;
                    const chartArea = chart.chartArea;

                    ctx.save();

                    const startX = chartArea.left;
                    const startY = 4;
                    const lineHeight = 14;

                    const startDate = self.formatDate(labels[0], isNarrowScreen);
                    const endDate = self.formatDate(labels[labels.length - 1], isNarrowScreen);
                    const dateRangeText = `${startDate} - ${endDate}`;
                    const avgText = isNarrowScreen
                        ? `Daily Avg: ${self.formatNumber(Math.round(average))}`
                        : `Daily Average: ${self.formatNumber(Math.round(average))}`;

                    // velocity calculation
                    const showVelocity = config.tier !== 'basic';
                    let velocityText = '';
                    let velocityColor = self.VELOCITY_COLORS.stagnant;

                    if (showVelocity) {
                        const velocityPct = average > 0
                            ? Math.round((velocity.slope / average) * 100 * n)
                            : 0;
                        const arrow = velocityPct > 0 ? '\u25B2' : velocityPct < 0 ? '\u25BC' : '\u25CF';
                        velocityText = `${arrow} ${velocityPct > 0 ? '+' : ''}${velocityPct}%`;

                        if (velocityPct > 5) {
                            velocityColor = self.VELOCITY_COLORS.growing;
                        } else if (velocityPct < -5) {
                            velocityColor = self.VELOCITY_COLORS.shrinking;
                        }
                    }

                    // line 1: "Downloads" title
                    ctx.font = "600 12px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
                    ctx.fillStyle = "#252733";
                    ctx.textAlign = "left";
                    ctx.textBaseline = "top";
                    ctx.fillText("Downloads", startX, startY);

                    // line 2: date range
                    ctx.font = "400 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
                    ctx.fillStyle = "#666666";
                    ctx.fillText(dateRangeText, startX, startY + lineHeight);

                    // right side
                    ctx.textAlign = "right";
                    if (showVelocity) {
                        ctx.font = "600 11px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
                        ctx.fillStyle = velocityColor;
                        ctx.fillText(velocityText, chartArea.right, startY);
                        ctx.fillStyle = "#666666";
                        ctx.fillText(avgText, chartArea.right, startY + lineHeight);
                    } else {
                        ctx.font = "600 12px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
                        ctx.fillStyle = "#666666";
                        ctx.fillText(avgText, chartArea.right, isNarrowScreen ? startY + lineHeight : startY);
                    }

                    ctx.restore();
                },
            },

            // PLUGIN: AVERAGE LINE
            {
                id: "averageLine",
                beforeDatasetsDraw: (chart) => {
                    if (!config.showAvgLine) return;

                    const ctx = chart.ctx;
                    const yScale = chart.scales.y;
                    const chartArea = chart.chartArea;
                    const yPos = yScale.getPixelForValue(average);

                    ctx.save();
                    ctx.beginPath();
                    ctx.setLineDash([6, 4]);
                    ctx.strokeStyle = COLORS.trendLine;
                    ctx.lineWidth = 1.5;
                    ctx.globalAlpha = 0.6;
                    ctx.moveTo(chartArea.left, yPos);
                    ctx.lineTo(chartArea.right, yPos);
                    ctx.stroke();
                    ctx.restore();
                },
            },

            // PLUGIN: VELOCITY LINE
            {
                id: "velocityLine",
                beforeDatasetsDraw: (chart) => {
                    if (config.tier === 'basic') return;
                    if (!config.showTrendline) return;

                    const ctx = chart.ctx;
                    const yScale = chart.scales.y;
                    const chartArea = chart.chartArea;

                    ctx.save();
                    ctx.beginPath();
                    ctx.setLineDash([]);

                    if (velocity.slope < -0.5) {
                        ctx.strokeStyle = COLORS.growthOrange;
                    } else if (velocity.slope < 0) {
                        ctx.strokeStyle = COLORS.growthYellow;
                    } else if (velocity.slope > 10) {
                        ctx.strokeStyle = COLORS.trendLine;
                    } else {
                        ctx.strokeStyle = COLORS.growthGreen;
                    }
                    ctx.lineWidth = 2;
                    ctx.globalAlpha = 0.7;

                    const yMin = yScale.min;
                    const yMax = yScale.max;
                    const clampedStart = Math.max(yMin, Math.min(yMax, velocity.start));
                    const clampedEnd = Math.max(yMin, Math.min(yMax, velocity.end));
                    ctx.moveTo(chartArea.left, yScale.getPixelForValue(clampedStart));
                    ctx.lineTo(chartArea.right, yScale.getPixelForValue(clampedEnd));
                    ctx.stroke();
                    ctx.restore();
                },
            },

            // PLUGIN: BAR VALUE LABELS
            {
                id: "barValueLabels",
                afterDatasetsDraw: (chart) => {
                    if (!config.showValues) return;

                    const ctx = chart.ctx;
                    const dataset = chart.data.datasets[0];
                    const meta = chart.getDatasetMeta(0);
                    const chartValues = dataset.data;

                    // month view: only show high, low, and center of zero runs
                    let indicesToShow = null;
                    if (isMonthView) {
                        indicesToShow = new Set();
                        const maxVal = Math.max(...chartValues);
                        indicesToShow.add(chartValues.indexOf(maxVal));

                        const nonZero = chartValues.filter(v => v > 0);
                        if (nonZero.length > 0) {
                            indicesToShow.add(chartValues.indexOf(Math.min(...nonZero)));
                        }

                        let zeroRunStart = -1;
                        for (let i = 0; i <= chartValues.length; i++) {
                            const isZero = i < chartValues.length && chartValues[i] === 0;
                            if (isZero && zeroRunStart === -1) {
                                zeroRunStart = i;
                            } else if (!isZero && zeroRunStart !== -1) {
                                const centerIndex = Math.floor((zeroRunStart + i - 1) / 2);
                                indicesToShow.add(centerIndex);
                                zeroRunStart = -1;
                            }
                        }
                    }

                    ctx.save();
                    ctx.textAlign = "center";
                    ctx.textBaseline = "bottom";
                    ctx.font = "bold 10px sans-serif";

                    meta.data.forEach((bar, index) => {
                        if (indicesToShow && !indicesToShow.has(index)) return;

                        const value = dataset.data[index];
                        const text = (isNarrowScreen || isMonthView)
                            ? self.formatCompact(value)
                            : self.formatNumber(value);
                        const isToday = index === data.days.length - 1;

                        // value background
                        const textWidth = ctx.measureText(text).width;
                        const padding = 2;
                        const bgWidth = textWidth + padding * 2;
                        const bgHeight = 12;
                        const cornerRadius = 2;

                        const bgX = bar.x - bgWidth / 2;
                        const minY = 34;
                        const idealY = bar.y - 4 - bgHeight;
                        const bgY = Math.max(minY, idealY);

                        ctx.fillStyle = "rgba(255, 255, 255, 0.4)";
                        ctx.beginPath();
                        ctx.moveTo(bgX + cornerRadius, bgY);
                        ctx.lineTo(bgX + bgWidth - cornerRadius, bgY);
                        ctx.arcTo(bgX + bgWidth, bgY, bgX + bgWidth, bgY + cornerRadius, cornerRadius);
                        ctx.lineTo(bgX + bgWidth, bgY + bgHeight - cornerRadius);
                        ctx.arcTo(bgX + bgWidth, bgY + bgHeight, bgX + bgWidth - cornerRadius, bgY + bgHeight, cornerRadius);
                        ctx.lineTo(bgX + cornerRadius, bgY + bgHeight);
                        ctx.arcTo(bgX, bgY + bgHeight, bgX, bgY + bgHeight - cornerRadius, cornerRadius);
                        ctx.lineTo(bgX, bgY + cornerRadius);
                        ctx.arcTo(bgX, bgY, bgX + cornerRadius, bgY, cornerRadius);
                        ctx.closePath();
                        ctx.fill();

                        ctx.fillStyle = isToday ? COLORS.todayHighlight : COLORS.labelColor;
                        ctx.fillText(text, bar.x, bgY + bgHeight);
                    });

                    ctx.restore();
                },
            },
        ];
    },

    /**
     * build Chart.js options object
     * @private
     */
    _buildChartOptions({ data, labels, values, average, isMonthView, isNarrowScreen, config, scaleMin, scaleMax, COLORS }) {
        const self = this;

        return {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { top: 28 },
            },
            plugins: {
                legend: { display: false },
                title: { display: false },
                tooltip: {
                    enabled: false,
                    external: (context) => self._handleTooltip(context, { data, average, isMonthView }),
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: COLORS.labelColor,
                        font: { size: isMonthView ? 9 : 11, weight: "bold" },
                        maxRotation: isNarrowScreen ? 30 : 45,
                        minRotation: isNarrowScreen ? 30 : 45,
                        autoSkip: false,
                        callback: function (value, index) {
                            const label = this.getLabelForValue(value);
                            const total = labels.length;

                            if (!isMonthView) return label;

                            const targetLabels = isNarrowScreen ? 5 : 7;
                            const interval = Math.max(1, Math.floor((total - 1) / (targetLabels - 1)));

                            if (index === 0 || index === total - 1) return label;
                            if (index === total - 2) return "";
                            if (index % interval === 0) return label;
                            return "";
                        },
                    },
                },
                y: {
                    type: config.scale === "log" ? "logarithmic" : "linear",
                    min: config.scale === "log" ? scaleMin : data.scale_min,
                    max: config.scale === "log" ? scaleMax : data.scale_max,
                    grid: {
                        display: true,
                        color: COLORS.gridLine,
                        drawBorder: true,
                        drawOnChartArea: true,
                        lineWidth: 1,
                    },
                    ticks: {
                        color: COLORS.labelColor,
                        font: { size: isNarrowScreen ? 10 : 11 },
                        stepSize: config.scale === "log" ? undefined : data.scale_step,
                        callback: (value) => self.formatCompact(value),
                    },
                },
            },
        };
    },

    /**
     * handle custom tooltip rendering
     * @private
     */
    _handleTooltip(context, { data, average, isMonthView }) {
        const { chart, tooltip } = context;
        let tooltipEl = document.getElementById("pp-stats-tooltip");

        if (!tooltipEl) {
            tooltipEl = document.createElement("div");
            tooltipEl.id = "pp-stats-tooltip";
            tooltipEl.style.cssText = `
                position: absolute;
                background: linear-gradient(135deg, #1e2130 0%, #252a3d 100%);
                color: #fff;
                padding: 14px 18px;
                border-radius: 8px;
                font-size: 14px;
                pointer-events: none;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.2s ease, transform 0.2s ease;
                box-shadow: 0 4px 16px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.05);
                min-width: 170px;
                transform: translateY(4px);
            `.replace(/\s+/g, ' ');
            document.body.appendChild(tooltipEl);

            const arrow = document.createElement("div");
            arrow.id = "pp-stats-tooltip-arrow";
            arrow.style.cssText = `
                position: absolute;
                bottom: -6px;
                left: 50%;
                transform: translateX(-50%);
                width: 0;
                height: 0;
                border-left: 7px solid transparent;
                border-right: 7px solid transparent;
                border-top: 7px solid #252a3d;
            `.replace(/\s+/g, ' ');
            tooltipEl.appendChild(arrow);
        }

        if (tooltip.opacity === 0) {
            tooltipEl.style.opacity = "0";
            tooltipEl.style.transform = "translateY(4px)";
            return;
        }

        const dataPoint = tooltip.dataPoints?.[0];
        if (!dataPoint) return;

        const day = data.days[dataPoint.dataIndex];
        const value = dataPoint.raw;
        const upArrow = "\u25B2";
        const downArrow = "\u25BC";

        let html = "";

        if (day.full_date) {
            html += `<div style="color:#8b8fa3;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">${day.full_date}</div>`;
        }
        html += `<div style="color:#fff;font-weight:600;font-size:18px;margin-bottom:8px">${this.formatNumber(value)} <span style="font-weight:400;font-size:13px;color:#8b8fa3">downloads</span></div>`;

        // vs average
        const diffFromAvg = value - average;
        const pctFromAvg = average > 0 ? Math.round((diffFromAvg / average) * 100) : 0;
        const avgColor = this.getGrowthColor(pctFromAvg);
        const avgIcon = pctFromAvg > 0 ? upArrow : pctFromAvg < 0 ? downArrow : "\u25CF";

        if (pctFromAvg !== 0) {
            const direction = pctFromAvg > 0 ? "above" : "below";
            html += `<div style="color:${avgColor};display:flex;align-items:center;gap:5px;margin-bottom:4px;font-size:13px"><span style="font-size:9px">${avgIcon}</span> ${this.formatNumber(Math.abs(pctFromAvg))}% ${direction} avg</div>`;
        } else {
            html += `<div style="color:${avgColor};display:flex;align-items:center;gap:5px;margin-bottom:4px;font-size:13px"><span style="font-size:9px">${avgIcon}</span> At average</div>`;
        }

        // vs previous period
        if (isMonthView && day.last_month_total !== undefined && day.last_month_total !== null) {
            const diff = value - day.last_month_total;
            const pct = day.last_month_total > 0 ? Math.round((diff / day.last_month_total) * 100) : 0;
            const sign = diff >= 0 ? "+" : "";
            const monthColor = this.getGrowthColor(pct);
            const monthIcon = pct > 0 ? upArrow : pct < 0 ? downArrow : "\u25CF";
            html += `<div style="color:${monthColor};display:flex;align-items:center;gap:5px;font-size:13px"><span style="font-size:9px">${monthIcon}</span> ${sign}${this.formatNumber(Math.abs(pct))}% vs last month</div>`;
        } else if (!isMonthView && day.last_week_total !== undefined && day.last_week_total !== null) {
            const diff = value - day.last_week_total;
            const pct = day.last_week_total > 0 ? Math.round((diff / day.last_week_total) * 100) : 0;
            const sign = diff >= 0 ? "+" : "";
            const weekColor = this.getGrowthColor(pct);
            const weekIcon = pct > 0 ? upArrow : pct < 0 ? downArrow : "\u25CF";
            html += `<div style="color:${weekColor};display:flex;align-items:center;gap:5px;font-size:13px"><span style="font-size:9px">${weekIcon}</span> ${sign}${this.formatNumber(Math.abs(pct))}% vs last week</div>`;
        }

        html += `<div id="pp-stats-tooltip-arrow" style="position:absolute;bottom:-6px;left:50%;transform:translateX(-50%);width:0;height:0;border-left:7px solid transparent;border-right:7px solid transparent;border-top:7px solid #252a3d;"></div>`;
        tooltipEl.innerHTML = html;

        const pos = chart.canvas.getBoundingClientRect();
        const tooltipWidth = tooltipEl.offsetWidth || 140;
        const xPos = pos.left + window.scrollX + tooltip.caretX - (tooltipWidth / 2);
        const yPos = pos.top + window.scrollY + tooltip.caretY - tooltipEl.offsetHeight - 12;
        tooltipEl.style.left = xPos + "px";
        tooltipEl.style.top = yPos + "px";
        tooltipEl.style.opacity = "1";
        tooltipEl.style.transform = "translateY(0)";
    },

    // ==========================================
    //              CLEANUP
    // ==========================================

    /**
     * destroy a chart instance and clean up
     * @param {Chart|null} chartInstance
     */
    destroy(chartInstance) {
        if (chartInstance) {
            chartInstance.destroy();
        }
        // clean up tooltip if present
        const tooltip = document.getElementById("pp-stats-tooltip");
        if (tooltip) tooltip.remove();
    },
};

export { PowerpressChart };
