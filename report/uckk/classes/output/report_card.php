<?php
// This file is part of Moodle - http://moodle.org/

namespace report_uckk\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Summary card for a UCKK report source.
 *
 * @package    report_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class report_card implements renderable, templatable {
    /** @var string Canonical report key. */
    private string $key;

    /** @var string Localized report title. */
    private string $title;

    /** @var string Localized report description. */
    private string $description;

    /** @var int Number of matching records. */
    private int $total;

    /** @var moodle_url Report URL. */
    private moodle_url $url;

    /** @var bool Whether this card is the selected report. */
    private bool $active;

    /**
     * Constructor.
     *
     * @param string $key Canonical report key.
     * @param string $title Localized title.
     * @param string $description Localized description.
     * @param int $total Number of matching records.
     * @param moodle_url $url Report URL.
     * @param bool $active Whether the card is active.
     */
    public function __construct(
        string $key,
        string $title,
        string $description,
        int $total,
        moodle_url $url,
        bool $active = false
    ) {
        $this->key = $key;
        $this->title = $title;
        $this->description = $description;
        $this->total = $total;
        $this->url = $url;
        $this->active = $active;
    }

    /**
     * Export card data for the Mustache template.
     *
     * Template: report_uckk/report_card
     *
     * @param renderer_base $output Renderer.
     * @return array<string,mixed>
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'total' => $this->total,
            'url' => $this->url->out(false),
            'active' => $this->active,
            'inactive' => !$this->active,
            'totaltext' => get_string('totalrows', 'report_uckk', $this->total),
        ];
    }
}