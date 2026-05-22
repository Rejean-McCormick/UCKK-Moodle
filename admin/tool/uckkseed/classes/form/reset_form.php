<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Reset form for the UCKK Seed admin tool.
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

use moodleform;

/**
 * Form used to reset selected UCKK seeded content.
 *
 * Reset is intentionally conservative. It must not delete arbitrary Moodle data.
 * Service classes must only reset content that can be identified as UCKK-seeded
 * or explicitly selected by the authorised operator.
 */
final class reset_form extends moodleform {
    /** Reset only seed run/log records. */
    public const SCOPE_SEED_LOGS = 'reset_seed_logs';

    /** Reset seeded content records. */
    public const SCOPE_SEEDED_CONTENT = 'reset_seeded_content';

    /** Reset seeded courses. */
    public const SCOPE_SEEDED_COURSES = 'reset_seeded_courses';

    /** Reset seeded roles/capability assignments. */
    public const SCOPE_SEEDED_ROLES = 'reset_seeded_roles';

    /** Reset seeded badges. */
    public const SCOPE_SEEDED_BADGES = 'reset_seeded_badges';

    /** Reset all UCKK-seeded content. */
    public const SCOPE_ALL_UCKK_SEEDED_CONTENT = 'reset_all_uckk_seeded_content';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'resetheader', get_string('resetdistribution', 'tool_uckkseed'));

        $mform->addElement(
            'static',
            'resetnotice',
            '',
            get_string('resetformnotice', 'tool_uckkseed')
        );

        $mform->addElement(
            'select',
            'scope',
            get_string('resetscope', 'tool_uckkseed'),
            self::get_scope_options()
        );
        $mform->setType('scope', PARAM_ALPHANUMEXT);
        $mform->setDefault('scope', $this->get_default_scope());
        $mform->addRule('scope', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('scope', 'resetscope', 'tool_uckkseed');

        $mform->addElement(
            'autocomplete',
            'components',
            get_string('components', 'tool_uckkseed'),
            $this->get_component_options(),
            [
                'multiple' => true,
                'noselectionstring' => get_string('allcomponents', 'tool_uckkseed'),
            ]
        );
        $mform->setType('components', PARAM_COMPONENT);
        $mform->addHelpButton('components', 'components', 'tool_uckkseed');

        $mform->addElement(
            'autocomplete',
            'presets',
            get_string('presets', 'tool_uckkseed'),
            $this->get_preset_options(),
            [
                'multiple' => true,
                'noselectionstring' => get_string('allpresets', 'tool_uckkseed'),
            ]
        );
        $mform->setType('presets', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('presets', 'presets', 'tool_uckkseed');

        $mform->addElement('header', 'executionheader', get_string('executionoptions', 'tool_uckkseed'));

        $mform->addElement(
            'advcheckbox',
            'dryrun',
            get_string('dryrun', 'tool_uckkseed'),
            get_string('dryrun_desc', 'tool_uckkseed')
        );
        $mform->setType('dryrun', PARAM_BOOL);
        $mform->setDefault('dryrun', 1);

        $mform->addElement(
            'advcheckbox',
            'rollbackplan',
            get_string('rollbackplan', 'tool_uckkseed'),
            get_string('rollbackplan_desc', 'tool_uckkseed')
        );
        $mform->setType('rollbackplan', PARAM_BOOL);
        $mform->setDefault('rollbackplan', 1);

        $mform->addElement(
            'advcheckbox',
            'force',
            get_string('force', 'tool_uckkseed'),
            get_string('force_desc', 'tool_uckkseed')
        );
        $mform->setType('force', PARAM_BOOL);
        $mform->setDefault('force', 0);

        $mform->addElement(
            'advcheckbox',
            'confirm',
            get_string('confirmationrequired', 'tool_uckkseed'),
            get_string('confirmreset_desc', 'tool_uckkseed')
        );
        $mform->setType('confirm', PARAM_BOOL);
        $mform->setDefault('confirm', 0);

        $mform->addElement('hidden', 'returnurl', $this->get_return_url());
        $mform->setType('returnurl', PARAM_LOCALURL);

        $this->add_action_buttons(true, get_string('resetdistribution', 'tool_uckkseed'));
    }

    /**
     * Validate form submission.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $scope = (string)($data['scope'] ?? '');

        if (!array_key_exists($scope, self::get_scope_options())) {
            $errors['scope'] = get_string('invalidresetscope', 'tool_uckkseed');
        }

        $components = $this->normalise_multi_value($data['components'] ?? []);
        foreach ($components as $component) {
            if (!array_key_exists($component, $this->get_component_options())) {
                $errors['components'] = get_string('invalidcomponentselection', 'tool_uckkseed');
                break;
            }
        }

        $presets = $this->normalise_multi_value($data['presets'] ?? []);
        foreach ($presets as $preset) {
            if (!array_key_exists($preset, $this->get_preset_options())) {
                $errors['presets'] = get_string('invalidpresetselection', 'tool_uckkseed');
                break;
            }
        }

        $dryrun = !empty($data['dryrun']);
        $force = !empty($data['force']);
        $confirm = !empty($data['confirm']);
        $requiresconfirmation = (bool)get_config('tool_uckkseed', 'requireconfirmation');

        if (!$dryrun && $requiresconfirmation && !$confirm) {
            $errors['confirm'] = get_string('resetconfirmationrequired', 'tool_uckkseed');
        }

        if (
            !$dryrun
            && $scope === self::SCOPE_ALL_UCKK_SEEDED_CONTENT
            && (!$force || !$confirm)
        ) {
            $errors['force'] = get_string('resetallrequiresforce', 'tool_uckkseed');
            $errors['confirm'] = get_string('resetallrequiresconfirmation', 'tool_uckkseed');
        }

        if (!$dryrun && empty($data['rollbackplan'])) {
            $errors['rollbackplan'] = get_string('rollbackplanrequired', 'tool_uckkseed');
        }

        return $errors;
    }

    /**
     * Return canonical reset options for service layer.
     *
     * @return array<string, mixed>
     */
    public function get_reset_options(): array {
        $data = $this->get_data();

        if ($data === null) {
            return [];
        }

        $dryrun = !empty($data->dryrun);
        $rollbackplan = !empty($data->rollbackplan);

        $mode = self::MODE_APPLY;

        if ($dryrun) {
            $mode = self::MODE_DRY_RUN;
        } else if ($rollbackplan) {
            $mode = self::MODE_ROLLBACK_PLAN;
        }

        return [
            'action' => 'reset',
            'mode' => $mode,
            'scope' => (string)$data->scope,
            'components' => $this->normalise_multi_value($data->components ?? []),
            'presets' => $this->normalise_multi_value($data->presets ?? []),
            'dryrun' => $dryrun,
            'rollbackplan' => $rollbackplan,
            'force' => !empty($data->force),
            'confirm' => !empty($data->confirm),
            'returnurl' => (string)($data->returnurl ?? ''),
            'source' => 'web_form',
        ];
    }

    /**
     * Return canonical reset scope options.
     *
     * @return array<string, string>
     */
    public static function get_scope_options(): array {
        return [
            self::SCOPE_SEED_LOGS => get_string('scope:reset_seed_logs', 'tool_uckkseed'),
            self::SCOPE_SEEDED_CONTENT => get_string('scope:reset_seeded_content', 'tool_uckkseed'),
            self::SCOPE_SEEDED_COURSES => get_string('scope:reset_seeded_courses', 'tool_uckkseed'),
            self::SCOPE_SEEDED_ROLES => get_string('scope:reset_seeded_roles', 'tool_uckkseed'),
            self::SCOPE_SEEDED_BADGES => get_string('scope:reset_seeded_badges', 'tool_uckkseed'),
            self::SCOPE_ALL_UCKK_SEEDED_CONTENT => get_string('scope:reset_all_uckk_seeded_content', 'tool_uckkseed'),
        ];
    }

    /**
     * Return component options.
     *
     * @return array<string, string>
     */
    private function get_component_options(): array {
        if (!empty($this->_customdata['components']) && is_array($this->_customdata['components'])) {
            return $this->_customdata['components'];
        }

        return [
            'local_uckk' => 'local_uckk',
            'theme_uckk' => 'theme_uckk',
            'format_uckk' => 'format_uckk',
            'block_uckk_dashboard' => 'block_uckk_dashboard',
            'mod_uckkchallenge' => 'mod_uckkchallenge',
            'mod_uckkassembly' => 'mod_uckkassembly',
            'mod_uckkarchive' => 'mod_uckkarchive',
            'tool_uckkintegrity' => 'tool_uckkintegrity',
            'report_uckk' => 'report_uckk',
        ];
    }

    /**
     * Return preset options.
     *
     * @return array<string, string>
     */
    private function get_preset_options(): array {
        if (!empty($this->_customdata['presets']) && is_array($this->_customdata['presets'])) {
            return $this->_customdata['presets'];
        }

        return [
            'categories' => get_string('preset_categories', 'tool_uckkseed'),
            'programs' => get_string('preset_programs', 'tool_uckkseed'),
            'pathways' => get_string('preset_pathways', 'tool_uckkseed'),
            'courses' => get_string('preset_courses', 'tool_uckkseed'),
            'cohorts' => get_string('preset_cohorts', 'tool_uckkseed'),
            'roles' => get_string('preset_roles', 'tool_uckkseed'),
            'capabilities' => get_string('preset_capabilities', 'tool_uckkseed'),
            'competencies' => get_string('preset_competencies', 'tool_uckkseed'),
            'badges' => get_string('preset_badges', 'tool_uckkseed'),
            'reports' => get_string('preset_reports', 'tool_uckkseed'),
            'course_templates' => get_string('preset_course_templates', 'tool_uckkseed'),
            'challenge_templates' => get_string('preset_challenge_templates', 'tool_uckkseed'),
            'assembly_templates' => get_string('preset_assembly_templates', 'tool_uckkseed'),
            'archive_templates' => get_string('preset_archive_templates', 'tool_uckkseed'),
        ];
    }

    /**
     * Return default scope.
     *
     * @return string
     */
    private function get_default_scope(): string {
        if (!empty($this->_customdata['scope'])) {
            $scope = clean_param((string)$this->_customdata['scope'], PARAM_ALPHANUMEXT);

            if (array_key_exists($scope, self::get_scope_options())) {
                return $scope;
            }
        }

        return self::SCOPE_SEED_LOGS;
    }

    /**
     * Return return URL from custom data.
     *
     * @return string
     */
    private function get_return_url(): string {
        if (!empty($this->_customdata['returnurl'])) {
            return clean_param((string)$this->_customdata['returnurl'], PARAM_LOCALURL);
        }

        return '';
    }

    /**
     * Normalise autocomplete/select multiple values.
     *
     * @param mixed $value Submitted value.
     * @return string[]
     */
    private function normalise_multi_value(mixed $value): array {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $normalised = [];

        foreach ($value as $item) {
            $item = trim((string)$item);

            if ($item === '') {
                continue;
            }

            $normalised[] = $item;
        }

        return array_values(array_unique($normalised));
    }
}