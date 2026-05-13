/**
 * Archive interactions for mod_uckkarchive.
 *
 * This module is intentionally UI-only:
 * - filters archive item lists;
 * - refreshes item cards and provenance panels;
 * - previews export packages;
 * - dispatches declared AJAX actions;
 * - displays accessible status messages.
 *
 * It must not validate archive records, revise archive history, expose
 * restricted items, export files authoritatively, open integrity cases, or
 * replace server-side capability checks.
 *
 * @module     mod_uckkarchive/archive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

const COMPONENT = 'uckkarchive';

const DEFAULT_METHODS = {
    getArchiveItems: 'mod_uckkarchive_get_archive_items',
    getArchiveItemCard: 'mod_uckkarchive_get_archive_item_card',
    getProvenancePanel: 'mod_uckkarchive_get_provenance_panel',
    getExportPreview: 'mod_uckkarchive_get_export_preview',
    exportItems: 'mod_uckkarchive_export_items',
    getExportStatus: 'mod_uckkarchive_get_export_status',
};

const SELECTORS = {
    root: '[data-region="uckkarchive"]',
    status: '[data-region="uckkarchive-status"]',
    content: '[data-region="uckkarchive-content"]',
    itemList: '[data-region="uckkarchive-item-list"]',
    itemCard: '[data-region="uckkarchive-item-card"]',
    provenancePanel: '[data-region="uckkarchive-provenance-panel"]',
    exportPreview: '[data-region="uckkarchive-export-preview"]',

    filterForm: '[data-region="uckkarchive-filter-form"]',
    search: '[data-field="archive-search"]',
    itemType: '[data-field="archive-itemtype"]',
    statusFilter: '[data-field="archive-status"]',
    validationState: '[data-field="archive-validationstate"]',
    visibility: '[data-field="archive-visibility"]',
    provenance: '[data-field="archive-provenance"]',
    sort: '[data-field="archive-sort"]',

    itemSelector: '[data-field="archive-item-select"]',
    selectAll: '[data-action="uckkarchive-select-all"]',

    refresh: '[data-action="uckkarchive-refresh"]',
    applyFilters: '[data-action="uckkarchive-apply-filters"]',
    resetFilters: '[data-action="uckkarchive-reset-filters"]',
    loadItem: '[data-action="uckkarchive-load-item"]',
    toggleProvenance: '[data-action="uckkarchive-toggle-provenance"]',
    previewExport: '[data-action="uckkarchive-preview-export"]',
    exportSelected: '[data-action="uckkarchive-export-selected"]',
    serviceAction: '[data-action="uckkarchive-service-action"]',
};

const CLASSES = {
    active: 'is-active',
    loading: 'is-loading',
    loaded: 'is-loaded',
    dirty: 'is-dirty',
    error: 'has-error',
    hidden: 'd-none',
    selected: 'is-selected',
};

const ATTRIBUTES = {
    initialised: 'data-uckkarchive-initialised',
};

let filterTimers = new WeakMap();

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
 * Set accessible status text.
 *
 * @param {HTMLElement} root Archive root.
 * @param {String} message Message.
 */
const setStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Return external method names for this root.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Object} options Optional init options.
 * @returns {Object}
 */
const getMethods = (root, options = {}) => {
    return {
        getArchiveItems: root.dataset.getItemsMethod || options.getItemsMethod || DEFAULT_METHODS.getArchiveItems,
        getArchiveItemCard: root.dataset.getCardMethod || options.getCardMethod || DEFAULT_METHODS.getArchiveItemCard,
        getProvenancePanel: root.dataset.getProvenanceMethod || options.getProvenanceMethod || DEFAULT_METHODS.getProvenancePanel,
        getExportPreview: root.dataset.getExportPreviewMethod || options.getExportPreviewMethod || DEFAULT_METHODS.getExportPreview,
        exportItems: root.dataset.exportItemsMethod || options.exportItemsMethod || DEFAULT_METHODS.exportItems,
        getExportStatus: root.dataset.getExportStatusMethod || options.getExportStatusMethod || DEFAULT_METHODS.getExportStatus,
    };
};

/**
 * Build base service arguments.
 *
 * @param {HTMLElement} root Archive root.
 * @returns {Object}
 */
const getBaseArgs = root => {
    return {
        cmid: getNumberData(root, 'cmid'),
        archiveid: getNumberData(root, 'archiveid'),
        contextid: getNumberData(root, 'contextid'),
        sesskey: M.cfg.sesskey,
    };
};

/**
 * Check whether base identifiers are usable.
 *
 * @param {Object} args Ajax args.
 * @returns {Boolean}
 */
const hasRequiredIdentifiers = args => {
    return Boolean(args.cmid || args.archiveid);
};

/**
 * Collect current filter values.
 *
 * @param {HTMLElement} root Archive root.
 * @returns {Object}
 */
const collectFilters = root => {
    return {
        search: getFieldValue(root, SELECTORS.search),
        itemtype: getFieldValue(root, SELECTORS.itemType),
        status: getFieldValue(root, SELECTORS.statusFilter),
        validationstate: getFieldValue(root, SELECTORS.validationState),
        visibility: getFieldValue(root, SELECTORS.visibility),
        provenance: getFieldValue(root, SELECTORS.provenance),
        sort: getFieldValue(root, SELECTORS.sort),
    };
};

/**
 * Get a single field value.
 *
 * @param {HTMLElement} root Archive root.
 * @param {String} selector Field selector.
 * @returns {String}
 */
const getFieldValue = (root, selector) => {
    const field = root.querySelector(selector);

    if (!field) {
        return '';
    }

    return String(field.value ?? '').trim();
};

/**
 * Collect selected archive item IDs.
 *
 * @param {HTMLElement} root Archive root.
 * @returns {Array<Number>}
 */
const getSelectedItemIds = root => {
    return Array.from(root.querySelectorAll(SELECTORS.itemSelector))
        .filter(field => field.checked)
        .map(field => Number(field.value || field.dataset.itemid || 0))
        .filter(value => Number.isFinite(value) && value > 0);
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
 * Replace region HTML.
 *
 * @param {HTMLElement|null} region Target region.
 * @param {String} html Markup.
 * @param {String} js Template JS.
 */
const replaceRegion = (region, html, js = '') => {
    if (!region) {
        return;
    }

    Templates.replaceNodeContents(region, html, js);

    if (js) {
        Templates.runTemplateJS(js);
    }
};

/**
 * Render a service response into a region.
 *
 * @param {HTMLElement|null} region Target region.
 * @param {Object} response Service response.
 * @param {String} fallbackTemplate Template name if response has context only.
 * @returns {Promise<void>}
 */
const renderResponse = async(region, response, fallbackTemplate = '') => {
    if (!region || !response) {
        return;
    }

    if (response.html) {
        replaceRegion(region, response.html, response.js || '');
        return;
    }

    if (response.template && response.context) {
        const html = await Templates.render(response.template, response.context);
        replaceRegion(region, html, '');
        return;
    }

    if (fallbackTemplate && response.context) {
        const html = await Templates.render(fallbackTemplate, response.context);
        replaceRegion(region, html, '');
    }
};

/**
 * Refresh archive item list.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Object} options Optional init options.
 * @returns {Promise<void>}
 */
const refreshArchiveItems = async(root, options = {}) => {
    const methods = getMethods(root, options);
    const args = {
        ...getBaseArgs(root),
        filters: collectFilters(root),
    };

    if (!hasRequiredIdentifiers(args)) {
        return;
    }

    const list = root.querySelector(SELECTORS.itemList) || root.querySelector(SELECTORS.content);

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('archive:refreshing', COMPONENT));

        const response = await callService(methods.getArchiveItems, args);

        await renderResponse(list, response, 'mod_uckkarchive/archive_view');

        root.classList.add(CLASSES.loaded);
        setStatus(root, await getString('archive:refreshed', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('archive:refreshfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Load one archive item card.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Number} itemid Archive item id.
 * @param {Object} options Optional init options.
 * @returns {Promise<void>}
 */
const loadArchiveItemCard = async(root, itemid, options = {}) => {
    if (!itemid) {
        return;
    }

    const methods = getMethods(root, options);
    const args = {
        ...getBaseArgs(root),
        itemid,
    };

    const cardRegion = root.querySelector(SELECTORS.itemCard);

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('archiveitem:loading', COMPONENT));

        const response = await callService(methods.getArchiveItemCard, args);

        await renderResponse(cardRegion, response, 'mod_uckkarchive/archive_item_card');

        markActiveItem(root, itemid);
        setStatus(root, await getString('archiveitem:loaded', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('archiveitem:loadfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Load provenance panel for one archive item.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Number} itemid Archive item id.
 * @param {Object} options Optional init options.
 * @returns {Promise<void>}
 */
const loadProvenancePanel = async(root, itemid, options = {}) => {
    if (!itemid) {
        return;
    }

    const methods = getMethods(root, options);
    const args = {
        ...getBaseArgs(root),
        itemid,
    };

    const panel = root.querySelector(SELECTORS.provenancePanel);

    if (!panel) {
        return;
    }

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('provenance:loading', COMPONENT));

        const response = await callService(methods.getProvenancePanel, args);

        await renderResponse(panel, response, 'mod_uckkarchive/provenance_panel');

        panel.classList.remove(CLASSES.hidden);
        panel.hidden = false;

        setStatus(root, await getString('provenance:loaded', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('provenance:loadfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Preview selected archive export.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Object} options Optional init options.
 * @returns {Promise<void>}
 */
const previewExport = async(root, options = {}) => {
    const itemids = getSelectedItemIds(root);

    if (!itemids.length) {
        await Notification.alert(
            await getString('export:noitems:title', COMPONENT),
            await getString('export:noitems:body', COMPONENT)
        );
        return;
    }

    const methods = getMethods(root, options);
    const args = {
        ...getBaseArgs(root),
        itemids,
        format: root.dataset.exportFormat || 'json',
    };

    const region = root.querySelector(SELECTORS.exportPreview);

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('export:previewloading', COMPONENT));

        const response = await callService(methods.getExportPreview, args);

        await renderResponse(region, response, '');

        if (region) {
            region.classList.remove(CLASSES.hidden);
            region.hidden = false;
        }

        setStatus(root, await getString('export:previewloaded', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('export:previewfailed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Request export for selected items.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Object} options Optional init options.
 * @returns {Promise<void>}
 */
const exportSelected = async(root, options = {}) => {
    const itemids = getSelectedItemIds(root);

    if (!itemids.length) {
        await Notification.alert(
            await getString('export:noitems:title', COMPONENT),
            await getString('export:noitems:body', COMPONENT)
        );
        return;
    }

    const confirmed = await confirmAction(
        'export:confirm:title',
        'export:confirm:body',
        'export:confirm:button'
    );

    if (!confirmed) {
        return;
    }

    const methods = getMethods(root, options);
    const args = {
        ...getBaseArgs(root),
        itemids,
        format: root.dataset.exportFormat || 'json',
    };

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('export:starting', COMPONENT));

        const response = await callService(methods.exportItems, args);

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (response?.downloadurl) {
            window.location.assign(response.downloadurl);
            return;
        }

        await renderResponse(root.querySelector(SELECTORS.exportPreview), response, '');

        setStatus(root, await getString('export:started', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('export:failed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Dispatch a generic declared service action.
 *
 * The button must provide:
 * - data-method
 * - optional data-item-id
 * - optional data-confirm="true"
 *
 * @param {HTMLElement} root Archive root.
 * @param {HTMLElement} trigger Action trigger.
 * @returns {Promise<void>}
 */
const runServiceAction = async(root, trigger) => {
    const method = trigger.dataset.method || '';
    const itemid = getNumberData(trigger, 'itemId');

    if (!method) {
        return;
    }

    if (trigger.dataset.confirm === 'true') {
        const confirmed = await confirmAction(
            trigger.dataset.confirmTitleKey || 'action:confirm:title',
            trigger.dataset.confirmBodyKey || 'action:confirm:body',
            trigger.dataset.confirmButtonKey || 'action:confirm:button'
        );

        if (!confirmed) {
            return;
        }
    }

    const args = {
        ...getBaseArgs(root),
        itemid,
        action: trigger.dataset.serviceAction || trigger.dataset.actionKey || '',
    };

    root.classList.add(CLASSES.loading);
    root.classList.remove(CLASSES.error);

    try {
        setStatus(root, await getString('action:running', COMPONENT));

        const response = await callService(method, args);

        if (response?.redirecturl) {
            window.location.assign(response.redirecturl);
            return;
        }

        if (response?.itemid || itemid) {
            await loadArchiveItemCard(root, Number(response?.itemid || itemid));
        } else {
            await refreshArchiveItems(root);
        }

        setStatus(root, await getString('action:done', COMPONENT));
    } catch (error) {
        root.classList.add(CLASSES.error);
        setStatus(root, await getString('action:failed', COMPONENT));
        Notification.exception(error);
    } finally {
        root.classList.remove(CLASSES.loading);
    }
};

/**
 * Confirm a user action.
 *
 * @param {String} titleKey Title string key.
 * @param {String} bodyKey Body string key.
 * @param {String} buttonKey Confirm button string key.
 * @returns {Promise<Boolean>}
 */
const confirmAction = async(titleKey, bodyKey, buttonKey) => {
    const [title, body, button, cancel] = await Promise.all([
        getString(titleKey, COMPONENT),
        getString(bodyKey, COMPONENT),
        getString(buttonKey, COMPONENT),
        getString('cancel', 'moodle'),
    ]);

    return new Promise(resolve => {
        Notification.confirm(
            title,
            body,
            button,
            cancel,
            () => resolve(true),
            () => resolve(false)
        );
    });
};

/**
 * Mark one item as active in the list.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Number} itemid Archive item id.
 */
const markActiveItem = (root, itemid) => {
    root.querySelectorAll(SELECTORS.loadItem).forEach(trigger => {
        const current = getNumberData(trigger, 'itemId');
        const active = current === itemid;

        trigger.classList.toggle(CLASSES.active, active);
        trigger.setAttribute('aria-current', active ? 'true' : 'false');
    });
};

/**
 * Set all visible item checkboxes.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Boolean} checked Checked state.
 */
const setAllSelections = (root, checked) => {
    root.querySelectorAll(SELECTORS.itemSelector).forEach(field => {
        if (!field.disabled) {
            field.checked = checked;
            field.closest(SELECTORS.itemCard)?.classList.toggle(CLASSES.selected, checked);
        }
    });
};

/**
 * Schedule debounced filter refresh.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Object} options Optional init options.
 */
const scheduleFilterRefresh = (root, options = {}) => {
    const delay = Number(options.filterDelay || root.dataset.filterDelay || 350);
    const previous = filterTimers.get(root);

    if (previous) {
        window.clearTimeout(previous);
    }

    const timer = window.setTimeout(() => {
        refreshArchiveItems(root, options);
    }, Number.isFinite(delay) ? delay : 350);

    filterTimers.set(root, timer);
};

/**
 * Reset filter form.
 *
 * @param {HTMLElement} root Archive root.
 */
const resetFilters = root => {
    const form = root.querySelector(SELECTORS.filterForm);

    if (form) {
        form.reset();
    }
};

/**
 * Handle root input/change events.
 *
 * @param {Event} event Input/change event.
 * @param {HTMLElement} root Archive root.
 * @param {Object} options Optional init options.
 */
const handleChange = (event, root, options = {}) => {
    const target = event.target;

    if (target.closest(SELECTORS.filterForm)) {
        root.classList.add(CLASSES.dirty);
        scheduleFilterRefresh(root, options);
        return;
    }

    if (target.matches(SELECTORS.itemSelector)) {
        target.closest(SELECTORS.itemCard)?.classList.toggle(CLASSES.selected, target.checked);
    }
};

/**
 * Handle click events.
 *
 * @param {MouseEvent} event Click event.
 * @param {HTMLElement} root Archive root.
 * @param {Object} options Optional init options.
 */
const handleClick = (event, root, options = {}) => {
    const refresh = event.target.closest(SELECTORS.refresh);

    if (refresh) {
        event.preventDefault();
        refreshArchiveItems(root, options);
        return;
    }

    const applyFilters = event.target.closest(SELECTORS.applyFilters);

    if (applyFilters) {
        event.preventDefault();
        refreshArchiveItems(root, options);
        return;
    }

    const reset = event.target.closest(SELECTORS.resetFilters);

    if (reset) {
        event.preventDefault();
        resetFilters(root);
        refreshArchiveItems(root, options);
        return;
    }

    const loadItem = event.target.closest(SELECTORS.loadItem);

    if (loadItem) {
        event.preventDefault();
        loadArchiveItemCard(root, getNumberData(loadItem, 'itemId'), options);
        return;
    }

    const provenance = event.target.closest(SELECTORS.toggleProvenance);

    if (provenance) {
        event.preventDefault();
        loadProvenancePanel(root, getNumberData(provenance, 'itemId'), options);
        return;
    }

    const selectAll = event.target.closest(SELECTORS.selectAll);

    if (selectAll) {
        event.preventDefault();
        setAllSelections(root, selectAll.dataset.checked !== 'true');
        selectAll.dataset.checked = selectAll.dataset.checked === 'true' ? 'false' : 'true';
        return;
    }

    const preview = event.target.closest(SELECTORS.previewExport);

    if (preview) {
        event.preventDefault();
        previewExport(root, options);
        return;
    }

    const exportButton = event.target.closest(SELECTORS.exportSelected);

    if (exportButton) {
        event.preventDefault();
        exportSelected(root, options);
        return;
    }

    const serviceAction = event.target.closest(SELECTORS.serviceAction);

    if (serviceAction) {
        event.preventDefault();
        runServiceAction(root, serviceAction);
    }
};

/**
 * Initialise one archive root.
 *
 * @param {HTMLElement} root Archive root.
 * @param {Object} options Optional init options.
 */
const initialiseRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialised, 'true');

    root.addEventListener('input', event => handleChange(event, root, options));
    root.addEventListener('change', event => handleChange(event, root, options));
    root.addEventListener('click', event => handleClick(event, root, options));

    if (options.refreshOnInit || root.dataset.refreshOnInit === 'true') {
        refreshArchiveItems(root, options);
    }
};

/**
 * Initialise archive UI.
 *
 * Recommended PHP:
 * $PAGE->requires->js_call_amd('mod_uckkarchive/archive', 'init', [$uniqid]);
 *
 * Recommended Mustache:
 * {{#js}}
 * require(['mod_uckkarchive/archive'], function(Archive) {
 *     Archive.init('{{uniqid}}');
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
 * Public helper to refresh archive items.
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

    return refreshArchiveItems(root, options);
};

/**
 * Public helper to load one archive item card.
 *
 * @param {String} rootId Root element id.
 * @param {Number} itemid Archive item id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<void>}
 */
export const loadItem = (rootId, itemid, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return loadArchiveItemCard(root, Number(itemid), options);
};

/**
 * Public helper to preview export for selected items.
 *
 * @param {String} rootId Root element id.
 * @param {Object} options Optional configuration.
 * @returns {Promise<void>}
 */
export const previewSelectedExport = (rootId, options = {}) => {
    const root = document.getElementById(rootId);

    if (!root) {
        return Promise.resolve();
    }

    return previewExport(root, options);
};