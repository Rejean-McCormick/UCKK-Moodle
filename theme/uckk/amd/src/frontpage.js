/**
 * Front page enhancements for theme_uckk.
 *
 * This module is intentionally presentational. It enhances the UCKK front page
 * with progressive navigation, reveal effects, keyboard activation, and local
 * UI state. It must not implement permissions, enrolment, grading, integrity,
 * archive, challenge, assembly, or AI decision logic.
 *
 * @module     theme_uckk/frontpage
 * @copyright  2026 Réjean McCormick
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    root: '[data-region="uckk-frontpage"]',
    hero: '[data-region="uckk-frontpage-hero"]',
    navigation: '[data-region="uckk-frontpage-navigation"]',
    card: '[data-region="uckk-frontpage-card"]',
    cardLink: '[data-region="uckk-frontpage-card-link"]',
    reveal: '[data-uckk-reveal]',
    scrollAction: '[data-action="uckk-scroll-to"]',
    boundaryToggle: '[data-action="toggle-uckk-boundary"]',
    boundaryPanel: '[data-region="uckk-boundary-panel"]',
    theatreToggle: '[data-action="toggle-uckk-theatre-notice"]',
    theatrePanel: '[data-region="uckk-theatre-notice"]',
    liveRegion: '[data-region="uckk-frontpage-live"]',
};

const CLASSES = {
    ready: 'uckk-frontpage--ready',
    reducedMotion: 'uckk-frontpage--reduced-motion',
    revealed: 'uckk-revealed',
    cardInteractive: 'uckk-frontpage-card--interactive',
    boundaryOpen: 'uckk-boundary--open',
    theatreOpen: 'uckk-theatre-notice--open',
    heroCompact: 'uckk-frontpage-hero--compact',
};

const STORAGE_KEYS = {
    boundaryOpen: 'theme_uckk_frontpage_boundary_open',
    theatreOpen: 'theme_uckk_frontpage_theatre_open',
};

const KEYBOARD_ACTIVATION_KEYS = ['Enter', ' '];

/**
 * Get a DOM element from a selector or element.
 *
 * @param {String|Element|null} root The root selector or DOM element.
 * @returns {Element|null}
 */
const getRootElement = (root) => {
    if (root instanceof Element) {
        return root;
    }

    if (typeof root === 'string' && root !== '') {
        return document.querySelector(root);
    }

    return document.querySelector(SELECTORS.root);
};

/**
 * Check whether the browser/user has requested reduced motion.
 *
 * @returns {Boolean}
 */
const prefersReducedMotion = () => {
    if (!window.matchMedia) {
        return false;
    }

    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
};

/**
 * Return all matching elements inside a root.
 *
 * @param {Element} root The root element.
 * @param {String} selector The CSS selector.
 * @returns {Element[]}
 */
const getElements = (root, selector) => Array.from(root.querySelectorAll(selector));

/**
 * Safely read a local storage value.
 *
 * @param {String} key The key to read.
 * @returns {String|null}
 */
const readStorage = (key) => {
    try {
        return window.localStorage.getItem(key);
    } catch (error) {
        return null;
    }
};

/**
 * Safely write a local storage value.
 *
 * @param {String} key The key to write.
 * @param {String} value The value to write.
 */
const writeStorage = (key, value) => {
    try {
        window.localStorage.setItem(key, value);
    } catch (error) {
        // Storage can be disabled. This UI enhancement must still work.
    }
};

/**
 * Announce a short message to assistive technologies when a live region exists.
 *
 * @param {Element} root The front page root.
 * @param {String} message The message to announce.
 */
const announce = (root, message) => {
    const liveRegion = root.querySelector(SELECTORS.liveRegion);

    if (!liveRegion || message === '') {
        return;
    }

    liveRegion.textContent = '';
    window.setTimeout(() => {
        liveRegion.textContent = message;
    }, 50);
};

/**
 * Smoothly move to a target when allowed.
 *
 * @param {Element} target The target element.
 */
const scrollToTarget = (target) => {
    if (prefersReducedMotion()) {
        target.scrollIntoView();
        return;
    }

    target.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

/**
 * Focus a target after scrolling.
 *
 * @param {Element} target The target element.
 */
const focusTarget = (target) => {
    const hadTabIndex = target.hasAttribute('tabindex');

    if (!hadTabIndex) {
        target.setAttribute('tabindex', '-1');
    }

    target.focus({
        preventScroll: true,
    });

    if (!hadTabIndex) {
        target.addEventListener('blur', () => {
            target.removeAttribute('tabindex');
        }, {once: true});
    }
};

/**
 * Enhance front page links that scroll to internal sections.
 *
 * Expected markup:
 * <button data-action="uckk-scroll-to" data-target="#uckk-challenges">...</button>
 *
 * @param {Element} root The front page root.
 */
const registerScrollActions = (root) => {
    getElements(root, SELECTORS.scrollAction).forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            const targetSelector = trigger.getAttribute('data-target');

            if (!targetSelector) {
                return;
            }

            const target = document.querySelector(targetSelector);

            if (!target) {
                return;
            }

            event.preventDefault();
            scrollToTarget(target);
            focusTarget(target);

            const announcement = trigger.getAttribute('data-announcement') || '';
            announce(root, announcement);
        });
    });
};

/**
 * Make a card act as a keyboard-accessible proxy for its primary link.
 *
 * Expected markup:
 * <article data-region="uckk-frontpage-card" tabindex="0">
 *     <a data-region="uckk-frontpage-card-link" href="...">...</a>
 * </article>
 *
 * @param {Element} root The front page root.
 */
const registerInteractiveCards = (root) => {
    getElements(root, SELECTORS.card).forEach((card) => {
        const primaryLink = card.querySelector(SELECTORS.cardLink);

        if (!primaryLink) {
            return;
        }

        card.classList.add(CLASSES.cardInteractive);

        if (!card.hasAttribute('tabindex')) {
            card.setAttribute('tabindex', '0');
        }

        if (!card.hasAttribute('role')) {
            card.setAttribute('role', 'link');
        }

        if (!card.hasAttribute('aria-label')) {
            card.setAttribute('aria-label', primaryLink.textContent.trim());
        }

        card.addEventListener('click', (event) => {
            const interactiveChild = event.target.closest('a, button, input, select, textarea');

            if (interactiveChild) {
                return;
            }

            primaryLink.click();
        });

        card.addEventListener('keydown', (event) => {
            if (!KEYBOARD_ACTIVATION_KEYS.includes(event.key)) {
                return;
            }

            event.preventDefault();
            primaryLink.click();
        });
    });
};

/**
 * Toggle a panel and persist the state.
 *
 * @param {Object} config The toggle configuration.
 * @param {Element} config.root The root element.
 * @param {String} config.toggleSelector The toggle selector.
 * @param {String} config.panelSelector The panel selector.
 * @param {String} config.openClass The root class when open.
 * @param {String} config.storageKey The local storage key.
 * @param {String} config.openAnnouncement The message announced on open.
 * @param {String} config.closeAnnouncement The message announced on close.
 */
const registerPersistentToggle = ({
    root,
    toggleSelector,
    panelSelector,
    openClass,
    storageKey,
    openAnnouncement,
    closeAnnouncement,
}) => {
    const toggle = root.querySelector(toggleSelector);
    const panel = root.querySelector(panelSelector);

    if (!toggle || !panel) {
        return;
    }

    const setOpen = (isOpen, shouldPersist = true) => {
        root.classList.toggle(openClass, isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        panel.hidden = !isOpen;

        if (shouldPersist) {
            writeStorage(storageKey, isOpen ? '1' : '0');
        }

        announce(root, isOpen ? openAnnouncement : closeAnnouncement);
    };

    const storedState = readStorage(storageKey);

    if (storedState !== null) {
        setOpen(storedState === '1', false);
    } else {
        const initiallyExpanded = toggle.getAttribute('aria-expanded') === 'true';
        setOpen(initiallyExpanded, false);
    }

    toggle.addEventListener('click', () => {
        const isCurrentlyOpen = toggle.getAttribute('aria-expanded') === 'true';
        setOpen(!isCurrentlyOpen);
    });
};

/**
 * Register the canonical boundary toggle.
 *
 * @param {Element} root The front page root.
 */
const registerBoundaryToggle = (root) => {
    registerPersistentToggle({
        root,
        toggleSelector: SELECTORS.boundaryToggle,
        panelSelector: SELECTORS.boundaryPanel,
        openClass: CLASSES.boundaryOpen,
        storageKey: STORAGE_KEYS.boundaryOpen,
        openAnnouncement: root.getAttribute('data-boundary-open-message') || '',
        closeAnnouncement: root.getAttribute('data-boundary-close-message') || '',
    });
};

/**
 * Register the theatre notice toggle.
 *
 * @param {Element} root The front page root.
 */
const registerTheatreToggle = (root) => {
    registerPersistentToggle({
        root,
        toggleSelector: SELECTORS.theatreToggle,
        panelSelector: SELECTORS.theatrePanel,
        openClass: CLASSES.theatreOpen,
        storageKey: STORAGE_KEYS.theatreOpen,
        openAnnouncement: root.getAttribute('data-theatre-open-message') || '',
        closeAnnouncement: root.getAttribute('data-theatre-close-message') || '',
    });
};

/**
 * Reveal elements as they enter the viewport.
 *
 * Expected markup:
 * <section data-uckk-reveal>...</section>
 *
 * @param {Element} root The front page root.
 */
const registerRevealEffects = (root) => {
    const revealElements = getElements(root, SELECTORS.reveal);

    if (revealElements.length === 0) {
        return;
    }

    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
        revealElements.forEach((element) => element.classList.add(CLASSES.revealed));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add(CLASSES.revealed);
            observer.unobserve(entry.target);
        });
    }, {
        root: null,
        rootMargin: '0px 0px -8% 0px',
        threshold: 0.12,
    });

    revealElements.forEach((element) => observer.observe(element));
};

/**
 * Compact the hero after initial scroll.
 *
 * This is a visual aid only. It does not modify page content or permissions.
 *
 * @param {Element} root The front page root.
 */
const registerHeroScrollState = (root) => {
    const hero = root.querySelector(SELECTORS.hero);

    if (!hero) {
        return;
    }

    let ticking = false;

    const updateHeroState = () => {
        hero.classList.toggle(CLASSES.heroCompact, window.scrollY > 80);
        ticking = false;
    };

    const requestUpdate = () => {
        if (ticking) {
            return;
        }

        ticking = true;
        window.requestAnimationFrame(updateHeroState);
    };

    updateHeroState();
    window.addEventListener('scroll', requestUpdate, {passive: true});
};

/**
 * Mark the current front page navigation link as active.
 *
 * @param {Element} root The front page root.
 */
const registerFrontpageNavigationState = (root) => {
    const navigation = root.querySelector(SELECTORS.navigation);

    if (!navigation) {
        return;
    }

    const links = getElements(navigation, 'a[href]');
    const currentPath = window.location.pathname;

    links.forEach((link) => {
        let linkPath = '';

        try {
            linkPath = new URL(link.href, window.location.origin).pathname;
        } catch (error) {
            return;
        }

        const isActive = linkPath === currentPath;
        link.classList.toggle('active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
};

/**
 * Prepare reduced-motion class for CSS hooks.
 *
 * @param {Element} root The front page root.
 */
const registerMotionPreference = (root) => {
    const setMotionClass = () => {
        root.classList.toggle(CLASSES.reducedMotion, prefersReducedMotion());
    };

    setMotionClass();

    if (!window.matchMedia) {
        return;
    }

    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (typeof motionQuery.addEventListener === 'function') {
        motionQuery.addEventListener('change', setMotionClass);
        return;
    }

    if (typeof motionQuery.addListener === 'function') {
        motionQuery.addListener(setMotionClass);
    }
};

/**
 * Initialise the UCKK front page module.
 *
 * This function is expected to be called from PHP:
 * $PAGE->requires->js_call_amd('theme_uckk/frontpage', 'init');
 *
 * @param {String|Element|null} root The optional root selector or element.
 */
export const init = (root = SELECTORS.root) => {
    const rootElement = getRootElement(root);

    if (!rootElement) {
        return;
    }

    if (rootElement.dataset.uckkFrontpageInitialised === 'true') {
        return;
    }

    rootElement.dataset.uckkFrontpageInitialised = 'true';

    registerMotionPreference(rootElement);
    registerScrollActions(rootElement);
    registerInteractiveCards(rootElement);
    registerBoundaryToggle(rootElement);
    registerTheatreToggle(rootElement);
    registerRevealEffects(rootElement);
    registerHeroScrollState(rootElement);
    registerFrontpageNavigationState(rootElement);

    rootElement.classList.add(CLASSES.ready);
};