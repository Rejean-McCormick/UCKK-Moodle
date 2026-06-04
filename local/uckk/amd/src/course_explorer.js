/**
 * Public course explorer interactions for local_uckk.
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

const find = (root, selector) => root ? root.querySelector(selector) : null;
const findAll = (root, selector) => root ? Array.from(root.querySelectorAll(selector)) : [];

const toString = value => {
    if (value === null || typeof value === 'undefined') {
        return '';
    }

    return String(value);
};

const toPositiveInteger = (value, fallback) => {
    const parsed = window.parseInt(value, 10);

    if (Number.isNaN(parsed) || parsed < 1) {
        return fallback;
    }

    return parsed;
};

const categoryForService = category => {
    const value = toString(category).trim();

    return value === 'all' ? '' : value;
};

const getUrlParams = () => new URLSearchParams(window.location.search);

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

const buildInitialState = (root, initialState) => {
    return Object.assign(
        {},
        DEFAULT_STATE,
        getDatasetState(root),
        getUrlState(),
        initialState || {}
    );
};

const getServiceMethod = (root, state) => {
    if (state && state.service) {
        return state.service;
    }

    if (root && root.dataset.service) {
        return root.dataset.service;
    }

    return DEFAULT_METHOD;
};

const setStatus = (root, message) => {
    const status = find(root, SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

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

const setError = (root, error) => {
    if (!root) {
        return;
    }

    root.classList.toggle(CLASSES.error, error);
};

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

const createTextElement = (tagName, className, text) => {
    const element = document.createElement(tagName);

    if (className) {
        element.className = className;
    }

    element.textContent = text || '';

    return element;
};

const createLink = (className, href, text) => {
    const link = document.createElement('a');

    link.className = className || '';
    link.href = href || '#';
    link.textContent = text || '';

    return link;
};

const metadataValue = (metadata, labels) => {
    if (!Array.isArray(metadata)) {
        return '';
    }

    const wanted = labels.map(label => label.toLowerCase());

    const match = metadata.find(item => {
        const label = toString(item && item.label).trim().toLowerCase();

        return wanted.includes(label);
    });

    return match ? toString(match.value).trim() : '';
};

const normalizeCourse = course => {
    const safe = course || {};
    const metadata = Array.isArray(safe.metadata) ? safe.metadata : [];

    const shortname = toString(
        safe.shortname
        || safe.code
        || metadataValue(metadata, ['Numéro de cours', 'Code', 'Numéro', 'Numero'])
        || ''
    ).trim();

    const category = toString(
        safe.category
        || safe.categorylabel
        || safe.eyebrow
        || metadataValue(metadata, ['Voie', 'Catégorie', 'Categorie'])
        || ''
    ).trim();

    return {
        title: toString(safe.title || safe.fullname || shortname || ''),
        url: toString(safe.url || ''),
        summary: toString(safe.summary || safe.body || ''),
        shortname,
        category,
    };
};

const createCourseMetaLine = (value, valueClass, modifier) => {
    const line = document.createElement('p');

    line.className = 'local-uckk-public-card__eyebrow local-uckk-course-card__meta-line'
        + (modifier ? ` local-uckk-course-card__meta-line--${modifier}` : '');

    line.appendChild(createTextElement(
        'span',
        valueClass,
        value
    ));

    return line;
};

const createCourseMetadata = course => {
    const meta = document.createElement('div');

    meta.className = 'local-uckk-course-card__meta';

    if (course.category !== '') {
        meta.appendChild(createCourseMetaLine(
            course.category,
            'local-uckk-course-card__pathway',
            'pathway'
        ));
    }

    if (course.shortname !== '') {
        meta.appendChild(createCourseMetaLine(
            course.shortname,
            'local-uckk-course-card__code',
            'code'
        ));
    }

    return meta;
};

const createCourseCard = rawCourse => {
    const course = normalizeCourse(rawCourse);

    const card = document.createElement('article');
    card.className = 'local-uckk-public-card local-uckk-public-card--course local-uckk-course-card';

    const content = document.createElement('div');
    content.className = 'local-uckk-public-card__content';

    if (course.title !== '') {
        const heading = document.createElement('h3');
        heading.className = 'local-uckk-public-card__title';

        if (course.url !== '') {
            heading.appendChild(createLink(
                'local-uckk-public-card__title-link',
                course.url,
                course.title
            ));
        } else {
            heading.textContent = course.title;
        }

        content.appendChild(heading);
    }

    if (course.summary !== '') {
        content.appendChild(createTextElement(
            'p',
            'local-uckk-public-card__body',
            course.summary
        ));
    }

    if (course.category !== '' || course.shortname !== '') {
        content.appendChild(createCourseMetadata(course));
    }

    card.appendChild(content);

    return card;
};

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

const updateCount = (root, total) => {
    const count = find(root, SELECTORS.count);

    if (count) {
        count.textContent = String(total);
    }
};

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

const resetExplorer = (root, state) => {
    const nextState = Object.assign({}, state, DEFAULT_STATE);

    writeFormState(root, nextState);
    updateUrl(nextState);

    Object.keys(nextState).forEach(key => {
        state[key] = nextState[key];
    });

    search(root, state, false);
};

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

export const init = initialState => {
    const roots = resolveRoots(initialState || {});

    roots.forEach(root => {
        initRoot(root, typeof initialState === 'object' ? initialState : {});
    });
};