/**
 * Main browser-side controller for UCKK Challenge pages.
 *
 * This module is intentionally non-authoritative:
 * - it refreshes panels through Moodle external functions;
 * - it toggles UI sections;
 * - it updates status messages;
 * - it never decides permissions, workflow state, grades, integrity,
 *   archive validity, badges, or competencies.
 *
 * @module     mod_uckkchallenge/challenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkchallenge';

const METHOD = {
    getChallenge: 'mod_uckkchallenge_get_challenge',
    getProofList: 'mod_uckkchallenge_get_proof_list',
    getSubmissionStatus: 'mod_uckkchallenge_get_submission_status',
    getIntegritySummary: 'mod_uckkchallenge_get_integrity_summary',
    getArchivePreview: 'mod_uckkchallenge_get_archive_preview',
};

const SELECTORS = {
    root: '[data-region="mod-uckkchallenge"]',
    status: '[data-region="mod-uckkchallenge-status"]',

    panel: '[data-region="mod-uckkchallenge-panel"]',
    panelBody: '[data-region="mod-uckkchallenge-panel-body"]',
    panelStatus: '[data-region="mod-uckkchallenge-panel-status"]',

    challengeSummary: '[data-region="mod-uckkchallenge-summary"]',
    proofList: '[data-region="mod-uckkchallenge-proof-list"]',
    submissionStatus: '[data-region="mod-uckkchallenge-submission-status"]',
    integritySummary: '[data-region="mod-uckkchallenge-integrity-summary"]',
    archivePreview: '[data-region="mod-uckkchallenge-archive-preview"]',

    togglePanel: '[data-action="mod-uckkchallenge-toggle-panel"]',
    refreshChallenge: '[data-action="mod-uckkchallenge-refresh-challenge"]',
    refreshProofs: '[data-action="mod-uckkchallenge-refresh-proofs"]',
    refreshSubmission: '[data-action="mod-uckkchallenge-refresh-submission"]',
    refreshIntegrity: '[data-action="mod-uckkchallenge-refresh-integrity"]',
    refreshArchivePreview: '[data-action="mod-uckkchallenge-refresh-archive-preview"]',
};

const CLASSES = {
    initialised: 'is-initialised',
    loading: 'is-loading',
    loaded: 'is-loaded',
    error: 'has-error',
    expanded: 'is-expanded',
    collapsed: 'is-collapsed',
};

const ATTRIBUTES = {
    initialised: 'data-mod-uckkchallenge-initialised',
    cmid: 'data-cm-id',
    challengeid: 'data-challenge-id',
    userid: 'data-user-id',
    template: 'data-template',
    expanded: 'data-expanded',
};

/**
 * Read integer data from a root element.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} attribute Attribute name.
 * @returns {Number}
 */
const getIntAttribute = (root, attribute) => {
    const value = Number(root.getAttribute(attribute) || 0);
    return Number.isFinite(value) ? value : 0;
};

/**
 * Build shared request args for challenge external functions.
 *
 * @param {HTMLElement} root Root element.
 * @returns {{cmid: Number, challengeid: Number, userid: Number}}
 */
const getBaseArgs = root => ({
    cmid: getIntAttribute(root, ATTRIBUTES.cmid),
    challengeid: getIntAttribute(root, ATTRIBUTES.challengeid),
    userid: getIntAttribute(root, ATTRIBUTES.userid),
});

/**
 * Return the root region from an event target.
 *
 * @param {HTMLElement} element Source element.
 * @returns {HTMLElement|null}
 */
const getRoot = element => {
    if (!element) {
        return null;
    }

    return element.closest(SELECTORS.root);
};

/**
 * Return the nearest panel.
 *
 * @param {HTMLElement} element Source element.
 * @returns {HTMLElement|null}
 */
const getPanel = element => {
    if (!element) {
        return null;
    }

    return element.closest(SELECTORS.panel);
};

/**
 * Set a polite accessible status message.
 *
 * @param {HTMLElement} root Root region.
 * @param {String} message Message.
 */
const setRootStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Set a panel-local status message.
 *
 * @param {HTMLElement} panel Panel.
 * @param {String} message Message.
 */
const setPanelStatus = (panel, message) => {
    const status = panel.querySelector(SELECTORS.panelStatus);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Toggle loading state.
 *
 * @param {HTMLElement} root Root element.
 * @param {Boolean} loading Loading state.
 */
const setLoading = (root, loading) => {
    root.classList.toggle(CLASSES.loading, loading);

    if (!loading) {
        root.classList.add(CLASSES.loaded);
    }
};

/**
 * Toggle panel loading state.
 *
 * @param {HTMLElement} panel Panel.
 * @param {Boolean} loading Loading state.
 */
const setPanelLoading = (panel, loading) => {
    panel.classList.toggle(CLASSES.loading, loading);

    if (!loading) {
        panel.classList.add(CLASSES.loaded);
    }
};

/**
 * Call a Moodle external function.
 *
 * @param {String} methodname External function name.
 * @param {Object} args Function args.
 * @returns {Promise<Object>}
 */
const callExternal = async(methodname, args) => {
    const requests = Ajax.call([{
        methodname,
        args,
    }]);

    return requests[0];
};

/**
 * Replace a target region from an external function response.
 *
 * Supported response shapes:
 * - {html: "..."}
 * - {template: "mod_uckkchallenge/proof_list", context: {...}}
 * - {context: {...}} with fallbackTemplate passed in.
 *
 * @param {HTMLElement} target Target element.
 * @param {Object} response External response.
 * @param {String} fallbackTemplate Fallback template.
 * @returns {Promise<void>}
 */
const replaceFromResponse = async(target, response, fallbackTemplate = '') => {
    if (!target || !response) {
        return;
    }

    if (response.html) {
        Templates.replaceNodeContents(target, response.html, '');
        return;
    }

    const template = response.template || target.getAttribute(ATTRIBUTES.template) || fallbackTemplate;

    if (template && response.context) {
        const html = await Templates.render(template, response.context);
        Templates.replaceNodeContents(target, html, '');
    }
};

/**
 * Refresh the challenge summary.
 *
 * @param {HTMLElement} root Root region.
 * @returns {Promise<void>}
 */
const refreshChallenge = async(root) => {
    const target = root.querySelector(SELECTORS.challengeSummary);

    if (!target) {
        return;
    }

    setLoading(root, true);
    root.classList.remove(CLASSES.error);
    setRootStatus(root, await getString('js:refreshingchallenge', COMPONENT));

    try {
        const response = await callExternal(METHOD.getChallenge, getBaseArgs(root));
        await replaceFromResponse(target, response, 'mod_uckkchallenge/challenge_summary');
        setRootStatus(root, await getString('js:challengerefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setRootStatus(root, await getString('js:refreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Refresh the proof list.
 *
 * @param {HTMLElement} root Root region.
 * @returns {Promise<void>}
 */
const refreshProofs = async(root) => {
    const target = root.querySelector(SELECTORS.proofList);

    if (!target) {
        return;
    }

    setLoading(root, true);
    root.classList.remove(CLASSES.error);
    setRootStatus(root, await getString('js:refreshingproofs', COMPONENT));

    try {
        const response = await callExternal(METHOD.getProofList, getBaseArgs(root));
        await replaceFromResponse(target, response, 'mod_uckkchallenge/proof_list');
        setRootStatus(root, await getString('js:proofsrefreshed', COMPONENT));

        root.dispatchEvent(new CustomEvent('mod_uckkchallenge:proofsrefreshed', {
            bubbles: true,
            detail: {
                challengeid: getBaseArgs(root).challengeid,
                cmid: getBaseArgs(root).cmid,
            },
        }));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setRootStatus(root, await getString('js:refreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Refresh the current user submission status.
 *
 * @param {HTMLElement} root Root region.
 * @returns {Promise<void>}
 */
const refreshSubmissionStatus = async(root) => {
    const target = root.querySelector(SELECTORS.submissionStatus);

    if (!target) {
        return;
    }

    setLoading(root, true);
    root.classList.remove(CLASSES.error);
    setRootStatus(root, await getString('js:refreshingsubmission', COMPONENT));

    try {
        const response = await callExternal(METHOD.getSubmissionStatus, getBaseArgs(root));
        await replaceFromResponse(target, response, 'mod_uckkchallenge/submission_status');
        setRootStatus(root, await getString('js:submissionrefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setRootStatus(root, await getString('js:refreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Refresh the integrity summary.
 *
 * @param {HTMLElement} root Root region.
 * @returns {Promise<void>}
 */
const refreshIntegritySummary = async(root) => {
    const target = root.querySelector(SELECTORS.integritySummary);

    if (!target) {
        return;
    }

    setLoading(root, true);
    root.classList.remove(CLASSES.error);
    setRootStatus(root, await getString('js:refreshingintegrity', COMPONENT));

    try {
        const response = await callExternal(METHOD.getIntegritySummary, getBaseArgs(root));
        await replaceFromResponse(target, response, 'mod_uckkchallenge/integrity_summary');
        setRootStatus(root, await getString('js:integrityrefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setRootStatus(root, await getString('js:refreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Refresh the archive preview.
 *
 * @param {HTMLElement} root Root region.
 * @returns {Promise<void>}
 */
const refreshArchivePreview = async(root) => {
    const target = root.querySelector(SELECTORS.archivePreview);

    if (!target) {
        return;
    }

    setLoading(root, true);
    root.classList.remove(CLASSES.error);
    setRootStatus(root, await getString('js:refreshingarchivepreview', COMPONENT));

    try {
        const response = await callExternal(METHOD.getArchivePreview, getBaseArgs(root));
        await replaceFromResponse(target, response, 'mod_uckkchallenge/archive_preview');
        setRootStatus(root, await getString('js:archivepreviewrefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setRootStatus(root, await getString('js:refreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Set panel expanded/collapsed state.
 *
 * @param {HTMLElement} panel Panel.
 * @param {Boolean} expanded Expanded state.
 */
const setPanelExpanded = (panel, expanded) => {
    const body = panel.querySelector(SELECTORS.panelBody);
    const toggle = panel.querySelector(SELECTORS.togglePanel);

    panel.classList.toggle(CLASSES.expanded, expanded);
    panel.classList.toggle(CLASSES.collapsed, !expanded);

    if (body) {
        body.hidden = !expanded;
        body.setAttribute('aria-hidden', expanded ? 'false' : 'true');
    }

    if (toggle) {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');

        if (body?.id) {
            toggle.setAttribute('aria-controls', body.id);
        }
    }
};

/**
 * Toggle one panel.
 *
 * @param {HTMLElement} panel Panel.
 */
const togglePanel = panel => {
    const toggle = panel.querySelector(SELECTORS.togglePanel);
    const expanded = toggle?.getAttribute('aria-expanded') === 'true';

    setPanelExpanded(panel, !expanded);
};

/**
 * Prepare all panels inside a root.
 *
 * @param {HTMLElement} root Root region.
 */
const preparePanels = root => {
    root.querySelectorAll(SELECTORS.panel).forEach(panel => {
        const expanded = panel.getAttribute(ATTRIBUTES.expanded) === 'true'
            || panel.classList.contains(CLASSES.expanded);

        setPanelExpanded(panel, expanded);
        setPanelLoading(panel, false);
        setPanelStatus(panel, '');
    });
};

/**
 * Handle click events.
 *
 * @param {MouseEvent} event Click event.
 */
const handleClick = event => {
    const toggle = event.target.closest(SELECTORS.togglePanel);

    if (toggle) {
        event.preventDefault();

        const panel = getPanel(toggle);

        if (panel) {
            togglePanel(panel);
        }

        return;
    }

    const root = getRoot(event.target);

    if (!root) {
        return;
    }

    if (event.target.closest(SELECTORS.refreshChallenge)) {
        event.preventDefault();
        refreshChallenge(root);
        return;
    }

    if (event.target.closest(SELECTORS.refreshProofs)) {
        event.preventDefault();
        refreshProofs(root);
        return;
    }

    if (event.target.closest(SELECTORS.refreshSubmission)) {
        event.preventDefault();
        refreshSubmissionStatus(root);
        return;
    }

    if (event.target.closest(SELECTORS.refreshIntegrity)) {
        event.preventDefault();
        refreshIntegritySummary(root);
        return;
    }

    if (event.target.closest(SELECTORS.refreshArchivePreview)) {
        event.preventDefault();
        refreshArchivePreview(root);
    }
};

/**
 * Register listeners for one root.
 *
 * @param {HTMLElement} root Root region.
 */
const registerRoot = root => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');
    root.classList.add(CLASSES.initialised);

    preparePanels(root);
    root.addEventListener('click', handleClick);

    if (root.dataset.refreshOnInit === 'true') {
        refreshChallenge(root);
        refreshProofs(root);
        refreshSubmissionStatus(root);
    }

    if (root.dataset.refreshIntegrityOnInit === 'true') {
        refreshIntegritySummary(root);
    }

    if (root.dataset.refreshArchiveOnInit === 'true') {
        refreshArchivePreview(root);
    }
};

/**
 * Initialise UCKK Challenge UI.
 *
 * Recommended template call:
 *
 * {{#js}}
 * require(['mod_uckkchallenge/challenge'], function(Challenge) {
 *     Challenge.init('{{uniqid}}');
 * });
 * {{/js}}
 *
 * @param {String|null} rootId Optional root id.
 */
export const init = (rootId = null) => {
    const roots = rootId
        ? [document.getElementById(rootId)].filter(Boolean)
        : Array.from(document.querySelectorAll(SELECTORS.root));

    roots.forEach(registerRoot);
};

/**
 * Public refresh helper used by other modules after proof submission.
 *
 * @param {String} rootId Root id.
 * @returns {Promise<void>}
 */
export const refresh = async(rootId) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return;
    }

    await Promise.all([
        refreshChallenge(root),
        refreshProofs(root),
        refreshSubmissionStatus(root),
    ]);
};

/**
 * Public helper for refreshing only proof data.
 *
 * @param {String} rootId Root id.
 * @returns {Promise<void>}
 */
export const refreshProofList = async(rootId) => {
    const root = document.getElementById(rootId);

    if (root) {
        await refreshProofs(root);
    }
};

/**
 * Public helper for refreshing integrity data.
 *
 * @param {String} rootId Root id.
 * @returns {Promise<void>}
 */
export const refreshIntegrity = async(rootId) => {
    const root = document.getElementById(rootId);

    if (root) {
        await refreshIntegritySummary(root);
    }
};