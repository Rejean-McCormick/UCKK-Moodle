// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Faculty dynamic block interactions for local_uckk.
 *
 * This module is intentionally UI-only:
 * - it does not read Atlas JSON files;
 * - it does not read Faculty JSON files;
 * - it does not choose dynamic block providers;
 * - it does not call Moodle external services;
 * - it does not decide permissions, visibility, enrolment, completion, badges, or access;
 * - it only enhances already-rendered dynamic blocks prepared by PHP.
 *
 * Expected optional root markup:
 *
 * <section data-region="local-uckk-faculty-dynamic-blocks">
 *     <article
 *         data-region="local-uckk-faculty-dynamic-block"
 *         data-block-id="announcements"
 *         data-block-type="announcements"
 *         data-provider="moodle_forum"
 *     >
 *         ...
 *     </article>
 * </section>
 *
 * Supported optional hooks:
 *
 * data-region="local-uckk-faculty-dynamic-block"
 * data-region="local-uckk-faculty-dynamic-block-content"
 * data-region="local-uckk-faculty-dynamic-block-empty"
 * data-region="local-uckk-faculty-dynamic-block-error"
 * data-region="local-uckk-faculty-dynamic-block-loading"
 * data-region="local-uckk-faculty-dynamic-block-count"
 * data-region="local-uckk-faculty-dynamic-block-item"
 *
 * data-action="local-uckk-faculty-dynamic-block-toggle"
 * data-action="local-uckk-faculty-dynamic-block-show-more"
 * data-action="local-uckk-faculty-dynamic-block-filter"
 * data-action="local-uckk-faculty-dynamic-block-reset"
 * data-action="local-uckk-faculty-dynamic-block-refresh"
 *
 * The refresh action dispatches a public event only. Any real refresh must be
 * provided by server-side Moodle code that has already checked permissions and
 * returned safe public data.
 *
 * @module     local_uckk/faculty_dynamic_blocks
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    root: '[data-region="local-uckk-faculty-dynamic-blocks"]',
    pageRoot: '[data-region="local-uckk-faculty-page"]',
    block: '[data-region="local-uckk-faculty-dynamic-block"]',
    content: '[data-region="local-uckk-faculty-dynamic-block-content"]',
    empty: '[data-region="local-uckk-faculty-dynamic-block-empty"]',
    error: '[data-region="local-uckk-faculty-dynamic-block-error"]',
    loading: '[data-region="local-uckk-faculty-dynamic-block-loading"]',
    count: '[data-region="local-uckk-faculty-dynamic-block-count"]',
    item: '[data-region="local-uckk-faculty-dynamic-block-item"]',
    toggle: '[data-action="local-uckk-faculty-dynamic-block-toggle"]',
    showMore: '[data-action="local-uckk-faculty-dynamic-block-show-more"]',
    filter: '[data-action="local-uckk-faculty-dynamic-block-filter"]',
    reset: '[data-action="local-uckk-faculty-dynamic-block-reset"]',
    refresh: '[data-action="local-uckk-faculty-dynamic-block-refresh"]',
};

const ATTRIBUTES = {
    initialised: 'data-local-uckk-faculty-dynamic-blocks-initialised',
    enhanced: 'data-enhanced',
    visible: 'data-visible',
    filtered: 'data-filtered',
    collapsed: 'data-collapsed',
    loading: 'data-loading',
    empty: 'data-empty',
    error: 'data-error',
    limit: 'data-limit',
    originalIndex: 'data-original-index',
};

const CLASSES = {
    jsReady: 'is-js-ready',
    enhanced: 'is-enhanced',
    hidden: 'is-hidden',
    collapsed: 'is-collapsed',
    expanded: 'is-expanded',
    loading: 'is-loading',
    empty: 'is-empty',
    error: 'has-error',
    hasItems: 'has-items',
    noItems: 'has-no-items',
    filtered: 'is-filtered',
};

const EVENTS = {
    ready: 'local_uckk:facultyDynamicBlocksReady',
    updated: 'local_uckk:facultyDynamicBlocksUpdated',
    blockToggled: 'local_uckk:facultyDynamicBlockToggled',
    showMore: 'local_uckk:facultyDynamicBlockShowMore',
    refreshRequested: 'local_uckk:facultyDynamicBlockRefreshRequested',
    reset: 'local_uckk:facultyDynamicBlocksReset',
};

const DEFAULT_OPTIONS = {
    defaultFilter: 'all',
    defaultItemLimit: 5,
    collapseEmptyBlocks: false,
    updateCounts: true,
    allowClientShowMore: true,
};

const ALLOWED_FILTERS = [
    'all',
    'announcements',
    'events',
    'moodle_course_list',
    'featured_courses',
    'faculty_news',
    'related_faculties',
    'public_resources',
    'cta_panel',
];

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
        .filter(page => page.querySelector(SELECTORS.block));
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
 * Resolve an element controlled by aria-controls.
 *
 * @param {HTMLElement} root
 * @param {String} id
 * @returns {HTMLElement|null}
 */
const resolveControlledElement = (root, id) => {
    if (!id) {
        return null;
    }

    const escaped = window.CSS && window.CSS.escape ? window.CSS.escape(id) : id.replace(/"/g, '\\"');

    return root.querySelector(`#${escaped}`) || document.getElementById(id);
};

/**
 * Return block elements.
 *
 * @param {HTMLElement} root
 * @returns {HTMLElement[]}
 */
const getBlocks = root => Array.from(root.querySelectorAll(SELECTORS.block));

/**
 * Return items inside a block.
 *
 * @param {HTMLElement} block
 * @returns {HTMLElement[]}
 */
const getItems = block => Array.from(block.querySelectorAll(SELECTORS.item));

/**
 * Return a canonical block id for reporting/event details.
 *
 * @param {HTMLElement} block
 * @returns {String}
 */
const getBlockId = block => block.dataset.blockId || block.id || '';

/**
 * Return the dynamic block type.
 *
 * @param {HTMLElement} block
 * @returns {String}
 */
const getBlockType = block => block.dataset.blockType || block.dataset.type || '';

/**
 * Return the provider id rendered by the server, when present.
 *
 * @param {HTMLElement} block
 * @returns {String}
 */
const getProvider = block => block.dataset.provider || '';

/**
 * Parse a positive integer from an attribute.
 *
 * @param {String|null} value
 * @param {Number} fallback
 * @returns {Number}
 */
const parsePositiveInt = (value, fallback) => {
    const parsed = window.parseInt(value || '', 10);

    if (Number.isNaN(parsed) || parsed < 1) {
        return fallback;
    }

    return parsed;
};

/**
 * Set a block loading state.
 *
 * @param {HTMLElement} block
 * @param {Boolean} loading
 */
const setLoading = (block, loading) => {
    const loadingRegion = block.querySelector(SELECTORS.loading);

    block.classList.toggle(CLASSES.loading, loading);
    block.setAttribute(ATTRIBUTES.loading, loading ? 'true' : 'false');
    block.setAttribute('aria-busy', loading ? 'true' : 'false');

    if (loadingRegion) {
        loadingRegion.hidden = !loading;
    }
};

/**
 * Set a block error state.
 *
 * @param {HTMLElement} block
 * @param {Boolean} hasError
 */
const setError = (block, hasError) => {
    const errorRegion = block.querySelector(SELECTORS.error);

    block.classList.toggle(CLASSES.error, hasError);
    block.setAttribute(ATTRIBUTES.error, hasError ? 'true' : 'false');

    if (errorRegion) {
        errorRegion.hidden = !hasError;
    }
};

/**
 * Set block empty state based on rendered items.
 *
 * @param {HTMLElement} block
 * @param {Object} options
 */
const updateEmptyState = (block, options = {}) => {
    const config = normaliseOptions(options);
    const items = getItems(block);
    const visibleItems = items.filter(item => !item.hidden);
    const isEmpty = visibleItems.length === 0;
    const emptyRegion = block.querySelector(SELECTORS.empty);
    const contentRegion = block.querySelector(SELECTORS.content);

    block.classList.toggle(CLASSES.empty, isEmpty);
    block.classList.toggle(CLASSES.hasItems, !isEmpty);
    block.classList.toggle(CLASSES.noItems, isEmpty);
    block.setAttribute(ATTRIBUTES.empty, isEmpty ? 'true' : 'false');

    if (emptyRegion) {
        emptyRegion.hidden = !isEmpty;
    }

    if (contentRegion) {
        contentRegion.hidden = isEmpty && !!emptyRegion;
    }

    if (config.collapseEmptyBlocks && isEmpty) {
        setCollapsed(block, true);
    }
};

/**
 * Update visible count.
 *
 * @param {HTMLElement} block
 */
const updateCount = block => {
    const count = block.querySelector(SELECTORS.count);

    if (!count) {
        return;
    }

    const total = getItems(block).length;
    const visible = getItems(block).filter(item => !item.hidden).length;

    count.textContent = `${visible} / ${total}`;
    count.setAttribute('aria-live', 'polite');
};

/**
 * Set collapsed state on a block.
 *
 * @param {HTMLElement} block
 * @param {Boolean} collapsed
 */
const setCollapsed = (block, collapsed) => {
    const content = block.querySelector(SELECTORS.content);

    block.classList.toggle(CLASSES.collapsed, collapsed);
    block.classList.toggle(CLASSES.expanded, !collapsed);
    block.setAttribute(ATTRIBUTES.collapsed, collapsed ? 'true' : 'false');

    if (content) {
        content.hidden = collapsed;
    }

    block.querySelectorAll(SELECTORS.toggle).forEach(button => {
        const controls = button.getAttribute('aria-controls') || '';

        if (!controls || (content && controls === content.id)) {
            button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
    });
};

/**
 * Toggle a block or controlled panel.
 *
 * @param {HTMLElement} button
 * @param {HTMLElement} root
 */
const toggleBlock = (button, root) => {
    const controls = button.getAttribute('aria-controls') || '';
    const block = button.closest(SELECTORS.block);

    if (!block || !root.contains(block)) {
        return;
    }

    if (controls) {
        const panel = resolveControlledElement(root, controls);
        if (panel) {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            const next = !expanded;

            button.setAttribute('aria-expanded', next ? 'true' : 'false');
            panel.hidden = !next;

            dispatch(root, EVENTS.blockToggled, {
                blockId: getBlockId(block),
                blockType: getBlockType(block),
                provider: getProvider(block),
                expanded: next,
            });
            return;
        }
    }

    const collapsed = block.getAttribute(ATTRIBUTES.collapsed) === 'true';
    setCollapsed(block, !collapsed);

    dispatch(root, EVENTS.blockToggled, {
        blockId: getBlockId(block),
        blockType: getBlockType(block),
        provider: getProvider(block),
        expanded: collapsed,
    });
};

/**
 * Apply a client-side item limit to one block.
 *
 * @param {HTMLElement} block
 * @param {Object} options
 */
const applyItemLimit = (block, options = {}) => {
    const config = normaliseOptions(options);
    const limit = parsePositiveInt(block.getAttribute(ATTRIBUTES.limit), config.defaultItemLimit);
    const items = getItems(block);
    const showMore = block.querySelector(SELECTORS.showMore);

    items.forEach((item, index) => {
        if (!item.hasAttribute(ATTRIBUTES.originalIndex)) {
            item.setAttribute(ATTRIBUTES.originalIndex, String(index + 1));
        }

        const visible = index < limit;

        item.hidden = !visible;
        item.setAttribute(ATTRIBUTES.visible, visible ? 'true' : 'false');
    });

    if (showMore) {
        showMore.hidden = items.length <= limit;
        showMore.disabled = items.length <= limit;
        showMore.setAttribute('aria-expanded', 'false');
    }
};

/**
 * Show all currently rendered items in one block.
 *
 * @param {HTMLElement} button
 * @param {HTMLElement} root
 * @param {Object} options
 */
const showMore = (button, root, options = {}) => {
    const config = normaliseOptions(options);

    if (!config.allowClientShowMore) {
        return;
    }

    const block = button.closest(SELECTORS.block);

    if (!block || !root.contains(block)) {
        return;
    }

    getItems(block).forEach(item => {
        item.hidden = false;
        item.setAttribute(ATTRIBUTES.visible, 'true');
    });

    button.hidden = true;
    button.disabled = true;
    button.setAttribute('aria-expanded', 'true');

    updateEmptyState(block, config);

    if (config.updateCounts) {
        updateCount(block);
    }

    dispatch(root, EVENTS.showMore, {
        blockId: getBlockId(block),
        blockType: getBlockType(block),
        provider: getProvider(block),
        total: getItems(block).length,
    });
};

/**
 * Resolve current type filter.
 *
 * @param {HTMLElement} root
 * @param {Object} options
 * @returns {String}
 */
const getCurrentFilter = (root, options = {}) => {
    const config = normaliseOptions(options);
    const control = root.querySelector(SELECTORS.filter);

    if (!control || !('value' in control)) {
        return config.defaultFilter;
    }

    const value = String(control.value || config.defaultFilter);

    return ALLOWED_FILTERS.indexOf(value) === -1 ? config.defaultFilter : value;
};

/**
 * Apply root-level block filter.
 *
 * @param {HTMLElement} root
 * @param {Object} options
 */
const applyFilter = (root, options = {}) => {
    const config = normaliseOptions(options);
    const filter = getCurrentFilter(root, config);
    let visible = 0;

    getBlocks(root).forEach(block => {
        const type = getBlockType(block);
        const matches = filter === 'all' || type === filter;

        block.hidden = !matches;
        block.classList.toggle(CLASSES.filtered, !matches);
        block.setAttribute(ATTRIBUTES.filtered, matches ? 'false' : 'true');
        block.setAttribute(ATTRIBUTES.visible, matches ? 'true' : 'false');

        if (matches) {
            visible++;
        }
    });

    dispatch(root, EVENTS.updated, {
        visible,
        total: getBlocks(root).length,
        filter,
    });
};

/**
 * Reset block filter and client-side limits.
 *
 * @param {HTMLElement} root
 * @param {Object} options
 */
const reset = (root, options = {}) => {
    const config = normaliseOptions(options);
    const control = root.querySelector(SELECTORS.filter);

    if (control && 'value' in control) {
        control.value = config.defaultFilter;
    }

    getBlocks(root).forEach(block => {
        applyItemLimit(block, config);
        updateEmptyState(block, config);

        if (config.updateCounts) {
            updateCount(block);
        }
    });

    applyFilter(root, config);

    dispatch(root, EVENTS.reset, {});
};

/**
 * Dispatch a refresh request event.
 *
 * The module does not perform the refresh itself. Server-side Moodle code may
 * listen to the event, or another module may handle it using a documented,
 * permission-checked service.
 *
 * @param {HTMLElement} button
 * @param {HTMLElement} root
 */
const requestRefresh = (button, root) => {
    const block = button.closest(SELECTORS.block);

    if (!block || !root.contains(block)) {
        return;
    }

    setLoading(block, true);
    setError(block, false);

    dispatch(root, EVENTS.refreshRequested, {
        blockId: getBlockId(block),
        blockType: getBlockType(block),
        provider: getProvider(block),
    });

    window.setTimeout(() => {
        setLoading(block, false);
    }, 0);
};

/**
 * Prepare one block.
 *
 * @param {HTMLElement} block
 * @param {Object} options
 */
const prepareBlock = (block, options = {}) => {
    const config = normaliseOptions(options);

    block.classList.add(CLASSES.enhanced);
    block.setAttribute(ATTRIBUTES.enhanced, 'true');

    setLoading(block, block.getAttribute(ATTRIBUTES.loading) === 'true');
    setError(block, block.getAttribute(ATTRIBUTES.error) === 'true');

    const explicitCollapsed = block.getAttribute(ATTRIBUTES.collapsed);
    if (explicitCollapsed === 'true' || explicitCollapsed === 'false') {
        setCollapsed(block, explicitCollapsed === 'true');
    }

    applyItemLimit(block, config);
    updateEmptyState(block, config);

    if (config.updateCounts) {
        updateCount(block);
    }
};

/**
 * Handle root clicks.
 *
 * @param {MouseEvent} event
 * @param {HTMLElement} root
 * @param {Object} options
 */
const handleClick = (event, root, options = {}) => {
    const toggle = event.target.closest(SELECTORS.toggle);
    if (toggle && root.contains(toggle)) {
        event.preventDefault();
        toggleBlock(toggle, root);
        return;
    }

    const more = event.target.closest(SELECTORS.showMore);
    if (more && root.contains(more)) {
        event.preventDefault();
        showMore(more, root, options);
        return;
    }

    const resetButton = event.target.closest(SELECTORS.reset);
    if (resetButton && root.contains(resetButton)) {
        event.preventDefault();
        reset(root, options);
        return;
    }

    const refreshButton = event.target.closest(SELECTORS.refresh);
    if (refreshButton && root.contains(refreshButton)) {
        event.preventDefault();
        requestRefresh(refreshButton, root);
    }
};

/**
 * Handle root filter changes.
 *
 * @param {Event} event
 * @param {HTMLElement} root
 * @param {Object} options
 */
const handleChange = (event, root, options = {}) => {
    if (event.target.closest(SELECTORS.filter)) {
        applyFilter(root, options);
    }
};

/**
 * Initialise one root.
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

    getBlocks(root).forEach(block => {
        prepareBlock(block, config);
    });

    applyFilter(root, config);

    root.addEventListener('click', event => {
        handleClick(event, root, config);
    });

    root.addEventListener('change', event => {
        handleChange(event, root, config);
    });

    dispatch(root, EVENTS.ready, {
        total: getBlocks(root).length,
    });
};

/**
 * Initialise Faculty dynamic block interactions.
 *
 * Recommended usage:
 *
 * $PAGE->requires->js_call_amd('local_uckk/faculty_dynamic_blocks', 'init');
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
 * Recompute block visibility, limits and counters.
 *
 * @param {String|HTMLElement|Object|null} root Root id, selector, element, or options object.
 * @param {Object} options Behaviour options.
 */
export const refresh = (root = null, options = {}) => {
    const config = normaliseOptions(options);

    resolveRoots(root).forEach(element => {
        getBlocks(element).forEach(block => {
            prepareBlock(block, config);
        });

        applyFilter(element, config);
    });
};

/**
 * Reset filters and item limits on already-rendered dynamic blocks.
 *
 * @param {String|HTMLElement|Object|null} root Root id, selector, element, or options object.
 * @param {Object} options Behaviour options.
 */
export const resetBlocks = (root = null, options = {}) => {
    resolveRoots(root).forEach(element => reset(element, options));
};

export default {
    init,
    refresh,
    resetBlocks,
};