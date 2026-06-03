/**
 * Public course explorer interactions for local_uckk.
 *
 * This module is UI-only:
 * - reads public search/filter controls;
 * - calls the declared public course search service;
 * - updates result regions, pagination and accessible status messages;
 * - keeps the query string in sync with the current filters.
 *
 * It must not authorize access, infer permissions, reveal hidden courses,
 * enrol users, decide completion, award recognitions, validate work,
 * or make accreditation claims. All visibility and filtering decisions
 * must happen server-side.
 *
 * @module     local_uckk/course_explorer
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

const COMPONENT = 'local_uckk';

const DEFAULT_METHOD = 'local_uckk_search_public_courses';

const SELECTORS = {
    root: '[data-region="local-uckk-course-explorer"]',
    form: '[data-region="course-search-form"]',
    results: '[data-region="course-results"]',
    status: '[data-region="course-status"]',
    empty: '[data-region="course-empty"]',
    count: '[data-region="course-count"]',
    loadMore: '[data-action="course-load-more"]',
    reset: '[data-action="course-reset"]',
    input: 'input, select, textarea',
};

const CLASSES = {
    loading: 'is-loading',
    ready: 'is-ready',
    empty: 'is-empty',
    error: 'has-error',
    hidden: 'd-none',
};

const DEFAULT_STATE = {
    q: '',
    category: 'all',
    sort: 'pedagogical',
    page: 1,
    perpage: 12,
};

const URL_STATE_KEYS = [
    'q',
    'category',
    'sort',
    'page',
    'perpage',
];

/**
 * Return the first matching child element.
 *
 * @param {Element} root Root element.
 * @param {String} selector CSS selector.
 * @returns {Element|null}
 */
const find = (root, selector) => root ? root.querySelector(selector) : null;

/**
 * Return all matching child elements.
 *
 * @param {Element} root Root element.
 * @param {String} selector CSS selector.
 * @returns {Element[]}
 */
const findAll = (root, selector) => root ? Array.from(root.querySelectorAll(selector)) : [];

/**
 * Return a safe string value.
 *
 * @param {*} value Source value.
 * @returns {String}
 */
const toString = value => {
    if (value === null || typeof value === 'undefined') {
        return '';
    }

    return String(value);
};

/**
 * Return a safe positive integer.
 *
 * @param {*} value Source value.
 * @param {Number} fallback Fallback value.
 * @returns {Number}
 */
const toPositiveInteger = (value, fallback) => {
    const parsed = window.parseInt(value, 10);

    if (Number.isNaN(parsed) || parsed < 1) {
        return fallback;
    }

    return parsed;
};

/**
 * Normalize category state.
 *
 * The UI may use "all" as a friendly value. The external service expects an
 * empty category to mean "all categories".
 *
 * @param {String} category Category key.
 * @returns {String}
 */
const categoryForService = category => {
    const value = toString(category).trim();

    return value === 'all' ? '' : value;
};

/**
 * Return the current URL search params.
 *
 * @returns {URLSearchParams}
 */
const getUrlParams = () => new URLSearchParams(window.location.search);

/**
 * Read state from URL query params.
 *
 * @returns {Object}
 */
const getUrlState = () => {
    const params = getUrlParams();
    const state = {};

    URL_STATE_KEYS.forEach(key => {
        if (params.has(key)) {
            state[key] = params.get(key);
        }
    });

    if (typeof state.page !== 'undefined') {
        state.page = toPositiveInteger(state.page, DEFAULT_STATE.page);
    }

    if (typeof state.perpage !== 'undefined') {
        state.perpage = toPositiveInteger(state.perpage, DEFAULT_STATE.perpage);
    }

    return state;
};

/**
 * Read state from root data attributes.
 *
 * @param {Element} root Explorer root.
 * @returns {Object}
 */
const getDatasetState = root => {
    if (!root) {
        return {};
    }

    const state = {};

    if (root.dataset.query) {
        state.q = root.dataset.query;
    }

    if (root.dataset.category) {
        state.category = root.dataset.category;
    }

    if (root.dataset.sort) {
        state.sort = root.dataset.sort;
    }

    if (root.dataset.page) {
        state.page = toPositiveInteger(root.dataset.page, DEFAULT_STATE.page);
    }

    if (root.dataset.perpage) {
        state.perpage = toPositiveInteger(root.dataset.perpage, DEFAULT_STATE.perpage);
    }

    if (root.dataset.service) {
        state.service = root.dataset.service;
    }

    if (root.dataset.contextid) {
        state.contextid = toPositiveInteger(root.dataset.contextid, 0);
    }

    return state;
};

/**
 * Merge initial state.
 *
 * @param {Element} root Explorer root.
 * @param {Object} initialState State passed from PHP.
 * @returns {Object}
 */
const buildInitialState = (root, initialState) => {
    return Object.assign(
        {},
        DEFAULT_STATE,
        getDatasetState(root),
        getUrlState(),
        initialState || {}
    );
};

/**
 * Get the service method name.
 *
 * @param {Element} root Explorer root.
 * @param {Object} state Current state.
 * @returns {String}
 */
const getServiceMethod = (root, state) => {
    if (state && state.service) {
        return state.service;
    }

    if (root && root.dataset.service) {
        return root.dataset.service;
    }

    return DEFAULT_METHOD;
};

/**
 * Set explorer status text.
 *
 * @param {Element} root Explorer root.
 * @param {String} message Status message.
 */
const setStatus = (root, message) => {
    const status = find(root, SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Set loading state.
 *
 * @param {Element} root Explorer root.
 * @param {Boolean} loading Loading state.
 */
const setLoading = (root, loading) => {
    if (!root) {
        return;
    }

    root.classList.toggle(CLASSES.loading, loading);

    findAll(root, SELECTORS.input).forEach(input => {
        input.disabled = loading;
    });

    const loadMore = find(root, SELECTORS.loadMore);
    if (loadMore) {
        loadMore.disabled = loading;
    }
};

/**
 * Set empty state.
 *
 * @param {Element} root Explorer root.
 * @param {Boolean} empty Empty state.
 */
const setEmpty = (root, empty) => {
    if (!root) {
        return;
    }

    root.classList.toggle(CLASSES.empty, empty);

    const emptyRegion = find(root, SELECTORS.empty);
    if (emptyRegion) {
        emptyRegion.hidden = !empty;
        emptyRegion.classList.toggle(CLASSES.hidden, !empty);
    }
};

/**
 * Set error state.
 *
 * @param {Element} root Explorer root.
 * @param {Boolean} error Error state.
 */
const setError = (root, error) => {
    if (!root) {
        return;
    }

    root.classList.toggle(CLASSES.error, error);
};

/**
 * Read form values.
 *
 * @param {Element} root Explorer root.
 * @returns {Object}
 */
const readFormState = root => {
    const form = find(root, SELECTORS.form);

    if (!form) {
        return {};
    }

    const formData = new FormData(form);

    return {
        q: toString(formData.get('q')).trim(),
        category: toString(formData.get('category') || DEFAULT_STATE.category),
        sort: toString(formData.get('sort') || DEFAULT_STATE.sort),
    };
};

/**
 * Write state values into form controls.
 *
 * @param {Element} root Explorer root.
 * @param {Object} state Current state.
 */
const writeFormState = (root, state) => {
    const form = find(root, SELECTORS.form);

    if (!form) {
        return;
    }

    Object.keys(DEFAULT_STATE).forEach(key => {
        const field = form.elements[key];

        if (field && typeof state[key] !== 'undefined') {
            field.value = state[key] === '' && key === 'category' ? DEFAULT_STATE.category : state[key];
        }
    });
};

/**
 * Update URL query string without reloading.
 *
 * @param {Object} state Current state.
 */
const updateUrl = state => {
    if (!window.history || !window.history.replaceState) {
        return;
    }

    const params = getUrlParams();

    URL_STATE_KEYS.forEach(key => {
        const value = typeof state[key] === 'undefined' ? '' : String(state[key]);

        if (
            value === ''
            || value === 'all'
            || (key === 'sort' && value === DEFAULT_STATE.sort)
            || (key === 'page' && value === String(DEFAULT_STATE.page))
            || (key === 'perpage' && value === String(DEFAULT_STATE.perpage))
        ) {
            params.delete(key);
            return;
        }

        params.set(key, value);
    });

    const query = params.toString();
    const nextUrl = `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`;

    window.history.replaceState({}, document.title, nextUrl);
};

/**
 * Create a text element.
 *
 * @param {String} tagName Tag name.
 * @param {String} className CSS class.
 * @param {String} text Text content.
 * @returns {HTMLElement}
 */
const createTextElement = (tagName, className, text) => {
    const element = document.createElement(tagName);

    if (className) {
        element.className = className;
    }

    element.textContent = text || '';

    return element;
};

/**
 * Create an anchor.
 *
 * @param {String} className CSS class.
 * @param {String} href URL.
 * @param {String} text Link text.
 * @returns {HTMLAnchorElement}
 */
const createLink = (className, href, text) => {
    const link = document.createElement('a');

    link.className = className || '';
    link.href = href || '#';
    link.textContent = text || '';

    return link;
};

/**
 * Normalize a course result DTO.
 *
 * @param {Object} course Raw course DTO.
 * @returns {Object}
 */
const normalizeCourse = course => {
    const safe = course || {};

    return {
        title: toString(safe.title || safe.fullname || safe.shortname || ''),
        url: toString(safe.url || ''),
        summary: toString(safe.summary || safe.body || ''),
        shortname: toString(safe.shortname || safe.code || ''),
        category: toString(safe.categorylabel || safe.category || safe.eyebrow || ''),
        metadata: Array.isArray(safe.metadata) ? safe.metadata : [],
    };
};

/**
 * Create metadata item.
 *
 * @param {String} label Metadata label.
 * @param {String} value Metadata value.
 * @returns {HTMLLIElement}
 */
const createMetadataItem = (label, value) => {
    const item = document.createElement('li');
    item.className = 'local-uckk-course-card__metadata-item';

    item.appendChild(createTextElement(
        'span',
        'local-uckk-course-card__metadata-label',
        label
    ));

    item.appendChild(createTextElement(
        'span',
        'local-uckk-course-card__metadata-value',
        value
    ));

    return item;
};

/**
 * Normalize metadata without duplicates.
 *
 * @param {Object} course Normalized course.
 * @returns {Object[]}
 */
const normalizeMetadata = course => {
    const metadata = [];
    const seen = new Set();

    const add = (label, value) => {
        const safeLabel = toString(label).trim();
        const safeValue = toString(value).trim();
        const key = `${safeLabel.toLowerCase()}::${safeValue.toLowerCase()}`;

        if (safeLabel === '' || safeValue === '' || seen.has(key)) {
            return;
        }

        seen.add(key);

        metadata.push({
            label: safeLabel,
            value: safeValue,
        });
    };

    course.metadata.forEach(item => {
        if (item) {
            add(item.label, item.value);
        }
    });

    if (metadata.length === 0) {
        add('Code', course.shortname);
        add('Catégorie', course.category);
    }

    return metadata;
};

/**
 * Create one public course card from a server DTO.
 *
 * Expected DTO fields:
 * - title
 * - url
 * - summary or body
 * - categorylabel, category or eyebrow
 * - metadata
 *
 * @param {Object} rawCourse Course DTO.
 * @returns {HTMLElement}
 */
const createCourseCard = rawCourse => {
    const course = normalizeCourse(rawCourse);

    const card = document.createElement('article');
    card.className = 'local-uckk-course-card';

    if (course.category !== '') {
        card.appendChild(createTextElement(
            'p',
            'local-uckk-course-card__eyebrow',
            course.category
        ));
    }

    if (course.title !== '') {
        const heading = document.createElement('h3');
        heading.className = 'local-uckk-course-card__title';

        if (course.url !== '') {
            heading.appendChild(createLink('', course.url, course.title));
        } else {
            heading.textContent = course.title;
        }

        card.appendChild(heading);
    }

    if (course.summary !== '') {
        card.appendChild(createTextElement(
            'p',
            'local-uckk-course-card__summary',
            course.summary
        ));
    }

    const metadata = normalizeMetadata(course);

    if (metadata.length > 0) {
        const list = document.createElement('ul');
        list.className = 'local-uckk-course-card__metadata';

        metadata.forEach(item => {
            list.appendChild(createMetadataItem(item.label, item.value));
        });

        card.appendChild(list);
    }

    return card;
};

/**
 * Render result cards.
 *
 * @param {Element} root Explorer root.
 * @param {Object[]} courses Course DTOs.
 * @param {Boolean} append Append instead of replacing.
 */
const renderCourses = (root, courses, append) => {
    const results = find(root, SELECTORS.results);

    if (!results) {
        return;
    }

    if (!append) {
        results.innerHTML = '';
    }

    courses.forEach(course => {
        results.appendChild(createCourseCard(course));
    });
};

/**
 * Update result count.
 *
 * @param {Element} root Explorer root.
 * @param {Number} total Total result count.
 */
const updateCount = (root, total) => {
    const count = find(root, SELECTORS.count);

    if (count) {
        count.textContent = String(total);
    }
};

/**
 * Update load-more button.
 *
 * @param {Element} root Explorer root.
 * @param {Object} response Service response.
 * @param {Object} state Current state.
 */
const updateLoadMore = (root, response, state) => {
    const loadMore = find(root, SELECTORS.loadMore);

    if (!loadMore) {
        return;
    }

    const hasMore = Boolean(response.hasmore || response.has_more);
    loadMore.hidden = !hasMore;
    loadMore.classList.toggle(CLASSES.hidden, !hasMore);
    loadMore.dataset.nextPage = String((state.page || DEFAULT_STATE.page) + 1);
};

/**
 * Normalize service response.
 *
 * @param {Object} response Raw service response.
 * @returns {Object}
 */
const normalizeResponse = response => {
    const safe = response || {};

    return {
        courses: Array.isArray(safe.courses) ? safe.courses : (Array.isArray(safe.results) ? safe.results : []),
        total: toPositiveInteger(safe.total, 0),
        page: toPositiveInteger(safe.page, DEFAULT_STATE.page),
        perpage: toPositiveInteger(safe.perpage, DEFAULT_STATE.perpage),
        hasmore: Boolean(safe.hasmore || safe.has_more),
        message: toString(safe.message || ''),
    };
};

/**
 * Fetch courses from the Moodle external service.
 *
 * @param {Element} root Explorer root.
 * @param {Object} state Current state.
 * @returns {Promise<Object>}
 */
const fetchCourses = (root, state) => {
    const methodname = getServiceMethod(root, state);

    const args = {
        q: state.q || '',
        category: categoryForService(state.category || DEFAULT_STATE.category),
        sort: state.sort || DEFAULT_STATE.sort,
        page: toPositiveInteger(state.page, DEFAULT_STATE.page),
        perpage: toPositiveInteger(state.perpage, DEFAULT_STATE.perpage),
    };

    const contextid = toPositiveInteger(state.contextid, 0);

    if (contextid > 0) {
        args.contextid = contextid;
    }

    return Ajax.call([{
        methodname,
        args,
    }], true, false)[0];
};

/**
 * Run a search and update the explorer.
 *
 * @param {Element} root Explorer root.
 * @param {Object} state Current state.
 * @param {Boolean} append Append mode.
 * @returns {Promise<void>}
 */
const search = async(root, state, append = false) => {
    setLoading(root, true);
    setError(root, false);

    try {
        setStatus(root, await getString('courseexplorerloading', COMPONENT));

        const response = normalizeResponse(await fetchCourses(root, state));

        renderCourses(root, response.courses, append);
        updateCount(root, response.total);
        updateLoadMore(root, response, state);

        const empty = response.courses.length === 0 && !append;
        setEmpty(root, empty);

        if (response.message !== '') {
            setStatus(root, response.message);
        } else if (empty) {
            setStatus(root, await getString('courseexplorerempty', COMPONENT));
        } else {
            setStatus(root, await getString('courseexplorerready', COMPONENT));
        }

        root.classList.add(CLASSES.ready);
    } catch (error) {
        setError(root, true);
        setStatus(root, await getString('courseexplorererror', COMPONENT));
        Notification.exception(error);
    } finally {
        setLoading(root, false);
    }
};

/**
 * Reset explorer filters.
 *
 * @param {Element} root Explorer root.
 * @param {Object} state Current state.
 */
const resetExplorer = (root, state) => {
    const nextState = Object.assign({}, state, DEFAULT_STATE);

    writeFormState(root, nextState);
    updateUrl(nextState);

    Object.keys(nextState).forEach(key => {
        state[key] = nextState[key];
    });

    search(root, state, false);
};

/**
 * Bind explorer events.
 *
 * @param {Element} root Explorer root.
 * @param {Object} state Current state.
 */
const bindEvents = (root, state) => {
    const form = find(root, SELECTORS.form);

    if (form) {
        form.addEventListener('submit', event => {
            event.preventDefault();

            const formState = readFormState(root);
            const nextState = Object.assign({}, state, formState, {
                page: 1,
            });

            Object.keys(nextState).forEach(key => {
                state[key] = nextState[key];
            });

            updateUrl(state);
            search(root, state, false);
        });

        form.addEventListener('change', event => {
            if (!event.target.matches('select')) {
                return;
            }

            const formState = readFormState(root);
            const nextState = Object.assign({}, state, formState, {
                page: 1,
            });

            Object.keys(nextState).forEach(key => {
                state[key] = nextState[key];
            });

            updateUrl(state);
            search(root, state, false);
        });
    }

    const reset = find(root, SELECTORS.reset);
    if (reset) {
        reset.addEventListener('click', event => {
            event.preventDefault();
            resetExplorer(root, state);
        });
    }

    const loadMore = find(root, SELECTORS.loadMore);
    if (loadMore) {
        loadMore.addEventListener('click', event => {
            event.preventDefault();

            state.page = toPositiveInteger(loadMore.dataset.nextPage, state.page + 1);
            updateUrl(state);
            search(root, state, true);
        });
    }
};

/**
 * Resolve explorer roots from an init argument.
 *
 * @param {Object|String|null} initialState Initial state or root id.
 * @returns {Element[]}
 */
const resolveRoots = initialState => {
    if (typeof initialState === 'string' && initialState !== '') {
        const rootById = document.getElementById(initialState);
        return rootById ? [rootById] : [];
    }

    if (initialState && initialState.rootId) {
        const rootByStateId = document.getElementById(initialState.rootId);
        return rootByStateId ? [rootByStateId] : [];
    }

    return Array.from(document.querySelectorAll(SELECTORS.root));
};

/**
 * Initialise one explorer root.
 *
 * @param {Element} root Explorer root.
 * @param {Object} initialState Initial state.
 */
const initRoot = (root, initialState) => {
    if (!root || root.dataset.courseExplorerInitialised === 'true') {
        return;
    }

    root.dataset.courseExplorerInitialised = 'true';

    const state = buildInitialState(root, initialState);

    writeFormState(root, state);
    bindEvents(root, state);

    if (root.dataset.autoload === 'true') {
        search(root, state, false);
    }
};

/**
 * Initialise public course explorer instances.
 *
 * @param {Object|String|null} initialState Initial state object or root id.
 */
export const init = initialState => {
    const roots = resolveRoots(initialState || {});

    roots.forEach(root => {
        initRoot(root, typeof initialState === 'object' ? initialState : {});
    });
};