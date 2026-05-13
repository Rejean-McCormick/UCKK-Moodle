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
 * This form captures the canonical fields of a UCKK program:
 *
 * - shortname;
 * - idnumber;
 * - fullname;
 * - program type;
 * - linked Moodle category;
 * - description / summary;
 * - status;
 * - visibility;
 * - sort order;
 * - metadata.
 *
 * It must not save records directly, create Moodle categories, create courses,
 * seed pathways, award badges, or publish recognitions. Those responsibilities
 * belong to APIs, seed tools, services and scheduled tasks.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\form;

use core_course_category;
use local_uckk\api\program_api;
use local_uckk\local\visibility;
use moodleform;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * UCKK program editing form.
 *
 * Expected customdata:
 *
 * [
 *     'currentid' => 0,
 *     'returnurl' => '',
 *     'caneditmetadata' => true,
 *     'allowcategorylink' => true,
 * ]
 *
 * @package local_uckk
 */
final class program_form extends moodleform {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Program table. */
    private const TABLE = 'local_uckk_program';

    /** Program type: tronc commun. */
    private const TYPE_TRONC_COMMUN = 'tronc_commun';

    /** Program type: baccalauréat. */
    private const TYPE_BACCALAUREAT = 'baccalaureat';

    /** Program type: mineure. */
    private const TYPE_MINEURE = 'mineure';

    /** Program type: séminaire. */
    private const TYPE_SEMINAIRE = 'seminaire';

    /** Program type: laboratoire. */
    private const TYPE_LABORATOIRE = 'laboratoire';

    /** Status: draft. */
    private const STATUS_DRAFT = 'draft';

    /** Status: active. */
    private const STATUS_ACTIVE = 'active';

    /** Status: hidden. */
    private const STATUS_HIDDEN = 'hidden';

    /** Status: archived. */
    private const STATUS_ARCHIVED = 'archived';

    /**
     * Define the form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $customdata = $this->_customdata ?? [];

        $currentid = (int)($customdata['currentid'] ?? 0);
        $returnurl = (string)($customdata['returnurl'] ?? '');
        $caneditmetadata = (bool)($customdata['caneditmetadata'] ?? true);
        $allowcategorylink = (bool)($customdata['allowcategorylink'] ?? true);

        $mform->addElement('hidden', 'id', $currentid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'returnurl', $returnurl);
        $mform->setType('returnurl', PARAM_LOCALURL);

        $this->add_identity_section($mform);
        $this->add_classification_section($mform, $allowcategorylink);
        $this->add_description_section($mform);
        $this->add_publication_section($mform);

        if ($caneditmetadata) {
            $this->add_metadata_section($mform);
        }

        $this->add_action_buttons(true, self::get_component_string('saveprogram', 'Save program'));
    }

    /**
     * Add identity fields.
     *
     * @param \MoodleQuickForm $mform Moodle form.
     * @return void
     */
    private function add_identity_section(\MoodleQuickForm $mform): void {
        $mform->addElement(
            'header',
            'identityhdr',
            self::get_component_string('program_identity', 'Program identity')
        );

        $mform->addElement(
            'text',
            'fullname',
            self::get_component_string('program_fullname', 'Program full name'),
            ['size' => 64, 'maxlength' => 255]
        );
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->addRule('fullname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement(
            'text',
            'shortname',
            self::get_component_string('program_shortname', 'Stable shortname'),
            ['size' => 40, 'maxlength' => 100]
        );
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->addRule('shortname', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $this->add_help_button_if_exists($mform, 'shortname', 'program_shortname');

        $mform->addElement(
            'text',
            'idnumber',
            self::get_component_string('program_idnumber', 'ID number'),
            ['size' => 40, 'maxlength' => 100]
        );
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addRule('idnumber', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $this->add_help_button_if_exists($mform, 'idnumber', 'program_idnumber');

        $mform->addElement(
            'static',
            'internalnotice',
            self::get_component_string('internalrecognitionnotice_title', 'Internal UCKK recognition'),
            self::get_component_string(
                'program_internal_notice',
                'UCKK programs are internal learning structures. They must not be presented as public accredited degrees unless officially recognised in the future.'
            )
        );
    }

    /**
     * Add classification fields.
     *
     * @param \MoodleQuickForm $mform Moodle form.
     * @param bool $allowcategorylink Whether category linking is enabled.
     * @return void
     */
    private function add_classification_section(\MoodleQuickForm $mform, bool $allowcategorylink): void {
        $mform->addElement(
            'header',
            'classificationhdr',
            self::get_component_string('program_classification', 'Program classification')
        );

        $mform->addElement(
            'select',
            'programtype',
            self::get_component_string('program_type', 'Program type'),
            self::program_type_options()
        );
        $mform->setType('programtype', PARAM_ALPHANUMEXT);
        $mform->setDefault('programtype', self::TYPE_BACCALAUREAT);
        $mform->addRule('programtype', get_string('required'), 'required', null, 'client');

        if ($allowcategorylink) {
            $mform->addElement(
                'select',
                'categoryid',
                self::get_component_string('program_categoryid', 'Linked Moodle category'),
                self::category_options()
            );
            $mform->setType('categoryid', PARAM_INT);
            $mform->setDefault('categoryid', 0);
            $this->add_help_button_if_exists($mform, 'categoryid', 'program_categoryid');
        } else {
            $mform->addElement('hidden', 'categoryid', 0);
            $mform->setType('categoryid', PARAM_INT);
        }

        $mform->addElement(
            'text',
            'sortorder',
            self::get_component_string('sortorder', 'Sort order'),
            ['size' => 8, 'maxlength' => 10]
        );
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);
        $mform->addRule('sortorder', null, 'numeric', null, 'client');
    }

    /**
     * Add description fields.
     *
     * @param \MoodleQuickForm $mform Moodle form.
     * @return void
     */
    private function add_description_section(\MoodleQuickForm $mform): void {
        $mform->addElement(
            'header',
            'descriptionhdr',
            self::get_component_string('program_description', 'Program description')
        );

        $mform->addElement(
            'editor',
            'summary_editor',
            self::get_component_string('program_summary', 'Program summary'),
            null,
            self::editor_options()
        );
        $mform->setType('summary_editor', PARAM_RAW);
        $this->add_help_button_if_exists($mform, 'summary_editor', 'program_summary');

        $mform->addElement(
            'textarea',
            'recognitionlabel',
            self::get_component_string('program_recognitionlabel', 'Recognition label'),
            [
                'rows' => 2,
                'cols' => 64,
                'maxlength' => 255,
            ]
        );
        $mform->setType('recognitionlabel', PARAM_TEXT);
        $mform->addRule('recognitionlabel', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setDefault(
            'recognitionlabel',
            self::get_component_string('program_default_recognitionlabel', 'Internal UCKK recognition')
        );
    }

    /**
     * Add publication fields.
     *
     * @param \MoodleQuickForm $mform Moodle form.
     * @return void
     */
    private function add_publication_section(\MoodleQuickForm $mform): void {
        $mform->addElement(
            'header',
            'publicationhdr',
            self::get_component_string('program_publication', 'Status and visibility')
        );

        $mform->addElement(
            'select',
            'status',
            self::get_component_string('status', 'Status'),
            self::status_options()
        );
        $mform->setType('status', PARAM_ALPHANUMEXT);
        $mform->setDefault('status', self::STATUS_DRAFT);
        $mform->addRule('status', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'select',
            'visibility',
            self::get_component_string('visibility', 'Visibility'),
            self::visibility_options()
        );
        $mform->setType('visibility', PARAM_ALPHANUMEXT);
        $mform->setDefault('visibility', 'institution');
        $mform->addRule('visibility', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'advcheckbox',
            'confirminternal',
            self::get_component_string('program_confirminternal', 'Confirm internal recognition notice'),
            self::get_component_string(
                'program_confirminternal_desc',
                'I confirm that this program is an internal UCKK learning structure and not a public accredited degree.'
            )
        );
        $mform->setType('confirminternal', PARAM_BOOL);
        $mform->addRule('confirminternal', get_string('required'), 'required', null, 'client');
    }

    /**
     * Add metadata fields.
     *
     * @param \MoodleQuickForm $mform Moodle form.
     * @return void
     */
    private function add_metadata_section(\MoodleQuickForm $mform): void {
        $mform->addElement(
            'header',
            'metadatahdr',
            self::get_component_string('metadata', 'Metadata')
        );
        $mform->setExpanded('metadatahdr', false);

        $mform->addElement(
            'textarea',
            'metadata',
            self::get_component_string('metadata_json', 'Metadata JSON'),
            [
                'rows' => 8,
                'cols' => 80,
                'spellcheck' => 'false',
            ]
        );
        $mform->setType('metadata', PARAM_RAW);
        $mform->setDefault('metadata', '{}');
        $this->add_help_button_if_exists($mform, 'metadata', 'metadata_json');
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);

        $currentid = (int)($data['id'] ?? 0);
        $shortname = self::normalise_shortname($data['shortname'] ?? '');
        $idnumber = trim((string)($data['idnumber'] ?? ''));
        $programtype = (string)($data['programtype'] ?? '');
        $status = (string)($data['status'] ?? '');
        $visibilityvalue = (string)($data['visibility'] ?? '');
        $categoryid = (int)($data['categoryid'] ?? 0);
        $sortorder = (int)($data['sortorder'] ?? 0);
        $metadata = trim((string)($data['metadata'] ?? ''));

        if ($shortname === '') {
            $errors['shortname'] = get_string('required');
        } else if (!preg_match('/^[a-z0-9_]+$/', $shortname)) {
            $errors['shortname'] = self::get_component_string(
                'program_shortname_invalid',
                'Use lowercase letters, numbers and underscores only.'
            );
        } else if ($this->record_exists_for_other_id('shortname', $shortname, $currentid)) {
            $errors['shortname'] = self::get_component_string(
                'program_shortname_exists',
                'A program with this shortname already exists.'
            );
        }

        if ($idnumber !== '' && $this->record_exists_for_other_id('idnumber', $idnumber, $currentid)) {
            $errors['idnumber'] = self::get_component_string(
                'program_idnumber_exists',
                'A program with this ID number already exists.'
            );
        }

        if (!array_key_exists($programtype, self::program_type_options())) {
            $errors['programtype'] = self::get_component_string(
                'program_type_invalid',
                'Invalid program type.'
            );
        }

        if (!array_key_exists($status, self::status_options())) {
            $errors['status'] = self::get_component_string(
                'status_invalid',
                'Invalid status.'
            );
        }

        if (!array_key_exists($visibilityvalue, self::visibility_options())) {
            $errors['visibility'] = self::get_component_string(
                'visibility_invalid',
                'Invalid visibility.'
            );
        }

        if ($categoryid > 0 && !$DB->record_exists('course_categories', ['id' => $categoryid])) {
            $errors['categoryid'] = self::get_component_string(
                'program_category_missing',
                'The selected Moodle category does not exist.'
            );
        }

        if ($sortorder < 0) {
            $errors['sortorder'] = self::get_component_string(
                'sortorder_invalid',
                'Sort order must be zero or greater.'
            );
        }

        if ($metadata !== '') {
            json_decode($metadata);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['metadata'] = self::get_component_string(
                    'metadata_invalid_json',
                    'Metadata must be valid JSON.'
                );
            }
        }

        if (empty($data['confirminternal'])) {
            $errors['confirminternal'] = self::get_component_string(
                'program_confirminternal_required',
                'You must confirm the internal UCKK recognition notice.'
            );
        }

        return $errors;
    }

    /**
     * Prepare a record object for program_api from submitted form data.
     *
     * This helper does not save anything. It only normalises the editor field
     * into the summary fields expected by local_uckk APIs.
     *
     * @param stdClass $data Raw form data.
     * @return stdClass
     */
    public static function data_to_program_record(stdClass $data): stdClass {
        $record = new stdClass();

        if (!empty($data->id)) {
            $record->id = (int)$data->id;
        }

        $record->shortname = self::normalise_shortname($data->shortname ?? '');
        $record->idnumber = trim((string)($data->idnumber ?? ''));
        $record->fullname = trim((string)($data->fullname ?? ''));
        $record->programtype = (string)($data->programtype ?? self::TYPE_BACCALAUREAT);
        $record->categoryid = (int)($data->categoryid ?? 0);
        $record->status = (string)($data->status ?? self::STATUS_DRAFT);
        $record->visibility = (string)($data->visibility ?? 'institution');
        $record->sortorder = (int)($data->sortorder ?? 0);
        $record->recognitionlabel = trim((string)($data->recognitionlabel ?? ''));
        $record->metadata = trim((string)($data->metadata ?? '{}'));

        if (isset($data->summary_editor) && is_array($data->summary_editor)) {
            $record->summary = (string)($data->summary_editor['text'] ?? '');
            $record->summaryformat = (int)($data->summary_editor['format'] ?? FORMAT_HTML);
        } else {
            $record->summary = (string)($data->summary ?? '');
            $record->summaryformat = (int)($data->summaryformat ?? FORMAT_HTML);
        }

        // Keep description aliases for compatibility with the documented data model.
        $record->description = $record->summary;
        $record->descriptionformat = $record->summaryformat;

        return $record;
    }

    /**
     * Prepare default form data from a program record.
     *
     * @param stdClass $program Program record.
     * @return stdClass
     */
    public static function program_record_to_form_data(stdClass $program): stdClass {
        $data = clone $program;

        $summary = $program->summary ?? $program->description ?? '';
        $summaryformat = $program->summaryformat ?? $program->descriptionformat ?? FORMAT_HTML;

        $data->summary_editor = [
            'text' => $summary,
            'format' => $summaryformat,
        ];

        if (empty($data->metadata)) {
            $data->metadata = '{}';
        } else if (is_array($data->metadata) || is_object($data->metadata)) {
            $data->metadata = json_encode($data->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $data->confirminternal = 1;

        return $data;
    }

    /**
     * Return editor options for summary fields.
     *
     * @return array<string, mixed>
     */
    public static function editor_options(): array {
        return [
            'maxfiles' => 0,
            'trusttext' => false,
            'subdirs' => false,
            'noclean' => false,
            'context' => \context_system::instance(),
        ];
    }

    /**
     * Return program type options.
     *
     * @return array<string, string>
     */
    private static function program_type_options(): array {
        if (class_exists(program_api::class) && method_exists(program_api::class, 'get_allowed_types')) {
            $types = program_api::get_allowed_types();
        } else {
            $types = [
                self::TYPE_TRONC_COMMUN,
                self::TYPE_BACCALAUREAT,
                self::TYPE_MINEURE,
                self::TYPE_SEMINAIRE,
                self::TYPE_LABORATOIRE,
            ];
        }

        $options = [];

        foreach ($types as $type) {
            $options[$type] = self::label_from_component('programtype_', $type);
        }

        return $options;
    }

    /**
     * Return status options.
     *
     * @return array<string, string>
     */
    private static function status_options(): array {
        if (class_exists(program_api::class) && method_exists(program_api::class, 'get_allowed_statuses')) {
            $statuses = program_api::get_allowed_statuses();
        } else {
            $statuses = [
                self::STATUS_DRAFT,
                self::STATUS_ACTIVE,
                self::STATUS_HIDDEN,
                self::STATUS_ARCHIVED,
            ];
        }

        $options = [];

        foreach ($statuses as $status) {
            $options[$status] = self::label_from_component('status_', $status);
        }

        return $options;
    }

    /**
     * Return visibility options.
     *
     * @return array<string, string>
     */
    private static function visibility_options(): array {
        if (class_exists(visibility::class) && method_exists(visibility::class, 'catalogue_selectable')) {
            $values = visibility::catalogue_selectable();
        } else if (class_exists(program_api::class) && method_exists(program_api::class, 'get_allowed_visibilities')) {
            $values = program_api::get_allowed_visibilities();
        } else {
            $values = [
                'private',
                'institution',
                'public',
                'hidden',
                'archived',
            ];
        }

        $options = [];

        foreach ($values as $value) {
            if (class_exists(visibility::class) && method_exists(visibility::class, 'label')) {
                $options[$value] = visibility::label($value);
            } else {
                $options[$value] = self::label_from_component('visibility_', $value);
            }
        }

        return $options;
    }

    /**
     * Return category options.
     *
     * @return array<int, string>
     */
    private static function category_options(): array {
        $options = [
            0 => self::get_component_string('no_category_link', 'No linked Moodle category'),
        ];

        try {
            $categories = core_course_category::make_categories_list();
        } catch (\Throwable $exception) {
            $categories = [];
        }

        foreach ($categories as $id => $name) {
            $options[(int)$id] = format_string($name);
        }

        return $options;
    }

    /**
     * Determine whether a record exists for another id.
     *
     * @param string $field Field name.
     * @param string $value Field value.
     * @param int $currentid Current record id.
     * @return bool
     */
    private function record_exists_for_other_id(string $field, string $value, int $currentid): bool {
        global $DB;

        if ($value === '') {
            return false;
        }

        $record = $DB->get_record(self::TABLE, [$field => $value], 'id', IGNORE_MISSING);

        if (!$record) {
            return false;
        }

        return (int)$record->id !== $currentid;
    }

    /**
     * Add a help button only when the help string exists.
     *
     * @param \MoodleQuickForm $mform Moodle form.
     * @param string $element Element name.
     * @param string $identifier String identifier.
     * @return void
     */
    private function add_help_button_if_exists(\MoodleQuickForm $mform, string $element, string $identifier): void {
        if (get_string_manager()->string_exists($identifier . '_help', self::COMPONENT)) {
            $mform->addHelpButton($element, $identifier, self::COMPONENT);
        }
    }

    /**
     * Return a label from the component language pack.
     *
     * @param string $prefix String prefix.
     * @param string $key Value key.
     * @return string
     */
    private static function label_from_component(string $prefix, string $key): string {
        $identifier = $prefix . str_replace('-', '', $key);

        if (get_string_manager()->string_exists($identifier, self::COMPONENT)) {
            return get_string($identifier, self::COMPONENT);
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Get a component string with fallback.
     *
     * @param string $identifier String identifier.
     * @param string $fallback Fallback text.
     * @return string
     */
    private static function get_component_string(string $identifier, string $fallback): string {
        if (get_string_manager()->string_exists($identifier, self::COMPONENT)) {
            return get_string($identifier, self::COMPONENT);
        }

        return $fallback;
    }

    /**
     * Normalise a shortname.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function normalise_shortname($value): string {
        $value = trim((string)$value);
        $value = \core_text::strtolower($value);
        $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
        $value = clean_param($value, PARAM_ALPHANUMEXT);
        $value = preg_replace('/_+/', '_', $value) ?? '';

        return trim($value, '_');
    }
}