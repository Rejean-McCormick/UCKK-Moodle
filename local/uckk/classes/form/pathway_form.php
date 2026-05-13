<?php
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
 * Program form for local_uckk.
 *
 * This form creates and edits UCKK program definitions.
 *
 * It does not write to the database directly. Form processing must be handled
 * by the controller page or by an API/service class after capability checks.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/uckk/locallib.php');

/**
 * Form for creating or editing a UCKK program.
 *
 * @package local_uckk
 */
class program_form extends \moodleform {
    /** Program type: tronc commun. */
    public const TYPE_TRONC_COMMUN = 'tronc_commun';

    /** Program type: baccalauréat. */
    public const TYPE_BACCALAUREAT = 'baccalaureat';

    /** Program type: mineure. */
    public const TYPE_MINEURE = 'mineure';

    /** Program type: certificate. */
    public const TYPE_CERTIFICAT = 'certificat';

    /** Program type: seminar. */
    public const TYPE_SEMINAIRE = 'seminaire';

    /** Program type: laboratory. */
    public const TYPE_LABORATOIRE = 'laboratoire';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * Define form fields.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $customdata = $this->_customdata ?? [];

        $programid = isset($customdata['id']) ? (int)$customdata['id'] : 0;
        $context = $customdata['context'] ?? \context_system::instance();

        // ---------------------------------------------------------------------
        // Hidden fields.
        // ---------------------------------------------------------------------

        $mform->addElement('hidden', 'id', $programid);
        $mform->setType('id', PARAM_INT);

        if (!empty($customdata['returnurl'])) {
            $mform->addElement('hidden', 'returnurl', (string)$customdata['returnurl']);
            $mform->setType('returnurl', PARAM_LOCALURL);
        }

        // ---------------------------------------------------------------------
        // General section.
        // ---------------------------------------------------------------------

        $mform->addElement('header', 'generalhdr', get_string('program:general', 'local_uckk'));

        $mform->addElement(
            'text',
            'fullname',
            get_string('program:fullname', 'local_uckk'),
            [
                'maxlength' => 255,
                'size' => 64,
            ]
        );
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->addRule('fullname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement(
            'text',
            'shortname',
            get_string('program:shortname', 'local_uckk'),
            [
                'maxlength' => 100,
                'size' => 40,
            ]
        );
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->addRule('shortname', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->addHelpButton('shortname', 'program:shortname', 'local_uckk');

        $mform->addElement(
            'text',
            'idnumber',
            get_string('program:idnumber', 'local_uckk'),
            [
                'maxlength' => 100,
                'size' => 40,
            ]
        );
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addRule('idnumber', get_string('required'), 'required', null, 'client');
        $mform->addRule('idnumber', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->addHelpButton('idnumber', 'program:idnumber', 'local_uckk');

        $mform->addElement(
            'select',
            'programtype',
            get_string('program:type', 'local_uckk'),
            self::get_program_type_options()
        );
        $mform->setType('programtype', PARAM_ALPHANUMEXT);
        $mform->setDefault('programtype', self::TYPE_BACCALAUREAT);
        $mform->addRule('programtype', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'select',
            'status',
            get_string('program:status', 'local_uckk'),
            self::get_status_options()
        );
        $mform->setType('status', PARAM_ALPHANUMEXT);
        $mform->setDefault('status', self::STATUS_DRAFT);
        $mform->addRule('status', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'select',
            'visibility',
            get_string('program:visibility', 'local_uckk'),
            self::get_visibility_options()
        );
        $mform->setType('visibility', PARAM_ALPHANUMEXT);
        $mform->setDefault('visibility', self::VISIBILITY_INSTITUTION);
        $mform->addRule('visibility', get_string('required'), 'required', null, 'client');

        // ---------------------------------------------------------------------
        // Academic structure.
        // ---------------------------------------------------------------------

        $mform->addElement('header', 'structurehdr', get_string('program:structure', 'local_uckk'));

        $categories = self::get_course_category_options();
        $mform->addElement(
            'select',
            'categoryid',
            get_string('program:category', 'local_uckk'),
            $categories
        );
        $mform->setType('categoryid', PARAM_INT);
        $mform->setDefault('categoryid', 0);
        $mform->addHelpButton('categoryid', 'program:category', 'local_uckk');

        $mform->addElement(
            'text',
            'cohortidnumber',
            get_string('program:cohortidnumber', 'local_uckk'),
            [
                'maxlength' => 100,
                'size' => 40,
            ]
        );
        $mform->setType('cohortidnumber', PARAM_TEXT);
        $mform->addRule('cohortidnumber', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->addHelpButton('cohortidnumber', 'program:cohortidnumber', 'local_uckk');

        $mform->addElement(
            'text',
            'requiredcourses',
            get_string('program:requiredcourses', 'local_uckk'),
            [
                'maxlength' => 1024,
                'size' => 80,
            ]
        );
        $mform->setType('requiredcourses', PARAM_TEXT);
        $mform->addHelpButton('requiredcourses', 'program:requiredcourses', 'local_uckk');

        $mform->addElement(
            'text',
            'requiredcompetencies',
            get_string('program:requiredcompetencies', 'local_uckk'),
            [
                'maxlength' => 1024,
                'size' => 80,
            ]
        );
        $mform->setType('requiredcompetencies', PARAM_TEXT);
        $mform->addHelpButton('requiredcompetencies', 'program:requiredcompetencies', 'local_uckk');

        $mform->addElement(
            'text',
            'requiredbadges',
            get_string('program:requiredbadges', 'local_uckk'),
            [
                'maxlength' => 1024,
                'size' => 80,
            ]
        );
        $mform->setType('requiredbadges', PARAM_TEXT);
        $mform->addHelpButton('requiredbadges', 'program:requiredbadges', 'local_uckk');

        // ---------------------------------------------------------------------
        // Description.
        // ---------------------------------------------------------------------

        $mform->addElement('header', 'descriptionhdr', get_string('program:description', 'local_uckk'));

        $editoroptions = self::get_editor_options($context);

        $mform->addElement(
            'editor',
            'description_editor',
            get_string('program:description', 'local_uckk'),
            null,
            $editoroptions
        );
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addHelpButton('description_editor', 'program:description', 'local_uckk');

        $mform->addElement(
            'editor',
            'outcomes_editor',
            get_string('program:outcomes', 'local_uckk'),
            null,
            $editoroptions
        );
        $mform->setType('outcomes_editor', PARAM_RAW);
        $mform->addHelpButton('outcomes_editor', 'program:outcomes', 'local_uckk');

        $mform->addElement(
            'editor',
            'recognition_editor',
            get_string('program:recognition', 'local_uckk'),
            null,
            $editoroptions
        );
        $mform->setType('recognition_editor', PARAM_RAW);
        $mform->addHelpButton('recognition_editor', 'program:recognition', 'local_uckk');

        // ---------------------------------------------------------------------
        // Governance and limits.
        // ---------------------------------------------------------------------

        $mform->addElement('header', 'governancehdr', get_string('program:governance', 'local_uckk'));

        $mform->addElement(
            'advcheckbox',
            'internalrecognition',
            get_string('program:internalrecognition', 'local_uckk'),
            get_string('program:internalrecognition_desc', 'local_uckk'),
            null,
            [0, 1]
        );
        $mform->setType('internalrecognition', PARAM_BOOL);
        $mform->setDefault('internalrecognition', 1);

        $mform->addElement(
            'advcheckbox',
            'requiresportfolio',
            get_string('program:requiresportfolio', 'local_uckk'),
            get_string('program:requiresportfolio_desc', 'local_uckk'),
            null,
            [0, 1]
        );
        $mform->setType('requiresportfolio', PARAM_BOOL);
        $mform->setDefault('requiresportfolio', 1);

        $mform->addElement(
            'advcheckbox',
            'requiresarchive',
            get_string('program:requiresarchive', 'local_uckk'),
            get_string('program:requiresarchive_desc', 'local_uckk'),
            null,
            [0, 1]
        );
        $mform->setType('requiresarchive', PARAM_BOOL);
        $mform->setDefault('requiresarchive', 1);

        $mform->addElement(
            'advcheckbox',
            'requiresintegrityreview',
            get_string('program:requiresintegrityreview', 'local_uckk'),
            get_string('program:requiresintegrityreview_desc', 'local_uckk'),
            null,
            [0, 1]
        );
        $mform->setType('requiresintegrityreview', PARAM_BOOL);
        $mform->setDefault('requiresintegrityreview', 0);

        $mform->addElement(
            'textarea',
            'limitsnotice',
            get_string('program:limitsnotice', 'local_uckk'),
            [
                'rows' => 4,
                'cols' => 80,
            ]
        );
        $mform->setType('limitsnotice', PARAM_TEXT);
        $mform->setDefault(
            'limitsnotice',
            get_string('program:limitsnotice_default', 'local_uckk')
        );

        // ---------------------------------------------------------------------
        // Display and sorting.
        // ---------------------------------------------------------------------

        $mform->addElement('header', 'displayhdr', get_string('program:display', 'local_uckk'));

        $mform->addElement(
            'text',
            'sortorder',
            get_string('program:sortorder', 'local_uckk'),
            [
                'maxlength' => 10,
                'size' => 10,
            ]
        );
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 100);

        $mform->addElement(
            'text',
            'color',
            get_string('program:color', 'local_uckk'),
            [
                'maxlength' => 20,
                'size' => 12,
                'placeholder' => '#b7791f',
            ]
        );
        $mform->setType('color', PARAM_TEXT);
        $mform->addRule('color', get_string('maximumchars', '', 20), 'maxlength', 20, 'client');

        $mform->addElement(
            'text',
            'icon',
            get_string('program:icon', 'local_uckk'),
            [
                'maxlength' => 100,
                'size' => 32,
                'placeholder' => 'program',
            ]
        );
        $mform->setType('icon', PARAM_ALPHANUMEXT);
        $mform->addRule('icon', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        // ---------------------------------------------------------------------
        // Metadata.
        // ---------------------------------------------------------------------

        $mform->addElement('header', 'metadatahdr', get_string('program:metadata', 'local_uckk'));

        $mform->addElement(
            'textarea',
            'metadata',
            get_string('program:metadatajson', 'local_uckk'),
            [
                'rows' => 8,
                'cols' => 80,
            ]
        );
        $mform->setType('metadata', PARAM_RAW);
        $mform->addHelpButton('metadata', 'program:metadatajson', 'local_uckk');
        $mform->setAdvanced('metadata');

        // ---------------------------------------------------------------------
        // Buttons.
        // ---------------------------------------------------------------------

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data Submitted form data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $shortname = isset($data['shortname']) ? trim((string)$data['shortname']) : '';
        $idnumber = isset($data['idnumber']) ? trim((string)$data['idnumber']) : '';
        $programtype = isset($data['programtype']) ? trim((string)$data['programtype']) : '';
        $status = isset($data['status']) ? trim((string)$data['status']) : '';
        $visibility = isset($data['visibility']) ? trim((string)$data['visibility']) : '';
        $categoryid = isset($data['categoryid']) ? (int)$data['categoryid'] : 0;
        $sortorder = isset($data['sortorder']) ? (int)$data['sortorder'] : 0;
        $color = isset($data['color']) ? trim((string)$data['color']) : '';
        $metadata = isset($data['metadata']) ? trim((string)$data['metadata']) : '';

        if ($shortname === '') {
            $errors['shortname'] = get_string('required');
        } else if (!preg_match('/^[a-zA-Z0-9_-]+$/', $shortname)) {
            $errors['shortname'] = get_string('program:error_shortname', 'local_uckk');
        }

        if ($idnumber === '') {
            $errors['idnumber'] = get_string('required');
        } else if (!preg_match('/^UCKK-PROG-[A-Z0-9_-]+$/', $idnumber)) {
            $errors['idnumber'] = get_string('program:error_idnumber', 'local_uckk');
        }

        if (!array_key_exists($programtype, self::get_program_type_options())) {
            $errors['programtype'] = get_string('program:error_programtype', 'local_uckk');
        }

        if (!array_key_exists($status, self::get_status_options())) {
            $errors['status'] = get_string('program:error_status', 'local_uckk');
        }

        if (!array_key_exists($visibility, self::get_visibility_options())) {
            $errors['visibility'] = get_string('program:error_visibility', 'local_uckk');
        }

        if ($categoryid < 0) {
            $errors['categoryid'] = get_string('program:error_category', 'local_uckk');
        }

        if ($sortorder < 0) {
            $errors['sortorder'] = get_string('program:error_sortorder', 'local_uckk');
        }

        if ($color !== '' && !preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color)) {
            $errors['color'] = get_string('program:error_color', 'local_uckk');
        }

        $this->validate_csv_field($data, $errors, 'requiredcourses', '/^UCKK-[A-Z0-9_-]+$/');
        $this->validate_csv_field($data, $errors, 'requiredcompetencies', '/^UCKK-COMP-[0-9]{3}$/');
        $this->validate_csv_field($data, $errors, 'requiredbadges', '/^[a-zA-Z0-9_-]+$/');

        if ($metadata !== '') {
            json_decode($metadata, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['metadata'] = get_string('program:error_metadatajson', 'local_uckk', json_last_error_msg());
            }
        }

        return $errors;
    }

    /**
     * Prepare data before calling set_data().
     *
     * This supports Moodle editor fields, which expect *_editor structures.
     *
     * @param \stdClass|array $data Raw data.
     * @param \context|null $context Editor context.
     * @return \stdClass
     */
    public static function prepare_data_for_form($data, ?\context $context = null): \stdClass {
        $data = (object)$data;
        $context = $context ?? \context_system::instance();

        $data->description_editor = [
            'text' => $data->description ?? '',
            'format' => $data->descriptionformat ?? FORMAT_HTML,
            'itemid' => 0,
        ];

        $data->outcomes_editor = [
            'text' => $data->outcomes ?? '',
            'format' => $data->outcomesformat ?? FORMAT_HTML,
            'itemid' => 0,
        ];

        $data->recognition_editor = [
            'text' => $data->recognition ?? '',
            'format' => $data->recognitionformat ?? FORMAT_HTML,
            'itemid' => 0,
        ];

        if (!isset($data->metadata) || $data->metadata === null) {
            $data->metadata = local_uckk_json_encode([]);
        }

        return $data;
    }

    /**
     * Extract clean program data from submitted form data.
     *
     * @param \stdClass $formdata Raw form data.
     * @return \stdClass
     */
    public static function extract_program_data(\stdClass $formdata): \stdClass {
        $program = new \stdClass();

        if (!empty($formdata->id)) {
            $program->id = (int)$formdata->id;
        }

        $program->fullname = trim((string)$formdata->fullname);
        $program->shortname = local_uckk_normalise_key((string)$formdata->shortname);
        $program->idnumber = trim((string)$formdata->idnumber);
        $program->programtype = local_uckk_normalise_key((string)$formdata->programtype);
        $program->status = local_uckk_normalise_key((string)$formdata->status);
        $program->visibility = local_uckk_normalise_key((string)$formdata->visibility);
        $program->categoryid = isset($formdata->categoryid) ? (int)$formdata->categoryid : 0;
        $program->cohortidnumber = trim((string)($formdata->cohortidnumber ?? ''));
        $program->requiredcourses = self::normalise_csv((string)($formdata->requiredcourses ?? ''));
        $program->requiredcompetencies = self::normalise_csv((string)($formdata->requiredcompetencies ?? ''));
        $program->requiredbadges = self::normalise_csv((string)($formdata->requiredbadges ?? ''));
        $program->internalrecognition = !empty($formdata->internalrecognition) ? 1 : 0;
        $program->requiresportfolio = !empty($formdata->requiresportfolio) ? 1 : 0;
        $program->requiresarchive = !empty($formdata->requiresarchive) ? 1 : 0;
        $program->requiresintegrityreview = !empty($formdata->requiresintegrityreview) ? 1 : 0;
        $program->limitsnotice = trim((string)($formdata->limitsnotice ?? ''));
        $program->sortorder = isset($formdata->sortorder) ? (int)$formdata->sortorder : 100;
        $program->color = trim((string)($formdata->color ?? ''));
        $program->icon = local_uckk_normalise_key((string)($formdata->icon ?? 'program'));

        $program->description = $formdata->description_editor['text'] ?? '';
        $program->descriptionformat = $formdata->description_editor['format'] ?? FORMAT_HTML;

        $program->outcomes = $formdata->outcomes_editor['text'] ?? '';
        $program->outcomesformat = $formdata->outcomes_editor['format'] ?? FORMAT_HTML;

        $program->recognition = $formdata->recognition_editor['text'] ?? '';
        $program->recognitionformat = $formdata->recognition_editor['format'] ?? FORMAT_HTML;

        $metadata = trim((string)($formdata->metadata ?? ''));
        $program->metadata = $metadata !== '' ? local_uckk_json_encode(json_decode($metadata, true)) : local_uckk_json_encode([]);

        return $program;
    }

    /**
     * Return program type options.
     *
     * @return array<string, string>
     */
    public static function get_program_type_options(): array {
        return [
            self::TYPE_TRONC_COMMUN => get_string('program:type_tronccommun', 'local_uckk'),
            self::TYPE_BACCALAUREAT => get_string('program:type_baccalaureat', 'local_uckk'),
            self::TYPE_MINEURE => get_string('program:type_mineure', 'local_uckk'),
            self::TYPE_CERTIFICAT => get_string('program:type_certificat', 'local_uckk'),
            self::TYPE_SEMINAIRE => get_string('program:type_seminaire', 'local_uckk'),
            self::TYPE_LABORATOIRE => get_string('program:type_laboratoire', 'local_uckk'),
        ];
    }

    /**
     * Return status options.
     *
     * @return array<string, string>
     */
    public static function get_status_options(): array {
        return [
            self::STATUS_DRAFT => get_string('status_draft', 'local_uckk'),
            self::STATUS_ACTIVE => get_string('status_active', 'local_uckk'),
            self::STATUS_HIDDEN => get_string('status_hidden', 'local_uckk'),
            self::STATUS_ARCHIVED => get_string('status_archived', 'local_uckk'),
        ];
    }

    /**
     * Return visibility options.
     *
     * @return array<string, string>
     */
    public static function get_visibility_options(): array {
        return [
            self::VISIBILITY_PRIVATE => get_string('visibility_private', 'local_uckk'),
            self::VISIBILITY_INSTITUTION => get_string('visibility_institution', 'local_uckk'),
            self::VISIBILITY_PUBLIC => get_string('visibility_public', 'local_uckk'),
        ];
    }

    /**
     * Return editor options.
     *
     * @param \context $context Editor context.
     * @return array<string, mixed>
     */
    private static function get_editor_options(\context $context): array {
        return [
            'trusttext' => false,
            'subdirs' => false,
            'maxfiles' => 0,
            'maxbytes' => 0,
            'context' => $context,
            'noclean' => false,
            'enable_filemanagement' => false,
        ];
    }

    /**
     * Return course category options.
     *
     * @return array<int, string>
     */
    private static function get_course_category_options(): array {
        $options = [
            0 => get_string('program:category_none', 'local_uckk'),
        ];

        if (class_exists('\core_course_category')) {
            $categories = \core_course_category::make_categories_list();

            foreach ($categories as $id => $name) {
                $options[(int)$id] = $name;
            }
        }

        return $options;
    }

    /**
     * Validate a comma-separated list field.
     *
     * @param array $data Submitted data.
     * @param array $errors Current errors.
     * @param string $field Field name.
     * @param string $pattern Validation regex.
     * @return void
     */
    private function validate_csv_field(array $data, array &$errors, string $field, string $pattern): void {
        if (empty($data[$field])) {
            return;
        }

        foreach (self::split_csv((string)$data[$field]) as $item) {
            if (preg_match($pattern, $item) !== 1) {
                $errors[$field] = get_string('program:error_csvfield', 'local_uckk', $item);
                return;
            }
        }
    }

    /**
     * Split comma-separated values.
     *
     * @param string $value Raw value.
     * @return string[]
     */
    private static function split_csv(string $value): array {
        $items = array_map('trim', explode(',', $value));
        $items = array_filter($items, static function(string $item): bool {
            return $item !== '';
        });

        return array_values($items);
    }

    /**
     * Normalise a comma-separated value list.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function normalise_csv(string $value): string {
        return implode(',', self::split_csv($value));
    }
}