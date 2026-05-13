/**
 * Player profile interactions for local_uckk.
 *
 * This AMD module is deliberately UI-only:
 * - it does not decide permissions;
 * - it does not validate UCKK symbolic roles;
 * - it does not award badges or competencies;
 * - it delegates all authoritative checks to local_uckk external services.
 *
 * Expected template hooks:
 *
 * data-region="uckk-player-profile"
 * data-region="uckk-profile-form"
 * data-region="uckk-profile-status"
 * data-action="uckk-profile-edit"
 * data-action="uckk-profile-cancel"
 * data-action="uckk-profile-save"
 * data-field="displayname"
 * data-field="bio"
 * data-field="symbolicrole"
 *
 * Optional root dataset values:
 *
 * data-userid="123"
 * data-contextid="456"
 *
 * @module     local_uckk/profile
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

const SELECTORS = {
    root: '[data-region="uckk-player-profile"]',
    form: '[data-region="uckk-profile-form"]',
    status: '[data-region="uckk-profile-status"]',
    editableField: '[data-field]',
    editButton: '[data-action="uckk-profile-edit"]',
    cancelButton: '[data-action="uckk-profile-cancel"]',
    saveButton: '[data-action="uckk-profile-save"]',
};

const CLASSES = {
    editing: 'uckk-profile--editing',
    saving: 'uckk-profile--saving',
};

const PROFILE_SERVICE = 'local_uckk_update_player_profile';

/**
 * Get the containing profile root for an event target.
 *
 * @param {EventTarget} target
 * @returns {HTMLElement|null}
 */
const getRootFromTarget = (target) => {
    if (!target || !target.closest) {
        return null;
    }

    return target.closest(SELECTORS.root);
};

/**
 * Get the profile form within a root element.
 *
 * @param {HTMLElement} root
 * @returns {HTMLElement|null}
 */
const getForm = (root) => root.querySelector(SELECTORS.form);

/**
 * Get the status region within a root element.
 *
 * @param {HTMLElement} root
 * @returns {HTMLElement|null}
 */
const getStatusRegion = (root) => root.querySelector(SELECTORS.status);

/**
 * Set a short status message.
 *
 * The message text must come from lang strings, not hard-coded visible UI text.
 *
 * @param {HTMLElement} root
 * @param {String} message
 * @param {String} state
 */
const setStatus = (root, message, state = 'info') => {
    const status = getStatusRegion(root);

    if (!status) {
        return;
    }

    status.textContent = message;
    status.dataset.state = state;
};

/**
 * Read initial field values into data-original-value.
 *
 * @param {HTMLElement} root
 */
const rememberOriginalValues = (root) => {
    root.querySelectorAll(SELECTORS.editableField).forEach((field) => {
        if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
            field.dataset.originalValue = field.value;
        }
    });
};

/**
 * Restore fields from data-original-value.
 *
 * @param {HTMLElement} root
 */
const restoreOriginalValues = (root) => {
    root.querySelectorAll(SELECTORS.editableField).forEach((field) => {
        if (
            field instanceof HTMLInputElement ||
            field instanceof HTMLTextAreaElement ||
            field instanceof HTMLSelectElement
        ) {
            field.value = field.dataset.originalValue || '';
        }
    });
};

/**
 * Toggle edit mode.
 *
 * @param {HTMLElement} root
 * @param {Boolean} editing
 */
const setEditing = (root, editing) => {
    root.classList.toggle(CLASSES.editing, editing);

    root.querySelectorAll(SELECTORS.editableField).forEach((field) => {
        if (
            field instanceof HTMLInputElement ||
            field instanceof HTMLTextAreaElement ||
            field instanceof HTMLSelectElement
        ) {
            field.disabled = !editing;
        }
    });

    const editButton = root.querySelector(SELECTORS.editButton);
    const cancelButton = root.querySelector(SELECTORS.cancelButton);
    const saveButton = root.querySelector(SELECTORS.saveButton);

    if (editButton) {
        editButton.hidden = editing;
    }

    if (cancelButton) {
        cancelButton.hidden = !editing;
    }

    if (saveButton) {
        saveButton.hidden = !editing;
    }
};

/**
 * Toggle saving state.
 *
 * @param {HTMLElement} root
 * @param {Boolean} saving
 */
const setSaving = (root, saving) => {
    root.classList.toggle(CLASSES.saving, saving);

    root.querySelectorAll('button, input, textarea, select').forEach((element) => {
        if (element.dataset.field) {
            element.disabled = saving || !root.classList.contains(CLASSES.editing);
            return;
        }

        element.disabled = saving;
    });
};

/**
 * Collect editable profile fields.
 *
 * Returned format is intentionally generic so the PHP external class can
 * whitelist supported fields server-side.
 *
 * @param {HTMLElement} root
 * @returns {Array}
 */
const collectFields = (root) => {
    const fields = [];

    root.querySelectorAll(SELECTORS.editableField).forEach((field) => {
        if (
            !(field instanceof HTMLInputElement) &&
            !(field instanceof HTMLTextAreaElement) &&
            !(field instanceof HTMLSelectElement)
        ) {
            return;
        }

        const name = field.dataset.field;

        if (!name) {
            return;
        }

        fields.push({
            name,
            value: field.value,
        });
    });

    return fields;
};

/**
 * Submit profile changes to Moodle.
 *
 * @param {HTMLElement} root
 * @returns {Promise<Object>}
 */
const submitProfile = async(root) => {
    const request = Ajax.call([{
        methodname: PROFILE_SERVICE,
        args: {
            userid: Number(root.dataset.userid || 0),
            contextid: Number(root.dataset.contextid || 0),
            fields: collectFields(root),
        },
    }])[0];

    return request;
};

/**
 * Handle edit action.
 *
 * @param {Event} event
 */
const handleEdit = async(event) => {
    const root = getRootFromTarget(event.target);

    if (!root) {
        return;
    }

    event.preventDefault();

    rememberOriginalValues(root);
    setEditing(root, true);

    setStatus(
        root,
        await getString('profileediting', 'local_uckk'),
        'info'
    );
};

/**
 * Handle cancel action.
 *
 * @param {Event} event
 */
const handleCancel = async(event) => {
    const root = getRootFromTarget(event.target);

    if (!root) {
        return;
    }

    event.preventDefault();

    restoreOriginalValues(root);
    setEditing(root, false);

    setStatus(
        root,
        await getString('profileeditcancelled', 'local_uckk'),
        'info'
    );
};

/**
 * Handle save action.
 *
 * @param {Event} event
 */
const handleSave = async(event) => {
    const root = getRootFromTarget(event.target);

    if (!root) {
        return;
    }

    event.preventDefault();

    setSaving(root, true);

    try {
        setStatus(
            root,
            await getString('profilesaving', 'local_uckk'),
            'info'
        );

        const response = await submitProfile(root);

        rememberOriginalValues(root);
        setEditing(root, false);

        const message = response && response.message
            ? response.message
            : await getString('profilesaved', 'local_uckk');

        setStatus(root, message, 'success');
    } catch (error) {
        setStatus(
            root,
            await getString('profileerror', 'local_uckk'),
            'error'
        );

        Notification.exception(error);
    } finally {
        setSaving(root, false);
    }
};

/**
 * Bind one profile root.
 *
 * @param {HTMLElement} root
 */
const registerRoot = (root) => {
    if (!root || root.dataset.uckkProfileInitialised === '1') {
        return;
    }

    root.dataset.uckkProfileInitialised = '1';

    rememberOriginalValues(root);
    setEditing(root, false);

    const form = getForm(root);

    if (form) {
        form.addEventListener('submit', handleSave);
    }

    const editButton = root.querySelector(SELECTORS.editButton);
    const cancelButton = root.querySelector(SELECTORS.cancelButton);
    const saveButton = root.querySelector(SELECTORS.saveButton);

    if (editButton) {
        editButton.addEventListener('click', handleEdit);
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', handleCancel);
    }

    if (saveButton) {
        saveButton.addEventListener('click', handleSave);
    }
};

/**
 * Initialise UCKK profile UI.
 *
 * @param {String} rootSelector
 */
export const init = (rootSelector = SELECTORS.root) => {
    document.querySelectorAll(rootSelector).forEach(registerRoot);
};