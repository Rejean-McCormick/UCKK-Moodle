/**
 * Evaluation interactions for mod_uckkchallenge.
 *
 * This module is intentionally UI-only:
 * - reads rubric values from the DOM;
 * - updates local display totals;
 * - saves evaluator drafts through declared Ajax services;
 * - submits evaluations through declared Ajax services;
 * - refreshes the evaluation panel markup.
 *
 * It must not grade authoritatively, validate integrity, award badges,
 * certify competencies, archive records, or change workflow state locally.
 *
 * @module     mod_uckkchallenge/evaluation
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkchallenge';

const DEFAULT_METHODS = {
    saveDraft: 'mod_uckkchallenge_save_evaluation_draft',
    submitEvaluation: 'mod_uckkchallenge_submit_evaluation',
    refreshPanel: 'mod_uckkchallenge_get_evaluation_panel',
};

const SELECTORS = {
    root: '[data-region="uckkchallenge-evaluation"]',
    form: '[data-region="uckkchallenge-evaluation-form"]',
    status: '[data-region="uckkchallenge-evaluation-status"]',
    panel: '[data-region="uckkchallenge-evaluation-panel"]',
    totalScore: '[data-region="uckkchallenge-evaluation-total"]',
    maxScore: '[data-region="uckkchallenge-evaluation-max"]',
    percentScore: '[data-region="uckkchallenge-evaluation-percent"]',
    scoreInput: '[data-field="rubric-score"]',
    competencyInput: '[data-field="competency-rating"]',
    badgeInput: '[data-field="badge-trigger"]',
    feedbackInput: '[data-field="feedback"]',
    publicSummaryInput: '[data-field="public-summary"]',
    privateFeedbackInput: '[data-field="private-feedback"]',
    integrityRecommendationInput: '[data-field="integrity-recommendation"]',
    archiveRecommendationInput: '[data-field="archive-recommendation"]',
    actionSaveDraft: '[data-action="uckkchallenge-save-evaluation-draft"]',
    actionSubmit: '[data-action="uckkchallenge-submit-evaluation"]',
    actionRefresh: '[data-action="uckkchallenge-refresh-evaluation"]',
    actionRecalculate: '[data-action="uckkchallenge-recalculate-evaluation"]',
    actionTogglePrivateFeedback: '[data-action="uckkchallenge-toggle-private-feedback"]',
    privateFeedbackRegion: '[data-region="uckkchallenge-private-feedback"]',
};

const CLASSES = {
    loading: 'is-loading',
    dirty: 'is-dirty',
    saved: 'is-saved',
    error: 'has-error',
    hidden: 'd-none',
};

const ATTRIBUTES = {
    initialised: 'data-uckkchallenge-evaluation-initialised',
};

let autosaveTimers = new WeakMap();

/**
 * Get a numeric dataset value.
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
 * Set a status message.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {String} message Message.
 */
const setStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Return service method names for this root.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {Object} options Init options.
 * @returns {Object}
 */
const getMethods = (root, options = {}) => {
    return {
        saveDraft: root.dataset.saveMethod || options.saveMethod || DEFAULT_METHODS.saveDraft,
        submitEvaluation: root.dataset.submitMethod || options.submitMethod || DEFAULT_METHODS.submitEvaluation,
        refreshPanel: root.dataset.refreshMethod || options.refreshMethod || DEFAULT_METHODS.refreshPanel,
    };
};

/**
 * Build base Ajax args.
 *
 * @param {HTMLElement} root Evaluation root.
 * @returns {Object}
 */
const getBaseArgs = root => {
    return {
        cmid: getNumberData(root, 'cmid'),
        challengeid: getNumberData(root, 'challengeid'),
        submissionid: getNumberData(root, 'submissionid'),
        evaluationid: getNumberData(root, 'evaluationid'),
    };
};

/**
 * Return true when the base identifiers are usable.
 *
 * @param {Object} args Ajax args.
 * @returns {Boolean}
 */
const hasRequiredIdentifiers = args => {
    return Boolean(args.cmid && args.challengeid && args.submissionid);
};

/**
 * Convert a form field value.
 *
 * @param {HTMLElement} field Field element.
 * @returns {String|Number|Boolean}
 */
const getFieldValue = field => {
    if (field.type === 'checkbox') {
        return field.checked;
    }

    if (field.type === 'radio') {
        return field.checked ? field.value : null;
    }

    if (field.type === 'number' || field.dataset.valueType === 'number') {
        const number = Number(field.value);
        return Number.isFinite(number) ? number : 0;
    }

    return field.value;
};

/**
 * Collect grouped field values.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {String} selector Field selector.
 * @returns {Array}
 */
const collectFields = (root, selector) => {
    const values = [];

    root.querySelectorAll(selector).forEach(field => {
        const value = getFieldValue(field);

        if (value === null) {
            return;
        }

        values.push({
            key: field.dataset.key || field.name || '',
            value,
            max: getNumberData(field, 'maxScore'),
            weight: getNumberData(field, 'weight', 1),
            criterionid: getNumberData(field, 'criterionid'),
            competencyid: getNumberData(field, 'competencyid'),
            badgekey: field.dataset.badgekey || '',
        });
    });

    return values;
};

/**
 * Collect evaluation payload from the DOM.
 *
 * @param {HTMLElement} root Evaluation root.
 * @returns {Object}
 */
const collectEvaluationData = root => {
    const form = root.querySelector(SELECTORS.form);
    const base = getBaseArgs(root);

    return {
        ...base,
        rubric: collectFields(root, SELECTORS.scoreInput),
        competencies: collectFields(root, SELECTORS.competencyInput),
        badges: collectFields(root, SELECTORS.badgeInput),
        feedback: collectTextValue(root, SELECTORS.feedbackInput),
        publicsummary: collectTextValue(root, SELECTORS.publicSummaryInput),
        privatefeedback: collectTextValue(root, SELECTORS.privateFeedbackInput),
        integrityrecommendation: collectTextValue(root, SELECTORS.integrityRecommendationInput),
        archiverecommendation: collectTextValue(root, SELECTORS.archiveRecommendationInput),
        sesskey: form?.dataset?.sesskey || M.cfg.sesskey,
    };
};

/**
 * Collect a single text value.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {String} selector Field selector.
 * @returns {String}
 */
const collectTextValue = (root, selector) => {
    const field = root.querySelector(selector);

    return field ? field.value : '';
};

/**
 * Calculate score totals from rubric score fields.
 *
 * @param {HTMLElement} root Evaluation root.
 * @returns {Object}
 */
const calculateScore = root => {
    let total = 0;
    let max = 0;

    root.querySelectorAll(SELECTORS.scoreInput).forEach(field => {
        const value = Number(field.value);
        const maxScore = getNumberData(field, 'maxScore');

        if (Number.isFinite(value)) {
            total += value;
        }

        if (maxScore > 0) {
            max += maxScore;
        }
    });

    const percent = max > 0 ? Math.round((total / max) * 100) : 0;

    return {
        total,
        max,
        percent,
    };
};

/**
 * Update local score displays.
 *
 * @param {HTMLElement} root Evaluation root.
 */
const updateScoreDisplay = root => {
    const score = calculateScore(root);
    const totalNode = root.querySelector(SELECTORS.totalScore);
    const maxNode = root.querySelector(SELECTORS.maxScore);
    const percentNode = root.querySelector(SELECTORS.percentScore);

    if (totalNode) {
        totalNode.textContent = String(score.total);
    }

    if (maxNode) {
        maxNode.textContent = String(score.max);
    }

    if (percentNode) {
        percentNode.textContent = `${score.percent}%`;
    }
};

/**
 * Mark the panel as changed.
 *
 * @param {HTMLElement} root Evaluation root.
 */
const markDirty = root => {
    root.classList.add(CLASSES.dirty);
    root.classList.remove(CLASSES.saved);
};

/**
 * Mark the panel as saved.
 *
 * @param {HTMLElement} root Evaluation root.
 */
const markSaved = root => {
    root.classList.remove(CLASSES.dirty);
    root.classList.add(CLASSES.saved);
};

/**
 * Validate client-side minimum requirements before sending.
 *
 * This is advisory only. Server-side validation remains authoritative.
 *
 * @param {HTMLElement} root Evaluation root.
 * @returns {Promise<Boolean>}
 */
const validateBeforeSubmit = async(root) => {
    const data = collectEvaluationData(root);

    if (!hasRequiredIdentifiers(data)) {
        await Notification.alert(
            await getString('evaluationerror', COMPONENT),
            await getString('evaluationmissingidentifiers', COMPONENT)
        );
        return false;
    }

    if (!data.rubric.length) {
        await Notification.alert(
            await getString('evaluationerror', COMPONENT),
            await getString('evaluationmissingrubric', COMPONENT)
        );
        return false;
    }

    if (!data.feedback.trim()) {
        await Notification.alert(
            await getString('evaluationerror', COMPONENT),
            await getString('evaluationmissingfeedback', COMPONENT)
        );
        return false;
    }

    return true;
};

/**
 * Call a Moodle Ajax service.
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
 * Save an evaluation draft.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const saveDraft = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = collectEvaluationData(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('evaluationsaving', COMPONENT));

        const response = await callService(methods.saveDraft, args);

        if (response?.evaluationid) {
            root.dataset.evaluationid = String(response.evaluationid);
        }

        markSaved(root);
        setStatus(root, await getString('evaluationsaved', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('evaluationsavefailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Submit the final evaluation to the server-side workflow.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const submitEvaluation = async(root, options = {}) => {
    const valid = await validateBeforeSubmit(root);

    if (!valid) {
        return;
    }

    const confirmed = await confirmSubmit();

    if (!confirmed) {
        return;
    }

    const methods = getMethods(root, options);
    const args = collectEvaluationData(root);

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('evaluationsubmitting', COMPONENT));

        const response = await callService(methods.submitEvaluation, args);

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        markSaved(root);
        setStatus(root, await getString('evaluationsubmitted', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('evaluationsubmitfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Confirm final evaluation submission.
 *
 * @returns {Promise<Boolean>}
 */
const confirmSubmit = async() => {
    return new Promise(resolve => {
        Notification.confirm(
            getString('confirmevaluationsubmit', COMPONENT),
            getString('confirmevaluationsubmitbody', COMPONENT),
            getString('submit', 'moodle'),
            getString('cancel', 'moodle'),
            () => resolve(true),
            () => resolve(false)
        );
    });
};

/**
 * Refresh the evaluation panel.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {Object} options Init options.
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
        setStatus(root, await getString('evaluationrefreshing', COMPONENT));

        const response = await callService(methods.refreshPanel, args);

        if (response?.template && response?.context) {
            const html = await Templates.render(response.template, response.context);
            replacePanel(root, html, '');
        } else if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        setStatus(root, await getString('evaluationrefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('evaluationrefreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Replace evaluation panel contents.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {String} html HTML markup.
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
 * @param {HTMLElement} root Evaluation root.
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
 * Toggle private feedback visibility.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {HTMLElement} trigger Toggle trigger.
 */
const togglePrivateFeedback = (root, trigger) => {
    const region = root.querySelector(SELECTORS.privateFeedbackRegion);

    if (!region) {
        return;
    }

    const isHidden = region.classList.contains(CLASSES.hidden) || region.hidden;

    region.classList.toggle(CLASSES.hidden, !isHidden);
    region.hidden = !isHidden;

    trigger.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
};

/**
 * Handle input changes.
 *
 * @param {Event} event Input/change event.
 * @param {HTMLElement} root Evaluation root.
 * @param {Object} options Init options.
 */
const handleChange = (event, root, options = {}) => {
    const target = event.target;

    if (!target.closest(SELECTORS.form)) {
        return;
    }

    markDirty(root);

    if (target.matches(SELECTORS.scoreInput)) {
        updateScoreDisplay(root);
    }

    scheduleAutosave(root, options);
};

/**
 * Handle click actions.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Evaluation root.
 * @param {Object} options Init options.
 */
const handleClick = (event, root, options = {}) => {
    const saveButton = event.target.closest(SELECTORS.actionSaveDraft);

    if (saveButton) {
        event.preventDefault();
        saveDraft(root, options);
        return;
    }

    const submitButton = event.target.closest(SELECTORS.actionSubmit);

    if (submitButton) {
        event.preventDefault();
        submitEvaluation(root, options);
        return;
    }

    const refreshButton = event.target.closest(SELECTORS.actionRefresh);

    if (refreshButton) {
        event.preventDefault();
        refreshPanel(root, options);
        return;
    }

    const recalculateButton = event.target.closest(SELECTORS.actionRecalculate);

    if (recalculateButton) {
        event.preventDefault();
        updateScoreDisplay(root);
        return;
    }

    const privateFeedbackToggle = event.target.closest(SELECTORS.actionTogglePrivateFeedback);

    if (privateFeedbackToggle) {
        event.preventDefault();
        togglePrivateFeedback(root, privateFeedbackToggle);
    }
};

/**
 * Initialise one evaluation root.
 *
 * @param {HTMLElement} root Evaluation root.
 * @param {Object} options Init options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');

    updateScoreDisplay(root);

    root.addEventListener('input', event => handleChange(event, root, options));
    root.addEventListener('change', event => handleChange(event, root, options));
    root.addEventListener('click', event => handleClick(event, root, options));
};

/**
 * Initialise challenge evaluation UI.
 *
 * Recommended PHP:
 * $PAGE->requires->js_call_amd('mod_uckkchallenge/evaluation', 'init', [$uniqid]);
 *
 * Recommended Mustache:
 * {{#js}}
 * require(['mod_uckkchallenge/evaluation'], function(Evaluation) {
 *     Evaluation.init('{{uniqid}}');
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
 * Public helper to save the current evaluation draft.
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
 * Public helper to refresh an evaluation panel.
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