/**
 * Minutes interactions for mod_uckkassembly.
 *
 * This module is intentionally UI-only:
 * - tracks dirty state;
 * - saves minutes drafts through declared Ajax services;
 * - confirms publication requests;
 * - refreshes the minutes panel;
 * - toggles minutes sections.
 *
 * It must not decide legitimacy, publish authoritatively, close contestations,
 * validate archive records, or alter integrity state locally.
 *
 * @module     mod_uckkassembly/minutes
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkassembly';

const DEFAULT_METHODS = {
    saveDraft: 'mod_uckkassembly_save_minutes_draft',
    publish: 'mod_uckkassembly_publish_minutes',
    refresh: 'mod_uckkassembly_get_minutes_panel',
};

const SELECTORS = {
    root: '[data-region="uckkassembly-minutes"]',
    form: '[data-region="uckkassembly-minutes-form"]',
    panel: '[data-region="uckkassembly-minutes-panel"]',
    status: '[data-region="uckkassembly-minutes-status"]',

    titleInput: '[data-field="minutes-title"]',
    summaryInput: '[data-field="minutes-summary"]',
    bodyInput: '[data-field="minutes-body"]',
    decisionsInput: '[data-field="minutes-decisions"]',
    minorityReportInput: '[data-field="minutes-minority-report"]',
    archiveNotesInput: '[data-field="minutes-archive-notes"]',

    section: '[data-region="uckkassembly-minutes-section"]',
    sectionBody: '[data-region="uckkassembly-minutes-section-body"]',

    saveDraft: '[data-action="uckkassembly-save-minutes-draft"]',
    publish: '[data-action="uckkassembly-publish-minutes"]',
    refresh: '[data-action="uckkassembly-refresh-minutes"]',
    toggleSection: '[data-action="uckkassembly-toggle-minutes-section"]',
};

const CLASSES = {
    loading: 'is-loading',
    dirty: 'is-dirty',
    saved: 'is-saved',
    error: 'has-error',
    expanded: 'is-expanded',
    collapsed: 'is-collapsed',
};

const ATTRIBUTES = {
    initialised: 'data-uckkassembly-minutes-initialised',
};

let autosaveTimers = new WeakMap();

/**
 * Get numeric data from a root element.
 *
 * @param {HTMLElement} element Source element.
 * @param {String} key Dataset key.
 * @param {Number} fallback Fallback value.
 * @returns {Number}
 */
const getNumberData = (element, key, fallback = 0) => {
    const value = Number(element?.dataset?.[key] ?? fallback);
    return Number.isFinite(value) ? value : fallback;
};

/**
 * Return configured service names.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Object}
 */
const getMethods = (root, options = {}) => {
    return {
        saveDraft: root.dataset.saveMethod || options.saveMethod || DEFAULT_METHODS.saveDraft,
        publish: root.dataset.publishMethod || options.publishMethod || DEFAULT_METHODS.publish,
        refresh: root.dataset.refreshMethod || options.refreshMethod || DEFAULT_METHODS.refresh,
    };
};

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
 * Build base Ajax identifiers.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const getBaseArgs = root => {
    return {
        cmid: getNumberData(root, 'cmid'),
        assemblyid: getNumberData(root, 'assemblyid'),
        minutesid: getNumberData(root, 'minutesid'),
        sesskey: M.cfg.sesskey,
    };
};

/**
 * Check required identifiers.
 *
 * @param {Object} args Ajax args.
 * @returns {Boolean}
 */
const hasRequiredIdentifiers = args => Boolean(args.cmid && args.assemblyid);

/**
 * Get a field value.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} selector Field selector.
 * @returns {String}
 */
const getTextValue = (root, selector) => {
    const field = root.querySelector(selector);
    return field ? field.value : '';
};

/**
 * Collect minutes payload from the DOM.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const collectMinutesData = root => {
    return {
        ...getBaseArgs(root),
        title: getTextValue(root, SELECTORS.titleInput),
        summary: getTextValue(root, SELECTORS.summaryInput),
        body: getTextValue(root, SELECTORS.bodyInput),
        decisions: getTextValue(root, SELECTORS.decisionsInput),
        minorityreport: getTextValue(root, SELECTORS.minorityReportInput),
        archivenotes: getTextValue(root, SELECTORS.archiveNotesInput),
    };
};

/**
 * Call a Moodle Ajax service.
 *
 * @param {String} methodname Method name.
 * @param {Object} args Ajax args.
 * @returns {Promise<Object>}
 */
const callService = (methodname, args) => {
    return Ajax.call([{
        methodname,
        args,
    }])[0];
};

/**
 * Mark root as dirty.
 *
 * @param {HTMLElement} root Root element.
 */
const markDirty = root => {
    root.classList.add(CLASSES.dirty);
    root.classList.remove(CLASSES.saved);
};

/**
 * Mark root as saved.
 *
 * @param {HTMLElement} root Root element.
 */
const markSaved = root => {
    root.classList.remove(CLASSES.dirty);
    root.classList.add(CLASSES.saved);
};

/**
 * Replace the minutes panel contents.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} html Rendered HTML.
 * @param {String} js Template JS.
 */
const replacePanel = (root, html, js = '') => {
    const panel = root.querySelector(SELECTORS.panel);

    if (!panel) {
        return;
    }

    Templates.replaceNodeContents(panel, html, js);

    if (js) {
        Templates.runTemplateJS(js);
    }
};

/**
 * Save minutes as draft.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const saveDraft = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = collectMinutesData(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('minutessaving', COMPONENT));

        const response = await callService(methods.saveDraft, args);

        if (response?.minutesid) {
            root.dataset.minutesid = String(response.minutesid);
        }

        if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        markSaved(root);
        setStatus(root, await getString('minutessaved', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('minutessavefailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Validate client-side requirements before publish.
 *
 * Server validation remains authoritative.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Promise<Boolean>}
 */
const validateBeforePublish = async(root) => {
    const data = collectMinutesData(root);

    if (!hasRequiredIdentifiers(data)) {
        await Notification.alert(
            await getString('minuteserror', COMPONENT),
            await getString('minutesmissingidentifiers', COMPONENT)
        );
        return false;
    }

    if (!data.title.trim()) {
        await Notification.alert(
            await getString('minuteserror', COMPONENT),
            await getString('minutesmissingtitle', COMPONENT)
        );
        return false;
    }

    if (!data.body.trim()) {
        await Notification.alert(
            await getString('minuteserror', COMPONENT),
            await getString('minutesmissingbody', COMPONENT)
        );
        return false;
    }

    return true;
};

/**
 * Confirm publication.
 *
 * @returns {Promise<Boolean>}
 */
const confirmPublish = async() => {
    const title = await getString('confirmpublishminutes', COMPONENT);
    const body = await getString('confirmpublishminutesbody', COMPONENT);
    const confirm = await getString('publishminutes', COMPONENT);
    const cancel = await getString('cancel', 'moodle');

    return new Promise(resolve => {
        Notification.confirm(
            title,
            body,
            confirm,
            cancel,
            () => resolve(true),
            () => resolve(false)
        );
    });
};

/**
 * Publish minutes through server-side workflow.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const publishMinutes = async(root, options = {}) => {
    const valid = await validateBeforePublish(root);

    if (!valid) {
        return;
    }

    const confirmed = await confirmPublish();

    if (!confirmed) {
        return;
    }

    const methods = getMethods(root, options);
    const args = collectMinutesData(root);

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('minutespublishing', COMPONENT));

        const response = await callService(methods.publish, args);

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        markSaved(root);
        setStatus(root, await getString('minutespublished', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('minutespublishfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Refresh minutes panel.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshMinutes = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = getBaseArgs(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('minutesrefreshing', COMPONENT));

        const response = await callService(methods.refresh, args);

        if (response?.template && response?.context) {
            const html = await Templates.render(response.template, response.context);
            replacePanel(root, html, '');
        } else if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        setStatus(root, await getString('minutesrefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('minutesrefreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Schedule autosave.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const scheduleAutosave = (root, options = {}) => {
    if (options.autosave === false || root.dataset.autosave === 'false') {
        return;
    }

    const delay = Number(options.autosaveDelay || root.dataset.autosaveDelay || 3000);
    const previous = autosaveTimers.get(root);

    if (previous) {
        window.clearTimeout(previous);
    }

    const timer = window.setTimeout(() => {
        saveDraft(root, options);
    }, Number.isFinite(delay) ? delay : 3000);

    autosaveTimers.set(root, timer);
};

/**
 * Toggle one minutes section.
 *
 * @param {HTMLElement} trigger Toggle trigger.
 */
const toggleSection = trigger => {
    const section = trigger.closest(SELECTORS.section);

    if (!section) {
        return;
    }

    const body = section.querySelector(SELECTORS.sectionBody);
    const expanded = trigger.getAttribute('aria-expanded') === 'true';
    const nextExpanded = !expanded;

    section.classList.toggle(CLASSES.expanded, nextExpanded);
    section.classList.toggle(CLASSES.collapsed, !nextExpanded);
    trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');

    if (body) {
        body.hidden = !nextExpanded;
        body.setAttribute('aria-hidden', nextExpanded ? 'false' : 'true');
    }
};

/**
 * Handle form changes.
 *
 * @param {Event} event Input/change event.
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const handleChange = (event, root, options = {}) => {
    if (!event.target.closest(SELECTORS.form)) {
        return;
    }

    markDirty(root);
    scheduleAutosave(root, options);
};

/**
 * Handle click actions.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const handleClick = (event, root, options = {}) => {
    const saveButton = event.target.closest(SELECTORS.saveDraft);

    if (saveButton) {
        event.preventDefault();
        saveDraft(root, options);
        return;
    }

    const publishButton = event.target.closest(SELECTORS.publish);

    if (publishButton) {
        event.preventDefault();
        publishMinutes(root, options);
        return;
    }

    const refreshButton = event.target.closest(SELECTORS.refresh);

    if (refreshButton) {
        event.preventDefault();
        refreshMinutes(root, options);
        return;
    }

    const toggleButton = event.target.closest(SELECTORS.toggleSection);

    if (toggleButton) {
        event.preventDefault();
        toggleSection(toggleButton);
    }
};

/**
 * Prepare existing collapsible minutes sections.
 *
 * @param {HTMLElement} root Root element.
 */
const prepareSections = root => {
    root.querySelectorAll(SELECTORS.section).forEach(section => {
        const trigger = section.querySelector(SELECTORS.toggleSection);
        const body = section.querySelector(SELECTORS.sectionBody);

        if (!trigger || !body) {
            return;
        }

        if (!trigger.hasAttribute('aria-expanded')) {
            const expanded = section.dataset.expanded === 'true';
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        const expanded = trigger.getAttribute('aria-expanded') === 'true';

        section.classList.toggle(CLASSES.expanded, expanded);
        section.classList.toggle(CLASSES.collapsed, !expanded);
        body.hidden = !expanded;
        body.setAttribute('aria-hidden', expanded ? 'false' : 'true');
    });
};

/**
 * Initialise one minutes root.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');

    prepareSections(root);

    root.addEventListener('input', event => handleChange(event, root, options));
    root.addEventListener('change', event => handleChange(event, root, options));
    root.addEventListener('click', event => handleClick(event, root, options));
};

/**
 * Initialise assembly minutes UI.
 *
 * Recommended PHP:
 * $PAGE->requires->js_call_amd('mod_uckkassembly/minutes', 'init', [$uniqid]);
 *
 * Recommended Mustache:
 * {{#js}}
 * require(['mod_uckkassembly/minutes'], function(Minutes) {
 *     Minutes.init('{{uniqid}}');
 * });
 * {{/js}}
 *
 * @param {String|null} rootId Optional root element id.
 * @param {Object} options Optional configuration.
 */
export const init = (rootId = null, options = {}) => {
    const roots = rootId
        ? [document.getElementById(rootId)].filter(Boolean)
        : Array.from(document.querySelectorAll(SELECTORS.root));

    roots.forEach(root => initialiseRoot(root, options));
};

/**
 * Public helper to save the current minutes draft.
 *
 * @param {String} rootId Root element id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<void>}
 */
export const save = (rootId, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return saveDraft(root, options);
};

/**
 * Public helper to refresh the minutes panel.
 *
 * @param {String} rootId Root element id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<void>}
 */
export const refresh = (rootId, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return refreshMinutes(root, options);
};