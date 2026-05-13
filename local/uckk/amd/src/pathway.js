// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Pathway interactions for local_uckk.
 *
 * This module is intentionally UI-only:
 * - it reads lightweight data from DOM data attributes;
 * - it calls permission-checked Moodle external services;
 * - it renders Moodle templates;
 * - it does not decide permissions, progress, completion, badges, or integrity.
 *
 * Expected root markup:
 *
 * <section
 *     data-region="local-uckk-pathway"
 *     data-contextid="1"
 *     data-userid="123"
 *     data-pathwayid="456">
 *     <div data-region="local-uckk-pathway-status"></div>
 *     <div data-region="local-uckk-pathway-content"></div>
 * </section>
 *
 * Supported actions inside the root:
 *
 * <button data-action="local-uckk-pathway-refresh">...</button>
 * <button data-action="local-uckk-pathway-select" data-pathwayid="456">...</button>
 * <button data-action="local-uckk-pathway-toggle" aria-controls="some-panel">...</button>
 *
 * @module     local_uckk/pathway
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';
import {getString} from 'core/str';

const COMPONENT = 'local_uckk';
const TEMPLATE = 'local_uckk/pathway_card';

const SELECTORS = {
    root: '[data-region="local-uckk-pathway"]',
    content: '[data-region="local-uckk-pathway-content"]',
    status: '[data-region="local-uckk-pathway-status"]',
    action: '[data-action]',
};

const ACTIONS = {
    refresh: 'local-uckk-pathway-refresh',
    select: 'local-uckk-pathway-select',
    toggle: 'local-uckk-pathway-toggle',
};

const EVENTS = {
    loading: 'local_uckk:pathwayLoading',
    loaded: 'local_uckk:pathwayLoaded',
    failed: 'local_uckk:pathwayFailed',
};

/**
 * Convert a DOM dataset value to an integer.
 *
 * @param {HTMLElement} element
 * @param {String} key
 * @param {Number} fallback
 * @returns {Number}
 */
const getDatasetInt = (element, key, fallback = 0) => {
    const value = window.parseInt(element.dataset[key] || '', 10);

    return Number.isNaN(value) ? fallback : value;
};

/**
 * Get all pathway roots for a selector.
 *
 * @param {String} rootSelector
 * @returns {HTMLElement[]}
 */
const getRoots = (rootSelector) => Array.from(document.querySelectorAll(rootSelector));

/**
 * Read service parameters from the pathway root.
 *
 * @param {HTMLElement} root
 * @returns {{contextid: Number, userid: Number, pathwayid: Number, includecompleted: Boolean}}
 */
const getParams = (root) => ({
    contextid: getDatasetInt(root, 'contextid'),
    userid: getDatasetInt(root, 'userid'),
    pathwayid: getDatasetInt(root, 'pathwayid'),
    includecompleted: root.dataset.includecompleted !== '0',
});

/**
 * Set a status region value.
 *
 * @param {HTMLElement} root
 * @param {String} message
 */
const setStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Set a status region value from a Moodle language string.
 *
 * @param {HTMLElement} root
 * @param {String} key
 * @returns {Promise<void>}
 */
const setStatusFromString = async(root, key) => {
    try {
        setStatus(root, await getString(key, COMPONENT));
    } catch (error) {
        setStatus(root, '');
    }
};

/**
 * Set the loading state for the pathway region.
 *
 * @param {HTMLElement} root
 * @param {Boolean} loading
 */
const setLoading = (root, loading) => {
    root.classList.toggle('local-uckk-pathway-loading', loading);
    root.setAttribute('aria-busy', loading ? 'true' : 'false');

    root.querySelectorAll('button, input, select, textarea').forEach((element) => {
        element.disabled = loading;
    });
};

/**
 * Dispatch a pathway lifecycle event.
 *
 * @param {HTMLElement} root
 * @param {String} name
 * @param {Object} detail
 */
const dispatchPathwayEvent = (root, name, detail = {}) => {
    root.dispatchEvent(new CustomEvent(name, {
        bubbles: true,
        detail,
    }));
};

/**
 * Call the pathway map web service.
 *
 * The service must be declared in local/uckk/db/services.php with:
 * - methodname: local_uckk_get_pathway_map
 * - ajax: true
 * - permission checks in the external class
 *
 * @param {{contextid: Number, userid: Number, pathwayid: Number, includecompleted: Boolean}} params
 * @returns {Promise<Object>}
 */
export const fetchPathwayMap = (params) => fetchMany([{
    methodname: 'local_uckk_get_pathway_map',
    args: {
        contextid: params.contextid,
        userid: params.userid,
        pathwayid: params.pathwayid,
        includecompleted: params.includecompleted,
    },
}])[0];

/**
 * Render a pathway response.
 *
 * The preferred response shape is:
 *
 * {
 *     templatecontext: {...}
 * }
 *
 * A response containing pre-rendered html/js is also accepted for compatibility
 * with future renderer-backed services.
 *
 * @param {HTMLElement} root
 * @param {Object} response
 * @returns {Promise<void>}
 */
const renderPathway = async(root, response) => {
    const target = root.querySelector(SELECTORS.content) || root;

    if (response.html) {
        Templates.replaceNodeContents(target, response.html, response.js || '');
        return;
    }

    const context = response.templatecontext || response;
    const {html, js} = await Templates.renderForPromise(TEMPLATE, context);

    Templates.replaceNodeContents(target, html, js);
};

/**
 * Refresh a pathway root from the Moodle external service.
 *
 * @param {HTMLElement} root
 * @returns {Promise<void>}
 */
export const refresh = async(root) => {
    const params = getParams(root);

    setLoading(root, true);
    dispatchPathwayEvent(root, EVENTS.loading, params);
    await setStatusFromString(root, 'pathwayloading');

    try {
        const response = await fetchPathwayMap(params);

        await renderPathway(root, response);
        await setStatusFromString(root, 'pathwayloaded');
        dispatchPathwayEvent(root, EVENTS.loaded, {
            params,
            response,
        });
    } catch (error) {
        await setStatusFromString(root, 'pathwayloadfailed');
        dispatchPathwayEvent(root, EVENTS.failed, {
            params,
            error,
        });
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Toggle a controlled panel inside the pathway region.
 *
 * @param {HTMLElement} root
 * @param {HTMLElement} trigger
 */
const togglePanel = (root, trigger) => {
    const panelId = trigger.getAttribute('aria-controls');

    if (!panelId) {
        return;
    }

    const panel = root.querySelector(`#${CSS.escape(panelId)}`);

    if (!panel) {
        return;
    }

    const expanded = trigger.getAttribute('aria-expanded') === 'true';

    trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    panel.hidden = expanded;
};

/**
 * Select a pathway and refresh the region.
 *
 * @param {HTMLElement} root
 * @param {HTMLElement} trigger
 * @returns {Promise<void>}
 */
const selectPathway = async(root, trigger) => {
    const pathwayid = getDatasetInt(trigger, 'pathwayid');

    if (!pathwayid) {
        return;
    }

    root.dataset.pathwayid = pathwayid.toString();

    root.querySelectorAll('[data-action="local-uckk-pathway-select"][aria-current="true"]')
        .forEach((element) => element.removeAttribute('aria-current'));

    trigger.setAttribute('aria-current', 'true');

    await refresh(root);
};

/**
 * Handle click actions inside a pathway region.
 *
 * @param {HTMLElement} root
 * @param {MouseEvent} event
 */
const handleClick = async(root, event) => {
    const trigger = event.target.closest(SELECTORS.action);

    if (!trigger || !root.contains(trigger)) {
        return;
    }

    switch (trigger.dataset.action) {
        case ACTIONS.refresh:
            event.preventDefault();
            await refresh(root);
            break;

        case ACTIONS.select:
            event.preventDefault();
            await selectPathway(root, trigger);
            break;

        case ACTIONS.toggle:
            event.preventDefault();
            togglePanel(root, trigger);
            break;

        default:
            break;
    }
};

/**
 * Initialise one pathway root.
 *
 * @param {HTMLElement} root
 */
const initRoot = (root) => {
    if (root.dataset.localUckkPathwayInitialised === '1') {
        return;
    }

    root.dataset.localUckkPathwayInitialised = '1';

    root.addEventListener('click', (event) => {
        handleClick(root, event);
    });

    if (root.dataset.autoload === '1') {
        refresh(root);
    }
};

/**
 * Initialise pathway interactions.
 *
 * Usage from PHP:
 *
 * $PAGE->requires->js_call_amd('local_uckk/pathway', 'init');
 *
 * Or:
 *
 * $PAGE->requires->js_call_amd('local_uckk/pathway', 'init', ['#my-pathway-region']);
 *
 * @param {String} rootSelector
 */
export const init = (rootSelector = SELECTORS.root) => {
    getRoots(rootSelector).forEach(initRoot);
};