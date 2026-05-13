<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * Renderable dashboard block data for block_uckk_dashboard.
 *
 * This class prepares display data only. It must not query the database,
 * decide permissions, evaluate challenges, validate archives, close integrity
 * cases, award badges, certify competencies, or store user preferences.
 *
 * All records passed into this class must already be filtered by the service
 * layer according to Moodle capabilities, UCKK visibility rules, provenance
 * rules, and privacy constraints.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uckk_dashboard\output;

use coding_exception;
use context;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use Stringable;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable dashboard block.
 *
 * The dashboard block is the user-facing cockpit for UCKK summaries. It accepts
 * already-authorised summary data and exports a stable context for:
 *
 * - blocks/uckk_dashboard/templates/dashboard_block.mustache
 * - blocks/uckk_dashboard/amd/src/dashboard.js
 * - blocks/uckk_dashboard/amd/src/refresh.js
 */
class dashboard_block implements renderable, templatable {
    /** Automatic dashboard mode. */
    public const VIEWMODE_AUTO = 'auto';

    /** Joueur dashboard mode. */
    public const VIEWMODE_PLAYER = 'player';

    /** Mentor dashboard mode. */
    public const VIEWMODE_MENTOR = 'mentor';

    /** Archiviste dashboard mode. */
    public const VIEWMODE_ARCHIVIST = 'archivist';

    /** Inquisiteur dashboard mode. */
    public const VIEWMODE_INQUISITOR = 'inquisitor';

    /** Gestionnaire dashboard mode. */
    public const VIEWMODE_MANAGER = 'manager';

    /** Pathway summary card. */
    public const CARD_PATHWAY = 'pathway';

    /** Tronc commun summary card. */
    public const CARD_TRONC_COMMUN = 'tronccommun';

    /** Competency summary card. */
    public const CARD_COMPETENCIES = 'competencies';

    /** Badge summary card. */
    public const CARD_BADGES = 'badges';

    /** Challenge summary card. */
    public const CARD_CHALLENGES = 'challenges';

    /** Assembly summary card. */
    public const CARD_ASSEMBLIES = 'assemblies';

    /** Archive summary card. */
    public const CARD_ARCHIVE = 'archive';

    /** Integrity feedback summary card. */
    public const CARD_INTEGRITY = 'integrity';

    /** Deadline summary card. */
    public const CARD_DEADLINES = 'deadlines';

    /** Portfolio summary card. */
    public const CARD_PORTFOLIO = 'portfolio';

    /** Valid view modes. */
    private const VALID_VIEWMODES = [
        self::VIEWMODE_AUTO,
        self::VIEWMODE_PLAYER,
        self::VIEWMODE_MENTOR,
        self::VIEWMODE_ARCHIVIST,
        self::VIEWMODE_INQUISITOR,
        self::VIEWMODE_MANAGER,
    ];

    /** Canonical dashboard card order. */
    private const CARD_ORDER = [
        self::CARD_PATHWAY,
        self::CARD_TRONC_COMMUN,
        self::CARD_COMPETENCIES,
        self::CARD_BADGES,
        self::CARD_CHALLENGES,
        self::CARD_ASSEMBLIES,
        self::CARD_ARCHIVE,
        self::CARD_INTEGRITY,
        self::CARD_DEADLINES,
        self::CARD_PORTFOLIO,
    ];

    /** Safe visual tone suffixes for templates. */
    private const TONES = [
        'default',
        'primary',
        'secondary',
        'success',
        'info',
        'warning',
        'danger',
        'light',
        'dark',
        'muted',
    ];

    /** @var context Moodle context in which the block is rendered. */
    private context $context;

    /** @var int User id whose dashboard is being displayed. */
    private int $userid;

    /** @var array Dashboard cards keyed by canonical card key. */
    private array $cards;

    /** @var array Footer/header actions prepared by caller. */
    private array $actions;

    /** @var string Current dashboard view mode. */
    private string $viewmode;

    /** @var int Client-side refresh interval in seconds. Zero disables auto-refresh. */
    private int $refreshinterval;

    /** @var int Maximum number of summary items per card. Zero means no limit. */
    private int $maxsummaryitems;

    /** @var bool Whether the UI may show restricted indicators. */
    private bool $showrestrictedindicators;

    /** @var bool Whether the UI may expose a manual refresh control. */
    private bool $allowmanualrefresh;

    /** @var moodle_url|null Optional refresh endpoint URL prepared by the caller. */
    private ?moodle_url $refreshurl;

    /** @var array Non-blocking display notices prepared by the caller. */
    private array $notices;

    /**
     * Constructor.
     *
     * Expected card shape:
     *
     * [
     *     'key' => 'pathway',
     *     'title' => 'My pathway',
     *     'subtitle' => 'Optional subtitle',
     *     'summary' => 'Optional summary',
     *     'count' => 3,
     *     'countlabel' => 'items',
     *     'status' => 'active',
     *     'tone' => 'success',
     *     'url' => new moodle_url('/local/uckk/pathway.php'),
     *     'restricted' => false,
     *     'items' => [
     *         [
     *             'label' => 'UCKK-TC101',
     *             'value' => 'completed',
     *             'detail' => 'Optional detail',
     *             'url' => new moodle_url('/course/view.php', ['id' => 2]),
     *             'tone' => 'success',
     *         ],
     *     ],
     *     'actions' => [
     *         [
     *             'label' => 'Open',
     *             'url' => new moodle_url('/local/uckk/pathway.php'),
     *             'primary' => true,
     *         ],
     *     ],
     * ]
     *
     * A card may also be a templatable summary object. In that case, its
     * exported data must contain at least a valid `key`.
     *
     * @param context $context Moodle context in which the block is rendered.
     * @param int $userid User id whose dashboard is displayed.
     * @param array $cards Dashboard card data, keyed or unkeyed.
     * @param array $actions Optional dashboard-level actions.
     * @param string $viewmode Dashboard mode.
     * @param array $options Display options.
     */
    public function __construct(
        context $context,
        int $userid,
        array $cards = [],
        array $actions = [],
        string $viewmode = self::VIEWMODE_AUTO,
        array $options = []
    ) {
        $this->context = $context;
        $this->userid = $userid;
        $this->cards = $cards;
        $this->actions = $actions;
        $this->viewmode = $this->normalise_viewmode($viewmode);
        $this->refreshinterval = max(0, (int)($options['refreshinterval'] ?? 0));
        $this->maxsummaryitems = max(0, (int)($options['maxsummaryitems'] ?? 5));
        $this->showrestrictedindicators = !empty($options['showrestrictedindicators']);
        $this->allowmanualrefresh = !empty($options['allowmanualrefresh']);
        $this->refreshurl = $options['refreshurl'] ?? null;
        $this->notices = $options['notices'] ?? [];

        if ($this->refreshurl !== null && !$this->refreshurl instanceof moodle_url) {
            throw new coding_exception('The refreshurl option must be a moodle_url instance.');
        }
    }

    /**
     * Export dashboard data for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $cards = $this->export_cards($output);
        $actions = $this->export_actions($this->actions);
        $notices = $this->export_notices($this->notices);

        $data = new stdClass();
        $data->uniqid = uniqid('block-uckk-dashboard-', false);
        $data->component = 'block_uckk_dashboard';
        $data->userid = $this->userid;
        $data->contextid = $this->context->id;
        $data->viewmode = $this->viewmode;
        $data->viewmodeclass = 'uckk-dashboard--' . $this->viewmode;
        $data->viewmodelabel = get_string('viewmode_' . $this->viewmode, 'block_uckk_dashboard');

        $data->hascards = !empty($cards);
        $data->cards = $cards;
        $data->isempty = empty($cards);

        $data->hasactions = !empty($actions);
        $data->actions = $actions;

        $data->hasnotices = !empty($notices);
        $data->notices = $notices;

        $data->allowmanualrefresh = $this->allowmanualrefresh;
        $data->showrestrictedindicators = $this->showrestrictedindicators;

        $data->refreshinterval = $this->refreshinterval;
        $data->autorefresh = $this->refreshinterval > 0;
        $data->hasrefreshurl = $this->refreshurl instanceof moodle_url;
        $data->refreshurl = $this->refreshurl instanceof moodle_url ? $this->refreshurl->out(false) : '';

        return $data;
    }

    /**
     * Export cards in canonical order.
     *
     * @param renderer_base $output Renderer.
     * @return array
     */
    private function export_cards(renderer_base $output): array {
        $normalised = [];

        foreach ($this->cards as $fallbackkey => $card) {
            $carddata = $this->normalise_card($card, $output, is_string($fallbackkey) ? $fallbackkey : null);
            $normalised[$carddata->key] = $carddata;
        }

        $ordered = [];

        foreach (self::CARD_ORDER as $key) {
            if (isset($normalised[$key])) {
                $ordered[] = $normalised[$key];
                unset($normalised[$key]);
            }
        }

        foreach ($normalised as $card) {
            $ordered[] = $card;
        }

        return $ordered;
    }

    /**
     * Normalise a single card.
     *
     * @param mixed $card Card data.
     * @param renderer_base $output Renderer.
     * @param string|null $fallbackkey Optional fallback key.
     * @return stdClass
     */
    private function normalise_card(mixed $card, renderer_base $output, ?string $fallbackkey = null): stdClass {
        $raw = $this->to_array($card, $output);

        $key = $this->clean_key((string)($raw['key'] ?? $fallbackkey ?? ''));
        if ($key === '') {
            throw new coding_exception('Dashboard cards must define a non-empty key.');
        }

        $items = $this->export_items($raw['items'] ?? []);
        $itemcount = count($items);
        $hasmoreitems = false;
        $moreitemcount = 0;

        if ($this->maxsummaryitems > 0 && $itemcount > $this->maxsummaryitems) {
            $hasmoreitems = true;
            $moreitemcount = $itemcount - $this->maxsummaryitems;
            $items = array_slice($items, 0, $this->maxsummaryitems);
        }

        $url = $this->normalise_url($raw['url'] ?? null);
        $actions = $this->export_actions($raw['actions'] ?? []);

        $data = new stdClass();
        $data->key = $key;
        $data->csskey = $this->clean_css_suffix($key);
        $data->title = $this->normalise_text($raw['title'] ?? $this->get_default_card_title($key));
        $data->subtitle = $this->normalise_text($raw['subtitle'] ?? '');
        $data->hassubtitle = $data->subtitle !== '';
        $data->summary = $this->normalise_text($raw['summary'] ?? '');
        $data->hassummary = $data->summary !== '';

        $data->count = isset($raw['count']) ? (int)$raw['count'] : $itemcount;
        $data->hascount = isset($raw['count']) || $itemcount > 0;
        $data->countlabel = $this->normalise_text($raw['countlabel'] ?? '');

        $data->status = $this->normalise_text($raw['status'] ?? '');
        $data->hasstatus = $data->status !== '';
        $data->tone = $this->normalise_tone($raw['tone'] ?? 'default');
        $data->icon = $this->normalise_text($raw['icon'] ?? '');
        $data->hasicon = $data->icon !== '';

        $data->url = $url;
        $data->hasurl = $url !== '';

        $data->items = $items;
        $data->hasitems = !empty($items);
        $data->itemcount = $itemcount;
        $data->hasmoreitems = $hasmoreitems;
        $data->moreitemcount = $moreitemcount;

        $data->actions = $actions;
        $data->hasactions = !empty($actions);

        $data->restricted = !empty($raw['restricted']);
        $data->showrestrictedindicator = $data->restricted && $this->showrestrictedindicators;

        $data->empty = empty($items) && $data->summary === '';
        $data->emptytext = $this->normalise_text(
            $raw['emptytext'] ?? get_string('dashboardcardempty', 'block_uckk_dashboard')
        );

        return $data;
    }

    /**
     * Export summary items.
     *
     * @param mixed $items Raw items.
     * @return array
     */
    private function export_items(mixed $items): array {
        if (!is_array($items)) {
            return [];
        }

        $exported = [];

        foreach ($items as $item) {
            if ($item instanceof stdClass) {
                $item = (array)$item;
            }

            if (!is_array($item)) {
                continue;
            }

            $url = $this->normalise_url($item['url'] ?? null);

            $data = new stdClass();
            $data->label = $this->normalise_text($item['label'] ?? '');
            $data->value = $this->normalise_text($item['value'] ?? '');
            $data->detail = $this->normalise_text($item['detail'] ?? '');
            $data->hasvalue = $data->value !== '';
            $data->hasdetail = $data->detail !== '';
            $data->url = $url;
            $data->hasurl = $url !== '';
            $data->status = $this->normalise_text($item['status'] ?? '');
            $data->hasstatus = $data->status !== '';
            $data->tone = $this->normalise_tone($item['tone'] ?? 'default');
            $data->icon = $this->normalise_text($item['icon'] ?? '');
            $data->hasicon = $data->icon !== '';

            if ($data->label !== '' || $data->value !== '' || $data->detail !== '') {
                $exported[] = $data;
            }
        }

        return $exported;
    }

    /**
     * Export dashboard actions.
     *
     * @param mixed $actions Raw actions.
     * @return array
     */
    private function export_actions(mixed $actions): array {
        if (!is_array($actions)) {
            return [];
        }

        $exported = [];

        foreach ($actions as $action) {
            if ($action instanceof stdClass) {
                $action = (array)$action;
            }

            if (!is_array($action)) {
                continue;
            }

            $url = $this->normalise_url($action['url'] ?? null);
            $label = $this->normalise_text($action['label'] ?? '');

            if ($label === '' || $url === '') {
                continue;
            }

            $data = new stdClass();
            $data->label = $label;
            $data->url = $url;
            $data->primary = !empty($action['primary']);
            $data->secondary = empty($action['primary']);
            $data->disabled = !empty($action['disabled']);
            $data->icon = $this->normalise_text($action['icon'] ?? '');
            $data->hasicon = $data->icon !== '';

            $exported[] = $data;
        }

        return $exported;
    }

    /**
     * Export dashboard notices.
     *
     * @param mixed $notices Raw notices.
     * @return array
     */
    private function export_notices(mixed $notices): array {
        if (!is_array($notices)) {
            return [];
        }

        $exported = [];

        foreach ($notices as $notice) {
            if ($notice instanceof stdClass) {
                $notice = (array)$notice;
            }

            if (!is_array($notice)) {
                $notice = ['message' => $notice];
            }

            $message = $this->normalise_text($notice['message'] ?? '');
            if ($message === '') {
                continue;
            }

            $data = new stdClass();
            $data->message = $message;
            $data->tone = $this->normalise_tone($notice['tone'] ?? 'info');
            $data->dismissible = !empty($notice['dismissible']);

            $exported[] = $data;
        }

        return $exported;
    }

    /**
     * Convert supported data types to array.
     *
     * @param mixed $value Value to convert.
     * @param renderer_base $output Renderer.
     * @return array
     */
    private function to_array(mixed $value, renderer_base $output): array {
        if ($value instanceof templatable) {
            $value = $value->export_for_template($output);
        }

        if ($value instanceof stdClass) {
            return (array)$value;
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    /**
     * Normalise dashboard view mode.
     *
     * @param string $viewmode View mode.
     * @return string
     */
    private function normalise_viewmode(string $viewmode): string {
        $viewmode = $this->clean_key($viewmode);

        if (!in_array($viewmode, self::VALID_VIEWMODES, true)) {
            return self::VIEWMODE_AUTO;
        }

        return $viewmode;
    }

    /**
     * Get the default card title language string.
     *
     * @param string $key Card key.
     * @return string
     */
    private function get_default_card_title(string $key): string {
        $stringkey = 'card_' . $this->clean_key($key);

        if (get_string_manager()->string_exists($stringkey, 'block_uckk_dashboard')) {
            return get_string($stringkey, 'block_uckk_dashboard');
        }

        return $key;
    }

    /**
     * Normalise URL values.
     *
     * @param mixed $url Raw URL.
     * @return string
     */
    private function normalise_url(mixed $url): string {
        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if ($url instanceof Stringable) {
            return trim((string)$url);
        }

        if (is_string($url)) {
            return trim($url);
        }

        return '';
    }

    /**
     * Normalise a text value.
     *
     * @param mixed $value Raw text.
     * @return string
     */
    private function normalise_text(mixed $value): string {
        if ($value instanceof Stringable || is_scalar($value)) {
            return trim((string)$value);
        }

        return '';
    }

    /**
     * Clean a machine key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private function clean_key(string $key): string {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\\-]/', '', $key);

        return $key ?? '';
    }

    /**
     * Clean a CSS suffix.
     *
     * @param string $suffix Raw suffix.
     * @return string
     */
    private function clean_css_suffix(string $suffix): string {
        $suffix = $this->clean_key($suffix);
        $suffix = str_replace('_', '-', $suffix);

        return $suffix;
    }

    /**
     * Normalise a visual tone.
     *
     * @param mixed $tone Raw tone.
     * @return string
     */
    private function normalise_tone(mixed $tone): string {
        $tone = $this->clean_key($this->normalise_text($tone));

        if (!in_array($tone, self::TONES, true)) {
            return 'default';
        }

        return $tone;
    }
}