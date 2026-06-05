/**
 * Public catalogue explorer interactions for local_uckk.
 *
 * Technical selectors, AMD module name, query keys and service method names keep
 * the mediatheque identifiers because they are part of the existing integration
 * contract. Public-facing wording should describe the interface as the catalogue
 * or public explorer to avoid repeating "Médiathèque" throughout the page.
 *
 * This module is UI-only:
 * - reads public search/filter controls;
 * - calls the public mediatheque search service;
 * - updates result regions, pagination and accessible status messages;
 * - keeps the query string in sync with the current filters.
 *
 * It must not authorize access, infer permissions, reveal restricted data,
 * validate cultural protocols, decide redaction, or construct private file URLs.
 * All filtering and policy decisions must happen server-side.
 *
 * @module     local_uckk/mediatheque_explorer
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

const COMPONENT = 'local_uckk';

const DEFAULT_METHOD = 'mod_uckkarchive_search_mediatheque';

const SELECTORS = {
    root: '[data-region="local-uckk-mediatheque-explorer"]',
    form: '[data-region="mediatheque-search-form"]',
    results: '[data-region="mediatheque-results"]',
    status: '[data-region="mediatheque-status"]',
    empty: '[data-region="mediatheque-empty"]',
    count: '[data-region="mediatheque-count"]',
    loadMore: '[data-action="mediatheque-load-more"]',
    reset: '[data-action="mediatheque-reset"]',
    input: 'input, select, textarea',
};

const DEFAULT_STATE = {
    cmid: 0,
    archiveid: 0,
    q: '',
    type: 'all',
    mediatype: 'all',
    collection: '',
    tag: '',
    source: '',
    advisory: 'all',
    cultural: 'all',
    audience: 'all',
    lang: '',
    validation: 'all',
    sort: 'relevance',
    page: 1,
    perpage: 12,
};

const URL_STATE_KEYS = [
    'q',
    'type',
    'mediatype',
    'collection',
    'tag',
    'source',
    'advisory',
    'cultural',
    'audience',
    'lang',
    'validation',
    'sort',
    'page',
    'perpage',
];

const FILTER_KEYS = [
    'type',
    'mediatype',
    'collection',
    'tag',
    'source',
    'advisory',
    'cultural',
    'audience',
    'lang',
    'validation',
];

const DEBOUNCE_DELAY = 300;

/**
 * Convert a value to a non-negative integer.
 *
 * @param {*} value Raw value.
 * @param {number} fallback Fallback value.
 * @returns {number}
 */
const toNonNegativeInt = (value, fallback) => {
    const parsed = parseInt(value, 10);
    return Number.isFinite(parsed) && parsed >= 0 ? parsed : fallback;
};

/**
 * Convert a value to a positive integer.
 *
 * @param {*} value Raw value.
 * @param {number} fallback Fallback value.
 * @returns {number}
 */
const toPositiveInt = (value, fallback) => {
    const parsed = parseInt(value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
};

/**
 * Safely parse a JSON dataset value.
 *
 * @param {string|undefined} value Dataset value.
 * @param {*} fallback Fallback value.
 * @returns {*}
 */
const parseJson = (value, fallback) => {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        return fallback;
    }
};

/**
 * Debounce helper.
 *
 * @param {Function} callback Callback.
 * @param {number} delay Delay in ms.
 * @returns {Function}
 */
const debounce = (callback, delay = DEBOUNCE_DELAY) => {
    let timer = null;

    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => callback(...args), delay);
    };
};

/**
 * Resolve the Moodle external service method for this explorer instance.
 *
 * The default method name remains "mod_uckkarchive_search_mediatheque" because
 * it is a technical contract, not a public label.
 *
 * @param {HTMLElement} root Root element.
 * @returns {string}
 */
const getMethod = root => root.dataset.method || DEFAULT_METHOD;

/**
 * Get base state from defaults, dataset and initial state.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const getBaseState = root => {
    const state = Object.assign({}, DEFAULT_STATE, parseJson(root.dataset.initialState, {}));

    if (typeof root.dataset.cmid !== 'undefined') {
        state.cmid = root.dataset.cmid;
    }

    if (typeof root.dataset.archiveid !== 'undefined') {
        state.archiveid = root.dataset.archiveid;
    }

    state.cmid = toNonNegativeInt(state.cmid, 0);
    state.archiveid = toNonNegativeInt(state.archiveid, 0);
    state.page = toPositiveInt(state.page, DEFAULT_STATE.page);
    state.perpage = toPositiveInt(state.perpage, DEFAULT_STATE.perpage);

    return state;
};

/**
 * Read the current explorer state from form controls and dataset defaults.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Object}
 */
const readState = root => {
    const state = getBaseState(root);
    const form = root.querySelector(SELECTORS.form);

    if (!form) {
        return state;
    }

    const data = new FormData(form);

    Object.keys(DEFAULT_STATE).forEach(key => {
        if (data.has(key)) {
            state[key] = String(data.get(key) || '').trim();
        }
    });

    state.cmid = toNonNegativeInt(state.cmid, 0);
    state.archiveid = toNonNegativeInt(state.archiveid, 0);
    state.page = toPositiveInt(state.page, DEFAULT_STATE.page);
    state.perpage = toPositiveInt(state.perpage, DEFAULT_STATE.perpage);

    return state;
};

/**
 * Write state back to form controls.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} state State.
 */
const writeState = (root, state) => {
    const form = root.querySelector(SELECTORS.form);

    if (!form) {
        return;
    }

    Object.keys(state).forEach(key => {
        const field = form.elements[key];

        if (!field) {
            return;
        }

        field.value = state[key] === null || typeof state[key] === 'undefined' ? '' : String(state[key]);
    });
};

/**
 * Update the browser query string.
 *
 * Internal scoping values such as cmid and archiveid are intentionally not
 * written to the public query string.
 *
 * @param {Object} state Current state.
 */
const updateUrl = state => {
    if (!window.history || !window.history.replaceState) {
        return;
    }

    const url = new URL(window.location.href);

    URL_STATE_KEYS.forEach(key => {
        const value = state[key];

        if (
            value === '' ||
            value === null ||
            typeof value === 'undefined' ||
            String(value) === String(DEFAULT_STATE[key])
        ) {
            url.searchParams.delete(key);
            return;
        }

        url.searchParams.set(key, String(value));
    });

    window.history.replaceState({}, '', url.toString());
};

/**
 * Build public filter payload for the external service.
 *
 * @param {Object} state Current state.
 * @returns {Object}
 */
const buildFilters = state => {
    const filters = {};

    FILTER_KEYS.forEach(key => {
        const value = state[key];

        if (value === null || typeof value === 'undefined' || value === '') {
            return;
        }

        filters[key] = value;
    });

    return filters;
};

/**
 * Build canonical service args.
 *
 * The public endpoint contract is:
 * cmid, archiveid, query, filters, page, perpage, sort.
 *
 * @param {Object} state Current state.
 * @returns {Object}
 */
const buildServiceArgs = state => ({
    cmid: toNonNegativeInt(state.cmid, 0),
    archiveid: toNonNegativeInt(state.archiveid, 0),
    query: state.q || '',
    filters: buildFilters(state),
    page: toPositiveInt(state.page, DEFAULT_STATE.page),
    perpage: toPositiveInt(state.perpage, DEFAULT_STATE.perpage),
    sort: state.sort || DEFAULT_STATE.sort,
});

/**
 * Set loading state.
 *
 * @param {HTMLElement} root Root element.
 * @param {boolean} loading Whether loading.
 */
const setLoading = (root, loading) => {
    root.classList.toggle('is-loading', loading);
    root.setAttribute('aria-busy', loading ? 'true' : 'false');

    const form = root.querySelector(SELECTORS.form);
    if (form) {
        form.querySelectorAll(SELECTORS.input).forEach(input => {
            input.disabled = loading && input.dataset.keepEnabled !== '1';
        });
    }

    const loadMore = root.querySelector(SELECTORS.loadMore);
    if (loadMore) {
        loadMore.disabled = loading;
    }
};

/**
 * Set accessible status text.
 *
 * @param {HTMLElement} root Root element.
 * @param {string} message Message.
 */
const setStatus = (root, message) => {
    const status = root.querySelector(SELECTORS.status);

    if (status) {
        status.textContent = message || '';
    }
};

/**
 * Set result count if present.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} pagination Pagination payload.
 */
const setCount = (root, pagination = {}) => {
    const count = root.querySelector(SELECTORS.count);

    if (!count) {
        return;
    }

    const total = toNonNegativeInt(pagination.total, 0);
    count.textContent = String(total);
};

/**
 * Get a catalogue result-count message.
 *
 * Prefer catalogue_* string identifiers for the public wording. Fall back to
 * legacy mediatheque_* identifiers so older language packs keep working.
 *
 * @param {number} total Total result count.
 * @returns {Promise<string>}
 */
const getResultCountMessage = async total => {
    const catalogueKey = total === 1 ? 'catalogue_result_count_one' : 'catalogue_result_count_many';
    const legacyKey = total === 1 ? 'mediatheque_result_count_one' : 'mediatheque_result_count_many';
    const fallback = `${total} résultat(s)`;

    try {
        return await getString(catalogueKey, COMPONENT, total);
    } catch (catalogueError) {
        try {
            return await getString(legacyKey, COMPONENT, total);
        } catch (legacyError) {
            return fallback;
        }
    }
};

/**
 * Render a fallback public card when the service returns structured items
 * without rendered HTML.
 *
 * This fallback intentionally uses only public-safe fields.
 *
 * @param {Object} item Public item DTO.
 * @returns {string}
 */
const renderFallbackCard = item => {
    const title = item.title || '';
    const summary = item.summary || '';
    const type = item.objecttype || item.mediatype || '';
    const detailUrl = item.detailurl || '#';

    const escape = value => {
        const span = document.createElement('span');
        span.textContent = String(value || '');
        return span.innerHTML;
    };

    return [
        '<article class="local-uckk-mediatheque-card">',
        '<div class="local-uckk-mediatheque-card__body">',
        type ? `<p class="local-uckk-mediatheque-card__eyebrow">${escape(type)}</p>` : '',
        `<h3 class="local-uckk-mediatheque-card__title"><a href="${escape(detailUrl)}">${escape(title)}</a></h3>`,
        summary ? `<p class="local-uckk-mediatheque-card__summary">${escape(summary)}</p>` : '',
        '</div>',
        '</article>',
    ].join('');
};

/**
 * Build result HTML from the public search response.
 *
 * Supported response forms:
 * - response.html
 * - response.resultshtml
 * - response.items[].html
 * - response.items[] structured DTO fallback
 *
 * @param {Object} response Service response.
 * @returns {string}
 */
const getResultsHtml = response => {
    if (response.html) {
        return response.html;
    }

    if (response.resultshtml) {
        return response.resultshtml;
    }

    if (!Array.isArray(response.items)) {
        return '';
    }

    return response.items.map(item => item.html || renderFallbackCard(item)).join('');
};

/**
 * Apply a service response to the UI.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} response Service response.
 * @param {boolean} append Whether to append instead of replace.
 */
const applyResponse = async(root, response, append) => {
    const results = root.querySelector(SELECTORS.results);
    const empty = root.querySelector(SELECTORS.empty);
    const pagination = response.pagination || {};
    const html = getResultsHtml(response);
    const isEmpty = !html;

    if (results) {
        if (append) {
            results.insertAdjacentHTML('beforeend', html);
        } else {
            results.innerHTML = html;
        }
    }

    if (empty) {
        empty.hidden = !isEmpty;
    }

    root.classList.toggle('is-empty', isEmpty);
    setCount(root, pagination);

    const loadMore = root.querySelector(SELECTORS.loadMore);
    if (loadMore) {
        loadMore.hidden = !pagination.hasmore;
        loadMore.dataset.nextPage = String(toPositiveInt(pagination.page, 1) + 1);
    }

    if (response.statusmessage) {
        setStatus(root, response.statusmessage);
    } else {
        const total = toNonNegativeInt(pagination.total, 0);
        setStatus(root, await getResultCountMessage(total));
    }
};

/**
 * Execute a search.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} overrides State overrides.
 * @param {boolean} append Whether to append.
 * @returns {Promise<void>}
 */
const search = async(root, overrides = {}, append = false) => {
    const state = Object.assign({}, readState(root), overrides);

    if (!append) {
        state.page = toPositiveInt(state.page, 1);
    }

    state.cmid = toNonNegativeInt(state.cmid, 0);
    state.archiveid = toNonNegativeInt(state.archiveid, 0);
    state.page = toPositiveInt(state.page, DEFAULT_STATE.page);
    state.perpage = toPositiveInt(state.perpage, DEFAULT_STATE.perpage);

    writeState(root, state);
    updateUrl(state);
    setLoading(root, true);

    try {
        const response = await Ajax.call([{
            methodname: getMethod(root),
            args: buildServiceArgs(state),
        }])[0];

        await applyResponse(root, response || {}, append);
    } catch (error) {
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Reset the explorer while preserving fixed scope values from the root element.
 *
 * @param {HTMLElement} root Root element.
 */
const reset = root => {
    const base = getBaseState(root);
    const state = Object.assign({}, DEFAULT_STATE, {
        cmid: base.cmid,
        archiveid: base.archiveid,
    });

    writeState(root, state);
    search(root, state, false);
};

/**
 * Bind events.
 *
 * @param {HTMLElement} root Root element.
 */
const bindEvents = root => {
    const form = root.querySelector(SELECTORS.form);
    const debouncedSearch = debounce(() => search(root, {page: 1}, false));

    if (form) {
        form.addEventListener('submit', event => {
            event.preventDefault();
            search(root, {page: 1}, false);
        });

        form.addEventListener('input', event => {
            if (event.target.matches('[type="search"], [data-live-search="1"]')) {
                debouncedSearch();
            }
        });

        form.addEventListener('change', event => {
            if (event.target.matches(SELECTORS.input)) {
                search(root, {page: 1}, false);
            }
        });
    }

    root.addEventListener('click', event => {
        const loadMore = event.target.closest(SELECTORS.loadMore);
        if (loadMore) {
            event.preventDefault();
            search(root, {page: toPositiveInt(loadMore.dataset.nextPage, 1)}, true);
            return;
        }

        const resetButton = event.target.closest(SELECTORS.reset);
        if (resetButton) {
            event.preventDefault();
            reset(root);
        }
    });
};

/**
 * Initialize one explorer root.
 *
 * @param {HTMLElement} root Root element.
 * @param {Object} options Options.
 */
const initRoot = (root, options = {}) => {
    if (!root || root.dataset.mediathequeExplorerInit === '1') {
        return;
    }

    root.dataset.mediathequeExplorerInit = '1';

    if (options.method) {
        root.dataset.method = options.method;
    }

    if (typeof options.cmid !== 'undefined') {
        root.dataset.cmid = String(toNonNegativeInt(options.cmid, 0));
    }

    if (typeof options.archiveid !== 'undefined') {
        root.dataset.archiveid = String(toNonNegativeInt(options.archiveid, 0));
    }

    if (options.initialState) {
        root.dataset.initialState = JSON.stringify(Object.assign(
            {},
            parseJson(root.dataset.initialState, {}),
            options.initialState
        ));
    }

    bindEvents(root);

    if (root.dataset.autoload === '1') {
        search(root, {}, false);
    }
};

/**
 * Initialize public catalogue explorer.
 *
 * The exported AMD name remains local_uckk/mediatheque_explorer for backwards
 * compatibility with existing templates and Moodle build output.
 *
 * Moodle js_call_amd calls this module as:
 *
 *     amd.init(initialState)
 *
 * In that case the first argument is a plain configuration object, not a DOM
 * root. This initializer also keeps support for the older forms:
 *
 *     init()
 *     init(selector, options)
 *     init(element, options)
 *
 * @param {string|HTMLElement|Object} rootSelector Root selector, element, or Moodle initial state.
 * @param {Object} options Init options.
 */
export const init = (rootSelector = SELECTORS.root, options = {}) => {
    if (
        rootSelector &&
        typeof rootSelector === 'object' &&
        typeof rootSelector.querySelector !== 'function'
    ) {
        const initialState = rootSelector;
        const root = initialState.rootId ? document.getElementById(initialState.rootId) : null;
        const initOptions = {
            method: initialState.service || initialState.method || DEFAULT_METHOD,
            cmid: initialState.cmid,
            archiveid: initialState.archiveid,
            initialState,
        };

        if (root) {
            initRoot(root, initOptions);
            return;
        }

        document.querySelectorAll(SELECTORS.root).forEach(element => initRoot(element, initOptions));
        return;
    }

    if (typeof rootSelector === 'string') {
        document.querySelectorAll(rootSelector).forEach(root => initRoot(root, options));
        return;
    }

    initRoot(rootSelector, options);
};

export default {
    init,
    search,
};