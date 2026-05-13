<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Export form for UCKK archive packages.
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
require_once($CFG->dirroot . '/mod/uckkarchive/locallib.php');

use context_module;
use moodleform;
use stdClass;

/**
 * Form used to request or configure an archive export package.
 *
 * This form collects export intent and packaging options only. It does not:
 * - bypass archive visibility rules;
 * - expose restricted integrity records;
 * - validate archive items;
 * - revise archive history;
 * - create files directly;
 * - run long export generation work.
 *
 * Export creation, redaction, provenance packaging, event emission, and file
 * generation belong in service classes and scheduled/ad-hoc tasks.
 */
final class export_form extends moodleform {
    /** Export scope: whole visible archive. */
    private const SCOPE_ARCHIVE = 'archive';

    /** Export scope: selected item ids. */
    private const SCOPE_SELECTED_ITEMS = 'selected_items';

    /** Export scope: validated visible items. */
    private const SCOPE_VALIDATED_ITEMS = 'validated_items';

    /** Export scope: public visible items. */
    private const SCOPE_PUBLIC_ITEMS = 'public_items';

    /** Export scope: restricted integrity material. */
    private const SCOPE_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Export format: JSON. */
    private const FORMAT_JSON = 'json';

    /** Export format: CSV. */
    private const FORMAT_CSV = 'csv';

    /** Export format: HTML. */
    private const FORMAT_HTML = 'html';

    /** Export format: ZIP package. */
    private const FORMAT_ZIP = 'zip';

    /** Export mode: generate immediately. */
    private const MODE_IMMEDIATE = 'immediate';

    /** Export mode: queue generation. */
    private const MODE_QUEUED = 'queued';

    /**
     * Define the form.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $archive = $this->get_custom_record('archive');
        $context = $this->get_context();

        $mform->addElement('header', 'exportheader', get_string('archiveexport', 'uckkarchive'));

        if (!empty($archive->name)) {
            $mform->addElement(
                'static',
                'archivename',
                get_string('archive', 'uckkarchive'),
                format_string($archive->name)
            );
        }

        $this->add_scope_section($mform);
        $this->add_package_section($mform);
        $this->add_provenance_section($mform);
        $this->add_privacy_section($mform);
        $this->add_governance_section($mform, $context);
        $this->add_hidden_fields($mform);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('requestexport', 'uckkarchive'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Add export scope fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_scope_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'scopeheader', get_string('exportscope', 'uckkarchive'));

        $mform->addElement('select', 'scope', get_string('exportscope', 'uckkarchive'), $this->get_scope_options());
        $mform->setDefault('scope', self::SCOPE_VALIDATED_ITEMS);
        $mform->addRule('scope', null, 'required', null, 'client');
        $mform->addHelpButton('scope', 'exportscope', 'uckkarchive');

        $mform->addElement('textarea', 'itemids', get_string('exportitemids', 'uckkarchive'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('itemids', PARAM_RAW_TRIMMED);
        $mform->hideIf('itemids', 'scope', 'neq', self::SCOPE_SELECTED_ITEMS);
        $mform->addHelpButton('itemids', 'exportitemids', 'uckkarchive');

        $mform->addElement('select', 'statusfilter', get_string('exportstatusfilter', 'uckkarchive'), $this->get_status_filter_options());
        $mform->setDefault('statusfilter', '');

        $mform->addElement('select', 'typefilter', get_string('exporttypefilter', 'uckkarchive'), $this->get_type_filter_options());
        $mform->setDefault('typefilter', '');

        $mform->addElement('date_time_selector', 'timefrom', get_string('exporttimefrom', 'uckkarchive'), [
            'optional' => true,
        ]);
        $mform->setDefault('timefrom', 0);

        $mform->addElement('date_time_selector', 'timeto', get_string('exporttimeto', 'uckkarchive'), [
            'optional' => true,
        ]);
        $mform->setDefault('timeto', 0);
    }

    /**
     * Add package output fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_package_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'packageheader', get_string('exportpackage', 'uckkarchive'));

        $mform->addElement('select', 'format', get_string('exportformat', 'uckkarchive'), $this->get_format_options());
        $mform->setDefault('format', self::FORMAT_ZIP);
        $mform->addRule('format', null, 'required', null, 'client');

        $mform->addElement('select', 'mode', get_string('exportmode', 'uckkarchive'), $this->get_mode_options());
        $mform->setDefault('mode', self::MODE_QUEUED);
        $mform->addRule('mode', null, 'required', null, 'client');

        $mform->addElement('text', 'packagename', get_string('exportpackagename', 'uckkarchive'), [
            'size' => 64,
            'maxlength' => 255,
        ]);
        $mform->setType('packagename', PARAM_TEXT);
        $mform->addHelpButton('packagename', 'exportpackagename', 'uckkarchive');

        $mform->addElement('textarea', 'description', get_string('exportdescription', 'uckkarchive'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('advcheckbox', 'includefiles', get_string('exportincludefiles', 'uckkarchive'));
        $mform->setDefault('includefiles', 1);

        $mform->addElement('advcheckbox', 'includeproofs', get_string('exportincludeproofs', 'uckkarchive'));
        $mform->setDefault('includeproofs', 1);

        $mform->addElement('advcheckbox', 'includekristals', get_string('exportincludekristals', 'uckkarchive'));
        $mform->setDefault('includekristals', 1);
    }

    /**
     * Add provenance and version fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_provenance_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'provenanceheader', get_string('exportprovenance', 'uckkarchive'));

        $mform->addElement('advcheckbox', 'includeprovenance', get_string('exportincludeprovenance', 'uckkarchive'));
        $mform->setDefault('includeprovenance', 1);

        $mform->addElement('advcheckbox', 'includeversions', get_string('exportincludeversions', 'uckkarchive'));
        $mform->setDefault('includeversions', 1);

        $mform->addElement('advcheckbox', 'includemetadata', get_string('exportincludemetadata', 'uckkarchive'));
        $mform->setDefault('includemetadata', 1);

        $mform->addElement('advcheckbox', 'includehashes', get_string('exportincludehashes', 'uckkarchive'));
        $mform->setDefault('includehashes', 1);

        $mform->addElement('advcheckbox', 'includeintegritysummary', get_string('exportincludeintegritysummary', 'uckkarchive'));
        $mform->setDefault('includeintegritysummary', 1);
        $mform->addHelpButton('includeintegritysummary', 'exportincludeintegritysummary', 'uckkarchive');
    }

    /**
     * Add privacy and redaction fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_privacy_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'privacyheader', get_string('exportprivacy', 'uckkarchive'));

        $mform->addElement('select', 'visibility', get_string('exportvisibility', 'uckkarchive'), $this->get_visibility_options());
        $mform->setDefault('visibility', UCKKARCHIVE_VISIBILITY_COURSE);
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'redactpersonaldata', get_string('exportredactpersonaldata', 'uckkarchive'));
        $mform->setDefault('redactpersonaldata', 1);

        $mform->addElement('advcheckbox', 'redactrestricted', get_string('exportredactrestricted', 'uckkarchive'));
        $mform->setDefault('redactrestricted', 1);

        $mform->addElement('advcheckbox', 'includeprivatefields', get_string('exportincludeprivatefields', 'uckkarchive'));
        $mform->setDefault('includeprivatefields', 0);
        $mform->addHelpButton('includeprivatefields', 'exportincludeprivatefields', 'uckkarchive');

        if (!$this->can_export_restricted()) {
            $mform->freeze('includeprivatefields');
        }
    }

    /**
     * Add governance fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param context_module $context Module context.
     */
    private function add_governance_section(\MoodleQuickForm $mform, context_module $context): void {
        $mform->addElement('header', 'governanceheader', get_string('exportgovernance', 'uckkarchive'));

        $mform->addElement('textarea', 'reason', get_string('exportreason', 'uckkarchive'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('reason', PARAM_RAW_TRIMMED);
        $mform->addRule('reason', null, 'required', null, 'client');
        $mform->addHelpButton('reason', 'exportreason', 'uckkarchive');

        $mform->addElement('textarea', 'auditnote', get_string('exportauditnote', 'uckkarchive'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('auditnote', PARAM_RAW_TRIMMED);

        $mform->addElement('static', 'exportnotice', get_string('exportnotice', 'uckkarchive'), get_string('exportnotice_desc', 'uckkarchive'));

        $mform->addElement('advcheckbox', 'confirmpolicy', get_string('exportconfirmpolicy', 'uckkarchive'));
        $mform->setDefault('confirmpolicy', 0);
        $mform->addRule('confirmpolicy', get_string('exportconfirmpolicyrequired', 'uckkarchive'), 'required', null, 'client');

        $mform->addElement('hidden', 'contextid', $context->id);
        $mform->setType('contextid', PARAM_INT);
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
            'exportid',
            'returnurl',
        ] as $field) {
            $mform->addElement('hidden', $field);
        }

        $mform->setType('id', PARAM_INT);
        $mform->setType('archiveid', PARAM_INT);
        $mform->setType('exportid', PARAM_INT);
        $mform->setType('returnurl', PARAM_LOCALURL);
    }

    /**
     * Validate form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $scope = (string)($data['scope'] ?? '');
        $format = (string)($data['format'] ?? '');
        $mode = (string)($data['mode'] ?? '');
        $visibility = (string)($data['visibility'] ?? '');

        if (!array_key_exists($scope, $this->get_scope_options())) {
            $errors['scope'] = get_string('invalidexportscope', 'uckkarchive');
        }

        if (!array_key_exists($format, $this->get_format_options())) {
            $errors['format'] = get_string('invalidexportformat', 'uckkarchive');
        }

        if (!array_key_exists($mode, $this->get_mode_options())) {
            $errors['mode'] = get_string('invalidexportmode', 'uckkarchive');
        }

        if (!array_key_exists($visibility, $this->get_visibility_options())) {
            $errors['visibility'] = get_string('invalidexportvisibility', 'uckkarchive');
        }

        if ($scope === self::SCOPE_SELECTED_ITEMS && empty($this->parse_item_ids((string)($data['itemids'] ?? '')))) {
            $errors['itemids'] = get_string('exportitemidsrequired', 'uckkarchive');
        }

        if (!empty($data['timefrom']) && !empty($data['timeto']) && (int)$data['timeto'] <= (int)$data['timefrom']) {
            $errors['timeto'] = get_string('exporttimetomustbeafterfrom', 'uckkarchive');
        }

        $restrictedexportrequested = $scope === self::SCOPE_RESTRICTED_INTEGRITY
            || $visibility === UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY
            || !empty($data['includeprivatefields']);

        if ($restrictedexportrequested && !$this->can_export_restricted()) {
            if ($scope === self::SCOPE_RESTRICTED_INTEGRITY) {
                $errors['scope'] = get_string('exportrestrictednotallowed', 'uckkarchive');
            }

            if ($visibility === UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY) {
                $errors['visibility'] = get_string('exportrestrictednotallowed', 'uckkarchive');
            }

            if (!empty($data['includeprivatefields'])) {
                $errors['includeprivatefields'] = get_string('exportrestrictednotallowed', 'uckkarchive');
            }
        }

        if ($restrictedexportrequested && empty($data['redactrestricted']) && !$this->can_export_unredacted()) {
            $errors['redactrestricted'] = get_string('exportunredactednotallowed', 'uckkarchive');
        }

        if (empty(trim((string)($data['reason'] ?? '')))) {
            $errors['reason'] = get_string('required');
        }

        if (empty($data['confirmpolicy'])) {
            $errors['confirmpolicy'] = get_string('exportconfirmpolicyrequired', 'uckkarchive');
        }

        return $errors;
    }

    /**
     * Convert submitted values into a service-friendly options object.
     *
     * @param stdClass $data Submitted form data.
     * @return stdClass
     */
    public function export_options_from_data(stdClass $data): stdClass {
        $options = new stdClass();

        $options->cmid = (int)($data->id ?? 0);
        $options->archiveid = (int)($data->archiveid ?? 0);
        $options->exportid = (int)($data->exportid ?? 0);
        $options->contextid = (int)($data->contextid ?? 0);

        $options->scope = (string)$data->scope;
        $options->itemids = $this->parse_item_ids((string)($data->itemids ?? ''));
        $options->statusfilter = clean_param((string)($data->statusfilter ?? ''), PARAM_ALPHANUMEXT);
        $options->typefilter = clean_param((string)($data->typefilter ?? ''), PARAM_ALPHANUMEXT);
        $options->timefrom = (int)($data->timefrom ?? 0);
        $options->timeto = (int)($data->timeto ?? 0);

        $options->format = (string)$data->format;
        $options->mode = (string)$data->mode;
        $options->packagename = trim((string)($data->packagename ?? ''));
        $options->description = trim((string)($data->description ?? ''));

        $options->includefiles = !empty($data->includefiles);
        $options->includeproofs = !empty($data->includeproofs);
        $options->includekristals = !empty($data->includekristals);
        $options->includeprovenance = !empty($data->includeprovenance);
        $options->includeversions = !empty($data->includeversions);
        $options->includemetadata = !empty($data->includemetadata);
        $options->includehashes = !empty($data->includehashes);
        $options->includeintegritysummary = !empty($data->includeintegritysummary);

        $options->visibility = (string)$data->visibility;
        $options->redactpersonaldata = !empty($data->redactpersonaldata);
        $options->redactrestricted = !empty($data->redactrestricted);
        $options->includeprivatefields = !empty($data->includeprivatefields);

        $options->reason = trim((string)($data->reason ?? ''));
        $options->auditnote = trim((string)($data->auditnote ?? ''));
        $options->returnurl = (string)($data->returnurl ?? '');

        return $options;
    }

    /**
     * Get module context from custom data.
     *
     * @return context_module
     */
    private function get_context(): context_module {
        if (!empty($this->_customdata['context']) && $this->_customdata['context'] instanceof context_module) {
            return $this->_customdata['context'];
        }

        throw new \coding_exception('export_form requires a module context in customdata.');
    }

    /**
     * Get a custom data record.
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
     * Whether this user/session can request restricted export data.
     *
     * @return bool
     */
    private function can_export_restricted(): bool {
        return !empty($this->_customdata['canexportrestricted']);
    }

    /**
     * Whether this user/session can request unredacted restricted export data.
     *
     * @return bool
     */
    private function can_export_unredacted(): bool {
        return !empty($this->_customdata['canexportunredacted']);
    }

    /**
     * Parse comma/newline/space separated item ids.
     *
     * @param string $raw Raw item ids.
     * @return int[]
     */
    private function parse_item_ids(string $raw): array {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $ids = [];

        foreach ($parts as $part) {
            $id = (int)$part;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Export scope options.
     *
     * @return array<string, string>
     */
    private function get_scope_options(): array {
        $options = [
            self::SCOPE_ARCHIVE => get_string('exportscope:archive', 'uckkarchive'),
            self::SCOPE_SELECTED_ITEMS => get_string('exportscope:selecteditems', 'uckkarchive'),
            self::SCOPE_VALIDATED_ITEMS => get_string('exportscope:validateditems', 'uckkarchive'),
            self::SCOPE_PUBLIC_ITEMS => get_string('exportscope:publicitems', 'uckkarchive'),
        ];

        if ($this->can_export_restricted()) {
            $options[self::SCOPE_RESTRICTED_INTEGRITY] = get_string('exportscope:restrictedintegrity', 'uckkarchive');
        }

        return $options;
    }

    /**
     * Export format options.
     *
     * @return array<string, string>
     */
    private function get_format_options(): array {
        return [
            self::FORMAT_ZIP => get_string('exportformat:zip', 'uckkarchive'),
            self::FORMAT_JSON => get_string('exportformat:json', 'uckkarchive'),
            self::FORMAT_CSV => get_string('exportformat:csv', 'uckkarchive'),
            self::FORMAT_HTML => get_string('exportformat:html', 'uckkarchive'),
        ];
    }

    /**
     * Export generation mode options.
     *
     * @return array<string, string>
     */
    private function get_mode_options(): array {
        return [
            self::MODE_QUEUED => get_string('exportmode:queued', 'uckkarchive'),
            self::MODE_IMMEDIATE => get_string('exportmode:immediate', 'uckkarchive'),
        ];
    }

    /**
     * Status filter options.
     *
     * @return array<string, string>
     */
    private function get_status_filter_options(): array {
        return ['' => get_string('all')] + uckkarchive_get_status_options();
    }

    /**
     * Type filter options.
     *
     * @return array<string, string>
     */
    private function get_type_filter_options(): array {
        return ['' => get_string('all')] + uckkarchive_get_item_type_options();
    }

    /**
     * Visibility options.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        $options = uckkarchive_get_visibility_options();

        if (!$this->can_export_restricted()) {
            unset($options[UCKKARCHIVE_VISIBILITY_RESTRICTED_INTEGRITY]);
        }

        return $options;
    }
}
```

Add these strings to `mod/uckkarchive/lang/en/uckkarchive.php`:

```php id="n5jk0f"
$string['archive'] = 'Archive';
$string['archiveexport'] = 'Archive export';
$string['exportauditnote'] = 'Audit note';
$string['exportconfirmpolicy'] = 'I confirm that this export respects archive visibility, provenance, privacy, integrity, and human validation rules.';
$string['exportconfirmpolicyrequired'] = 'You must confirm the export policy.';
$string['exportdescription'] = 'Export description';
$string['exportformat'] = 'Export format';
$string['exportformat:csv'] = 'CSV';
$string['exportformat:html'] = 'HTML';
$string['exportformat:json'] = 'JSON';
$string['exportformat:zip'] = 'ZIP package';
$string['exportgovernance'] = 'Export governance';
$string['exportincludefiles'] = 'Include files';
$string['exportincludehashes'] = 'Include provenance hashes';
$string['exportincludeintegritysummary'] = 'Include integrity summary';
$string['exportincludeintegritysummary_help'] = 'Include integrity state summaries only. Restricted case details remain governed by restricted capabilities and redaction settings.';
$string['exportincludekristals'] = 'Include Kristals';
$string['exportincludemetadata'] = 'Include metadata';
$string['exportincludeprivatefields'] = 'Include private or restricted fields';
$string['exportincludeprivatefields_help'] = 'Use only for authorised institutional or integrity exports. Redaction and capability checks still apply server-side.';
$string['exportincludeproofs'] = 'Include proofs';
$string['exportincludeprovenance'] = 'Include provenance records';
$string['exportincludeversions'] = 'Include version history';
$string['exportitemids'] = 'Archive item IDs';
$string['exportitemids_help'] = 'For selected-item exports, enter archive item IDs separated by commas, spaces, semicolons, or new lines.';
$string['exportitemidsrequired'] = 'Enter at least one archive item ID.';
$string['exportmode'] = 'Export mode';
$string['exportmode:immediate'] = 'Generate immediately';
$string['exportmode:queued'] = 'Queue export generation';
$string['exportnotice'] = 'Export notice';
$string['exportnotice_desc'] = 'Exports are institutional records. They must not bypass visibility, privacy, integrity restrictions, provenance, or human validation.';
$string['exportpackage'] = 'Export package';
$string['exportpackagename'] = 'Package name';
$string['exportpackagename_help'] = 'Optional stable name for this export package.';
$string['exportprivacy'] = 'Export privacy';
$string['exportprovenance'] = 'Provenance and versions';
$string['exportreason'] = 'Export reason';
$string['exportreason_help'] = 'Explain why this archive export is needed. This reason becomes part of the export audit trail.';
$string['exportredactpersonaldata'] = 'Redact personal data where possible';
$string['exportredactrestricted'] = 'Redact restricted integrity details';
$string['exportrestrictednotallowed'] = 'You cannot request restricted export data.';
$string['exportscope'] = 'Export scope';
$string['exportscope_help'] = 'Choose which archive records should be included. Server-side permissions and visibility filters remain authoritative.';
$string['exportscope:archive'] = 'All visible archive items';
$string['exportscope:publicitems'] = 'Public items only';
$string['exportscope:restrictedintegrity'] = 'Restricted integrity material';
$string['exportscope:selecteditems'] = 'Selected archive items';
$string['exportscope:validateditems'] = 'Validated items only';
$string['exportstatusfilter'] = 'Status filter';
$string['exporttimefrom'] = 'From';
$string['exporttimeto'] = 'To';
$string['exporttimetomustbeafterfrom'] = 'The end time must be after the start time.';
$string['exporttypefilter'] = 'Item type filter';
$string['exportunredactednotallowed'] = 'You cannot request unredacted restricted export data.';
$string['exportvisibility'] = 'Export visibility';
$string['invalidexportformat'] = 'Invalid export format.';
$string['invalidexportmode'] = 'Invalid export mode.';
$string['invalidexportscope'] = 'Invalid export scope.';
$string['invalidexportvisibility'] = 'Invalid export visibility.';
$string['requestexport'] = 'Request export';
```

Add these strings to `mod/uckkarchive/lang/fr/uckkarchive.php`:

```php id="q93uzq"
$string['archive'] = 'Archive';
$string['archiveexport'] = 'Export d’archive';
$string['exportauditnote'] = 'Note d’audit';
$string['exportconfirmpolicy'] = 'Je confirme que cet export respecte les règles de visibilité, provenance, confidentialité, intégrité et validation humaine de l’archive.';
$string['exportconfirmpolicyrequired'] = 'Vous devez confirmer la politique d’export.';
$string['exportdescription'] = 'Description de l’export';
$string['exportformat'] = 'Format d’export';
$string['exportformat:csv'] = 'CSV';
$string['exportformat:html'] = 'HTML';
$string['exportformat:json'] = 'JSON';
$string['exportformat:zip'] = 'Paquet ZIP';
$string['exportgovernance'] = 'Gouvernance de l’export';
$string['exportincludefiles'] = 'Inclure les fichiers';
$string['exportincludehashes'] = 'Inclure les empreintes de provenance';
$string['exportincludeintegritysummary'] = 'Inclure la synthèse d’intégrité';
$string['exportincludeintegritysummary_help'] = 'Inclut seulement les synthèses d’état d’intégrité. Les détails de dossiers restreints demeurent gouvernés par les capacités restreintes et les paramètres de caviardage.';
$string['exportincludekristals'] = 'Inclure les Kristals';
$string['exportincludemetadata'] = 'Inclure les métadonnées';
$string['exportincludeprivatefields'] = 'Inclure les champs privés ou restreints';
$string['exportincludeprivatefields_help'] = 'À utiliser seulement pour les exports institutionnels ou d’intégrité autorisés. Le caviardage et les capacités demeurent vérifiés côté serveur.';
$string['exportincludeproofs'] = 'Inclure les preuves';
$string['exportincludeprovenance'] = 'Inclure les traces de provenance';
$string['exportincludeversions'] = 'Inclure l’historique des versions';
$string['exportitemids'] = 'ID des éléments d’archive';
$string['exportitemids_help'] = 'Pour un export d’éléments sélectionnés, entrez les ID d’éléments d’archive séparés par virgules, espaces, points-virgules ou retours de ligne.';
$string['exportitemidsrequired'] = 'Entrez au moins un ID d’élément d’archive.';
$string['exportmode'] = 'Mode d’export';
$string['exportmode:immediate'] = 'Générer immédiatement';
$string['exportmode:queued'] = 'Mettre la génération en file';
$string['exportnotice'] = 'Avis d’export';
$string['exportnotice_desc'] = 'Les exports sont des traces institutionnelles. Ils ne doivent pas contourner la visibilité, la confidentialité, les restrictions d’intégrité, la provenance ou la validation humaine.';
$string['exportpackage'] = 'Paquet d’export';
$string['exportpackagename'] = 'Nom du paquet';
$string['exportpackagename_help'] = 'Nom stable optionnel pour ce paquet d’export.';
$string['exportprivacy'] = 'Confidentialité de l’export';
$string['exportprovenance'] = 'Provenance et versions';
$string['exportreason'] = 'Raison de l’export';
$string['exportreason_help'] = 'Expliquez pourquoi cet export d’archive est nécessaire. Cette raison devient partie de la trace d’audit.';
$string['exportredactpersonaldata'] = 'Caviarder les données personnelles lorsque possible';
$string['exportredactrestricted'] = 'Caviarder les détails d’intégrité restreints';
$string['exportrestrictednotallowed'] = 'Vous ne pouvez pas demander des données d’export restreintes.';
$string['exportscope'] = 'Portée de l’export';
$string['exportscope_help'] = 'Choisissez les traces d’archive à inclure. Les permissions et filtres de visibilité côté serveur demeurent autoritaires.';
$string['exportscope:archive'] = 'Tous les éléments d’archive visibles';
$string['exportscope:publicitems'] = 'Éléments publics seulement';
$string['exportscope:restrictedintegrity'] = 'Matériel d’intégrité restreint';
$string['exportscope:selecteditems'] = 'Éléments d’archive sélectionnés';
$string['exportscope:validateditems'] = 'Éléments validés seulement';
$string['exportstatusfilter'] = 'Filtre de statut';
$string['exporttimefrom'] = 'À partir de';
$string['exporttimeto'] = 'Jusqu’à';
$string['exporttimetomustbeafterfrom'] = 'La date de fin doit être après la date de début.';
$string['exporttypefilter'] = 'Filtre de type d’élément';
$string['exportunredactednotallowed'] = 'Vous ne pouvez pas demander un export restreint non caviardé.';
$string['exportvisibility'] = 'Visibilité de l’export';
$string['invalidexportformat'] = 'Format d’export invalide.';
$string['invalidexportmode'] = 'Mode d’export invalide.';
$string['invalidexportscope'] = 'Portée d’export invalide.';
$string['invalidexportvisibility'] = 'Visibilité d’export invalide.';
$string['requestexport'] = 'Demander l’export';

