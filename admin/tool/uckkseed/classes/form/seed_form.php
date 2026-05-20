<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Seed form for the UCKK distribution seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form used to seed the UCKK-Moodle distribution.
 *
 * This form collects operator intent only. It does not create categories,
 * courses, roles, capabilities, competencies, badges, reports, activities, or
 * archive records directly. Execution belongs to
 * \tool_uckkseed\local\seeder and the specialised seed classes.
 */
final class seed_form extends \moodleform {
    /** Action: seed. */
    public const ACTION_SEED = 'seed';

    /** Mode: apply changes. */
    public const MODE_APPLY = 'apply';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: report only. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Default academic registry JSON folder, relative to Moodle dirroot. */
    public const DEFAULT_PRESET_PATH = 'academic_registry_json';

    /** Preset: categories. */
    public const PRESET_CATEGORIES = 'categories';

    /** Preset: courses. */
    public const PRESET_COURSES = 'courses';

    /** Preset: cohorts. */
    public const PRESET_COHORTS = 'cohorts';

    /** Preset: roles. */
    public const PRESET_ROLES = 'roles';

    /** Preset: capabilities. */
    public const PRESET_CAPABILITIES = 'capabilities';

    /** Preset: competencies. */
    public const PRESET_COMPETENCIES = 'competencies';

    /** Preset: badges. */
    public const PRESET_BADGES = 'badges';

    /** Preset: reports. */
    public const PRESET_REPORTS = 'reports';

    /** Preset: course templates. */
    public const PRESET_COURSE_TEMPLATES = 'course_templates';

    /** Preset: challenge templates. */
    public const PRESET_CHALLENGE_TEMPLATES = 'challenge_templates';

    /** Preset: assembly templates. */
    public const PRESET_ASSEMBLY_TEMPLATES = 'assembly_templates';

    /** Preset: archive templates. */
    public const PRESET_ARCHIVE_TEMPLATES = 'archive_templates';

    /**
     * Define the form.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'seedheader', get_string('seeddistribution', 'tool_uckkseed'));

        $mform->addElement(
            'static',
            'seednotice',
            get_string('notice', 'moodle'),
            get_string('seeddistributionnotice', 'tool_uckkseed')
        );

        $mform->addElement('hidden', 'action', self::ACTION_SEED);
        $mform->setType('action', PARAM_ALPHANUMEXT);

        $this->add_mode_fields();
        $this->add_preset_fields();
        $this->add_component_fields();
        $this->add_execution_fields();
        $this->add_hidden_fields();

        $this->add_action_buttons(true, get_string('seeddistribution', 'tool_uckkseed'));
    }

    /**
     * Add seed mode controls.
     */
    private function add_mode_fields(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'modeheader', get_string('executionmode', 'tool_uckkseed'));

        $modes = [
            self::MODE_DRY_RUN => get_string('mode_dry_run', 'tool_uckkseed'),
            self::MODE_APPLY => get_string('mode_apply', 'tool_uckkseed'),
            self::MODE_REPORT => get_string('mode_report', 'tool_uckkseed'),
            self::MODE_ROLLBACK_PLAN => get_string('mode_rollback_plan', 'tool_uckkseed'),
        ];

        $defaultmode = $this->get_default_mode();

        $mform->addElement('select', 'mode', get_string('mode', 'tool_uckkseed'), $modes);
        $mform->setDefault('mode', $defaultmode);
        $mform->addHelpButton('mode', 'mode', 'tool_uckkseed');

        $mform->addElement('advcheckbox', 'dryrun', get_string('dryrun', 'tool_uckkseed'));
        $mform->setDefault('dryrun', $defaultmode === self::MODE_DRY_RUN ? 1 : 0);
        $mform->addHelpButton('dryrun', 'dryrun', 'tool_uckkseed');

        $mform->addElement('advcheckbox', 'report', get_string('reportmode', 'tool_uckkseed'));
        $mform->setDefault('report', $defaultmode === self::MODE_REPORT ? 1 : 0);
        $mform->addHelpButton('report', 'reportmode', 'tool_uckkseed');

        $mform->addElement('advcheckbox', 'rollbackplan', get_string('rollbackplan', 'tool_uckkseed'));
        $mform->setDefault('rollbackplan', $defaultmode === self::MODE_ROLLBACK_PLAN ? 1 : 0);
        $mform->addHelpButton('rollbackplan', 'rollbackplan', 'tool_uckkseed');
    }

    /**
     * Add preset selectors.
     */
    private function add_preset_fields(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'presetheader', get_string('presets', 'tool_uckkseed'));

        $presetoptions = $this->get_preset_options();

        $select = $mform->addElement(
            'select',
            'presets',
            get_string('presets', 'tool_uckkseed'),
            $presetoptions,
            [
                'multiple' => 'multiple',
                'size' => min(12, max(6, count($presetoptions))),
            ]
        );
        $select->setMultiple(true);

        $mform->setDefault('presets', array_keys($presetoptions));
        $mform->addRule('presets', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('presets', 'presets', 'tool_uckkseed');

        $mform->addElement(
            'static',
            'presetpathnotice',
            get_string('presetpath', 'tool_uckkseed'),
            s($this->get_preset_path())
        );
    }

    /**
     * Add component selector.
     */
    private function add_component_fields(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'componentheader', get_string('components', 'tool_uckkseed'));

        $componentoptions = $this->get_component_options();

        $select = $mform->addElement(
            'select',
            'components',
            get_string('components', 'tool_uckkseed'),
            $componentoptions,
            [
                'multiple' => 'multiple',
                'size' => min(10, max(5, count($componentoptions))),
            ]
        );
        $select->setMultiple(true);

        $mform->setDefault('components', array_keys($componentoptions));
        $mform->addRule('components', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('components', 'components', 'tool_uckkseed');
    }

    /**
     * Add force/confirmation controls.
     */
    private function add_execution_fields(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'executionheader', get_string('executionoptions', 'tool_uckkseed'));

        $mform->addElement('advcheckbox', 'force', get_string('force', 'tool_uckkseed'));
        $mform->setDefault('force', 0);
        $mform->addHelpButton('force', 'force', 'tool_uckkseed');

        $mform->addElement('advcheckbox', 'confirm', get_string('confirmseed', 'tool_uckkseed'));
        $mform->setDefault('confirm', 0);
        $mform->addHelpButton('confirm', 'confirmseed', 'tool_uckkseed');

        $mform->addElement(
            'static',
            'authoritynotice',
            get_string('authoritynotice', 'tool_uckkseed'),
            get_string('seedauthoritynotice', 'tool_uckkseed')
        );

        $mform->addElement(
            'static',
            'idempotencenotice',
            get_string('idempotency', 'tool_uckkseed'),
            get_string('seedidempotencynotice', 'tool_uckkseed')
        );
    }

    /**
     * Add hidden fields.
     */
    private function add_hidden_fields(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);
    }

    /**
     * Validate submitted data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $mode = $this->normalise_mode((string)($data['mode'] ?? self::MODE_DRY_RUN));

        if (!empty($data['dryrun']) && $mode !== self::MODE_DRY_RUN) {
            $errors['dryrun'] = get_string('modecheckboxconflict', 'tool_uckkseed');
        }

        if (!empty($data['report']) && $mode !== self::MODE_REPORT) {
            $errors['report'] = get_string('modecheckboxconflict', 'tool_uckkseed');
        }

        if (!empty($data['rollbackplan']) && $mode !== self::MODE_ROLLBACK_PLAN) {
            $errors['rollbackplan'] = get_string('modecheckboxconflict', 'tool_uckkseed');
        }

        $presets = $this->normalise_list($data['presets'] ?? []);
        $components = $this->normalise_list($data['components'] ?? []);

        if (empty($presets)) {
            $errors['presets'] = get_string('required');
        }

        if (empty($components)) {
            $errors['components'] = get_string('required');
        }

        $unknownpresets = array_diff($presets, array_keys($this->get_preset_options()));

        if (!empty($unknownpresets)) {
            $errors['presets'] = get_string('invalidpresetselection', 'tool_uckkseed');
        }

        $unknowncomponents = array_diff($components, array_keys($this->get_component_options()));

        if (!empty($unknowncomponents)) {
            $errors['components'] = get_string('invalidcomponentselection', 'tool_uckkseed');
        }

        $isapply = $mode === self::MODE_APPLY;
        $isforced = !empty($data['force']);
        $confirmed = !empty($data['confirm']);

        if (($isapply || $isforced) && !$confirmed) {
            $errors['confirm'] = get_string('confirmationrequired', 'tool_uckkseed');
        }

        if (!$this->is_reset_allowed() && $isforced) {
            $errors['force'] = get_string('forceunavailable', 'tool_uckkseed');
        }

        return $errors;
    }

    /**
     * Return normalised seed options for controller/service use.
     *
     * @return array<string, mixed>
     */
    public function get_seed_options(): array {
        $data = $this->get_data();

        if ($data === null) {
            return [];
        }

        $mode = $this->normalise_mode((string)($data->mode ?? self::MODE_DRY_RUN));

        return [
            'action' => self::ACTION_SEED,
            'mode' => $mode,
            'presets' => $this->normalise_list($data->presets ?? []),
            'components' => $this->normalise_list($data->components ?? []),
            'presetpath' => $this->get_preset_path(),
            'dryrun' => $mode === self::MODE_DRY_RUN,
            'report' => $mode === self::MODE_REPORT,
            'rollbackplan' => $mode === self::MODE_ROLLBACK_PLAN,
            'force' => !empty($data->force),
            'confirm' => !empty($data->confirm),
            'returnurl' => (string)($data->returnurl ?? ''),
            'source' => 'web_form',
        ];
    }

    /**
     * Return default mode.
     *
     * @return string
     */
    private function get_default_mode(): string {
        $configured = (string)get_config('tool_uckkseed', 'defaultmode');

        return $this->normalise_mode($configured !== '' ? $configured : self::MODE_DRY_RUN);
    }

    /**
     * Return configured academic registry JSON path.
     *
     * The value is normally relative to Moodle dirroot.
     *
     * @return string
     */
    private function get_preset_path(): string {
        $configured = trim((string)get_config('tool_uckkseed', 'presetpath'));

        if ($configured !== '') {
            return $configured;
        }

        return self::DEFAULT_PRESET_PATH;
    }

    /**
     * Whether forced execution is allowed by configuration.
     *
     * @return bool
     */
    private function is_reset_allowed(): bool {
        return (bool)get_config('tool_uckkseed', 'allowreset');
    }

    /**
     * Return preset options.
     *
     * @return array<string, string>
     */
    private function get_preset_options(): array {
        $custom = $this->_customdata['presets'] ?? null;

        if (is_array($custom) && !empty($custom)) {
            return $this->normalise_option_map($custom);
        }

        return [
            self::PRESET_CATEGORIES => get_string('preset_categories', 'tool_uckkseed'),
            self::PRESET_COURSES => get_string('preset_courses', 'tool_uckkseed'),
            self::PRESET_COHORTS => get_string('preset_cohorts', 'tool_uckkseed'),
            self::PRESET_ROLES => get_string('preset_roles', 'tool_uckkseed'),
            self::PRESET_CAPABILITIES => get_string('preset_capabilities', 'tool_uckkseed'),
            self::PRESET_COMPETENCIES => get_string('preset_competencies', 'tool_uckkseed'),
            self::PRESET_BADGES => get_string('preset_badges', 'tool_uckkseed'),
            self::PRESET_REPORTS => get_string('preset_reports', 'tool_uckkseed'),
            self::PRESET_COURSE_TEMPLATES => get_string('preset_course_templates', 'tool_uckkseed'),
            self::PRESET_CHALLENGE_TEMPLATES => get_string('preset_challenge_templates', 'tool_uckkseed'),
            self::PRESET_ASSEMBLY_TEMPLATES => get_string('preset_assembly_templates', 'tool_uckkseed'),
            self::PRESET_ARCHIVE_TEMPLATES => get_string('preset_archive_templates', 'tool_uckkseed'),
        ];
    }

    /**
     * Return component options.
     *
     * @return array<string, string>
     */
    private function get_component_options(): array {
        $custom = $this->_customdata['components'] ?? null;

        if (is_array($custom) && !empty($custom)) {
            return $this->normalise_option_map($custom);
        }

        return [
            'local_uckk' => get_string('component_local_uckk', 'tool_uckkseed'),
            'theme_uckk' => get_string('component_theme_uckk', 'tool_uckkseed'),
            'format_uckk' => get_string('component_format_uckk', 'tool_uckkseed'),
            'block_uckk_dashboard' => get_string('component_block_uckk_dashboard', 'tool_uckkseed'),
            'mod_uckkchallenge' => get_string('component_mod_uckkchallenge', 'tool_uckkseed'),
            'mod_uckkassembly' => get_string('component_mod_uckkassembly', 'tool_uckkseed'),
            'mod_uckkarchive' => get_string('component_mod_uckkarchive', 'tool_uckkseed'),
            'tool_uckkintegrity' => get_string('component_tool_uckkintegrity', 'tool_uckkseed'),
            'report_uckk' => get_string('component_report_uckk', 'tool_uckkseed'),
        ];
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        $allowed = [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Normalise a submitted multi-select list.
     *
     * @param mixed $value Raw value.
     * @return string[]
     */
    private function normalise_list(mixed $value): array {
        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            $item = clean_param((string)$item, PARAM_ALPHANUMEXT);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    /**
     * Normalise an option map passed through customdata.
     *
     * @param array $options Raw options.
     * @return array<string, string>
     */
    private function normalise_option_map(array $options): array {
        $normalised = [];

        foreach ($options as $key => $label) {
            if (is_int($key) && is_string($label)) {
                $key = $label;
            }

            $key = clean_param((string)$key, PARAM_ALPHANUMEXT);

            if ($key === '') {
                continue;
            }

            if (is_array($label)) {
                $label = $label['label'] ?? $key;
            } else if (is_object($label)) {
                $label = $label->label ?? $key;
            }

            $normalised[$key] = format_string((string)$label);
        }

        return $normalised;
    }
}