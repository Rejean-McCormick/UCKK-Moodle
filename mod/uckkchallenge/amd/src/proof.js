/**
 * Proof submission UI helpers for mod_uckkchallenge.
 *
 * This module is intentionally UI-only:
 * - shows/hides proof-specific fields;
 * - prompts for AI disclosure when AI assistance is selected;
 * - warns when provenance or criteria fields are empty;
 * - does not submit, grade, validate, award, archive, or decide integrity.
 *
 * @module     mod_uckkchallenge/proof
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

const COMPONENT = 'uckkchallenge';

const SELECTORS = {
    root: '[data-region="uckkchallenge-proof-form"]',
    status: '[data-region="uckkchallenge-proof-status"]',

    prooftype: 'select[name="prooftype"]',
    submissionurl: 'input[name="submissionurl"]',
    relationtocriteria: 'textarea[name="relationtocriteria"]',
    provenancestatement: 'textarea[name="provenancestatement"]',
    aiassisted: 'input[name="aiassisted"]',
    ailog: 'textarea[name="ailog"]',
    uncertaintynotes: 'textarea[name="uncertaintynotes"]',

    submissionTextEditor: '[name="submissiontext_editor[text]"]',
    submitButton: 'input[name="submitbutton"], button[name="submitbutton"]',
    saveDraftButton: 'input[name="savedraft"], button[name="savedraft"]',

    proofTypeRegion: '[data-proof-type]',
    urlRegion: '[data-region="uckkchallenge-proof-url"]',
    fileRegion: '[data-region="uckkchallenge-proof-files"]',
    textRegion: '[data-region="uckkchallenge-proof-text"]',
    aiRegion: '[data-region="uckkchallenge-proof-ai"]',
    aiLogRegion: '[data-region="uckkchallenge-proof-ai-log"]',
    provenanceRegion: '[data-region="uckkchallenge-proof-provenance"]',
};

const CLASSES = {
    hidden: 'd-none',
    warning: 'has-warning',
    error: 'has-error',
    active: 'is-active',
    inactive: 'is-inactive',
};

const ATTRIBUTES = {
    initialised: 'data-uckkchallenge-proof-initialised',
};

/**
 * Get a field from a root node.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} selector CSS selector.
 * @returns {HTMLElement|null}
 */
const getField = (root, selector) => root.querySelector(selector);

/**
 * Get the closest Moodle form item wrapper for a field.
 *
 * @param {HTMLElement|null} field Field element.
 * @returns {HTMLElement|null}
 */
const getFieldWrapper = field => {
    if (!field) {
        return null;
    }

    return field.closest('[data-fieldtype], .fitem, .form-group, .mb-3');
};

/**
 * Set field wrapper visibility.
 *
 * @param {HTMLElement|null} field Field element.
 * @param {Boolean} visible Whether the wrapper should be visible.
 */
const setFieldVisible = (field, visible) => {
    const wrapper = getFieldWrapper(field);

    if (!wrapper) {
        return;
    }

    wrapper.classList.toggle(CLASSES.hidden, !visible);
    wrapper.classList.toggle(CLASSES.active, visible);
    wrapper.classList.toggle(CLASSES.inactive, !visible);
    wrapper.setAttribute('aria-hidden', visible ? 'false' : 'true');
};

/**
 * Set explicit region visibility.
 *
 * @param {HTMLElement|null} region Region element.
 * @param {Boolean} visible Whether region should be visible.
 */
const setRegionVisible = (region, visible) => {
    if (!region) {
        return;
    }

    region.classList.toggle(CLASSES.hidden, !visible);
    region.classList.toggle(CLASSES.active, visible);
    region.classList.toggle(CLASSES.inactive, !visible);
    region.setAttribute('aria-hidden', visible ? 'false' : 'true');
};

/**
 * Get trimmed field value.
 *
 * @param {HTMLElement|null} field Field element.
 * @returns {String}
 */
const getValue = field => field && typeof field.value === 'string' ? field.value.trim() : '';

/**
 * Set accessible status text.
 *
 * @param {HTMLElement} root Root element.
 * @param {String} message Message.
 */
const setStatus = (root, message) => {
    const status = getField(root, SELECTORS.status);

    if (status) {
        status.textContent = message;
    }
};

/**
 * Mark one field with a warning state.
 *
 * @param {HTMLElement|null} field Field element.
 * @param {Boolean} warn Whether to show warning.
 */
const setWarning = (field, warn) => {
    const wrapper = getFieldWrapper(field);

    if (wrapper) {
        wrapper.classList.toggle(CLASSES.warning, warn);
    }
};

/**
 * Mark one field with an error state.
 *
 * @param {HTMLElement|null} field Field element.
 * @param {Boolean} error Whether to show error.
 */
const setError = (field, error) => {
    const wrapper = getFieldWrapper(field);

    if (wrapper) {
        wrapper.classList.toggle(CLASSES.error, error);
    }
};

/**
 * Toggle proof-type-specific UI.
 *
 * @param {HTMLElement} root Root element.
 */
const updateProofTypeUi = root => {
    const proofType = getField(root, SELECTORS.prooftype);
    const selected = proofType ? proofType.value : 'text';

    const submissionUrl = getField(root, SELECTORS.submissionurl);
    const submissionText = getField(root, SELECTORS.submissionTextEditor);

    const urlRegion = getField(root, SELECTORS.urlRegion);
    const fileRegion = getField(root, SELECTORS.fileRegion);
    const textRegion = getField(root, SELECTORS.textRegion);

    const showUrl = selected === 'url' || selected === 'video' || selected === 'dataset';
    const showFile = selected === 'file' || selected === 'image' || selected === 'video' || selected === 'dataset';
    const showText = selected === 'text'
        || selected === 'testimony'
        || selected === 'observation'
        || selected === 'ai_log'
        || selected === 'decision_record';

    setRegionVisible(urlRegion, showUrl);
    setRegionVisible(fileRegion, showFile);
    setRegionVisible(textRegion, showText);

    // Fallback for forms that do not wrap fields in explicit data regions.
    setFieldVisible(submissionUrl, showUrl || selected === 'url');
    setFieldVisible(submissionText, true);

    if (submissionUrl) {
        submissionUrl.required = selected === 'url';
    }

    if (submissionText) {
        submissionText.required = showText;
    }
};

/**
 * Toggle AI collaboration log field.
 *
 * @param {HTMLElement} root Root element.
 */
const updateAiUi = root => {
    const aiAssisted = getField(root, SELECTORS.aiassisted);
    const aiLog = getField(root, SELECTORS.ailog);
    const aiRegion = getField(root, SELECTORS.aiRegion);
    const aiLogRegion = getField(root, SELECTORS.aiLogRegion);

    const enabled = Boolean(aiAssisted && aiAssisted.checked);

    setRegionVisible(aiRegion, true);
    setRegionVisible(aiLogRegion, enabled);
    setFieldVisible(aiLog, enabled);

    if (aiLog) {
        aiLog.required = enabled;

        if (!enabled) {
            aiLog.setCustomValidity('');
            setError(aiLog, false);
        }
    }
};

/**
 * Update soft warnings for provenance, criteria relation and uncertainty fields.
 *
 * These warnings help the user but are not authoritative validation.
 *
 * @param {HTMLElement} root Root element.
 */
const updateProofWarnings = root => {
    const relation = getField(root, SELECTORS.relationtocriteria);
    const provenance = getField(root, SELECTORS.provenancestatement);
    const uncertainty = getField(root, SELECTORS.uncertaintynotes);

    setWarning(relation, getValue(relation) === '');
    setWarning(provenance, getValue(provenance) === '');

    if (uncertainty) {
        const proofType = getField(root, SELECTORS.prooftype);
        const selected = proofType ? proofType.value : '';
        setWarning(uncertainty, selected === 'ai_log' && getValue(uncertainty) === '');
    }
};

/**
 * Validate AI disclosure before submit.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Promise<Boolean>} True when valid.
 */
const validateAiDisclosure = async root => {
    const aiAssisted = getField(root, SELECTORS.aiassisted);
    const aiLog = getField(root, SELECTORS.ailog);

    if (!aiAssisted || !aiAssisted.checked || !aiLog) {
        return true;
    }

    if (getValue(aiLog) !== '') {
        aiLog.setCustomValidity('');
        setError(aiLog, false);
        return true;
    }

    const message = await getString('ailogrequired', COMPONENT);

    aiLog.setCustomValidity(message);
    aiLog.reportValidity();
    setError(aiLog, true);
    setStatus(root, message);

    return false;
};

/**
 * Validate minimal proof presence before final submit.
 *
 * Moodle/PHP validation remains authoritative. This is only a user-facing guard.
 *
 * @param {HTMLElement} root Root element.
 * @returns {Promise<Boolean>} True when valid.
 */
const validateProofPresence = async root => {
    const text = getField(root, SELECTORS.submissionTextEditor);
    const url = getField(root, SELECTORS.submissionurl);

    const hasText = getValue(text) !== '';
    const hasUrl = getValue(url) !== '';

    if (hasText || hasUrl) {
        if (text) {
            text.setCustomValidity('');
        }
        return true;
    }

    const message = await getString('submissionrequiresproof', COMPONENT);

    if (text) {
        text.setCustomValidity(message);
        text.reportValidity();
        setError(text, true);
    }

    setStatus(root, message);

    return false;
};

/**
 * Handle final submit.
 *
 * @param {SubmitEvent} event Submit event.
 * @param {HTMLElement} root Root element.
 */
const handleSubmit = async(event, root) => {
    const submitter = event.submitter || document.activeElement;
    const isDraft = submitter && submitter.matches(SELECTORS.saveDraftButton);

    updateProofWarnings(root);

    const aiValid = await validateAiDisclosure(root);

    if (!aiValid) {
        event.preventDefault();
        event.stopPropagation();
        return;
    }

    if (!isDraft) {
        const proofValid = await validateProofPresence(root);

        if (!proofValid) {
            event.preventDefault();
            event.stopPropagation();
        }
    }
};

/**
 * Bind listeners for one root.
 *
 * @param {HTMLElement} root Root element.
 */
const bindRoot = root => {
    const proofType = getField(root, SELECTORS.prooftype);
    const aiAssisted = getField(root, SELECTORS.aiassisted);
    const aiLog = getField(root, SELECTORS.ailog);
    const relation = getField(root, SELECTORS.relationtocriteria);
    const provenance = getField(root, SELECTORS.provenancestatement);
    const uncertainty = getField(root, SELECTORS.uncertaintynotes);
    const form = root.closest('form') || root.querySelector('form');

    if (proofType) {
        proofType.addEventListener('change', () => {
            updateProofTypeUi(root);
            updateProofWarnings(root);
        });
    }

    if (aiAssisted) {
        aiAssisted.addEventListener('change', () => updateAiUi(root));
    }

    [aiLog, relation, provenance, uncertainty].forEach(field => {
        if (field) {
            field.addEventListener('input', () => {
                field.setCustomValidity('');
                setError(field, false);
                updateProofWarnings(root);
            });
        }
    });

    if (form) {
        form.addEventListener('submit', event => {
            handleSubmit(event, root);
        });
    }

    updateProofTypeUi(root);
    updateAiUi(root);
    updateProofWarnings(root);
};

/**
 * Initialise one proof submission region.
 *
 * Recommended PHP usage:
 *
 * $PAGE->requires->js_call_amd('mod_uckkchallenge/proof', 'init', [$rootid]);
 *
 * @param {String|null} rootId Optional root element id.
 */
export const init = rootId => {
    const roots = rootId
        ? [document.getElementById(rootId)].filter(Boolean)
        : Array.from(document.querySelectorAll(SELECTORS.root));

    roots.forEach(root => {
        if (root.getAttribute(ATTRIBUTES.initialised) === 'true') {
            return;
        }

        root.setAttribute(ATTRIBUTES.initialised, 'true');
        bindRoot(root);
    });
};