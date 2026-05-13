// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Archive panel behaviour for the UCKK course format.
 *
 * This module is intentionally UI-only. It manages the opening, closing,
 * accessibility state and optional local persistence of archive panels.
 *
 * It must not:
 * - decide whether a user can access an archive;
 * - fetch restricted archive content;
 * - validate evidence;
 * - change archive states;
 * - make integrity decisions;
 * - expose private data.
 *
 * Required markup conventions:
 *
 * <button
 *     type="button"
 *     data-action="toggle-archive-panel"
 *     data-target="#uckk-archive-panel-12"
 *     aria-controls="uckk-archive-panel-12"
 *     aria-expanded="false">
 *     Archives
 * </button>
 *
 * <section
 *     id="uckk-archive-panel-12"
 *     data-region="uckk-archive-panel"
 *     hidden>
 *     ...
 * </section>
 *
 * Optional data attributes:
 * - data-course-id="12"
 * - data-persist="true"
 * - data-close-on-escape="true"
 * - data-focus-on-open="#some-selector"
 *
 * @module     format_uckk/archivepanel
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    panel: '[data-region="uckk-archive-panel"]',
    toggle: '[data-action="toggle-archive-panel"]',
    open: '[data-action="open-archive-panel"]',
    close: '[data-action="close-archive-panel"]',
    panelTitle: '[data-region="uckk-archive-panel-title"]',
    panelBody: '[data-region="uckk-archive-panel-body"]',
};

const CLASSES = {
    panelOpen: 'uckk-archive-panel-open',
    bodyHasOpenPanel: 'uckk-has-open-archive-panel',
    toggleActive: 'active',
};

const DEFAULTS = {
    courseid: 0,
    persist: true,
    closeOnEscape: true,
    closeOthers: false,
    focusOnOpen: true,
    storagePrefix: 'format_uckk_archivepanel',
};

/**
 * Normalise the init config.
 *
 * @param {Object} config Raw config.
 * @returns {Object}
 */
const normaliseConfig = config => {
    const merged = Object.assign({}, DEFAULTS, config || {});

    merged.courseid = parseInt(merged.courseid, 10) || 0;
    merged.persist = merged.persist !== false;
    merged.closeOnEscape = merged.closeOnEscape !== false;
    merged.closeOthers = merged.closeOthers === true;
    merged.focusOnOpen = merged.focusOnOpen !== false;

    return merged;
};

/**
 * Build a stable localStorage key.
 *
 * @param {Object} config Archive panel config.
 * @returns {String}
 */
const getStorageKey = config => {
    return `${config.storagePrefix}_${config.courseid || 'site'}`;
};

/**
 * Check whether localStorage can be used.
 *
 * @returns {Boolean}
 */
const canUseStorage = () => {
    try {
        const testKey = 'format_uckk_archivepanel_test';
        window.localStorage.setItem(testKey, '1');
        window.localStorage.removeItem(testKey);
        return true;
    } catch (error) {
        return false;
    }
};

/**
 * Read the persisted open panel id.
 *
 * @param {Object} config Archive panel config.
 * @returns {String|null}
 */
const getPersistedPanelId = config => {
    if (!config.persist || !canUseStorage()) {
        return null;
    }

    return window.localStorage.getItem(getStorageKey(config));
};

/**
 * Persist the open panel id.
 *
 * @param {Object} config Archive panel config.
 * @param {String|null} panelId Panel id.
 */
const persistPanelId = (config, panelId) => {
    if (!config.persist || !canUseStorage()) {
        return;
    }

    const storageKey = getStorageKey(config);

    if (!panelId) {
        window.localStorage.removeItem(storageKey);
        return;
    }

    window.localStorage.setItem(storageKey, panelId);
};

/**
 * Resolve a panel from a selector, id, element, or toggle.
 *
 * @param {String|HTMLElement|null} target Target selector, id, panel, or toggle.
 * @returns {HTMLElement|null}
 */
const resolvePanel = target => {
    if (!target) {
        return null;
    }

    if (target instanceof HTMLElement) {
        if (target.matches(SELECTORS.panel)) {
            return target;
        }

        const explicitTarget = target.getAttribute('data-target') || target.getAttribute('aria-controls');
        return resolvePanel(explicitTarget);
    }

    if (typeof target !== 'string') {
        return null;
    }

    let selector = target.trim();

    if (selector === '') {
        return null;
    }

    if (!selector.startsWith('#') && !selector.startsWith('[') && !selector.startsWith('.')) {
        selector = `#${selector}`;
    }

    return document.querySelector(selector);
};

/**
 * Get every toggle linked to a panel.
 *
 * @param {HTMLElement} panel Archive panel.
 * @returns {HTMLElement[]}
 */
const getPanelToggles = panel => {
    if (!panel || !panel.id) {
        return [];
    }

    const idSelector = CSS.escape(panel.id);

    return Array.from(document.querySelectorAll(
        `${SELECTORS.toggle}[data-target="#${idSelector}"], ` +
        `${SELECTORS.toggle}[data-target="${idSelector}"], ` +
        `${SELECTORS.toggle}[aria-controls="${idSelector}"], ` +
        `${SELECTORS.open}[data-target="#${idSelector}"], ` +
        `${SELECTORS.close}[data-target="#${idSelector}"]`
    ));
};

/**
 * Set ARIA state for controls linked to a panel.
 *
 * @param {HTMLElement} panel Archive panel.
 * @param {Boolean} expanded Whether the panel is open.
 */
const setControlState = (panel, expanded) => {
    getPanelToggles(panel).forEach(control => {
        control.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        control.classList.toggle(CLASSES.toggleActive, expanded);
    });
};

/**
 * Ensure a panel has the minimum accessible attributes.
 *
 * @param {HTMLElement} panel Archive panel.
 */
const preparePanelAccessibility = panel => {
    if (!panel.id) {
        panel.id = `uckk-archive-panel-${Math.random().toString(36).slice(2)}`;
    }

    if (!panel.hasAttribute('role')) {
        panel.setAttribute('role', 'region');
    }

    if (!panel.hasAttribute('tabindex')) {
        panel.setAttribute('tabindex', '-1');
    }

    const title = panel.querySelector(SELECTORS.panelTitle);
    if (title) {
        if (!title.id) {
            title.id = `${panel.id}-title`;
        }

        if (!panel.hasAttribute('aria-labelledby')) {
            panel.setAttribute('aria-labelledby', title.id);
        }
    }
};

/**
 * Check whether a panel is currently open.
 *
 * @param {HTMLElement} panel Archive panel.
 * @returns {Boolean}
 */
export const isOpen = panel => {
    return !!panel && !panel.hidden && panel.classList.contains(CLASSES.panelOpen);
};

/**
 * Close a panel.
 *
 * @param {String|HTMLElement} target Panel target.
 * @param {Object} config Archive panel config.
 */
export const closePanel = (target, config = {}) => {
    const panel = resolvePanel(target);
    const options = normaliseConfig(config);

    if (!panel) {
        return;
    }

    panel.hidden = true;
    panel.classList.remove(CLASSES.panelOpen);
    panel.setAttribute('aria-hidden', 'true');

    setControlState(panel, false);

    if (getPersistedPanelId(options) === panel.id) {
        persistPanelId(options, null);
    }

    if (!document.querySelector(`${SELECTORS.panel}.${CLASSES.panelOpen}`)) {
        document.body.classList.remove(CLASSES.bodyHasOpenPanel);
    }

    panel.dispatchEvent(new CustomEvent('format_uckk:archivepanelclosed', {
        bubbles: true,
        detail: {
            panel,
            panelid: panel.id,
        },
    }));
};

/**
 * Close every archive panel except an optional panel.
 *
 * @param {HTMLElement|null} except Panel to keep open.
 * @param {Object} config Archive panel config.
 */
const closeOtherPanels = (except, config) => {
    document.querySelectorAll(SELECTORS.panel).forEach(panel => {
        if (panel !== except) {
            closePanel(panel, config);
        }
    });
};

/**
 * Focus the most appropriate element in a panel.
 *
 * @param {HTMLElement} panel Archive panel.
 * @param {Object} config Archive panel config.
 */
const focusPanel = (panel, config) => {
    if (!config.focusOnOpen) {
        return;
    }

    const focusSelector = panel.getAttribute('data-focus-on-open');
    const focusTarget = focusSelector ? panel.querySelector(focusSelector) : null;

    if (focusTarget instanceof HTMLElement) {
        focusTarget.focus();
        return;
    }

    panel.focus();
};

/**
 * Open a panel.
 *
 * @param {String|HTMLElement} target Panel target.
 * @param {Object} config Archive panel config.
 */
export const openPanel = (target, config = {}) => {
    const panel = resolvePanel(target);
    const options = normaliseConfig(config);

    if (!panel) {
        return;
    }

    preparePanelAccessibility(panel);

    if (options.closeOthers) {
        closeOtherPanels(panel, options);
    }

    panel.hidden = false;
    panel.classList.add(CLASSES.panelOpen);
    panel.setAttribute('aria-hidden', 'false');

    setControlState(panel, true);
    document.body.classList.add(CLASSES.bodyHasOpenPanel);

    persistPanelId(options, panel.id);
    focusPanel(panel, options);

    panel.dispatchEvent(new CustomEvent('format_uckk:archivepanelopened', {
        bubbles: true,
        detail: {
            panel,
            panelid: panel.id,
        },
    }));
};

/**
 * Toggle a panel.
 *
 * @param {String|HTMLElement} target Panel target.
 * @param {Object} config Archive panel config.
 */
export const togglePanel = (target, config = {}) => {
    const panel = resolvePanel(target);

    if (!panel) {
        return;
    }

    if (isOpen(panel)) {
        closePanel(panel, config);
    } else {
        openPanel(panel, config);
    }
};

/**
 * Bind click handlers for open/close/toggle controls.
 *
 * @param {HTMLElement|Document} root Root element.
 * @param {Object} config Archive panel config.
 */
const bindControls = (root, config) => {
    root.addEventListener('click', event => {
        const toggle = event.target.closest(SELECTORS.toggle);
        const open = event.target.closest(SELECTORS.open);
        const close = event.target.closest(SELECTORS.close);

        if (!toggle && !open && !close) {
            return;
        }

        const control = toggle || open || close;
        const panel = resolvePanel(control);

        if (!panel) {
            return;
        }

        event.preventDefault();

        if (toggle) {
            togglePanel(panel, config);
            return;
        }

        if (open) {
            openPanel(panel, config);
            return;
        }

        closePanel(panel, config);
    });
};

/**
 * Bind keyboard shortcuts.
 *
 * @param {HTMLElement|Document} root Root element.
 * @param {Object} config Archive panel config.
 */
const bindKeyboard = (root, config) => {
    if (!config.closeOnEscape) {
        return;
    }

    root.addEventListener('keydown', event => {
        if (event.key !== 'Escape') {
            return;
        }

        const activePanel = event.target.closest(SELECTORS.panel);

        if (!activePanel || !isOpen(activePanel)) {
            return;
        }

        event.preventDefault();
        closePanel(activePanel, config);

        const toggles = getPanelToggles(activePanel);
        const firstToggle = toggles.find(toggle => toggle.matches(SELECTORS.toggle));

        if (firstToggle) {
            firstToggle.focus();
        }
    });
};

/**
 * Prepare all archive panels in the document.
 *
 * @param {HTMLElement|Document} root Root element.
 */
const preparePanels = root => {
    root.querySelectorAll(SELECTORS.panel).forEach(panel => {
        preparePanelAccessibility(panel);

        const isInitiallyOpen = panel.classList.contains(CLASSES.panelOpen) || panel.getAttribute('data-open') === 'true';

        panel.hidden = !isInitiallyOpen;
        panel.setAttribute('aria-hidden', isInitiallyOpen ? 'false' : 'true');
        setControlState(panel, isInitiallyOpen);

        if (isInitiallyOpen) {
            document.body.classList.add(CLASSES.bodyHasOpenPanel);
        }
    });
};

/**
 * Restore a persisted archive panel state.
 *
 * @param {Object} config Archive panel config.
 */
const restorePersistedState = config => {
    const panelId = getPersistedPanelId(config);

    if (!panelId) {
        return;
    }

    const panel = resolvePanel(panelId);

    if (panel) {
        openPanel(panel, Object.assign({}, config, {
            focusOnOpen: false,
        }));
    }
};

/**
 * Initialise archive panel behaviour.
 *
 * @param {Object} config Configuration.
 */
export const init = (config = {}) => {
    const options = normaliseConfig(config);
    const root = document;

    preparePanels(root);
    bindControls(root, options);
    bindKeyboard(root, options);
    restorePersistedState(options);

    document.dispatchEvent(new CustomEvent('format_uckk:archivepanelready', {
        bubbles: true,
        detail: {
            courseid: options.courseid,
        },
    }));
};