/**
 * Dashboard interactions for local_uckk.
 *
 * This module is intentionally UI-only. It may request already-authorised
 * dashboard data from Moodle external services, but it must not decide access,
 * validate evidence, calculate official progress, award badges, or change
 * canonical UCKK records.
 *
 * @module     local_uckk/dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/ajax',
    'core/notification',
    'core/templates',
    'core/str'
], function(
    Ajax,
    Notification,
    Templates,
    Str
) {
    'use strict';

    const SELECTORS = {
        ROOT: '[data-region="local-uckk-dashboard"]',
        CONTENT: '[data-region="uckk-dashboard-content"]',
        LOADING: '[data-region="uckk-dashboard-loading"]',
        ERROR: '[data-region="uckk-dashboard-error"]',
        REFRESH: '[data-action="uckk-dashboard-refresh"]',
        SECTION_TOGGLE: '[data-action="uckk-dashboard-toggle-section"]',
        SECTION: '[data-region="uckk-dashboard-section"]'
    };

    const CLASSES = {
        LOADING: 'is-loading',
        COLLAPSED: 'is-collapsed',
        HAS_ERROR: 'has-error'
    };

    /**
     * Normalise configuration passed by PHP.
     *
     * @param {Object} config Raw configuration.
     * @returns {Object}
     */
    const normaliseConfig = config => {
        config = config || {};

        return {
            rootId: config.rootId || '',
            userId: parseInt(config.userId || config.userid || 0, 10),
            contextId: parseInt(config.contextId || config.contextid || 0, 10),
            refreshEnabled: Boolean(config.refreshEnabled || config.refreshenabled || false),
            refreshRate: parseInt(config.refreshRate || config.refreshrate || 0, 10),
            template: config.template || 'local_uckk/dashboard'
        };
    };

    /**
     * Find the dashboard root element.
     *
     * @param {Object} config Normalised config.
     * @returns {HTMLElement|null}
     */
    const getRoot = config => {
        if (config.rootId) {
            return document.getElementById(config.rootId);
        }

        return document.querySelector(SELECTORS.ROOT);
    };

    /**
     * Find a child element within a dashboard root.
     *
     * @param {HTMLElement} root Dashboard root.
     * @param {String} selector Selector.
     * @returns {HTMLElement|null}
     */
    const find = (root, selector) => root ? root.querySelector(selector) : null;

    /**
     * Set loading state.
     *
     * @param {HTMLElement} root Dashboard root.
     * @param {Boolean} loading Whether loading is active.
     */
    const setLoading = (root, loading) => {
        if (!root) {
            return;
        }

        root.classList.toggle(CLASSES.LOADING, loading);

        const loadingRegion = find(root, SELECTORS.LOADING);
        if (loadingRegion) {
            loadingRegion.hidden = !loading;
            loadingRegion.setAttribute('aria-hidden', loading ? 'false' : 'true');
        }

        const refreshButton = find(root, SELECTORS.REFRESH);
        if (refreshButton) {
            refreshButton.disabled = loading;
            refreshButton.setAttribute('aria-busy', loading ? 'true' : 'false');
        }
    };

    /**
     * Clear dashboard error state.
     *
     * @param {HTMLElement} root Dashboard root.
     */
    const clearError = root => {
        if (!root) {
            return;
        }

        root.classList.remove(CLASSES.HAS_ERROR);

        const errorRegion = find(root, SELECTORS.ERROR);
        if (errorRegion) {
            errorRegion.hidden = true;
            errorRegion.textContent = '';
        }
    };

    /**
     * Show dashboard error state.
     *
     * @param {HTMLElement} root Dashboard root.
     * @param {String} message Error message.
     */
    const showError = (root, message) => {
        if (!root) {
            return;
        }

        root.classList.add(CLASSES.HAS_ERROR);

        const errorRegion = find(root, SELECTORS.ERROR);
        if (errorRegion) {
            errorRegion.hidden = false;
            errorRegion.textContent = message;
        }
    };

    /**
     * Request dashboard data from the local_uckk external service.
     *
     * The service is responsible for all permission checks and data shaping.
     *
     * @param {Object} config Normalised config.
     * @returns {Promise}
     */
    const fetchDashboard = config => {
        const request = {
            methodname: 'local_uckk_get_player_dashboard',
            args: {
                userid: config.userId,
                contextid: config.contextId
            }
        };

        return Ajax.call([request])[0];
    };

    /**
     * Render dashboard data.
     *
     * If the service returns HTML, it is used directly. Otherwise the configured
     * Mustache template is rendered using the returned data object.
     *
     * @param {HTMLElement} root Dashboard root.
     * @param {Object} config Normalised config.
     * @param {Object} response Service response.
     * @returns {Promise}
     */
    const renderDashboard = (root, config, response) => {
        const content = find(root, SELECTORS.CONTENT);

        if (!content) {
            return Promise.resolve();
        }

        if (response && typeof response.html === 'string') {
            Templates.replaceNodeContents(content, response.html, '');
            return Promise.resolve();
        }

        const data = response && response.data ? response.data : response;

        return Templates.render(config.template, data || {})
            .then(function(html, js) {
                Templates.replaceNodeContents(content, html, js);
                return true;
            });
    };

    /**
     * Refresh dashboard content.
     *
     * @param {HTMLElement} root Dashboard root.
     * @param {Object} config Normalised config.
     * @returns {Promise}
     */
    const refresh = (root, config) => {
        if (!root) {
            return Promise.resolve();
        }

        clearError(root);
        setLoading(root, true);

        return fetchDashboard(config)
            .then(response => renderDashboard(root, config, response))
            .catch(error => {
                return Str.get_string('refreshfailed', 'local_uckk')
                    .then(message => {
                        showError(root, message);
                        Notification.exception(error);
                    });
            })
            .finally(() => {
                setLoading(root, false);
            });
    };

    /**
     * Toggle a collapsible dashboard section.
     *
     * @param {HTMLElement} button Toggle button.
     */
    const toggleSection = button => {
        const sectionId = button.getAttribute('aria-controls');

        if (!sectionId) {
            return;
        }

        const section = document.getElementById(sectionId);

        if (!section) {
            return;
        }

        const expanded = button.getAttribute('aria-expanded') === 'true';

        button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        section.classList.toggle(CLASSES.COLLAPSED, expanded);
        section.hidden = expanded;
    };

    /**
     * Bind dashboard UI events.
     *
     * @param {HTMLElement} root Dashboard root.
     * @param {Object} config Normalised config.
     */
    const bindEvents = (root, config) => {
        root.addEventListener('click', event => {
            const refreshButton = event.target.closest(SELECTORS.REFRESH);

            if (refreshButton && root.contains(refreshButton)) {
                event.preventDefault();
                refresh(root, config);
                return;
            }

            const toggleButton = event.target.closest(SELECTORS.SECTION_TOGGLE);

            if (toggleButton && root.contains(toggleButton)) {
                event.preventDefault();
                toggleSection(toggleButton);
            }
        });
    };

    /**
     * Start optional automatic refresh.
     *
     * @param {HTMLElement} root Dashboard root.
     * @param {Object} config Normalised config.
     */
    const startAutoRefresh = (root, config) => {
        if (!config.refreshEnabled || config.refreshRate <= 0) {
            return;
        }

        window.setInterval(() => {
            if (!document.body.contains(root)) {
                return;
            }

            refresh(root, config);
        }, config.refreshRate * 1000);
    };

    /**
     * Initialise local_uckk dashboard interactions.
     *
     * @param {Object} config Module configuration.
     */
    const init = config => {
        config = normaliseConfig(config);

        const root = getRoot(config);

        if (!root || root.dataset.uckkDashboardInitialised === '1') {
            return;
        }

        root.dataset.uckkDashboardInitialised = '1';

        bindEvents(root, config);
        startAutoRefresh(root, config);
    };

    return {
        init: init,
        refresh: refresh
    };
});