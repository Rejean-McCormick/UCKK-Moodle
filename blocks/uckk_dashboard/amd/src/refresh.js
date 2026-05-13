/**
 * Refresh behaviour for the UCKK dashboard block.
 *
 * @module     block_uckk_dashboard/refresh
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as ajaxCall} from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';

const DEFAULTS = {
    methodName: 'local_uckk_get_player_dashboard',
    template: 'block_uckk_dashboard/dashboard_block',
    contentSelector: '[data-region="uckk-dashboard-content"]',
    refreshSelector: '[data-action="uckk-dashboard-refresh"]',
    statusSelector: '[data-region="uckk-dashboard-refresh-status"]',
    loadingClass: 'uckk-dashboard-loading',
    loadedClass: 'uckk-dashboard-loaded',
    errorClass: 'uckk-dashboard-error',
    autoRefreshSeconds: 0,
    contextId: 0,
    courseId: 0,
    userId: 0,
};

/**
 * Active auto-refresh timers by root element.
 *
 * @type {WeakMap<Element, number>}
 */
const refreshTimers = new WeakMap();

/**
 * Resolve a dashboard root element.
 *
 * @param {string|Element} root Dashboard root selector or element.
 * @returns {Element|null}
 */
const resolveRoot = root => {
    if (root instanceof Element) {
        return root;
    }

    if (typeof root === 'string' && root.trim() !== '') {
        return document.querySelector(root);
    }

    return null;
};

/**
 * Merge defaults, caller options, and root data attributes.
 *
 * @param {Element} root Dashboard root element.
 * @param {Object} options Runtime options passed from PHP.
 * @returns {Object}
 */
const getSettings = (root, options = {}) => {
    const dataset = root.dataset || {};

    return {
        ...DEFAULTS,
        ...options,
        contextId: Number(options.contextId || dataset.contextid || DEFAULTS.contextId),
        courseId: Number(options.courseId || dataset.courseid || DEFAULTS.courseId),
        userId: Number(options.userId || dataset.userid || DEFAULTS.userId),
        autoRefreshSeconds: Number(
            options.autoRefreshSeconds ||
            dataset.autorefreshseconds ||
            DEFAULTS.autoRefreshSeconds
        ),
        methodName: options.methodName || dataset.methodname || DEFAULTS.methodName,
        template: options.template || dataset.template || DEFAULTS.template,
    };
};

/**
 * Build web service arguments.
 *
 * Keep these small. Moodle's js_call_amd parameters should remain small, and
 * larger dashboard data should be fetched by AJAX.
 *
 * @param {Element} root Dashboard root element.
 * @param {Object} settings Refresh settings.
 * @returns {Object}
 */
const buildServiceArgs = (root, settings) => {
    const args = {
        contextid: settings.contextId,
        courseid: settings.courseId,
        userid: settings.userId,
    };

    if (root.dataset && root.dataset.instanceid) {
        args.instanceid = Number(root.dataset.instanceid);
    }

    return args;
};

/**
 * Set the dashboard loading state.
 *
 * @param {Element} root Dashboard root element.
 * @param {Object} settings Refresh settings.
 * @param {boolean} loading Whether the block is loading.
 */
const setLoading = (root, settings, loading) => {
    root.classList.toggle(settings.loadingClass, loading);
    root.setAttribute('aria-busy', loading ? 'true' : 'false');

    const refreshButton = root.querySelector(settings.refreshSelector);
    if (refreshButton) {
        refreshButton.disabled = loading;
        refreshButton.setAttribute('aria-disabled', loading ? 'true' : 'false');
    }
};

/**
 * Set a screen-reader friendly refresh status.
 *
 * @param {Element} root Dashboard root element.
 * @param {Object} settings Refresh settings.
 * @param {string} message Status message.
 */
const setStatus = (root, settings, message) => {
    const statusRegion = root.querySelector(settings.statusSelector);

    if (statusRegion) {
        statusRegion.textContent = message;
    }
};

/**
 * Render a dashboard response.
 *
 * Supported response shapes:
 *
 * 1. {html: "..."}
 * 2. {template: "component/template", context: {...}}
 * 3. plain context object rendered through settings.template
 *
 * @param {Element} root Dashboard root element.
 * @param {Object} settings Refresh settings.
 * @param {Object} response Web service response.
 * @returns {Promise<void>}
 */
const renderResponse = async(root, settings, response) => {
    const content = root.querySelector(settings.contentSelector);

    if (!content) {
        throw new Error(`Missing dashboard content region: ${settings.contentSelector}`);
    }

    if (response && typeof response.html === 'string') {
        content.innerHTML = response.html;
        Templates.runTemplateJS(response.html);
        return;
    }

    const templateName = response && response.template ? response.template : settings.template;
    const templateContext = response && response.context ? response.context : response;

    const html = await Templates.render(templateName, templateContext || {});
    content.innerHTML = html;
    Templates.runTemplateJS(html);
};

/**
 * Refresh one dashboard block.
 *
 * @param {Element} root Dashboard root element.
 * @param {Object} settings Refresh settings.
 * @returns {Promise<void>}
 */
const refreshDashboard = async(root, settings) => {
    setLoading(root, settings, true);
    root.classList.remove(settings.errorClass);

    try {
        setStatus(root, settings, await getString('refreshing', 'block_uckk_dashboard'));

        const response = await ajaxCall([{
            methodname: settings.methodName,
            args: buildServiceArgs(root, settings),
        }])[0];

        await renderResponse(root, settings, response);

        root.classList.add(settings.loadedClass);
        setStatus(root, settings, await getString('refreshed', 'block_uckk_dashboard'));
    } catch (error) {
        root.classList.add(settings.errorClass);
        setStatus(root, settings, await getString('refreshfailed', 'block_uckk_dashboard'));

        await Notification.exception(error);
    } finally {
        setLoading(root, settings, false);
    }
};

/**
 * Bind manual refresh control.
 *
 * @param {Element} root Dashboard root element.
 * @param {Object} settings Refresh settings.
 */
const bindManualRefresh = (root, settings) => {
    root.addEventListener('click', event => {
        const refreshButton = event.target.closest(settings.refreshSelector);

        if (!refreshButton || !root.contains(refreshButton)) {
            return;
        }

        event.preventDefault();
        refreshDashboard(root, settings);
    });
};

/**
 * Bind optional auto-refresh.
 *
 * Auto-refresh is disabled unless autoRefreshSeconds is greater than zero.
 *
 * @param {Element} root Dashboard root element.
 * @param {Object} settings Refresh settings.
 */
const bindAutoRefresh = (root, settings) => {
    if (!settings.autoRefreshSeconds || settings.autoRefreshSeconds <= 0) {
        return;
    }

    if (refreshTimers.has(root)) {
        window.clearInterval(refreshTimers.get(root));
    }

    const timer = window.setInterval(() => {
        if (!document.body.contains(root)) {
            window.clearInterval(timer);
            refreshTimers.delete(root);
            return;
        }

        refreshDashboard(root, settings);
    }, settings.autoRefreshSeconds * 1000);

    refreshTimers.set(root, timer);
};

/**
 * Initialise refresh behaviour for one dashboard block.
 *
 * Recommended PHP call:
 *
 * $PAGE->requires->js_call_amd('block_uckk_dashboard/refresh', 'init', [
 *     '#uckk-dashboard-block-' . $this->instance->id,
 *     [
 *         'contextId' => $context->id,
 *         'courseId' => $COURSE->id,
 *         'userId' => $USER->id,
 *     ],
 * ]);
 *
 * @param {string|Element} root Dashboard root selector or element.
 * @param {Object} options Runtime options.
 */
export const init = (root, options = {}) => {
    const rootElement = resolveRoot(root);

    if (!rootElement) {
        return;
    }

    if (rootElement.dataset.refreshInitialised === '1') {
        return;
    }

    rootElement.dataset.refreshInitialised = '1';

    const settings = getSettings(rootElement, options);

    bindManualRefresh(rootElement, settings);
    bindAutoRefresh(rootElement, settings);
};

/**
 * Public refresh function for tests or external dashboard controls.
 *
 * @param {string|Element} root Dashboard root selector or element.
 * @param {Object} options Runtime options.
 * @returns {Promise<void>}
 */
export const refresh = async(root, options = {}) => {
    const rootElement = resolveRoot(root);

    if (!rootElement) {
        return;
    }

    const settings = getSettings(rootElement, options);
    await refreshDashboard(rootElement, settings);
};