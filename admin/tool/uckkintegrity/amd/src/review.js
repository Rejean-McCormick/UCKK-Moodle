// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Behaviour for UCKK integrity review panels.
 *
 * @module     tool_uckkintegrity/review
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    /**
     * Initialise review UI behaviour.
     *
     * @param {String} selector Root selector.
     */
    const init = function(selector) {
        const root = document.querySelector(selector);

        if (!root) {
            return;
        }

        root.classList.add('js-enhanced');

        const statusField = root.querySelector('[name="status"]');
        const assignedToField = root.querySelector('[name="assignedto"]');
        const noteTypeField = root.querySelector('[name="notetype"]');
        const bodyField = root.querySelector('[name="body"]');

        if (statusField && bodyField) {
            statusField.addEventListener('change', function() {
                const status = statusField.value;

                if (status === 'correction_required' && noteTypeField) {
                    noteTypeField.value = 'correction';
                }

                if (status === 'waiting_for_response' && noteTypeField) {
                    noteTypeField.value = 'response';
                }

                if (status === 'resolved' && noteTypeField) {
                    noteTypeField.value = 'decision';
                }

                if (!bodyField.value.trim()) {
                    bodyField.focus();
                }
            });
        }

        if (assignedToField && statusField) {
            assignedToField.addEventListener('change', function() {
                if (assignedToField.value && statusField.value === 'opened') {
                    statusField.value = 'assigned';
                }
            });
        }
    };

    return {
        init: init
    };
});