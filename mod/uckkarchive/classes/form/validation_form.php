<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Validation form for UCKK archive items.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkarchive\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use context_module;
use moodleform;
use stdClass;

/**
 * Server-side validation form for archive items.
 *
 * This form collects Archiviste/Inquisiteur validation input. It does not
 * silently rewrite archive history, delete evidence, close integrity cases,
 * award recognition, or bypass provenance/versioning rules.
 */
final class validation_form extends moodleform {
    /** Validation state: unverified. */
    private const VALIDATION_UNVERIFIED = 'unverified';

    /** Validation state: human reviewed. */
    private const VALIDATION_HUMAN_REVIEWED = 'human_reviewed';

    /** Validation state: verified. */
    private const VALIDATION_VERIFIED = 'verified';

    /** Validation state: contested. */
    private const VALIDATION_CONTESTED = 'contested';

    /** Validation state: invalidated. */
    private const VALIDATION_INVALIDATED = 'invalidated';

    /** Validation state: archived. */
    private const VALIDATION_ARCHIVED = 'archived';

    /** Status: pending review. */
    private const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: validated. */
    private const STATUS_VALIDATED = 'validated';

    /** Status: rejected. */
    private const STATUS_REJECTED = 'rejected';

    /** Status: correction required. */
    private const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Status: contested. */
    private const STATUS_CONTESTED = 'contested';

    /** Status: invalidated. */
    private const STATUS_INVALIDATED = 'invalidated';

    /** Status: archived. */
    private const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    private const VISIBILITY_PRIVATE = 'private';

    /** Visibility: user. */
    private const VISIBILITY_USER = 'user';

    /** Visibility: group. */
    private const VISIBILITY_GROUP = 'group';

    /** Visibility: course. */
    private const VISIBILITY_COURSE = 'course';

    /** Visibility: cohort. */
    private const VISIBILITY_COHORT = 'cohort';

    /** Visibility: program. */
    private const VISIBILITY_PROGRAM = 'program';

    /** Visibility: institution. */
    private const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    private const VISIBILITY_PUBLIC = 'public';

    /** Visibility: restricted. */
    private const VISIBILITY_RESTRICTED = 'restricted';

    /** Visibility: restricted integrity. */
    private const VISIBILITY_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Provenance: human. */
    private const PROVENANCE_HUMAN = 'human';

    /** Provenance: AI-assisted. */
    private const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance: imported. */
    private const PROVENANCE_IMPORTED = 'imported';

    /** Provenance: system. */
    private const PROVENANCE_SYSTEM = 'system';

    /** Provenance: archive. */
    private const PROVENANCE_ARCHIVE = 'archive';

    /** Provenance: assembly. */
    private const PROVENANCE_ASSEMBLY = 'assembly';

    /** Provenance: challenge. */
    private const PROVENANCE_CHALLENGE = 'challenge';

    /** Provenance: integrity. */
    private const PROVENANCE_INTEGRITY = 'integrity';

    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $context = $this->get_context();
        $archive = $this->get_custom_record('archive');
        $item = $this->get_custom_record('item');

        $this->add_item_summary_section($mform, $archive, $item);
        $this->add_validation_decision_section($mform);
        $this->add_provenance_section($mform);
        $this->add_visibility_section($mform);
        $this->add_integrity_section($mform);
        $this->add_revision_section($mform, $context);
        $this->add_hidden_fields($mform);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'savedraft', get_string('savevalidationdraft', 'uckkarchive'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('submitvalidation', 'uckkarchive'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Add archive item summary section.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param stdClass $archive Archive instance.
     * @param stdClass $item Archive item.
     */
    private function add_item_summary_section(\MoodleQuickForm $mform, stdClass $archive, stdClass $item): void {
        $mform->addElement('header', 'itemsummaryheader', get_string('archivevalidation', 'uckkarchive'));

        if (!empty($archive->name)) {
            $mform->addElement(
                'static',
                'archivename',
                get_string('archive', 'uckkarchive'),
                format_string($archive->name)
            );
        }

        if (!empty($item->title) || !empty($item->name)) {
            $mform->addElement(
                'static',
                'itemtitle',
                get_string('archiveitem', 'uckkarchive'),
                format_string((string)($item->title ?? $item->name))
            );
        }

        if (!empty($item->summary)) {
            $mform->addElement(
                'static',
                'itemsummary',
                get_string('summary', 'uckkarchive'),
                format_text((string)$item->summary, FORMAT_HTML)
            );
        }

        if (!empty($item->status)) {
            $mform->addElement(
                'static',
                'currentstatus',
                get_string('currentstatus', 'uckkarchive'),
                s((string)$item->status)
            );
        }

        if (!empty($item->visibility)) {
            $mform->addElement(
                'static',
                'currentvisibility',
                get_string('currentvisibility', 'uckkarchive'),
                s((string)$item->visibility)
            );
        }

        if (!empty($item->validationstate)) {
            $mform->addElement(
                'static',
                'currentvalidationstate',
                get_string('currentvalidationstate', 'uckkarchive'),
                s((string)$item->validationstate)
            );
        }
    }

    /**
     * Add validation decision section.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_validation_decision_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'validationdecisionheader', get_string('validationdecision', 'uckkarchive'));

        $mform->addElement(
            'select',
            'validationstate',
            get_string('validationstate', 'uckkarchive'),
            $this->get_validation_state_options()
        );
        $mform->setDefault('validationstate', self::VALIDATION_HUMAN_REVIEWED);
        $mform->addRule('validationstate', null, 'required', null, 'client');

        $mform->addElement(
            'select',
            'status',
            get_string('status'),
            $this->get_status_options()
        );
        $mform->setDefault('status', self::STATUS_PENDING_REVIEW);
        $mform->addRule('status', null, 'required', null, 'client');

        $mform->addElement('text', 'validationgrade', get_string('validationgrade', 'uckkarchive'), [
            'size' => 8,
            'maxlength' => 20,
        ]);
        $mform->setType('validationgrade', PARAM_FLOAT);
        $mform->addHelpButton('validationgrade', 'validationgrade', 'uckkarchive');

        $mform->addElement('textarea', 'validationstatement', get_string('validationstatement', 'uckkarchive'), [
            'rows' => 6,
            'cols' => 80,
        ]);
        $mform->setType('validationstatement', PARAM_RAW);
        $mform->addRule('validationstatement', null, 'required', null, 'client');
        $mform->addHelpButton('validationstatement', 'validationstatement', 'uckkarchive');

        $mform->addElement('textarea', 'publicsummary', get_string('publicsummary', 'uckkarchive'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('publicsummary', PARAM_RAW);
        $mform->addHelpButton('publicsummary', 'publicsummary', 'uckkarchive');

        $mform->addElement('textarea', 'privatefeedback', get_string('privatefeedback', 'uckkarchive'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('privatefeedback', PARAM_RAW);
        $mform->addHelpButton('privatefeedback', 'privatefeedback', 'uckkarchive');
    }

    /**
     * Add provenance verification section.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_provenance_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'provenanceheader', get_string('provenancevalidation', 'uckkarchive'));

        $mform->addElement(
            'select',
            'provenance',
            get_string('provenance', 'uckkarchive'),
            $this->get_provenance_options()
        );
        $mform->setDefault('provenance', self::PROVENANCE_HUMAN);
        $mform->addRule('provenance', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'provenanceverified', get_string('provenanceverified', 'uckkarchive'));
        $mform->setDefault('provenanceverified', 0);

        $mform->addElement('advcheckbox', 'sourceverified', get_string('sourceverified', 'uckkarchive'));
        $mform->setDefault('sourceverified', 0);

        $mform->addElement('advcheckbox', 'contextverified', get_string('contextverified', 'uckkarchive'));
        $mform->setDefault('contextverified', 0);

        $mform->addElement('advcheckbox', 'evidencerelationverified', get_string('evidencerelationverified', 'uckkarchive'));
        $mform->setDefault('evidencerelationverified', 0);

        $mform->addElement('textarea', 'provenancenotes', get_string('provenancenotes', 'uckkarchive'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('provenancenotes', PARAM_RAW);
        $mform->addHelpButton('provenancenotes', 'provenancenotes', 'uckkarchive');

        $mform->addElement('text', 'provenancehash', get_string('provenancehash', 'uckkarchive'), [
            'size' => 64,
            'maxlength' => 128,
        ]);
        $mform->setType('provenancehash', PARAM_ALPHANUMEXT);
    }

    /**
     * Add visibility section.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_visibility_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'visibilityheader', get_string('visibilityreview', 'uckkarchive'));

        $mform->addElement(
            'select',
            'visibility',
            get_string('visibility', 'uckkarchive'),
            $this->get_visibility_options()
        );
        $mform->setDefault('visibility', self::VISIBILITY_COURSE);
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'visibilityconfirmed', get_string('visibilityconfirmed', 'uckkarchive'));
        $mform->setDefault('visibilityconfirmed', 0);

        $mform->addElement('textarea', 'visibilitynotes', get_string('visibilitynotes', 'uckkarchive'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('visibilitynotes', PARAM_RAW);
        $mform->addHelpButton('visibilitynotes', 'visibilitynotes', 'uckkarchive');
    }

    /**
     * Add integrity review section.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_integrity_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'integrityheader', get_string('integrityreview', 'uckkarchive'));

        $mform->addElement('advcheckbox', 'requiresintegritycase', get_string('requiresintegritycase', 'uckkarchive'));
        $mform->setDefault('requiresintegritycase', 0);
        $mform->addHelpButton('requiresintegritycase', 'requiresintegritycase', 'uckkarchive');

        $mform->addElement('text', 'integritysummary', get_string('integritysummary', 'uckkarchive'), [
            'size' => 80,
            'maxlength' => 255,
        ]);
        $mform->setType('integritysummary', PARAM_TEXT);
        $mform->hideIf('integritysummary', 'requiresintegritycase', 'notchecked');

        $mform->addElement('textarea', 'integritynotes', get_string('integritynotes', 'uckkarchive'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('integritynotes', PARAM_RAW);
        $mform->addHelpButton('integritynotes', 'integritynotes', 'uckkarchive');

        $mform->addElement('advcheckbox', 'contestationallowed', get_string('contestationallowed', 'uckkarchive'));
        $mform->setDefault('contestationallowed', 1);
    }

    /**
     * Add revision and archive memory section.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param context_module $context Module context.
     */
    private function add_revision_section(\MoodleQuickForm $mform, context_module $context): void {
        $mform->addElement('header', 'revisionheader', get_string('revisionandmemory', 'uckkarchive'));

        $mform->addElement('advcheckbox', 'requiresrevision', get_string('requiresrevision', 'uckkarchive'));
        $mform->setDefault('requiresrevision', 0);

        $mform->addElement('textarea', 'revisioninstructions', get_string('revisioninstructions', 'uckkarchive'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('revisioninstructions', PARAM_RAW);
        $mform->hideIf('revisioninstructions', 'requiresrevision', 'notchecked');

        $mform->addElement('editor', 'archivistnotes_editor', get_string('archivistnotes', 'uckkarchive'), null, $this->get_editor_options($context));
        $mform->setType('archivistnotes_editor', PARAM_RAW);
        $mform->addHelpButton('archivistnotes_editor', 'archivistnotes', 'uckkarchive');

        $mform->addElement('advcheckbox', 'createversionrecord', get_string('createversionrecord', 'uckkarchive'));
        $mform->setDefault('createversionrecord', 1);

        $mform->addElement('advcheckbox', 'readyforexport', get_string('readyforexport', 'uckkarchive'));
        $mform->setDefault('readyforexport', 0);
        $mform->hideIf('readyforexport', 'status', 'neq', self::STATUS_VALIDATED);
    }

    /**
     * Add hidden identifiers.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_hidden_fields(\MoodleQuickForm $mform): void {
        foreach ([
            'id',
            'archiveid',
            'itemid',
            'validationid',
            'revisionid',
            'userid',
            'returnurl',
        ] as $field) {
            $mform->addElement('hidden', $field);
        }

        $mform->setType('id', PARAM_INT);
        $mform->setType('archiveid', PARAM_INT);
        $mform->setType('itemid', PARAM_INT);
        $mform->setType('validationid', PARAM_INT);
        $mform->setType('revisionid', PARAM_INT);
        $mform->setType('userid', PARAM_INT);
        $mform->setType('returnurl', PARAM_LOCALURL);
    }

    /**
     * Validate submitted data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $isfinalsubmit = !empty($data['submitbutton']);
        $status = (string)($data['status'] ?? '');
        $validationstate = (string)($data['validationstate'] ?? '');
        $visibility = (string)($data['visibility'] ?? '');

        if (!in_array($status, array_keys($this->get_status_options()), true)) {
            $errors['status'] = get_string('invalidstatus', 'uckkarchive');
        }

        if (!in_array($validationstate, array_keys($this->get_validation_state_options()), true)) {
            $errors['validationstate'] = get_string('invalidvalidationstate', 'uckkarchive');
        }

        if (!in_array($visibility, array_keys($this->get_visibility_options()), true)) {
            $errors['visibility'] = get_string('invalidvisibility', 'uckkarchive');
        }

        if (isset($data['validationgrade']) && $data['validationgrade'] !== '') {
            $grade = (float)$data['validationgrade'];

            if ($grade < 0 || $grade > 100) {
                $errors['validationgrade'] = get_string('validationgrademustbeinrange', 'uckkarchive');
            }
        }

        $statement = trim((string)($data['validationstatement'] ?? ''));

        if ($isfinalsubmit && $statement === '') {
            $errors['validationstatement'] = get_string('validationstatementrequired', 'uckkarchive');
        }

        if ($status === self::STATUS_VALIDATED) {
            if (empty($data['provenanceverified'])) {
                $errors['provenanceverified'] = get_string('provenancemustbeverified', 'uckkarchive');
            }

            if (empty($data['visibilityconfirmed'])) {
                $errors['visibilityconfirmed'] = get_string('visibilitymustbeconfirmed', 'uckkarchive');
            }

            if ($validationstate !== self::VALIDATION_VERIFIED && $validationstate !== self::VALIDATION_HUMAN_REVIEWED) {
                $errors['validationstate'] = get_string('validateditemneedsreviewstate', 'uckkarchive');
            }
        }

        if ($status === self::STATUS_CORRECTION_REQUIRED && empty($data['requiresrevision'])) {
            $errors['requiresrevision'] = get_string('correctionrequiresrevision', 'uckkarchive');
        }

        if (!empty($data['requiresrevision']) && trim((string)($data['revisioninstructions'] ?? '')) === '') {
            $errors['revisioninstructions'] = get_string('revisioninstructionsrequired', 'uckkarchive');
        }

        if (!empty($data['requiresintegritycase'])) {
            if (trim((string)($data['integritysummary'] ?? '')) === '') {
                $errors['integritysummary'] = get_string('integritysummaryrequired', 'uckkarchive');
            }

            if (trim((string)($data['integritynotes'] ?? '')) === '') {
                $errors['integritynotes'] = get_string('integritynotesrequired', 'uckkarchive');
            }
        }

        if (
            in_array($visibility, [self::VISIBILITY_PUBLIC, self::VISIBILITY_INSTITUTION], true)
            && trim((string)($data['publicsummary'] ?? '')) === ''
            && $isfinalsubmit
        ) {
            $errors['publicsummary'] = get_string('publicsummaryrequired', 'uckkarchive');
        }

        if (
            in_array($validationstate, [self::VALIDATION_CONTESTED, self::VALIDATION_INVALIDATED], true)
            && trim((string)($data['integritynotes'] ?? '')) === ''
        ) {
            $errors['integritynotes'] = get_string('integritynotesrequired', 'uckkarchive');
        }

        return $errors;
    }

    /**
     * Whether the submitted action is only a draft save.
     *
     * @return bool
     */
    public function is_draft_save(): bool {
        $data = $this->get_data();

        return $data !== null && !empty($data->savedraft) && empty($data->submitbutton);
    }

    /**
     * Return module context from custom data.
     *
     * @return context_module
     */
    private function get_context(): context_module {
        if (!empty($this->_customdata['context']) && $this->_customdata['context'] instanceof context_module) {
            return $this->_customdata['context'];
        }

        throw new \coding_exception('validation_form requires a module context in customdata.');
    }

    /**
     * Get a custom record.
     *
     * @param string $key Custom data key.
     * @return stdClass
     */
    private function get_custom_record(string $key): stdClass {
        if (empty($this->_customdata[$key])) {
            return new stdClass();
        }

        return (object)$this->_customdata[$key];
    }

    /**
     * Editor options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private function get_editor_options(context_module $context): array {
        return [
            'context' => $context,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => get_max_upload_file_size(),
            'trusttext' => false,
            'noclean' => false,
            'subdirs' => false,
        ];
    }

    /**
     * Validation state options.
     *
     * @return array<string, string>
     */
    private function get_validation_state_options(): array {
        return [
            self::VALIDATION_UNVERIFIED => get_string('validationstate:unverified', 'uckkarchive'),
            self::VALIDATION_HUMAN_REVIEWED => get_string('validationstate:human_reviewed', 'uckkarchive'),
            self::VALIDATION_VERIFIED => get_string('validationstate:verified', 'uckkarchive'),
            self::VALIDATION_CONTESTED => get_string('validationstate:contested', 'uckkarchive'),
            self::VALIDATION_INVALIDATED => get_string('validationstate:invalidated', 'uckkarchive'),
            self::VALIDATION_ARCHIVED => get_string('validationstate:archived', 'uckkarchive'),
        ];
    }

    /**
     * Status options.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return [
            self::STATUS_PENDING_REVIEW => get_string('status:pending_review', 'uckkarchive'),
            self::STATUS_VALIDATED => get_string('status:validated', 'uckkarchive'),
            self::STATUS_REJECTED => get_string('status:rejected', 'uckkarchive'),
            self::STATUS_CORRECTION_REQUIRED => get_string('status:correction_required', 'uckkarchive'),
            self::STATUS_CONTESTED => get_string('status:contested', 'uckkarchive'),
            self::STATUS_INVALIDATED => get_string('status:invalidated', 'uckkarchive'),
            self::STATUS_ARCHIVED => get_string('status:archived', 'uckkarchive'),
        ];
    }

    /**
     * Visibility options.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        return [
            self::VISIBILITY_PRIVATE => get_string('visibility:private', 'uckkarchive'),
            self::VISIBILITY_USER => get_string('visibility:user', 'uckkarchive'),
            self::VISIBILITY_GROUP => get_string('visibility:group', 'uckkarchive'),
            self::VISIBILITY_COURSE => get_string('visibility:course', 'uckkarchive'),
            self::VISIBILITY_COHORT => get_string('visibility:cohort', 'uckkarchive'),
            self::VISIBILITY_PROGRAM => get_string('visibility:program', 'uckkarchive'),
            self::VISIBILITY_INSTITUTION => get_string('visibility:institution', 'uckkarchive'),
            self::VISIBILITY_PUBLIC => get_string('visibility:public', 'uckkarchive'),
            self::VISIBILITY_RESTRICTED => get_string('visibility:restricted', 'uckkarchive'),
            self::VISIBILITY_RESTRICTED_INTEGRITY => get_string('visibility:restricted_integrity', 'uckkarchive'),
        ];
    }

    /**
     * Provenance options.
     *
     * @return array<string, string>
     */
    private function get_provenance_options(): array {
        return [
            self::PROVENANCE_HUMAN => get_string('provenance:human', 'uckkarchive'),
            self::PROVENANCE_AI_ASSISTED => get_string('provenance:ai_assisted', 'uckkarchive'),
            self::PROVENANCE_IMPORTED => get_string('provenance:imported', 'uckkarchive'),
            self::PROVENANCE_SYSTEM => get_string('provenance:system', 'uckkarchive'),
            self::PROVENANCE_ARCHIVE => get_string('provenance:archive', 'uckkarchive'),
            self::PROVENANCE_ASSEMBLY => get_string('provenance:assembly', 'uckkarchive'),
            self::PROVENANCE_CHALLENGE => get_string('provenance:challenge', 'uckkarchive'),
            self::PROVENANCE_INTEGRITY => get_string('provenance:integrity', 'uckkarchive'),
        ];
    }
}
```

Add these strings to `mod/uckkarchive/lang/en/uckkarchive.php`:

```php id="7bm85z"
$string['archive'] = 'Archive';
$string['archiveitem'] = 'Archive item';
$string['archivevalidation'] = 'Archive validation';
$string['archivistnotes'] = 'Archivist notes';
$string['archivistnotes_help'] = 'Internal validation notes for archive history. These notes must remain proportionate, auditable, and permission-controlled.';
$string['contestationallowed'] = 'Contestation remains allowed';
$string['contextverified'] = 'Context verified';
$string['correctionrequiresrevision'] = 'A correction-required decision must request a revision.';
$string['createversionrecord'] = 'Create version record';
$string['currentstatus'] = 'Current status';
$string['currentvalidationstate'] = 'Current validation state';
$string['currentvisibility'] = 'Current visibility';
$string['evidencerelationverified'] = 'Evidence relation verified';
$string['integritynotes'] = 'Integrity notes';
$string['integritynotesrequired'] = 'Integrity notes are required for this decision.';
$string['integrityreview'] = 'Integrity review';
$string['integritysummary'] = 'Integrity summary';
$string['integritysummaryrequired'] = 'An integrity summary is required when an integrity case is requested.';
$string['invalidstatus'] = 'Invalid status.';
$string['invalidvalidationstate'] = 'Invalid validation state.';
$string['invalidvisibility'] = 'Invalid visibility.';
$string['privatefeedback'] = 'Private feedback';
$string['privatefeedback_help'] = 'Feedback visible only to authorised reviewers and record owners according to archive visibility rules.';
$string['provenance'] = 'Provenance';
$string['provenance:ai_assisted'] = 'AI-assisted';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assembly';
$string['provenance:challenge'] = 'Challenge';
$string['provenance:human'] = 'Human';
$string['provenance:imported'] = 'Imported';
$string['provenance:integrity'] = 'Integrity';
$string['provenance:system'] = 'System';
$string['provenancehash'] = 'Provenance hash';
$string['provenancemustbeverified'] = 'Provenance must be verified before the item can be validated.';
$string['provenancenotes'] = 'Provenance notes';
$string['provenancenotes_help'] = 'Document sources, transformations, uncertainty, author/context/source/date checks, and any limits of verification.';
$string['provenancevalidation'] = 'Provenance validation';
$string['provenanceverified'] = 'Provenance verified';
$string['publicsummary'] = 'Public summary';
$string['publicsummary_help'] = 'Summary safe for the selected visibility level.';
$string['publicsummaryrequired'] = 'A public or institutional item requires a public summary.';
$string['readyforexport'] = 'Ready for export';
$string['requiresintegritycase'] = 'Requires integrity case';
$string['requiresintegritycase_help'] = 'Request a restricted integrity case when the item involves contested facts, privacy, dignity, manipulation, AI misuse, or procedural risk.';
$string['requiresrevision'] = 'Requires revision';
$string['revisionandmemory'] = 'Revision and memory';
$string['revisioninstructions'] = 'Revision instructions';
$string['revisioninstructionsrequired'] = 'Revision instructions are required when revision is requested.';
$string['savevalidationdraft'] = 'Save validation draft';
$string['sourceverified'] = 'Source verified';
$string['status:archived'] = 'Archived';
$string['status:contested'] = 'Contested';
$string['status:correction_required'] = 'Correction required';
$string['status:invalidated'] = 'Invalidated';
$string['status:pending_review'] = 'Pending review';
$string['status:rejected'] = 'Rejected';
$string['status:validated'] = 'Validated';
$string['submitvalidation'] = 'Submit validation';
$string['summary'] = 'Summary';
$string['validateditemneedsreviewstate'] = 'A validated item must have a human reviewed or verified validation state.';
$string['validationdecision'] = 'Validation decision';
$string['validationgrade'] = 'Validation grade';
$string['validationgrade_help'] = 'Optional internal validation confidence or quality score from 0 to 100.';
$string['validationgrademustbeinrange'] = 'The validation grade must be between 0 and 100.';
$string['validationstate'] = 'Validation state';
$string['validationstate:archived'] = 'Archived';
$string['validationstate:contested'] = 'Contested';
$string['validationstate:human_reviewed'] = 'Human reviewed';
$string['validationstate:invalidated'] = 'Invalidated';
$string['validationstate:unverified'] = 'Unverified';
$string['validationstate:verified'] = 'Verified';
$string['validationstatement'] = 'Validation statement';
$string['validationstatement_help'] = 'Explain what was validated, what remains uncertain, and why the selected validation decision is justified.';
$string['validationstatementrequired'] = 'A validation statement is required before final submission.';
$string['visibility'] = 'Visibility';
$string['visibility:cohort'] = 'Cohort';
$string['visibility:course'] = 'Course';
$string['visibility:group'] = 'Group';
$string['visibility:institution'] = 'Institution';
$string['visibility:private'] = 'Private';
$string['visibility:program'] = 'Program';
$string['visibility:public'] = 'Public';
$string['visibility:restricted'] = 'Restricted';
$string['visibility:restricted_integrity'] = 'Restricted integrity';
$string['visibility:user'] = 'User';
$string['visibilityconfirmed'] = 'Visibility confirmed';
$string['visibilitymustbeconfirmed'] = 'Visibility must be confirmed before the item can be validated.';
$string['visibilitynotes'] = 'Visibility notes';
$string['visibilitynotes_help'] = 'Explain why this visibility level is appropriate, especially for public, restricted, private, or integrity-sensitive records.';
$string['visibilityreview'] = 'Visibility review';
```

Add these strings to `mod/uckkarchive/lang/fr/uckkarchive.php`:

```php id="0ci8da"
$string['archive'] = 'Archive';
$string['archiveitem'] = 'Élément d’archive';
$string['archivevalidation'] = 'Validation d’archive';
$string['archivistnotes'] = 'Notes de l’archiviste';
$string['archivistnotes_help'] = 'Notes internes de validation pour l’historique d’archive. Elles doivent rester proportionnées, auditables et contrôlées par permissions.';
$string['contestationallowed'] = 'La contestation demeure permise';
$string['contextverified'] = 'Contexte vérifié';
$string['correctionrequiresrevision'] = 'Une décision de correction requise doit demander une révision.';
$string['createversionrecord'] = 'Créer une trace de version';
$string['currentstatus'] = 'Statut actuel';
$string['currentvalidationstate'] = 'État de validation actuel';
$string['currentvisibility'] = 'Visibilité actuelle';
$string['evidencerelationverified'] = 'Lien avec la preuve vérifié';
$string['integritynotes'] = 'Notes d’intégrité';
$string['integritynotesrequired'] = 'Des notes d’intégrité sont requises pour cette décision.';
$string['integrityreview'] = 'Revue d’intégrité';
$string['integritysummary'] = 'Résumé d’intégrité';
$string['integritysummaryrequired'] = 'Un résumé d’intégrité est requis lorsqu’un dossier d’intégrité est demandé.';
$string['invalidstatus'] = 'Statut invalide.';
$string['invalidvalidationstate'] = 'État de validation invalide.';
$string['invalidvisibility'] = 'Visibilité invalide.';
$string['privatefeedback'] = 'Rétroaction privée';
$string['privatefeedback_help'] = 'Rétroaction visible seulement par les personnes autorisées et les propriétaires du dossier selon les règles de visibilité de l’archive.';
$string['provenance'] = 'Provenance';
$string['provenance:ai_assisted'] = 'Assistée par IA';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assemblée';
$string['provenance:challenge'] = 'Défi';
$string['provenance:human'] = 'Humaine';
$string['provenance:imported'] = 'Importée';
$string['provenance:integrity'] = 'Intégrité';
$string['provenance:system'] = 'Système';
$string['provenancehash'] = 'Empreinte de provenance';
$string['provenancemustbeverified'] = 'La provenance doit être vérifiée avant que l’élément puisse être validé.';
$string['provenancenotes'] = 'Notes de provenance';
$string['provenancenotes_help'] = 'Documentez les sources, transformations, incertitudes, vérifications d’auteur/contexte/source/date et les limites de vérification.';
$string['provenancevalidation'] = 'Validation de provenance';
$string['provenanceverified'] = 'Provenance vérifiée';
$string['publicsummary'] = 'Résumé public';
$string['publicsummary_help'] = 'Résumé compatible avec le niveau de visibilité sélectionné.';
$string['publicsummaryrequired'] = 'Un élément public ou institutionnel exige un résumé public.';
$string['readyforexport'] = 'Prêt pour export';
$string['requiresintegritycase'] = 'Requiert un dossier d’intégrité';
$string['requiresintegritycase_help'] = 'Demander un dossier d’intégrité restreint lorsque l’élément implique des faits contestés, de la vie privée, de la dignité, de la manipulation, un mauvais usage de l’IA ou un risque procédural.';
$string['requiresrevision'] = 'Requiert une révision';
$string['revisionandmemory'] = 'Révision et mémoire';
$string['revisioninstructions'] = 'Instructions de révision';
$string['revisioninstructionsrequired'] = 'Des instructions de révision sont requises lorsqu’une révision est demandée.';
$string['savevalidationdraft'] = 'Enregistrer le brouillon de validation';
$string['sourceverified'] = 'Source vérifiée';
$string['status:archived'] = 'Archivé';
$string['status:contested'] = 'Contesté';
$string['status:correction_required'] = 'Correction requise';
$string['status:invalidated'] = 'Invalidé';
$string['status:pending_review'] = 'En attente de revue';
$string['status:rejected'] = 'Rejeté';
$string['status:validated'] = 'Validé';
$string['submitvalidation'] = 'Soumettre la validation';
$string['summary'] = 'Résumé';
$string['validateditemneedsreviewstate'] = 'Un élément validé doit avoir un état de validation revu humainement ou vérifié.';
$string['validationdecision'] = 'Décision de validation';
$string['validationgrade'] = 'Note de validation';
$string['validationgrade_help'] = 'Score interne optionnel de confiance ou de qualité de validation entre 0 et 100.';
$string['validationgrademustbeinrange'] = 'La note de validation doit être entre 0 et 100.';
$string['validationstate'] = 'État de validation';
$string['validationstate:archived'] = 'Archivé';
$string['validationstate:contested'] = 'Contesté';
$string['validationstate:human_reviewed'] = 'Revu humainement';
$string['validationstate:invalidated'] = 'Invalidé';
$string['validationstate:unverified'] = 'Non vérifié';
$string['validationstate:verified'] = 'Vérifié';
$string['validationstatement'] = 'Énoncé de validation';
$string['validationstatement_help'] = 'Expliquez ce qui a été validé, ce qui reste incertain et pourquoi la décision de validation choisie est justifiée.';
$string['validationstatementrequired'] = 'Un énoncé de validation est requis avant la soumission finale.';
$string['visibility'] = 'Visibilité';
$string['visibility:cohort'] = 'Cohorte';
$string['visibility:course'] = 'Cours';
$string['visibility:group'] = 'Groupe';
$string['visibility:institution'] = 'Institution';
$string['visibility:private'] = 'Privée';
$string['visibility:program'] = 'Programme';
$string['visibility:public'] = 'Publique';
$string['visibility:restricted'] = 'Restreinte';
$string['visibility:restricted_integrity'] = 'Restreinte à l’intégrité';
$string['visibility:user'] = 'Utilisateur';
$string['visibilityconfirmed'] = 'Visibilité confirmée';
$string['visibilitymustbeconfirmed'] = 'La visibilité doit être confirmée avant que l’élément puisse être validé.';
$string['visibilitynotes'] = 'Notes de visibilité';
$string['visibilitynotes_help'] = 'Expliquez pourquoi ce niveau de visibilité est approprié, surtout pour les traces publiques, restreintes, privées ou sensibles à l’intégrité.';
$string['visibilityreview'] = 'Revue de visibilité';

