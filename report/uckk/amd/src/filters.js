// This file is part of Moodle - http://moodle.org/

/**
 * Filter interactions for report_uckk.
 *
 * Keeps dashboard filters aligned with the canonical filter names used by:
 * - report_uckk\local\filters
 * - report_uckk\local\exporter
 * - report_uckk\output\report_dashboard
 * - templates/report_filters.mustache
 *
 * @module     report_uckk/filters
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    /**
     * Selector for the report filter form.
     *
     * @type {String}
     */
    const SELECTOR = '[data-region="report-uckk-filters"]';

    /**
     * Field names that should contain positive integers or be empty.
     *
     * @type {String[]}
     */
    const INTEGER_FIELDS = [
        'userid',
        'cohortid',
        'programid',
        'courseid',
        'categoryid',
        'competencyid',
        'badgeid',
        'from',
        'to',
        'limit'
    ];

    /**
     * Text filter fields shared with PHP filters.
     *
     * @type {String[]}
     */
    const TEXT_FIELDS = [
        'status',
        'visibility',
        'challengetype',
        'assemblytype',
        'integritytype'
    ];

    /**
     * Return a named form control.
     *
     * @param {HTMLFormElement} form Filter form.
     * @param {String} name Field name.
     * @returns {HTMLInputElement|null}
     */
    const getField = function(form, name) {
        return form.querySelector('[name="' + name + '"]');
    };

    /**
     * Normalize an integer field.
     *
     * Empty, invalid, negative, and zero values are removed except for limit,
     * which must remain at least 1.
     *
     * @param {HTMLInputElement} field Field.
     * @returns {void}
     */
    const normalizeIntegerField = function(field) {
        if (!field) {
            return;
        }

        const name = field.getAttribute('name');
        const raw = String(field.value || '').trim();

        if (raw === '') {
            return;
        }

        const value = parseInt(raw, 10);

        if (Number.isNaN(value)) {
            field.value = '';
            return;
        }

        if (name === 'limit') {
            field.value = String(Math.max(1, Math.min(value, 10000)));
            return;
        }

        field.value = value > 0 ? String(value) : '';
    };

    /**
     * Normalize a text field.
     *
     * @param {HTMLInputElement} field Field.
     * @returns {void}
     */
    const normalizeTextField = function(field) {
        if (!field) {
            return;
        }

        field.value = String(field.value || '').trim();
    };

    /**
     * Remove empty filter fields before submission.
     *
     * This keeps URLs short and aligned with filters::url_params().
     *
     * @param {HTMLFormElement} form Filter form.
     * @returns {void}
     */
    const disableEmptyFields = function(form) {
        form.querySelectorAll('input, select').forEach(function(field) {
            const name = field.getAttribute('name');

            if (!name || name === 'report') {
                return;
            }

            if (String(field.value || '').trim() === '') {
                field.disabled = true;
            }
        });
    };

    /**
     * Restore disabled fields after failed submit handling or browser navigation.
     *
     * @param {HTMLFormElement} form Filter form.
     * @returns {void}
     */
    const restoreFields = function(form) {
        form.querySelectorAll('input, select').forEach(function(field) {
            field.disabled = false;
        });
    };

    /**
     * Validate date range fields.
     *
     * The PHP side accepts timestamps. This only prevents a clearly inverted
     * range from being submitted accidentally.
     *
     * @param {HTMLFormElement} form Filter form.
     * @returns {Boolean}
     */
    const validateRange = function(form) {
        const from = getField(form, 'from');
        const to = getField(form, 'to');

        if (!from || !to || from.value === '' || to.value === '') {
            return true;
        }

        return parseInt(from.value, 10) <= parseInt(to.value, 10);
    };

    /**
     * Mark form as changed.
     *
     * @param {HTMLFormElement} form Filter form.
     * @returns {void}
     */
    const markDirty = function(form) {
        form.setAttribute('data-dirty', 'true');
    };

    /**
     * Normalize all fields.
     *
     * @param {HTMLFormElement} form Filter form.
     * @returns {void}
     */
    const normalize = function(form) {
        INTEGER_FIELDS.forEach(function(name) {
            normalizeIntegerField(getField(form, name));
        });

        TEXT_FIELDS.forEach(function(name) {
            normalizeTextField(getField(form, name));
        });
    };

    /**
     * Initialize filter form behavior.
     *
     * @returns {void}
     */
    const init = function() {
        const form = document.querySelector(SELECTOR);

        if (!form) {
            return;
        }

        restoreFields(form);
        normalize(form);

        form.querySelectorAll('input, select').forEach(function(field) {
            field.addEventListener('change', function() {
                normalize(form);
                markDirty(form);
            });

            field.addEventListener('input', function() {
                markDirty(form);
            });
        });

        form.addEventListener('submit', function(event) {
            restoreFields(form);
            normalize(form);

            if (!validateRange(form)) {
                event.preventDefault();
                form.setAttribute('data-invalid-range', 'true');
                return;
            }

            form.removeAttribute('data-invalid-range');
            disableEmptyFields(form);
        });

        window.addEventListener('pageshow', function() {
            restoreFields(form);
        });
    };

    return {
        init: init
    };
});