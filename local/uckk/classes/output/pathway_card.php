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
 * Pathway card renderable for the UCKK institutional core plugin.
 *
 * A pathway card displays a compact, template-ready summary of a UCKK
 * learning pathway: identity, linked program, requirements, progress,
 * internal recognition notice, status, actions and optional warnings.
 *
 * This class prepares display data only.
 *
 * It must not:
 * - write to the database;
 * - assign pathways;
 * - enrol users;
 * - issue badges;
 * - validate competencies;
 * - validate archive items;
 * - perform integrity review;
 * - make accreditation claims.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\output;

use local_uckk\local\status;
use moodle_url;
use named_templatable;
use renderable;
use renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Pathway card renderable.
 */
final class pathway_card implements renderable, named_templatable {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Default template. */
    private const TEMPLATE = 'local_uckk/pathway_card';

    /** @var stdClass Pathway display record. */
    private stdClass $pathway;

    /** @var stdClass|null Program display record. */
    private ?stdClass $program;

    /** @var stdClass|null Progress summary. */
    private ?stdClass $progress;

    /** @var array<int, array<string, mixed>> Actions prepared by caller. */
    private array $actions;

    /** @var array<int, array<string, mixed>> Warnings prepared by caller. */
    private array $warnings;

    /** @var array<string, mixed> Display options. */
    private array $options;

    /**
     * Constructor.
     *
     * Expected pathway fields:
     * - id
     * - shortname
     * - fullname
     * - description
     * - status
     * - programid
     * - requiredcourseidsdecoded or requiredcourseids
     * - requiredcompetenciesdecoded or requiredcompetencies
     * - requiredbadgesdecoded or requiredbadges
     *
     * Optional progress fields:
     * - requiredcoursecount
     * - completedcoursecount
     * - percent
     * - complete
     *
     * @param stdClass|array<string, mixed> $pathway Pathway data.
     * @param stdClass|array<string, mixed>|null $program Program data.
     * @param stdClass|array<string, mixed>|null $progress Progress data.
     * @param array<int, array<string, mixed>> $actions Action data.
     * @param array<string, mixed> $options Display options.
     */
    public function __construct(
        $pathway,
        $program = null,
        $progress = null,
        array $actions = [],
        array $options = []
    ) {
        $this->pathway = $this->normalise_object($pathway);
        $this->program = $program !== null ? $this->normalise_object($program) : null;
        $this->progress = $progress !== null ? $this->normalise_object($progress) : null;
        $this->actions = $actions;
        $this->warnings = $options['warnings'] ?? [];
        $this->options = $options;
    }

    /**
     * Return the Mustache template name.
     *
     * @param renderer_base $renderer Renderer.
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return self::TEMPLATE;
    }

    /**
     * Export template data.
     *
     * Mustache context must remain simple: scalars, arrays and stdClass only.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $pathway = $this->pathway;
        $program = $this->program;
        $progress = $this->get_progress_data();

        $statuskey = $this->normalise_key($pathway->status ?? status::DRAFT);
        $requiredcourses = $this->get_requirement_list($pathway, 'requiredcourseidsdecoded', 'requiredcourseids');
        $requiredcompetencies = $this->get_requirement_list($pathway, 'requiredcompetenciesdecoded', 'requiredcompetencies');
        $requiredbadges = $this->get_requirement_list($pathway, 'requiredbadgesdecoded', 'requiredbadges');
        $metadata = $this->decode_metadata($pathway->metadata ?? '{}');

        $data = new stdClass();

        // Identity.
        $data->id = (int)($pathway->id ?? 0);
        $data->shortname = $this->clean_text($pathway->shortname ?? '');
        $data->fullname = $this->clean_text($pathway->fullname ?? $data->shortname);
        $data->description = (string)($pathway->description ?? '');
        $data->hasdescription = trim($data->description) !== '';

        // Program relation.
        $data->programid = (int)($pathway->programid ?? 0);
        $data->hasprogram = $program !== null;
        $data->program = $this->export_program($program);

        // Status.
        $data->status = $statuskey;
        $data->statuslabel = status::get_label($statuskey);
        $data->statusvisualtype = status::get_visual_type($statuskey);
        $data->statusbadgeclass = status::get_badge_class($statuskey);
        $data->statuscssclass = status::get_css_classes($statuskey);
        $data->statusdata = status::export_for_template($statuskey);

        $data->isactive = $statuskey === status::ACTIVE;
        $data->isdraft = $statuskey === status::DRAFT;
        $data->ishidden = $statuskey === status::HIDDEN;
        $data->isarchived = $statuskey === status::ARCHIVED;
        $data->isdeleted = $statuskey === status::DELETED;
        $data->issensitive = status::is_sensitive($statuskey);
        $data->ispublicsafe = status::is_public_safe($statuskey);

        // Requirements.
        $data->requiredcourses = $this->export_requirement_items($requiredcourses, 'course');
        $data->requiredcoursecount = count($requiredcourses);
        $data->hasrequiredcourses = $data->requiredcoursecount > 0;

        $data->requiredcompetencies = $this->export_requirement_items($requiredcompetencies, 'competency');
        $data->requiredcompetencycount = count($requiredcompetencies);
        $data->hasrequiredcompetencies = $data->requiredcompetencycount > 0;

        $data->requiredbadges = $this->export_requirement_items($requiredbadges, 'badge');
        $data->requiredbadgecount = count($requiredbadges);
        $data->hasrequiredbadges = $data->requiredbadgecount > 0;

        $data->requirementcount = $data->requiredcoursecount
            + $data->requiredcompetencycount
            + $data->requiredbadgecount;
        $data->hasrequirements = $data->requirementcount > 0;

        // Progress.
        $data->progress = $progress;
        $data->hasprogress = $progress->hasprogress;
        $data->progresspercent = $progress->percent;
        $data->progresslabel = $progress->label;
        $data->complete = $progress->complete;
        $data->completedcoursecount = $progress->completedcoursecount;
        $data->progressrequiredcoursecount = $progress->requiredcoursecount;

        // Metadata.
        $data->metadata = $metadata;
        $data->metadatajson = json_encode((object)$metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data->hasmetadata = !empty($metadata);

        // Notices.
        $data->internalrecognitionnotice = get_string('warning_internalrecognition', self::COMPONENT);
        $data->showinternalrecognitionnotice = $this->get_bool_option('showinternalrecognitionnotice', true);

        $data->symbolicrolesnotice = get_string('warning_symbolicroles', self::COMPONENT);
        $data->showsymbolicrolesnotice = $this->get_bool_option('showsymbolicrolesnotice', false);

        // Actions.
        $data->actions = $this->export_actions($this->actions);
        $data->hasactions = !empty($data->actions);

        // Warnings.
        $data->warnings = $this->export_warnings($this->warnings);
        $data->haswarnings = !empty($data->warnings);

        // Display options.
        $data->compact = $this->get_bool_option('compact', false);
        $data->showdescription = $this->get_bool_option('showdescription', true);
        $data->showrequirements = $this->get_bool_option('showrequirements', true);
        $data->showprogress = $this->get_bool_option('showprogress', true);
        $data->showactions = $this->get_bool_option('showactions', true);
        $data->showprogram = $this->get_bool_option('showprogram', true);
        $data->showstatus = $this->get_bool_option('showstatus', true);

        // CSS and data attributes.
        $data->cssclass = $this->get_css_classes($statuskey);
        $data->dataattributes = $this->get_data_attributes();

        // Timestamps.
        $data->timecreated = (int)($pathway->timecreated ?? 0);
        $data->timemodified = (int)($pathway->timemodified ?? 0);
        $data->hasdates = $data->timecreated > 0 || $data->timemodified > 0;

        return $data;
    }

    /**
     * Export program summary.
     *
     * @param stdClass|null $program Program data.
     * @return stdClass
     */
    private function export_program(?stdClass $program): stdClass {
        $data = new stdClass();

        if ($program === null) {
            $data->id = 0;
            $data->shortname = '';
            $data->fullname = '';
            $data->programtype = '';
            $data->programtypelabel = '';
            $data->status = '';
            $data->hasprogram = false;

            return $data;
        }

        $programtype = $this->normalise_key($program->programtype ?? '');

        $data->id = (int)($program->id ?? 0);
        $data->shortname = $this->clean_text($program->shortname ?? '');
        $data->fullname = $this->clean_text($program->fullname ?? $data->shortname);
        $data->programtype = $programtype;
        $data->programtypelabel = $this->get_string_fallback(
            'programtype_' . $programtype,
            ucfirst(str_replace('_', ' ', $programtype))
        );
        $data->status = $this->normalise_key($program->status ?? status::DRAFT);
        $data->statuslabel = status::get_label($data->status);
        $data->hasprogram = true;

        return $data;
    }

    /**
     * Build progress data.
     *
     * @return stdClass
     */
    private function get_progress_data(): stdClass {
        $progress = $this->progress ?? new stdClass();

        $requiredcoursecount = (int)($progress->requiredcoursecount ?? 0);
        $completedcoursecount = (int)($progress->completedcoursecount ?? 0);

        $percent = 0;
        if (isset($progress->percent)) {
            $percent = $this->normalise_percent($progress->percent);
        } else if ($requiredcoursecount > 0) {
            $percent = $this->normalise_percent(floor(($completedcoursecount / $requiredcoursecount) * 100));
        }

        $complete = isset($progress->complete)
            ? (bool)$progress->complete
            : ($requiredcoursecount > 0 && $completedcoursecount >= $requiredcoursecount);

        $data = new stdClass();
        $data->hasprogress = $this->progress !== null;
        $data->percent = $percent;
        $data->label = $percent . '%';
        $data->complete = $complete;
        $data->requiredcoursecount = $requiredcoursecount;
        $data->completedcoursecount = $completedcoursecount;
        $data->remainingcoursecount = max(0, $requiredcoursecount - $completedcoursecount);

        return $data;
    }

    /**
     * Export requirement items.
     *
     * @param array<int, mixed> $items Requirement items.
     * @param string $type Requirement type.
     * @return array<int, array<string, mixed>>
     */
    private function export_requirement_items(array $items, string $type): array {
        $exported = [];

        foreach (array_values($items) as $index => $item) {
            $label = is_scalar($item) ? (string)$item : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $exported[] = [
                'index' => $index,
                'type' => $type,
                'value' => $label,
                'label' => $label,
                'cssclass' => 'uckk-requirement uckk-requirement-' . $type,
            ];
        }

        return $exported;
    }

    /**
     * Export action data.
     *
     * @param array<int, array<string, mixed>> $actions Actions.
     * @return array<int, array<string, mixed>>
     */
    private function export_actions(array $actions): array {
        $exported = [];

        foreach ($actions as $action) {
            $key = $this->normalise_key($action['key'] ?? 'action');
            $url = $this->normalise_url($action['url'] ?? '');
            $label = $this->clean_text($action['label'] ?? $this->get_string_fallback('action_' . $key, ucfirst($key)));

            if ($url === '') {
                continue;
            }

            $exported[] = [
                'key' => $key,
                'label' => $label,
                'url' => $url,
                'primary' => !empty($action['primary']),
                'disabled' => !empty($action['disabled']),
                'cssclass' => $this->clean_text($action['cssclass'] ?? ''),
            ];
        }

        return $exported;
    }

    /**
     * Export warning data.
     *
     * @param array<int, array<string, mixed>> $warnings Warnings.
     * @return array<int, array<string, mixed>>
     */
    private function export_warnings(array $warnings): array {
        $exported = [];

        foreach ($warnings as $warning) {
            $code = $this->normalise_key($warning['code'] ?? 'warning');
            $message = $this->clean_text($warning['message'] ?? '');

            if ($message === '') {
                continue;
            }

            $exported[] = [
                'code' => $code,
                'message' => $message,
                'cssclass' => 'uckk-warning uckk-warning-' . $code,
            ];
        }

        return $exported;
    }

    /**
     * Return requirement list from either decoded array or JSON/string field.
     *
     * @param stdClass $record Record.
     * @param string $decodedfield Decoded field name.
     * @param string $rawfield Raw field name.
     * @return array<int, mixed>
     */
    private function get_requirement_list(stdClass $record, string $decodedfield, string $rawfield): array {
        if (isset($record->{$decodedfield}) && is_array($record->{$decodedfield})) {
            return array_values($record->{$decodedfield});
        }

        if (!isset($record->{$rawfield})) {
            return [];
        }

        $raw = $record->{$rawfield};

        if (is_array($raw)) {
            return array_values($raw);
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }

    /**
     * Build CSS classes.
     *
     * @param string $statuskey Status key.
     * @return string
     */
    private function get_css_classes(string $statuskey): string {
        $classes = [
            'uckk-pathway-card',
            'uckk-pathway-status-' . $statuskey,
        ];

        if ($this->get_bool_option('compact', false)) {
            $classes[] = 'uckk-pathway-card-compact';
        }

        if (status::is_sensitive($statuskey)) {
            $classes[] = 'uckk-pathway-card-sensitive';
        }

        if (status::is_final_state($statuskey)) {
            $classes[] = 'uckk-pathway-card-final';
        }

        if (!empty($this->options['cssclass'])) {
            $classes[] = clean_param((string)$this->options['cssclass'], PARAM_TEXT);
        }

        $classes = array_filter(array_map('trim', $classes));

        return implode(' ', array_unique($classes));
    }

    /**
     * Build Mustache-friendly data attributes.
     *
     * @return array<int, array{name: string, value: string|int}>
     */
    private function get_data_attributes(): array {
        $attributes = [
            'data-region' => 'uckk-pathway-card',
            'data-pathway-id' => (int)($this->pathway->id ?? 0),
            'data-pathway-shortname' => $this->clean_text($this->pathway->shortname ?? ''),
            'data-pathway-status' => $this->normalise_key($this->pathway->status ?? status::DRAFT),
        ];

        if (!empty($this->pathway->programid)) {
            $attributes['data-program-id'] = (int)$this->pathway->programid;
        }

        $items = [];
        foreach ($attributes as $name => $value) {
            $items[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        return $items;
    }

    /**
     * Convert array/object to stdClass.
     *
     * @param stdClass|array<string, mixed> $value Value.
     * @return stdClass
     */
    private function normalise_object($value): stdClass {
        if ($value instanceof stdClass) {
            return $value;
        }

        return (object)(array)$value;
    }

    /**
     * Normalise a machine key.
     *
     * @param mixed $value Raw key.
     * @return string
     */
    private function normalise_key($value): string {
        $value = trim(\core_text::strtolower((string)$value));
        $value = str_replace(['-', ' '], '_', $value);

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Clean text for display metadata.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function clean_text($value): string {
        return clean_param((string)$value, PARAM_TEXT);
    }

    /**
     * Normalise a URL value.
     *
     * @param mixed $url URL.
     * @return string
     */
    private function normalise_url($url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url) && trim($url) !== '') {
            return $url;
        }

        return '';
    }

    /**
     * Decode metadata.
     *
     * @param mixed $metadata Metadata.
     * @return array<string, mixed>
     */
    private function decode_metadata($metadata): array {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (!is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get boolean display option.
     *
     * @param string $key Option key.
     * @param bool $default Default value.
     * @return bool
     */
    private function get_bool_option(string $key, bool $default): bool {
        if (!array_key_exists($key, $this->options)) {
            return $default;
        }

        return (bool)$this->options[$key];
    }

    /**
     * Normalise a percentage.
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
     * Return a Moodle string if it exists, otherwise a fallback.
     *
     * @param string $identifier String identifier.
     * @param string $fallback Fallback text.
     * @return string
     */
    private function get_string_fallback(string $identifier, string $fallback): string {
        if (get_string_manager()->string_exists($identifier, self::COMPONENT)) {
            return get_string($identifier, self::COMPONENT);
        }

        return $fallback;
    }
}

