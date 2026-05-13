<?php
// This file is part of UCKK-Moodle.
//
// UCKK-Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

use block_uckk_dashboard\output\dashboard_block;

/**
 * UCKK dashboard block.
 *
 * This block is the Moodle entry point for the UCKK Joueur/staff cockpit.
 * It does not own canonical UCKK data. It delegates data preparation to
 * renderables/services and rendering to the block renderer.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_uckk_dashboard extends block_base {

    /**
     * Initialise the block.
     *
     * The block title can be customised per instance in {@see specialization()}.
     *
     * @return void
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_uckk_dashboard');
    }

    /**
     * Specialise the block instance after configuration has been loaded.
     *
     * @return void
     */
    public function specialization(): void {
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title, true, [
                'context' => $this->context ?? context_system::instance(),
            ]);
            return;
        }

        $this->title = get_string('pluginname', 'block_uckk_dashboard');
    }

    /**
     * Return the rendered block content.
     *
     * @return stdClass
     */
    public function get_content(): stdClass {
        global $OUTPUT, $PAGE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = (object) [
            'text' => '',
            'footer' => '',
        ];

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $context = $this->context ?? context_system::instance();

        if (!has_capability('block/uckk_dashboard:view', $context)) {
            return $this->content;
        }

        $instanceid = isset($this->instance->id) ? (int) $this->instance->id : 0;

        $renderable = new dashboard_block(
            (int) $USER->id,
            $context,
            $this->config ?? null,
            $instanceid
        );

        $renderer = $PAGE->get_renderer('block_uckk_dashboard');

        $this->content->text = $renderer->render($renderable);

        if (!empty($this->config->showfooterlink)) {
            $url = new moodle_url('/my/');
            $this->content->footer = html_writer::link(
                $url,
                get_string('gotodashboard', 'block_uckk_dashboard')
            );
        }

        $PAGE->requires->js_call_amd('block_uckk_dashboard/dashboard', 'init', [[
            'instanceid' => $instanceid,
            'userid' => (int) $USER->id,
        ]]);

        return $this->content;
    }

    /**
     * Define where the block may be added.
     *
     * @return array<string, bool>
     */
    public function applicable_formats(): array {
        return [
            'all' => false,
            'my' => true,
            'site-index' => true,
            'course-view' => true,
            'user-profile' => true,
        ];
    }

    /**
     * The dashboard should normally appear once per page.
     *
     * @return bool
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Allow per-instance configuration through edit_form.php.
     *
     * @return bool
     */
    public function instance_allow_config(): bool {
        return true;
    }

    /**
     * The block has global settings in settings.php.
     *
     * @return bool
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Add stable wrapper classes for styling and AMD targeting.
     *
     * @return array<string, mixed>
     */
    public function html_attributes(): array {
        $attributes = parent::html_attributes();

        if (empty($attributes['class'])) {
            $attributes['class'] = '';
        }

        $attributes['class'] .= ' block_uckk_dashboard--cockpit';
        $attributes['data-region'] = 'uckk-dashboard-block';

        if (!empty($this->instance->id)) {
            $attributes['data-instance-id'] = (int) $this->instance->id;
        }

        return $attributes;
    }
}