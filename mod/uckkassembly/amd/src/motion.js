/**
 * Motion interactions for mod_uckkassembly.
 *
 * This module is intentionally UI-only:
 * - expands and collapses motion cards;
 * - tracks form changes;
 * - autosaves motion drafts through declared Ajax services;
 * - submits motions through declared Ajax services;
 * - refreshes the motion list/panel;
 * - opens motion-related UI affordances.
 *
 * It must not decide permissions, accept motions, reject motions, publish
 * decisions, record votes, archive records, resolve objections, or alter
 * integrity state locally.
 *
 * @module     mod_uckkassembly/motion
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkassembly';

const DEFAULT_METHODS = {
    saveDraft: 'mod_uckkassembly_save_motion_draft',
    submitMotion: 'mod_uckkassembly_submit_motion',
    withdrawMotion: 'mod_uckkassembly_withdraw_motion',
    refreshMotions: 'mod_uckkassembly_get_motion_panel',
};

const SELECTORS = {
    root: '[data-region="uckkassembly-motion"]',
    form: '[data-region="uckkassembly-motion-form"]',
    panel: '[data-region="uckkassembly-motion-panel"]',
    list: '[data-region="uckkassembly-motion-list"]',
    status: '[data-region="uckkassembly-motion-status"]',
    card: '[data-region="uckkassembly-motion-card"]',
    cardBody: '[data-region="uckkassembly-motion-card-body"]',
    characterCount: '[data-region="uckkassembly-character-count"]',

    fieldTitle: '[data-field="motion-title"]',
    fieldBody: '[data-field="motion-body"]',
    fieldRationale: '[data-field="motion-rationale"]',
    fieldSummary: '[data-field="motion-summary"]',
    fieldMotionType: '[data-field="motion-type"]',
    fieldVisibility: '[data-field="motion-visibility"]',
    fieldEvidence: '[data-field="motion-evidence"]',
    fieldArchiveRecommendation: '[data-field="motion-archive-recommendation"]',

    actionToggle: '[data-action="uckkassembly-toggle-motion"]',
    actionSaveDraft: '[data-action="uckkassembly-save-motion-draft"]',
    actionSubmit: '[data-action="uckkassembly-submit-motion"]',
    actionWithdraw: '[data-action="uckkassembly-withdraw-motion"]',
    actionRefresh: '[data-action="uckkassembly-refresh-motions"]',
    actionClearDraft: '[data-action="uckkassembly-clear-motion-draft"]',
    actionShowForm: '[data-action="uckkassembly-show-motion-form"]',
    actionHideForm: '[data-action="uckkassembly-hide-motion-form"]',
};

const CLASSES = {
    expanded: 'is-expanded',
    collapsed: 'is-collapsed',
    loading: 'is-loading',
    dirty: 'is-dirty',
    saved: 'is-saved',
    error: 'has-error',
    hidden: 'd-none',
};

const ATTRIBUTES = {
    initialised: 'data-uckkassembly-motion-initialised',
};

let autosaveTimers = new WeakMap();

/**
 * Get an integer value from root dataset.
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
 * Get service method names.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Object}
 */
const getMethods = (root, options = {}) => {
    return {
        saveDraft: root.dataset.saveMethod || options.saveMethod || DEFAULT_METHODS.saveDraft,
        submitMotion: root.dataset.submitMethod || options.submitMethod || DEFAULT_METHODS.submitMotion,
        withdrawMotion: root.dataset.withdrawMethod || options.withdrawMethod || DEFAULT_METHODS.withdrawMotion,
        refreshMotions: root.dataset.refreshMethod || options.refreshMethod || DEFAULT_METHODS.refreshMotions,
    };
};

/**
 * Base Ajax identifiers.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const getBaseArgs = root => {
    return {
        cmid: getNumberData(root, 'cmid'),
        assemblyid: getNumberData(root, 'assemblyid'),
        motionid: getNumberData(root, 'motionid'),
        sesskey: root.querySelector(SELECTORS.form)?.dataset?.sesskey || M.cfg.sesskey,
    };
};

/**
 * Check whether base identifiers are valid.
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
const getFieldValue = (root, selector) => {
    const field = root.querySelector(selector);

    return field ? field.value : '';
};

/**
 * Collect motion form data.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const collectMotionData = root => {
    return {
        ...getBaseArgs(root),
        title: getFieldValue(root, SELECTORS.fieldTitle),
        body: getFieldValue(root, SELECTORS.fieldBody),
        rationale: getFieldValue(root, SELECTORS.fieldRationale),
        summary: getFieldValue(root, SELECTORS.fieldSummary),
        motiontype: getFieldValue(root, SELECTORS.fieldMotionType),
        visibility: getFieldValue(root, SELECTORS.fieldVisibility),
        evidence: getFieldValue(root, SELECTORS.fieldEvidence),
        archiverecommendation: getFieldValue(root, SELECTORS.fieldArchiveRecommendation),
    };
};

/**
 * Call one Moodle Ajax service.
 *
 * @param {String} methodname Method name.
 * @param {Object} args Method args.
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
 * Validate minimum motion fields before submit.
 *
 * This is a client-side hint only. Server-side validation remains authoritative.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Promise<Boolean>}
 */
const validateBeforeSubmit = async(root) => {
    const data = collectMotionData(root);

    if (!hasRequiredIdentifiers(data)) {
        await Notification.alert(
            await getString('motionerror', COMPONENT),
            await getString('motionmissingidentifiers', COMPONENT)
        );
        return false;
    }

    if (!data.title.trim()) {
        await Notification.alert(
            await getString('motionerror', COMPONENT),
            await getString('motionmissingtitle', COMPONENT)
        );
        return false;
    }

    if (!data.body.trim()) {
        await Notification.alert(
            await getString('motionerror', COMPONENT),
            await getString('motionmissingbody', COMPONENT)
        );
        return false;
    }

    if (!data.rationale.trim()) {
        await Notification.alert(
            await getString('motionerror', COMPONENT),
            await getString('motionmissingrationale', COMPONENT)
        );
        return false;
    }

    return true;
};

/**
 * Confirm final motion submission.
 *
 * @returns {Promise<Boolean>}
 */
const confirmSubmit = async() => {
    const title = await getString('confirmmotionsubmit', COMPONENT);
    const body = await getString('confirmmotionsubmitbody', COMPONENT);
    const submit = await getString('submit', 'moodle');
    const cancel = await getString('cancel', 'moodle');

    return new Promise(resolve => {
        Notification.confirm(
            title,
            body,
            submit,
            cancel,
            () => resolve(true),
            () => resolve(false)
        );
    });
};

/**
 * Confirm motion withdrawal.
 *
 * @returns {Promise<Boolean>}
 */
const confirmWithdraw = async() => {
    const title = await getString('confirmmotionwithdraw', COMPONENT);
    const body = await getString('confirmmotionwithdrawbody', COMPONENT);
    const confirm = await getString('withdrawmotion', COMPONENT);
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
 * Save motion draft.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const saveDraft = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = collectMotionData(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('motionsaving', COMPONENT));

        const response = await callService(methods.saveDraft, args);

        if (response?.motionid) {
            root.dataset.motionid = String(response.motionid);
        }

        markSaved(root);
        setStatus(root, await getString('motionsaved', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('motionsavefailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Submit motion to server-side workflow.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const submitMotion = async(root, options = {}) => {
    const valid = await validateBeforeSubmit(root);

    if (!valid) {
        return;
    }

    const confirmed = await confirmSubmit();

    if (!confirmed) {
        return;
    }

    const methods = getMethods(root, options);
    const args = collectMotionData(root);

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('motionsubmitting', COMPONENT));

        const response = await callService(methods.submitMotion, args);

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        } else {
            await refreshMotions(root, options);
        }

        markSaved(root);
        setStatus(root, await getString('motionsubmitted', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('motionsubmitfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Withdraw a motion.
 *
 * @param {HTMLElement} root Root element.
 * @param {Number} motionid Motion id.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const withdrawMotion = async(root, motionid, options = {}) => {
    if (!motionid) {
        return;
    }

    const confirmed = await confirmWithdraw();

    if (!confirmed) {
        return;
    }

    const methods = getMethods(root, options);
    const args = {
        ...getBaseArgs(root),
        motionid,
    };

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('motionwithdrawing', COMPONENT));

        const response = await callService(methods.withdrawMotion, args);

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        } else {
            await refreshMotions(root, options);
        }

        setStatus(root, await getString('motionwithdrawn', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('motionwithdrawfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Refresh the motion panel.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshMotions = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = getBaseArgs(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('motionrefreshing', COMPONENT));

        const response = await callService(methods.refreshMotions, args);

        if (response?.template && response?.context) {
            const html = await Templates.render(response.template, response.context);
            replacePanel(root, html, '');
        } else if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        setStatus(root, await getString('motionrefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('motionrefreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Replace motion panel contents.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} html HTML.
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
 * Toggle one motion card.
 *
 * @param {HTMLElement} card Motion card.
 */
const toggleCard = card => {
    const body = card.querySelector(SELECTORS.cardBody);
    const trigger = card.querySelector(SELECTORS.actionToggle);
    const expanded = trigger?.getAttribute('aria-expanded') === 'true';

    card.classList.toggle(CLASSES.expanded, !expanded);
    card.classList.toggle(CLASSES.collapsed, expanded);

    if (trigger) {
        trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    }

    if (body) {
        body.hidden = expanded;
        body.setAttribute('aria-hidden', expanded ? 'true' : 'false');
    }
};

/**
 * Prepare existing motion cards.
 *
 * @param {HTMLElement} root Root element.
 */
const prepareCards = root => {
    root.querySelectorAll(SELECTORS.card).forEach(card => {
        const trigger = card.querySelector(SELECTORS.actionToggle);
        const body = card.querySelector(SELECTORS.cardBody);

        if (!trigger || !body) {
            return;
        }

        if (!trigger.hasAttribute('aria-expanded')) {
            trigger.setAttribute('aria-expanded', 'false');
        }

        const expanded = trigger.getAttribute('aria-expanded') === 'true';

        card.classList.toggle(CLASSES.expanded, expanded);
        card.classList.toggle(CLASSES.collapsed, !expanded);
        body.hidden = !expanded;
        body.setAttribute('aria-hidden', expanded ? 'false' : 'true');
    });
};

/**
 * Update character counters.
 *
 * @param {HTMLElement} root Root element.
 */
const updateCharacterCounts = root => {
    root.querySelectorAll(SELECTORS.characterCount).forEach(counter => {
        const targetSelector = counter.dataset.target || '';
        const target = targetSelector ? root.querySelector(targetSelector) : null;

        if (!target) {
            return;
        }

        const count = target.value.length;
        const max = Number(target.getAttribute('maxlength') || counter.dataset.max || 0);

        counter.textContent = max > 0 ? `${count} / ${max}` : String(count);
    });
};

/**
 * Schedule draft autosave.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const scheduleAutosave = (root, options = {}) => {
    if (options.autosave === false || root.dataset.autosave === 'false') {
        return;
    }

    const delay = Number(options.autosaveDelay || root.dataset.autosaveDelay || 2500);
    const previous = autosaveTimers.get(root);

    if (previous) {
        window.clearTimeout(previous);
    }

    const timer = window.setTimeout(() => {
        saveDraft(root, options);
    }, Number.isFinite(delay) ? delay : 2500);

    autosaveTimers.set(root, timer);
};

/**
 * Show or hide the motion form.
 *
 * @param {HTMLElement} root Root element.
 * @param {Boolean} show Whether to show the form.
 */
const setFormVisible = (root, show) => {
    const form = root.querySelector(SELECTORS.form);

    if (!form) {
        return;
    }

    form.classList.toggle(CLASSES.hidden, !show);
    form.hidden = !show;

    if (show) {
        const first = form.querySelector('input, textarea, select, button');
        first?.focus();
    }
};

/**
 * Clear local form values.
 *
 * This only clears the browser form. Persisted drafts are handled server-side.
 *
 * @param {HTMLElement} root Root element.
 */
const clearDraftForm = root => {
    const form = root.querySelector(SELECTORS.form);

    if (!form) {
        return;
    }

    form.querySelectorAll('input, textarea, select').forEach(field => {
        if (field.type === 'hidden') {
            return;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = false;
            return;
        }

        field.value = '';
    });

    root.dataset.motionid = '0';
    updateCharacterCounts(root);
    markDirty(root);
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
    updateCharacterCounts(root);
    scheduleAutosave(root, options);
};

/**
 * Handle click events.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const handleClick = (event, root, options = {}) => {
    const toggle = event.target.closest(SELECTORS.actionToggle);

    if (toggle) {
        event.preventDefault();

        const card = toggle.closest(SELECTORS.card);

        if (card) {
            toggleCard(card);
        }

        return;
    }

    const saveButton = event.target.closest(SELECTORS.actionSaveDraft);

    if (saveButton) {
        event.preventDefault();
        saveDraft(root, options);
        return;
    }

    const submitButton = event.target.closest(SELECTORS.actionSubmit);

    if (submitButton) {
        event.preventDefault();
        submitMotion(root, options);
        return;
    }

    const withdrawButton = event.target.closest(SELECTORS.actionWithdraw);

    if (withdrawButton) {
        event.preventDefault();

        const motionid = Number(withdrawButton.dataset.motionid || 0);
        withdrawMotion(root, motionid, options);
        return;
    }

    const refreshButton = event.target.closest(SELECTORS.actionRefresh);

    if (refreshButton) {
        event.preventDefault();
        refreshMotions(root, options);
        return;
    }

    const clearButton = event.target.closest(SELECTORS.actionClearDraft);

    if (clearButton) {
        event.preventDefault();
        clearDraftForm(root);
        return;
    }

    const showFormButton = event.target.closest(SELECTORS.actionShowForm);

    if (showFormButton) {
        event.preventDefault();
        setFormVisible(root, true);
        return;
    }

    const hideFormButton = event.target.closest(SELECTORS.actionHideForm);

    if (hideFormButton) {
        event.preventDefault();
        setFormVisible(root, false);
    }
};

/**
 * Initialise one root.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');

    prepareCards(root);
    updateCharacterCounts(root);

    root.addEventListener('input', event => handleChange(event, root, options));
    root.addEventListener('change', event => handleChange(event, root, options));
    root.addEventListener('click', event => handleClick(event, root, options));

    if (options.refreshOnInit === true || root.dataset.refreshOnInit === 'true') {
        refreshMotions(root, options);
    }
};

/**
 * Initialise Assembly motion UI.
 *
 * Recommended PHP:
 * $PAGE->requires->js_call_amd('mod_uckkassembly/motion', 'init', [$uniqid]);
 *
 * Recommended Mustache:
 * {{#js}}
 * require(['mod_uckkassembly/motion'], function(Motion) {
 *     Motion.init('{{uniqid}}');
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
 * Public helper to save the active draft.
 *
 * @param {String} rootId Root id.
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
 * Public helper to refresh the motion panel.
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

    return refreshMotions(root, options);
};
