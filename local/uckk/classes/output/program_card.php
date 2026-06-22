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
 * Program card renderable for local_uckk.
 *
 * This class prepares display data for one UCKK program card.
 *
 * It must not:
 * - create or update programs;
 * - enrol users;
 * - assign pathways;
 * - assign roles or capabilities;
 * - award badges;
 * - validate competencies;
 * - calculate authoritative completion;
 * - publish institutional recognition.
 *
 * It only exports safe, template-ready data for Mustache.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\output;

use coding_exception;
use core_text;
use moodle_url;
use named_templatable;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Program card renderable.
 *
 * Expected template:
 *
 * ```text
 * local/uckk/templates/program_card.mustache
 * ```
 *
 * Example:
 *
 * ```php
 * $card = new \local_uckk\output\program_card($program, [
 *     'canmanage' => has_capability('local/uckk:manageprograms', $context),
 *     'progresspercent' => 42,
 *     'coursecount' => 8,
 *     'badgecount' => 3,
 * ]);
 *
 * echo $OUTPUT->render($card);
 * ```
 *
 * @package local_uckk
 */
final class program_card implements renderable, templatable, named_templatable {
    /** Program type: tronc commun. */
    public const TYPE_TRONC_COMMUN = 'tronc_commun';

    /**
     * Program type: legacy technical key for a UCKK Voie-level program.
     *
     * Keep this value for DB compatibility; do not expose it as a public label.
     */
    public const TYPE_BACCALAUREAT = 'baccalaureat';

    /**
     * Program type: legacy technical key for a UCKK initiation-level Voie.
     *
     * Keep this value for DB compatibility; do not expose it as a public label.
     */
    public const TYPE_MINEURE = 'mineure';

    /** Program type: séminaire. */
    public const TYPE_SEMINAR = 'seminar';

    /** Program type: laboratory. */
    public const TYPE_LAB = 'lab';

    /** Program status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Program status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Program status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Program status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: course. */
    public const VISIBILITY_COURSE = 'course';

    /** Visibility: cohort. */
    public const VISIBILITY_COHORT = 'cohort';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /** @var stdClass Program display data. */
    private stdClass $program;

    /** @var array<string, mixed> Display options prepared by caller. */
    private array $options;

    /**
     * Constructor.
     *
     * @param stdClass|array<string, mixed> $program Program record or data array.
     * @param array<string, mixed> $options Display options.
     */
    public function __construct($program, array $options = []) {
        if (is_array($program)) {
            $program = (object)$program;
        }

        if (!$program instanceof stdClass) {
            throw new coding_exception('program_card expects a program record or array.');
        }

        $this->program = self::normalise_program($program);
        $this->options = $options;
    }

    /**
     * Create a program card from a DB record.
     *
     * @param stdClass $program Program record.
     * @param array<string, mixed> $options Display options.
     * @return self
     */
    public static function from_record(stdClass $program, array $options = []): self {
        return new self($program, $options);
    }

    /**
     * Get the Mustache template name.
     *
     * @param renderer_base $renderer Renderer.
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'local_uckk/program_card';
    }

    /**
     * Export data for Mustache.
     *
     * All returned data must be made of scalars, arrays or stdClass objects.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $program = $this->program;

        $data = new stdClass();

        // Identity.
        $data->id = $program->id;
        $data->shortname = $program->shortname;
        $data->fullname = $program->fullname;
        $data->displayname = $program->fullname !== '' ? $program->fullname : $program->shortname;
        $data->programtype = $program->programtype;
        $data->programtypelabel = self::get_program_type_label($program->programtype);
        $data->categoryid = $program->categoryid;
        $data->hascategory = $program->categoryid > 0;
        $data->sortorder = $program->sortorder;

        // Description.
        $data->description = $this->get_description_html();
        $data->hasdescription = trim($data->description) !== '';

        // Status and visibility.
        $data->status = $program->status;
        $data->statuslabel = self::get_status_label($program->status);
        $data->statustype = self::get_status_type($program->status);
        $data->visibility = $program->visibility;
        $data->visibilitylabel = self::get_visibility_label($program->visibility);

        // Flags.
        $data->isactive = $program->status === self::STATUS_ACTIVE;
        $data->isdraft = $program->status === self::STATUS_DRAFT;
        $data->ishidden = $program->status === self::STATUS_HIDDEN;
        $data->isarchived = $program->status === self::STATUS_ARCHIVED;
        $data->ispublic = $program->visibility === self::VISIBILITY_PUBLIC;
        $data->isinstitutional = $program->visibility === self::VISIBILITY_INSTITUTION;
        $data->istronccommun = $program->programtype === self::TYPE_TRONC_COMMUN;
        $data->isbaccalaureat = $program->programtype === self::TYPE_BACCALAUREAT;
        $data->ismineure = $program->programtype === self::TYPE_MINEURE;
        $data->isseminar = $program->programtype === self::TYPE_SEMINAR;
        $data->islab = $program->programtype === self::TYPE_LAB;

        // URLs.
        $data->viewurl = $this->get_url('view')->out(false);
        $data->hasviewurl = true;

        $data->manageurl = $this->get_url('manage')->out(false);
        $data->hasmanageurl = !empty($this->options['canmanage']);

        $data->pathwaysurl = $this->get_url('pathways')->out(false);
        $data->haspathwaysurl = !empty($this->options['showpathways']);

        $data->categoryurl = $this->get_category_url();
        $data->hascategoryurl = $data->categoryurl !== '';

        // Permissions prepared by caller.
        $data->canview = $this->get_bool_option('canview', true);
        $data->canmanage = $this->get_bool_option('canmanage', false);
        $data->canedit = $this->get_bool_option('canedit', false);
        $data->candelete = $this->get_bool_option('candelete', false);
        $data->canassignpathway = $this->get_bool_option('canassignpathway', false);

        // Metrics prepared by caller. This class does not calculate authority.
        $data->coursecount = $this->get_int_option('coursecount');
        $data->hascoursecount = $data->coursecount > 0;

        $data->pathwaycount = $this->get_int_option('pathwaycount');
        $data->haspathwaycount = $data->pathwaycount > 0;

        $data->competencycount = $this->get_int_option('competencycount');
        $data->hascompetencycount = $data->competencycount > 0;

        $data->badgecount = $this->get_int_option('badgecount');
        $data->hasbadgecount = $data->badgecount > 0;

        $data->cohortcount = $this->get_int_option('cohortcount');
        $data->hascohortcount = $data->cohortcount > 0;

        $data->playercount = $this->get_int_option('playercount');
        $data->hasplayercount = $data->playercount > 0;

        // Progress is a display hint, not an authoritative certification.
        $data->progresspercent = $this->normalise_percent($this->options['progresspercent'] ?? 0);
        $data->hasprogress = $this->get_bool_option('hasprogress', array_key_exists('progresspercent', $this->options));
        $data->progresslabel = $this->options['progresslabel'] ?? get_string('progress', 'local_uckk');

        // Tags.
        $data->tags = $this->normalise_tags($this->options['tags'] ?? []);
        $data->hastags = !empty($data->tags);

        // Warnings and institutional notices.
        $data->showinternalrecognitionnotice = $this->get_bool_option(
            'showinternalrecognitionnotice',
            $this->should_show_internal_recognition_notice()
        );
        $data->internalrecognitionnotice = get_string('internalrecognitionnotice_short', 'local_uckk');

        $data->showboundarynotice = $this->get_bool_option('showboundarynotice', false);
        $data->boundarynotice = get_string('boundarynotice_short', 'local_uckk');

        // Icon / visual key.
        $data->icon = $this->get_icon_key();
        $data->cssclass = $this->get_css_classes();

        // Optional call-to-action prepared by caller.
        $data->primaryactionlabel = $this->options['primaryactionlabel'] ?? get_string('viewprogram', 'local_uckk');
        $data->primaryactionurl = $this->normalise_url($this->options['primaryactionurl'] ?? $data->viewurl);
        $data->hasprimaryaction = $data->primaryactionurl !== '';

        $data->secondaryactionlabel = $this->options['secondaryactionlabel'] ?? '';
        $data->secondaryactionurl = $this->normalise_url($this->options['secondaryactionurl'] ?? '');
        $data->hassecondaryaction = $data->secondaryactionlabel !== '' && $data->secondaryactionurl !== '';

        // Extra data for templates.
        $data->metadata = $this->decode_json_object($program->metadata);
        $data->hasmetadata = !empty((array)$data->metadata);

        return $data;
    }

    /**
     * Return normalized program record.
     *
     * @param stdClass $program Raw program.
     * @return stdClass
     */
    private static function normalise_program(stdClass $program): stdClass {
        $record = new stdClass();

        $record->id = max(0, (int)($program->id ?? 0));
        $record->shortname = clean_param((string)($program->shortname ?? ''), PARAM_ALPHANUMEXT);
        $record->fullname = clean_param((string)($program->fullname ?? ''), PARAM_TEXT);
        $record->programtype = self::normalise_program_type($program->programtype ?? self::TYPE_BACCALAUREAT);
        $record->categoryid = max(0, (int)($program->categoryid ?? 0));
        $record->description = (string)($program->description ?? '');
        $record->descriptionformat = (int)($program->descriptionformat ?? FORMAT_HTML);
        $record->status = self::normalise_status($program->status ?? self::STATUS_DRAFT);
        $record->visibility = self::normalise_visibility($program->visibility ?? self::VISIBILITY_INSTITUTION);
        $record->sortorder = max(0, (int)($program->sortorder ?? 0));
        $record->metadata = (string)($program->metadata ?? '{}');

        return $record;
    }

    /**
     * Get a plugin URL.
     *
     * @param string $action Action.
     * @return moodle_url
     */
    private function get_url(string $action): moodle_url {
        $params = [];

        if ($this->program->id > 0) {
            $params['id'] = $this->program->id;
        }

        switch ($action) {
            case 'manage':
                $params['action'] = 'edit';
                return new moodle_url('/local/uckk/programs.php', $params);

            case 'pathways':
                $params['programid'] = $this->program->id;
                unset($params['id']);
                return new moodle_url('/local/uckk/pathways.php', $params);

            case 'view':
            default:
                return new moodle_url('/local/uckk/programs.php', $params);
        }
    }

    /**
     * Get Moodle category URL when linked.
     *
     * @return string
     */
    private function get_category_url(): string {
        if ($this->program->categoryid <= 0) {
            return '';
        }

        return (new moodle_url('/course/index.php', [
            'categoryid' => $this->program->categoryid,
        ]))->out(false);
    }

    /**
     * Get formatted program description.
     *
     * @return string
     */
    private function get_description_html(): string {
        if (trim($this->program->description) === '') {
            return '';
        }

        return format_text(
            $this->program->description,
            $this->program->descriptionformat,
            [
                'overflowdiv' => true,
                'noclean' => false,
            ]
        );
    }

    /**
     * Get CSS classes for this card.
     *
     * @return string
     */
    private function get_css_classes(): string {
        $classes = [
            'uckk-program-card',
            'uckk-program-type-' . $this->program->programtype,
            'uckk-program-status-' . $this->program->status,
            'uckk-program-visibility-' . $this->program->visibility,
        ];

        if (!empty($this->options['cssclass'])) {
            $classes[] = clean_param((string)$this->options['cssclass'], PARAM_TEXT);
        }

        return implode(' ', array_filter($classes));
    }

    /**
     * Get icon key for the program type.
     *
     * @return string
     */
    private function get_icon_key(): string {
        if (!empty($this->options['icon'])) {
            return clean_param((string)$this->options['icon'], PARAM_ALPHANUMEXT);
        }

        switch ($this->program->programtype) {
            case self::TYPE_TRONC_COMMUN:
                return 'tronccommun';

            case self::TYPE_MINEURE:
                return 'mineure';

            case self::TYPE_SEMINAR:
                return 'seminar';

            case self::TYPE_LAB:
                return 'lab';

            case self::TYPE_BACCALAUREAT:
            default:
                return 'program';
        }
    }

    /**
     * Determine whether to show internal recognition notice.
     *
     * @return bool
     */
    private function should_show_internal_recognition_notice(): bool {
        return in_array($this->program->programtype, [
            self::TYPE_BACCALAUREAT,
            self::TYPE_MINEURE,
            self::TYPE_SEMINAR,
            self::TYPE_LAB,
        ], true);
    }

    /**
     * Get a boolean option.
     *
     * @param string $key Option key.
     * @param bool $default Default.
     * @return bool
     */
    private function get_bool_option(string $key, bool $default = false): bool {
        if (!array_key_exists($key, $this->options)) {
            return $default;
        }

        return !empty($this->options[$key]);
    }

    /**
     * Get an integer option.
     *
     * @param string $key Option key.
     * @param int $default Default.
     * @return int
     */
    private function get_int_option(string $key, int $default = 0): int {
        if (!array_key_exists($key, $this->options)) {
            return $default;
        }

        return max(0, (int)$this->options[$key]);
    }

    /**
     * Normalize a percentage value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    private function normalise_percent($value): int {
        $value = (int)$value;

        if ($value < 0) {
            return 0;
        }

        if ($value > 100) {
            return 100;
        }

        return $value;
    }

    /**
     * Normalize tags for Mustache.
     *
     * @param mixed $tags Raw tags.
     * @return array<int, array<string, string>>
     */
    private function normalise_tags($tags): array {
        if (!is_array($tags)) {
            return [];
        }

        $items = [];

        foreach ($tags as $tag) {
            if (is_array($tag)) {
                $label = trim((string)($tag['label'] ?? ''));
                $key = trim((string)($tag['key'] ?? $label));
            } else {
                $label = trim((string)$tag);
                $key = $label;
            }

            if ($label === '') {
                continue;
            }

            $items[] = [
                'key' => clean_param($key, PARAM_ALPHANUMEXT),
                'label' => clean_param($label, PARAM_TEXT),
            ];
        }

        return $items;
    }

    /**
     * Normalize a URL-like value.
     *
     * @param mixed $url URL value.
     * @return string
     */
    private function normalise_url($url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url)) {
            return trim($url);
        }

        return '';
    }

    /**
     * Decode JSON metadata.
     *
     * @param mixed $json JSON.
     * @return stdClass
     */
    private function decode_json_object($json): stdClass {
        if (is_array($json)) {
            return (object)$json;
        }

        if (!is_string($json) || trim($json) === '') {
            return new stdClass();
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return new stdClass();
        }

        return (object)$decoded;
    }

    /**
     * Normalize program type.
     *
     * @param mixed $type Raw type.
     * @return string
     */
    private static function normalise_program_type($type): string {
        $type = clean_param(\core_text::strtolower(trim((string)$type)), PARAM_ALPHANUMEXT);

        $valid = [
            self::TYPE_TRONC_COMMUN,
            self::TYPE_BACCALAUREAT,
            self::TYPE_MINEURE,
            self::TYPE_SEMINAR,
            self::TYPE_LAB,
        ];

        if (!in_array($type, $valid, true)) {
            return self::TYPE_BACCALAUREAT;
        }

        return $type;
    }

    /**
     * Normalize status.
     *
     * @param mixed $status Raw status.
     * @return string
     */
    private static function normalise_status($status): string {
        $status = clean_param(\core_text::strtolower(trim((string)$status)), PARAM_ALPHANUMEXT);

        $valid = [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
            self::STATUS_ARCHIVED,
        ];

        if (!in_array($status, $valid, true)) {
            return self::STATUS_DRAFT;
        }

        return $status;
    }

    /**
     * Normalize visibility.
     *
     * @param mixed $visibility Raw visibility.
     * @return string
     */
    private static function normalise_visibility($visibility): string {
        $visibility = clean_param(\core_text::strtolower(trim((string)$visibility)), PARAM_ALPHANUMEXT);

        $valid = [
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_COURSE,
            self::VISIBILITY_COHORT,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
        ];

        if (!in_array($visibility, $valid, true)) {
            return self::VISIBILITY_INSTITUTION;
        }

        return $visibility;
    }

    /**
     * Get program type label.
     *
     * @param string $type Program type.
     * @return string
     */
    private static function get_program_type_label(string $type): string {
        $type = self::normalise_program_type($type);

        // Public-facing UCKK nomenclature override.
        //
        // The DB may still contain legacy technical keys such as "baccalaureat"
        // and "mineure". Those keys are preserved for compatibility, but the
        // rendered interface must not expose controlled university-style labels.
        switch ($type) {
            case self::TYPE_BACCALAUREAT:
                return 'Voie UCKK — Niveau visé : Puissance opératoire';

            case self::TYPE_MINEURE:
                return 'Voie UCKK — Niveau visé : Initiation';
        }

        $stringkey = 'programtype_' . $type;

        if (get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        return ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get status label.
     *
     * @param string $status Status.
     * @return string
     */
    private static function get_status_label(string $status): string {
        $stringkey = 'status_' . $status;

        if (get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get visibility label.
     *
     * @param string $visibility Visibility.
     * @return string
     */
    private static function get_visibility_label(string $visibility): string {
        $stringkey = 'visibility_' . $visibility;

        if (get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        return ucfirst(str_replace('_', ' ', $visibility));
    }

    /**
     * Get Bootstrap-compatible status type.
     *
     * @param string $status Status.
     * @return string
     */
    private static function get_status_type(string $status): string {
        switch ($status) {
            case self::STATUS_ACTIVE:
                return 'success';

            case self::STATUS_HIDDEN:
                return 'warning';

            case self::STATUS_ARCHIVED:
                return 'secondary';

            case self::STATUS_DRAFT:
            default:
                return 'light';
        }
    }
}