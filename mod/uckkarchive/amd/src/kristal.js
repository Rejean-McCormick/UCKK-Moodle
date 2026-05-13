/**
 * Kristal interactions for mod_uckkarchive.
 *
 * This module is UI-only:
 * - filters Kristal cards;
 * - expands and collapses Kristal details;
 * - refreshes Kristal panels through declared Ajax services;
 * - handles save/submit form calls when a page chooses to expose them;
 * - confirms server-side actions.
 *
 * It must not validate Kristals, revise archive records, decide provenance,
 * change visibility, export packages, delete evidence, or perform integrity
 * workflow decisions locally.
 *
 * @module     mod_uckkarchive/kristal
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkarchive';

const DEFAULT_METHODS = {
    getKristals: 'mod_uckkarchive_get_kristals',
    getKristal: 'mod_uckkarchive_get_kristal',
    saveDraft: 'mod_uckkarchive_save_kristal_draft',
    submitKristal: 'mod_uckkarchive_submit_kristal',
    refreshCard: 'mod_uckkarchive_get_kristal_card',
};

const SELECTORS = {
    root: '[data-region="uckkarchive-kristals"]',
    panel: '[data-region="uckkarchive-kristal-panel"]',
    list: '[data-region="uckkarchive-kristal-list"]',
    status: '[data-region="uckkarchive-kristal-status"]',

    card: '[data-region="uckkarchive-kristal"]',
    details: '[data-region="uckkarchive-kristal-details"]',
    preview: '[data-region="uckkarchive-kristal-preview"]',

    form: '[data-region="uckkarchive-kristal-form"]',
    titleInput: '[data-field="kristal-title"]',
    summaryInput: '[data-field="kristal-summary"]',
    contentInput: '[data-field="kristal-content"]',
    typeInput: '[data-field="kristal-type"]',
    statusInput: '[data-field="kristal-status"]',
    visibilityInput: '[data-field="kristal-visibility"]',
    validationInput: '[data-field="kristal-validation"]',
    provenanceInput: '[data-field="kristal-provenance"]',
    sourceComponentInput: '[data-field="kristal-source-component"]',
    sourceIdInput: '[data-field="kristal-source-id"]',

    search: '[data-action="uckkarchive-kristal-search"]',
    filter: '[data-action="uckkarchive-kristal-filter"]',
    filterButton: '[data-action="uckkarchive-kristal-filter-button"]',
    clearFilters: '[data-action="uckkarchive-kristal-clear-filters"]',

    toggle: '[data-action="uckkarchive-kristal-toggle"]',
    previewToggle: '[data-action="uckkarchive-kristal-toggle-preview"]',

    refresh: '[data-action="uckkarchive-kristal-refresh"]',
    refreshCard: '[data-action="uckkarchive-kristal-refresh-card"]',
    saveDraft: '[data-action="uckkarchive-kristal-save-draft"]',
    submit: '[data-action="uckkarchive-kristal-submit"]',
    confirmAction: '[data-action="uckkarchive-kristal-confirm-action"]',

    countVisible: '[data-region="uckkarchive-kristal-count-visible"]',
    countTotal: '[data-region="uckkarchive-kristal-count-total"]',
    emptyFiltered: '[data-region="uckkarchive-kristal-empty-filtered"]',
};

const CLASSES = {
    loading: 'is-loading',
    dirty: 'is-dirty',
    saved: 'is-saved',
    error: 'has-error',
    hidden: 'd-none',
    expanded: 'is-expanded',
    filteredOut: 'is-filtered-out',
};

const ATTRIBUTES = {
    initialised: 'data-uckkarchive-kristal-initialised',
};

let autosaveTimers = new WeakMap();

/**
 * Get a numeric dataset value.
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
 * Resolve method names from data attributes or options.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Object}
 */
const getMethods = (root, options = {}) => {
    return {
        getKristals: root.dataset.getKristalsMethod || options.getKristalsMethod || DEFAULT_METHODS.getKristals,
        getKristal: root.dataset.getKristalMethod || options.getKristalMethod || DEFAULT_METHODS.getKristal,
        saveDraft: root.dataset.saveDraftMethod || options.saveDraftMethod || DEFAULT_METHODS.saveDraft,
        submitKristal: root.dataset.submitMethod || options.submitMethod || DEFAULT_METHODS.submitKristal,
        refreshCard: root.dataset.refreshCardMethod || options.refreshCardMethod || DEFAULT_METHODS.refreshCard,
    };
};

/**
 * Base Ajax args for this region.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const getBaseArgs = root => {
    return {
        cmid: getNumberData(root, 'cmid'),
        archiveid: getNumberData(root, 'archiveid'),
        courseid: getNumberData(root, 'courseid'),
        contextid: getNumberData(root, 'contextid'),
    };
};

/**
 * Check required identifiers.
 *
 * @param {Object} args Ajax args.
 * @returns {Boolean}
 */
const hasRequiredIdentifiers = args => Boolean(args.cmid && args.archiveid);

/**
 * Set status text.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} message Message.
 */
const setStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Call one Moodle Ajax service.
 *
 * @param {String} methodname Service name.
 * @param {Object} args Service args.
 * @returns {Promise<Object>}
 */
const callService = (methodname, args) => {
    return Ajax.call([{
        methodname,
        args,
    }])[0];
};

/**
 * Return all visible/filterable Kristal cards.
 *
 * @param {HTMLElement} root Root element.
 * @returns {HTMLElement[]}
 */
const getCards = root => Array.from(root.querySelectorAll(SELECTORS.card));

/**
 * Get currently active filters.
 *
 * @param {HTMLElement} root Root element.
 * @returns {{search: String, filters: Object}}
 */
const getActiveFilters = root => {
    const search = root.querySelector(SELECTORS.search);
    const filters = {};

    root.querySelectorAll(SELECTORS.filter).forEach(field => {
        const key = field.dataset.filterKey || field.name || '';

        if (!key) {
            return;
        }

        if (field.type === 'checkbox') {
            if (field.checked) {
                filters[key] = field.value || '1';
            }
            return;
        }

        if (field.value !== '') {
            filters[key] = field.value;
        }
    });

    root.querySelectorAll(SELECTORS.filterButton).forEach(button => {
        if (button.getAttribute('aria-pressed') !== 'true') {
            return;
        }

        const key = button.dataset.filterKey || '';
        const value = button.dataset.filterValue || '';

        if (key && value) {
            filters[key] = value;
        }
    });

    return {
        search: search ? search.value.trim().toLowerCase() : '',
        filters,
    };
};

/**
 * Convert filter key to dataset key.
 *
 * @param {String} key Filter key.
 * @returns {String}
 */
const filterKeyToDatasetKey = key => {
    return key.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
};

/**
 * Whether a card matches active filters.
 *
 * @param {HTMLElement} card Kristal card.
 * @param {String} search Search query.
 * @param {Object} filters Filters.
 * @returns {Boolean}
 */
const matchesFilters = (card, search, filters) => {
    if (search) {
        const haystack = [
            card.textContent || '',
            card.dataset.title || '',
            card.dataset.summary || '',
            card.dataset.kristalType || '',
            card.dataset.status || '',
            card.dataset.visibility || '',
            card.dataset.validation || '',
            card.dataset.provenance || '',
            card.dataset.sourceComponent || '',
        ].join(' ').toLowerCase();

        if (!haystack.includes(search)) {
            return false;
        }
    }

    return Object.entries(filters).every(([key, value]) => {
        if (value === '*' || value === 'all') {
            return true;
        }

        const datasetKey = filterKeyToDatasetKey(key);
        return (card.dataset[datasetKey] || '') === value;
    });
};

/**
 * Apply current filters to Kristal cards.
 *
 * @param {HTMLElement} root Root element.
 */
const applyFilters = root => {
    const {search, filters} = getActiveFilters(root);
    const cards = getCards(root);
    let visible = 0;

    cards.forEach(card => {
        const match = matchesFilters(card, search, filters);

        card.hidden = !match;
        card.classList.toggle(CLASSES.hidden, !match);
        card.classList.toggle(CLASSES.filteredOut, !match);

        if (match) {
            visible++;
        }
    });

    updateCounts(root, visible, cards.length);
};

/**
 * Update visible and total counts.
 *
 * @param {HTMLElement} root Root element.
 * @param {Number} visible Visible count.
 * @param {Number} total Total count.
 */
const updateCounts = (root, visible, total) => {
    const visibleNode = root.querySelector(SELECTORS.countVisible);
    const totalNode = root.querySelector(SELECTORS.countTotal);
    const emptyNode = root.querySelector(SELECTORS.emptyFiltered);

    if (visibleNode) {
        visibleNode.textContent = String(visible);
    }

    if (totalNode) {
        totalNode.textContent = String(total);
    }

    if (emptyNode) {
        const showEmpty = total > 0 && visible === 0;
        emptyNode.hidden = !showEmpty;
        emptyNode.classList.toggle(CLASSES.hidden, !showEmpty);
    }
};

/**
 * Clear filters.
 *
 * @param {HTMLElement} root Root element.
 */
const clearFilters = root => {
    const search = root.querySelector(SELECTORS.search);

    if (search) {
        search.value = '';
    }

    root.querySelectorAll(SELECTORS.filter).forEach(field => {
        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = false;
        } else {
            field.value = '';
        }
    });

    root.querySelectorAll(SELECTORS.filterButton).forEach(button => {
        button.setAttribute('aria-pressed', 'false');
        button.classList.remove('active');
    });

    applyFilters(root);
};

/**
 * Toggle a filter button group.
 *
 * @param {HTMLElement} root Root element.
 * @param {HTMLElement} button Button.
 */
const toggleFilterButton = (root, button) => {
    const key = button.dataset.filterKey || '';

    if (!key) {
        return;
    }

    const pressed = button.getAttribute('aria-pressed') === 'true';

    root.querySelectorAll(`${SELECTORS.filterButton}[data-filter-key="${key}"]`).forEach(peer => {
        peer.setAttribute('aria-pressed', 'false');
        peer.classList.remove('active');
    });

    button.setAttribute('aria-pressed', pressed ? 'false' : 'true');
    button.classList.toggle('active', !pressed);

    applyFilters(root);
};

/**
 * Toggle a Kristal detail panel.
 *
 * @param {HTMLElement} trigger Trigger.
 */
const toggleDetails = trigger => {
    const card = trigger.closest(SELECTORS.card);

    if (!card) {
        return;
    }

    const details = card.querySelector(SELECTORS.details);

    if (!details) {
        return;
    }

    const expanded = trigger.getAttribute('aria-expanded') === 'true';
    const nextExpanded = !expanded;

    trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
    details.hidden = !nextExpanded;
    details.classList.toggle(CLASSES.hidden, !nextExpanded);
    card.classList.toggle(CLASSES.expanded, nextExpanded);
};

/**
 * Toggle a Kristal preview panel.
 *
 * @param {HTMLElement} trigger Trigger.
 */
const togglePreview = trigger => {
    const card = trigger.closest(SELECTORS.card);

    if (!card) {
        return;
    }

    const preview = card.querySelector(SELECTORS.preview);

    if (!preview) {
        return;
    }

    const expanded = trigger.getAttribute('aria-expanded') === 'true';
    const nextExpanded = !expanded;

    trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
    preview.hidden = !nextExpanded;
    preview.classList.toggle(CLASSES.hidden, !nextExpanded);
};

/**
 * Collect form field value.
 *
 * @param {HTMLElement|null} field Field.
 * @returns {String|Number|Boolean}
 */
const getFieldValue = field => {
    if (!field) {
        return '';
    }

    if (field.type === 'checkbox') {
        return field.checked;
    }

    if (field.type === 'number' || field.dataset.valueType === 'number') {
        const value = Number(field.value);
        return Number.isFinite(value) ? value : 0;
    }

    return field.value;
};

/**
 * Collect one Kristal form payload.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const collectKristalData = root => {
    const form = root.querySelector(SELECTORS.form);

    return {
        ...getBaseArgs(root),
        kristalid: getNumberData(root, 'kristalid') || getNumberData(form, 'kristalid'),
        itemid: getNumberData(root, 'itemid') || getNumberData(form, 'itemid'),
        proofid: getNumberData(root, 'proofid') || getNumberData(form, 'proofid'),
        title: getFieldValue(root.querySelector(SELECTORS.titleInput)),
        summary: getFieldValue(root.querySelector(SELECTORS.summaryInput)),
        content: getFieldValue(root.querySelector(SELECTORS.contentInput)),
        kristaltype: getFieldValue(root.querySelector(SELECTORS.typeInput)),
        status: getFieldValue(root.querySelector(SELECTORS.statusInput)),
        visibility: getFieldValue(root.querySelector(SELECTORS.visibilityInput)),
        validationstate: getFieldValue(root.querySelector(SELECTORS.validationInput)),
        provenance: getFieldValue(root.querySelector(SELECTORS.provenanceInput)),
        sourcecomponent: getFieldValue(root.querySelector(SELECTORS.sourceComponentInput)),
        sourceid: getFieldValue(root.querySelector(SELECTORS.sourceIdInput)),
        sesskey: form?.dataset?.sesskey || M.cfg.sesskey,
    };
};

/**
 * Validate minimum client-side requirements before submit.
 *
 * Server-side validation remains authoritative.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Promise<Boolean>}
 */
const validateBeforeSubmit = async(root) => {
    const data = collectKristalData(root);

    if (!hasRequiredIdentifiers(data)) {
        await Notification.alert(
            await getString('kristalerror', COMPONENT),
            await getString('archivemissingidentifiers', COMPONENT)
        );
        return false;
    }

    if (!String(data.title || '').trim()) {
        await Notification.alert(
            await getString('kristalerror', COMPONENT),
            await getString('kristalmissingtitle', COMPONENT)
        );
        return false;
    }

    if (!String(data.content || '').trim()) {
        await Notification.alert(
            await getString('kristalerror', COMPONENT),
            await getString('kristalmissingcontent', COMPONENT)
        );
        return false;
    }

    return true;
};

/**
 * Mark form region dirty.
 *
 * @param {HTMLElement} root Root element.
 */
const markDirty = root => {
    root.classList.add(CLASSES.dirty);
    root.classList.remove(CLASSES.saved);
};

/**
 * Mark form region saved.
 *
 * @param {HTMLElement} root Root element.
 */
const markSaved = root => {
    root.classList.remove(CLASSES.dirty);
    root.classList.add(CLASSES.saved);
};

/**
 * Save a Kristal draft.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const saveDraft = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = collectKristalData(root);

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('kristalsaving', COMPONENT));

        const response = await callService(methods.saveDraft, args);

        if (response?.kristalid) {
            root.dataset.kristalid = String(response.kristalid);
        }

        if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        markSaved(root);
        setStatus(root, await getString('kristalsaved', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('kristalsavefailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Submit a Kristal to server-side workflow.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const submitKristal = async(root, options = {}) => {
    const valid = await validateBeforeSubmit(root);

    if (!valid) {
        return;
    }

    const confirmed = await confirmAction({
        dataset: {
            confirmTitle: await getString('confirmkristalsubmit', COMPONENT),
            confirmBody: await getString('confirmkristalsubmitbody', COMPONENT),
        },
    });

    if (!confirmed) {
        return;
    }

    const methods = getMethods(root, options);
    const args = collectKristalData(root);

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('kristalsubmitting', COMPONENT));

        const response = await callService(methods.submitKristal, args);

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        markSaved(root);
        setStatus(root, await getString('kristalsubmitted', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('kristalsubmitfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Refresh all Kristals for a region.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshKristals = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = getBaseArgs(root);

    if (!hasRequiredIdentifiers(args)) {
        await Notification.alert(
            await getString('kristalerror', COMPONENT),
            await getString('archivemissingidentifiers', COMPONENT)
        );
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('kristalrefreshing', COMPONENT));

        const response = await callService(methods.getKristals, args);

        if (response?.template && response?.context) {
            const html = await Templates.render(response.template, response.context);
            replacePanel(root, html, response.js || '');
        } else if (response?.html) {
            replacePanel(root, response.html, response.js || '');
        }

        applyFilters(root);
        setStatus(root, await getString('kristalrefreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('kristalrefreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Refresh a single Kristal card.
 *
 * @param {HTMLElement} root Root element.
 * @param {HTMLElement} trigger Trigger.
 * @param {Object} options Init options.
 * @returns {Promise<void>}
 */
const refreshKristalCard = async(root, trigger, options = {}) => {
    const card = trigger.closest(SELECTORS.card);
    const kristalid = getNumberData(trigger, 'kristalid') || getNumberData(card, 'kristalid');

    if (!kristalid) {
        return;
    }

    const methods = getMethods(root, options);
    const args = {
        ...getBaseArgs(root),
        kristalid,
    };

    card?.classList.add(CLASSES.loading);

    try {
        const response = await callService(methods.refreshCard, args);

        if (card && response?.template && response?.context) {
            const html = await Templates.render(response.template, response.context);
            Templates.replaceNode(card, html, response.js || '');
        } else if (card && response?.html) {
            Templates.replaceNode(card, response.html, response.js || '');
        }

        if (response?.js) {
            Templates.runTemplateJS(response.js);
        }

        applyFilters(root);
    } catch (error) {
        Notification.exception(error);
    } finally {
        card?.classList.remove(CLASSES.loading);
    }
};

/**
 * Replace panel contents.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} html HTML.
 * @param {String} js Template JS.
 */
const replacePanel = (root, html, js = '') => {
    const panel = root.querySelector(SELECTORS.panel) || root.querySelector(SELECTORS.list);

    if (!panel) {
        return;
    }

    Templates.replaceNodeContents(panel, html, js);

    if (js) {
        Templates.runTemplateJS(js);
    }
};

/**
 * Confirm a server-side action.
 *
 * @param {HTMLElement|Object} trigger Trigger or trigger-like object.
 * @returns {Promise<Boolean>}
 */
const confirmAction = async(trigger) => {
    const title = trigger.dataset.confirmTitle || await getString('confirmarchiveaction', COMPONENT);
    const body = trigger.dataset.confirmBody || await getString('confirmarchiveactionbody', COMPONENT);
    const confirmLabel = trigger.dataset.confirmButton || await getString('confirm', 'moodle');
    const cancelLabel = trigger.dataset.cancelButton || await getString('cancel', 'moodle');

    return new Promise(resolve => {
        Notification.confirm(
            title,
            body,
            confirmLabel,
            cancelLabel,
            () => resolve(true),
            () => resolve(false)
        );
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

    const form = root.querySelector(SELECTORS.form);

    if (!form) {
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
 * Handle form/filter changes.
 *
 * @param {Event} event Event.
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const handleChange = (event, root, options = {}) => {
    if (
        event.target.closest(SELECTORS.search)
        || event.target.closest(SELECTORS.filter)
    ) {
        applyFilters(root);
        return;
    }

    if (event.target.closest(SELECTORS.form)) {
        markDirty(root);
        scheduleAutosave(root, options);
    }
};

/**
 * Handle click actions.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const handleClick = async(event, root, options = {}) => {
    const clearButton = event.target.closest(SELECTORS.clearFilters);
    if (clearButton) {
        event.preventDefault();
        clearFilters(root);
        return;
    }

    const filterButton = event.target.closest(SELECTORS.filterButton);
    if (filterButton) {
        event.preventDefault();
        toggleFilterButton(root, filterButton);
        return;
    }

    const toggleButton = event.target.closest(SELECTORS.toggle);
    if (toggleButton) {
        event.preventDefault();
        toggleDetails(toggleButton);
        return;
    }

    const previewButton = event.target.closest(SELECTORS.previewToggle);
    if (previewButton) {
        event.preventDefault();
        togglePreview(previewButton);
        return;
    }

    const refreshButton = event.target.closest(SELECTORS.refresh);
    if (refreshButton) {
        event.preventDefault();
        await refreshKristals(root, options);
        return;
    }

    const refreshCardButton = event.target.closest(SELECTORS.refreshCard);
    if (refreshCardButton) {
        event.preventDefault();
        await refreshKristalCard(root, refreshCardButton, options);
        return;
    }

    const saveButton = event.target.closest(SELECTORS.saveDraft);
    if (saveButton) {
        event.preventDefault();
        await saveDraft(root, options);
        return;
    }

    const submitButton = event.target.closest(SELECTORS.submit);
    if (submitButton) {
        event.preventDefault();
        await submitKristal(root, options);
        return;
    }

    const confirmTrigger = event.target.closest(SELECTORS.confirmAction);
    if (confirmTrigger) {
        const confirmed = await confirmAction(confirmTrigger);

        if (!confirmed) {
            event.preventDefault();
            event.stopPropagation();
        }
    }
};

/**
 * Initialise one Kristal root.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Init options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');

    root.addEventListener('input', event => handleChange(event, root, options));
    root.addEventListener('change', event => handleChange(event, root, options));
    root.addEventListener('click', event => {
        handleClick(event, root, options);
    });

    applyFilters(root);
};

/**
 * Initialise UCKK archive Kristal UI.
 *
 * Recommended PHP:
 * $PAGE->requires->js_call_amd('mod_uckkarchive/kristal', 'init', [$uniqid]);
 *
 * Recommended Mustache:
 * {{#js}}
 * require(['mod_uckkarchive/kristal'], function(Kristal) {
 *     Kristal.init('{{uniqid}}');
 * });
 * {{/js}}
 *
 * @param {String|null} rootId Optional root element id.
 * @param {Object} options Runtime options.
 */
export const init = (rootId = null, options = {}) => {
    const roots = rootId
        ? [document.getElementById(rootId)].filter(Boolean)
        : Array.from(document.querySelectorAll(SELECTORS.root));

    roots.forEach(root => initialiseRoot(root, options));
};

/**
 * Public helper to refresh Kristals.
 *
 * @param {String} rootId Root element id.
 * @param {Object} options Runtime options.
 * @returns {Promise<void>}
 */
export const refresh = (rootId, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return refreshKristals(root, options);
};

/**
 * Public helper to save the current Kristal draft.
 *
 * @param {String} rootId Root element id.
 * @param {Object} options Runtime options.
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
 * Public helper to reapply filters.
 *
 * @param {String} rootId Root element id.
 */
export const filter = rootId => {
    const root = document.getElementById(rootId);

    if (root) {
        applyFilters(root);
    }
};