// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Faculty course card interactions for local_uckk.
 *
 * This module is intentionally UI-only:
 * - it does not read Atlas JSON files;
 * - it does not read Faculty JSON files;
 * - it does not call Moodle external services;
 * - it does not decide enrolment, completion, visibility, badges, grades, or permissions;
 * - it only filters, sorts, counts, expands and enhances already-rendered public course cards.
 *
 * Expected optional root markup:
 *
 * <section data-region="local-uckk-faculty-courses">
 *     <input data-action="local-uckk-faculty-course-search">
 *     <select data-action="local-uckk-faculty-course-sort"></select>
 *     <button data-action="local-uckk-faculty-course-reset"></button>
 *     <output data-region="local-uckk-faculty-course-count"></output>
 *     <div data-region="local-uckk-faculty-course-list">
 *         <article data-region="local-uckk-faculty-course-card"></article>
 *     </div>
 *     <div data-region="local-uckk-faculty-course-empty" hidden></div>
 * </section>
 *
 * Optional card data hooks:
 *
 * data-course-code
 * data-course-title
 * data-course-order
 * data-course-level
 * data-course-concept
 *
 * Supported actions:
 *
 * data-action="local-uckk-faculty-course-search"
 * data-action="local-uckk-faculty-course-sort"
 * data-action="local-uckk-faculty-course-filter-level"
 * data-action="local-uckk-faculty-course-reset"
 * data-action="local-uckk-faculty-course-toggle"
 *
 * @module     local_uckk/faculty_courses
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    root: '[data-region="local-uckk-faculty-courses"]',
    pageRoot: '[data-region="local-uckk-faculty-page"]',
    list: '[data-region="local-uckk-faculty-course-list"]',
    card: '[data-region="local-uckk-faculty-course-card"]',
    count: '[data-region="local-uckk-faculty-course-count"]',
    empty: '[data-region="local-uckk-faculty-course-empty"]',
    search: '[data-action="local-uckk-faculty-course-search"]',
    sort: '[data-action="local-uckk-faculty-course-sort"]',
    levelFilter: '[data-action="local-uckk-faculty-course-filter-level"]',
    reset: '[data-action="local-uckk-faculty-course-reset"]',
    toggle: '[data-action="local-uckk-faculty-course-toggle"]',
};

const ATTRIBUTES = {
    initialised: 'data-local-uckk-faculty-courses-initialised',
    enhanced: 'data-enhanced',
    visible: 'data-visible',
    filtered: 'data-filtered',
    originalIndex: 'data-original-index',
    matches: 'data-matches',
};

const CLASSES = {
    jsReady: 'is-js-ready',
    enhanced: 'is-enhanced',
    hidden: 'is-hidden',
    filtered: 'is-filtered',
    hasResults: 'has-results',
    noResults: 'has-no-results',
};

const SORTS = {
    pedagogical: 'pedagogical',
    title: 'title',
    code: 'code',
    level: 'level',
};

const EVENTS = {
    ready: 'local_uckk:facultyCoursesReady',
    updated: 'local_uckk:facultyCoursesUpdated',
    reset: 'local_uckk:facultyCoursesReset',
};

const DEFAULT_OPTIONS = {
    searchParam: 'q',
    sortParam: 'sort',
    levelParam: 'level',
    updateUrl: false,
    defaultSort: SORTS.pedagogical,
    minimumSearchLength: 0,
};

/**
 * Merge options with defaults.
 *
 * @param {Object} options
 * @returns {Object}
 */
const normaliseOptions = (options = {}) => ({
    ...DEFAULT_OPTIONS,
    ...(options || {}),
});

/**
 * Return whether a value is an HTMLElement.
 *
 * @param {*} value
 * @returns {Boolean}
 */
const isElement = value => value instanceof HTMLElement;

/**
 * Resolve root elements from init arguments.
 *
 * Supported:
 * - init()
 * - init('root-id')
 * - init('#selector')
 * - init(element)
 * - init({rootId: 'root-id'})
 * - init({selector: '[data-region="..."]'})
 *
 * @param {String|HTMLElement|Object|null} root
 * @returns {HTMLElement[]}
 */
const resolveRoots = (root = null) => {
    if (isElement(root)) {
        return [root];
    }

    if (root && typeof root === 'object') {
        if (root.rootId) {
            const element = document.getElementById(root.rootId);
            return element ? [element] : [];
        }

        if (root.selector) {
            return Array.from(document.querySelectorAll(root.selector));
        }
    }

    if (typeof root === 'string' && root !== '') {
        if (root.charAt(0) === '#' || root.charAt(0) === '.' || root.indexOf('[data-') === 0) {
            return Array.from(document.querySelectorAll(root));
        }

        const byId = document.getElementById(root);
        if (byId) {
            return [byId];
        }

        return Array.from(document.querySelectorAll(root));
    }

    const directRoots = Array.from(document.querySelectorAll(SELECTORS.root));
    if (directRoots.length > 0) {
        return directRoots;
    }

    return Array.from(document.querySelectorAll(SELECTORS.pageRoot))
        .filter(page => page.querySelector(SELECTORS.card));
};

/**
 * Dispatch a DOM event from the root.
 *
 * @param {HTMLElement} root
 * @param {String} name
 * @param {Object} detail
 */
const dispatch = (root, name, detail = {}) => {
    root.dispatchEvent(new CustomEvent(name, {
        bubbles: true,
        detail,
    }));
};

/**
 * Normalise searchable text.
 *
 * @param {String} value
 * @returns {String}
 */
const normaliseText = value => (value || '')
    .toString()
    .toLocaleLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim();

/**
 * Read a card field from dataset or rendered text.
 *
 * @param {HTMLElement} card
 * @param {String} key
 * @param {String} fallbackSelector
 * @returns {String}
 */
const readCardField = (card, key, fallbackSelector = '') => {
    if (card.dataset && card.dataset[key]) {
        return card.dataset[key];
    }

    if (fallbackSelector) {
        const element = card.querySelector(fallbackSelector);
        if (element) {
            return element.textContent.trim();
        }
    }

    return '';
};

/**
 * Get card title.
 *
 * @param {HTMLElement} card
 * @returns {String}
 */
const getCardTitle = card => {
    const explicit = readCardField(card, 'courseTitle');
    if (explicit) {
        return explicit;
    }

    const heading = card.querySelector('h2, h3, h4, [data-region="local-uckk-faculty-course-title"]');
    return heading ? heading.textContent.trim() : card.textContent.trim();
};

/**
 * Get card code.
 *
 * @param {HTMLElement} card
 * @returns {String}
 */
const getCardCode = card => readCardField(card, 'courseCode', '[data-region="local-uckk-faculty-course-code"]');

/**
 * Get card order.
 *
 * @param {HTMLElement} card
 * @returns {Number}
 */
const getCardOrder = card => {
    const order = window.parseInt(card.dataset.courseOrder || card.getAttribute(ATTRIBUTES.originalIndex) || '0', 10);
    return Number.isNaN(order) ? 0 : order;
};

/**
 * Get card level.
 *
 * @param {HTMLElement} card
 * @returns {String}
 */
const getCardLevel = card => readCardField(card, 'courseLevel', '[data-region="local-uckk-faculty-course-level"]');

/**
 * Get full searchable card text.
 *
 * @param {HTMLElement} card
 * @returns {String}
 */
const getCardSearchText = card => {
    const parts = [
        getCardCode(card),
        getCardTitle(card),
        getCardLevel(card),
        readCardField(card, 'courseConcept', '[data-region="local-uckk-faculty-course-concept"]'),
        card.textContent || '',
    ];

    return normaliseText(parts.join(' '));
};

/**
 * Find cards under a root.
 *
 * @param {HTMLElement} root
 * @returns {HTMLElement[]}
 */
const getCards = root => Array.from(root.querySelectorAll(SELECTORS.card));

/**
 * Find the card list container.
 *
 * @param {HTMLElement} root
 * @returns {HTMLElement|null}
 */
const getList = root => root.querySelector(SELECTORS.list) || null;

/**
 * Get current UI state.
 *
 * @param {HTMLElement} root
 * @param {Object} options
 * @returns {{query: String, sort: String, level: String}}
 */
const getState = (root, options) => {
    const search = root.querySelector(SELECTORS.search);
    const sort = root.querySelector(SELECTORS.sort);
    const level = root.querySelector(SELECTORS.levelFilter);

    return {
        query: search && search.value ? search.value.trim() : '',
        sort: sort && sort.value ? sort.value : options.defaultSort,
        level: level && level.value ? level.value : '',
    };
};

/**
 * Decide whether a card matches current filters.
 *
 * @param {HTMLElement} card
 * @param {{query: String, sort: String, level: String}} state
 * @param {Object} options
 * @returns {Boolean}
 */
const cardMatches = (card, state, options) => {
    const query = normaliseText(state.query);
    const level = normaliseText(state.level);

    if (level && normaliseText(getCardLevel(card)) !== level) {
        return false;
    }

    if (query.length < options.minimumSearchLength) {
        return true;
    }

    if (!query) {
        return true;
    }

    return getCardSearchText(card).indexOf(query) !== -1;
};

/**
 * Sort card nodes.
 *
 * @param {HTMLElement[]} cards
 * @param {String} sort
 * @returns {HTMLElement[]}
 */
const sortCards = (cards, sort) => {
    const sorted = [...cards];

    sorted.sort((a, b) => {
        if (sort === SORTS.title) {
            return getCardTitle(a).localeCompare(getCardTitle(b), undefined, {sensitivity: 'base'});
        }

        if (sort === SORTS.code) {
            return getCardCode(a).localeCompare(getCardCode(b), undefined, {numeric: true, sensitivity: 'base'});
        }

        if (sort === SORTS.level) {
            const level = getCardLevel(a).localeCompare(getCardLevel(b), undefined, {numeric: true, sensitivity: 'base'});
            return level !== 0 ? level : getCardOrder(a) - getCardOrder(b);
        }

        return getCardOrder(a) - getCardOrder(b);
    });

    return sorted;
};

/**
 * Update URL query params when explicitly enabled.
 *
 * @param {{query: String, sort: String, level: String}} state
 * @param {Object} options
 */
const updateUrl = (state, options) => {
    if (!options.updateUrl || !window.history || !window.history.replaceState) {
        return;
    }

    const url = new URL(window.location.href);

    if (state.query) {
        url.searchParams.set(options.searchParam, state.query);
    } else {
        url.searchParams.delete(options.searchParam);
    }

    if (state.sort && state.sort !== options.defaultSort) {
        url.searchParams.set(options.sortParam, state.sort);
    } else {
        url.searchParams.delete(options.sortParam);
    }

    if (state.level) {
        url.searchParams.set(options.levelParam, state.level);
    } else {
        url.searchParams.delete(options.levelParam);
    }

    window.history.replaceState(null, '', url.toString());
};

/**
 * Update count and empty state.
 *
 * @param {HTMLElement} root
 * @param {Number} visible
 * @param {Number} total
 */
const updateSummary = (root, visible, total) => {
    const count = root.querySelector(SELECTORS.count);
    const empty = root.querySelector(SELECTORS.empty);

    if (count) {
        count.textContent = `${visible} / ${total}`;
        count.setAttribute('aria-live', 'polite');
    }

    if (empty) {
        empty.hidden = visible > 0;
    }

    root.classList.toggle(CLASSES.hasResults, visible > 0);
    root.classList.toggle(CLASSES.noResults, visible === 0);
};

/**
 * Apply filters and sort.
 *
 * @param {HTMLElement} root
 * @param {Object} options
 */
const applyState = (root, options = {}) => {
    const config = normaliseOptions(options);
    const state = getState(root, config);
    const list = getList(root);
    const cards = getCards(root);

    const sorted = sortCards(cards, state.sort);
    let visible = 0;

    sorted.forEach(card => {
        const matches = cardMatches(card, state, config);

        card.hidden = !matches;
        card.classList.toggle(CLASSES.filtered, !matches);
        card.setAttribute(ATTRIBUTES.filtered, matches ? 'false' : 'true');
        card.setAttribute(ATTRIBUTES.visible, matches ? 'true' : 'false');
        card.setAttribute(ATTRIBUTES.matches, matches ? 'true' : 'false');

        if (matches) {
            visible++;
        }

        if (list) {
            list.appendChild(card);
        }
    });

    updateSummary(root, visible, cards.length);
    updateUrl(state, config);

    dispatch(root, EVENTS.updated, {
        visible,
        total: cards.length,
        query: state.query,
        sort: state.sort,
        level: state.level,
    });
};

/**
 * Reset controls to their default state.
 *
 * @param {HTMLElement} root
 * @param {Object} options
 */
const reset = (root, options = {}) => {
    const config = normaliseOptions(options);
    const search = root.querySelector(SELECTORS.search);
    const sort = root.querySelector(SELECTORS.sort);
    const level = root.querySelector(SELECTORS.levelFilter);

    if (search) {
        search.value = '';
    }

    if (sort) {
        sort.value = config.defaultSort;
    }

    if (level) {
        level.value = '';
    }

    applyState(root, config);

    dispatch(root, EVENTS.reset, {});
};

/**
 * Toggle a controlled course-card panel.
 *
 * @param {HTMLElement} button
 * @param {HTMLElement} root
 */
const togglePanel = (button, root) => {
    const controls = button.getAttribute('aria-controls') || '';
    if (!controls) {
        return;
    }

    const escaped = window.CSS && window.CSS.escape ? window.CSS.escape(controls) : controls.replace(/"/g, '\\"');
    const panel = root.querySelector(`#${escaped}`) || document.getElementById(controls);

    if (!panel) {
        return;
    }

    const expanded = button.getAttribute('aria-expanded') === 'true';
    const next = !expanded;

    button.setAttribute('aria-expanded', next ? 'true' : 'false');
    panel.hidden = !next;
};

/**
 * Handle input and change events.
 *
 * @param {Event} event
 * @param {HTMLElement} root
 * @param {Object} options
 */
const handleControlChange = (event, root, options) => {
    if (
        event.target.closest(SELECTORS.search) ||
        event.target.closest(SELECTORS.sort) ||
        event.target.closest(SELECTORS.levelFilter)
    ) {
        applyState(root, options);
    }
};

/**
 * Handle click events.
 *
 * @param {MouseEvent} event
 * @param {HTMLElement} root
 * @param {Object} options
 */
const handleClick = (event, root, options) => {
    const resetButton = event.target.closest(SELECTORS.reset);
    if (resetButton && root.contains(resetButton)) {
        event.preventDefault();
        reset(root, options);
        return;
    }

    const toggleButton = event.target.closest(SELECTORS.toggle);
    if (toggleButton && root.contains(toggleButton)) {
        event.preventDefault();
        togglePanel(toggleButton, root);
    }
};

/**
 * Prepare cards for filtering and sorting.
 *
 * @param {HTMLElement} root
 */
const prepareCards = root => {
    getCards(root).forEach((card, index) => {
        if (!card.hasAttribute(ATTRIBUTES.originalIndex)) {
            card.setAttribute(ATTRIBUTES.originalIndex, String(index + 1));
        }

        card.classList.add(CLASSES.enhanced);
        card.setAttribute(ATTRIBUTES.enhanced, 'true');

        if (!card.dataset.courseTitle) {
            card.dataset.courseTitle = getCardTitle(card);
        }

        if (!card.dataset.courseCode) {
            const code = getCardCode(card);
            if (code) {
                card.dataset.courseCode = code;
            }
        }
    });
};

/**
 * Prepare expandable panels inside course cards.
 *
 * @param {HTMLElement} root
 */
const prepareToggles = root => {
    root.querySelectorAll(SELECTORS.toggle).forEach(button => {
        const controls = button.getAttribute('aria-controls') || '';
        if (!controls) {
            return;
        }

        const escaped = window.CSS && window.CSS.escape ? window.CSS.escape(controls) : controls.replace(/"/g, '\\"');
        const panel = root.querySelector(`#${escaped}`) || document.getElementById(controls);

        if (!panel) {
            return;
        }

        const expanded = button.getAttribute('aria-expanded') === 'true';
        panel.hidden = !expanded;
    });
};

/**
 * Restore controls from current URL query params when enabled.
 *
 * @param {HTMLElement} root
 * @param {Object} options
 */
const restoreFromUrl = (root, options) => {
    if (!options.updateUrl) {
        return;
    }

    const url = new URL(window.location.href);
    const search = root.querySelector(SELECTORS.search);
    const sort = root.querySelector(SELECTORS.sort);
    const level = root.querySelector(SELECTORS.levelFilter);

    if (search && url.searchParams.has(options.searchParam)) {
        search.value = url.searchParams.get(options.searchParam) || '';
    }

    if (sort && url.searchParams.has(options.sortParam)) {
        sort.value = url.searchParams.get(options.sortParam) || options.defaultSort;
    }

    if (level && url.searchParams.has(options.levelParam)) {
        level.value = url.searchParams.get(options.levelParam) || '';
    }
};

/**
 * Initialise one Faculty courses root.
 *
 * @param {HTMLElement} root
 * @param {Object} options
 */
const initRoot = (root, options = {}) => {
    if (!root || root.getAttribute(ATTRIBUTES.initialised) === 'true') {
        return;
    }

    const config = normaliseOptions(options);

    root.setAttribute(ATTRIBUTES.initialised, 'true');
    root.classList.add(CLASSES.jsReady);

    prepareCards(root);
    prepareToggles(root);
    restoreFromUrl(root, config);
    applyState(root, config);

    root.addEventListener('input', event => {
        handleControlChange(event, root, config);
    });

    root.addEventListener('change', event => {
        handleControlChange(event, root, config);
    });

    root.addEventListener('click', event => {
        handleClick(event, root, config);
    });

    dispatch(root, EVENTS.ready, {
        total: getCards(root).length,
    });
};

/**
 * Initialise Faculty course interactions.
 *
 * Recommended usage:
 *
 * require(['local_uckk/faculty_courses'], function(FacultyCourses) {
 *     FacultyCourses.init();
 * });
 *
 * Or:
 *
 * $PAGE->requires->js_call_amd('local_uckk/faculty_courses', 'init');
 *
 * @param {String|HTMLElement|Object|null} root Root id, selector, element, or options object.
 * @param {Object} options Behaviour options.
 */
export const init = (root = null, options = {}) => {
    if (
        root &&
        typeof root === 'object' &&
        !isElement(root) &&
        !root.rootId &&
        !root.selector
    ) {
        const config = normaliseOptions(root);
        resolveRoots(null).forEach(element => initRoot(element, config));
        return;
    }

    resolveRoots(root).forEach(element => initRoot(element, options));
};

/**
 * Re-apply filters on an already initialised root.
 *
 * @param {String|HTMLElement|Object|null} root Root id, selector, element, or options object.
 * @param {Object} options Behaviour options.
 */
export const refresh = (root = null, options = {}) => {
    resolveRoots(root).forEach(element => applyState(element, options));
};

/**
 * Reset an already initialised root.
 *
 * @param {String|HTMLElement|Object|null} root Root id, selector, element, or options object.
 * @param {Object} options Behaviour options.
 */
export const resetFilters = (root = null, options = {}) => {
    resolveRoots(root).forEach(element => reset(element, options));
};

export default {
    init,
    refresh,
    resetFilters,
};