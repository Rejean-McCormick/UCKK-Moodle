/**
 * Main Assembly interactions for mod_uckkassembly.
 *
 * This module is intentionally UI-only:
 * - toggles panels and tabs;
 * - refreshes Assembly state, motions, vote results, minutes, and integrity panels;
 * - dispatches declared AJAX actions;
 * - displays accessible status messages.
 *
 * It must not publish decisions, validate integrity, resolve contestations,
 * archive records, or replace server-side capability checks.
 *
 * @module     mod_uckkassembly/assembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkassembly';

const DEFAULT_METHODS = {
    getAssemblyState: 'mod_uckkassembly_get_assembly_state',
    getMotionList: 'mod_uckkassembly_get_motion_list',
    getVoteResults: 'mod_uckkassembly_get_vote_results',
    getMinutesPanel: 'mod_uckkassembly_get_minutes_panel',
    getIntegrityPanel: 'mod_uckkassembly_get_integrity_panel',
};

const SELECTORS = {
    root: '[data-region="uckkassembly"]',
    status: '[data-region="uckkassembly-status"]',
    content: '[data-region="uckkassembly-content"]',

    tab: '[data-action="uckkassembly-tab"]',
    panel: '[data-region="uckkassembly-panel"]',

    disclosure: '[data-action="uckkassembly-toggle"]',
    disclosureTarget: '[data-region="uckkassembly-toggle-target"]',

    refreshAssembly: '[data-action="uckkassembly-refresh"]',
    refreshMotions: '[data-action="uckkassembly-refresh-motions"]',
    refreshVotes: '[data-action="uckkassembly-refresh-votes"]',
    refreshMinutes: '[data-action="uckkassembly-refresh-minutes"]',
    refreshIntegrity: '[data-action="uckkassembly-refresh-integrity"]',

    serviceAction: '[data-action="uckkassembly-service-action"]',
};

const CLASSES = {
    active: 'is-active',
    loading: 'is-loading',
    loaded: 'is-loaded',
    dirty: 'is-dirty',
    error: 'has-error',
    hidden: 'd-none',
};

const ATTRIBUTES = {
    initialised: 'data-uckkassembly-initialised',
};

/**
 * Get a numeric value from root dataset.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} key Dataset key.
 * @param {Number} fallback Fallback value.
 * @returns {Number}
 */
const getNumberData = (root, key, fallback = 0) => {
    const value = Number(root?.dataset?.[key] ?? fallback);

    return Number.isFinite(value) ? value : fallback;
};

/**
 * Return base arguments for Assembly external functions.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const getBaseArgs = root => ({
    cmid: getNumberData(root, 'cmid'),
    assemblyid: getNumberData(root, 'assemblyid'),
});

/**
 * Return method names, allowing per-page overrides.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Initialisation options.
 * @returns {Object}
 */
const getMethods = (root, options = {}) => ({
    getAssemblyState: root.dataset.getAssemblyStateMethod || options.getAssemblyStateMethod || DEFAULT_METHODS.getAssemblyState,
    getMotionList: root.dataset.getMotionListMethod || options.getMotionListMethod || DEFAULT_METHODS.getMotionList,
    getVoteResults: root.dataset.getVoteResultsMethod || options.getVoteResultsMethod || DEFAULT_METHODS.getVoteResults,
    getMinutesPanel: root.dataset.getMinutesPanelMethod || options.getMinutesPanelMethod || DEFAULT_METHODS.getMinutesPanel,
    getIntegrityPanel: root.dataset.getIntegrityPanelMethod || options.getIntegrityPanelMethod || DEFAULT_METHODS.getIntegrityPanel,
});

/**
 * Set accessible status text.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} message Status message.
 */
const setStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Call one Moodle external service.
 *
 * @param {String} methodname External function name.
 * @param {Object} args External function args.
 * @returns {Promise<Object>}
 */
const callService = (methodname, args) => Ajax.call([{
    methodname,
    args,
}])[0];

/**
 * Replace a DOM region from an external-function response.
 *
 * Response may be:
 * - {html: "...", js: "..."}
 * - {template: "mod_uckkassembly/example", context: {...}}
 *
 * @param {HTMLElement} region Target region.
 * @param {Object} response Service response.
 * @returns {Promise<void>}
 */
const replaceRegionFromResponse = async(region, response) => {
    if (!region || !response) {
        return;
    }

    if (response.template && response.context) {
        const html = await Templates.render(response.template, response.context);
        Templates.replaceNodeContents(region, html, '');
        return;
    }

    if (response.html) {
        Templates.replaceNodeContents(region, response.html, response.js || '');

        if (response.js) {
            Templates.runTemplateJS(response.js);
        }
    }
};

/**
 * Find a target region.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} regionName Data-region name.
 * @returns {HTMLElement|null}
 */
const getRegion = (root, regionName) => root.querySelector(`[data-region="${regionName}"]`);

/**
 * Refresh one Assembly region through an external function.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} methodname External function name.
 * @param {Object} args Arguments.
 * @param {String} targetRegion Target region name.
 * @param {String} loadingKey Language string while loading.
 * @param {String} successKey Language string on success.
 * @param {String} failureKey Language string on failure.
 * @returns {Promise<void>}
 */
const refreshRegion = async(root, methodname, args, targetRegion, loadingKey, successKey, failureKey) => {
    const region = getRegion(root, targetRegion);

    if (!region) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString(loadingKey, COMPONENT));

        const response = await callService(methodname, args);
        await replaceRegionFromResponse(region, response);

        root.classList.add(CLASSES.loaded);
        setStatus(root, await getString(successKey, COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString(failureKey, COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Refresh the main Assembly state.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshAssemblyState = (root, options = {}) => {
    const methods = getMethods(root, options);

    return refreshRegion(
        root,
        methods.getAssemblyState,
        getBaseArgs(root),
        'uckkassembly-content',
        'assemblyrefreshing',
        'assemblyrefreshed',
        'assemblyrefreshfailed'
    );
};

/**
 * Refresh the motion list.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshMotionList = (root, options = {}) => {
    const methods = getMethods(root, options);

    return refreshRegion(
        root,
        methods.getMotionList,
        getBaseArgs(root),
        'uckkassembly-motion-list',
        'motionsrefreshing',
        'motionsrefreshed',
        'motionsrefreshfailed'
    );
};

/**
 * Refresh vote results for a motion.
 *
 * @param {HTMLElement} root Root element.
 * @param {HTMLElement|null} source Source element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshVoteResults = (root, source = null, options = {}) => {
    const methods = getMethods(root, options);
    const motionid = getNumberData(source, 'motionid', getNumberData(root, 'motionid'));

    return refreshRegion(
        root,
        methods.getVoteResults,
        {
            ...getBaseArgs(root),
            motionid,
        },
        'uckkassembly-vote-results',
        'voteresultsrefreshing',
        'voteresultsrefreshed',
        'voteresultsrefreshfailed'
    );
};

/**
 * Refresh the minutes panel.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshMinutesPanel = (root, options = {}) => {
    const methods = getMethods(root, options);

    return refreshRegion(
        root,
        methods.getMinutesPanel,
        getBaseArgs(root),
        'uckkassembly-minutes-panel',
        'minutesrefreshing',
        'minutesrefreshed',
        'minutesrefreshfailed'
    );
};

/**
 * Refresh the integrity panel.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshIntegrityPanel = (root, options = {}) => {
    const methods = getMethods(root, options);

    return refreshRegion(
        root,
        methods.getIntegrityPanel,
        getBaseArgs(root),
        'uckkassembly-integrity-panel',
        'integrityrefreshing',
        'integrityrefreshed',
        'integrityrefreshfailed'
    );
};

/**
 * Activate a tab and its panel.
 *
 * @param {HTMLElement} root Root element.
 * @param {HTMLElement} tab Tab trigger.
 */
const activateTab = (root, tab) => {
    const target = tab.dataset.target;

    if (!target) {
        return;
    }

    root.querySelectorAll(SELECTORS.tab).forEach(item => {
        const active = item === tab;
        item.classList.toggle(CLASSES.active, active);
        item.setAttribute('aria-selected', active ? 'true' : 'false');
        item.setAttribute('tabindex', active ? '0' : '-1');
    });

    root.querySelectorAll(SELECTORS.panel).forEach(panel => {
        const active = panel.id === target || panel.dataset.panel === target;
        panel.classList.toggle(CLASSES.active, active);
        panel.hidden = !active;
        panel.setAttribute('aria-hidden', active ? 'false' : 'true');
    });
};

/**
 * Toggle a disclosure region.
 *
 * @param {HTMLElement} trigger Toggle trigger.
 */
const toggleDisclosure = trigger => {
    const targetId = trigger.getAttribute('aria-controls') || trigger.dataset.target;

    if (!targetId) {
        return;
    }

    const target = document.getElementById(targetId)
        || document.querySelector(`[data-region="${targetId}"]`);

    if (!target) {
        return;
    }

    const expanded = trigger.getAttribute('aria-expanded') === 'true';

    trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    target.hidden = expanded;
    target.classList.toggle(CLASSES.hidden, expanded);
};

/**
 * Collect generic data-* payload for a service action.
 *
 * @param {HTMLElement} root Root element.
 * @param {HTMLElement} trigger Trigger element.
 * @returns {Object}
 */
const collectServiceActionArgs = (root, trigger) => {
    const args = {
        ...getBaseArgs(root),
        sesskey: M.cfg.sesskey,
    };

    Object.entries(trigger.dataset).forEach(([key, value]) => {
        if (key.startsWith('arg')) {
            const argName = key.substring(3).replace(/^[A-Z]/, char => char.toLowerCase());
            args[argName] = normaliseDatasetValue(value);
        }
    });

    return args;
};

/**
 * Normalise values from data attributes.
 *
 * @param {String} value Raw value.
 * @returns {String|Number|Boolean}
 */
const normaliseDatasetValue = value => {
    if (value === 'true') {
        return true;
    }

    if (value === 'false') {
        return false;
    }

    if (/^-?\d+$/.test(value)) {
        return Number(value);
    }

    return value;
};

/**
 * Confirm a write action when requested.
 *
 * @param {HTMLElement} trigger Trigger element.
 * @returns {Promise<Boolean>}
 */
const confirmActionIfNeeded = async(trigger) => {
    if (trigger.dataset.confirm !== 'true') {
        return true;
    }

    const title = trigger.dataset.confirmTitle || await getString('confirm', 'moodle');
    const message = trigger.dataset.confirmMessage || await getString('confirmassemblyaction', COMPONENT);
    const yes = trigger.dataset.confirmYes || await getString('confirm', 'moodle');
    const no = trigger.dataset.confirmNo || await getString('cancel', 'moodle');

    return new Promise(resolve => {
        Notification.confirm(
            title,
            message,
            yes,
            no,
            () => resolve(true),
            () => resolve(false)
        );
    });
};

/**
 * Dispatch a generic service action.
 *
 * Expected trigger attributes:
 * data-action="uckkassembly-service-action"
 * data-method-name="mod_uckkassembly_submit_vote"
 * data-target-region="uckkassembly-vote-results"
 * data-arg-motionid="123"
 *
 * @param {HTMLElement} root Root element.
 * @param {HTMLElement} trigger Action trigger.
 * @returns {Promise<void>}
 */
const dispatchServiceAction = async(root, trigger) => {
    const methodname = trigger.dataset.methodName;
    const targetRegion = trigger.dataset.targetRegion || '';
    const confirmed = await confirmActionIfNeeded(trigger);

    if (!methodname || !confirmed) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('submitting', COMPONENT));

        const response = await callService(methodname, collectServiceActionArgs(root, trigger));

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (targetRegion) {
            await replaceRegionFromResponse(getRegion(root, targetRegion), response);
        }

        setStatus(root, await getString('submitted', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('submitfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Handle click events.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const handleClick = (event, root, options = {}) => {
    const tab = event.target.closest(SELECTORS.tab);

    if (tab) {
        event.preventDefault();
        activateTab(root, tab);
        return;
    }

    const disclosure = event.target.closest(SELECTORS.disclosure);

    if (disclosure) {
        event.preventDefault();
        toggleDisclosure(disclosure);
        return;
    }

    const refreshAssembly = event.target.closest(SELECTORS.refreshAssembly);

    if (refreshAssembly) {
        event.preventDefault();
        refreshAssemblyState(root, options);
        return;
    }

    const refreshMotions = event.target.closest(SELECTORS.refreshMotions);

    if (refreshMotions) {
        event.preventDefault();
        refreshMotionList(root, options);
        return;
    }

    const refreshVotes = event.target.closest(SELECTORS.refreshVotes);

    if (refreshVotes) {
        event.preventDefault();
        refreshVoteResults(root, refreshVotes, options);
        return;
    }

    const refreshMinutes = event.target.closest(SELECTORS.refreshMinutes);

    if (refreshMinutes) {
        event.preventDefault();
        refreshMinutesPanel(root, options);
        return;
    }

    const refreshIntegrity = event.target.closest(SELECTORS.refreshIntegrity);

    if (refreshIntegrity) {
        event.preventDefault();
        refreshIntegrityPanel(root, options);
        return;
    }

    const serviceAction = event.target.closest(SELECTORS.serviceAction);

    if (serviceAction) {
        event.preventDefault();
        dispatchServiceAction(root, serviceAction);
    }
};

/**
 * Mark root dirty when fields inside it change.
 *
 * @param {HTMLElement} root Root element.
 */
const markDirty = root => {
    root.classList.add(CLASSES.dirty);
};

/**
 * Initialise accessibility state for tabs and panels.
 *
 * @param {HTMLElement} root Root element.
 */
const initialiseTabs = root => {
    const tabs = Array.from(root.querySelectorAll(SELECTORS.tab));

    if (!tabs.length) {
        return;
    }

    const selected = tabs.find(tab => tab.getAttribute('aria-selected') === 'true')
        || tabs.find(tab => tab.classList.contains(CLASSES.active))
        || tabs[0];

    activateTab(root, selected);
};

/**
 * Initialise one Assembly root.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');

    initialiseTabs(root);

    root.addEventListener('click', event => {
        handleClick(event, root, options);
    });

    root.addEventListener('input', () => {
        markDirty(root);
    });

    root.addEventListener('change', () => {
        markDirty(root);
    });

    if (options.refreshOnInit === true || root.dataset.refreshOnInit === 'true') {
        refreshAssemblyState(root, options);
    }
};

/**
 * Initialise UCKK Assembly UI.
 *
 * Recommended PHP:
 * $PAGE->requires->js_call_amd('mod_uckkassembly/assembly', 'init', [$uniqid]);
 *
 * Recommended Mustache:
 * {{#js}}
 * require(['mod_uckkassembly/assembly'], function(Assembly) {
 *     Assembly.init('{{uniqid}}');
 * });
 * {{/js}}
 *
 * @param {String|null} rootId Optional root id.
 * @param {Object} options Optional configuration.
 */
export const init = (rootId = null, options = {}) => {
    const roots = rootId
        ? [document.getElementById(rootId)].filter(Boolean)
        : Array.from(document.querySelectorAll(SELECTORS.root));

    roots.forEach(root => initialiseRoot(root, options));
};

/**
 * Public helper to refresh the whole Assembly state.
 *
 * @param {String} rootId Root id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<void>}
 */
export const refresh = (rootId, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return refreshAssemblyState(root, options);
};

/**
 * Public helper to refresh minutes.
 *
 * @param {String} rootId Root id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<void>}
 */
export const refreshMinutes = (rootId, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return refreshMinutesPanel(root, options);
};

/**
 * Public helper to refresh vote results.
 *
 * @param {String} rootId Root id.
 * @param {Number} motionid Motion id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<void>}
 */
export const refreshVotes = (rootId, motionid = 0, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return refreshVoteResults(root, {
        dataset: {
            motionid: String(motionid),
        },
    }, options);
};