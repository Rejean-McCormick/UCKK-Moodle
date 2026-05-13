<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Proof list output class for UCKK challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable proof list for Défis King Klown.
 *
 * This class prepares display data only. It does not decide permissions,
 * validate integrity, grade submissions, award badges, certify competencies,
 * or archive records.
 */
final class proof_list implements renderable, templatable {
    /**
     * Supported proof types.
     */
    private const PROOF_TYPES = [
        'text',
        'file',
        'url',
        'dataset',
        'image',
        'video',
        'testimony',
        'observation',
        'ai_log',
        'decision_record',
        'archive_item',
        'portfolio_item',
        'assembly_decision',
        'mentor_observation',
        'inquisiteur_note',
        'external_artifact',
    ];

    /**
     * Supported proof statuses.
     */
    private const STATUSES = [
        'draft',
        'submitted',
        'pending_review',
        'under_review',
        'validated',
        'rejected',
        'correction_required',
        'contested',
        'invalidated',
        'archived',
    ];

    /**
     * Supported visibility values.
     */
    private const VISIBILITIES = [
        'private',
        'user',
        'group',
        'course',
        'cohort',
        'program',
        'institution',
        'public',
        'restricted',
        'restricted_integrity',
        'hidden',
        'archived',
    ];

    /**
     * Supported integrity states.
     */
    private const INTEGRITY_STATES = [
        'unverified',
        'human_reviewed',
        'verified',
        'contested',
        'invalidated',
        'archived',
    ];

    /**
     * Challenge id.
     *
     * @var int
     */
    private int $challengeid;

    /**
     * Course module id.
     *
     * @var int
     */
    private int $cmid;

    /**
     * Prepared proof rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $proofs;

    /**
     * Whether the current viewer can review proof records.
     *
     * @var bool
     */
    private bool $canreview;

    /**
     * Whether the current viewer can submit proof.
     *
     * @var bool
     */
    private bool $cansubmit;

    /**
     * Whether restricted proof metadata may be displayed.
     *
     * @var bool
     */
    private bool $canviewrestricted;

    /**
     * Optional URL to submit proof.
     *
     * @var moodle_url|null
     */
    private ?moodle_url $submiturl;

    /**
     * Optional URL to review all proof.
     *
     * @var moodle_url|null
     */
    private ?moodle_url $reviewurl;

    /**
     * Optional heading override.
     *
     * @var string|null
     */
    private ?string $heading;

    /**
     * Constructor.
     *
     * @param int $challengeid Challenge instance id.
     * @param int $cmid Course module id.
     * @param array<int, array<string, mixed>|stdClass> $proofs Permission-filtered proof rows.
     * @param array<string, mixed> $options Display options.
     */
    public function __construct(
        int $challengeid,
        int $cmid,
        array $proofs = [],
        array $options = []
    ) {
        $this->challengeid = $challengeid;
        $this->cmid = $cmid;
        $this->proofs = array_map([$this, 'normalise_proof'], $proofs);
        $this->canreview = !empty($options['canreview']);
        $this->cansubmit = !empty($options['cansubmit']);
        $this->canviewrestricted = !empty($options['canviewrestricted']);
        $this->submiturl = $this->normalise_moodle_url($options['submiturl'] ?? null);
        $this->reviewurl = $this->normalise_moodle_url($options['reviewurl'] ?? null);
        $this->heading = isset($options['heading']) ? (string)$options['heading'] : null;
    }

    /**
     * Export data for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $data->challengeid = $this->challengeid;
        $data->cmid = $this->cmid;
        $data->heading = $this->heading ?? get_string('prooflist', 'uckkchallenge');

        $data->hasproofs = !empty($this->proofs);
        $data->emptylabel = get_string('noproofs', 'uckkchallenge');

        $data->canreview = $this->canreview;
        $data->cansubmit = $this->cansubmit;
        $data->canviewrestricted = $this->canviewrestricted;

        $data->hassubmiturl = $this->submiturl !== null;
        $data->submiturl = $this->submiturl ? $this->submiturl->out(false) : '';
        $data->submitlabel = get_string('submitproof', 'uckkchallenge');

        $data->hasreviewurl = $this->reviewurl !== null;
        $data->reviewurl = $this->reviewurl ? $this->reviewurl->out(false) : '';
        $data->reviewlabel = get_string('reviewproofs', 'uckkchallenge');

        $data->counts = $this->build_counts();
        $data->proofs = $this->build_proof_rows();
        $data->hasrestricteditems = $this->has_restricted_items();
        $data->hasintegritywarnings = $this->has_integrity_warnings();
        $data->hasaiassisteditems = $this->has_ai_assisted_items();

        return $data;
    }

    /**
     * Get the expected template name.
     *
     * @param renderer_base $renderer Renderer.
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'mod_uckkchallenge/proof_list';
    }

    /**
     * Normalise one proof row.
     *
     * @param array<string, mixed>|stdClass $proof Raw proof row.
     * @return array<string, mixed>
     */
    private function normalise_proof(array|stdClass $proof): array {
        $row = (array)$proof;

        $id = (int)($row['id'] ?? 0);
        $userid = (int)($row['userid'] ?? 0);
        $type = $this->normalise_choice((string)($row['prooftype'] ?? $row['type'] ?? 'text'), self::PROOF_TYPES, 'text');
        $status = $this->normalise_choice((string)($row['status'] ?? 'submitted'), self::STATUSES, 'submitted');
        $visibility = $this->normalise_choice((string)($row['visibility'] ?? 'course'), self::VISIBILITIES, 'course');
        $integritystate = $this->normalise_choice(
            (string)($row['integritystate'] ?? $row['validationstate'] ?? 'unverified'),
            self::INTEGRITY_STATES,
            'unverified'
        );

        $title = trim((string)($row['title'] ?? $row['name'] ?? ''));
        if ($title === '') {
            $title = get_string('proof', 'uckkchallenge') . ' #' . $id;
        }

        $summary = trim((string)($row['summary'] ?? ''));
        $relationtocriteria = trim((string)($row['relationtocriteria'] ?? ''));
        $provenance = trim((string)($row['provenance'] ?? $row['provenancestatement'] ?? ''));
        $sourceauthor = trim((string)($row['sourceauthor'] ?? $row['author'] ?? ''));
        $source = trim((string)($row['source'] ?? $row['sourcedescription'] ?? ''));
        $sourceurl = $this->normalise_url_string($row['sourceurl'] ?? $row['proofurl'] ?? $row['url'] ?? null);

        $timecreated = (int)($row['timecreated'] ?? 0);
        $timemodified = (int)($row['timemodified'] ?? 0);
        $sourcedate = (int)($row['sourcedate'] ?? 0);

        $reviewurl = $this->normalise_url_string($row['reviewurl'] ?? null);
        $viewurl = $this->normalise_url_string($row['viewurl'] ?? $row['url'] ?? null);
        $editurl = $this->normalise_url_string($row['editurl'] ?? null);
        $downloadurl = $this->normalise_url_string($row['downloadurl'] ?? null);

        $files = $this->normalise_files($row['files'] ?? []);

        return [
            'id' => $id,
            'userid' => $userid,
            'userfullname' => format_string((string)($row['userfullname'] ?? '')),
            'hasuserfullname' => trim((string)($row['userfullname'] ?? '')) !== '',

            'title' => format_string($title),
            'summary' => format_string($summary),
            'hassummary' => $summary !== '',

            'type' => $type,
            'typelabel' => $this->get_lang_label('prooftype', $type),
            'typeclass' => $this->get_css_suffix($type),

            'status' => $status,
            'statuslabel' => $this->get_lang_label('status', $status),
            'statusclass' => $this->get_css_suffix($status),

            'visibility' => $visibility,
            'visibilitylabel' => $this->get_lang_label('visibility', $visibility),
            'visibilityclass' => $this->get_css_suffix($visibility),
            'isrestricted' => in_array($visibility, ['restricted', 'restricted_integrity', 'hidden'], true),

            'integritystate' => $integritystate,
            'integritylabel' => $this->get_lang_label('integritystate', $integritystate),
            'integrityclass' => $this->get_css_suffix($integritystate),
            'hasintegritywarning' => in_array($integritystate, ['unverified', 'contested', 'invalidated'], true),

            'relationtocriteria' => format_string($relationtocriteria),
            'hasrelationtocriteria' => $relationtocriteria !== '',

            'provenance' => format_string($provenance),
            'hasprovenance' => $provenance !== '',

            'sourceauthor' => format_string($sourceauthor),
            'hassourceauthor' => $sourceauthor !== '',

            'source' => format_string($source),
            'hassource' => $source !== '',

            'sourceurl' => $sourceurl,
            'hassourceurl' => $sourceurl !== '',

            'timecreated' => $timecreated,
            'timecreatedlabel' => $timecreated > 0 ? userdate($timecreated) : '',
            'hastimecreated' => $timecreated > 0,

            'timemodified' => $timemodified,
            'timemodifiedlabel' => $timemodified > 0 ? userdate($timemodified) : '',
            'hastimemodified' => $timemodified > 0,

            'sourcedate' => $sourcedate,
            'sourcedatelabel' => $sourcedate > 0 ? userdate($sourcedate, get_string('strftimedatefullshort', 'langconfig')) : '',
            'hassourcedate' => $sourcedate > 0,

            'aiassisted' => !empty($row['aiassisted']) || $type === 'ai_log',
            'ailogavailable' => !empty($row['ailogavailable']) || trim((string)($row['ailog'] ?? '')) !== '',
            'uncertaintynotes' => format_string((string)($row['uncertaintynotes'] ?? '')),
            'hasuncertaintynotes' => trim((string)($row['uncertaintynotes'] ?? '')) !== '',

            'filecount' => count($files),
            'hasfiles' => !empty($files),
            'files' => $files,

            'viewurl' => $viewurl,
            'hasviewurl' => $viewurl !== '',
            'editurl' => $editurl,
            'hasediturl' => $editurl !== '',
            'reviewurl' => $reviewurl,
            'hasreviewurl' => $reviewurl !== '',
            'downloadurl' => $downloadurl,
            'hasdownloadurl' => $downloadurl !== '',
        ];
    }

    /**
     * Build count summary.
     *
     * @return stdClass
     */
    private function build_counts(): stdClass {
        $counts = (object)[
            'total' => count($this->proofs),
            'draft' => 0,
            'submitted' => 0,
            'pendingreview' => 0,
            'validated' => 0,
            'correctionrequired' => 0,
            'contested' => 0,
            'invalidated' => 0,
            'archived' => 0,
            'restricted' => 0,
            'aiassisted' => 0,
            'files' => 0,
        ];

        foreach ($this->proofs as $proof) {
            if ($proof['status'] === 'draft') {
                $counts->draft++;
            }

            if ($proof['status'] === 'submitted') {
                $counts->submitted++;
            }

            if (in_array($proof['status'], ['pending_review', 'under_review'], true)) {
                $counts->pendingreview++;
            }

            if ($proof['status'] === 'validated') {
                $counts->validated++;
            }

            if ($proof['status'] === 'correction_required') {
                $counts->correctionrequired++;
            }

            if ($proof['status'] === 'contested') {
                $counts->contested++;
            }

            if ($proof['status'] === 'invalidated') {
                $counts->invalidated++;
            }

            if ($proof['status'] === 'archived') {
                $counts->archived++;
            }

            if (!empty($proof['isrestricted'])) {
                $counts->restricted++;
            }

            if (!empty($proof['aiassisted'])) {
                $counts->aiassisted++;
            }

            $counts->files += (int)$proof['filecount'];
        }

        return $counts;
    }

    /**
     * Export proof rows.
     *
     * @return array<int, stdClass>
     */
    private function build_proof_rows(): array {
        $rows = [];

        foreach ($this->proofs as $proof) {
            if (!empty($proof['isrestricted']) && !$this->canviewrestricted) {
                $rows[] = $this->build_restricted_placeholder($proof);
                continue;
            }

            $rows[] = (object)[
                'id' => $proof['id'],
                'userid' => $proof['userid'],
                'userfullname' => $proof['userfullname'],
                'hasuserfullname' => $proof['hasuserfullname'],

                'title' => $proof['title'],
                'summary' => $proof['summary'],
                'hassummary' => $proof['hassummary'],

                'type' => $proof['type'],
                'typelabel' => $proof['typelabel'],
                'typeclass' => $proof['typeclass'],

                'status' => $proof['status'],
                'statuslabel' => $proof['statuslabel'],
                'statusclass' => $proof['statusclass'],

                'visibility' => $proof['visibility'],
                'visibilitylabel' => $proof['visibilitylabel'],
                'visibilityclass' => $proof['visibilityclass'],
                'isrestricted' => $proof['isrestricted'],

                'integritystate' => $proof['integritystate'],
                'integritylabel' => $proof['integritylabel'],
                'integrityclass' => $proof['integrityclass'],
                'hasintegritywarning' => $proof['hasintegritywarning'],

                'relationtocriteria' => $proof['relationtocriteria'],
                'hasrelationtocriteria' => $proof['hasrelationtocriteria'],

                'provenance' => $proof['provenance'],
                'hasprovenance' => $proof['hasprovenance'],

                'sourceauthor' => $proof['sourceauthor'],
                'hassourceauthor' => $proof['hassourceauthor'],
                'source' => $proof['source'],
                'hassource' => $proof['hassource'],
                'sourceurl' => $proof['sourceurl'],
                'hassourceurl' => $proof['hassourceurl'],

                'timecreated' => $proof['timecreated'],
                'timecreatedlabel' => $proof['timecreatedlabel'],
                'hastimecreated' => $proof['hastimecreated'],
                'timemodified' => $proof['timemodified'],
                'timemodifiedlabel' => $proof['timemodifiedlabel'],
                'hastimemodified' => $proof['hastimemodified'],
                'sourcedate' => $proof['sourcedate'],
                'sourcedatelabel' => $proof['sourcedatelabel'],
                'hassourcedate' => $proof['hassourcedate'],

                'aiassisted' => $proof['aiassisted'],
                'ailogavailable' => $proof['ailogavailable'],
                'uncertaintynotes' => $proof['uncertaintynotes'],
                'hasuncertaintynotes' => $proof['hasuncertaintynotes'],

                'filecount' => $proof['filecount'],
                'hasfiles' => $proof['hasfiles'],
                'files' => $proof['files'],

                'viewurl' => $proof['viewurl'],
                'hasviewurl' => $proof['hasviewurl'],
                'editurl' => $proof['editurl'],
                'hasediturl' => $proof['hasediturl'],
                'reviewurl' => $proof['reviewurl'],
                'hasreviewurl' => $proof['hasreviewurl'] && $this->canreview,
                'downloadurl' => $proof['downloadurl'],
                'hasdownloadurl' => $proof['hasdownloadurl'],
            ];
        }

        return $rows;
    }

    /**
     * Build a redacted row for restricted proof.
     *
     * @param array<string, mixed> $proof Normalised proof.
     * @return stdClass
     */
    private function build_restricted_placeholder(array $proof): stdClass {
        return (object)[
            'id' => $proof['id'],
            'userid' => 0,
            'userfullname' => '',
            'hasuserfullname' => false,

            'title' => get_string('restrictedproof', 'uckkchallenge'),
            'summary' => get_string('restrictedproofnotice', 'uckkchallenge'),
            'hassummary' => true,

            'type' => $proof['type'],
            'typelabel' => $proof['typelabel'],
            'typeclass' => $proof['typeclass'],

            'status' => $proof['status'],
            'statuslabel' => $proof['statuslabel'],
            'statusclass' => $proof['statusclass'],

            'visibility' => $proof['visibility'],
            'visibilitylabel' => $proof['visibilitylabel'],
            'visibilityclass' => $proof['visibilityclass'],
            'isrestricted' => true,

            'integritystate' => $proof['integritystate'],
            'integritylabel' => $proof['integritylabel'],
            'integrityclass' => $proof['integrityclass'],
            'hasintegritywarning' => true,

            'relationtocriteria' => '',
            'hasrelationtocriteria' => false,
            'provenance' => '',
            'hasprovenance' => false,
            'sourceauthor' => '',
            'hassourceauthor' => false,
            'source' => '',
            'hassource' => false,
            'sourceurl' => '',
            'hassourceurl' => false,

            'timecreated' => 0,
            'timecreatedlabel' => '',
            'hastimecreated' => false,
            'timemodified' => 0,
            'timemodifiedlabel' => '',
            'hastimemodified' => false,
            'sourcedate' => 0,
            'sourcedatelabel' => '',
            'hassourcedate' => false,

            'aiassisted' => false,
            'ailogavailable' => false,
            'uncertaintynotes' => '',
            'hasuncertaintynotes' => false,

            'filecount' => 0,
            'hasfiles' => false,
            'files' => [],

            'viewurl' => '',
            'hasviewurl' => false,
            'editurl' => '',
            'hasediturl' => false,
            'reviewurl' => '',
            'hasreviewurl' => false,
            'downloadurl' => '',
            'hasdownloadurl' => false,
        ];
    }

    /**
     * Normalise file rows.
     *
     * @param mixed $files Raw files.
     * @return array<int, stdClass>
     */
    private function normalise_files(mixed $files): array {
        if (!is_array($files)) {
            return [];
        }

        $normalised = [];

        foreach ($files as $file) {
            $row = (array)$file;
            $filename = trim((string)($row['filename'] ?? $row['name'] ?? ''));

            if ($filename === '') {
                continue;
            }

            $url = $this->normalise_url_string($row['url'] ?? $row['downloadurl'] ?? null);

            $normalised[] = (object)[
                'filename' => s($filename),
                'filesize' => (int)($row['filesize'] ?? $row['size'] ?? 0),
                'filesizelabel' => !empty($row['filesizelabel'])
                    ? (string)$row['filesizelabel']
                    : display_size((int)($row['filesize'] ?? $row['size'] ?? 0)),
                'mimetype' => s((string)($row['mimetype'] ?? '')),
                'url' => $url,
                'hasurl' => $url !== '',
            ];
        }

        return $normalised;
    }

    /**
     * Check whether there are restricted items.
     *
     * @return bool
     */
    private function has_restricted_items(): bool {
        foreach ($this->proofs as $proof) {
            if (!empty($proof['isrestricted'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether there are integrity warnings.
     *
     * @return bool
     */
    private function has_integrity_warnings(): bool {
        foreach ($this->proofs as $proof) {
            if (!empty($proof['hasintegritywarning'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether there are AI-assisted proof records.
     *
     * @return bool
     */
    private function has_ai_assisted_items(): bool {
        foreach ($this->proofs as $proof) {
            if (!empty($proof['aiassisted'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalise a value against an allowed list.
     *
     * @param string $value Raw value.
     * @param array<int, string> $allowed Allowed values.
     * @param string $default Default.
     * @return string
     */
    private function normalise_choice(string $value, array $allowed, string $default): string {
        $value = clean_param($value, PARAM_ALPHANUMEXT);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Build a language label.
     *
     * @param string $prefix String prefix.
     * @param string $value Canonical value.
     * @return string
     */
    private function get_lang_label(string $prefix, string $value): string {
        $stringkey = $prefix . ':' . $value;

        if (get_string_manager()->string_exists($stringkey, 'uckkchallenge')) {
            return get_string($stringkey, 'uckkchallenge');
        }

        return ucfirst(str_replace('_', ' ', $value));
    }

    /**
     * Return a CSS-safe suffix.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function get_css_suffix(string $value): string {
        return str_replace('_', '-', clean_param($value, PARAM_ALPHANUMEXT));
    }

    /**
     * Normalise a Moodle URL option.
     *
     * @param mixed $url Raw URL.
     * @return moodle_url|null
     */
    private function normalise_moodle_url(mixed $url): ?moodle_url {
        if ($url instanceof moodle_url) {
            return $url;
        }

        if (is_string($url) && trim($url) !== '') {
            return new moodle_url($url);
        }

        return null;
    }

    /**
     * Normalise a URL for template output.
     *
     * @param mixed $url Raw URL.
     * @return string
     */
    private function normalise_url_string(mixed $url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url) && trim($url) !== '') {
            return (new moodle_url($url))->out(false);
        }

        return '';
    }
}