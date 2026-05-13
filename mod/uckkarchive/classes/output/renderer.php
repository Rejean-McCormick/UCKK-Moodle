<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Output renderer for mod_uckkarchive.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkarchive\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;
use stdClass;
use templatable;

/**
 * Renderer for UCKK Archive output objects.
 *
 * This renderer is intentionally presentation-only. It must not validate
 * archive items, revise archive records, expose restricted integrity data,
 * build export packages, calculate provenance, or perform workflow actions.
 *
 * Archive business rules belong in mod_uckkarchive service/local classes.
 */
final class renderer extends plugin_renderer_base {
    /**
     * Render the main archive view.
     *
     * @param archive_view $page Archive view renderable.
     * @return string HTML.
     */
    public function render_archive_view(archive_view $page): string {
        return $this->render_templatable($page, 'mod_uckkarchive/archive_view');
    }

    /**
     * Render one archive item card.
     *
     * @param archive_item_card $card Archive item card renderable.
     * @return string HTML.
     */
    public function render_archive_item_card(archive_item_card $card): string {
        return $this->render_templatable($card, 'mod_uckkarchive/archive_item_card');
    }

    /**
     * Render one Kristal card.
     *
     * @param kristal_card $card Kristal card renderable.
     * @return string HTML.
     */
    public function render_kristal_card(kristal_card $card): string {
        return $this->render_templatable($card, 'mod_uckkarchive/kristal_card');
    }

    /**
     * Render a provenance panel.
     *
     * @param provenance_panel $panel Provenance panel renderable.
     * @return string HTML.
     */
    public function render_provenance_panel(provenance_panel $panel): string {
        return $this->render_templatable($panel, 'mod_uckkarchive/provenance_panel');
    }

    /**
     * Render an archive proof card.
     *
     * Kept untyped because the proof card may be prepared either by a dedicated
     * output object or by another output class using a stdClass context.
     *
     * @param templatable|stdClass|array $proof Proof card data.
     * @return string HTML.
     */
    public function render_proof_card($proof): string {
        return $this->render_flexible_context($proof, 'mod_uckkarchive/proof_card');
    }

    /**
     * Render a validation panel.
     *
     * Kept untyped because validation panel data may be produced by the
     * validation controller, a validation form wrapper, or a dedicated output
     * object depending on the page.
     *
     * @param templatable|stdClass|array $panel Validation panel data.
     * @return string HTML.
     */
    public function render_validation_panel($panel): string {
        return $this->render_flexible_context($panel, 'mod_uckkarchive/validation_panel');
    }

    /**
     * Render an export panel or export summary.
     *
     * This is presentation only. Export package creation remains in services
     * and tasks.
     *
     * @param templatable|stdClass|array $panel Export panel data.
     * @return string HTML.
     */
    public function render_export_panel($panel): string {
        return $this->render_flexible_context($panel, 'mod_uckkarchive/export_panel');
    }

    /**
     * Render a generic archive status badge.
     *
     * @param stdClass|array $badge Badge data.
     * @return string HTML.
     */
    public function render_status_badge($badge): string {
        return $this->render_flexible_context($badge, 'mod_uckkarchive/status_badge');
    }

    /**
     * Render an empty state.
     *
     * @param stdClass|array $emptystate Empty-state data.
     * @return string HTML.
     */
    public function render_empty_state($emptystate): string {
        return $this->render_flexible_context($emptystate, 'mod_uckkarchive/empty_state');
    }

    /**
     * Render a notification list.
     *
     * @param stdClass|array $notices Notice data.
     * @return string HTML.
     */
    public function render_notice_list($notices): string {
        return $this->render_flexible_context($notices, 'mod_uckkarchive/notice_list');
    }

    /**
     * Render a templatable object using a specific Mustache template.
     *
     * @param templatable $output Output object.
     * @param string $templatename Template name.
     * @return string HTML.
     */
    private function render_templatable(templatable $output, string $templatename): string {
        $data = $output->export_for_template($this);

        return $this->render_from_template($templatename, $data);
    }

    /**
     * Render templatable objects, stdClass data, or array data.
     *
     * This helper keeps controllers flexible while preserving the rule that the
     * renderer only renders already-prepared, permission-filtered data.
     *
     * @param templatable|stdClass|array $context Template context.
     * @param string $templatename Template name.
     * @return string HTML.
     */
    private function render_flexible_context($context, string $templatename): string {
        if ($context instanceof templatable) {
            $context = $context->export_for_template($this);
        }

        if (is_array($context)) {
            $context = $this->array_to_object_recursive($context);
        }

        if (!$context instanceof stdClass) {
            $context = (object)[
                'content' => (string)$context,
            ];
        }

        return $this->render_from_template($templatename, $context);
    }

    /**
     * Convert arrays recursively into stdClass structures for Mustache.
     *
     * Numeric arrays remain arrays of converted values. Associative arrays
     * become stdClass objects.
     *
     * @param array $value Raw array.
     * @return array|stdClass
     */
    private function array_to_object_recursive(array $value): array|stdClass {
        $islist = array_is_list($value);

        if ($islist) {
            return array_map(function ($item) {
                if (is_array($item)) {
                    return $this->array_to_object_recursive($item);
                }

                return $item;
            }, $value);
        }

        $object = new stdClass();

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = $this->array_to_object_recursive($item);
            }

            $object->{$key} = $item;
        }

        return $object;
    }

    /**
     * Render an archive item action button.
     *
     * This helper is intentionally small and only formats already-authorised
     * actions prepared by output/service classes.
     *
     * @param string $url Action URL.
     * @param string $label Button label.
     * @param string $style Button style: primary, secondary, danger, warning, link.
     * @param bool $disabled Whether the action is disabled.
     * @return string HTML.
     */
    public function action_button(
        string $url,
        string $label,
        string $style = 'secondary',
        bool $disabled = false
    ): string {
        $allowedstyles = [
            'primary',
            'secondary',
            'danger',
            'warning',
            'link',
        ];

        if (!in_array($style, $allowedstyles, true)) {
            $style = 'secondary';
        }

        $attributes = [
            'href' => $url,
            'class' => 'btn btn-' . $style . ' uckkarchive-action',
        ];

        if ($disabled) {
            $attributes['aria-disabled'] = 'true';
            $attributes['tabindex'] = '-1';
            $attributes['class'] .= ' disabled';
        }

        return \html_writer::link($url, $label, $attributes);
    }

    /**
     * Render a POST action form for privileged archive actions.
     *
     * This helper only renders the form shell. Controllers and services remain
     * responsible for sesskey validation, capabilities, state transitions,
     * provenance, events, and audit records.
     *
     * @param string $url Form action URL.
     * @param string $action Action key.
     * @param string $label Button label.
     * @param array<string, scalar> $hidden Hidden fields.
     * @param string $style Button style.
     * @param bool $requiresreason Whether to include a reason textarea.
     * @return string HTML.
     */
    public function post_action_form(
        string $url,
        string $action,
        string $label,
        array $hidden = [],
        string $style = 'primary',
        bool $requiresreason = false
    ): string {
        $allowedstyles = [
            'primary',
            'secondary',
            'danger',
            'warning',
        ];

        if (!in_array($style, $allowedstyles, true)) {
            $style = 'primary';
        }

        $html = \html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $url,
            'class' => 'uckkarchive-post-action-form',
        ]);

        $html .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        $html .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => clean_param($action, PARAM_ALPHANUMEXT),
        ]);

        foreach ($hidden as $name => $value) {
            $html .= \html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => clean_param((string)$name, PARAM_ALPHANUMEXT),
                'value' => (string)$value,
            ]);
        }

        if ($requiresreason) {
            $fieldid = 'id_uckkarchive_reason_' . uniqid();

            $html .= \html_writer::start_div('form-group');
            $html .= \html_writer::tag('label', get_string('reason', 'uckkarchive'), [
                'for' => $fieldid,
            ]);
            $html .= \html_writer::tag('textarea', '', [
                'id' => $fieldid,
                'name' => 'reason',
                'class' => 'form-control',
                'rows' => 3,
                'maxlength' => 2000,
                'required' => 'required',
            ]);
            $html .= \html_writer::end_div();
        }

        $html .= \html_writer::tag('button', $label, [
            'type' => 'submit',
            'class' => 'btn btn-' . $style,
        ]);

        $html .= \html_writer::end_tag('form');

        return $html;
    }
}
```

Add this string if it is not already in `mod/uckkarchive/lang/en/uckkarchive.php`:

```php
$string['reason'] = 'Reason';
```

Add this to `mod/uckkarchive/lang/fr/uckkarchive.php`:

```php
$string['reason'] = 'Raison';

