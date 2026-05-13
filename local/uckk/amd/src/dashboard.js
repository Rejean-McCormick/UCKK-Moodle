/* eslint-disable no-console */
/**
 * Dashboard interactions for block_uckk_dashboard.
 *
 * @module     block_uckk_dashboard/dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Pending from 'core/pending';
import Templates from 'core/templates';

/**
 * Dashboard selectors.
 *
 * These selectors intentionally use data attributes so the module does not
 * depend on theme-specific CSS classes.
 *
 * @type {Object}
 */
const SELECTORS = {
    dashboard: '[data-region="uckk-dashboard"]',
    content: '[data-region="uckk-dashboard-content"]',
    refresh: '[data-action="refresh-dashboard"]',
    dismiss: '[data-action="dismiss-dashboard-notice"]',
    collapsibleToggle: '[data-action="toggle-dashboard-section"]',
    collapsibleTarget: '[data-region="uckk-dashboard-section"]',
    loading: '[data-region="uckk-dashboard-loading"]',
    error: '[data-region="uckk-dashboard-error"]',
};

/**
 * CSS state classes.
 *
 * @type {Object}
 */
const CLASSES = {
    loading: 'is-loading',
    collapsed: 'is-collapsed',
    hidden: 'd-none',
};

/**
 * Default external function used when the root element does not define one.
 *
 * The matching service must be declared in db/services.php and must enforce
 * all permissions server-side.
 *
 * @type {String}
 */
const DEFAULT_REFRESH_SERVICE = 'local_uckk_get_player_dashboard';

/**
 * Default template used when a refresh response returns structured data.
 *
 * @type {String}
 */
const DEFAULT_TEMPLATE = 'block_uckk_dashboard/dashboard_block';

/**
 * Prevent duplicate initialisation on the same dashboard node.
 *
 * @type {WeakSet<Element>}
 */
const initializedDashboards = new WeakSet();

/**
 * Registered auto-refresh timer ids.
 *
 * @type {WeakMap<Element, Number>}
 */
const refreshTimers = new WeakMap();

/**
 * Initialise all matching dashboard blocks on the page.
 *
 * @param {String} rootSelector Optional CSS selector for dashboard roots.
 */
export const init = (rootSelector = SELECTORS.dashboard) => {
    document.querySelectorAll(rootSelector).forEach((dashboard) => {
        registerDashboard(dashboard);
    });
};

/**
 * Register one dashboard root element.
 *
 * @param {Element} dashboard Dashboard root.
 */
const registerDashboard = (dashboard) => {
    if (initializedDashboards.has(dashboard)) {
        return;
    }

    initializedDashboards.add(dashboard);

    dashboard.addEventListener('click', (event) => {
        const refreshButton = event.target.closest(SELECTORS.refresh);
        if (refreshButton && dashboard.contains(refreshButton)) {
            event.preventDefault();
            refreshDashboard(dashboard, refreshButton);
            return;
        }

        const dismissButton = event.target.closest(SELECTORS.dismiss);
        if (dismissButton && dashboard.contains(dismissButton)) {
            event.preventDefault();
            dismissNotice(dismissButton);
            return;
        }

        const toggleButton = event.target.closest(SELECTORS.collapsibleToggle);
        if (toggleButton && dashboard.contains(toggleButton)) {
            event.preventDefault();
            toggleSection(dashboard, toggleButton);
        }
    });

    configureAutoRefresh(dashboard);

    if (dashboard.dataset.refreshOnInit === '1') {
        refreshDashboard(dashboard);
    }
};

/**
 * Refresh the dashboard from a Moodle external function.
 *
 * The function may return either:
 * - { html: "<section>...</section>" }
 * - { template: "block_uckk_dashboard/dashboard_block", context: {...} }
 * - a raw context object rendered through the configured dashboard template.
 *
 * @param {Element} dashboard Dashboard root.
 * @param {Element|null} trigger Optional button/link that triggered refresh.
 * @return {Promise<void>}
 */
const refreshDashboard = async(dashboard, trigger = null) => {
    const content = dashboard.querySelector(SELECTORS.content);
    if (!content) {
        return;
    }

    const pendingPromise = new Pending('block_uckk_dashboard/dashboard:refresh');

    setLoadingState(dashboard, trigger, true);
    clearError(dashboard);

    try {
        const response = await callRefreshService(dashboard);
        await renderRefreshResponse(dashboard, content, response);
    } catch (error) {
        showError(dashboard);
        Notification.exception(error);
    } finally {
        setLoadingState(dashboard, trigger, false);
        pendingPromise.resolve();
    }
};

/**
 * Call the configured dashboard refresh service.
 *
 * @param {Element} dashboard Dashboard root.
 * @return {Promise<Object>} Service response.
 */
const callRefreshService = (dashboard) => {
    const methodname = dashboard.dataset.refreshService || DEFAULT_REFRESH_SERVICE;

    const args = {
        userid: getIntegerDatasetValue(dashboard, 'userid'),
        contextid: getIntegerDatasetValue(dashboard, 'contextid'),
        blockinstanceid: getIntegerDatasetValue(dashboard, 'blockinstanceid'),
    };

    return Ajax.call([{
        methodname,
        args,
    }])[0];
};

/**
 * Render a refresh response into the dashboard content region.
 *
 * @param {Element} dashboard Dashboard root.
 * @param {Element} content Content region.
 * @param {Object} response Service response.
 * @return {Promise<void>}
 */
const renderRefreshResponse = async(dashboard, content, response) => {
    if (!response) {
        return;
    }

    if (typeof response.html === 'string') {
        replaceContent(content, response.html, response.javascript || '');
        return;
    }

    const templateName = response.template || dashboard.dataset.template || DEFAULT_TEMPLATE;
    const context = response.context || response;

    const rendered = await Templates.renderForPromise(templateName, context);
    replaceContent(content, rendered.html, rendered.js);
};

/**
 * Replace a dashboard content region with new HTML and JavaScript.
 *
 * @param {Element} content Content region.
 * @param {String} html Rendered HTML.
 * @param {String} js Rendered JS.
 */
const replaceContent = (content, html, js = '') => {
    Templates.replaceNodeContents(content, html, js);
};

/**
 * Set visual loading state.
 *
 * @param {Element} dashboard Dashboard root.
 * @param {Element|null} trigger Optional triggering button/link.
 * @param {Boolean} loading Whether loading is active.
 */
const setLoadingState = (dashboard, trigger, loading) => {
    dashboard.classList.toggle(CLASSES.loading, loading);
    dashboard.setAttribute('aria-busy', loading ? 'true' : 'false');

    const loadingRegion = dashboard.querySelector(SELECTORS.loading);
    if (loadingRegion) {
        loadingRegion.classList.toggle(CLASSES.hidden, !loading);
    }

    if (trigger) {
        if ('disabled' in trigger) {
            trigger.disabled = loading;
        }

        trigger.setAttribute('aria-disabled', loading ? 'true' : 'false');
    }
};

/**
 * Show a local dashboard error region if present.
 *
 * @param {Element} dashboard Dashboard root.
 */
const showError = (dashboard) => {
    const errorRegion = dashboard.querySelector(SELECTORS.error);
    if (errorRegion) {
        errorRegion.classList.remove(CLASSES.hidden);
    }
};

/**
 * Hide a local dashboard error region if present.
 *
 * @param {Element} dashboard Dashboard root.
 */
const clearError = (dashboard) => {
    const errorRegion = dashboard.querySelector(SELECTORS.error);
    if (errorRegion) {
        errorRegion.classList.add(CLASSES.hidden);
    }
};

/**
 * Dismiss a dashboard notice.
 *
 * This is only client-side dismissal. Persistent user preferences must be saved
 * through a permission-checked Moodle service if needed.
 *
 * @param {Element} dismissButton Button that triggered dismissal.
 */
const dismissNotice = (dismissButton) => {
    const notice = dismissButton.closest('[data-region="uckk-dashboard-notice"]');
    if (notice) {
        notice.remove();
    }
};

/**
 * Toggle a dashboard section.
 *
 * @param {Element} dashboard Dashboard root.
 * @param {Element} toggleButton Toggle control.
 */
const toggleSection = (dashboard, toggleButton) => {
    const targetId = toggleButton.getAttribute('aria-controls') || toggleButton.dataset.target;
    if (!targetId) {
        return;
    }

    const target = dashboard.querySelector(`#${CSS.escape(targetId)}`);
    if (!target || !target.matches(SELECTORS.collapsibleTarget)) {
        return;
    }

    const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
    const nextExpanded = !expanded;

    toggleButton.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
    target.classList.toggle(CLASSES.collapsed, !nextExpanded);
    target.hidden = !nextExpanded;
};

/**
 * Configure optional dashboard auto-refresh.
 *
 * Use:
 * data-auto-refresh-seconds="60"
 *
 * @param {Element} dashboard Dashboard root.
 */
const configureAutoRefresh = (dashboard) => {
    const seconds = getIntegerDatasetValue(dashboard, 'autoRefreshSeconds');

    if (seconds <= 0) {
        return;
    }

    const minimumInterval = 30;
    const interval = Math.max(seconds, minimumInterval) * 1000;

    const timerId = window.setInterval(() => {
        if (!document.body.contains(dashboard)) {
            clearAutoRefresh(dashboard);
            return;
        }

        if (dashboard.getAttribute('aria-busy') !== 'true') {
            refreshDashboard(dashboard);
        }
    }, interval);

    refreshTimers.set(dashboard, timerId);
};

/**
 * Clear an auto-refresh timer for a dashboard root.
 *
 * @param {Element} dashboard Dashboard root.
 */
const clearAutoRefresh = (dashboard) => {
    const timerId = refreshTimers.get(dashboard);

    if (timerId) {
        window.clearInterval(timerId);
        refreshTimers.delete(dashboard);
    }
};

/**
 * Safely read an integer data-* value.
 *
 * @param {Element} element Source element.
 * @param {String} key Dataset key.
 * @return {Number}
 */
const getIntegerDatasetValue = (element, key) => {
    const value = window.parseInt(element.dataset[key] || '0', 10);

    if (Number.isNaN(value) || value < 0) {
        return 0;
    }

    return value;
};