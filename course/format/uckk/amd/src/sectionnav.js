// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Section navigation for the UCKK course format.
 *
 * This module enhances the UCKK course format section navigation.
 * It is intentionally UI-only:
 *
 * - no permission checks;
 * - no grading logic;
 * - no archive validation;
 * - no challenge workflow;
 * - no assembly workflow;
 * - no integrity decision.
 *
 * PHP output classes and Mustache templates must provide the DOM structure,
 * URLs, data attributes and access decisions. This script only improves
 * navigation, accessibility and active-section feedback.
 *
 * Expected optional DOM:
 *
 * <nav data-region="uckk-section-nav">
 *     <a href="#section-0" data-uckk-section-link data-sectionnum="0">Orientation</a>
 *     ...
 * </nav>
 *
 * <li id="section-0" data-uckk-section data-sectionnum="0" data-sectiontype="orientation">
 *     ...
 * </li>
 *
 * @module     format_uckk/sectionnav
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    root: '[data-region="format-uckk-course"], .format-uckk, body',
    nav: '[data-region="uckk-section-nav"]',
    navList: '[data-region="uckk-section-nav-list"]',
    link: '[data-uckk-section-link]',
    section: '[data-uckk-section]',
    sectionFallback: '[id^="section-"]',
    activeLink: '[data-uckk-section-link][aria-current="true"]',
    nextButton: '[data-action="uckk-next-section"]',
    previousButton: '[data-action="uckk-previous-section"]',
    progress: '[data-region="uckk-section-progress"]',
};

const CLASSES = {
    active: 'active',
    current: 'uckk-section-current',
    navReady: 'uckk-section-nav-ready',
    sticky: 'uckk-section-nav-sticky',
};

const ATTRIBUTES = {
    sectionNumber: 'data-sectionnum',
    sectionType: 'data-sectiontype',
    link: 'data-uckk-section-link',
    section: 'data-uckk-section',
    initialized: 'data-uckk-sectionnav-initialized',
};

const DEFAULT_SECTION_TYPES = [
    'orientation',
    'concepts',
    'canon',
    'atelier',
    'preuves',
    'deliberation',
    'livrable',
    'evaluation',
    'archive',
];

/**
 * Runtime state.
 *
 * @type {Object}
 */
const state = {
    root: null,
    nav: null,
    links: [],
    sections: [],
    observer: null,
    currentSectionNumber: null,
    initialized: false,
};

/**
 * Initialise the UCKK section navigation.
 *
 * This is the function called by Moodle:
 *
 * $PAGE->requires->js_call_amd('format_uckk/sectionnav', 'init');
 *
 * @param {String} rootSelector Optional root selector.
 */
export const init = (rootSelector = SELECTORS.root) => {
    const root = document.querySelector(rootSelector) || document.body;

    if (!root || root.getAttribute(ATTRIBUTES.initialized) === '1') {
        return;
    }

    state.root = root;
    state.nav = root.querySelector(SELECTORS.nav) || document.querySelector(SELECTORS.nav);
    state.sections = getSections(root);
    state.links = getLinks(root);

    if (!state.sections.length) {
        return;
    }

    root.setAttribute(ATTRIBUTES.initialized, '1');

    normaliseSections();
    normaliseLinks();

    bindLinkNavigation();
    bindKeyboardNavigation();
    bindPreviousNextButtons();
    observeSections();

    updateFromHash();
    updateProgress();

    if (state.nav) {
        state.nav.classList.add(CLASSES.navReady);
    }

    state.initialized = true;

    dispatchSectionEvent('ready', {
        sections: state.sections.length,
    });
};

/**
 * Destroy the current section navigation instance.
 *
 * Useful for tests and dynamic page refreshes.
 */
export const destroy = () => {
    if (state.observer) {
        state.observer.disconnect();
    }

    if (state.root) {
        state.root.removeAttribute(ATTRIBUTES.initialized);
    }

    state.root = null;
    state.nav = null;
    state.links = [];
    state.sections = [];
    state.observer = null;
    state.currentSectionNumber = null;
    state.initialized = false;
};

/**
 * Get section nodes.
 *
 * @param {HTMLElement} root Root node.
 * @returns {HTMLElement[]}
 */
const getSections = root => {
    const explicit = Array.from(root.querySelectorAll(SELECTORS.section));

    if (explicit.length) {
        return explicit;
    }

    return Array.from(root.querySelectorAll(SELECTORS.sectionFallback))
        .filter(section => getSectionNumber(section) !== null);
};

/**
 * Get navigation links.
 *
 * @param {HTMLElement} root Root node.
 * @returns {HTMLElement[]}
 */
const getLinks = root => {
    const scopedLinks = Array.from(root.querySelectorAll(SELECTORS.link));

    if (scopedLinks.length) {
        return scopedLinks;
    }

    return Array.from(document.querySelectorAll(SELECTORS.link));
};

/**
 * Ensure all detected sections expose UCKK section data.
 */
const normaliseSections = () => {
    state.sections.forEach((section, index) => {
        const sectionNumber = getSectionNumber(section);

        if (sectionNumber === null) {
            section.setAttribute(ATTRIBUTES.sectionNumber, String(index));
        }

        if (!section.hasAttribute(ATTRIBUTES.section)) {
            section.setAttribute(ATTRIBUTES.section, '1');
        }

        if (!section.hasAttribute(ATTRIBUTES.sectionType)) {
            const number = getSectionNumber(section) ?? index;
            section.setAttribute(ATTRIBUTES.sectionType, getDefaultSectionType(number));
        }

        if (!section.id) {
            section.id = `section-${getSectionNumber(section) ?? index}`;
        }

        section.setAttribute('tabindex', '-1');
    });
};

/**
 * Ensure all links expose accessible navigation state.
 */
const normaliseLinks = () => {
    state.links.forEach(link => {
        if (!link.hasAttribute('role')) {
            link.setAttribute('role', 'link');
        }

        if (!link.hasAttribute(ATTRIBUTES.sectionNumber)) {
            const target = getLinkTarget(link);
            const section = target ? document.querySelector(target) : null;
            const sectionNumber = section ? getSectionNumber(section) : null;

            if (sectionNumber !== null) {
                link.setAttribute(ATTRIBUTES.sectionNumber, String(sectionNumber));
            }
        }

        link.setAttribute('aria-current', 'false');
    });
};

/**
 * Bind click navigation on section links.
 */
const bindLinkNavigation = () => {
    state.links.forEach(link => {
        link.addEventListener('click', event => {
            const targetSelector = getLinkTarget(link);

            if (!targetSelector) {
                return;
            }

            const section = document.querySelector(targetSelector);

            if (!section) {
                return;
            }

            event.preventDefault();

            scrollToSection(section, true);
            setActiveSection(getSectionNumber(section), true);
        });
    });
};

/**
 * Bind keyboard navigation for the section nav.
 */
const bindKeyboardNavigation = () => {
    if (!state.nav) {
        return;
    }

    state.nav.addEventListener('keydown', event => {
        const currentIndex = getCurrentLinkIndex();

        if (currentIndex === -1) {
            return;
        }

        switch (event.key) {
            case 'ArrowLeft':
            case 'ArrowUp':
                event.preventDefault();
                focusLinkByIndex(currentIndex - 1);
                break;

            case 'ArrowRight':
            case 'ArrowDown':
                event.preventDefault();
                focusLinkByIndex(currentIndex + 1);
                break;

            case 'Home':
                event.preventDefault();
                focusLinkByIndex(0);
                break;

            case 'End':
                event.preventDefault();
                focusLinkByIndex(state.links.length - 1);
                break;

            case 'Enter':
            case ' ':
                if (document.activeElement && document.activeElement.matches(SELECTORS.link)) {
                    event.preventDefault();
                    document.activeElement.click();
                }
                break;

            default:
                break;
        }
    });
};

/**
 * Bind previous / next buttons when present.
 */
const bindPreviousNextButtons = () => {
    const previousButtons = Array.from(document.querySelectorAll(SELECTORS.previousButton));
    const nextButtons = Array.from(document.querySelectorAll(SELECTORS.nextButton));

    previousButtons.forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            navigateRelative(-1);
        });
    });

    nextButtons.forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            navigateRelative(1);
        });
    });
};

/**
 * Observe visible sections to keep navigation state updated.
 */
const observeSections = () => {
    if (!('IntersectionObserver' in window)) {
        return;
    }

    state.observer = new IntersectionObserver(entries => {
        const visible = entries
            .filter(entry => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

        if (!visible.length) {
            return;
        }

        const section = visible[0].target;
        setActiveSection(getSectionNumber(section), false);
    }, {
        root: null,
        rootMargin: '-20% 0px -65% 0px',
        threshold: [0.15, 0.3, 0.6],
    });

    state.sections.forEach(section => state.observer.observe(section));
};

/**
 * Activate section from current URL hash when possible.
 */
const updateFromHash = () => {
    if (!window.location.hash) {
        const first = state.sections[0];

        if (first) {
            setActiveSection(getSectionNumber(first), false);
        }

        return;
    }

    const section = document.querySelector(window.location.hash);

    if (section && state.sections.includes(section)) {
        setActiveSection(getSectionNumber(section), false);
    }
};

/**
 * Navigate to the previous or next section.
 *
 * @param {Number} direction -1 or +1.
 */
const navigateRelative = direction => {
    const current = getCurrentSectionIndex();

    if (current === -1) {
        return;
    }

    const targetIndex = clamp(current + direction, 0, state.sections.length - 1);
    const section = state.sections[targetIndex];

    if (!section) {
        return;
    }

    scrollToSection(section, true);
    setActiveSection(getSectionNumber(section), true);
};

/**
 * Scroll to a section.
 *
 * @param {HTMLElement} section Section node.
 * @param {Boolean} updateHash Whether to update the URL hash.
 */
const scrollToSection = (section, updateHash = true) => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    section.scrollIntoView({
        behavior: prefersReducedMotion ? 'auto' : 'smooth',
        block: 'start',
    });

    section.focus({
        preventScroll: true,
    });

    if (updateHash && section.id) {
        replaceHash(section.id);
    }
};

/**
 * Set active section state.
 *
 * @param {Number|null} sectionNumber Section number.
 * @param {Boolean} userInitiated Whether the change came from user action.
 */
const setActiveSection = (sectionNumber, userInitiated = false) => {
    if (sectionNumber === null || sectionNumber === state.currentSectionNumber) {
        return;
    }

    state.currentSectionNumber = sectionNumber;

    state.sections.forEach(section => {
        const isCurrent = getSectionNumber(section) === sectionNumber;
        section.classList.toggle(CLASSES.current, isCurrent);
        section.setAttribute('aria-current', isCurrent ? 'true' : 'false');
    });

    state.links.forEach(link => {
        const isCurrent = getSectionNumberFromLink(link) === sectionNumber;

        link.classList.toggle(CLASSES.active, isCurrent);
        link.setAttribute('aria-current', isCurrent ? 'true' : 'false');

        if (isCurrent) {
            link.setAttribute('tabindex', '0');
        } else {
            link.setAttribute('tabindex', '-1');
        }
    });

    updateProgress();

    dispatchSectionEvent('change', {
        sectionNumber,
        sectionType: getDefaultSectionType(sectionNumber),
        userInitiated,
    });
};

/**
 * Update progress indicator when present.
 */
const updateProgress = () => {
    const progressNodes = Array.from(document.querySelectorAll(SELECTORS.progress));

    if (!progressNodes.length || !state.sections.length) {
        return;
    }

    const currentIndex = getCurrentSectionIndex();
    const percent = currentIndex === -1 ? 0 : Math.round(((currentIndex + 1) / state.sections.length) * 100);

    progressNodes.forEach(node => {
        node.style.width = `${percent}%`;
        node.setAttribute('aria-valuenow', String(percent));
        node.setAttribute('data-progress', String(percent));
        node.textContent = node.hasAttribute('data-show-text') ? `${percent}%` : node.textContent;
    });
};

/**
 * Get link target selector.
 *
 * @param {HTMLElement} link Link node.
 * @returns {String|null}
 */
const getLinkTarget = link => {
    const href = link.getAttribute('href');

    if (!href || href.charAt(0) !== '#') {
        return null;
    }

    return href;
};

/**
 * Get a section number from a section node.
 *
 * @param {HTMLElement} section Section node.
 * @returns {Number|null}
 */
const getSectionNumber = section => {
    if (!section) {
        return null;
    }

    const explicit = section.getAttribute(ATTRIBUTES.sectionNumber);

    if (explicit !== null && explicit !== '') {
        const parsed = Number.parseInt(explicit, 10);
        return Number.isNaN(parsed) ? null : parsed;
    }

    const id = section.getAttribute('id') || '';
    const match = id.match(/^section-(\d+)$/);

    if (!match) {
        return null;
    }

    const parsed = Number.parseInt(match[1], 10);
    return Number.isNaN(parsed) ? null : parsed;
};

/**
 * Get the section number referenced by a nav link.
 *
 * @param {HTMLElement} link Link node.
 * @returns {Number|null}
 */
const getSectionNumberFromLink = link => {
    const explicit = link.getAttribute(ATTRIBUTES.sectionNumber);

    if (explicit !== null && explicit !== '') {
        const parsed = Number.parseInt(explicit, 10);
        return Number.isNaN(parsed) ? null : parsed;
    }

    const target = getLinkTarget(link);
    const section = target ? document.querySelector(target) : null;

    return getSectionNumber(section);
};

/**
 * Get current section index in state.sections.
 *
 * @returns {Number}
 */
const getCurrentSectionIndex = () => {
    if (state.currentSectionNumber === null) {
        return -1;
    }

    return state.sections.findIndex(section => getSectionNumber(section) === state.currentSectionNumber);
};

/**
 * Get current nav link index.
 *
 * @returns {Number}
 */
const getCurrentLinkIndex = () => {
    const activeElement = document.activeElement;

    if (activeElement && activeElement.matches(SELECTORS.link)) {
        return state.links.indexOf(activeElement);
    }

    return state.links.findIndex(link => link.getAttribute('aria-current') === 'true');
};

/**
 * Focus a nav link by index.
 *
 * @param {Number} index Requested index.
 */
const focusLinkByIndex = index => {
    if (!state.links.length) {
        return;
    }

    const targetIndex = clamp(index, 0, state.links.length - 1);
    const link = state.links[targetIndex];

    if (link) {
        link.focus();
    }
};

/**
 * Replace hash without creating a new history entry.
 *
 * @param {String} id Element id.
 */
const replaceHash = id => {
    if (!id || !window.history || !window.history.replaceState) {
        return;
    }

    const url = new URL(window.location.href);
    url.hash = id;

    window.history.replaceState(null, '', url.toString());
};

/**
 * Get the default UCKK section type by number.
 *
 * @param {Number} sectionNumber Section number.
 * @returns {String}
 */
const getDefaultSectionType = sectionNumber => {
    return DEFAULT_SECTION_TYPES[sectionNumber] || 'extension';
};

/**
 * Clamp a number.
 *
 * @param {Number} value Value.
 * @param {Number} min Minimum.
 * @param {Number} max Maximum.
 * @returns {Number}
 */
const clamp = (value, min, max) => {
    return Math.min(Math.max(value, min), max);
};

/**
 * Dispatch a UCKK section navigation event.
 *
 * @param {String} name Event suffix.
 * @param {Object} detail Event detail.
 */
const dispatchSectionEvent = (name, detail = {}) => {
    document.dispatchEvent(new CustomEvent(`format_uckk:sectionnav:${name}`, {
        bubbles: true,
        detail,
    }));
};