<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Archive item card output object.
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
 * Renderable card for one UCKK archive item.
 *
 * This class prepares display data only. It does not decide permissions,
 * validate archive records, revise archive history, export files, open
 * integrity cases, or make AI/human authority decisions.
 */
final class archive_item_card implements renderable, templatable {
    /** Archive item type: proof. */
    public const TYPE_PROOF = 'proof';

    /** Archive item type: decision. */
    public const TYPE_DECISION = 'decision';

    /** Archive item type: course work. */
    public const TYPE_COURSE_WORK = 'course_work';

    /** Archive item type: challenge result. */
    public const TYPE_CHALLENGE_RESULT = 'challenge_result';

    /** Archive item type: assembly minutes. */
    public const TYPE_ASSEMBLY_MINUTES = 'assembly_minutes';

    /** Archive item type: integrity case summary. */
    public const TYPE_INTEGRITY_CASE_SUMMARY = 'integrity_case_summary';

    /** Archive item type: Kristal. */
    public const TYPE_KRISTAL = 'kristal';

    /** Archive item type: reflection. */
    public const TYPE_REFLECTION = 'reflection';

    /** Archive item type: portfolio item. */
    public const TYPE_PORTFOLIO_ITEM = 'portfolio_item';

    /** Archive item type: version record. */
    public const TYPE_VERSION_RECORD = 'version_record';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Status: pending. */
    public const STATUS_PENDING = 'pending';

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

    /** Status: cancelled. */
    public const STATUS_CANCELLED = 'cancelled';

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

    /** Provenance: human. */
    public const PROVENANCE_HUMAN = 'human';

    /** Provenance: AI-assisted. */
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';

    /** Provenance: imported. */
    public const PROVENANCE_IMPORTED = 'imported';

    /** Provenance: system. */
    public const PROVENANCE_SYSTEM = 'system';

    /** Provenance: archive. */
    public const PROVENANCE_ARCHIVE = 'archive';

    /** Provenance: assembly. */
    public const PROVENANCE_ASSEMBLY = 'assembly';

    /** Provenance: challenge. */
    public const PROVENANCE_CHALLENGE = 'challenge';

    /** Provenance: integrity. */
    public const PROVENANCE_INTEGRITY = 'integrity';

    /**
     * Archive item data.
     *
     * @var array<string, mixed>
     */
    private array $item;

    /**
     * Proof rows already filtered for the viewer.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $proofs;

    /**
     * Action rows already filtered for the viewer.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $actions;

    /**
     * Warning rows already filtered for the viewer.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $warnings;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|stdClass $item Archive item data.
     * @param array<int, array<string, mixed>|stdClass> $proofs Permission-filtered proof summaries.
     * @param array<int, array<string, mixed>|stdClass> $actions Permission-filtered action rows.
     * @param array<int, array<string, mixed>|stdClass|string> $warnings Permission-filtered warnings.
     */
    public function __construct(
        array|stdClass $item,
        array $proofs = [],
        array $actions = [],
        array $warnings = []
    ) {
        $this->item = $this->normalise_item($item);
        $this->proofs = array_map([$this, 'normalise_proof'], $proofs);
        $this->actions = array_map([$this, 'normalise_action'], $actions);
        $this->warnings = $this->normalise_warnings($warnings);
    }

    /**
     * Export context for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $data->id = $this->item['id'];
        $data->archiveid = $this->item['archiveid'];
        $data->courseid = $this->item['courseid'];
        $data->cmid = $this->item['cmid'];
        $data->contextid = $this->item['contextid'];

        $data->itemtype = $this->item['itemtype'];
        $data->itemtypelabel = $this->get_item_type_label($this->item['itemtype']);
        $data->itemtypeclass = $this->get_css_class('archive-item-type', $this->item['itemtype']);

        $data->title = $this->item['title'];
        $data->summary = $this->item['summary'];
        $data->hassummary = $data->summary !== '';

        $data->status = $this->item['status'];
        $data->statuslabel = $this->get_status_label($this->item['status']);
        $data->statusclass = $this->get_css_class('status', $this->item['status']);

        $data->validationstate = $this->item['validationstate'];
        $data->validationlabel = $this->get_validation_label($this->item['validationstate']);
        $data->validationclass = $this->get_css_class('validation', $this->item['validationstate']);

        $data->visibility = $this->item['visibility'];
        $data->visibilitylabel = $this->get_visibility_label($this->item['visibility']);
        $data->visibilityclass = $this->get_css_class('visibility', $this->item['visibility']);

        $data->provenance = $this->item['provenance'];
        $data->provenancelabel = $this->get_provenance_label($this->item['provenance']);
        $data->provenanceclass = $this->get_css_class('provenance', $this->item['provenance']);

        $data->sourcecomponent = $this->item['sourcecomponent'];
        $data->hassourcecomponent = $data->sourcecomponent !== '';
        $data->sourceitemid = $this->item['sourceitemid'];
        $data->hassourceitemid = $data->sourceitemid > 0;
        $data->sourceurl = $this->item['sourceurl'];
        $data->hassourceurl = $data->sourceurl !== '';
        $data->sourceauthor = $this->item['sourceauthor'];
        $data->hassourceauthor = $data->sourceauthor !== '';
        $data->sourcedate = $this->item['sourcedate'];
        $data->sourcedatelabel = $this->item['sourcedate'] > 0 ? userdate($this->item['sourcedate']) : '';
        $data->hassourcedate = $data->sourcedatelabel !== '';

        $data->url = $this->item['url'];
        $data->hasurl = $data->url !== '';
        $data->viewurl = $this->build_item_url();
        $data->hasviewurl = $data->viewurl !== '';

        $data->kristalid = $this->item['kristalid'];
        $data->haskristal = $data->kristalid > 0;
        $data->kristaltitle = $this->item['kristaltitle'];
        $data->kristalurl = $this->item['kristalurl'];
        $data->haskristalurl = $data->kristalurl !== '';

        $data->versionno = $this->item['versionno'];
        $data->versionlabel = get_string('versionnumber', 'uckkarchive', $data->versionno);
        $data->hasversion = $data->versionno > 0;

        $data->timecreated = $this->item['timecreated'];
        $data->timecreatedlabel = $this->item['timecreated'] > 0 ? userdate($this->item['timecreated']) : '';
        $data->hastimecreated = $data->timecreatedlabel !== '';

        $data->timemodified = $this->item['timemodified'];
        $data->timemodifiedlabel = $this->item['timemodified'] > 0 ? userdate($this->item['timemodified']) : '';
        $data->hastimemodified = $data->timemodifiedlabel !== '';

        $data->validatedby = $this->item['validatedby'];
        $data->validatorlabel = $this->item['validatorlabel'];
        $data->hasvalidator = $data->validatorlabel !== '';
        $data->timevalidated = $this->item['timevalidated'];
        $data->timevalidatedlabel = $this->item['timevalidated'] > 0 ? userdate($this->item['timevalidated']) : '';
        $data->hastimevalidated = $data->timevalidatedlabel !== '';

        $data->isvalidated = $this->is_validated();
        $data->iscontested = $this->is_contested();
        $data->isinvalidated = $this->is_invalidated();
        $data->isrestricted = $this->is_restricted();
        $data->isarchived = $this->item['status'] === self::STATUS_ARCHIVED
            || $this->item['validationstate'] === self::VALIDATION_ARCHIVED;
        $data->isaiassisted = $this->item['provenance'] === self::PROVENANCE_AI_ASSISTED;

        $data->proofs = $this->build_proof_rows();
        $data->proofcount = count($data->proofs);
        $data->hasproofs = $data->proofcount > 0;

        $data->warnings = $this->build_warning_rows();
        $data->haswarnings = !empty($data->warnings);

        $data->actions = $this->build_action_rows();
        $data->hasactions = !empty($data->actions);

        $data->badges = $this->build_badge_rows();
        $data->hasbadges = !empty($data->badges);

        $data->notice = $this->get_notice();

        return $data;
    }

    /**
     * Normalise archive item data.
     *
     * @param array<string, mixed>|stdClass $item Raw item data.
     * @return array<string, mixed>
     */
    private function normalise_item(array|stdClass $item): array {
        $row = (array)$item;

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'archiveid' => max(0, (int)($row['archiveid'] ?? $row['uckkarchiveid'] ?? 0)),
            'courseid' => max(0, (int)($row['courseid'] ?? 0)),
            'cmid' => max(0, (int)($row['cmid'] ?? 0)),
            'contextid' => max(0, (int)($row['contextid'] ?? 0)),
            'userid' => max(0, (int)($row['userid'] ?? 0)),

            'itemtype' => $this->normalise_item_type((string)($row['itemtype'] ?? $row['type'] ?? self::TYPE_PROOF)),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? '')),
            'summary' => format_string((string)($row['summary'] ?? '')),

            'status' => $this->normalise_status((string)($row['status'] ?? self::STATUS_DRAFT)),
            'validationstate' => $this->normalise_validation_state(
                (string)($row['validationstate'] ?? $row['validation'] ?? self::VALIDATION_UNVERIFIED)
            ),
            'visibility' => $this->normalise_visibility((string)($row['visibility'] ?? self::VISIBILITY_COURSE)),
            'provenance' => $this->normalise_provenance((string)($row['provenance'] ?? self::PROVENANCE_HUMAN)),

            'sourcecomponent' => clean_param((string)($row['sourcecomponent'] ?? ''), PARAM_COMPONENT),
            'sourceitemid' => max(0, (int)($row['sourceitemid'] ?? 0)),
            'sourceurl' => $this->normalise_url($row['sourceurl'] ?? ''),
            'sourceauthor' => format_string((string)($row['sourceauthor'] ?? '')),
            'sourcedate' => max(0, (int)($row['sourcedate'] ?? 0)),

            'url' => $this->normalise_url($row['url'] ?? ''),
            'kristalid' => max(0, (int)($row['kristalid'] ?? 0)),
            'kristaltitle' => format_string((string)($row['kristaltitle'] ?? '')),
            'kristalurl' => $this->normalise_url($row['kristalurl'] ?? ''),

            'versionno' => max(1, (int)($row['versionno'] ?? 1)),
            'validatedby' => max(0, (int)($row['validatedby'] ?? 0)),
            'validatorlabel' => format_string((string)($row['validatorlabel'] ?? '')),
            'timevalidated' => max(0, (int)($row['timevalidated'] ?? 0)),
            'timecreated' => max(0, (int)($row['timecreated'] ?? 0)),
            'timemodified' => max(0, (int)($row['timemodified'] ?? 0)),
        ];
    }

    /**
     * Normalise proof summary row.
     *
     * @param array<string, mixed>|stdClass $proof Raw proof data.
     * @return array<string, mixed>
     */
    private function normalise_proof(array|stdClass $proof): array {
        $row = (array)$proof;

        $prooftype = clean_param((string)($row['prooftype'] ?? $row['type'] ?? 'text'), PARAM_ALPHANUMEXT);
        $validationstate = $this->normalise_validation_state(
            (string)($row['validationstate'] ?? $row['validation'] ?? self::VALIDATION_UNVERIFIED)
        );
        $visibility = $this->normalise_visibility((string)($row['visibility'] ?? self::VISIBILITY_COURSE));

        return [
            'id' => max(0, (int)($row['id'] ?? 0)),
            'prooftype' => $prooftype,
            'prooftypelabel' => $this->get_proof_type_label($prooftype),
            'title' => format_string((string)($row['title'] ?? $row['name'] ?? '')),
            'summary' => format_string((string)($row['summary'] ?? '')),
            'hassummary' => trim((string)($row['summary'] ?? '')) !== '',
            'validationstate' => $validationstate,
            'validationlabel' => $this->get_validation_label($validationstate),
            'visibility' => $visibility,
            'visibilitylabel' => $this->get_visibility_label($visibility),
            'url' => $this->normalise_url($row['url'] ?? ''),
            'hasurl' => trim((string)($row['url'] ?? '')) !== '',
            'fileurl' => $this->normalise_url($row['fileurl'] ?? ''),
            'hasfileurl' => trim((string)($row['fileurl'] ?? '')) !== '',
            'filename' => format_string((string)($row['filename'] ?? '')),
            'hasfilename' => trim((string)($row['filename'] ?? '')) !== '',
            'restricted' => in_array($visibility, [
                self::VISIBILITY_PRIVATE,
                self::VISIBILITY_RESTRICTED,
                self::VISIBILITY_RESTRICTED_INTEGRITY,
                self::VISIBILITY_HIDDEN,
            ], true),
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
            'url' => $this->normalise_url($row['url'] ?? ''),
            'method' => $method,
            'primary' => !empty($row['primary']),
            'danger' => !empty($row['danger']),
            'disabled' => !empty($row['disabled']),
            'disabledreason' => format_string((string)($row['disabledreason'] ?? '')),
            'requiresconfirmation' => !empty($row['requiresconfirmation']),
            'confirmmessage' => format_string((string)($row['confirmmessage'] ?? '')),
            'sesskey' => (string)($row['sesskey'] ?? sesskey()),
        ];
    }

    /**
     * Build proof rows.
     *
     * @return array<int, stdClass>
     */
    private function build_proof_rows(): array {
        $rows = [];

        foreach ($this->proofs as $proof) {
            $rows[] = (object)[
                'id' => $proof['id'],
                'prooftype' => $proof['prooftype'],
                'prooftypelabel' => $proof['prooftypelabel'],
                'title' => $proof['title'],
                'summary' => $proof['summary'],
                'hassummary' => $proof['hassummary'],
                'validationstate' => $proof['validationstate'],
                'validationlabel' => $proof['validationlabel'],
                'validationclass' => $this->get_css_class('validation', $proof['validationstate']),
                'visibility' => $proof['visibility'],
                'visibilitylabel' => $proof['visibilitylabel'],
                'visibilityclass' => $this->get_css_class('visibility', $proof['visibility']),
                'url' => $proof['url'],
                'hasurl' => $proof['hasurl'],
                'fileurl' => $proof['fileurl'],
                'hasfileurl' => $proof['hasfileurl'],
                'filename' => $proof['filename'],
                'hasfilename' => $proof['hasfilename'],
                'restricted' => $proof['restricted'],
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
                'hasdisabledreason' => $action['disabledreason'] !== '',
                'requiresconfirmation' => $action['requiresconfirmation'],
                'confirmmessage' => $action['confirmmessage'],
                'hasconfirmmessage' => $action['confirmmessage'] !== '',
                'sesskey' => $action['sesskey'],
                'itemid' => $this->item['id'],
            ];
        }

        return $rows;
    }

    /**
     * Build warning rows.
     *
     * @return array<int, stdClass>
     */
    private function build_warning_rows(): array {
        $rows = [];

        foreach ($this->warnings as $warning) {
            $rows[] = (object)[
                'message' => $warning['message'],
                'severity' => $warning['severity'],
                'class' => 'alert-' . $this->get_warning_class($warning['severity']),
            ];
        }

        return $rows;
    }

    /**
     * Build badge rows.
     *
     * @return array<int, stdClass>
     */
    private function build_badge_rows(): array {
        $badges = [];

        if ($this->is_restricted()) {
            $badges[] = (object)[
                'key' => 'restricted',
                'label' => get_string('badge:restricted', 'uckkarchive'),
                'class' => 'badge-warning',
            ];
        }

        if ($this->is_validated()) {
            $badges[] = (object)[
                'key' => 'validated',
                'label' => get_string('badge:validated', 'uckkarchive'),
                'class' => 'badge-success',
            ];
        }

        if ($this->is_contested()) {
            $badges[] = (object)[
                'key' => 'contested',
                'label' => get_string('badge:contested', 'uckkarchive'),
                'class' => 'badge-warning',
            ];
        }

        if ($this->is_invalidated()) {
            $badges[] = (object)[
                'key' => 'invalidated',
                'label' => get_string('badge:invalidated', 'uckkarchive'),
                'class' => 'badge-danger',
            ];
        }

        if ($this->item['provenance'] === self::PROVENANCE_AI_ASSISTED) {
            $badges[] = (object)[
                'key' => 'ai_assisted',
                'label' => get_string('badge:aiassisted', 'uckkarchive'),
                'class' => 'badge-info',
            ];
        }

        return $badges;
    }

    /**
     * Normalise warning rows.
     *
     * @param array<int, array<string, mixed>|stdClass|string> $warnings Warning data.
     * @return array<int, array<string, string>>
     */
    private function normalise_warnings(array $warnings): array {
        $rows = [];

        foreach ($warnings as $warning) {
            if (is_string($warning)) {
                $message = trim($warning);
                $severity = 'warning';
            } else {
                $row = (array)$warning;
                $message = trim((string)($row['message'] ?? ''));
                $severity = clean_param((string)($row['severity'] ?? 'warning'), PARAM_ALPHANUMEXT);
            }

            if ($message === '') {
                continue;
            }

            $rows[] = [
                'message' => format_string($message),
                'severity' => $severity,
            ];
        }

        return $rows;
    }

    /**
     * Build archive item URL.
     *
     * @return string
     */
    private function build_item_url(): string {
        if ($this->item['cmid'] <= 0 || $this->item['id'] <= 0) {
            return '';
        }

        return (new moodle_url('/mod/uckkarchive/item.php', [
            'id' => $this->item['cmid'],
            'itemid' => $this->item['id'],
        ]))->out(false);
    }

    /**
     * Return notice text.
     *
     * @return string
     */
    private function get_notice(): string {
        if ($this->item['provenance'] === self::PROVENANCE_AI_ASSISTED) {
            return get_string('notice:aiassisted', 'uckkarchive');
        }

        if ($this->is_restricted()) {
            return get_string('notice:restricted', 'uckkarchive');
        }

        return get_string('notice:archiveitem', 'uckkarchive');
    }

    /**
     * Whether item is validated.
     *
     * @return bool
     */
    private function is_validated(): bool {
        return in_array($this->item['validationstate'], [
            self::VALIDATION_HUMAN_REVIEWED,
            self::VALIDATION_VERIFIED,
            self::VALIDATION_ARCHIVED,
        ], true) || $this->item['status'] === self::STATUS_VALIDATED;
    }

    /**
     * Whether item is contested.
     *
     * @return bool
     */
    private function is_contested(): bool {
        return $this->item['status'] === self::STATUS_CONTESTED
            || $this->item['validationstate'] === self::VALIDATION_CONTESTED;
    }

    /**
     * Whether item is invalidated.
     *
     * @return bool
     */
    private function is_invalidated(): bool {
        return $this->item['status'] === self::STATUS_INVALIDATED
            || $this->item['validationstate'] === self::VALIDATION_INVALIDATED;
    }

    /**
     * Whether item is restricted.
     *
     * @return bool
     */
    private function is_restricted(): bool {
        return in_array($this->item['visibility'], [
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_RESTRICTED,
            self::VISIBILITY_RESTRICTED_INTEGRITY,
            self::VISIBILITY_HIDDEN,
        ], true);
    }

    /**
     * Get allowed item types.
     *
     * @return string[]
     */
    private function get_allowed_item_types(): array {
        return [
            self::TYPE_PROOF,
            self::TYPE_DECISION,
            self::TYPE_COURSE_WORK,
            self::TYPE_CHALLENGE_RESULT,
            self::TYPE_ASSEMBLY_MINUTES,
            self::TYPE_INTEGRITY_CASE_SUMMARY,
            self::TYPE_KRISTAL,
            self::TYPE_REFLECTION,
            self::TYPE_PORTFOLIO_ITEM,
            self::TYPE_VERSION_RECORD,
        ];
    }

    /**
     * Get allowed statuses.
     *
     * @return string[]
     */
    private function get_allowed_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
            self::STATUS_PENDING,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get allowed validation states.
     *
     * @return string[]
     */
    private function get_allowed_validation_states(): array {
        return [
            self::VALIDATION_UNVERIFIED,
            self::VALIDATION_HUMAN_REVIEWED,
            self::VALIDATION_VERIFIED,
            self::VALIDATION_CONTESTED,
            self::VALIDATION_INVALIDATED,
            self::VALIDATION_ARCHIVED,
        ];
    }

    /**
     * Get allowed visibilities.
     *
     * @return string[]
     */
    private function get_allowed_visibilities(): array {
        return [
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
    }

    /**
     * Get allowed provenance sources.
     *
     * @return string[]
     */
    private function get_allowed_provenance_sources(): array {
        return [
            self::PROVENANCE_HUMAN,
            self::PROVENANCE_AI_ASSISTED,
            self::PROVENANCE_IMPORTED,
            self::PROVENANCE_SYSTEM,
            self::PROVENANCE_ARCHIVE,
            self::PROVENANCE_ASSEMBLY,
            self::PROVENANCE_CHALLENGE,
            self::PROVENANCE_INTEGRITY,
        ];
    }

    /**
     * Normalise item type.
     *
     * @param string $itemtype Raw item type.
     * @return string
     */
    private function normalise_item_type(string $itemtype): string {
        $itemtype = clean_param($itemtype, PARAM_ALPHANUMEXT);

        return in_array($itemtype, $this->get_allowed_item_types(), true)
            ? $itemtype
            : self::TYPE_PROOF;
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        return in_array($status, $this->get_allowed_statuses(), true)
            ? $status
            : self::STATUS_DRAFT;
    }

    /**
     * Normalise validation state.
     *
     * @param string $validationstate Raw validation state.
     * @return string
     */
    private function normalise_validation_state(string $validationstate): string {
        $validationstate = clean_param($validationstate, PARAM_ALPHANUMEXT);

        return in_array($validationstate, $this->get_allowed_validation_states(), true)
            ? $validationstate
            : self::VALIDATION_UNVERIFIED;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private function normalise_visibility(string $visibility): string {
        $visibility = clean_param($visibility, PARAM_ALPHANUMEXT);

        return in_array($visibility, $this->get_allowed_visibilities(), true)
            ? $visibility
            : self::VISIBILITY_COURSE;
    }

    /**
     * Normalise provenance.
     *
     * @param string $provenance Raw provenance.
     * @return string
     */
    private function normalise_provenance(string $provenance): string {
        $provenance = clean_param($provenance, PARAM_ALPHANUMEXT);

        return in_array($provenance, $this->get_allowed_provenance_sources(), true)
            ? $provenance
            : self::PROVENANCE_HUMAN;
    }

    /**
     * Normalise URL.
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

    /**
     * Get item type label.
     *
     * @param string $itemtype Item type.
     * @return string
     */
    private function get_item_type_label(string $itemtype): string {
        return $this->get_component_string('itemtype:' . $itemtype, ucfirst(str_replace('_', ' ', $itemtype)));
    }

    /**
     * Get proof type label.
     *
     * @param string $prooftype Proof type.
     * @return string
     */
    private function get_proof_type_label(string $prooftype): string {
        return $this->get_component_string('prooftype:' . $prooftype, ucfirst(str_replace('_', ' ', $prooftype)));
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
     * Get validation label.
     *
     * @param string $validationstate Validation state.
     * @return string
     */
    private function get_validation_label(string $validationstate): string {
        return $this->get_component_string(
            'validation:' . $validationstate,
            ucfirst(str_replace('_', ' ', $validationstate))
        );
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
     * Get provenance label.
     *
     * @param string $provenance Provenance.
     * @return string
     */
    private function get_provenance_label(string $provenance): string {
        return $this->get_component_string('provenance:' . $provenance, ucfirst(str_replace('_', ' ', $provenance)));
    }

    /**
     * Get action label.
     *
     * @param string $action Action key.
     * @return string
     */
    private function get_action_label(string $action): string {
        return $this->get_component_string('action:' . $action, get_string('view'));
    }

    /**
     * Get component string if it exists, otherwise fallback.
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
     * Build CSS class.
     *
     * @param string $prefix Prefix.
     * @param string $value Value.
     * @return string
     */
    private function get_css_class(string $prefix, string $value): string {
        return $prefix . '-' . str_replace('_', '-', clean_param($value, PARAM_ALPHANUMEXT));
    }

    /**
     * Map warning severity to Bootstrap alert class suffix.
     *
     * @param string $severity Severity key.
     * @return string
     */
    private function get_warning_class(string $severity): string {
        return match ($severity) {
            'danger', 'error', 'invalidated' => 'danger',
            'info', 'notice' => 'info',
            'success', 'clear' => 'success',
            default => 'warning',
        };
    }
}
