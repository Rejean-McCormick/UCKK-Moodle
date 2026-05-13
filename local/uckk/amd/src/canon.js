/**
 * Canon panel interactions for local_uckk.
 *
 * This module is intentionally UI-only:
 * - expands and collapses canon panels;
 * - refreshes canon panel markup through a declared Moodle external function;
 * - never decides permissions, validation, provenance, or authority.
 *
 * @module     local_uckk/canon
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'local_uckk';
const DEFAULT_REFRESH_METHOD = 'local_uckk_get_canon_panel';

const SELECTORS = {
    root: '[data-region="local-uckk-canon"]',
    panel: '[data-region="local-uckk-canon-panel"]',
    panelBody: '[data-region="local-uckk-canon-panel-body"]',
    panelStatus: '[data-region="local-uckk-canon-panel-status"]',
    toggle: '[data-action="local-uckk-toggle-canon-panel"]',
    refresh: '[data-action="local-uckk-refresh-canon-panel"]',
};

const CLASSES = {
    expanded: 'is-expanded',
    collapsed: 'is-collapsed',
    loading: 'is-loading',
    loaded: 'is-loaded',
    error: 'has-error',
};

const ATTRIBUTES = {
    initialised: 'data-local-uckk-canon-initialised',
    panelId: 'data-canon-id',
    contextId: 'data-context-id',
    refreshMethod: 'data-refresh-method',
    refreshTemplate: 'data-refresh-template',
};

/**
 * Find the closest canon panel for an element.
 *
 * @param {HTMLElement} element Source element.
 * @returns {HTMLElement|null}
 */
const getPanelFromElement = element => {
    if (!element) {
        return null;
    }

    return element.closest(SELECTORS.panel);
};

/**
 * Return a panel body element.
 *
 * @param {HTMLElement} panel Canon panel.
 * @returns {HTMLElement|null}
 */
const getPanelBody = panel => panel.querySelector(SELECTORS.panelBody);

/**
 * Return a panel status element.
 *
 * @param {HTMLElement} panel Canon panel.
 * @returns {HTMLElement|null}
 */
const getPanelStatus = panel => panel.querySelector(SELECTORS.panelStatus);

/**
 * Set an accessible status message inside a panel.
 *
 * @param {HTMLElement} panel Canon panel.
 * @param {String} message Message text.
 */
const setPanelStatus = (panel, message) => {
    const status = getPanelStatus(panel);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Resolve the controlled panel body id for a toggle button.
 *
 * @param {HTMLElement} toggle Toggle button/link.
 * @param {HTMLElement} panel Canon panel.
 * @returns {String}
 */
const getControlledId = (toggle, panel) => {
    const explicit = toggle.getAttribute('aria-controls');

    if (explicit) {
        return explicit;
    }

    const body = getPanelBody(panel);

    if (body && body.id) {
        return body.id;
    }

    return '';
};

/**
 * Set the visual and accessibility state of one panel.
 *
 * @param {HTMLElement} panel Canon panel.
 * @param {Boolean} expanded Whether the panel is expanded.
 */
const setExpanded = (panel, expanded) => {
    const toggle = panel.querySelector(SELECTORS.toggle);
    const body = getPanelBody(panel);

    panel.classList.toggle(CLASSES.expanded, expanded);
    panel.classList.toggle(CLASSES.collapsed, !expanded);

    if (toggle) {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    if (body) {
        body.hidden = !expanded;
        body.setAttribute('aria-hidden', expanded ? 'false' : 'true');
    }
};

/**
 * Toggle a panel open or closed.
 *
 * @param {HTMLElement} panel Canon panel.
 */
const togglePanel = panel => {
    const toggle = panel.querySelector(SELECTORS.toggle);
    const expanded = toggle?.getAttribute('aria-expanded') === 'true';

    setExpanded(panel, !expanded);
};

/**
 * Collapse sibling panels when the root is configured as an accordion.
 *
 * @param {HTMLElement} root Root canon region.
 * @param {HTMLElement} activePanel Panel that should remain open.
 */
const collapseSiblingsIfNeeded = (root, activePanel) => {
    const isAccordion = root.dataset.accordion === 'true';

    if (!isAccordion) {
        return;
    }

    root.querySelectorAll(SELECTORS.panel).forEach(panel => {
        if (panel !== activePanel) {
            setExpanded(panel, false);
        }
    });
};

/**
 * Get the refresh method for a panel.
 *
 * @param {HTMLElement} panel Canon panel.
 * @param {Object} options Init options.
 * @returns {String}
 */
const getRefreshMethod = (panel, options = {}) => {
    return panel.getAttribute(ATTRIBUTES.refreshMethod)
        || options.refreshMethod
        || DEFAULT_REFRESH_METHOD;
};

/**
 * Build the request arguments for a canon refresh call.
 *
 * @param {HTMLElement} panel Canon panel.
 * @returns {Object}
 */
const getRefreshArgs = panel => {
    const canonId = Number(panel.getAttribute(ATTRIBUTES.panelId) || 0);
    const contextId = Number(panel.getAttribute(ATTRIBUTES.contextId) || 0);

    return {
        canonid: canonId,
        contextid: contextId,
    };
};

/**
 * Render refreshed canon content into a panel body.
 *
 * The external function may return either:
 * - {html: "..."} when the server has already rendered safe HTML; or
 * - {template: "local_uckk/canon_panel", context: {...}} for client rendering.
 *
 * @param {HTMLElement} panel Canon panel.
 * @param {Object} response Service response.
 * @returns {Promise<void>}
 */
const renderRefreshResponse = async(panel, response) => {
    const body = getPanelBody(panel);

    if (!body) {
        return;
    }

    if (response.template && response.context) {
        const rendered = await Templates.render(response.template, response.context);
        Templates.replaceNodeContents(body, rendered, '');
        return;
    }

    if (response.html) {
        Templates.replaceNodeContents(body, response.html, '');
    }
};

/**
 * Refresh one canon panel from a Moodle external function.
 *
 * @param {HTMLElement} panel Canon panel.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshPanel = async(panel, options = {}) => {
    const methodname = getRefreshMethod(panel, options);
    const args = getRefreshArgs(panel);

    if (!args.canonid || !args.contextid) {
        return;
    }

    panel.classList.add(CLASSES.loading);
    panel.classList.remove(CLASSES.error);

    const loadingString = await getString('canonrefreshing', COMPONENT);
    setPanelStatus(panel, loadingString);

    try {
        const response = await Ajax.call([{
            methodname,
            args,
        }])[0];

        await renderRefreshResponse(panel, response);

        panel.classList.add(CLASSES.loaded);
        const refreshedString = await getString('canonrefreshed', COMPONENT);
        setPanelStatus(panel, refreshedString);
    } catch (error) {
        panel.classList.add(CLASSES.error);

        const errorString = await getString('canonrefreshfailed', COMPONENT);
        setPanelStatus(panel, errorString);

        Notification.exception(error);
    } finally {
        panel.classList.remove(CLASSES.loading);
    }
};

/**
 * Handle click events inside a canon root.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Root canon region.
 * @param {Object} options Init options.
 */
const handleClick = (event, root, options = {}) => {
    const toggle = event.target.closest(SELECTORS.toggle);

    if (toggle) {
        event.preventDefault();

        const panel = getPanelFromElement(toggle);

        if (!panel) {
            return;
        }

        collapseSiblingsIfNeeded(root, panel);
        togglePanel(panel);
        return;
    }

    const refresh = event.target.closest(SELECTORS.refresh);

    if (refresh) {
        event.preventDefault();

        const panel = getPanelFromElement(refresh);

        if (panel) {
            refreshPanel(panel, options);
        }
    }
};

/**
 * Prepare one toggle button and its target panel body.
 *
 * @param {HTMLElement} panel Canon panel.
 */
const preparePanel = panel => {
    const toggle = panel.querySelector(SELECTORS.toggle);
    const body = getPanelBody(panel);

    if (!toggle || !body) {
        return;
    }

    const controlledId = getControlledId(toggle, panel);

    if (controlledId) {
        toggle.setAttribute('aria-controls', controlledId);
    }

    if (!toggle.hasAttribute('aria-expanded')) {
        const initiallyExpanded = panel.dataset.expanded === 'true';
        toggle.setAttribute('aria-expanded', initiallyExpanded ? 'true' : 'false');
    }

    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    setExpanded(panel, expanded);
};

/**
 * Initialise one canon root region.
 *
 * @param {HTMLElement} root Root canon region.
 * @param {Object} options Init options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');

    root.querySelectorAll(SELECTORS.panel).forEach(preparePanel);

    root.addEventListener('click', event => {
        handleClick(event, root, options);
    });

    if (options.refreshOnInit === true) {
        root.querySelectorAll(SELECTORS.panel).forEach(panel => {
            refreshPanel(panel, options);
        });
    }
};

/**
 * Initialise UCKK canon interactions.
 *
 * Recommended Mustache usage:
 *
 * {{#js}}
 * require(['local_uckk/canon'], function(Canon) {
 *     Canon.init('{{uniqid}}');
 * });
 * {{/js}}
 *
 * @param {String|null} rootId Optional root element id.
 * @param {Object} options Optional behaviour flags.
 */
export const init = (rootId = null, options = {}) => {
    const roots = rootId
        ? [document.getElementById(rootId)].filter(Boolean)
        : Array.from(document.querySelectorAll(SELECTORS.root));

    roots.forEach(root => initialiseRoot(root, options));
};

/**
 * Public helper for refreshing all canon panels in a root.
 *
 * @param {String|null} rootId Optional root element id.
 * @param {Object} options Optional behaviour flags.
 * @returns {Promise<void[]>}
 */
export const refreshAll = (rootId = null, options = {}) => {
    const root = rootId ? document.getElementById(rootId) : document.querySelector(SELECTORS.root);

    if (!root) {
        return Promise.resolve([]);
    }

    const panels = Array.from(root.querySelectorAll(SELECTORS.panel));

    return Promise.all(panels.map(panel => refreshPanel(panel, options)));
};