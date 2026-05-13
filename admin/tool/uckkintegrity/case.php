/**
 * Case page helpers for tool_uckkintegrity.
 *
 * @module     tool_uckkintegrity/case
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    root: '[data-region="tool-uckkintegrity-case"]',
    confirmable: '[data-tool-uckkintegrity-confirm]',
    statusSelect: '[data-tool-uckkintegrity-status]',
    statusWarning: '[data-tool-uckkintegrity-status-warning]',
    noteTypeSelect: '[data-tool-uckkintegrity-note-type]',
    restrictedHint: '[data-tool-uckkintegrity-restricted-hint]',
};

const FINAL_OR_SENSITIVE_STATUSES = [
    'correction_required',
    'resolved',
    'dismissed',
    'escalated',
    'archived',
    'closed',
];

const RESTRICTED_NOTE_TYPES = [
    'evidence',
    'decision',
    'correction',
];

const hide = element => {
    if (element) {
        element.setAttribute('hidden', 'hidden');
    }
};

const show = element => {
    if (element) {
        element.removeAttribute('hidden');
    }
};

const enhanceConfirmations = root => {
    root.querySelectorAll(SELECTORS.confirmable).forEach(element => {
        if (element.dataset.toolUckkintegrityConfirmBound === '1') {
            return;
        }

        element.dataset.toolUckkintegrityConfirmBound = '1';

        const eventname = element.tagName.toLowerCase() === 'form' ? 'submit' : 'click';

        element.addEventListener(eventname, event => {
            const message = element.getAttribute('data-tool-uckkintegrity-confirm');

            if (message && !window.confirm(message)) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });
};

const enhanceStatusWarning = root => {
    const statusSelect = root.querySelector(SELECTORS.statusSelect);
    const warning = root.querySelector(SELECTORS.statusWarning);

    if (!statusSelect || !warning) {
        return;
    }

    const update = () => {
        if (FINAL_OR_SENSITIVE_STATUSES.includes(statusSelect.value)) {
            show(warning);
        } else {
            hide(warning);
        }
    };

    statusSelect.addEventListener('change', update);
    update();
};

const enhanceRestrictedHint = root => {
    const noteTypeSelect = root.querySelector(SELECTORS.noteTypeSelect);
    const hint = root.querySelector(SELECTORS.restrictedHint);

    if (!noteTypeSelect || !hint) {
        return;
    }

    const update = () => {
        if (RESTRICTED_NOTE_TYPES.includes(noteTypeSelect.value)) {
            show(hint);
        } else {
            hide(hint);
        }
    };

    noteTypeSelect.addEventListener('change', update);
    update();
};

export const init = rootSelector => {
    const root = rootSelector
        ? document.querySelector(rootSelector)
        : document.querySelector(SELECTORS.root) || document;

    if (!root) {
        return;
    }

    enhanceConfirmations(root);
    enhanceStatusWarning(root);
    enhanceRestrictedHint(root);
};