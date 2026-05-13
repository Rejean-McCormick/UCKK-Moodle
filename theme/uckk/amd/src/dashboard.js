// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Dashboard behaviours for the UCKK theme.
 *
 * Component: theme_uckk
 * File: theme/uckk/amd/src/dashboard.js
 *
 * This module is intentionally limited to visual and interaction behaviours:
 *
 * - dashboard region initialisation;
 * - collapsible visual panels;
 * - visual progress meters;
 * - local UI filtering;
 * - keyboard support for dashboard cards;
 * - accessibility state updates.
 *
 * It must not implement:
 *
 * - pathway calculations;
 * - grading;
 * - badge awarding;
 * - challenge workflow;
 * - assembly workflow;
 * - archive validation;
 * - integrity decisions;
 * - AI authority;
 * - permission checks.
 *
 * Those responsibilities belong to local_uckk, block_uckk_dashboard,
 * mod_uckkchallenge, mod_uckkassembly, mod_uckkarchive, tool_uckkintegrity,
 * report_uckk and aiprovider_uckk.
 *
 * @module     theme_uckk/dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Pending from 'core/pending';

/**
 * Main dashboard selector.
 *
 * @type {string}
 */
const SELECTOR_DASHBOARD = '[data-region="uckk-dashboard"]';

/**
 * Selectors used by the dashboard module.
 *
 * @type {Object}
 */
const SELECTORS = {
    collapsible: '[data-region="uckk-dashboard-collapsible"]',
    collapseToggle: '[data-action="toggle-uckk-dashboard-section"]',
    filter: '[data-region="uckk-dashboard-filter"]',
    filterTarget: '[data-filter-target]',
    quicklink: '[data-region="uckk-dashboard-quicklink"]',
    progressMeter: '[data-region="uckk-progress-meter"]',
    progressValue: '[data-region="uckk-progress-value"]',
    dismissibleNotice: '[data-region="uckk-dismissible-notice"]',
    dismissNoticeButton: '[data-action="dismiss-uckk-notice"]',
    integrityNotice: '[data-region="uckk-integrity-notice"]',
    focusCard: '[data-region="uckk-focus-card"]',
};

/**
 * CSS classes used by this module.
 *
 * @type {Object}
 */
const CLASSES = {
    enhanced: 'uckk-dashboard--js',
    collapsed: 'is-collapsed',
    hidden: 'd-none',
    filteredOut: 'uckk-dashboard-item--filtered-out',
    keyboardFocus: 'uckk-dashboard-card--keyboard-focus',
    dismissed: 'uckk-notice--dismissed',
};

/**
 * Local storage key prefix.
 *
 * @type {string}
 */
const STORAGE_PREFIX = 'theme_uckk_dashboard';

/**
 * Initialise the UCKK dashboard.
 *
 * This function can be called by the dashboard layout or by any Mustache
 * template that renders a dashboard-like UCKK region.
 *
 * Example:
 *
 *     {{#js}}
 *         require(['theme_uckk/dashboard'], function(Dashboard) {
 *             Dashboard.init();
 *         });
 *     {{/js}}
 *
 * @param {string} rootSelector Dashboard root selector.
 * @returns {void}
 */
export const init = (rootSelector = SELECTOR_DASHBOARD) => {
    const pending = new Pending('theme_uckk/dashboard:init');
    const dashboards = document.querySelectorAll(rootSelector);

    dashboards.forEach((dashboard) => {
        initialiseDashboard(dashboard);
    });

    pending.resolve();
};

/**
 * Initialise one dashboard root.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @returns {void}
 */
const initialiseDashboard = (dashboard) => {
    if (!dashboard || dashboard.dataset.uckkDashboardInitialised === 'true') {
        return;
    }

    dashboard.dataset.uckkDashboardInitialised = 'true';
    dashboard.classList.add(CLASSES.enhanced);

    initialiseCollapsibleSections(dashboard);
    initialiseFilters(dashboard);
    initialiseProgressMeters(dashboard);
    initialiseDismissibleNotices(dashboard);
    initialiseIntegrityNotices(dashboard);
    initialiseFocusCards(dashboard);
    initialiseQuicklinks(dashboard);
};

/**
 * Initialise collapsible dashboard sections.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @returns {void}
 */
const initialiseCollapsibleSections = (dashboard) => {
    const toggles = dashboard.querySelectorAll(SELECTORS.collapseToggle);

    toggles.forEach((toggle) => {
        const target = getTargetElement(toggle, dashboard);

        if (!target) {
            return;
        }

        const storageKey = getStorageKey(dashboard, 'collapse', getElementKey(target));
        const storedState = readStorage(storageKey);

        if (storedState === 'collapsed') {
            setCollapsedState(toggle, target, true);
        } else {
            setCollapsedState(toggle, target, target.classList.contains(CLASSES.collapsed));
        }

        toggle.addEventListener('click', (event) => {
            event.preventDefault();

            const isCollapsed = !target.classList.contains(CLASSES.collapsed);
            setCollapsedState(toggle, target, isCollapsed);
            writeStorage(storageKey, isCollapsed ? 'collapsed' : 'expanded');
        });
    });
};

/**
 * Set collapsed state for a dashboard section.
 *
 * @param {HTMLElement} toggle Toggle element.
 * @param {HTMLElement} target Target section.
 * @param {boolean} collapsed Whether the section is collapsed.
 * @returns {void}
 */
const setCollapsedState = (toggle, target, collapsed) => {
    target.classList.toggle(CLASSES.collapsed, collapsed);
    target.hidden = collapsed;

    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

    if (target.id) {
        toggle.setAttribute('aria-controls', target.id);
    }
};

/**
 * Initialise local dashboard filtering.
 *
 * This is a visual client-side filter only. It does not alter enrolment,
 * permissions, pathway progress, challenge status, archive state, or reports.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @returns {void}
 */
const initialiseFilters = (dashboard) => {
    const filters = dashboard.querySelectorAll(SELECTORS.filter);

    filters.forEach((filter) => {
        const targetSelector = filter.dataset.target || SELECTORS.filterTarget;

        filter.addEventListener('input', () => {
            applyFilter(dashboard, targetSelector, filter.value);
        });

        filter.addEventListener('search', () => {
            applyFilter(dashboard, targetSelector, filter.value);
        });
    });
};

/**
 * Apply a local text filter to dashboard items.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @param {string} targetSelector Selector for target items.
 * @param {string} rawQuery Search query.
 * @returns {void}
 */
const applyFilter = (dashboard, targetSelector, rawQuery) => {
    const query = normaliseText(rawQuery);
    const items = dashboard.querySelectorAll(targetSelector);

    items.forEach((item) => {
        const haystack = getFilterText(item);
        const isVisible = query === '' || haystack.includes(query);

        item.classList.toggle(CLASSES.filteredOut, !isVisible);
        item.classList.toggle(CLASSES.hidden, !isVisible);
        item.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
    });
};

/**
 * Get searchable text for a dashboard item.
 *
 * @param {HTMLElement} item Dashboard item.
 * @returns {string}
 */
const getFilterText = (item) => {
    const explicitText = item.dataset.filterText || '';

    if (explicitText !== '') {
        return normaliseText(explicitText);
    }

    return normaliseText(item.textContent || '');
};

/**
 * Initialise progress meters.
 *
 * The meter value must already be rendered by Moodle server-side output.
 * This function only reflects that value visually through CSS variables and
 * accessibility attributes.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @returns {void}
 */
const initialiseProgressMeters = (dashboard) => {
    const meters = dashboard.querySelectorAll(SELECTORS.progressMeter);

    meters.forEach((meter) => {
        const value = parsePercent(meter.dataset.value);
        setProgressMeterValue(meter, value);
    });
};

/**
 * Set a progress meter value.
 *
 * @param {HTMLElement} meter Progress meter element.
 * @param {number} value Progress percentage.
 * @returns {void}
 */
const setProgressMeterValue = (meter, value) => {
    const boundedValue = Math.max(0, Math.min(100, value));

    meter.style.setProperty('--uckk-progress-value', `${boundedValue}%`);
    meter.setAttribute('role', meter.getAttribute('role') || 'progressbar');
    meter.setAttribute('aria-valuemin', '0');
    meter.setAttribute('aria-valuemax', '100');
    meter.setAttribute('aria-valuenow', String(Math.round(boundedValue)));

    const visualValue = meter.querySelector(SELECTORS.progressValue);

    if (visualValue) {
        visualValue.textContent = `${Math.round(boundedValue)}%`;
    }
};

/**
 * Initialise dismissible dashboard notices.
 *
 * This is strictly a local UI preference. Dismissing a theme notice must not
 * close an integrity case, validate evidence, archive a decision, or change
 * Moodle data.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @returns {void}
 */
const initialiseDismissibleNotices = (dashboard) => {
    const notices = dashboard.querySelectorAll(SELECTORS.dismissibleNotice);

    notices.forEach((notice) => {
        const noticeKey = getElementKey(notice);
        const storageKey = getStorageKey(dashboard, 'notice', noticeKey);

        if (readStorage(storageKey) === 'dismissed') {
            hideNotice(notice);
            return;
        }

        const buttons = notice.querySelectorAll(SELECTORS.dismissNoticeButton);

        buttons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                hideNotice(notice);
                writeStorage(storageKey, 'dismissed');
            });
        });
    });
};

/**
 * Hide a dashboard notice.
 *
 * @param {HTMLElement} notice Notice element.
 * @returns {void}
 */
const hideNotice = (notice) => {
    notice.classList.add(CLASSES.dismissed, CLASSES.hidden);
    notice.setAttribute('aria-hidden', 'true');
};

/**
 * Initialise integrity notices.
 *
 * This function only improves accessibility and visual status handling. It does
 * not read or decide integrity status.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @returns {void}
 */
const initialiseIntegrityNotices = (dashboard) => {
    const notices = dashboard.querySelectorAll(SELECTORS.integrityNotice);

    notices.forEach((notice) => {
        const severity = normaliseText(notice.dataset.severity || 'info');

        if (!notice.hasAttribute('role')) {
            notice.setAttribute('role', severity === 'critical' ? 'alert' : 'note');
        }

        if (!notice.hasAttribute('aria-live')) {
            notice.setAttribute('aria-live', severity === 'critical' ? 'assertive' : 'polite');
        }
    });
};

/**
 * Initialise keyboard focus styling on dashboard cards.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @returns {void}
 */
const initialiseFocusCards = (dashboard) => {
    const cards = dashboard.querySelectorAll(SELECTORS.focusCard);

    cards.forEach((card) => {
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                triggerPrimaryCardAction(card, event);
            }
        });

        card.addEventListener('focusin', () => {
            card.classList.add(CLASSES.keyboardFocus);
        });

        card.addEventListener('focusout', () => {
            card.classList.remove(CLASSES.keyboardFocus);
        });
    });
};

/**
 * Trigger the primary link inside a focus card.
 *
 * @param {HTMLElement} card Card element.
 * @param {KeyboardEvent} event Keyboard event.
 * @returns {void}
 */
const triggerPrimaryCardAction = (card, event) => {
    const activeElement = document.activeElement;

    if (activeElement && activeElement !== card) {
        return;
    }

    const link = card.querySelector('a[href]');

    if (!link) {
        return;
    }

    event.preventDefault();
    link.click();
};

/**
 * Initialise quicklink behaviour.
 *
 * Quicklinks remain normal links. This function only records the last focused
 * quicklink locally so the user can return to the same visual area.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @returns {void}
 */
const initialiseQuicklinks = (dashboard) => {
    const links = dashboard.querySelectorAll(SELECTORS.quicklink);
    const storageKey = getStorageKey(dashboard, 'lastquicklink', 'focus');

    links.forEach((link) => {
        link.addEventListener('focus', () => {
            writeStorage(storageKey, getElementKey(link));
        });
    });

    const lastQuicklink = readStorage(storageKey);

    if (lastQuicklink === '') {
        return;
    }

    links.forEach((link) => {
        if (getElementKey(link) === lastQuicklink) {
            link.dataset.lastFocused = 'true';
        }
    });
};

/**
 * Get a target element from a trigger.
 *
 * Supported attributes:
 *
 * - data-target
 * - data-uckk-target
 * - href="#id"
 *
 * @param {HTMLElement} trigger Trigger element.
 * @param {HTMLElement} root Root search element.
 * @returns {HTMLElement|null}
 */
const getTargetElement = (trigger, root) => {
    const selector = trigger.dataset.target || trigger.dataset.uckkTarget || getHashSelector(trigger);

    if (!selector) {
        return null;
    }

    try {
        return root.querySelector(selector) || document.querySelector(selector);
    } catch (error) {
        return null;
    }
};

/**
 * Get a hash selector from an anchor-like element.
 *
 * @param {HTMLElement} element Element.
 * @returns {string}
 */
const getHashSelector = (element) => {
    const href = element.getAttribute('href') || '';

    if (!href.startsWith('#')) {
        return '';
    }

    return href;
};

/**
 * Get a stable key for an element.
 *
 * @param {HTMLElement} element Element.
 * @returns {string}
 */
const getElementKey = (element) => {
    return element.dataset.key || element.id || element.getAttribute('name') || 'default';
};

/**
 * Build a namespaced local storage key.
 *
 * @param {HTMLElement} dashboard Dashboard root element.
 * @param {string} namespace Namespace.
 * @param {string} key Key.
 * @returns {string}
 */
const getStorageKey = (dashboard, namespace, key) => {
    const dashboardKey = dashboard.dataset.key || dashboard.id || 'dashboard';

    return `${STORAGE_PREFIX}:${dashboardKey}:${namespace}:${key}`;
};

/**
 * Read from local storage safely.
 *
 * @param {string} key Storage key.
 * @returns {string}
 */
const readStorage = (key) => {
    try {
        return window.localStorage.getItem(key) || '';
    } catch (error) {
        return '';
    }
};

/**
 * Write to local storage safely.
 *
 * @param {string} key Storage key.
 * @param {string} value Storage value.
 * @returns {void}
 */
const writeStorage = (key, value) => {
    try {
        window.localStorage.setItem(key, value);
    } catch (error) {
        // Ignore local storage failures. UI state persistence is optional.
    }
};

/**
 * Parse a percentage value.
 *
 * @param {string|undefined} rawValue Raw value.
 * @returns {number}
 */
const parsePercent = (rawValue) => {
    const value = Number.parseFloat(rawValue || '0');

    if (Number.isNaN(value)) {
        return 0;
    }

    return value;
};

/**
 * Normalise text for local filtering and comparison.
 *
 * @param {string} value Raw text.
 * @returns {string}
 */
const normaliseText = (value) => {
    return String(value)
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
};

export default {
    init,
};