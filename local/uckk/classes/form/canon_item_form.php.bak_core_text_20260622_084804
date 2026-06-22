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
 * Form for creating or editing a UCKK canon item.
 *
 * A canon item is an institutional knowledge object: a rule, principle,
 * definition, doctrine, program reference, governance text, symbolic boundary,
 * methodological statement or reusable Kristal-like item.
 *
 * This form collects structured data only. It must not itself publish canon,
 * validate institutional legitimacy, create assembly decisions, resolve
 * integrity cases or write archive records. Those actions belong to services,
 * events, Assemblées, Archives and Inquisiteur workflows.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Canon item form.
 *
 * Expected custom data:
 *
 * ```php
 * [
 *     'contextid' => $context->id,
 *     'itemtypes' => [
 *         'principle' => get_string('canon_type_principle', 'local_uckk'),
 *         'rule' => get_string('canon_type_rule', 'local_uckk'),
 *     ],
 *     'statuses' => [...],
 *     'visibilities' => [...],
 *     'parents' => [...],
 *     'editoroptions' => [...],
 *     'isnew' => true,
 * ]
 * ```
 *
 * @package local_uckk
 */
final class canon_item_form extends \moodleform {
    /** Default visibility for new canon items. */
    private const DEFAULT_VISIBILITY = 'institution';

    /** Default status for new canon items. */
    private const DEFAULT_STATUS = 'draft';

    /** Maximum item key length. */
    private const MAX_ITEMKEY_LENGTH = 100;

    /** Maximum title length. */
    private const MAX_TITLE_LENGTH = 255;

    /** Maximum source text length. */
    private const MAX_SOURCE_TEXT_LENGTH = 500;

    /**
     * Define the Moodle form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $customdata = $this->_customdata ?? [];
        $isnew = !empty($customdata['isnew']);
        $contextid = isset($customdata['contextid']) ? (int)$customdata['contextid'] : 0;

        $editoroptions = $customdata['editoroptions'] ?? self::get_default_editor_options();
        $itemtypes = $customdata['itemtypes'] ?? self::get_default_item_types();
        $statuses = $customdata['statuses'] ?? self::get_default_statuses();
        $visibilities = $customdata['visibilities'] ?? self::get_default_visibilities();
        $parents = $customdata['parents'] ?? [];

        // ---------------------------------------------------------------------
        // Hidden identity fields.
        // ---------------------------------------------------------------------

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);
        $mform->setDefault('contextid', $contextid);

        $mform->addElement('hidden', 'versionno');
        $mform->setType('versionno', PARAM_INT);
        $mform->setDefault('versionno', 1);

        // ---------------------------------------------------------------------
        // Canon identity.
        // ---------------------------------------------------------------------

        $mform->addElement(
            'header',
            'identityhdr',
            get_string('canon_form_identity', 'local_uckk')
        );

        $mform->addElement(
            'text',
            'itemkey',
            get_string('canon_itemkey', 'local_uckk'),
            ['maxlength' => self::MAX_ITEMKEY_LENGTH, 'size' => 48]
        );
        $mform->setType('itemkey', PARAM_ALPHANUMEXT);
        $mform->addRule('itemkey', get_string('required'), 'required', null, 'client');
        $mform->addRule(
            'itemkey',
            get_string('maximumchars', '', self::MAX_ITEMKEY_LENGTH),
            'maxlength',
            self::MAX_ITEMKEY_LENGTH,
            'client'
        );
        $mform->addHelpButton('itemkey', 'canon_itemkey', 'local_uckk');

        $mform->addElement(
            'select',
            'itemtype',
            get_string('canon_itemtype', 'local_uckk'),
            $itemtypes
        );
        $mform->setType('itemtype', PARAM_ALPHANUMEXT);
        $mform->setDefault('itemtype', 'principle');
        $mform->addRule('itemtype', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('itemtype', 'canon_itemtype', 'local_uckk');

        $mform->addElement(
            'text',
            'title',
            get_string('canon_title', 'local_uckk'),
            ['maxlength' => self::MAX_TITLE_LENGTH, 'size' => 80]
        );
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', null, 'client');
        $mform->addRule(
            'title',
            get_string('maximumchars', '', self::MAX_TITLE_LENGTH),
            'maxlength',
            self::MAX_TITLE_LENGTH,
            'client'
        );

        $mform->addElement(
            'textarea',
            'summary',
            get_string('canon_summary', 'local_uckk'),
            ['rows' => 4, 'cols' => 80]
        );
        $mform->setType('summary', PARAM_TEXT);
        $mform->addHelpButton('summary', 'canon_summary', 'local_uckk');

        if (!empty($parents)) {
            $mform->addElement(
                'select',
                'parentid',
                get_string('canon_parentid', 'local_uckk'),
                [0 => get_string('none')] + $parents
            );
            $mform->setType('parentid', PARAM_INT);
            $mform->setDefault('parentid', 0);
            $mform->addHelpButton('parentid', 'canon_parentid', 'local_uckk');
        } else {
            $mform->addElement('hidden', 'parentid');
            $mform->setType('parentid', PARAM_INT);
            $mform->setDefault('parentid', 0);
        }

        // ---------------------------------------------------------------------
        // Canon content.
        // ---------------------------------------------------------------------

        $mform->addElement(
            'header',
            'contenthdr',
            get_string('canon_form_content', 'local_uckk')
        );

        $mform->addElement(
            'editor',
            'content_editor',
            get_string('canon_content', 'local_uckk'),
            null,
            $editoroptions
        );
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('content_editor', 'canon_content', 'local_uckk');

        $mform->addElement(
            'textarea',
            'limits',
            get_string('canon_limits', 'local_uckk'),
            ['rows' => 4, 'cols' => 80]
        );
        $mform->setType('limits', PARAM_TEXT);
        $mform->addHelpButton('limits', 'canon_limits', 'local_uckk');

        $mform->addElement(
            'textarea',
            'interpretationnotes',
            get_string('canon_interpretationnotes', 'local_uckk'),
            ['rows' => 4, 'cols' => 80]
        );
        $mform->setType('interpretationnotes', PARAM_TEXT);
        $mform->addHelpButton('interpretationnotes', 'canon_interpretationnotes', 'local_uckk');

        // ---------------------------------------------------------------------
        // Status, visibility and governance.
        // ---------------------------------------------------------------------

        $mform->addElement(
            'header',
            'governancehdr',
            get_string('canon_form_governance', 'local_uckk')
        );

        $mform->addElement(
            'select',
            'status',
            get_string('status', 'local_uckk'),
            $statuses
        );
        $mform->setType('status', PARAM_ALPHANUMEXT);
        $mform->setDefault('status', self::DEFAULT_STATUS);
        $mform->addRule('status', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'select',
            'visibility',
            get_string('visibility', 'local_uckk'),
            $visibilities
        );
        $mform->setType('visibility', PARAM_ALPHANUMEXT);
        $mform->setDefault('visibility', self::DEFAULT_VISIBILITY);
        $mform->addRule('visibility', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'advcheckbox',
            'requiresassembly',
            get_string('canon_requiresassembly', 'local_uckk'),
            get_string('canon_requiresassembly_label', 'local_uckk')
        );
        $mform->setType('requiresassembly', PARAM_BOOL);
        $mform->setDefault('requiresassembly', 0);
        $mform->addHelpButton('requiresassembly', 'canon_requiresassembly', 'local_uckk');

        $mform->addElement(
            'advcheckbox',
            'requiresintegrityreview',
            get_string('canon_requiresintegrityreview', 'local_uckk'),
            get_string('canon_requiresintegrityreview_label', 'local_uckk')
        );
        $mform->setType('requiresintegrityreview', PARAM_BOOL);
        $mform->setDefault('requiresintegrityreview', 0);
        $mform->addHelpButton('requiresintegrityreview', 'canon_requiresintegrityreview', 'local_uckk');

        $mform->addElement(
            'advcheckbox',
            'archiveonpublish',
            get_string('canon_archiveonpublish', 'local_uckk'),
            get_string('canon_archiveonpublish_label', 'local_uckk')
        );
        $mform->setType('archiveonpublish', PARAM_BOOL);
        $mform->setDefault('archiveonpublish', 1);
        $mform->addHelpButton('archiveonpublish', 'canon_archiveonpublish', 'local_uckk');

        $mform->addElement(
            'advcheckbox',
            'contestable',
            get_string('canon_contestable', 'local_uckk'),
            get_string('canon_contestable_label', 'local_uckk')
        );
        $mform->setType('contestable', PARAM_BOOL);
        $mform->setDefault('contestable', 1);
        $mform->addHelpButton('contestable', 'canon_contestable', 'local_uckk');

        // ---------------------------------------------------------------------
        // Provenance.
        // ---------------------------------------------------------------------

        $mform->addElement(
            'header',
            'provenancehdr',
            get_string('canon_form_provenance', 'local_uckk')
        );

        $mform->addElement(
            'text',
            'sourcecomponent',
            get_string('canon_sourcecomponent', 'local_uckk'),
            ['maxlength' => 100, 'size' => 48]
        );
        $mform->setType('sourcecomponent', PARAM_COMPONENT);
        $mform->setDefault('sourcecomponent', 'local_uckk');
        $mform->addHelpButton('sourcecomponent', 'canon_sourcecomponent', 'local_uckk');

        $mform->addElement(
            'text',
            'sourceid',
            get_string('canon_sourceid', 'local_uckk'),
            ['maxlength' => 20, 'size' => 12]
        );
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', 0);

        $mform->addElement(
            'text',
            'sourcetext',
            get_string('canon_sourcetext', 'local_uckk'),
            ['maxlength' => self::MAX_SOURCE_TEXT_LENGTH, 'size' => 80]
        );
        $mform->setType('sourcetext', PARAM_TEXT);
        $mform->addRule(
            'sourcetext',
            get_string('maximumchars', '', self::MAX_SOURCE_TEXT_LENGTH),
            'maxlength',
            self::MAX_SOURCE_TEXT_LENGTH,
            'client'
        );
        $mform->addHelpButton('sourcetext', 'canon_sourcetext', 'local_uckk');

        $mform->addElement(
            'text',
            'sourceurl',
            get_string('canon_sourceurl', 'local_uckk'),
            ['maxlength' => 2048, 'size' => 80]
        );
        $mform->setType('sourceurl', PARAM_URL);
        $mform->addHelpButton('sourceurl', 'canon_sourceurl', 'local_uckk');

        $mform->addElement(
            'textarea',
            'evidencenote',
            get_string('canon_evidencenote', 'local_uckk'),
            ['rows' => 4, 'cols' => 80]
        );
        $mform->setType('evidencenote', PARAM_TEXT);
        $mform->addHelpButton('evidencenote', 'canon_evidencenote', 'local_uckk');

        // ---------------------------------------------------------------------
        // Versioning.
        // ---------------------------------------------------------------------

        $mform->addElement(
            'header',
            'versionhdr',
            get_string('canon_form_versioning', 'local_uckk')
        );

        $mform->addElement(
            'textarea',
            'changereason',
            get_string('canon_changereason', 'local_uckk'),
            ['rows' => 4, 'cols' => 80]
        );
        $mform->setType('changereason', PARAM_TEXT);
        $mform->addHelpButton('changereason', 'canon_changereason', 'local_uckk');

        if (!$isnew) {
            $mform->addRule('changereason', get_string('required'), 'required', null, 'client');
        }

        $mform->addElement(
            'advcheckbox',
            'minorchange',
            get_string('canon_minorchange', 'local_uckk'),
            get_string('canon_minorchange_label', 'local_uckk')
        );
        $mform->setType('minorchange', PARAM_BOOL);
        $mform->setDefault('minorchange', 0);

        // ---------------------------------------------------------------------
        // Metadata.
        // ---------------------------------------------------------------------

        $mform->addElement(
            'header',
            'metadatahdr',
            get_string('canon_form_metadata', 'local_uckk')
        );

        $mform->addElement(
            'textarea',
            'metadatajson',
            get_string('canon_metadatajson', 'local_uckk'),
            ['rows' => 6, 'cols' => 80]
        );
        $mform->setType('metadatajson', PARAM_RAW_TRIMMED);
        $mform->setDefault('metadatajson', '{}');
        $mform->addHelpButton('metadatajson', 'canon_metadatajson', 'local_uckk');

        $this->add_action_buttons(true, $isnew
            ? get_string('canon_createitem', 'local_uckk')
            : get_string('canon_saveitem', 'local_uckk')
        );
    }

    /**
     * Validate submitted form data.
     *
     * @param array<string, mixed> $data Submitted data.
     * @param array<string, mixed> $files Submitted files.
     * @return array<string, string> Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $itemkey = trim((string)($data['itemkey'] ?? ''));
        $itemtype = trim((string)($data['itemtype'] ?? ''));
        $title = trim((string)($data['title'] ?? ''));
        $status = trim((string)($data['status'] ?? ''));
        $visibility = trim((string)($data['visibility'] ?? ''));
        $sourceurl = trim((string)($data['sourceurl'] ?? ''));
        $metadatajson = trim((string)($data['metadatajson'] ?? ''));

        if ($itemkey === '') {
            $errors['itemkey'] = get_string('required');
        } else if (!preg_match('/^[a-zA-Z0-9_-]+$/', $itemkey)) {
            $errors['itemkey'] = get_string('canon_itemkey_invalid', 'local_uckk');
        } else if (strlen($itemkey) > self::MAX_ITEMKEY_LENGTH) {
            $errors['itemkey'] = get_string('maximumchars', '', self::MAX_ITEMKEY_LENGTH);
        }

        if ($itemtype === '') {
            $errors['itemtype'] = get_string('required');
        } else if (!array_key_exists($itemtype, $this->get_item_type_options())) {
            $errors['itemtype'] = get_string('canon_itemtype_invalid', 'local_uckk');
        }

        if ($title === '') {
            $errors['title'] = get_string('required');
        } else if (core_text::strlen($title) > self::MAX_TITLE_LENGTH) {
            $errors['title'] = get_string('maximumchars', '', self::MAX_TITLE_LENGTH);
        }

        if ($status === '') {
            $errors['status'] = get_string('required');
        } else if (!array_key_exists($status, $this->get_status_options())) {
            $errors['status'] = get_string('canon_status_invalid', 'local_uckk');
        }

        if ($visibility === '') {
            $errors['visibility'] = get_string('required');
        } else if (!array_key_exists($visibility, $this->get_visibility_options())) {
            $errors['visibility'] = get_string('canon_visibility_invalid', 'local_uckk');
        }

        if ($sourceurl !== '' && !filter_var($sourceurl, FILTER_VALIDATE_URL)) {
            $errors['sourceurl'] = get_string('invalidurl');
        }

        if (!empty($data['requiresintegrityreview']) && $visibility === 'public') {
            $errors['visibility'] = get_string('canon_public_integrity_conflict', 'local_uckk');
        }

        if ($status === 'published' && empty($data['archiveonpublish'])) {
            $errors['archiveonpublish'] = get_string('canon_published_requires_archive', 'local_uckk');
        }

        if ($status === 'published' && empty($data['sourcetext']) && empty($data['sourceurl'])) {
            $errors['sourcetext'] = get_string('canon_published_requires_source', 'local_uckk');
        }

        if ($metadatajson !== '') {
            json_decode($metadatajson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['metadatajson'] = get_string('canon_metadatajson_invalid', 'local_uckk');
            }
        }

        $content = $data['content_editor']['text'] ?? '';
        if (trim((string)$content) === '') {
            $errors['content_editor'] = get_string('required');
        }

        return $errors;
    }

    /**
     * Return normalized data for service-layer storage.
     *
     * This method is optional for page controllers. It helps keep controllers
     * small by translating editor data and JSON metadata into predictable keys.
     *
     * @return object|null
     */
    public function get_normalized_data(): ?object {
        $data = $this->get_data();

        if (!$data) {
            return null;
        }

        $data->content = $data->content_editor['text'] ?? '';
        $data->contentformat = $data->content_editor['format'] ?? FORMAT_HTML;
        $data->contentitemid = $data->content_editor['itemid'] ?? 0;

        $metadata = [];
        if (!empty($data->metadatajson)) {
            $decoded = json_decode($data->metadatajson, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        $metadata['requiresassembly'] = !empty($data->requiresassembly);
        $metadata['requiresintegrityreview'] = !empty($data->requiresintegrityreview);
        $metadata['archiveonpublish'] = !empty($data->archiveonpublish);
        $metadata['contestable'] = !empty($data->contestable);
        $metadata['minorchange'] = !empty($data->minorchange);
        $metadata['limits'] = $data->limits ?? '';
        $metadata['interpretationnotes'] = $data->interpretationnotes ?? '';
        $metadata['evidencenote'] = $data->evidencenote ?? '';

        $data->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $data;
    }

    /**
     * Get item type options from custom data or defaults.
     *
     * @return array<string, string>
     */
    private function get_item_type_options(): array {
        return $this->_customdata['itemtypes'] ?? self::get_default_item_types();
    }

    /**
     * Get status options from custom data or defaults.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return $this->_customdata['statuses'] ?? self::get_default_statuses();
    }

    /**
     * Get visibility options from custom data or defaults.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        return $this->_customdata['visibilities'] ?? self::get_default_visibilities();
    }

    /**
     * Default editor options.
     *
     * @return array<string, mixed>
     */
    private static function get_default_editor_options(): array {
        return [
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'changeformat' => 1,
            'context' => \context_system::instance(),
            'noclean' => 0,
            'trusttext' => 0,
        ];
    }

    /**
     * Default canon item types.
     *
     * @return array<string, string>
     */
    private static function get_default_item_types(): array {
        return [
            'principle' => get_string('canon_type_principle', 'local_uckk'),
            'rule' => get_string('canon_type_rule', 'local_uckk'),
            'definition' => get_string('canon_type_definition', 'local_uckk'),
            'method' => get_string('canon_type_method', 'local_uckk'),
            'program' => get_string('canon_type_program', 'local_uckk'),
            'course_reference' => get_string('canon_type_course_reference', 'local_uckk'),
            'governance' => get_string('canon_type_governance', 'local_uckk'),
            'symbolic_boundary' => get_string('canon_type_symbolic_boundary', 'local_uckk'),
            'integrity_policy' => get_string('canon_type_integrity_policy', 'local_uckk'),
            'archive_policy' => get_string('canon_type_archive_policy', 'local_uckk'),
            'kristal' => get_string('canon_type_kristal', 'local_uckk'),
        ];
    }

    /**
     * Default status options.
     *
     * @return array<string, string>
     */
    private static function get_default_statuses(): array {
        return [
            'draft' => get_string('status_draft', 'local_uckk'),
            'proposed' => get_string('status_proposed', 'local_uckk'),
            'under_review' => get_string('status_underreview', 'local_uckk'),
            'published' => get_string('status_published', 'local_uckk'),
            'contested' => get_string('status_contested', 'local_uckk'),
            'superseded' => get_string('status_superseded', 'local_uckk'),
            'archived' => get_string('status_archived', 'local_uckk'),
            'invalidated' => get_string('status_invalidated', 'local_uckk'),
        ];
    }

    /**
     * Default visibility options.
     *
     * @return array<string, string>
     */
    private static function get_default_visibilities(): array {
        return [
            'private' => get_string('visibility_private', 'local_uckk'),
            'course' => get_string('visibility_course', 'local_uckk'),
            'cohort' => get_string('visibility_cohort', 'local_uckk'),
            'institution' => get_string('visibility_institution', 'local_uckk'),
            'public' => get_string('visibility_public', 'local_uckk'),
            'restricted' => get_string('visibility_restricted', 'local_uckk'),
        ];
    }
}