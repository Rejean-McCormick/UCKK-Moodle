/**
 * Vote and readings interactions for mod_uckkassembly.
 *
 * This module is intentionally UI-only:
 * - handles vote option selection;
 * - collects rationale and reading metadata;
 * - updates local display summaries;
 * - saves vote drafts through declared Ajax services;
 * - submits votes/readings through declared Ajax services;
 * - refreshes vote/readings panels.
 *
 * It must not decide outcomes, publish decisions, resolve contestations,
 * validate integrity, archive records, or hide authority in one opaque score.
 *
 * @module     mod_uckkassembly/vote
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkassembly';

const DEFAULT_METHODS = {
    saveDraft: 'mod_uckkassembly_save_vote_draft',
    submitVote: 'mod_uckkassembly_submit_vote',
    refreshPanel: 'mod_uckkassembly_get_vote_panel',
};

const SELECTORS = {
    root: '[data-region="uckkassembly-vote"]',
    form: '[data-region="uckkassembly-vote-form"]',
    panel: '[data-region="uckkassembly-vote-panel"]',
    status: '[data-region="uckkassembly-vote-status"]',

    voteOption: '[data-field="vote-option"]',
    readingOption: '[data-field="reading-option"]',
    rationale: '[data-field="vote-rationale"]',
    evidenceNote: '[data-field="evidence-note"]',
    minorityNote: '[data-field="minority-note"]',
    integrityNote: '[data-field="integrity-note"]',
    uncertaintyNote: '[data-field="uncertainty-note"]',

    selectedLabel: '[data-region="uckkassembly-selected-vote"]',
    readingSummary: '[data-region="uckkassembly-reading-summary"]',
    draftIndicator: '[data-region="uckkassembly-vote-draft-indicator"]',

    saveDraft: '[data-action="uckkassembly-save-vote-draft"]',
    submitVote: '[data-action="uckkassembly-submit-vote"]',
    refresh: '[data-action="uckkassembly-refresh-vote-panel"]',
    clearVote: '[data-action="uckkassembly-clear-vote"]',
    toggleReading: '[data-action="uckkassembly-toggle-reading"]',

    readingRegion: '[data-region="uckkassembly-reading"]',
};

const CLASSES = {
    loading: 'is-loading',
    dirty: 'is-dirty',
    saved: 'is-saved',
    error: 'has-error',
    selected: 'is-selected',
    hidden: 'd-none',
};

const ATTRIBUTES = {
    initialised: 'data-uckkassembly-vote-initialised',
};

let autosaveTimers = new WeakMap();

/**
 * Return number from element dataset.
 *
 * @param {HTMLElement} element Source element.
 * @param {String} name Dataset key.
 * @param {Number} fallback Fallback value.
 * @returns {Number}
 */
const getNumberData = (element, name, fallback = 0) => {
    const value = Number(element?.dataset?.[name] ?? fallback);

    return Number.isFinite(value) ? value : fallback;
};

/**
 * Set accessible status message.
 *
 * @param {HTMLElement} root Vote root.
 * @param {String} message Status message.
 */
const setStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Resolve configured Ajax method names.
 *
 * @param {HTMLElement} root Vote root.
 * @param {Object} options Optional init options.
 * @returns {Object}
 */
const getMethods = (root, options = {}) => {
    return {
        saveDraft: root.dataset.saveMethod || options.saveMethod || DEFAULT_METHODS.saveDraft,
        submitVote: root.dataset.submitMethod || options.submitMethod || DEFAULT_METHODS.submitVote,
        refreshPanel: root.dataset.refreshMethod || options.refreshMethod || DEFAULT_METHODS.refreshPanel,
    };
};

/**
 * Build base request identifiers.
 *
 * @param {HTMLElement} root Vote root.
 * @returns {Object}
 */
const getBaseArgs = root => {
    return {
        cmid: getNumberData(root, 'cmid'),
        assemblyid: getNumberData(root, 'assemblyid'),
        motionid: getNumberData(root, 'motionid'),
        voteid: getNumberData(root, 'voteid'),
    };
};

/**
 * Return true if required identifiers are present.
 *
 * @param {Object} args Request args.
 * @returns {Boolean}
 */
const hasRequiredIdentifiers = args => Boolean(args.cmid && args.assemblyid && args.motionid);

/**
 * Get selected vote option.
 *
 * @param {HTMLElement} root Vote root.
 * @returns {String}
 */
const getSelectedVote = root => {
    const selected = root.querySelector(`${SELECTORS.voteOption}:checked`);

    return selected ? selected.value : '';
};

/**
 * Get selected reading options.
 *
 * @param {HTMLElement} root Vote root.
 * @returns {Array}
 */
const getSelectedReadings = root => {
    return Array.from(root.querySelectorAll(`${SELECTORS.readingOption}:checked`)).map(field => ({
        key: field.value,
        label: field.dataset.label || '',
        weight: getNumberData(field, 'weight', 0),
    }));
};

/**
 * Get text value for one field.
 *
 * @param {HTMLElement} root Vote root.
 * @param {String} selector Field selector.
 * @returns {String}
 */
const getTextValue = (root, selector) => {
    const field = root.querySelector(selector);

    return field ? field.value : '';
};

/**
 * Collect vote payload from DOM.
 *
 * @param {HTMLElement} root Vote root.
 * @returns {Object}
 */
const collectVoteData = root => {
    const form = root.querySelector(SELECTORS.form);

    return {
        ...getBaseArgs(root),
        option: getSelectedVote(root),
        readings: getSelectedReadings(root),
        rationale: getTextValue(root, SELECTORS.rationale),
        evidencenote: getTextValue(root, SELECTORS.evidenceNote),
        minoritynote: getTextValue(root, SELECTORS.minorityNote),
        integritynote: getTextValue(root, SELECTORS.integrityNote),
        uncertaintynote: getTextValue(root, SELECTORS.uncertaintyNote),
        sesskey: form?.dataset?.sesskey || M.cfg.sesskey,
    };
};

/**
 * Call Moodle Ajax service.
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
 * Mark root dirty.
 *
 * @param {HTMLElement} root Vote root.
 */
const markDirty = root => {
    root.classList.add(CLASSES.dirty);
    root.classList.remove(CLASSES.saved);

    const indicator = root.querySelector(SELECTORS.draftIndicator);

    if (indicator) {
        indicator.hidden = false;
    }
};

/**
 * Mark root saved.
 *
 * @param {HTMLElement} root Vote root.
 */
const markSaved = root => {
    root.classList.remove(CLASSES.dirty);
    root.classList.add(CLASSES.saved);

    const indicator = root.querySelector(SELECTORS.draftIndicator);

    if (indicator) {
        indicator.hidden = true;
    }
};

/**
 * Update selected vote label in the UI.
 *
 * @param {HTMLElement} root Vote root.
 */
const updateSelectedVoteDisplay = root => {
    const selected = root.querySelector(`${SELECTORS.voteOption}:checked`);
    const labelNode = root.querySelector(SELECTORS.selectedLabel);

    root.querySelectorAll(SELECTORS.voteOption).forEach(option => {
        const optionContainer = option.closest('[data-region="uckkassembly-vote-option"]');

        if (optionContainer) {
            optionContainer.classList.toggle(CLASSES.selected, option.checked);
        }
    });

    if (labelNode) {
        labelNode.textContent = selected?.dataset?.label || selected?.value || '';
    }
};

/**
 * Update selected reading summary.
 *
 * @param {HTMLElement} root Vote root.
 */
const updateReadingSummary = root => {
    const summary = root.querySelector(SELECTORS.readingSummary);

    if (!summary) {
        return;
    }

    const readings = getSelectedReadings(root);

    if (!readings.length) {
        getString('votereadings:none', COMPONENT).then(message => {
            summary.textContent = message;
            return message;
        }).catch(Notification.exception);
        return;
    }

    summary.textContent = readings
        .map(reading => reading.label || reading.key)
        .filter(Boolean)
        .join(', ');
};

/**
 * Toggle a reading detail region.
 *
 * @param {HTMLElement} root Vote root.
 * @param {HTMLElement} trigger Trigger element.
 */
const toggleReadingRegion = (root, trigger) => {
    const targetId = trigger.getAttribute('aria-controls');

    if (!targetId) {
        return;
    }

    const region = root.querySelector(`#${CSS.escape(targetId)}`);

    if (!region) {
        return;
    }

    const expanded = trigger.getAttribute('aria-expanded') === 'true';

    trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    region.hidden = expanded;
    region.classList.toggle(CLASSES.hidden, expanded);
};

/**
 * Clear current vote selection and notes.
 *
 * @param {HTMLElement} root Vote root.
 */
const clearVote = root => {
    root.querySelectorAll(SELECTORS.voteOption).forEach(option => {
        option.checked = false;
    });

    [
        SELECTORS.rationale,
        SELECTORS.evidenceNote,
        SELECTORS.minorityNote,
        SELECTORS.integrityNote,
        SELECTORS.uncertaintyNote,
    ].forEach(selector => {
        const field = root.querySelector(selector);

        if (field) {
            field.value = '';
        }
    });

    updateSelectedVoteDisplay(root);
    updateReadingSummary(root);
    markDirty(root);
};

/**
 * Validate before final submit.
 *
 * Client validation is advisory. Server-side validation remains authoritative.
 *
 * @param {HTMLElement} root Vote root.
 * @returns {Promise<Boolean>}
 */
const validateBeforeSubmit = async(root) => {
    const data = collectVoteData(root);

    if (!hasRequiredIdentifiers(data)) {
        await Notification.alert(
            await getString('voteerror', COMPONENT),
            await getString('votemissingidentifiers', COMPONENT)
        );
        return false;
    }

    if (!data.option) {
        await Notification.alert(
            await getString('voteerror', COMPONENT),
            await getString('votemissingoption', COMPONENT)
        );
        return false;
    }

    if (!data.rationale.trim()) {
        await Notification.alert(
            await getString('voteerror', COMPONENT),
            await getString('votemissingrationale', COMPONENT)
        );
        return false;
    }

    return true;
};

/**
 * Confirm final vote submission.
 *
 * @returns {Promise<Boolean>}
 */
const confirmSubmit = async() => {
    const title = await getString('confirmvotesubmit', COMPONENT);
    const body = await getString('confirmvotesubmitbody', COMPONENT);
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
 * Save vote draft.
 *
 * @param {HTMLElement} root Vote root.
 * @param {Object} options Optional init options.
 * @returns {Promise<void>}
 */
const saveDraft = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = collectVoteData(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('votesaving', COMPONENT));

        const response = await callService(methods.saveDraft, args);

        if (response?.voteid) {
            root.dataset.voteid = String(response.voteid);
        }

        markSaved(root);
        setStatus(root, await getString('votesaved', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('votesavefailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Submit final vote/readings.
 *
 * @param {HTMLElement} root Vote root.
 * @param {Object} options Optional init options.
 * @returns {Promise<void>}
 */
const submitVote = async(root, options = {}) => {
    const valid = await validateBeforeSubmit(root);

    if (!valid) {
        return;
    }

    const confirmed = await confirmSubmit();

    if (!confirmed) {
        return;
    }

    const methods = getMethods(root, options);
    const args = collectVoteData(root);

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('votesubmitting', COMPONENT));

        const response = await callService(methods.submitVote, args);

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (response?.template && response?.context) {
            const html = await Templates.render(response.template, response.context);
            replacePanel(root, html, '');
        } else if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        markSaved(root);
        setStatus(root, await getString('votesubmitted', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('votesubmitfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Refresh vote panel.
 *
 * @param {HTMLElement} root Vote root.
 * @param {Object} options Optional init options.
 * @returns {Promise<void>}
 */
const refreshPanel = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = getBaseArgs(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('voterefreshing', COMPONENT));

        const response = await callService(methods.refreshPanel, args);

        if (response?.template && response?.context) {
            const html = await Templates.render(response.template, response.context);
            replacePanel(root, html, '');
        } else if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        setStatus(root, await getString('voterefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('voterefreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Replace panel contents.
 *
 * @param {HTMLElement} root Vote root.
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
 * Schedule autosave.
 *
 * @param {HTMLElement} root Vote root.
 * @param {Object} options Optional init options.
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
 * Handle form changes.
 *
 * @param {Event} event Input/change event.
 * @param {HTMLElement} root Vote root.
 * @param {Object} options Optional init options.
 */
const handleChange = (event, root, options = {}) => {
    const target = event.target;

    if (!target.closest(SELECTORS.form)) {
        return;
    }

    markDirty(root);

    if (target.matches(SELECTORS.voteOption)) {
        updateSelectedVoteDisplay(root);
    }

    if (target.matches(SELECTORS.readingOption)) {
        updateReadingSummary(root);
    }

    scheduleAutosave(root, options);
};

/**
 * Handle click actions.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Vote root.
 * @param {Object} options Optional init options.
 */
const handleClick = (event, root, options = {}) => {
    const saveButton = event.target.closest(SELECTORS.saveDraft);

    if (saveButton) {
        event.preventDefault();
        saveDraft(root, options);
        return;
    }

    const submitButton = event.target.closest(SELECTORS.submitVote);

    if (submitButton) {
        event.preventDefault();
        submitVote(root, options);
        return;
    }

    const refreshButton = event.target.closest(SELECTORS.refresh);

    if (refreshButton) {
        event.preventDefault();
        refreshPanel(root, options);
        return;
    }

    const clearButton = event.target.closest(SELECTORS.clearVote);

    if (clearButton) {
        event.preventDefault();
        clearVote(root);
        return;
    }

    const readingToggle = event.target.closest(SELECTORS.toggleReading);

    if (readingToggle) {
        event.preventDefault();
        toggleReadingRegion(root, readingToggle);
    }
};

/**
 * Initialise one vote root.
 *
 * @param {HTMLElement} root Vote root.
 * @param {Object} options Optional init options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');

    updateSelectedVoteDisplay(root);
    updateReadingSummary(root);

    root.addEventListener('input', event => handleChange(event, root, options));
    root.addEventListener('change', event => handleChange(event, root, options));
    root.addEventListener('click', event => handleClick(event, root, options));
};

/**
 * Initialise Assembly voting UI.
 *
 * Recommended PHP:
 * $PAGE->requires->js_call_amd('mod_uckkassembly/vote', 'init', [$uniqid]);
 *
 * Recommended Mustache:
 * {{#js}}
 * require(['mod_uckkassembly/vote'], function(Vote) {
 *     Vote.init('{{uniqid}}');
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
 * Public helper to save a vote draft.
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
 * Public helper to refresh a vote panel.
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

    return refreshPanel(root, options);
};