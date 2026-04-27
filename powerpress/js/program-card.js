/**
 * Program Card Entry Point
 * standalone bundle for program card functionality
 *
 * loads: PowerpressChart, StatsWidget, ShowInfoCard, ProgramCardManager
 */

import { PowerpressChart } from './modules/program-card/PowerpressChart.js';
import { StatsWidget } from './modules/program-card/StatsWidget.js';
import { ShowInfoCard } from './modules/program-card/ShowInfoCard.js';
import { ProgramCardManager, initProgramCardManager } from './modules/program-card/ProgramCardManager.js';

// expose to global scope for inline scripts and debugging
window.PowerpressChart = PowerpressChart;
window.StatsWidget = StatsWidget;
window.ShowInfoCard = ShowInfoCard;
window.ProgramCardManager = ProgramCardManager;

// auto-init when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initProgramCardManager();
});
