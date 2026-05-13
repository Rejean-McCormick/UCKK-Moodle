<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Archive item form for mod_uckkarchive.
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
use context_user;
use moodleform;
use stdClass;

/**
 * Form used to create or edit one archive item.
 *
 * This form collects archive metadata, content, proof files, provenance, source
 * data, visibility and validation state. It does not validate archive truth,
 * decide integrity outcomes, publish public records, revise history, or export
 * packages. Those actions belong to capability-checked controllers/services.
 */
final class archive_item_form extends moodleform {
    /** Default item type. */
    private const DEFAULT_ITEMTYPE = 'proof';

    /** Default status. */
    private const DEFAULT_STATUS = 'draft';

    /** Default visibility. */
    private const DEFAULT_VISIBILITY = 'course';

    /** Default provenance. */
    private const DEFAULT_PROVENANCE = 'human';

    /** Default validation state. */
    private const DEFAULT_VALIDATION = 'unverified';

    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $context = $this->get_context();

        $this->add_identity_section($mform);
        $this->add_content_section($mform, $context);
        $this->add_files_section($mform, $context);
        $this->add_source_section($mform);
        $this->add_governance_section($mform);
        $this->add_origin_section($mform);
        $this->add_metadata_section($mform);
        $this->add_hidden_fields($mform);

        $this->add_action_buttons(true, get_string('savearchiveitem', 'uckkarchive'));
    }

    /**
     * Add item identity fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_identity_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'identityheader', get_string('archiveitemidentity', 'uckkarchive'));

        $mform->addElement('text', 'title', get_string('archiveitemtitle', 'uckkarchive'), [
            'size' => 64,
            'maxlength' => 255,
        ]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('select', 'itemtype', get_string('archiveitemtype', 'uckkarchive'), $this->get_item_type_options());
        $mform->setDefault('itemtype', self::DEFAULT_ITEMTYPE);
        $mform->addRule('itemtype', null, 'required', null, 'client');
        $mform->addHelpButton('itemtype', 'archiveitemtype', 'uckkarchive');
    }

    /**
     * Add content editors.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param context_module $context Module context.
     */
    private function add_content_section(\MoodleQuickForm $mform, context_module $context): void {
        $mform->addElement('header', 'contentheader', get_string('archiveitemcontent', 'uckkarchive'));

        $mform->addElement(
            'editor',
            'content_editor',
            get_string('archiveitembody', 'uckkarchive'),
            null,
            $this->get_content_editor_options($context)
        );
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addHelpButton('content_editor', 'archiveitembody', 'uckkarchive');

        $mform->addElement(
            'editor',
            'publicsummary_editor',
            get_string('publicsummary', 'uckkarchive'),
            null,
            $this->get_public_summary_editor_options($context)
        );
        $mform->setType('publicsummary_editor', PARAM_RAW);
        $mform->addHelpButton('publicsummary_editor', 'publicsummary', 'uckkarchive');
    }

    /**
     * Add file manager fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param context_module $context Module context.
     */
    private function add_files_section(\MoodleQuickForm $mform, context_module $context): void {
        $mform->addElement('header', 'filesheader', get_string('archiveitemfiles', 'uckkarchive'));

        $mform->addElement(
            'filemanager',
            'itemfiles',
            get_string('archiveitemfiles', 'uckkarchive'),
            null,
            $this->get_filemanager_options($context)
        );
        $mform->setDefault('itemfiles', (int)($this->_customdata['draftitemid'] ?? 0));
        $mform->addHelpButton('itemfiles', 'archiveitemfiles', 'uckkarchive');

        $mform->addElement(
            'filemanager',
            'proof_files',
            get_string('proof_files', 'uckkarchive'),
            null,
            $this->get_filemanager_options($context)
        );
        $mform->setDefault('proof_files', (int)($this->_customdata['draftproofid'] ?? 0));
        $mform->addHelpButton('proof_files', 'proof_files', 'uckkarchive');
    }

    /**
     * Add source/provenance source fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_source_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'sourceheader', get_string('archivesource', 'uckkarchive'));

        $mform->addElement('url', 'sourceurl', get_string('sourceurl', 'uckkarchive'), [
            'size' => 80,
        ]);
        $mform->setType('sourceurl', PARAM_URL);
        $mform->addHelpButton('sourceurl', 'sourceurl', 'uckkarchive');

        $mform->addElement('text', 'sourcetitle', get_string('sourcetitle', 'uckkarchive'), [
            'size' => 64,
            'maxlength' => 255,
        ]);
        $mform->setType('sourcetitle', PARAM_TEXT);

        $mform->addElement('text', 'sourceauthor', get_string('sourceauthor', 'uckkarchive'), [
            'size' => 64,
            'maxlength' => 255,
        ]);
        $mform->setType('sourceauthor', PARAM_TEXT);
        $mform->addHelpButton('sourceauthor', 'sourceauthor', 'uckkarchive');

        $mform->addElement('date_time_selector', 'sourcedate', get_string('sourcedate', 'uckkarchive'), [
            'optional' => true,
        ]);
        $mform->setDefault('sourcedate', 0);
    }

    /**
     * Add status, visibility, provenance, and validation fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_governance_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'governanceheader', get_string('archivegovernance', 'uckkarchive'));

        $mform->addElement('select', 'status', get_string('status'), $this->get_status_options());
        $mform->setDefault('status', self::DEFAULT_STATUS);
        $mform->addRule('status', null, 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkarchive'), $this->get_visibility_options());
        $mform->setDefault('visibility', self::DEFAULT_VISIBILITY);
        $mform->addRule('visibility', null, 'required', null, 'client');
        $mform->addHelpButton('visibility', 'visibility', 'uckkarchive');

        $mform->addElement('select', 'provenance', get_string('provenance', 'uckkarchive'), $this->get_provenance_options());
        $mform->setDefault('provenance', self::DEFAULT_PROVENANCE);
        $mform->addRule('provenance', null, 'required', null, 'client');
        $mform->addHelpButton('provenance', 'provenance', 'uckkarchive');

        $mform->addElement('select', 'validationstate', get_string('validationstate', 'uckkarchive'), $this->get_validation_options());
        $mform->setDefault('validationstate', self::DEFAULT_VALIDATION);
        $mform->addRule('validationstate', null, 'required', null, 'client');
        $mform->addHelpButton('validationstate', 'validationstate', 'uckkarchive');

        $mform->addElement('textarea', 'reason', get_string('archivereason', 'uckkarchive'), [
            'rows' => 4,
            'cols' => 80,
            'maxlength' => 4000,
        ]);
        $mform->setType('reason', PARAM_TEXT);
        $mform->addHelpButton('reason', 'archivereason', 'uckkarchive');
    }

    /**
     * Add origin fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_origin_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'originheader', get_string('originrecord', 'uckkarchive'));

        $origincomponent = (string)($this->_customdata['origincomponent'] ?? '');
        $originarea = (string)($this->_customdata['originarea'] ?? '');
        $originid = (int)($this->_customdata['originid'] ?? 0);
        $origintype = (string)($this->_customdata['origintype'] ?? '');

        $mform->addElement('text', 'origincomponent', get_string('origincomponent', 'uckkarchive'), [
            'size' => 40,
            'maxlength' => 100,
        ]);
        $mform->setType('origincomponent', PARAM_COMPONENT);
        $mform->setDefault('origincomponent', $origincomponent);

        $mform->addElement('text', 'originarea', get_string('originarea', 'uckkarchive'), [
            'size' => 40,
            'maxlength' => 100,
        ]);
        $mform->setType('originarea', PARAM_ALPHANUMEXT);
        $mform->setDefault('originarea', $originarea);

        $mform->addElement('text', 'originid', get_string('originid', 'uckkarchive'), [
            'size' => 12,
            'maxlength' => 20,
        ]);
        $mform->setType('originid', PARAM_INT);
        $mform->setDefault('originid', $originid);

        if ($origintype !== '') {
            $mform->addElement('static', 'origintype_display', get_string('origintype', 'uckkarchive'), s($origintype));
        }
    }

    /**
     * Add metadata JSON field.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_metadata_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'metadataheader', get_string('metadata', 'uckkarchive'));

        $mform->addElement('textarea', 'metadatajson', get_string('metadatajson', 'uckkarchive'), [
            'rows' => 8,
            'cols' => 80,
        ]);
        $mform->setType('metadatajson', PARAM_RAW);
        $mform->addHelpButton('metadatajson', 'metadatajson', 'uckkarchive');
    }

    /**
     * Add hidden identifiers expected by add.php/item controllers.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_hidden_fields(\MoodleQuickForm $mform): void {
        foreach ([
            'id',
            'archiveid',
            'courseid',
            'cmid',
            'contextid',
        ] as $field) {
            $mform->addElement('hidden', $field);
            $mform->setType($field, PARAM_INT);
        }
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $this->validate_allowed_value($errors, $data, 'itemtype', $this->get_allowed_values('allowedtypes'), 'invalidarchiveitemtype');
        $this->validate_allowed_value($errors, $data, 'status', $this->get_allowed_values('allowedstatuses'), 'invalidarchivestatus');
        $this->validate_allowed_value($errors, $data, 'visibility', $this->get_allowed_values('allowedvisibilities'), 'invalidarchivevisibility');
        $this->validate_allowed_value($errors, $data, 'provenance', $this->get_allowed_values('allowedprovenance'), 'invalidarchiveprovenance');
        $this->validate_allowed_value($errors, $data, 'validationstate', $this->get_allowed_values('allowedvalidation'), 'invalidarchivevalidationstate');

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            $errors['title'] = get_string('required');
        }

        $content = trim((string)($data['content_editor']['text'] ?? ''));
        $sourceurl = trim((string)($data['sourceurl'] ?? ''));
        $hasitemfiles = $this->draft_area_has_files((int)($data['itemfiles'] ?? 0));
        $hasprooffiles = $this->draft_area_has_files((int)($data['proof_files'] ?? 0));

        if ($content === '' && $sourceurl === '' && !$hasitemfiles && !$hasprooffiles) {
            $errors['content_editor'] = get_string('archiveitemrequirescontent', 'uckkarchive');
        }

        $metadatajson = trim((string)($data['metadatajson'] ?? ''));
        if ($metadatajson !== '') {
            json_decode($metadatajson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['metadatajson'] = get_string('invalidmetadatajson', 'uckkarchive');
            }
        }

        $publicsummary = trim((string)($data['publicsummary_editor']['text'] ?? ''));
        if (($data['visibility'] ?? '') === 'public' && $publicsummary === '') {
            $errors['publicsummary_editor'] = get_string('publicsummaryrequired', 'uckkarchive');
        }

        if (($data['provenance'] ?? '') === 'ai_assisted') {
            $metadata = [];

            if ($metadatajson !== '') {
                $decoded = json_decode($metadatajson, true);
                $metadata = is_array($decoded) ? $decoded : [];
            }

            if (empty($metadata['ai']) && empty($metadata['ai_policy'])) {
                $errors['metadatajson'] = get_string('aimetadatarequired', 'uckkarchive');
            }
        }

        return $errors;
    }

    /**
     * Validate one submitted value against an allowed value list.
     *
     * @param array<string, string> $errors Error list.
     * @param array $data Submitted data.
     * @param string $field Field name.
     * @param string[] $allowed Allowed values.
     * @param string $errorstring Error string key.
     */
    private function validate_allowed_value(array &$errors, array $data, string $field, array $allowed, string $errorstring): void {
        $value = (string)($data[$field] ?? '');

        if ($value === '' || !in_array($value, $allowed, true)) {
            $errors[$field] = get_string($errorstring, 'uckkarchive');
        }
    }

    /**
     * Check whether a draft file area contains files.
     *
     * @param int $draftitemid Draft item id.
     * @return bool
     */
    private function draft_area_has_files(int $draftitemid): bool {
        global $USER;

        if ($draftitemid <= 0) {
            return false;
        }

        $fs = get_file_storage();
        $usercontext = context_user::instance((int)$USER->id);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

        return !empty($files);
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

        throw new \coding_exception('archive_item_form requires context_module in customdata.');
    }

    /**
     * Return canonical allowed values supplied by controller or fallback.
     *
     * @param string $key Custom data key.
     * @return string[]
     */
    private function get_allowed_values(string $key): array {
        if (!empty($this->_customdata[$key]) && is_array($this->_customdata[$key])) {
            return array_values(array_map('strval', $this->_customdata[$key]));
        }

        return match ($key) {
            'allowedtypes' => [
                'proof',
                'decision',
                'course_work',
                'challenge_result',
                'assembly_minutes',
                'integrity_case_summary',
                'kristal',
                'reflection',
                'portfolio_item',
                'version_record',
            ],
            'allowedstatuses' => [
                'draft',
                'submitted',
                'under_review',
            ],
            'allowedvisibilities' => [
                'private',
                'course',
                'cohort',
                'program',
                'institution',
                'institutional',
                'public',
                'restricted_integrity',
            ],
            'allowedprovenance' => [
                'human',
                'ai_assisted',
                'imported',
                'system',
                'archive',
                'assembly',
                'challenge',
                'integrity',
            ],
            'allowedvalidation' => [
                'unverified',
                'human_reviewed',
            ],
            default => [],
        };
    }

    /**
     * Return options for item types.
     *
     * @return array<string, string>
     */
    private function get_item_type_options(): array {
        return $this->build_options('allowedtypes', 'itemtype');
    }

    /**
     * Return options for status.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return $this->build_options('allowedstatuses', 'status');
    }

    /**
     * Return options for visibility.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        return $this->build_options('allowedvisibilities', 'visibility');
    }

    /**
     * Return options for provenance.
     *
     * @return array<string, string>
     */
    private function get_provenance_options(): array {
        return $this->build_options('allowedprovenance', 'provenance');
    }

    /**
     * Return options for validation state.
     *
     * @return array<string, string>
     */
    private function get_validation_options(): array {
        return $this->build_options('allowedvalidation', 'validation');
    }

    /**
     * Build select options using component language keys where available.
     *
     * @param string $allowedkey Custom data key.
     * @param string $prefix Lang key prefix.
     * @return array<string, string>
     */
    private function build_options(string $allowedkey, string $prefix): array {
        $options = [];

        foreach ($this->get_allowed_values($allowedkey) as $value) {
            $options[$value] = $this->get_optional_string($prefix . ':' . $value, $this->humanise_key($value));
        }

        return $options;
    }

    /**
     * Return string if it exists, otherwise fallback.
     *
     * @param string $key Lang key.
     * @param string $fallback Fallback label.
     * @return string
     */
    private function get_optional_string(string $key, string $fallback): string {
        if (get_string_manager()->string_exists($key, 'uckkarchive')) {
            return get_string($key, 'uckkarchive');
        }

        return $fallback;
    }

    /**
     * Humanise a canonical key.
     *
     * @param string $key Canonical key.
     * @return string
     */
    private function humanise_key(string $key): string {
        return ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Content editor options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private function get_content_editor_options(context_module $context): array {
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
     * Public summary editor options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private function get_public_summary_editor_options(context_module $context): array {
        return [
            'context' => $context,
            'maxfiles' => 0,
            'maxbytes' => 0,
            'trusttext' => false,
            'noclean' => false,
            'subdirs' => false,
        ];
    }

    /**
     * File manager options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private function get_filemanager_options(context_module $context): array {
        return [
            'context' => $context,
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size(),
            'maxfiles' => 20,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        ];
    }
}
```

Add these strings to `mod/uckkarchive/lang/en/uckkarchive.php`:

```php id="34j7uk"
$string['aimetadatarequired'] = 'AI-assisted archive items require AI provenance metadata.';
$string['archivegovernance'] = 'Archive governance';
$string['archiveitembody'] = 'Archive item content';
$string['archiveitembody_help'] = 'Describe the item, evidence, decision, Kristal, reflection, or record being archived.';
$string['archiveitemcontent'] = 'Archive content';
$string['archiveitemfiles'] = 'Archive item files';
$string['archiveitemfiles_help'] = 'Files attached to the archive item itself.';
$string['archiveitemidentity'] = 'Archive item identity';
$string['archiveitemrequirescontent'] = 'Add archive content, a source URL, or at least one file.';
$string['archiveitemtitle'] = 'Archive item title';
$string['archiveitemtype'] = 'Archive item type';
$string['archiveitemtype_help'] = 'Choose the canonical UCKK type represented by this archive item.';
$string['archivereason'] = 'Archive reason';
$string['archivereason_help'] = 'Explain why this record is being added to the archive.';
$string['archivesource'] = 'Source';
$string['metadata'] = 'Metadata';
$string['metadatajson'] = 'Metadata JSON';
$string['metadatajson_help'] = 'Optional JSON metadata. Use this only for genuinely variable data.';
$string['proof_files'] = 'Proof files';
$string['proof_files_help'] = 'Evidence files supporting the archived item.';
$string['publicsummary'] = 'Public summary';
$string['publicsummary_help'] = 'A safe summary for public or broad visibility. Do not include restricted or private details.';
$string['publicsummaryrequired'] = 'A public summary is required for public archive items.';
$string['savearchiveitem'] = 'Save archive item';
$string['sourcedate'] = 'Source date';
$string['sourceauthor'] = 'Source author';
$string['sourceauthor_help'] = 'Person, group, system, or institution that produced the source.';
$string['sourcetitle'] = 'Source title';
$string['sourceurl'] = 'Source URL';
$string['sourceurl_help'] = 'Optional URL for the source record or evidence.';

$string['itemtype:proof'] = 'Proof';
$string['itemtype:decision'] = 'Decision';
$string['itemtype:course_work'] = 'Course work';
$string['itemtype:challenge_result'] = 'Challenge result';
$string['itemtype:assembly_minutes'] = 'Assembly minutes';
$string['itemtype:integrity_case_summary'] = 'Integrity case summary';
$string['itemtype:kristal'] = 'Kristal';
$string['itemtype:reflection'] = 'Reflection';
$string['itemtype:portfolio_item'] = 'Portfolio item';
$string['itemtype:version_record'] = 'Version record';

$string['status:draft'] = 'Draft';
$string['status:submitted'] = 'Submitted';
$string['status:under_review'] = 'Under review';

$string['visibility:private'] = 'Private';
$string['visibility:course'] = 'Course';
$string['visibility:cohort'] = 'Cohort';
$string['visibility:program'] = 'Program';
$string['visibility:institution'] = 'Institution';
$string['visibility:institutional'] = 'Institutional';
$string['visibility:public'] = 'Public';
$string['visibility:restricted_integrity'] = 'Restricted integrity';

$string['provenance:human'] = 'Human';
$string['provenance:ai_assisted'] = 'AI-assisted';
$string['provenance:imported'] = 'Imported';
$string['provenance:system'] = 'System';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assembly';
$string['provenance:challenge'] = 'Challenge';
$string['provenance:integrity'] = 'Integrity';

$string['validation:unverified'] = 'Unverified';
$string['validation:human_reviewed'] = 'Human reviewed';
```

Add these strings to `mod/uckkarchive/lang/fr/uckkarchive.php`:

```php id="lo20r7"
$string['aimetadatarequired'] = 'Les éléments d’archive assistés par IA exigent des métadonnées de provenance IA.';
$string['archivegovernance'] = 'Gouvernance de l’archive';
$string['archiveitembody'] = 'Contenu de l’élément d’archive';
$string['archiveitembody_help'] = 'Décrivez l’élément, la preuve, la décision, le Kristal, la réflexion ou la trace archivée.';
$string['archiveitemcontent'] = 'Contenu d’archive';
$string['archiveitemfiles'] = 'Fichiers de l’élément d’archive';
$string['archiveitemfiles_help'] = 'Fichiers attachés à l’élément d’archive lui-même.';
$string['archiveitemidentity'] = 'Identité de l’élément d’archive';
$string['archiveitemrequirescontent'] = 'Ajoutez un contenu d’archive, une URL source ou au moins un fichier.';
$string['archiveitemtitle'] = 'Titre de l’élément d’archive';
$string['archiveitemtype'] = 'Type d’élément d’archive';
$string['archiveitemtype_help'] = 'Choisissez le type UCKK canonique représenté par cet élément d’archive.';
$string['archivereason'] = 'Raison d’archivage';
$string['archivereason_help'] = 'Expliquez pourquoi cette trace est ajoutée à l’archive.';
$string['archivesource'] = 'Source';
$string['metadata'] = 'Métadonnées';
$string['metadatajson'] = 'Métadonnées JSON';
$string['metadatajson_help'] = 'Métadonnées JSON optionnelles. Utilisez ce champ seulement pour les données réellement variables.';
$string['proof_files'] = 'Fichiers de preuve';
$string['proof_files_help'] = 'Fichiers de preuve soutenant l’élément archivé.';
$string['publicsummary'] = 'Résumé public';
$string['publicsummary_help'] = 'Résumé sûr pour une visibilité publique ou élargie. N’incluez pas de détails privés ou restreints.';
$string['publicsummaryrequired'] = 'Un résumé public est requis pour les éléments d’archive publics.';
$string['savearchiveitem'] = 'Enregistrer l’élément d’archive';
$string['sourcedate'] = 'Date de la source';
$string['sourceauthor'] = 'Auteur de la source';
$string['sourceauthor_help'] = 'Personne, groupe, système ou institution qui a produit la source.';
$string['sourcetitle'] = 'Titre de la source';
$string['sourceurl'] = 'URL source';
$string['sourceurl_help'] = 'URL optionnelle vers la trace source ou la preuve.';

$string['itemtype:proof'] = 'Preuve';
$string['itemtype:decision'] = 'Décision';
$string['itemtype:course_work'] = 'Travail de cours';
$string['itemtype:challenge_result'] = 'Résultat de défi';
$string['itemtype:assembly_minutes'] = 'Procès-verbal d’assemblée';
$string['itemtype:integrity_case_summary'] = 'Résumé de dossier d’intégrité';
$string['itemtype:kristal'] = 'Kristal';
$string['itemtype:reflection'] = 'Réflexion';
$string['itemtype:portfolio_item'] = 'Élément de portfolio';
$string['itemtype:version_record'] = 'Trace de version';

$string['status:draft'] = 'Brouillon';
$string['status:submitted'] = 'Soumis';
$string['status:under_review'] = 'En revue';

$string['visibility:private'] = 'Privée';
$string['visibility:course'] = 'Cours';
$string['visibility:cohort'] = 'Cohorte';
$string['visibility:program'] = 'Programme';
$string['visibility:institution'] = 'Institution';
$string['visibility:institutional'] = 'Institutionnelle';
$string['visibility:public'] = 'Publique';
$string['visibility:restricted_integrity'] = 'Restreinte à l’intégrité';

$string['provenance:human'] = 'Humaine';
$string['provenance:ai_assisted'] = 'Assistée par IA';
$string['provenance:imported'] = 'Importée';
$string['provenance:system'] = 'Système';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assemblée';
$string['provenance:challenge'] = 'Défi';
$string['provenance:integrity'] = 'Intégrité';

$string['validation:unverified'] = 'Non vérifiée';
$string['validation:human_reviewed'] = 'Revue humaine';

