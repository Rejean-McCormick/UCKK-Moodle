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
 * JavaScript enhancements for the UCKK course format.
 *
 * This module is intentionally light. It does not replace Moodle's
 * core_courseformat editor, does not execute workflow decisions, does not
 * grade, does not validate integrity, and does not write data.
 *
 * It only enhances the UCKK course format DOM by:
 * - normalising UCKK section and activity markers;
 * - improving keyboard and accessibility affordances;
 * - wiring optional local UI actions declared with data attributes;
 * - reapplying enhancements after Moodle refreshes section or cmitem HTML.
 *
 * @module     format_uckk/courseformat
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Stable selectors used by the UCKK course format.
 *
 * These selectors are intentionally based on data attributes so they remain
 * compatible with Moodle's modern course format rendering approach.
 *
 * @type {Object}
 */
const SELECTORS = {
    root: '[data-region="format-uckk-course"], .format-uckk, [data-format="uckk"]',
    section: '[data-uckk-section-kind], .format-uckk-section',
    cmitem: '[data-uckk-cmid], .format-uckk-cmitem',
    badge: '.format-uckk-activity-badge',
    scrollAction: '[data-action="format-uckk-scroll-section"]',
    toggleAction: '[data-action="format-uckk-toggle-panel"]',
    focusTarget: '[data-uckk-focus-target]',
    courseIndexLink: '[data-uckk-section-link]',
    regionLabel: '[data-uckk-region-label]',
};

/**
 * UCKK section kinds.
 *
 * @type {Array}
 */
const SECTION_KINDS = [
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
 * UCKK marker types used by cmitem.php and Mustache templates.
 *
 * @type {Array}
 */
const MARKER_TYPES = [
    'proof-source',
    'deliberation-source',
    'archive-source',
    'integrity-sensitive',
];

/**
 * Convert an array-like value into an array.
 *
 * @param {NodeList|Array} nodes Nodes.
 * @returns {Array}
 */
const toArray = (nodes) => Array.prototype.slice.call(nodes || []);

/**
 * Clean a DOM token so it can be safely used as a CSS class suffix.
 *
 * @param {String} value Raw value.
 * @returns {String}
 */
const cleanToken = (value) => {
    if (typeof value !== 'string') {
        return '';
    }

    return value.toLowerCase().replace(/[^a-z0-9_-]/g, '');
};

/**
 * Find the course format root.
 *
 * @param {String|HTMLElement} rootSelector Root selector or node.
 * @returns {HTMLElement|null}
 */
const getRoot = (rootSelector) => {
    if (rootSelector instanceof HTMLElement) {
        return rootSelector;
    }

    if (typeof rootSelector === 'string' && rootSelector !== '') {
        return document.querySelector(rootSelector);
    }

    return document.querySelector(SELECTORS.root);
};

/**
 * Get all matching elements inside a root.
 *
 * @param {HTMLElement} root Root node.
 * @param {String} selector CSS selector.
 * @returns {Array}
 */
const findAll = (root, selector) => {
    if (!root) {
        return [];
    }

    return toArray(root.querySelectorAll(selector));
};

/**
 * Resolve an element by id or selector.
 *
 * @param {String} target Target id or selector.
 * @returns {HTMLElement|null}
 */
const resolveTarget = (target) => {
    if (!target || typeof target !== 'string') {
        return null;
    }

    if (target.charAt(0) === '#') {
        return document.querySelector(target);
    }

    const byId = document.getElementById(target);
    if (byId) {
        return byId;
    }

    return document.querySelector(target);
};

/**
 * Mark a root as initialised.
 *
 * @param {HTMLElement} root Root node.
 * @returns {Boolean} True when root can be initialised.
 */
const claimRoot = (root) => {
    if (!root) {
        return false;
    }

    if (root.dataset.uckkCourseformatInitialised === '1') {
        return false;
    }

    root.dataset.uckkCourseformatInitialised = '1';
    return true;
};

/**
 * Enhance UCKK sections.
 *
 * @param {HTMLElement} root Root node.
 */
const enhanceSections = (root) => {
    findAll(root, SELECTORS.section).forEach((section) => {
        const kind = cleanToken(section.dataset.uckkSectionKind || '');

        if (!kind) {
            return;
        }

        section.classList.add('format-uckk-section-' + kind);

        if (SECTION_KINDS.indexOf(kind) !== -1) {
            section.dataset.uckkKnownSection = '1';
        }

        if (!section.getAttribute('data-region')) {
            section.setAttribute('data-region', 'format-uckk-section');
        }
    });
};

/**
 * Enhance UCKK course module items.
 *
 * @param {HTMLElement} root Root node.
 */
const enhanceCmItems = (root) => {
    findAll(root, SELECTORS.cmitem).forEach((cmitem) => {
        const cmid = cmitem.dataset.uckkCmid || '';
        const moduleName = cleanToken(cmitem.dataset.uckkModule || '');
        const sectionKind = cleanToken(cmitem.dataset.uckkSectionKind || '');

        cmitem.classList.add('format-uckk-enhanced-cmitem');

        if (cmid !== '') {
            cmitem.setAttribute('data-region', 'format-uckk-cmitem');
        }

        if (moduleName !== '') {
            cmitem.classList.add('format-uckk-module-' + moduleName);
        }

        if (sectionKind !== '') {
            cmitem.classList.add('format-uckk-cmitem-section-' + sectionKind);
        }

        MARKER_TYPES.forEach((marker) => {
            const datasetKey = marker.replace(/-([a-z])/g, (match, letter) => letter.toUpperCase());
            const attrName = 'uckk' + datasetKey.charAt(0).toUpperCase() + datasetKey.slice(1);
            const value = cmitem.dataset[attrName];

            if (value === '1') {
                cmitem.classList.add('format-uckk-' + marker);
            }
        });

        enhanceActivityBadge(cmitem);
    });
};

/**
 * Enhance the UCKK activity badge inside a cm item.
 *
 * @param {HTMLElement} cmitem Course module item.
 */
const enhanceActivityBadge = (cmitem) => {
    const badge = cmitem.querySelector(SELECTORS.badge);

    if (!badge) {
        return;
    }

    const label = badge.textContent.trim();

    if (label !== '' && !badge.getAttribute('aria-label')) {
        badge.setAttribute('aria-label', label);
    }

    badge.setAttribute('data-region', 'format-uckk-activity-badge');
};

/**
 * Enhance regions that provide their own data label.
 *
 * @param {HTMLElement} root Root node.
 */
const enhanceRegionLabels = (root) => {
    findAll(root, SELECTORS.regionLabel).forEach((region) => {
        const label = region.dataset.uckkRegionLabel || '';

        if (label !== '' && !region.getAttribute('aria-label')) {
            region.setAttribute('aria-label', label);
        }
    });
};

/**
 * Bind smooth scrolling to UCKK section navigation elements.
 *
 * Expected markup:
 * <a href="#section-id" data-action="format-uckk-scroll-section" data-target="#section-id">...</a>
 *
 * @param {HTMLElement} root Root node.
 */
const bindScrollActions = (root) => {
    root.addEventListener('click', (event) => {
        const trigger = event.target.closest(SELECTORS.scrollAction + ', ' + SELECTORS.courseIndexLink);

        if (!trigger || !root.contains(trigger)) {
            return;
        }

        const targetValue = trigger.dataset.target || trigger.getAttribute('href') || '';
        const target = resolveTarget(targetValue);

        if (!target) {
            return;
        }

        event.preventDefault();

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });

        setFocusSafely(target);
    });
};

/**
 * Bind optional panel toggles.
 *
 * This is only a presentation helper. The expanded/collapsed state is local to
 * the browser and does not represent a Moodle completion or workflow state.
 *
 * Expected markup:
 * <button data-action="format-uckk-toggle-panel" data-target="#panel-id">...</button>
 *
 * @param {HTMLElement} root Root node.
 */
const bindToggleActions = (root) => {
    root.addEventListener('click', (event) => {
        const trigger = event.target.closest(SELECTORS.toggleAction);

        if (!trigger || !root.contains(trigger)) {
            return;
        }

        const target = resolveTarget(trigger.dataset.target || '');

        if (!target) {
            return;
        }

        event.preventDefault();

        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        const nextExpanded = !expanded;

        trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
        target.hidden = !nextExpanded;
        target.dataset.uckkExpanded = nextExpanded ? '1' : '0';

        if (nextExpanded) {
            setFocusSafely(target);
        }
    });
};

/**
 * Improve keyboard activation for non-button elements that intentionally behave
 * as buttons.
 *
 * @param {HTMLElement} root Root node.
 */
const bindKeyboardActivation = (root) => {
    root.addEventListener('keydown', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (target.getAttribute('role') !== 'button') {
            return;
        }

        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        target.click();
    });
};

/**
 * Focus a target safely.
 *
 * @param {HTMLElement} target Target element.
 */
const setFocusSafely = (target) => {
    if (!target) {
        return;
    }

    const focusTarget = target.matches(SELECTORS.focusTarget) ? target : target.querySelector(SELECTORS.focusTarget);
    const element = focusTarget || target;

    if (!element.hasAttribute('tabindex')) {
        element.setAttribute('tabindex', '-1');
        element.dataset.uckkTemporaryTabindex = '1';
    }

    element.focus({
        preventScroll: true,
    });
};

/**
 * Observe section/cmitem refreshes and reapply enhancements.
 *
 * Moodle can refresh course format fragments through the course format frontend.
 * This observer keeps UCKK visual markers stable after DOM replacement.
 *
 * @param {HTMLElement} root Root node.
 * @returns {MutationObserver|null}
 */
const observeCourseUpdates = (root) => {
    if (!window.MutationObserver) {
        return null;
    }

    let queued = false;

    const observer = new MutationObserver(() => {
        if (queued) {
            return;
        }

        queued = true;

        window.requestAnimationFrame(() => {
            enhance(root);
            queued = false;
        });
    });

    observer.observe(root, {
        childList: true,
        subtree: true,
    });

    return observer;
};

/**
 * Apply all static enhancements.
 *
 * @param {HTMLElement} root Root node.
 */
const enhance = (root) => {
    enhanceSections(root);
    enhanceCmItems(root);
    enhanceRegionLabels(root);
};

/**
 * Initialise the UCKK course format JavaScript.
 *
 * This function is the public API called from PHP:
 *
 * $PAGE->requires->js_call_amd('format_uckk/courseformat', 'init', [$selector, $options]);
 *
 * Keep parameters small. Use DOM data attributes for detailed state.
 *
 * @param {String|HTMLElement} rootSelector Root selector or node.
 * @param {Object} options Small initialisation options.
 * @returns {Object|null}
 */
export const init = (rootSelector = SELECTORS.root, options = {}) => {
    const root = getRoot(rootSelector);

    if (!claimRoot(root)) {
        return null;
    }

    const settings = Object.assign({
        observe: true,
    }, options || {});

    enhance(root);
    bindScrollActions(root);
    bindToggleActions(root);
    bindKeyboardActivation(root);

    const observer = settings.observe ? observeCourseUpdates(root) : null;

    return {
        root,
        observer,
        refresh: () => enhance(root),
        destroy: () => {
            if (observer) {
                observer.disconnect();
            }

            delete root.dataset.uckkCourseformatInitialised;
        },
    };
};

/**
 * Reapply UCKK visual enhancements to an already initialised course page.
 *
 * This can be useful after custom AJAX operations outside core_courseformat.
 *
 * @param {String|HTMLElement} rootSelector Root selector or node.
 */
export const refresh = (rootSelector = SELECTORS.root) => {
    const root = getRoot(rootSelector);

    if (!root) {
        return;
    }

    enhance(root);
};