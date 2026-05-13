// This file is part of Moodle - http://moodle.org/

/**
 * Dashboard interactions for report_uckk.
 *
 * @module     report_uckk/report
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    /**
     * Mark the active report card.
     *
     * @param {HTMLElement} dashboard Dashboard root node.
     * @return {void}
     */
    const markActiveCard = function(dashboard) {
        const activeCard = dashboard.querySelector('.report-uckk-card.border-primary');

        if (!activeCard) {
            return;
        }

        activeCard.setAttribute('aria-current', 'true');
    };

    /**
     * Add accessible table helpers.
     *
     * @param {HTMLElement} dashboard Dashboard root node.
     * @return {void}
     */
    const enhanceTable = function(dashboard) {
        const table = dashboard.querySelector('.report-uckk-table');

        if (!table) {
            return;
        }

        table.setAttribute('data-enhanced', 'true');

        const headers = Array.from(table.querySelectorAll('thead th')).map(function(header) {
            return header.textContent.trim();
        });

        table.querySelectorAll('tbody tr').forEach(function(row) {
            row.querySelectorAll('td').forEach(function(cell, index) {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    };

    /**
     * Track report card clicks for UI state only.
     *
     * @param {HTMLElement} dashboard Dashboard root node.
     * @return {void}
     */
    const bindReportCards = function(dashboard) {
        dashboard.querySelectorAll('.report-uckk-card a').forEach(function(link) {
            link.addEventListener('click', function() {
                dashboard.setAttribute('data-last-action', 'report-selected');
            });
        });
    };

    /**
     * Track export link clicks for UI state only.
     *
     * Export permission checks and audit logging are handled server-side.
     *
     * @param {HTMLElement} dashboard Dashboard root node.
     * @return {void}
     */
    const bindExportLinks = function(dashboard) {
        dashboard.querySelectorAll('a[href*="format=csv"], a[href*="format=json"]').forEach(function(link) {
            link.addEventListener('click', function() {
                dashboard.setAttribute('data-last-action', 'report-export-requested');
            });
        });
    };

    return {
        /**
         * Initialize UCKK report dashboard behaviour.
         *
         * @return {void}
         */
        init: function() {
            const dashboard = document.querySelector('[data-region="report-uckk-dashboard"]');

            if (!dashboard) {
                return;
            }

            dashboard.setAttribute('data-js-ready', 'true');

            markActiveCard(dashboard);
            enhanceTable(dashboard);
            bindReportCards(dashboard);
            bindExportLinks(dashboard);
        }
    };
});