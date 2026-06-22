// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Faculty public page interactions for local_uckk.
 *
 * This module is intentionally UI-only:
 * - it does not read Atlas JSON files;
 * - it does not read Faculty JSON files;
 * - it does not decide visibility, enrolment, completion, badges, or permissions;
 * - it does not fetch dynamic blocks;
 * - it does not modify Moodle data;
 * - it only enhances already-rendered public markup.
 *
 * Expected root hook:
 *
 * <main data-region="local-uckk-faculty-page">
 *     ...
 * </main>
 *
 * Supported optional hooks:
 *
 * data-region="local-uckk-faculty-navigation"
 * data-region="local-uckk-faculty-section"
 * data-region="local-uckk-faculty-course-card"
 * data-region="local-uckk-faculty-dynamic-block"
 * data-region="local-uckk-faculty-faq"
 * data-region="local-uckk-faculty-notice"
 *
 * data-action="local-uckk-faculty-nav"
 * data-action="local-uckk-faculty-toggle"
 * data-action="local-uckk-faculty-dismiss-notice"
 *
 * @module     local_uckk/faculty_page
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    root: '[data-region="local-uckk-faculty-page"]',
    navigation: '[data-region="local-uckk-faculty-navigation"]',
    navigationLink: '[data-action="local-uckk-faculty-nav"], a[href^="#"]',
    section: '[data-region="local-uckk-faculty-section"], section[id]',
    toggle: '[data-action="local-uckk-faculty-toggle"]',
    noticeDismiss: '[data-action="local-uckk-faculty-dismiss-notice"]',
    notice: '[data-region="local-uckk-faculty-notice"]',
    courseCard: '[data-region="local-uckk-faculty-course-card"]',
    dynamicBlock: '[data-region="local-uckk-faculty-dynamic-block"]',
    faq: '[data-region="local-uckk-faculty-faq"]',
};

const ATTRIBUTES = {
    initialised: 'data-local-uckk-faculty-page-initialised',
    active: 'data-active',
    dismissed: 'data-dismissed',
    enhanced: 'data-enhanced',
};

const CLASSES = {
    active: 'is-active',
    dismissed: 'is-dismissed',
    enhanced: 'is-enhanced',
    jsReady: 'is-js-ready',
};

const EVENTS = {
    ready: 'local_uckk:facultyPageReady',
    sectionChanged: 'local_uckk:facultySectionChanged',
    noticeDismissed: 'local_uckk:facultyNoticeDismissed',
};

const DEFAULT_OPTIONS = {
    smoothScroll: true,
    updateHash: true,
    observeSections: true,
    enhanceDetails: true,
    dismissNotices: true,
};

/**
 * Safely merge init options.
 *
 * @param {Object} options
 * @returns {Object}
 */
const normaliseOptions = (options = {}) => ({
    ...DEFAULT_OPTIONS,
    ...(options || {}),
});

/**
 * Return true when a value is an HTMLElement.
 *
 * @param {*} value
 * @returns {Boolean}
 */
const isElement = value => value instanceof HTMLElement;

/**
 * Resolve root elements from a Moodle AMD init argument.
 *
 * Supported calls:
 *
 * FacultyPage.init()
 * FacultyPage.init('root-id')
 * FacultyPage.init('#selector')
 * FacultyPage.init(element)
 * FacultyPage.init({rootId: 'root-id'})
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

    return Array.from(document.querySelectorAll(SELECTORS.root));
};

/**
 * Get a safe target id from a link href.
 *
 * @param {HTMLElement} link
 * @returns {String}
 */
const getHashTargetId = link => {
    if (!link || !link.getAttribute) {
        return '';
    }

    const href = link.getAttribute('href') || '';
    if (href.charAt(0) !== '#') {
        return '';
    }

    try {
        return decodeURIComponent(href.substring(1));
    } catch (error) {
        return href.substring(1);
    }
};

/**
 * Resolve an in-page target.
 *
 * @param {HTMLElement} root
 * @param {String} targetId
 * @returns {HTMLElement|null}
 */
const resolveTarget = (root, targetId) => {
    if (!targetId) {
        return null;
    }

    const escaped = window.CSS && window.CSS.escape ? window.CSS.escape(targetId) : targetId.replace(/"/g, '\\"');

    return root.querySelector(`#${escaped}`) || document.getElementById(targetId);
};

/**
 * Dispatch a public DOM event from the root element.
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
 * Set focus on a target after navigation.
 *
 * @param {HTMLElement} target
 */
const focusTarget = target => {
    if (!target) {
        return;
    }

    const hadTabIndex = target.hasAttribute('tabindex');

    if (!hadTabIndex) {
        target.setAttribute('tabindex', '-1');
    }

    target.focus({preventScroll: true});

    if (!hadTabIndex) {
        target.addEventListener('blur', () => {
            target.removeAttribute('tabindex');
        }, {once: true});
    }
};

/**
 * Mark the active navigation link.
 *
 * @param {HTMLElement} root
 * @param {String} sectionId
 */
const setActiveNavigation = (root, sectionId) => {
    const navigation = root.querySelector(SELECTORS.navigation) || root;
    const links = Array.from(navigation.querySelectorAll(SELECTORS.navigationLink));

    links.forEach(link => {
        const currentId = getHashTargetId(link);
        const active = currentId === sectionId;

        link.classList.toggle(CLASSES.active, active);
        link.setAttribute(ATTRIBUTES.active, active ? 'true' : 'false');

        if (active) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    });

    if (sectionId) {
        dispatch(root, EVENTS.sectionChanged, {sectionId});
    }
};

/**
 * Handle in-page navigation clicks.
 *
 * @param {MouseEvent} event
 * @param {HTMLElement} root
 * @param {Object} options
 */
const handleNavigationClick = (event, root, options) => {
    const link = event.target.closest(SELECTORS.navigationLink);

    if (!link || !root.contains(link)) {
        return;
    }

    const targetId = getHashTargetId(link);
    const target = resolveTarget(root, targetId);

    if (!target) {
        return;
    }

    event.preventDefault();

    if (options.smoothScroll && typeof target.scrollIntoView === 'function') {
        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    } else {
        target.scrollIntoView();
    }

    focusTarget(target);
    setActiveNavigation(root, targetId);

    if (options.updateHash && window.history && window.history.pushState) {
        window.history.pushState(null, '', `#${encodeURIComponent(targetId)}`);
    }
};

/**
 * Toggle a panel controlled by aria-controls.
 *
 * @param {HTMLElement} button
 * @param {HTMLElement} root
 */
const toggleControlledPanel = (button, root) => {
    const controls = button.getAttribute('aria-controls') || '';
    const panel = resolveTarget(root, controls);

    if (!panel) {
        return;
    }

    const expanded = button.getAttribute('aria-expanded') === 'true';
    const next = !expanded;

    button.setAttribute('aria-expanded', next ? 'true' : 'false');
    panel.hidden = !next;
};

/**
 * Dismiss a rendered notice in the current session.
 *
 * @param {HTMLElement} button
 * @param {HTMLElement} root
 */
const dismissNotice = (button, root) => {
    const notice = button.closest(SELECTORS.notice);

    if (!notice || !root.contains(notice)) {
        return;
    }

    notice.classList.add(CLASSES.dismissed);
    notice.setAttribute(ATTRIBUTES.dismissed, 'true');
    notice.hidden = true;

    dispatch(root, EVENTS.noticeDismissed, {
        id: notice.id || '',
    });
};

/**
 * Handle delegated root clicks.
 *
 * @param {MouseEvent} event
 * @param {HTMLElement} root
 * @param {Object} options
 */
const handleClick = (event, root, options) => {
    const toggle = event.target.closest(SELECTORS.toggle);
    if (toggle && root.contains(toggle)) {
        event.preventDefault();
        toggleControlledPanel(toggle, root);
        return;
    }

    const dismiss = event.target.closest(SELECTORS.noticeDismiss);
    if (dismiss && root.contains(dismiss) && options.dismissNotices) {
        event.preventDefault();
        dismissNotice(dismiss, root);
        return;
    }

    handleNavigationClick(event, root, options);
};

/**
 * Ensure toggle buttons and panels have a coherent initial state.
 *
 * @param {HTMLElement} root
 */
const prepareToggles = root => {
    root.querySelectorAll(SELECTORS.toggle).forEach(button => {
        if (button.getAttribute(ATTRIBUTES.enhanced) === 'true') {
            return;
        }

        button.setAttribute(ATTRIBUTES.enhanced, 'true');

        const controls = button.getAttribute('aria-controls') || '';
        const panel = resolveTarget(root, controls);

        if (!panel) {
            return;
        }

        const expanded = button.getAttribute('aria-expanded') === 'true';

        panel.hidden = !expanded;
    });
};

/**
 * Mark progressive-enhancement regions as enhanced.
 *
 * @param {HTMLElement} root
 */
const markEnhancedRegions = root => {
    [
        SELECTORS.courseCard,
        SELECTORS.dynamicBlock,
        SELECTORS.faq,
        SELECTORS.notice,
    ].forEach(selector => {
        root.querySelectorAll(selector).forEach(element => {
            element.classList.add(CLASSES.enhanced);
            element.setAttribute(ATTRIBUTES.enhanced, 'true');
        });
    });
};

/**
 * Track active section using IntersectionObserver.
 *
 * @param {HTMLElement} root
 * @returns {IntersectionObserver|null}
 */
const observeSections = root => {
    if (!('IntersectionObserver' in window)) {
        return null;
    }

    const sections = Array.from(root.querySelectorAll(SELECTORS.section))
        .filter(section => section.id);

    if (sections.length === 0) {
        return null;
    }

    let current = '';

    const observer = new IntersectionObserver(entries => {
        const visible = entries
            .filter(entry => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)
            .shift();

        if (!visible || !visible.target || visible.target.id === current) {
            return;
        }

        current = visible.target.id;
        setActiveNavigation(root, current);
    }, {
        root: null,
        rootMargin: '-20% 0px -60% 0px',
        threshold: [0.1, 0.25, 0.5, 0.75],
    });

    sections.forEach(section => observer.observe(section));

    return observer;
};

/**
 * Activate navigation from the current URL hash when possible.
 *
 * @param {HTMLElement} root
 */
const activateInitialHash = root => {
    const hash = window.location.hash ? window.location.hash.substring(1) : '';

    if (!hash) {
        return;
    }

    let targetId = hash;

    try {
        targetId = decodeURIComponent(hash);
    } catch (error) {
        targetId = hash;
    }

    const target = resolveTarget(root, targetId);
    if (target) {
        setActiveNavigation(root, targetId);
    }
};

/**
 * Initialise one Faculty page root.
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

    prepareToggles(root);
    markEnhancedRegions(root);
    activateInitialHash(root);

    root.addEventListener('click', event => {
        handleClick(event, root, config);
    });

    if (config.observeSections) {
        observeSections(root);
    }

    dispatch(root, EVENTS.ready, {
        rootId: root.id || '',
    });
};

/**
 * Initialise public Faculty page interactions.
 *
 * Recommended PHP usage:
 *
 * $PAGE->requires->js_call_amd('local_uckk/faculty_page', 'init');
 *
 * Recommended Mustache usage:
 *
 * {{#js}}
 * require(['local_uckk/faculty_page'], function(FacultyPage) {
 *     FacultyPage.init('{{uniqid}}-faculty-page');
 * });
 * {{/js}}
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