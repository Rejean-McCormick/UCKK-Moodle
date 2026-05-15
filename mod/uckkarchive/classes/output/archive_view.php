<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Main archive view output object.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkarchive\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable main view for a UCKK Archive activity.
 *
 * This class prepares already-filtered display data for
 * mod_uckkarchive/archive_view.mustache.
 *
 * It must not validate archive items, revise records, decide provenance,
 * change visibility, export packages, delete evidence, or perform integrity
 * workflow decisions.
 */
final class archive_view implements renderable, templatable {
    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: validated. */
    public const STATUS_VALIDATED = 'validated';

    /** Status: rejected. */
    public const STATUS_REJECTED = 'rejected';

    /** Status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Status: invalidated. */
    public const STATUS_INVALIDATED = 'invalidated';

    /** Status: closed. */
    public const STATUS_CLOSED = 'closed';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: user. */
    public const VISIBILITY_USER = 'user';

    /** Visibility: group. */
    public const VISIBILITY_GROUP = 'group';

    /** Visibility: course. */
    public const VISIBILITY_COURSE = 'course';

    /** Visibility: cohort. */
    public const VISIBILITY_COHORT = 'cohort';

    /** Visibility: program. */
    public const VISIBILITY_PROGRAM = 'program';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /** Visibility: restricted. */
    public const VISIBILITY_RESTRICTED = 'restricted';

    /** Visibility: restricted integrity. */
    public const VISIBILITY_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Visibility: hidden. */
    public const VISIBILITY_HIDDEN = 'hidden';

    /** Visibility: archived. */
    public const VISIBILITY_ARCHIVED = 'archived';

    /** Validation state: unverified. */
    public const VALIDATION_UNVERIFIED = 'unverified';

    /** Validation state: human reviewed. */
    public const VALIDATION_HUMAN_REVIEWED = 'human_reviewed';

    /** Validation state: verified. */
    public const VALIDATION_VERIFIED = 'verified';

    /** Validation state: contested. */
    public const VALIDATION_CONTESTED = 'contested';

    /** Validation state: invalidated. */
    public const VALIDATION_INVALIDATED = 'invalidated';

    /** Validation state: archived. */
    public const VALIDATION_ARCHIVED = 'archived';

    /**
     * Archive instance id.
     *
     * @var int
     */
    private int $archiveid;

    /**
     * Course module id.
     *
     * @var int
     */
    private int $cmid;

    /**
     * Course id.
     *
     * @var int
     */
    private int $courseid;

    /**
     * Context id.
     *
     * @var int
     */
    private int $contextid;

    /**
     * Archive title.
     *
     * @var string
     */
    private string $title;

    /**
     * Archive intro HTML.
     *
     * @var string
     */
    private string $introhtml;

    /**
     * Prepared archive summary.
     *
     * @var array<string, mixed>
     */
    private array $summary;

    /**
     * Prepared archive item rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $items;

    /**
     * Prepared Kristal rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $kristals;

    /**
     * Prepared proof rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $proofs;

    /**
     * Prepared export rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $exports;

    /**
     * Prepared action rows.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $actions;

    /**
     * Constructor.
     *
     * @param int $archiveid Archive instance id.
     * @param int $cmid Course module id.
     * @param int $courseid Course id.
     * @param int $contextid Moodle context id.
     * @param string $title Archive title.
     * @param string $introhtml Already formatted intro HTML.
     * @param array<string, mixed> $summary Already-filtered summary.
     * @param array<int, array<string, mixed>|stdClass> $items Archive item rows.
     * @param array<int, array<string, mixed>|stdClass> $kristals Kristal rows.
     * @param array<int, array<string, mixed>|stdClass> $proofs Proof rows.
     * @param array<int, array<string, mixed>|stdClass> $exports Export rows.
     * @param array<int, array<string, mixed>|stdClass> $actions Permitted action rows.
     */
    public function __construct(
        int $archiveid,
        int $cmid,
        int $courseid,
        int $contextid,
        string $title,
        string $introhtml = '',
        array $summary = [],
        array $items = [],
        array $kristals = [],
        array $proofs = [],
        array $exports = [],
        array $actions = []
    ) {
        $this->archiveid = max(0, $archiveid);
        $this->cmid = max(0, $cmid);
        $this->courseid = max(0, $courseid);
        $this->contextid = max(0, $contextid);
        $this->title = format_string($title);
        $this->introhtml = $introhtml;
        $this->summary = $summary;
        $this->items = array_map([$this, 'normalise_archive_item'], $items);
        $this->kristals = array_map([$this, 'normalise_kristal'], $kristals);
        $this->proofs = array_map([$this, 'normalise_proof'], $proofs);
        $this->exports = array_map([$this, 'normalise_export'], $exports);
        $this->actions = array_map([$this, 'normalise_action'], $actions);
    }

    /**
     * Export context for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $status = $this->normalise_status((string)($this->summary['status'] ?? self::STATUS_ACTIVE));
        $visibility = $this->normalise_visibility((string)($this->summary['visibility'] ?? self::VISIBILITY_COURSE));
        $validationstate = $this->normalise_validation_state(
            (string)($this->summary['validationstate'] ?? self::VALIDATION_UNVERIFIED)
        );

        $data = new stdClass();
        $data->archiveid = $this->archiveid;
        $data->cmid = $this->cmid;
        $data->courseid = $this->courseid;
        $data->contextid = $this->contextid;
        $data->title = $this->title;
        $data->heading = get_string('archiveview', 'uckkarchive');

        $data->viewurl = (new moodle_url('/mod/uckkarchive/view.php', [
            'id' => $this->cmid,
        ]))->out(false);

        $data->additemurl = (new moodle_url('/mod/uckkarchive/add.php', [
            'id' => $this->cmid,
        ]))->out(false);

        $data->validateurl = (new moodle_url('/mod/uckkarchive/validate.php', [
            'id' => $this->cmid,
        ]))->out(false);

        $data->exporturl = (new moodle_url('/mod/uckkarchive/export.php', [
            'id' => $this->cmid,
        ]))->out(false);

        $data->hasintro = trim($this->introhtml) !== '';
        $data->introhtml = $this->introhtml;

        $data->status = $status;
        $data->statuslabel = $this->get_status_label($status);
        $data->statusclass = 'status-' . str_replace('_', '-', $status);

        $data->visibility = $visibility;
        $data->visibilitylabel = $this->get_visibility_label($visibility);
        $data->visibilityclass = 'visibility-' . str_replace('_', '-', $visibility);

        $data->validationstate = $validationstate;
        $data->validationlabel = $this->get_validation_state_label($validationstate);
        $data->validationclass = 'validation-' . str_replace('_', '-', $validationstate);

        $data->isrestricted = in_array($visibility, [
            self::VISIBILITY_RESTRICTED,
            self::VISIBILITY_RESTRICTED_INTEGRITY,
            self::VISIBILITY_HIDDEN,
        ], true);

        $data->isvalidated = in_array($validationstate, [
            self::VALIDATION_HUMAN_REVIEWED,
            self::VALIDATION_VERIFIED,
            self::VALIDATION_ARCHIVED,
        ], true);

        $data->iscontested = $status === self::STATUS_CONTESTED
            || $validationstate === self::VALIDATION_CONTESTED;

        $data->isinvalidated = $status === self::STATUS_INVALIDATED
            || $validationstate === self::VALIDATION_INVALIDATED;

        $data->timeopen = (int)($this->summary['timeopen'] ?? 0);
        $data->timeclose = (int)($this->summary['timeclose'] ?? 0);
        $data->hastimeopen = $data->timeopen > 0;
        $data->hastimeclose = $data->timeclose > 0;
        $data->timeopenlabel = $data->timeopen > 0 ? userdate($data->timeopen) : '';
        $data->timecloselabel = $data->timeclose > 0 ? userdate($data->timeclose) : '';

        $data->summaryhtml = (string)($this->summary['summaryhtml'] ?? '');
        $data->hassummaryhtml = trim($data->summaryhtml) !== '';

        $data->items = $this->build_archive_item_rows();
        $data->hasitems = !empty($data->items);
        $data->itemcount = count($data->items);

        $data->kristals = $this->build_kristal_rows();
        $data->haskristals = !empty($data->kristals);
        $data->kristalcount = count($data->kristals);

        $data->proofs = $this->build_proof_rows();
        $data->hasproofs = !empty($data->proofs);
        $data->proofcount = count($data->proofs);

        $data->exports = $this->build_export_rows();
        $data->hasexports = !empty($data->exports);
        $data->exportcount = count($data->exports);

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->warnings = $this->normalise_warnings($this->summary['warnings'] ?? []);
        $data->haswarnings = !empty($data->warnings);

        $data->notices = $this->normalise_notices($this->summary['notices'] ?? []);
        $data->hasnotices = !empty($data->notices);

        $data->stats = $this->build_stats($data);
        $data->hasstats = !empty($data->stats);

        $data->emptyitemslabel = get_string('archiveitems:none', 'uckkarchive');
        $data->emptykristalslabel = get_string('kristals:none', 'uckkarchive');
        $data->emptyproofslabel = get_string('proofs:none', 'uckkarchive');
        $data->emptyexportslabel = get_string('exports:none', 'uckkarchive');
        $data->notice = get_string('archivegovernancenotice', 'uckkarchive');

        return $data;
    }

    /**
     * Normalise archive item row.
     *
     * @param array<string, mixed>|stdClass $item Raw row.
     * @return array<string, mixed>
     */
    private function normalise_archive_item(array|stdClass $item): array {
        $row = (array)$item;
        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_DRAFT));
        $visibility = $this->normalise_visibility((string)($row['visibility'] ?? self::VISIBILITY_COURSE));
        $validationstate = $this->normalise_validation_state(
            (string)($row['validationstate'] ?? $row['integritystate'] ?? self::VALIDATION_UNVERIFIED)
        );

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? '')),
            'summary' => format_string((string)($row['summary'] ?? '')),
            'summaryhtml' => (string)($row['summaryhtml'] ?? ''),
            'itemtype' => clean_param((string)($row['itemtype'] ?? $row['type'] ?? 'archive_item'), PARAM_ALPHANUMEXT),
            'itemtypelabel' => format_string((string)($row['itemtypelabel'] ?? '')),
            'status' => $status,
            'statuslabel' => format_string((string)($row['statuslabel'] ?? $this->get_status_label($status))),
            'visibility' => $visibility,
            'visibilitylabel' => format_string((string)($row['visibilitylabel'] ?? $this->get_visibility_label($visibility))),
            'validationstate' => $validationstate,
            'validationlabel' => format_string(
                (string)($row['validationlabel'] ?? $this->get_validation_state_label($validationstate))
            ),
            'provenance' => clean_param((string)($row['provenance'] ?? ''), PARAM_ALPHANUMEXT),
            'provenancelabel' => format_string((string)($row['provenancelabel'] ?? '')),
            'url' => $this->normalise_url($row['url'] ?? null),
            'sourcecomponent' => clean_param((string)($row['sourcecomponent'] ?? ''), PARAM_COMPONENT),
            'sourceid' => max(0, (int)($row['sourceid'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'restricted' => !empty($row['restricted']) || in_array($visibility, [
                self::VISIBILITY_RESTRICTED,
                self::VISIBILITY_RESTRICTED_INTEGRITY,
                self::VISIBILITY_HIDDEN,
            ], true),
        ];
    }

    /**
     * Normalise Kristal row.
     *
     * @param array<string, mixed>|stdClass $kristal Raw row.
     * @return array<string, mixed>
     */
    private function normalise_kristal(array|stdClass $kristal): array {
        $row = (array)$kristal;
        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_DRAFT));
        $validationstate = $this->normalise_validation_state(
            (string)($row['validationstate'] ?? self::VALIDATION_UNVERIFIED)
        );

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? '')),
            'summary' => format_string((string)($row['summary'] ?? '')),
            'content' => (string)($row['content'] ?? ''),
            'kristaltype' => clean_param((string)($row['kristaltype'] ?? $row['type'] ?? 'concept'), PARAM_ALPHANUMEXT),
            'kristaltypelabel' => format_string((string)($row['kristaltypelabel'] ?? '')),
            'status' => $status,
            'statuslabel' => format_string((string)($row['statuslabel'] ?? $this->get_status_label($status))),
            'validationstate' => $validationstate,
            'validationlabel' => format_string(
                (string)($row['validationlabel'] ?? $this->get_validation_state_label($validationstate))
            ),
            'url' => $this->normalise_url($row['url'] ?? null),
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
        ];
    }

    /**
     * Normalise proof row.
     *
     * @param array<string, mixed>|stdClass $proof Raw row.
     * @return array<string, mixed>
     */
    private function normalise_proof(array|stdClass $proof): array {
        $row = (array)$proof;
        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_DRAFT));

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? '')),
            'summary' => format_string((string)($row['summary'] ?? '')),
            'prooftype' => clean_param((string)($row['prooftype'] ?? $row['type'] ?? 'text'), PARAM_ALPHANUMEXT),
            'prooftypelabel' => format_string((string)($row['prooftypelabel'] ?? '')),
            'status' => $status,
            'statuslabel' => format_string((string)($row['statuslabel'] ?? $this->get_status_label($status))),
            'provenance' => clean_param((string)($row['provenance'] ?? ''), PARAM_ALPHANUMEXT),
            'provenancelabel' => format_string((string)($row['provenancelabel'] ?? '')),
            'url' => $this->normalise_url($row['url'] ?? null),
            'hasfiles' => !empty($row['hasfiles']),
            'files' => $this->normalise_files($row['files'] ?? []),
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
        ];
    }

    /**
     * Normalise export row.
     *
     * @param array<string, mixed>|stdClass $export Raw row.
     * @return array<string, mixed>
     */
    private function normalise_export(array|stdClass $export): array {
        $row = (array)$export;
        $status = $this->normalise_status((string)($row['status'] ?? self::STATUS_DRAFT));

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? '')),
            'exporttype' => clean_param((string)($row['exporttype'] ?? $row['type'] ?? 'package'), PARAM_ALPHANUMEXT),
            'exporttypelabel' => format_string((string)($row['exporttypelabel'] ?? '')),
            'status' => $status,
            'statuslabel' => format_string((string)($row['statuslabel'] ?? $this->get_status_label($status))),
            'url' => $this->normalise_url($row['url'] ?? null),
            'downloadurl' => $this->normalise_url($row['downloadurl'] ?? null),
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
        ];
    }

    /**
     * Normalise action row.
     *
     * @param array<string, mixed>|stdClass $action Raw action.
     * @return array<string, mixed>
     */
    private function normalise_action(array|stdClass $action): array {
        $row = (array)$action;
        $key = clean_param((string)($row['key'] ?? $row['action'] ?? ''), PARAM_ALPHANUMEXT);
        $method = strtoupper(clean_param((string)($row['method'] ?? 'get'), PARAM_ALPHA));

        if (!in_array($method, ['GET', 'POST'], true)) {
            $method = 'GET';
        }

        return [
            'key' => $key,
            'label' => format_string((string)($row['label'] ?? $this->get_action_label($key))),
            'url' => $this->normalise_url($row['url'] ?? null),
            'method' => $method,
            'primary' => !empty($row['primary']),
            'danger' => !empty($row['danger']),
            'disabled' => !empty($row['disabled']),
            'disabledreason' => format_string((string)($row['disabledreason'] ?? '')),
            'requiresconfirmation' => !empty($row['requiresconfirmation']),
            'confirmmessage' => format_string((string)($row['confirmmessage'] ?? '')),
        ];
    }

    /**
     * Build archive item rows.
     *
     * @return array<int, stdClass>
     */
    private function build_archive_item_rows(): array {
        $rows = [];

        foreach ($this->items as $item) {
            if ($item['title'] === '') {
                continue;
            }

            $rows[] = (object)[
                'id' => $item['id'],
                'title' => $item['title'],
                'summary' => $item['summary'],
                'hassummary' => $item['summary'] !== '',
                'summaryhtml' => $item['summaryhtml'],
                'hassummaryhtml' => trim($item['summaryhtml']) !== '',
                'itemtype' => $item['itemtype'],
                'itemtypelabel' => $item['itemtypelabel'] !== ''
                    ? $item['itemtypelabel']
                    : $this->get_item_type_label($item['itemtype']),
                'status' => $item['status'],
                'statuslabel' => $item['statuslabel'],
                'statusclass' => 'status-' . str_replace('_', '-', $item['status']),
                'visibility' => $item['visibility'],
                'visibilitylabel' => $item['visibilitylabel'],
                'visibilityclass' => 'visibility-' . str_replace('_', '-', $item['visibility']),
                'validationstate' => $item['validationstate'],
                'validationlabel' => $item['validationlabel'],
                'provenance' => $item['provenance'],
                'provenancelabel' => $item['provenancelabel'],
                'hasprovenance' => $item['provenance'] !== '',
                'url' => $item['url'],
                'hasurl' => $item['url'] !== '',
                'sourcecomponent' => $item['sourcecomponent'],
                'sourceid' => $item['sourceid'],
                'hassource' => $item['sourcecomponent'] !== '' || $item['sourceid'] > 0,
                'timecreated' => $item['timecreated'],
                'timecreatedlabel' => $item['timecreated'] > 0 ? userdate($item['timecreated']) : '',
                'hastimecreated' => $item['timecreated'] > 0,
                'timemodified' => $item['timemodified'],
                'timemodifiedlabel' => $item['timemodified'] > 0 ? userdate($item['timemodified']) : '',
                'hastimemodified' => $item['timemodified'] > 0,
                'restricted' => $item['restricted'],
            ];
        }

        return $rows;
    }

    /**
     * Build Kristal rows.
     *
     * @return array<int, stdClass>
     */
    private function build_kristal_rows(): array {
        $rows = [];

        foreach ($this->kristals as $kristal) {
            if ($kristal['title'] === '') {
                continue;
            }

            $rows[] = (object)[
                'id' => $kristal['id'],
                'title' => $kristal['title'],
                'summary' => $kristal['summary'],
                'hassummary' => $kristal['summary'] !== '',
                'content' => $kristal['content'],
                'hascontent' => trim($kristal['content']) !== '',
                'kristaltype' => $kristal['kristaltype'],
                'kristaltypelabel' => $kristal['kristaltypelabel'] !== ''
                    ? $kristal['kristaltypelabel']
                    : $this->get_kristal_type_label($kristal['kristaltype']),
                'status' => $kristal['status'],
                'statuslabel' => $kristal['statuslabel'],
                'statusclass' => 'status-' . str_replace('_', '-', $kristal['status']),
                'validationstate' => $kristal['validationstate'],
                'validationlabel' => $kristal['validationlabel'],
                'url' => $kristal['url'],
                'hasurl' => $kristal['url'] !== '',
                'timecreated' => $kristal['timecreated'],
                'timecreatedlabel' => $kristal['timecreated'] > 0 ? userdate($kristal['timecreated']) : '',
                'hastimecreated' => $kristal['timecreated'] > 0,
                'timemodified' => $kristal['timemodified'],
                'timemodifiedlabel' => $kristal['timemodified'] > 0 ? userdate($kristal['timemodified']) : '',
                'hastimemodified' => $kristal['timemodified'] > 0,
            ];
        }

        return $rows;
    }

    /**
     * Build proof rows.
     *
     * @return array<int, stdClass>
     */
    private function build_proof_rows(): array {
        $rows = [];

        foreach ($this->proofs as $proof) {
            if ($proof['title'] === '') {
                continue;
            }

            $rows[] = (object)[
                'id' => $proof['id'],
                'title' => $proof['title'],
                'summary' => $proof['summary'],
                'hassummary' => $proof['summary'] !== '',
                'prooftype' => $proof['prooftype'],
                'prooftypelabel' => $proof['prooftypelabel'] !== ''
                    ? $proof['prooftypelabel']
                    : $this->get_proof_type_label($proof['prooftype']),
                'status' => $proof['status'],
                'statuslabel' => $proof['statuslabel'],
                'statusclass' => 'status-' . str_replace('_', '-', $proof['status']),
                'provenance' => $proof['provenance'],
                'provenancelabel' => $proof['provenancelabel'],
                'hasprovenance' => $proof['provenance'] !== '',
                'url' => $proof['url'],
                'hasurl' => $proof['url'] !== '',
                'hasfiles' => $proof['hasfiles'],
                'files' => $proof['files'],
                'timecreated' => $proof['timecreated'],
                'timecreatedlabel' => $proof['timecreated'] > 0 ? userdate($proof['timecreated']) : '',
                'hastimecreated' => $proof['timecreated'] > 0,
                'timemodified' => $proof['timemodified'],
                'timemodifiedlabel' => $proof['timemodified'] > 0 ? userdate($proof['timemodified']) : '',
                'hastimemodified' => $proof['timemodified'] > 0,
            ];
        }

        return $rows;
    }

    /**
     * Build export rows.
     *
     * @return array<int, stdClass>
     */
    private function build_export_rows(): array {
        $rows = [];

        foreach ($this->exports as $export) {
            if ($export['title'] === '') {
                continue;
            }

            $rows[] = (object)[
                'id' => $export['id'],
                'title' => $export['title'],
                'exporttype' => $export['exporttype'],
                'exporttypelabel' => $export['exporttypelabel'] !== ''
                    ? $export['exporttypelabel']
                    : $this->get_export_type_label($export['exporttype']),
                'status' => $export['status'],
                'statuslabel' => $export['statuslabel'],
                'statusclass' => 'status-' . str_replace('_', '-', $export['status']),
                'url' => $export['url'],
                'hasurl' => $export['url'] !== '',
                'downloadurl' => $export['downloadurl'],
                'hasdownloadurl' => $export['downloadurl'] !== '',
                'timecreated' => $export['timecreated'],
                'timecreatedlabel' => $export['timecreated'] > 0 ? userdate($export['timecreated']) : '',
                'hastimecreated' => $export['timecreated'] > 0,
                'timemodified' => $export['timemodified'],
                'timemodifiedlabel' => $export['timemodified'] > 0 ? userdate($export['timemodified']) : '',
                'hastimemodified' => $export['timemodified'] > 0,
            ];
        }

        return $rows;
    }

    /**
     * Build action rows.
     *
     * @return array<int, stdClass>
     */
    private function build_action_rows(): array {
        $rows = [];

        foreach ($this->actions as $action) {
            if ($action['key'] === '' || $action['url'] === '') {
                continue;
            }

            $rows[] = (object)[
                'key' => $action['key'],
                'label' => $action['label'],
                'url' => $action['url'],
                'method' => $action['method'],
                'ispost' => $action['method'] === 'POST',
                'isget' => $action['method'] === 'GET',
                'primary' => $action['primary'],
                'danger' => $action['danger'],
                'secondary' => !$action['primary'] && !$action['danger'],
                'disabled' => $action['disabled'],
                'disabledreason' => $action['disabledreason'],
                'hasdisabledreason' => trim($action['disabledreason']) !== '',
                'requiresconfirmation' => $action['requiresconfirmation'],
                'confirmmessage' => $action['confirmmessage'],
                'hasconfirmmessage' => trim($action['confirmmessage']) !== '',
            ];
        }

        return $rows;
    }

    /**
     * Build summary statistics.
     *
     * @param stdClass $data Exported data.
     * @return array<int, stdClass>
     */
    private function build_stats(stdClass $data): array {
        return [
            (object)[
                'key' => 'items',
                'label' => get_string('archiveitems', 'uckkarchive'),
                'value' => (string)$data->itemcount,
                'class' => $data->itemcount > 0 ? 'state-active' : 'state-empty',
            ],
            (object)[
                'key' => 'kristals',
                'label' => get_string('kristals', 'uckkarchive'),
                'value' => (string)$data->kristalcount,
                'class' => $data->kristalcount > 0 ? 'state-active' : 'state-empty',
            ],
            (object)[
                'key' => 'proofs',
                'label' => get_string('proofs', 'uckkarchive'),
                'value' => (string)$data->proofcount,
                'class' => $data->proofcount > 0 ? 'state-active' : 'state-empty',
            ],
            (object)[
                'key' => 'validation',
                'label' => get_string('validationstate', 'uckkarchive'),
                'value' => $data->validationlabel,
                'class' => $data->validationclass,
            ],
        ];
    }

    /**
     * Normalise file rows.
     *
     * @param mixed $files File rows.
     * @return array<int, stdClass>
     */
    private function normalise_files(mixed $files): array {
        if (!is_array($files)) {
            return [];
        }

        $rows = [];

        foreach ($files as $file) {
            $row = (array)$file;
            $filename = format_string((string)($row['filename'] ?? $row['name'] ?? ''));
            $url = $this->normalise_url($row['url'] ?? null);

            if ($filename === '' || $url === '') {
                continue;
            }

            $rows[] = (object)[
                'filename' => $filename,
                'url' => $url,
                'mimetype' => clean_param((string)($row['mimetype'] ?? ''), PARAM_TEXT),
                'filesize' => max(0, (int)($row['filesize'] ?? 0)),
                'filesizelabel' => !empty($row['filesizelabel'])
                    ? format_string((string)$row['filesizelabel'])
                    : '',
            ];
        }

        return $rows;
    }

    /**
     * Normalise warnings.
     *
     * @param mixed $warnings Warning list.
     * @return array<int, stdClass>
     */
    private function normalise_warnings(mixed $warnings): array {
        return $this->normalise_messages($warnings, 'warning');
    }

    /**
     * Normalise notices.
     *
     * @param mixed $notices Notice list.
     * @return array<int, stdClass>
     */
    private function normalise_notices(mixed $notices): array {
        return $this->normalise_messages($notices, 'info');
    }

    /**
     * Normalise message rows.
     *
     * @param mixed $messages Message list.
     * @param string $defaultseverity Default severity.
     * @return array<int, stdClass>
     */
    private function normalise_messages(mixed $messages, string $defaultseverity): array {
        if (!is_array($messages)) {
            return [];
        }

        $rows = [];

        foreach ($messages as $message) {
            if (is_string($message)) {
                $text = trim($message);
                $severity = $defaultseverity;
            } else {
                $row = (array)$message;
                $text = trim((string)($row['message'] ?? ''));
                $severity = clean_param((string)($row['severity'] ?? $defaultseverity), PARAM_ALPHANUMEXT);
            }

            if ($text === '') {
                continue;
            }

            $rows[] = (object)[
                'message' => format_string($text),
                'severity' => $severity,
                'class' => 'alert-' . $this->get_alert_class($severity),
            ];
        }

        return $rows;
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_ACTIVE;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private function normalise_visibility(string $visibility): string {
        $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

        $allowed = [
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_USER,
            self::VISIBILITY_GROUP,
            self::VISIBILITY_COURSE,
            self::VISIBILITY_COHORT,
            self::VISIBILITY_PROGRAM,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_RESTRICTED,
            self::VISIBILITY_RESTRICTED_INTEGRITY,
            self::VISIBILITY_HIDDEN,
            self::VISIBILITY_ARCHIVED,
        ];

        return in_array($visibility, $allowed, true) ? $visibility : self::VISIBILITY_COURSE;
    }

    /**
     * Normalise validation state.
     *
     * @param string $state Raw validation state.
     * @return string
     */
    private function normalise_validation_state(string $state): string {
        $state = clean_param($state, PARAM_ALPHANUMEXT);

        $allowed = [
            self::VALIDATION_UNVERIFIED,
            self::VALIDATION_HUMAN_REVIEWED,
            self::VALIDATION_VERIFIED,
            self::VALIDATION_CONTESTED,
            self::VALIDATION_INVALIDATED,
            self::VALIDATION_ARCHIVED,
        ];

        return in_array($state, $allowed, true) ? $state : self::VALIDATION_UNVERIFIED;
    }

    /**
     * Get status label.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_label(string $status): string {
        return $this->get_component_string('status:' . $status, ucfirst(str_replace('_', ' ', $status)));
    }

    /**
     * Get visibility label.
     *
     * @param string $visibility Visibility.
     * @return string
     */
    private function get_visibility_label(string $visibility): string {
        return $this->get_component_string('visibility:' . $visibility, ucfirst(str_replace('_', ' ', $visibility)));
    }

    /**
     * Get validation state label.
     *
     * @param string $state Validation state.
     * @return string
     */
    private function get_validation_state_label(string $state): string {
        return $this->get_component_string('validation:' . $state, ucfirst(str_replace('_', ' ', $state)));
    }

    /**
     * Get archive item type label.
     *
     * @param string $type Item type.
     * @return string
     */
    private function get_item_type_label(string $type): string {
        return $this->get_component_string('itemtype:' . $type, ucfirst(str_replace('_', ' ', $type)));
    }

    /**
     * Get Kristal type label.
     *
     * @param string $type Kristal type.
     * @return string
     */
    private function get_kristal_type_label(string $type): string {
        return $this->get_component_string('kristaltype:' . $type, ucfirst(str_replace('_', ' ', $type)));
    }

    /**
     * Get proof type label.
     *
     * @param string $type Proof type.
     * @return string
     */
    private function get_proof_type_label(string $type): string {
        return $this->get_component_string('prooftype:' . $type, ucfirst(str_replace('_', ' ', $type)));
    }

    /**
     * Get export type label.
     *
     * @param string $type Export type.
     * @return string
     */
    private function get_export_type_label(string $type): string {
        return $this->get_component_string('exporttype:' . $type, ucfirst(str_replace('_', ' ', $type)));
    }

    /**
     * Get action label.
     *
     * @param string $key Action key.
     * @return string
     */
    private function get_action_label(string $key): string {
        if ($key === '') {
            return get_string('view');
        }

        return $this->get_component_string('action:' . $key, ucfirst(str_replace('_', ' ', $key)));
    }

    /**
     * Get component string with fallback.
     *
     * @param string $key String key.
     * @param string $fallback Fallback.
     * @return string
     */
    private function get_component_string(string $key, string $fallback): string {
        if (get_string_manager()->string_exists($key, 'uckkarchive')) {
            return get_string($key, 'uckkarchive');
        }

        return $fallback;
    }

    /**
     * Map severity to Bootstrap alert suffix.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function get_alert_class(string $severity): string {
        return match ($severity) {
            'danger', 'error', 'invalidated', 'restricted' => 'danger',
            'success', 'validated', 'verified' => 'success',
            'info', 'notice' => 'info',
            default => 'warning',
        };
    }

    /**
     * Normalise a URL.
     *
     * @param mixed $url Raw URL.
     * @return string
     */
    private function normalise_url(mixed $url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url) && trim($url) !== '') {
            return (new moodle_url($url))->out(false);
        }

        return '';
    }
}
