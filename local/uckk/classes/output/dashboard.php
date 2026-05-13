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
 * Dashboard output object for local_uckk.
 *
 * This class prepares a Mustache-ready dashboard context for UCKK-Moodle.
 *
 * It must not:
 * - query large datasets directly;
 * - decide permissions;
 * - award badges;
 * - validate competencies;
 * - validate archive items;
 * - open or close integrity cases;
 * - decide AI outputs;
 * - duplicate data owned by other plugins.
 *
 * Data should be prepared by API classes, services, controllers or reports,
 * then injected into this output object as simple arrays.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Dashboard renderable for UCKK-Moodle.
 *
 * @package local_uckk
 */
final class dashboard implements renderable, templatable {
    /** Default template name. */
    public const TEMPLATE = 'local_uckk/dashboard';

    /** Dashboard region: player. */
    public const REGION_PLAYER = 'player';

    /** Dashboard region: mentor. */
    public const REGION_MENTOR = 'mentor';

    /** Dashboard region: manager. */
    public const REGION_MANAGER = 'manager';

    /** Dashboard region: archivist. */
    public const REGION_ARCHIVIST = 'archivist';

    /** Dashboard region: inquisitor. */
    public const REGION_INQUISITOR = 'inquisitor';

    /** Status type: neutral. */
    public const STATUS_NEUTRAL = 'neutral';

    /** Status type: success. */
    public const STATUS_SUCCESS = 'success';

    /** Status type: warning. */
    public const STATUS_WARNING = 'warning';

    /** Status type: danger. */
    public const STATUS_DANGER = 'danger';

    /** Status type: info. */
    public const STATUS_INFO = 'info';

    /** @var int|null Dashboard owner user id. */
    private ?int $userid;

    /** @var string Dashboard region. */
    private string $region;

    /** @var string Dashboard title. */
    private string $title;

    /** @var string Dashboard subtitle. */
    private string $subtitle;

    /** @var string Intro text. */
    private string $intro;

    /** @var string Canonical tagline. */
    private string $tagline;

    /** @var string Canonical formula. */
    private string $formula;

    /** @var string Boundary notice. */
    private string $boundarynotice;

    /** @var array<int, array<string, mixed>> Dashboard cards. */
    private array $cards;

    /** @var array<int, array<string, mixed>> Metrics. */
    private array $metrics;

    /** @var array<int, array<string, mixed>> Quick links. */
    private array $quicklinks;

    /** @var array<int, array<string, mixed>> Tasks. */
    private array $tasks;

    /** @var array<int, array<string, mixed>> Alerts. */
    private array $alerts;

    /** @var array<int, array<string, mixed>> Integrations. */
    private array $integrations;

    /** @var array<string, mixed> Progress summary. */
    private array $progress;

    /** @var array<string, mixed> Capability flags already checked by the controller/service. */
    private array $capabilities;

    /** @var array<string, mixed> Optional display flags. */
    private array $display;

    /**
     * Constructor.
     *
     * Expected data:
     *
     * ```php
     * new dashboard([
     *     'userid' => $USER->id,
     *     'region' => dashboard::REGION_PLAYER,
     *     'title' => get_string('dashboard_title', 'local_uckk'),
     *     'cards' => [...],
     *     'metrics' => [...],
     *     'quicklinks' => [...],
     *     'tasks' => [...],
     *     'alerts' => [...],
     *     'integrations' => [...],
     *     'progress' => [...],
     *     'capabilities' => [...],
     *     'display' => [...],
     * ]);
     * ```
     *
     * @param array<string, mixed> $data Dashboard data.
     */
    public function __construct(array $data = []) {
        $this->userid = isset($data['userid']) ? (int)$data['userid'] : null;
        $this->region = self::normalise_key($data['region'] ?? self::REGION_PLAYER);

        $this->title = self::clean_text($data['title'] ?? get_string('dashboard_title', 'local_uckk'));
        $this->subtitle = self::clean_text($data['subtitle'] ?? get_string('dashboard_subtitle', 'local_uckk'));
        $this->intro = self::clean_text($data['intro'] ?? get_string('dashboard_intro', 'local_uckk'));
        $this->tagline = self::clean_text($data['tagline'] ?? get_string('campus_tagline', 'local_uckk'));
        $this->formula = self::clean_text($data['formula'] ?? get_string('campus_formula', 'local_uckk'));
        $this->boundarynotice = self::clean_text(
            $data['boundarynotice'] ?? get_string('campus_boundary_notice', 'local_uckk')
        );

        $this->cards = self::normalise_cards($data['cards'] ?? []);
        $this->metrics = self::normalise_metrics($data['metrics'] ?? []);
        $this->quicklinks = self::normalise_links($data['quicklinks'] ?? []);
        $this->tasks = self::normalise_tasks($data['tasks'] ?? []);
        $this->alerts = self::normalise_alerts($data['alerts'] ?? []);
        $this->integrations = self::normalise_integrations($data['integrations'] ?? []);
        $this->progress = self::normalise_progress($data['progress'] ?? []);
        $this->capabilities = self::normalise_assoc($data['capabilities'] ?? []);
        $this->display = self::normalise_assoc($data['display'] ?? []);
    }

    /**
     * Export data for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $data->userid = $this->userid;
        $data->region = $this->region;
        $data->title = $this->title;
        $data->subtitle = $this->subtitle;
        $data->hassubtitle = $this->subtitle !== '';
        $data->intro = $this->intro;
        $data->hasintro = $this->intro !== '';
        $data->tagline = $this->tagline;
        $data->hastagline = $this->tagline !== '';
        $data->formula = $this->formula;
        $data->hasformula = $this->formula !== '';
        $data->boundarynotice = $this->boundarynotice;
        $data->hasboundarynotice = $this->boundarynotice !== '';

        $data->isplayer = $this->region === self::REGION_PLAYER;
        $data->ismentor = $this->region === self::REGION_MENTOR;
        $data->ismanager = $this->region === self::REGION_MANAGER;
        $data->isarchivist = $this->region === self::REGION_ARCHIVIST;
        $data->isinquisitor = $this->region === self::REGION_INQUISITOR;

        $data->cards = $this->cards;
        $data->hascards = !empty($this->cards);

        $data->primarycards = array_values(array_filter($this->cards, static function(array $card): bool {
            return !empty($card['primary']);
        }));
        $data->hasprimarycards = !empty($data->primarycards);

        $data->secondarycards = array_values(array_filter($this->cards, static function(array $card): bool {
            return empty($card['primary']);
        }));
        $data->hassecondarycards = !empty($data->secondarycards);

        $data->metrics = $this->metrics;
        $data->hasmetrics = !empty($this->metrics);

        $data->quicklinks = $this->quicklinks;
        $data->hasquicklinks = !empty($this->quicklinks);

        $data->tasks = $this->tasks;
        $data->hastasks = !empty($this->tasks);
        $data->opentaskcount = self::count_by_empty_flag($this->tasks, 'completed');

        $data->alerts = $this->alerts;
        $data->hasalerts = !empty($this->alerts);
        $data->criticalalertcount = self::count_by_value($this->alerts, 'type', self::STATUS_DANGER);
        $data->warningalertcount = self::count_by_value($this->alerts, 'type', self::STATUS_WARNING);

        $data->integrations = $this->integrations;
        $data->hasintegrations = !empty($this->integrations);
        $data->missingintegrationcount = self::count_by_empty_flag($this->integrations, 'installed');

        $data->progress = (object)$this->progress;
        $data->hasprogress = !empty($this->progress);
        $data->progresspercent = $this->progress['percent'] ?? 0;
        $data->progresslabel = $this->progress['label'] ?? '';
        $data->hasprogresslabel = !empty($data->progresslabel);

        $data->capabilities = (object)$this->capabilities;
        $data->display = (object)$this->display;

        $data->canmanageprograms = !empty($this->capabilities['manageprograms']);
        $data->canmanagepathways = !empty($this->capabilities['managepathways']);
        $data->canmanageprofiles = !empty($this->capabilities['manageprofiles']);
        $data->canmanagecanon = !empty($this->capabilities['managecanon']);
        $data->canviewreports = !empty($this->capabilities['viewreports']);
        $data->canexportdata = !empty($this->capabilities['exportdata']);

        $data->showrecognitionnotice = $this->display['showrecognitionnotice'] ?? true;
        $data->recognitionnotice = get_string('campus_recognition_notice', 'local_uckk');

        $data->showintegritynotice = $this->display['showintegritynotice'] ?? true;
        $data->integritynotice = get_string('dashboard_integrity_notice', 'local_uckk');

        $data->showainotice = $this->display['showainotice'] ?? true;
        $data->ainotice = get_string('dashboard_ai_notice', 'local_uckk');

        $data->emptytitle = get_string('dashboard_empty_title', 'local_uckk');
        $data->emptytext = get_string('dashboard_empty_text', 'local_uckk');

        return $data;
    }

    /**
     * Return the template name intended for this output object.
     *
     * This method is useful for renderers that support explicit named templates.
     *
     * @return string
     */
    public function get_template_name(): string {
        return self::TEMPLATE;
    }

    /**
     * Build a default player dashboard shell.
     *
     * The returned object contains only display placeholders. Real counts and
     * links should be injected by controller/service code.
     *
     * @param int|null $userid Optional user id.
     * @return self
     */
    public static function default_player_dashboard(?int $userid = null): self {
        return new self([
            'userid' => $userid,
            'region' => self::REGION_PLAYER,
            'cards' => [
                [
                    'key' => 'pathway',
                    'title' => get_string('dashboard_card_pathway_title', 'local_uckk'),
                    'description' => get_string('dashboard_card_pathway_desc', 'local_uckk'),
                    'url' => new moodle_url('/local/uckk/pathways.php'),
                    'icon' => 'pathway',
                    'type' => self::STATUS_INFO,
                    'primary' => true,
                    'enabled' => true,
                ],
                [
                    'key' => 'courses',
                    'title' => get_string('dashboard_card_courses_title', 'local_uckk'),
                    'description' => get_string('dashboard_card_courses_desc', 'local_uckk'),
                    'url' => new moodle_url('/my/courses.php'),
                    'icon' => 'course',
                    'type' => self::STATUS_INFO,
                    'primary' => true,
                    'enabled' => true,
                ],
                [
                    'key' => 'challenges',
                    'title' => get_string('dashboard_card_challenges_title', 'local_uckk'),
                    'description' => get_string('dashboard_card_challenges_desc', 'local_uckk'),
                    'url' => '',
                    'icon' => 'challenge',
                    'type' => self::STATUS_WARNING,
                    'primary' => true,
                    'enabled' => false,
                ],
                [
                    'key' => 'assemblies',
                    'title' => get_string('dashboard_card_assemblies_title', 'local_uckk'),
                    'description' => get_string('dashboard_card_assemblies_desc', 'local_uckk'),
                    'url' => '',
                    'icon' => 'assembly',
                    'type' => self::STATUS_INFO,
                    'primary' => false,
                    'enabled' => false,
                ],
                [
                    'key' => 'archives',
                    'title' => get_string('dashboard_card_archives_title', 'local_uckk'),
                    'description' => get_string('dashboard_card_archives_desc', 'local_uckk'),
                    'url' => '',
                    'icon' => 'archive',
                    'type' => self::STATUS_NEUTRAL,
                    'primary' => false,
                    'enabled' => false,
                ],
                [
                    'key' => 'badges',
                    'title' => get_string('dashboard_card_badges_title', 'local_uckk'),
                    'description' => get_string('dashboard_card_badges_desc', 'local_uckk'),
                    'url' => new moodle_url('/badges/mybadges.php'),
                    'icon' => 'badge',
                    'type' => self::STATUS_SUCCESS,
                    'primary' => false,
                    'enabled' => true,
                ],
            ],
        ]);
    }

    /**
     * Normalise card data.
     *
     * @param mixed $cards Raw cards.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_cards($cards): array {
        if (!is_array($cards)) {
            return [];
        }

        $normalised = [];

        foreach ($cards as $card) {
            if (!is_array($card)) {
                continue;
            }

            $key = self::normalise_key($card['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $url = self::normalise_url($card['url'] ?? '');

            $normalised[] = [
                'key' => $key,
                'title' => self::clean_text($card['title'] ?? ''),
                'description' => self::clean_text($card['description'] ?? ''),
                'url' => $url,
                'hasurl' => $url !== '',
                'icon' => self::normalise_key($card['icon'] ?? $key),
                'type' => self::normalise_status_type($card['type'] ?? self::STATUS_NEUTRAL),
                'status' => self::clean_text($card['status'] ?? ''),
                'hasstatus' => !empty($card['status']),
                'value' => self::clean_text($card['value'] ?? ''),
                'hasvalue' => isset($card['value']) && (string)$card['value'] !== '',
                'caption' => self::clean_text($card['caption'] ?? ''),
                'hascaption' => !empty($card['caption']),
                'primary' => !empty($card['primary']),
                'enabled' => !array_key_exists('enabled', $card) || !empty($card['enabled']),
                'disabled' => array_key_exists('enabled', $card) && empty($card['enabled']),
                'classes' => self::clean_css_classes($card['classes'] ?? ''),
                'dataattributes' => self::normalise_template_attributes($card['dataattributes'] ?? []),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise metric data.
     *
     * @param mixed $metrics Raw metrics.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_metrics($metrics): array {
        if (!is_array($metrics)) {
            return [];
        }

        $normalised = [];

        foreach ($metrics as $metric) {
            if (!is_array($metric)) {
                continue;
            }

            $key = self::normalise_key($metric['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $normalised[] = [
                'key' => $key,
                'label' => self::clean_text($metric['label'] ?? ''),
                'value' => self::clean_text($metric['value'] ?? '0'),
                'caption' => self::clean_text($metric['caption'] ?? ''),
                'hascaption' => !empty($metric['caption']),
                'type' => self::normalise_status_type($metric['type'] ?? self::STATUS_NEUTRAL),
                'icon' => self::normalise_key($metric['icon'] ?? $key),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise links.
     *
     * @param mixed $links Raw links.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_links($links): array {
        if (!is_array($links)) {
            return [];
        }

        $normalised = [];

        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }

            $label = self::clean_text($link['label'] ?? '');
            $url = self::normalise_url($link['url'] ?? '');

            if ($label === '' || $url === '') {
                continue;
            }

            $normalised[] = [
                'key' => self::normalise_key($link['key'] ?? $label),
                'label' => $label,
                'url' => $url,
                'icon' => self::normalise_key($link['icon'] ?? ''),
                'hasicon' => !empty($link['icon']),
                'external' => !empty($link['external']),
                'classes' => self::clean_css_classes($link['classes'] ?? ''),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise tasks.
     *
     * @param mixed $tasks Raw tasks.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_tasks($tasks): array {
        if (!is_array($tasks)) {
            return [];
        }

        $normalised = [];

        foreach ($tasks as $task) {
            if (!is_array($task)) {
                continue;
            }

            $key = self::normalise_key($task['key'] ?? '');
            $title = self::clean_text($task['title'] ?? '');

            if ($key === '' && $title === '') {
                continue;
            }

            $url = self::normalise_url($task['url'] ?? '');
            $duedate = self::clean_text($task['duedate'] ?? '');

            $normalised[] = [
                'key' => $key,
                'title' => $title,
                'description' => self::clean_text($task['description'] ?? ''),
                'hasdescription' => !empty($task['description']),
                'url' => $url,
                'hasurl' => $url !== '',
                'duedate' => $duedate,
                'hasduedate' => $duedate !== '',
                'type' => self::normalise_status_type($task['type'] ?? self::STATUS_NEUTRAL),
                'completed' => !empty($task['completed']),
                'icon' => self::normalise_key($task['icon'] ?? ''),
                'hasicon' => !empty($task['icon']),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise alerts.
     *
     * @param mixed $alerts Raw alerts.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_alerts($alerts): array {
        if (!is_array($alerts)) {
            return [];
        }

        $normalised = [];

        foreach ($alerts as $alert) {
            if (!is_array($alert)) {
                continue;
            }

            $message = self::clean_text($alert['message'] ?? '');
            if ($message === '') {
                continue;
            }

            $url = self::normalise_url($alert['url'] ?? '');

            $normalised[] = [
                'key' => self::normalise_key($alert['key'] ?? ''),
                'title' => self::clean_text($alert['title'] ?? ''),
                'hastitle' => !empty($alert['title']),
                'message' => $message,
                'type' => self::normalise_status_type($alert['type'] ?? self::STATUS_INFO),
                'url' => $url,
                'hasurl' => $url !== '',
                'dismissible' => !empty($alert['dismissible']),
                'icon' => self::normalise_key($alert['icon'] ?? ''),
                'hasicon' => !empty($alert['icon']),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise integrations.
     *
     * @param mixed $integrations Raw integrations.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_integrations($integrations): array {
        if (!is_array($integrations)) {
            return [];
        }

        $normalised = [];

        foreach ($integrations as $integration) {
            if (!is_array($integration)) {
                continue;
            }

            $key = self::normalise_key($integration['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $installed = !empty($integration['installed']);

            $normalised[] = [
                'key' => $key,
                'label' => self::clean_text($integration['label'] ?? $key),
                'installed' => $installed,
                'missing' => !$installed,
                'status' => $installed
                    ? get_string('status_available', 'local_uckk')
                    : get_string('status_missingplugin', 'local_uckk'),
                'type' => $installed ? self::STATUS_SUCCESS : self::STATUS_WARNING,
                'url' => self::normalise_url($integration['url'] ?? ''),
                'hasurl' => !empty($integration['url']),
            ];
        }

        return $normalised;
    }

    /**
     * Normalise progress data.
     *
     * @param mixed $progress Raw progress.
     * @return array<string, mixed>
     */
    private static function normalise_progress($progress): array {
        if (!is_array($progress)) {
            return [];
        }

        $percent = isset($progress['percent']) ? (int)$progress['percent'] : 0;
        $percent = max(0, min(100, $percent));

        return [
            'percent' => $percent,
            'label' => self::clean_text($progress['label'] ?? ''),
            'completed' => isset($progress['completed']) ? (int)$progress['completed'] : 0,
            'total' => isset($progress['total']) ? (int)$progress['total'] : 0,
            'caption' => self::clean_text($progress['caption'] ?? ''),
            'hascaption' => !empty($progress['caption']),
        ];
    }

    /**
     * Normalise associative values as booleans/scalars.
     *
     * @param mixed $values Raw values.
     * @return array<string, mixed>
     */
    private static function normalise_assoc($values): array {
        if (!is_array($values)) {
            return [];
        }

        $normalised = [];

        foreach ($values as $key => $value) {
            $cleankey = self::normalise_key((string)$key);

            if ($cleankey === '') {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $normalised[$cleankey] = $value;
            } else if (is_string($value)) {
                $normalised[$cleankey] = self::clean_text($value);
            }
        }

        return $normalised;
    }

    /**
     * Normalise data attributes for templates.
     *
     * @param mixed $attributes Raw attributes.
     * @return array<int, array{name: string, value: string}>
     */
    private static function normalise_template_attributes($attributes): array {
        if (!is_array($attributes)) {
            return [];
        }

        $normalised = [];

        foreach ($attributes as $name => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $name = clean_param((string)$name, PARAM_ALPHANUMEXT);
            $value = self::clean_text((string)$value);

            if ($name === '' || $value === '') {
                continue;
            }

            $normalised[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        return $normalised;
    }

    /**
     * Normalise a URL-like value.
     *
     * @param mixed $url URL value.
     * @return string
     */
    private static function normalise_url($url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (is_string($url)) {
            return trim($url);
        }

        return '';
    }

    /**
     * Normalise a key.
     *
     * @param mixed $key Raw key.
     * @return string
     */
    private static function normalise_key($key): string {
        if (!is_scalar($key)) {
            return '';
        }

        $key = \core_text::strtolower(trim((string)$key));
        $key = str_replace(['-', ' ', '.', '/', '\\'], '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        $key = preg_replace('/[^a-z0-9_]/', '', $key);

        return trim($key, '_');
    }

    /**
     * Normalise a dashboard status type.
     *
     * @param mixed $type Raw type.
     * @return string
     */
    private static function normalise_status_type($type): string {
        $type = self::normalise_key($type);

        $allowed = [
            self::STATUS_NEUTRAL,
            self::STATUS_SUCCESS,
            self::STATUS_WARNING,
            self::STATUS_DANGER,
            self::STATUS_INFO,
        ];

        return in_array($type, $allowed, true) ? $type : self::STATUS_NEUTRAL;
    }

    /**
     * Clean text for template display.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function clean_text($value): string {
        if (!is_scalar($value)) {
            return '';
        }

        return trim((string)$value);
    }

    /**
     * Clean CSS class string.
     *
     * @param mixed $classes Raw classes.
     * @return string
     */
    private static function clean_css_classes($classes): string {
        if (!is_scalar($classes)) {
            return '';
        }

        $classes = preg_split('/\s+/', trim((string)$classes)) ?: [];
        $clean = [];

        foreach ($classes as $class) {
            $class = clean_param($class, PARAM_ALPHANUMEXT);

            if ($class !== '') {
                $clean[] = $class;
            }
        }

        return implode(' ', array_unique($clean));
    }

    /**
     * Count items where a key equals a value.
     *
     * @param array<int, array<string, mixed>> $items Items.
     * @param string $key Field key.
     * @param mixed $value Expected value.
     * @return int
     */
    private static function count_by_value(array $items, string $key, $value): int {
        $count = 0;

        foreach ($items as $item) {
            if (($item[$key] ?? null) === $value) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count items where a boolean-like key is empty.
     *
     * @param array<int, array<string, mixed>> $items Items.
     * @param string $key Field key.
     * @return int
     */
    private static function count_by_empty_flag(array $items, string $key): int {
        $count = 0;

        foreach ($items as $item) {
            if (empty($item[$key])) {
                $count++;
            }
        }

        return $count;
    }
}

