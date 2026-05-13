// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Navigation enhancements for the UCKK theme.
 *
 * This module is presentation-only.
 *
 * It must not contain:
 * - permission logic;
 * - workflow logic;
 * - grading logic;
 * - integrity decision logic;
 * - archive validation logic;
 * - assembly decision logic;
 * - challenge validation logic;
 * - AI authority logic.
 *
 * @module     theme_uckk/navigation
 * @copyright  2026 Momus et Bouche Cousue
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const Selectors = {
    body: 'body',
    region: '[data-region="theme_uckk/navigation"], .uckk-navigation',
    links: '.uckk-navigation-link, [data-region="theme_uckk/navigation"] a',
    activeLinks: '.uckk-navigation-link.active, [data-region="theme_uckk/navigation"] a.active',
    domainLinks: '[data-uckk-nav-domain]',
    toggleButtons: '[data-action="theme_uckk/toggle-navigation-section"]',
    boundaryToggleButtons: '[data-action="theme_uckk/toggle-boundary-note"]',
    scrollLinks: '[data-action="theme_uckk/scroll-to"]',
    toggleTargetAttribute: 'data-target',
    boundaryTargetAttribute: 'data-target',
    scrollTargetAttribute: 'data-target',
};

const Classes = {
    bodyEnhanced: 'theme-uckk-navigation-enhanced',
    active: 'active',
    current: 'is-current',
    hidden: 'd-none',
    expanded: 'is-expanded',
    collapsed: 'is-collapsed',
    domainPrefix: 'uckk-navigation-link--',
};

const Attributes = {
    enhanced: 'data-theme-uckk-navigation-enhanced',
    activeMatched: 'data-uckk-active-matched',
    domain: 'data-uckk-nav-domain',
    ariaCurrent: 'aria-current',
    ariaExpanded: 'aria-expanded',
    ariaHidden: 'aria-hidden',
    tabIndex: 'tabindex',
};

let initialised = false;

/**
 * Return the current browser URL.
 *
 * @returns {URL}
 */
const getCurrentUrl = () => new URL(window.location.href);

/**
 * Normalize a URL path for comparison.
 *
 * @param {string} path Path to normalize.
 * @returns {string}
 */
const normalizePath = path => {
    if (!path) {
        return '/';
    }

    const normalized = path.replace(/\/+$/, '');

    return normalized === '' ? '/' : normalized;
};

/**
 * Safely build a URL object from a link href.
 *
 * @param {HTMLAnchorElement} link Link element.
 * @returns {URL|null}
 */
const getLinkUrl = link => {
    const href = link.getAttribute('href');

    if (!href || href === '#') {
        return null;
    }

    try {
        return new URL(href, window.location.origin);
    } catch (error) {
        return null;
    }
};

/**
 * Determine whether a link points to the current page or section.
 *
 * UCKK navigation can contain broad entry points such as /course/ or /mod/uckkarchive/.
 * A link can opt into prefix matching with data-uckk-match="prefix".
 *
 * @param {HTMLAnchorElement} link Link element.
 * @returns {boolean}
 */
const isCurrentLink = link => {
    const currentUrl = getCurrentUrl();
    const linkUrl = getLinkUrl(link);

    if (!linkUrl || linkUrl.origin !== currentUrl.origin) {
        return false;
    }

    const currentPath = normalizePath(currentUrl.pathname);
    const linkPath = normalizePath(linkUrl.pathname);
    const matchMode = link.dataset.uckkMatch || 'auto';

    if (currentPath === linkPath && currentUrl.search === linkUrl.search) {
        return true;
    }

    if (currentPath === linkPath && !linkUrl.search) {
        return true;
    }

    if (matchMode === 'exact') {
        return false;
    }

    if (matchMode === 'prefix') {
        return currentPath.startsWith(linkPath + '/') || currentPath === linkPath;
    }

    if (linkPath === '/') {
        return currentPath === '/';
    }

    return currentPath.startsWith(linkPath + '/') && linkPath.length > 1;
};

/**
 * Clear active state from a navigation region.
 *
 * @param {HTMLElement} region Navigation region.
 */
const clearActiveState = region => {
    region.querySelectorAll(Selectors.activeLinks).forEach(link => {
        link.classList.remove(Classes.active);
        link.classList.remove(Classes.current);
        link.removeAttribute(Attributes.ariaCurrent);
        link.removeAttribute(Attributes.activeMatched);
    });
};

/**
 * Apply active state to links matching the current page.
 *
 * @param {HTMLElement} region Navigation region.
 */
const applyActiveState = region => {
    let matched = false;

    clearActiveState(region);

    region.querySelectorAll(Selectors.links).forEach(link => {
        if (!(link instanceof HTMLAnchorElement)) {
            return;
        }

        if (!isCurrentLink(link)) {
            return;
        }

        link.classList.add(Classes.active);
        link.classList.add(Classes.current);
        link.setAttribute(Attributes.ariaCurrent, 'page');
        link.setAttribute(Attributes.activeMatched, 'true');
        matched = true;
    });

    if (matched) {
        region.setAttribute(Attributes.activeMatched, 'true');
    } else {
        region.removeAttribute(Attributes.activeMatched);
    }
};

/**
 * Apply domain classes for UCKK navigation entries.
 *
 * Supported values:
 * - campus
 * - course
 * - challenge
 * - assembly
 * - archive
 * - integrity
 * - canon
 * - pathway
 *
 * @param {HTMLElement} root Root element.
 */
const applyDomainClasses = root => {
    root.querySelectorAll(Selectors.domainLinks).forEach(link => {
        const domain = link.getAttribute(Attributes.domain);

        if (!domain) {
            return;
        }

        const normalized = domain.toLowerCase().replace(/[^a-z0-9_-]/g, '');

        if (!normalized) {
            return;
        }

        link.classList.add(`${Classes.domainPrefix}${normalized}`);
    });
};

/**
 * Set an expandable region state.
 *
 * @param {HTMLElement} button Toggle button.
 * @param {HTMLElement} target Target element.
 * @param {boolean} expanded Whether the target should be expanded.
 */
const setExpanded = (button, target, expanded) => {
    button.setAttribute(Attributes.ariaExpanded, expanded ? 'true' : 'false');
    target.setAttribute(Attributes.ariaHidden, expanded ? 'false' : 'true');
    target.classList.toggle(Classes.hidden, !expanded);
    target.classList.toggle(Classes.expanded, expanded);
    target.classList.toggle(Classes.collapsed, !expanded);

    if (expanded) {
        target.removeAttribute(Attributes.tabIndex);
    } else {
        target.setAttribute(Attributes.tabIndex, '-1');
    }
};

/**
 * Resolve a toggle target from a button.
 *
 * @param {HTMLElement} button Toggle button.
 * @param {string} attributeName Attribute containing the target selector.
 * @returns {HTMLElement|null}
 */
const getTargetFromButton = (button, attributeName) => {
    const selector = button.getAttribute(attributeName);

    if (!selector) {
        return null;
    }

    try {
        return document.querySelector(selector);
    } catch (error) {
        return null;
    }
};

/**
 * Register expandable navigation section controls.
 *
 * @param {HTMLElement} root Root element.
 */
const registerSectionToggles = root => {
    root.querySelectorAll(Selectors.toggleButtons).forEach(button => {
        const target = getTargetFromButton(button, Selectors.toggleTargetAttribute);

        if (!target) {
            return;
        }

        const expanded = button.getAttribute(Attributes.ariaExpanded) === 'true';

        setExpanded(button, target, expanded);

        button.addEventListener('click', event => {
            event.preventDefault();

            const isExpanded = button.getAttribute(Attributes.ariaExpanded) === 'true';

            setExpanded(button, target, !isExpanded);
        });
    });
};

/**
 * Register boundary note toggles.
 *
 * Boundary notes are explanatory UI only. They do not change access, status,
 * integrity state, archive state, or workflow state.
 *
 * @param {HTMLElement} root Root element.
 */
const registerBoundaryToggles = root => {
    root.querySelectorAll(Selectors.boundaryToggleButtons).forEach(button => {
        const target = getTargetFromButton(button, Selectors.boundaryTargetAttribute);

        if (!target) {
            return;
        }

        button.addEventListener('click', event => {
            event.preventDefault();

            const isExpanded = button.getAttribute(Attributes.ariaExpanded) === 'true';

            setExpanded(button, target, !isExpanded);
        });
    });
};

/**
 * Scroll to an in-page navigation target.
 *
 * @param {HTMLElement} target Target element.
 */
const scrollToTarget = target => {
    if (!target) {
        return;
    }

    target.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'start',
    });

    if (!target.hasAttribute(Attributes.tabIndex)) {
        target.setAttribute(Attributes.tabIndex, '-1');
    }

    target.focus({
        preventScroll: true,
    });
};

/**
 * Register local scroll controls.
 *
 * @param {HTMLElement} root Root element.
 */
const registerScrollLinks = root => {
    root.querySelectorAll(Selectors.scrollLinks).forEach(button => {
        button.addEventListener('click', event => {
            const target = getTargetFromButton(button, Selectors.scrollTargetAttribute);

            if (!target) {
                return;
            }

            event.preventDefault();
            scrollToTarget(target);
        });
    });
};

/**
 * Mark navigation as enhanced.
 *
 * @param {HTMLElement} root Root element.
 */
const markEnhanced = root => {
    root.setAttribute(Attributes.enhanced, 'true');
    root.classList.add(Classes.bodyEnhanced);
};

/**
 * Enhance a single navigation region.
 *
 * @param {HTMLElement} region Navigation region.
 */
const enhanceRegion = region => {
    if (region.getAttribute(Attributes.enhanced) === 'true') {
        return;
    }

    applyActiveState(region);
    applyDomainClasses(region);
    registerSectionToggles(region);
    registerBoundaryToggles(region);
    registerScrollLinks(region);
    markEnhanced(region);
};

/**
 * Enhance all UCKK navigation regions on the page.
 */
const enhanceAllRegions = () => {
    document.querySelectorAll(Selectors.region).forEach(region => {
        enhanceRegion(region);
    });
};

/**
 * Register a history/navigation refresh hook.
 *
 * This keeps active state coherent when Moodle or another plugin updates
 * the URL with pushState or replaceState.
 */
const registerHistoryRefresh = () => {
    window.addEventListener('popstate', enhanceAllRegions);
    window.addEventListener('hashchange', enhanceAllRegions);
};

/**
 * Register global click handling for late-loaded UCKK navigation regions.
 *
 * This is intentionally narrow and only enhances UCKK navigation containers.
 */
const registerGlobalDelegation = () => {
    document.addEventListener('click', event => {
        const region = event.target.closest(Selectors.region);

        if (!region) {
            return;
        }

        enhanceRegion(region);
    });
};

/**
 * Initialise UCKK navigation enhancements.
 *
 * This function is safe to call multiple times.
 */
export const init = () => {
    if (initialised) {
        enhanceAllRegions();
        return;
    }

    initialised = true;

    const body = document.querySelector(Selectors.body);

    if (body) {
        markEnhanced(body);
    }

    enhanceAllRegions();
    registerHistoryRefresh();
    registerGlobalDelegation();
};