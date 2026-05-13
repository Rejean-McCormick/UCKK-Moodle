/**
 * Export interactions for mod_uckkarchive.
 *
 * This module is intentionally UI-only:
 * - collects export scope/options from the DOM;
 * - requests export preparation through declared Ajax services;
 * - refreshes export status/panel markup;
 * - exposes download links returned by the server;
 * - optionally polls pending export jobs.
 *
 * It must not decide permissions, bypass visibility, include restricted data
 * locally, validate archive records, create provenance, or generate packages
 * client-side.
 *
 * @module     mod_uckkarchive/export
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkarchive';

const DEFAULT_METHODS = {
    prepare: 'mod_uckkarchive_prepare_export',
    status: 'mod_uckkarchive_get_export_status',
    cancel: 'mod_uckkarchive_cancel_export',
    panel: 'mod_uckkarchive_get_export_panel',
};

const SELECTORS = {
    root: '[data-region="uckkarchive-export"]',
    form: '[data-region="uckkarchive-export-form"]',
    panel: '[data-region="uckkarchive-export-panel"]',
    status: '[data-region="uckkarchive-export-status"]',
    progress: '[data-region="uckkarchive-export-progress"]',
    progressBar: '[data-region="uckkarchive-export-progressbar"]',
    downloadRegion: '[data-region="uckkarchive-export-download"]',
    downloadLink: '[data-region="uckkarchive-export-download-link"]',
    warningRegion: '[data-region="uckkarchive-export-warnings"]',

    scopeInput: '[data-field="export-scope"]',
    formatInput: '[data-field="export-format"]',
    visibilityInput: '[data-field="export-visibility"]',
    includeProofsInput: '[data-field="include-proofs"]',
    includeProvenanceInput: '[data-field="include-provenance"]',
    includeRevisionsInput: '[data-field="include-revisions"]',
    includeKristalsInput: '[data-field="include-kristals"]',
    includeRestrictedInput: '[data-field="include-restricted"]',
    includeIntegrityInput: '[data-field="include-integrity"]',
    anonymiseInput: '[data-field="anonymise-users"]',
    reasonInput: '[data-field="export-reason"]',

    actionPrepare: '[data-action="uckkarchive-prepare-export"]',
    actionRefresh: '[data-action="uckkarchive-refresh-export"]',
    actionCancel: '[data-action="uckkarchive-cancel-export"]',
    actionDownload: '[data-action="uckkarchive-download-export"]',
    actionToggleAdvanced: '[data-action="uckkarchive-toggle-export-advanced"]',
    advancedRegion: '[data-region="uckkarchive-export-advanced"]',
};

const CLASSES = {
    loading: 'is-loading',
    pending: 'is-pending',
    complete: 'is-complete',
    error: 'has-error',
    hidden: 'd-none',
};

const ATTRIBUTES = {
    initialised: 'data-uckkarchive-export-initialised',
};

const POLL_LIMIT = 60;
const POLL_INTERVAL = 3000;

let pollTimers = new WeakMap();
let pollCounts = new WeakMap();

/**
 * Get integer dataset value.
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
 * Return configured Ajax method names.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} options Initialisation options.
 * @returns {Object}
 */
const getMethods = (root, options = {}) => ({
    prepare: root.dataset.prepareMethod || options.prepareMethod || DEFAULT_METHODS.prepare,
    status: root.dataset.statusMethod || options.statusMethod || DEFAULT_METHODS.status,
    cancel: root.dataset.cancelMethod || options.cancelMethod || DEFAULT_METHODS.cancel,
    panel: root.dataset.panelMethod || options.panelMethod || DEFAULT_METHODS.panel,
});

/**
 * Build base request arguments.
 *
 * @param {HTMLElement} root Export root.
 * @returns {Object}
 */
const getBaseArgs = root => ({
    cmid: getNumberData(root, 'cmid'),
    archiveid: getNumberData(root, 'archiveid'),
    exportid: getNumberData(root, 'exportid'),
});

/**
 * Validate required identifiers.
 *
 * @param {Object} args Request arguments.
 * @returns {Boolean}
 */
const hasRequiredIdentifiers = args => Boolean(args.cmid && args.archiveid);

/**
 * Set local status text.
 *
 * @param {HTMLElement} root Export root.
 * @param {String} message Message.
 */
const setStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Show or hide loading state.
 *
 * @param {HTMLElement} root Export root.
 * @param {Boolean} loading Loading state.
 */
const setLoading = (root, loading) => {
    root.classList.toggle(CLASSES.loading, loading);

    root.querySelectorAll('button, input, select, textarea').forEach(control => {
        if (control.matches(SELECTORS.actionDownload)) {
            return;
        }

        control.disabled = loading;
    });
};

/**
 * Get value for one field.
 *
 * @param {HTMLElement|null} field Field.
 * @returns {String|Boolean}
 */
const getFieldValue = field => {
    if (!field) {
        return '';
    }

    if (field.type === 'checkbox') {
        return field.checked;
    }

    if (field.type === 'radio') {
        return field.checked ? field.value : '';
    }

    return field.value;
};

/**
 * Collect radio/select/text value from a selector.
 *
 * @param {HTMLElement} root Export root.
 * @param {String} selector Field selector.
 * @returns {String|Boolean}
 */
const collectSingleValue = (root, selector) => {
    const fields = Array.from(root.querySelectorAll(selector));

    if (!fields.length) {
        return '';
    }

    if (fields[0].type === 'radio') {
        const checked = fields.find(field => field.checked);
        return checked ? checked.value : '';
    }

    return getFieldValue(fields[0]);
};

/**
 * Collect export options from the form.
 *
 * @param {HTMLElement} root Export root.
 * @returns {Object}
 */
const collectExportData = root => {
    const form = root.querySelector(SELECTORS.form);
    const base = getBaseArgs(root);

    return {
        ...base,
        scope: String(collectSingleValue(root, SELECTORS.scopeInput) || 'archive'),
        format: String(collectSingleValue(root, SELECTORS.formatInput) || 'json'),
        visibility: String(collectSingleValue(root, SELECTORS.visibilityInput) || 'course'),
        includeproofs: Boolean(collectSingleValue(root, SELECTORS.includeProofsInput)),
        includeprovenance: Boolean(collectSingleValue(root, SELECTORS.includeProvenanceInput)),
        includerevisions: Boolean(collectSingleValue(root, SELECTORS.includeRevisionsInput)),
        includekristals: Boolean(collectSingleValue(root, SELECTORS.includeKristalsInput)),
        includerestricted: Boolean(collectSingleValue(root, SELECTORS.includeRestrictedInput)),
        includeintegrity: Boolean(collectSingleValue(root, SELECTORS.includeIntegrityInput)),
        anonymiseusers: Boolean(collectSingleValue(root, SELECTORS.anonymiseInput)),
        reason: String(collectSingleValue(root, SELECTORS.reasonInput) || ''),
        sesskey: form?.dataset?.sesskey || M.cfg.sesskey,
    };
};

/**
 * Call a Moodle Ajax service.
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
 * Confirm export request.
 *
 * @param {Object} data Export data.
 * @returns {Promise<Boolean>}
 */
const confirmExport = async(data) => {
    const title = await getString('confirmexport', COMPONENT);
    const message = data.includerestricted || data.includeintegrity
        ? await getString('confirmrestrictedexportbody', COMPONENT)
        : await getString('confirmexportbody', COMPONENT);
    const confirm = await getString('exportarchive', COMPONENT);
    const cancel = await getString('cancel', 'moodle');

    return new Promise(resolve => {
        Notification.confirm(
            title,
            message,
            confirm,
            cancel,
            () => resolve(true),
            () => resolve(false)
        );
    });
};

/**
 * Validate export request client-side.
 *
 * Server-side validation remains authoritative.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} data Export data.
 * @returns {Promise<Boolean>}
 */
const validateBeforePrepare = async(root, data) => {
    if (!hasRequiredIdentifiers(data)) {
        await Notification.alert(
            await getString('exporterror', COMPONENT),
            await getString('exportmissingidentifiers', COMPONENT)
        );
        return false;
    }

    if (!data.reason.trim() && (data.includerestricted || data.includeintegrity)) {
        await Notification.alert(
            await getString('exporterror', COMPONENT),
            await getString('exportreasonrequired', COMPONENT)
        );

        const reason = root.querySelector(SELECTORS.reasonInput);
        if (reason) {
            reason.focus();
        }

        return false;
    }

    return true;
};

/**
 * Prepare export package.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} options Initialisation options.
 * @returns {Promise<void>}
 */
const prepareExport = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const data = collectExportData(root);

    if (!await validateBeforePrepare(root, data)) {
        return;
    }

    if (!await confirmExport(data)) {
        return;
    }

    setLoading(root, true);
    root.classList.remove(CLASSES.error, CLASSES.complete);
    root.classList.add(CLASSES.pending);

    try {
        setStatus(root, await getString('exportpreparing', COMPONENT));

        const response = await callService(methods.prepare, data);

        applyExportResponse(root, response);

        if (response?.status === 'complete' || response?.downloadurl) {
            root.classList.remove(CLASSES.pending);
            root.classList.add(CLASSES.complete);
            setStatus(root, await getString('exportready', COMPONENT));
            stopPolling(root);
            return;
        }

        setStatus(root, await getString('exportqueued', COMPONENT));
        startPolling(root, options);
    } catch (error) {
        root.classList.add(CLASSES.error);
        root.classList.remove(CLASSES.pending);
        setStatus(root, await getString('exportfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Refresh export status.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} options Initialisation options.
 * @returns {Promise<Object|null>}
 */
const refreshStatus = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = getBaseArgs(root);

    if (!hasRequiredIdentifiers(args)) {
        return null;
    }

    try {
        const response = await callService(methods.status, args);
        applyExportResponse(root, response);

        if (response?.status === 'complete' || response?.downloadurl) {
            root.classList.remove(CLASSES.pending);
            root.classList.add(CLASSES.complete);
            setStatus(root, await getString('exportready', COMPONENT));
            stopPolling(root);
        } else if (response?.status === 'failed') {
            root.classList.add(CLASSES.error);
            root.classList.remove(CLASSES.pending);
            setStatus(root, await getString('exportfailed', COMPONENT));
            stopPolling(root);
        }

        return response;
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('exportrefreshfailed', COMPONENT));
        Notification.exception(error);
        return null;
    }
};

/**
 * Refresh export panel markup.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} options Initialisation options.
 * @returns {Promise<void>}
 */
const refreshPanel = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = getBaseArgs(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    setLoading(root, true);

    try {
        setStatus(root, await getString('exportrefreshing', COMPONENT));

        const response = await callService(methods.panel, args);

        if (response?.template && response?.context) {
            const html = await Templates.render(response.template, response.context);
            replacePanel(root, html, '');
        } else if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        setStatus(root, await getString('exportrefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('exportrefreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Cancel export.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} options Initialisation options.
 * @returns {Promise<void>}
 */
const cancelExport = async(root, options = {}) => {
    const title = await getString('cancelexport', COMPONENT);
    const message = await getString('cancelexportbody', COMPONENT);
    const confirm = await getString('cancelexport', COMPONENT);
    const cancel = await getString('cancel', 'moodle');

    const confirmed = await new Promise(resolve => {
        Notification.confirm(
            title,
            message,
            confirm,
            cancel,
            () => resolve(true),
            () => resolve(false)
        );
    });

    if (!confirmed) {
        return;
    }

    const methods = getMethods(root, options);
    const args = getBaseArgs(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    setLoading(root, true);

    try {
        await callService(methods.cancel, {
            ...args,
            sesskey: M.cfg.sesskey,
        });

        stopPolling(root);
        root.classList.remove(CLASSES.pending, CLASSES.complete);
        setStatus(root, await getString('exportcancelled', COMPONENT));
        await refreshPanel(root, options);
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('exportcancelfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Apply export response to UI.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} response Service response.
 */
const applyExportResponse = (root, response = {}) => {
    if (!response || typeof response !== 'object') {
        return;
    }

    if (response.exportid) {
        root.dataset.exportid = String(response.exportid);
    }

    if (response.status) {
        root.dataset.exportStatus = response.status;
    }

    updateProgress(root, Number(response.progress ?? 0));

    if (response.message) {
        setStatus(root, response.message);
    }

    if (response.downloadurl) {
        showDownload(root, response.downloadurl, response.filename || '');
    }

    if (response.warnings) {
        showWarnings(root, response.warnings);
    }

    if (response.html) {
        replacePanel(root, response.html, response.js || '');
    }
};

/**
 * Update visual progress state.
 *
 * @param {HTMLElement} root Export root.
 * @param {Number} progress Progress percent.
 */
const updateProgress = (root, progress) => {
    if (!Number.isFinite(progress)) {
        return;
    }

    const safeProgress = Math.max(0, Math.min(100, Math.round(progress)));
    const progressRegion = root.querySelector(SELECTORS.progress);
    const progressBar = root.querySelector(SELECTORS.progressBar);

    if (progressRegion) {
        progressRegion.hidden = false;
        progressRegion.setAttribute('aria-valuenow', String(safeProgress));
    }

    if (progressBar) {
        progressBar.style.width = `${safeProgress}%`;
        progressBar.textContent = `${safeProgress}%`;
    }
};

/**
 * Display download link.
 *
 * @param {HTMLElement} root Export root.
 * @param {String} url Download URL.
 * @param {String} filename Optional filename.
 */
const showDownload = (root, url, filename = '') => {
    const region = root.querySelector(SELECTORS.downloadRegion);
    const link = root.querySelector(SELECTORS.downloadLink);

    if (region) {
        region.hidden = false;
        region.classList.remove(CLASSES.hidden);
    }

    if (link) {
        link.href = url;

        if (filename) {
            link.textContent = filename;
            link.setAttribute('download', filename);
        }
    }
};

/**
 * Display warning messages.
 *
 * @param {HTMLElement} root Export root.
 * @param {Array} warnings Warning rows or strings.
 */
const showWarnings = (root, warnings = []) => {
    const region = root.querySelector(SELECTORS.warningRegion);

    if (!region || !Array.isArray(warnings)) {
        return;
    }

    region.innerHTML = '';

    warnings.forEach(warning => {
        const message = typeof warning === 'string' ? warning : warning.message;

        if (!message) {
            return;
        }

        const node = document.createElement('div');
        node.className = 'alert alert-warning uckkarchive-export__warning';
        node.textContent = message;
        region.appendChild(node);
    });

    region.hidden = !region.children.length;
};

/**
 * Replace export panel contents.
 *
 * @param {HTMLElement} root Export root.
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
 * Start polling export status.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} options Initialisation options.
 */
const startPolling = (root, options = {}) => {
    stopPolling(root);

    pollCounts.set(root, 0);

    const interval = Number(options.pollInterval || root.dataset.pollInterval || POLL_INTERVAL);

    const timer = window.setInterval(async() => {
        const count = (pollCounts.get(root) || 0) + 1;
        pollCounts.set(root, count);

        if (count > POLL_LIMIT) {
            stopPolling(root);
            setStatus(root, await getString('exportpollingstopped', COMPONENT));
            return;
        }

        await refreshStatus(root, options);
    }, Number.isFinite(interval) ? interval : POLL_INTERVAL);

    pollTimers.set(root, timer);
};

/**
 * Stop polling export status.
 *
 * @param {HTMLElement} root Export root.
 */
const stopPolling = root => {
    const timer = pollTimers.get(root);

    if (timer) {
        window.clearInterval(timer);
    }

    pollTimers.delete(root);
    pollCounts.delete(root);
};

/**
 * Toggle advanced export options.
 *
 * @param {HTMLElement} root Export root.
 * @param {HTMLElement} trigger Toggle control.
 */
const toggleAdvanced = (root, trigger) => {
    const region = root.querySelector(SELECTORS.advancedRegion);

    if (!region) {
        return;
    }

    const expanded = trigger.getAttribute('aria-expanded') === 'true';

    trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    region.hidden = expanded;
    region.classList.toggle(CLASSES.hidden, expanded);
};

/**
 * Handle click events.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Export root.
 * @param {Object} options Initialisation options.
 */
const handleClick = (event, root, options = {}) => {
    const prepare = event.target.closest(SELECTORS.actionPrepare);

    if (prepare) {
        event.preventDefault();
        prepareExport(root, options);
        return;
    }

    const refresh = event.target.closest(SELECTORS.actionRefresh);

    if (refresh) {
        event.preventDefault();
        refreshPanel(root, options);
        return;
    }

    const cancel = event.target.closest(SELECTORS.actionCancel);

    if (cancel) {
        event.preventDefault();
        cancelExport(root, options);
        return;
    }

    const toggle = event.target.closest(SELECTORS.actionToggleAdvanced);

    if (toggle) {
        event.preventDefault();
        toggleAdvanced(root, toggle);
    }
};

/**
 * Initialise one root.
 *
 * @param {HTMLElement} root Export root.
 * @param {Object} options Initialisation options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');
    root.addEventListener('click', event => handleClick(event, root, options));

    if (root.dataset.autopoll === 'true') {
        startPolling(root, options);
    }
};

/**
 * Initialise archive export UI.
 *
 * Recommended PHP:
 * $PAGE->requires->js_call_amd('mod_uckkarchive/export', 'init', [$uniqid]);
 *
 * Recommended Mustache:
 * {{#js}}
 * require(['mod_uckkarchive/export'], function(Export) {
 *     Export.init('{{uniqid}}');
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
 * Public helper to prepare an archive export.
 *
 * @param {String} rootId Root id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<void>}
 */
export const prepare = (rootId, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return prepareExport(root, options);
};

/**
 * Public helper to refresh archive export status.
 *
 * @param {String} rootId Root id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<Object|null>}
 */
export const refreshStatusFor = (rootId, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve(null);
    }

    return refreshStatus(root, options);
};

/**
 * Public helper to refresh archive export panel.
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

    return refreshPanel(root, options);
};

/**
 * Public helper to stop status polling.
 *
 * @param {String} rootId Root id.
 */
export const stop = rootId => {
    const root = document.getElementById(rootId);

    if (root) {
        stopPolling(root);
    }
};