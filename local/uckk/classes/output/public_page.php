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
 * Public page output object for local_uckk.
 *
 * This renderable is the single output contract between:
 * - local_uckk/classes/local/public_pages.php;
 * - local_uckk/templates/public_page.mustache;
 * - local_uckk/styles.css.
 *
 * It prepares a stable Mustache-ready context for public institutional pages.
 * It must not query the database, decide permissions, mutate Moodle data,
 * award recognitions, validate submissions, or present UCKK recognitions as
 * accredited public degrees.
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
 * Public institutional page renderable.
 *
 * Official Mustache context:
 * - uniqid, slug, component, pagetype, ispublic, classes, rootclasses;
 * - layout, layoutclasses, shellclasses, bodyclasses, mainclasses, asideclasses, hasaside;
 * - railclasses, headerclasses, heroclasses, contentclasses, asideinnerclasses;
 * - typographyclasses, sectiongridclasses, cardgridclasses, designversion, fontstrategy;
 * - eyebrow, title, subtitle, summary, boundarynotice;
 * - hasnavigation, navigation, navigationclasses;
 * - hasquicklinks, quicklinks;
 * - hassections, sections;
 * - hascards, cards, cardsheading;
 * - hasnotices, notices;
 * - hasmetadata, metadata, metadataheading;
 * - hascta, cta;
 * - has_course_explorer, course_explorer_id, course_explorer,
 *   course_explorer_initial_state, course_explorer_initial_state_json;
 * - has_mediatheque_explorer, mediatheque_explorer_id, mediatheque_initial_state,
 *   mediatheque_initial_state_json;
 * - has_mediatheque_item, mediatheque_item, mediatheque_item_payload,
 *   has_mediatheque_item_error, mediatheque_item_error, mediatheque_item_back_url,
 *   mediatheque_item_requested_uuid, mediatheque_item_requested_type.
 *
 * @package local_uckk
 */
final class public_page implements renderable, templatable {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Mustache template. */
    private const TEMPLATE = 'local_uckk/public_page';

    /** Standard public layout. */
    private const LAYOUT_STANDARD = 'standard';

    /** Wider public layout for institutional pages with broad navigation. */
    private const LAYOUT_WIDE = 'wide';

    /** Full-width public layout for visual landing pages. */
    private const LAYOUT_FULL = 'full';

    /** Single-line public navigation layout. */
    private const NAV_SINGLELINE = 'singleline';

    /** Wrapping public navigation layout. */
    private const NAV_WRAP = 'wrap';

    /** Public page slugs. */
    public const KEY_HOME = 'home';
    public const KEY_ABOUT = 'about';
    public const KEY_PROGRAMS = 'programs';
    public const KEY_COURSES = 'courses';
    public const KEY_CHALLENGES = 'challenges';
    public const KEY_ASSEMBLIES = 'assemblies';
    public const KEY_INTEGRITY = 'integrity';
    public const KEY_ARCHIVES = 'archives';
    public const KEY_MEDIATHEQUE = 'mediatheque';
    public const KEY_NEWS = 'news';
    public const KEY_CONTACT = 'contact';

    /** Notice types. */
    public const NOTICE_INSTITUTIONAL = 'institutional';
    public const NOTICE_INTEGRITY = 'integrity';
    public const NOTICE_WARNING = 'warning';
    public const NOTICE_LIGHT = 'light';

    /** @var string Active page slug. */
    private string $slug;

    /** @var array<string, mixed> Page definition. */
    private array $definition;

    /**
     * Constructor.
     *
     * @param string $slug Active public page slug.
     * @param array<string, mixed> $definition Optional page definition override.
     */
    public function __construct(string $slug, array $definition = []) {
        $this->slug = self::clean_slug($slug);
        $this->definition = self::normalise_legacy_definition(
            self::merge_definition(self::page_defaults($this->slug), $definition)
        );
    }

    /**
     * Return Mustache template name.
     *
     * @param renderer_base $renderer Renderer.
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return self::TEMPLATE;
    }

    /**
     * Export page data for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $layout = self::layout_value($this->definition);
        $navigationlayout = self::navigation_layout_value($this->definition);

        $data->uniqid = $this->unique_id();
        $data->slug = $this->slug;
        $data->component = self::COMPONENT;
        $data->pagetype = 'public';
        $data->ispublic = true;
        $data->layout = $layout;
        $data->navigationlayout = $navigationlayout;

        $data->eyebrow = self::value($this->definition, 'eyebrow');
        $data->title = self::value($this->definition, 'title');
        $data->subtitle = self::value($this->definition, 'subtitle');
        $data->summary = self::value($this->definition, 'summary');
        $data->boundarynotice = self::value($this->definition, 'boundarynotice');

        $data->haseyebrow = $data->eyebrow !== '';
        $data->hassubtitle = $data->subtitle !== '';
        $data->hassummary = $data->summary !== '';
        $data->hasboundarynotice = $data->boundarynotice !== '';

        $data->navigation = self::export_navigation(
            self::array_value($this->definition, 'navigation'),
            $this->slug
        );
        $data->hasnavigation = !empty($data->navigation);
        $data->navigationclasses = self::navigation_classes($navigationlayout);

        $data->quicklinks = self::export_quicklinks(
            self::array_value($this->definition, 'quicklinks')
        );
        $data->hasquicklinks = !empty($data->quicklinks);

        $data->sections = self::export_sections(
            self::array_value($this->definition, 'sections')
        );
        $data->hassections = !empty($data->sections);

        $data->cards = self::export_cards(
            self::array_value($this->definition, 'cards')
        );
        $data->hascards = !empty($data->cards);
        $data->cardsheading = self::value($this->definition, 'cardsheading');

        if ($data->cardsheading === '') {
            $data->cardsheading = self::default_cards_heading($this->slug);
        }

        $data->notices = self::export_notices(
            self::array_value($this->definition, 'notices')
        );
        $data->hasnotices = !empty($data->notices);

        $data->metadata = self::export_metadata(
            self::array_value($this->definition, 'metadata')
        );
        $data->hasmetadata = !empty($data->metadata);
        $data->metadataheading = self::value($this->definition, 'metadataheading');

        if ($data->metadataheading === '') {
            $data->metadataheading = self::default_metadata_heading($this->slug);
        }

        $data->cta = self::export_cta(
            self::array_value($this->definition, 'cta')
        );
        $data->hascta = !empty((array)$data->cta);

        $data->course_explorer_id = self::dom_id_value(
            $this->definition,
            'course_explorer_id',
            'local-uckk-course-explorer-' . substr(md5($data->uniqid), 0, 8)
        );
        $data->course_explorer = self::export_course_explorer(
            self::array_value($this->definition, 'course_explorer'),
            $data->course_explorer_id
        );
        $data->has_course_explorer = self::bool_value(
            $this->definition,
            'has_course_explorer',
            !empty((array)$data->course_explorer)
        );
        $data->course_explorer_initial_state = self::course_explorer_initial_state(
            self::array_value($this->definition, 'course_explorer_initial_state'),
            $data->course_explorer
        );
        $data->course_explorer_initial_state_json = json_encode(
            $data->course_explorer_initial_state,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';

        $data->has_mediatheque_explorer = self::bool_value($this->definition, 'has_mediatheque_explorer');
        $data->mediatheque_explorer_id = self::dom_id_value(
            $this->definition,
            'mediatheque_explorer_id',
            'local-uckk-mediatheque-explorer-' . substr(md5($data->uniqid), 0, 8)
        );
        $data->mediatheque_initial_state = self::array_value($this->definition, 'mediatheque_initial_state');
        $data->mediatheque_initial_state_json = json_encode(
            $data->mediatheque_initial_state,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';

        $data->mediatheque_item = self::array_value($this->definition, 'mediatheque_item');
        $data->mediatheque_item_payload = self::array_value($this->definition, 'mediatheque_item_payload');
        $data->has_mediatheque_item = self::bool_value(
            $this->definition,
            'has_mediatheque_item',
            !empty($data->mediatheque_item)
        );
        $data->mediatheque_item_error = self::value($this->definition, 'mediatheque_item_error');
        $data->has_mediatheque_item_error = self::bool_value(
            $this->definition,
            'has_mediatheque_item_error',
            $data->mediatheque_item_error !== ''
        );
        $data->mediatheque_item_back_url = self::url_value($this->definition, 'mediatheque_item_back_url');
        $data->mediatheque_item_requested_uuid = self::value($this->definition, 'mediatheque_item_requested_uuid');
        $data->mediatheque_item_requested_type = self::value($this->definition, 'mediatheque_item_requested_type');

        $data->hasaside = $data->hasnotices || $data->hasmetadata || $data->hascta;
        $data->rootclasses = self::page_classes($this->slug, $layout, $data->hasaside);
        $data->classes = $data->rootclasses;
        $data->layoutclasses = self::layout_classes($layout, $data->hasaside);
        $data->shellclasses = self::shell_classes($layout, $data->hasaside);
        $data->bodyclasses = self::body_classes($layout, $data->hasaside);
        $data->mainclasses = self::main_classes($layout, $data->hasaside);
        $data->asideclasses = self::aside_classes($layout);

        /*
         * Layout v2 hooks.
         *
         * These are intentionally additive: older templates can ignore them,
         * while the refreshed public_page.mustache and styles.css can use the
         * same context object for the unified rail, hero, content grid,
         * sticky aside and typography system.
         */
        $data->designversion = '2026-layout-v2';
        $data->fontstrategy = 'libre-baskerville-primary-eb-garamond-accent';
        $data->typographyclasses = self::typography_classes($this->definition);
        $data->railclasses = self::rail_classes($layout);
        $data->headerclasses = self::header_classes($layout);
        $data->heroclasses = self::hero_classes($layout, $data->hasaside);
        $data->contentclasses = self::content_classes($layout, $data->hasaside);
        $data->sectiongridclasses = self::section_grid_classes($layout);
        $data->cardgridclasses = self::card_grid_classes($layout);
        $data->asideinnerclasses = self::aside_inner_classes($layout);

        return $data;
    }

    /**
     * Build a safe unique DOM id.
     *
     * @return string
     */
    private function unique_id(): string {
        return 'local-uckk-public-' . $this->slug . '-' . substr(md5(uniqid('', true)), 0, 8);
    }

    /**
     * Page defaults used when the central registry does not provide a full
     * definition. The authoritative registry remains public_pages.php.
     *
     * @param string $slug Page slug.
     * @return array<string, mixed>
     */
    private static function page_defaults(string $slug): array {
        $titles = [
            self::KEY_HOME => ['Accueil', 'Établissement virtuel de puissance opératoire'],
            self::KEY_ABOUT => ['À propos', 'Clarifier UCKK'],
            self::KEY_PROGRAMS => ['Voies UCKK', 'Former par les Voies'],
            self::KEY_COURSES => ['Cours', 'Explorer les cours'],
            self::KEY_CHALLENGES => ['Défis', 'Mettre la lucidité à l’épreuve'],
            self::KEY_ASSEMBLIES => ['Assemblées', 'Délibérer, vérifier, orienter'],
            self::KEY_INTEGRITY => ['Intégrité', 'Protéger la preuve et la dignité'],
            self::KEY_ARCHIVES => ['Registraire UCKK', 'Tenir le registre public'],
            self::KEY_MEDIATHEQUE => ['Médiathèque UCKK', 'Médiathèque publique'],
            self::KEY_NEWS => ['Actualités', 'Nouvelles et appels'],
            self::KEY_CONTACT => ['Contact', 'Entrer en relation'],
        ];

        [$title, $eyebrow] = $titles[$slug] ?? $titles[self::KEY_HOME];

        return [
            'slug' => $slug,
            'title' => $title,
            'eyebrow' => $eyebrow,
            'subtitle' => '',
            'summary' => '',
            'boundarynotice' => '',
            'layout' => self::LAYOUT_WIDE,
            'navigationlayout' => self::NAV_SINGLELINE,
            'typography' => 'institutional',
            'navigation' => self::default_navigation(),
            'quicklinks' => [],
            'sections' => [],
            'cards' => [],
            'cardsheading' => self::default_cards_heading($slug),
            'notices' => [],
            'metadata' => [],
            'metadataheading' => self::default_metadata_heading($slug),
            'cta' => [],
            'has_course_explorer' => false,
            'course_explorer_id' => '',
            'course_explorer' => [],
            'course_explorer_initial_state' => [],
            'has_mediatheque_explorer' => false,
            'mediatheque_explorer_id' => '',
            'mediatheque_initial_state' => [],
            'has_mediatheque_item' => false,
            'mediatheque_item' => [],
            'mediatheque_item_payload' => [],
            'has_mediatheque_item_error' => false,
            'mediatheque_item_error' => '',
            'mediatheque_item_back_url' => '',
            'mediatheque_item_requested_uuid' => '',
            'mediatheque_item_requested_type' => '',
        ];
    }

    /**
     * Default heading for the generic page-level card section.
     *
     * @param string $slug Page slug.
     * @return string
     */
    private static function default_cards_heading(string $slug): string {
        $headings = [
            self::KEY_HOME => 'Portes d’entrée',
            self::KEY_ABOUT => 'Repères institutionnels',
            self::KEY_PROGRAMS => 'Repères publics',
            self::KEY_COURSES => 'Accès aux cours',
            self::KEY_CHALLENGES => 'Repères pour les défis',
            self::KEY_ASSEMBLIES => 'Repères d’assemblée',
            self::KEY_INTEGRITY => 'Repères d’intégrité',
            self::KEY_ARCHIVES => 'Repères du registraire',
            self::KEY_MEDIATHEQUE => 'Explorer la Médiathèque',
            self::KEY_NEWS => 'Repères d’actualité',
            self::KEY_CONTACT => 'Repères de contact',
        ];

        return $headings[$slug] ?? 'Repères publics';
    }

    /**
     * Default heading for page-level metadata.
     *
     * This avoids hard-coded generic labels in Mustache while keeping a safe
     * fallback for existing page definitions. Page definitions may override the
     * value with the `metadataheading` key.
     *
     * @param string $slug Page slug.
     * @return string
     */
    private static function default_metadata_heading(string $slug): string {
        $headings = [
            self::KEY_HOME => 'Repères publics',
            self::KEY_ABOUT => 'Repères institutionnels',
            self::KEY_PROGRAMS => 'État du registre',
            self::KEY_COURSES => 'Repères des cours',
            self::KEY_CHALLENGES => 'Repères des défis',
            self::KEY_ASSEMBLIES => 'Repères d’assemblée',
            self::KEY_INTEGRITY => 'Repères d’intégrité',
            self::KEY_ARCHIVES => 'Repères du registraire',
            self::KEY_MEDIATHEQUE => 'Repères de la médiathèque',
            self::KEY_NEWS => 'Repères d’actualité',
            self::KEY_CONTACT => 'Repères de contact',
        ];

        return $headings[$slug] ?? 'Repères publics';
    }

    /**
     * Default public navigation.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function default_navigation(): array {
        return [
            ['key' => self::KEY_HOME, 'label' => 'Accueil', 'url' => '/local/uckk/index.php'],
            ['key' => self::KEY_ABOUT, 'label' => 'À propos', 'url' => '/local/uckk/about.php'],
            ['key' => self::KEY_PROGRAMS, 'label' => 'Voies', 'url' => '/local/uckk/programs.php'],
            ['key' => self::KEY_COURSES, 'label' => 'Cours', 'url' => '/local/uckk/courses.php'],
            ['key' => self::KEY_CHALLENGES, 'label' => 'Défis', 'url' => '/local/uckk/challenges.php'],
            ['key' => self::KEY_ASSEMBLIES, 'label' => 'Assemblées', 'url' => '/local/uckk/assemblies.php'],
            ['key' => self::KEY_INTEGRITY, 'label' => 'Intégrité', 'url' => '/local/uckk/integrity.php'],
            ['key' => self::KEY_MEDIATHEQUE, 'label' => 'Médiathèque', 'url' => '/local/uckk/mediatheque.php'],
            ['key' => self::KEY_NEWS, 'label' => 'Actualités', 'url' => '/local/uckk/news.php'],
            ['key' => self::KEY_CONTACT, 'label' => 'Contact', 'url' => '/local/uckk/contact.php'],
        ];
    }

    /**
     * Normalise older context keys into the official context contract.
     *
     * @param array<string, mixed> $definition Page definition.
     * @return array<string, mixed>
     */
    private static function normalise_legacy_definition(array $definition): array {
        $aliases = [
            'key' => 'slug',
            'heading' => 'title',
            'kicker' => 'eyebrow',
            'intro' => 'summary',
            'footer' => 'boundarynotice',
            'font' => 'typography',
            'nav' => 'navigation',
            'actions' => 'quicklinks',
            'meta' => 'metadata',
            'courseexplorer' => 'course_explorer',
            'courseexplorerstate' => 'course_explorer_initial_state',
        ];

        foreach ($aliases as $old => $new) {
            if (!array_key_exists($new, $definition) && array_key_exists($old, $definition)) {
                $definition[$new] = $definition[$old];
            }
        }

        return $definition;
    }

    /**
     * Export public course explorer context.
     *
     * @param array<string, mixed> $explorer Explorer data.
     * @param string $fallbackid Fallback DOM id.
     * @return stdClass
     */
    private static function export_course_explorer(array $explorer, string $fallbackid): stdClass {
        $obj = new stdClass();

        if (empty($explorer)) {
            return $obj;
        }

        $id = self::clean_string($explorer['id'] ?? '');
        $query = self::clean_string($explorer['query'] ?? $explorer['q'] ?? '');
        $category = self::clean_modifier($explorer['category'] ?? 'all');
        $sort = self::clean_modifier($explorer['sort'] ?? 'pedagogical');
        $service = self::clean_string($explorer['service'] ?? 'local_uckk_search_public_courses');

        $results = self::export_cards(self::array_value($explorer, 'results'));
        $filters = self::export_choice_items(
            self::array_value($explorer, 'filters'),
            $category,
            'category'
        );
        $sortoptions = self::export_choice_items(
            self::array_value($explorer, 'sortoptions'),
            $sort,
            'sort'
        );

        $total = isset($explorer['total']) && is_numeric($explorer['total'])
            ? max(0, (int)$explorer['total'])
            : count($results);

        $obj->id = $id !== '' ? $id : $fallbackid;
        $obj->query = $query;
        $obj->category = $category !== '' ? $category : 'all';
        $obj->sort = $sort !== '' ? $sort : 'pedagogical';
        $obj->service = $service;
        $obj->title = self::clean_string($explorer['title'] ?? 'Explorer les cours');
        $obj->summary = self::clean_string(
            $explorer['summary'] ?? 'Rechercher et filtrer les cours publics de l’UCKK.'
        );
        $obj->searchlabel = self::clean_string($explorer['searchlabel'] ?? 'Recherche');
        $obj->searchplaceholder = self::clean_string(
            $explorer['searchplaceholder'] ?? 'Rechercher un cours, un code ou une notion'
        );
        $obj->filterlabel = self::clean_string($explorer['filterlabel'] ?? 'Filtrer');
        $obj->sortlabel = self::clean_string($explorer['sortlabel'] ?? 'Trier');
        $obj->resetlabel = self::clean_string($explorer['resetlabel'] ?? 'Réinitialiser');
        $obj->submitlabel = self::clean_string($explorer['submitlabel'] ?? 'Appliquer');
        $obj->resultscountlabel = self::clean_string(
            $explorer['resultscountlabel'] ?? ($total === 1 ? '1 cours affiché' : $total . ' cours affichés')
        );
        $obj->emptytitle = self::clean_string($explorer['emptytitle'] ?? 'Aucun cours trouvé');
        $obj->emptybody = self::clean_string(
            $explorer['emptybody'] ?? 'Modifier la recherche ou les filtres pour afficher d’autres cours.'
        );
        $obj->filters = $filters;
        $obj->hasfilters = !empty($filters);
        $obj->sortoptions = $sortoptions;
        $obj->hassortoptions = !empty($sortoptions);
        $obj->results = $results;
        $obj->hasresults = !empty($results);
        $obj->total = $total;
        $obj->hasquery = $query !== '';
        $obj->classes = self::join_classes([
            'local-uckk-course-explorer',
            $obj->hasresults ? 'local-uckk-course-explorer--has-results' : 'local-uckk-course-explorer--empty',
        ]);

        return $obj;
    }

    /**
     * Build AMD-safe initial state for the course explorer.
     *
     * @param array<string, mixed> $state Explicit state.
     * @param stdClass $explorer Exported explorer context.
     * @return array<string, mixed>
     */
    private static function course_explorer_initial_state(array $state, stdClass $explorer): array {
        if (!empty($state)) {
            return $state;
        }

        if (empty((array)$explorer)) {
            return [];
        }

        return [
            'rootId' => self::clean_string($explorer->id ?? ''),
            'service' => self::clean_string($explorer->service ?? 'local_uckk_search_public_courses'),
            'query' => self::clean_string($explorer->query ?? ''),
            'category' => self::clean_string($explorer->category ?? 'all'),
            'sort' => self::clean_string($explorer->sort ?? 'pedagogical'),
            'total' => isset($explorer->total) ? (int)$explorer->total : 0,
        ];
    }

    /**
     * Export choice/filter items.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Choice items.
     * @param string $active Active value.
     * @param string $param Query parameter name.
     * @return array<int, stdClass>
     */
    private static function export_choice_items(array $items, string $active, string $param): array {
        $out = [];

        foreach ($items as $item) {
            $item = (array)$item;
            $value = self::clean_modifier($item['value'] ?? $item['key'] ?? '');
            $label = self::clean_string($item['label'] ?? $item['title'] ?? '');

            if ($value === '' || $label === '') {
                continue;
            }

            $url = self::url_value($item, 'url');
            $isactive = $value === $active || !empty($item['active']);

            $obj = new stdClass();
            $obj->value = $value;
            $obj->key = $value;
            $obj->label = $label;
            $obj->url = $url;
            $obj->param = $param;
            $obj->active = $isactive;
            $obj->checked = $isactive;
            $obj->ariacurrent = $isactive ? 'true' : '';
            $obj->hasariacurrent = $isactive;
            $obj->classes = self::join_classes([
                'local-uckk-course-explorer__choice',
                $isactive ? 'is-active' : null,
            ]);

            $out[] = $obj;
        }

        return $out;
    }

    /**
     * Export navigation items.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Navigation items.
     * @param string $active Active slug.
     * @return array<int, stdClass>
     */
    private static function export_navigation(array $items, string $active): array {
        $out = [];

        foreach ($items as $item) {
            $item = (array)$item;
            $key = self::clean_string($item['key'] ?? '');
            $label = self::clean_string($item['label'] ?? '');
            $url = self::url_value($item, 'url');

            if ($key === '' || $label === '' || $url === '') {
                continue;
            }

            $isactive = $key === $active || !empty($item['active']);
            $modifier = self::clean_modifier($item['type'] ?? $item['modifier'] ?? '');

            $classes = ['local-uckk-public-nav__link'];

            if ($isactive) {
                $classes[] = 'is-active';
            }

            if ($modifier !== '') {
                $classes[] = 'local-uckk-public-nav__link--' . $modifier;
            }

            $nav = new stdClass();
            $nav->key = $key;
            $nav->label = $label;
            $nav->url = $url;
            $nav->active = $isactive;
            $nav->classes = implode(' ', $classes);
            $nav->itemclasses = 'local-uckk-public-nav__item'
                . ($isactive ? ' local-uckk-public-nav__item--active' : '');
            $nav->ariacurrent = $isactive ? 'page' : '';
            $nav->hasariacurrent = $isactive;

            $out[] = $nav;
        }

        return $out;
    }

    /**
     * Export quicklinks.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Quicklinks.
     * @return array<int, stdClass>
     */
    private static function export_quicklinks(array $items): array {
        $out = [];

        foreach ($items as $item) {
            $item = (array)$item;
            $label = self::clean_string($item['label'] ?? $item['title'] ?? '');
            $description = self::clean_string($item['description'] ?? $item['body'] ?? '');
            $url = self::url_value($item, 'url');

            if ($label === '') {
                continue;
            }

            $link = new stdClass();
            $link->label = $label;
            $link->description = $description;
            $link->url = $url;
            $link->hasdescription = $description !== '';
            $link->hasurl = $url !== '';
            $link->classes = 'local-uckk-public-quicklink';

            $out[] = $link;
        }

        return $out;
    }

    /**
     * Export sections.
     *
     * @param array<int, array<string, mixed>|stdClass> $sections Sections.
     * @return array<int, stdClass>
     */
    private static function export_sections(array $sections): array {
        $out = [];

        foreach ($sections as $index => $section) {
            $section = (array)$section;

            $title = self::clean_string($section['title'] ?? '');
            $body = self::clean_string($section['body'] ?? $section['summary'] ?? '');
            $eyebrow = self::clean_string($section['eyebrow'] ?? '');

            $items = self::export_text_items(self::array_value($section, 'items'));
            $cards = self::export_cards(self::array_value($section, 'cards'));

            // Some public_page.mustache versions render section content only through
            // {{#items}}. Keep real section cards available as cards, but also expose
            // card-shaped items so the same data appears in older/stricter templates.
            foreach ($cards as $card) {
                $item = clone $card;
                $item->text = $card->title;
                $item->iscard = true;
                $items[] = $item;
            }

            if ($title === '' && $body === '' && empty($items) && empty($cards)) {
                continue;
            }

            $obj = new stdClass();
            $obj->eyebrow = $eyebrow;
            $obj->title = $title;
            $obj->body = $body;
            $obj->items = $items;
            $obj->cards = $cards;
            $obj->haseyebrow = $eyebrow !== '';
            $obj->hastitle = $title !== '';
            $obj->hasbody = $body !== '';
            $obj->hasitems = !empty($items);
            $obj->hascards = !empty($cards);
            $obj->position = $index + 1;
            $obj->isfirst = $index === 0;
            $obj->classes = self::join_classes([
                self::class_value($section, 'local-uckk-public-section'),
                $index === 0 ? 'local-uckk-public-section--first' : null,
            ]);

            $out[] = $obj;
        }

        return $out;
    }

    /**
     * Export cards.
     *
     * @param array<int, array<string, mixed>|stdClass> $cards Cards.
     * @return array<int, stdClass>
     */
    private static function export_cards(array $cards): array {
        $out = [];

        foreach ($cards as $index => $card) {
            $card = (array)$card;

            $title = self::clean_string($card['title'] ?? '');
            $body = self::clean_string($card['body'] ?? $card['summary'] ?? '');
            $eyebrow = self::clean_string($card['eyebrow'] ?? '');
            $url = self::url_value($card, 'url');
            $actionlabel = self::clean_string($card['actionlabel'] ?? $card['action'] ?? '');
            $type = self::clean_modifier($card['type'] ?? '');

            if ($title === '' && $body === '') {
                continue;
            }

            $metadata = self::export_metadata(self::array_value($card, 'metadata'));

            $shortname = self::clean_string($card['shortname'] ?? '');
            $code = self::clean_string($card['code'] ?? '');
            $category = self::clean_string($card['category'] ?? '');
            $categorylabel = self::clean_string($card['categorylabel'] ?? '');

            if ($shortname === '') {
                $shortname = self::metadata_value($metadata, ['Numéro de cours', 'Code', 'Course code']);
            }

            if ($code === '') {
                $code = $shortname;
            }

            if ($categorylabel === '') {
                $categorylabel = self::metadata_value($metadata, ['Voie', 'Catégorie', 'Category']);
            }

            if ($category === '') {
                $category = $categorylabel !== '' ? $categorylabel : $eyebrow;
            }

            $obj = new stdClass();

            $obj->id = isset($card['id']) && is_numeric($card['id']) ? (int)$card['id'] : 0;
            $obj->eyebrow = $eyebrow;
            $obj->title = $title;
            $obj->body = $body;
            $obj->summary = $body;
            $obj->description = self::clean_string($card['description'] ?? $body);
            $obj->url = $url;
            $obj->actionlabel = $actionlabel;
            $obj->type = $type;

            /*
             * Course cards use these explicit fields in course_explorer.mustache.
             * Keep them on the exported card instead of forcing the template to
             * recover them from generic metadata. Otherwise the initial server-side
             * render can display the category metadata as an extra course number,
             * while the AJAX render appears correct because JavaScript preserves
             * the course-specific fields.
             */
            $obj->shortname = $shortname;
            $obj->code = $code;
            $obj->category = $category;
            $obj->categorylabel = $categorylabel;
            $obj->categorykey = self::clean_modifier($card['categorykey'] ?? '');
            $obj->categoryname = self::clean_string($card['categoryname'] ?? '');
            $obj->categoryidnumber = self::clean_string($card['categoryidnumber'] ?? '');
            $obj->hascategory = $category !== '' || $categorylabel !== '';
            $obj->hasshortname = $shortname !== '';
            $obj->hascode = $code !== '';

            $obj->haseyebrow = $eyebrow !== '';
            $obj->hasbody = $body !== '';
            $obj->hasurl = $url !== '';
            $obj->hasaction = $url !== '' && $actionlabel !== '';

            /*
             * Important:
             * Always define card-level metadata flags.
             *
             * Without these properties, Mustache can resolve {{hasmetadata}}
             * and {{metadata}} from the parent page context inside nested
             * course cards. That is what causes the page-level
             * "Cours affichés 114 / 114" metadata to appear on every course.
             */
            $obj->metadata = $metadata;
            $obj->hasmetadata = !empty($metadata);

            $obj->position = $index + 1;
            $obj->isfirst = $index === 0;

            $obj->classes = self::join_classes([
                'local-uckk-public-card',
                $type !== '' ? 'local-uckk-public-card--' . $type : null,
                $url !== '' ? 'local-uckk-public-card--linked' : 'local-uckk-public-card--static',
                $index === 0 ? 'local-uckk-public-card--first' : null,
            ]);

            $out[] = $obj;
        }

        return $out;
    }

    /**
     * Return a metadata value by label.
     *
     * @param array<int, stdClass> $metadata Exported metadata items.
     * @param array<int, string> $labels Accepted labels.
     * @return string
     */
    private static function metadata_value(array $metadata, array $labels): string {
        $wanted = [];

        foreach ($labels as $label) {
            $wanted[] = \core_text::strtolower(self::clean_string($label));
        }

        foreach ($metadata as $item) {
            $label = \core_text::strtolower(self::clean_string($item->label ?? ''));

            if ($label !== '' && in_array($label, $wanted, true)) {
                return self::clean_string($item->value ?? '');
            }
        }

        return '';
    }

    /**
     * Export notices.
     *
     * @param array<int, array<string, mixed>|stdClass> $notices Notices.
     * @return array<int, stdClass>
     */
    private static function export_notices(array $notices): array {
        $out = [];

        foreach ($notices as $notice) {
            $notice = (array)$notice;
            $title = self::clean_string($notice['title'] ?? '');
            $body = self::clean_string($notice['body'] ?? $notice['text'] ?? '');
            $type = self::clean_notice_type($notice['type'] ?? self::NOTICE_INSTITUTIONAL);

            if ($title === '' && $body === '') {
                continue;
            }

            $obj = new stdClass();
            $obj->title = $title;
            $obj->body = $body;
            $obj->type = $type;
            $obj->hastitle = $title !== '';
            $obj->hasbody = $body !== '';
            $obj->classes = 'local-uckk-public-notice local-uckk-public-notice--' . $type;

            $out[] = $obj;
        }

        return $out;
    }

    /**
     * Export metadata.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Metadata items.
     * @return array<int, stdClass>
     */
    private static function export_metadata(array $items): array {
        $out = [];

        foreach ($items as $item) {
            $item = (array)$item;
            $label = self::clean_string($item['label'] ?? $item['name'] ?? '');
            $value = self::clean_string($item['value'] ?? $item['body'] ?? '');

            if ($label === '' && $value === '') {
                continue;
            }

            $obj = new stdClass();
            $obj->label = $label;
            $obj->value = $value;
            $obj->haslabel = $label !== '';
            $obj->hasvalue = $value !== '';

            $out[] = $obj;
        }

        return $out;
    }

    /**
     * Export CTA.
     *
     * @param array<string, mixed> $cta CTA data.
     * @return stdClass
     */
    private static function export_cta(array $cta): stdClass {
        $title = self::clean_string($cta['title'] ?? '');
        $body = self::clean_string($cta['body'] ?? $cta['summary'] ?? '');
        $url = self::url_value($cta, 'url');
        $label = self::clean_string($cta['label'] ?? $cta['actionlabel'] ?? $cta['action'] ?? '');

        $obj = new stdClass();

        if ($title === '' && $body === '' && $url === '' && $label === '') {
            return $obj;
        }

        $actionlabel = $label !== '' ? $label : 'Continuer';

        $obj->title = $title;
        $obj->body = $body;
        $obj->url = $url;
        $obj->label = $actionlabel;
        $obj->actionlabel = $actionlabel;
        $obj->hastitle = $title !== '';
        $obj->hasbody = $body !== '';
        $obj->hasurl = $url !== '';
        $obj->hasaction = $url !== '';
        $obj->classes = 'btn btn-primary local-uckk-public-cta__link';

        return $obj;
    }

    /**
     * Export simple text/card-like items.
     *
     * @param array<int, mixed> $items Items.
     * @return array<int, stdClass>
     */
    private static function export_text_items(array $items): array {
        $out = [];

        foreach ($items as $index => $item) {
            $data = is_array($item) || $item instanceof stdClass ? (array)$item : ['text' => $item];

            $eyebrow = self::clean_string($data['eyebrow'] ?? '');
            $title = self::clean_string($data['title'] ?? $data['label'] ?? $data['text'] ?? '');
            $body = self::clean_string($data['body'] ?? $data['description'] ?? $data['summary'] ?? '');
            $text = self::clean_string($data['text'] ?? $title);
            $url = self::url_value($data, 'url');
            $actionlabel = self::clean_string($data['actionlabel'] ?? $data['action'] ?? '');
            $type = self::clean_modifier($data['type'] ?? '');

            if ($title === '' && $body === '' && $text === '') {
                continue;
            }

            $obj = new stdClass();
            $obj->text = $text !== '' ? $text : $title;
            $obj->eyebrow = $eyebrow;
            $obj->title = $title;
            $obj->body = $body;
            $obj->url = $url;
            $obj->actionlabel = $actionlabel;
            $obj->haseyebrow = $eyebrow !== '';
            $obj->hastitle = $title !== '';
            $obj->hasbody = $body !== '';
            $obj->hasurl = $url !== '';
            $obj->hasaction = $url !== '' && $actionlabel !== '';
            $obj->iscard = $title !== '' || $body !== '' || $url !== '';
            $obj->position = $index + 1;
            $obj->isfirst = $index === 0;
            $obj->classes = self::join_classes([
                'local-uckk-public-card',
                'local-uckk-public-card--section-item',
                $type !== '' ? 'local-uckk-public-card--' . $type : null,
                $url !== '' ? 'local-uckk-public-card--linked' : 'local-uckk-public-card--static',
                $index === 0 ? 'local-uckk-public-card--first' : null,
            ]);

            $out[] = $obj;
        }

        return $out;
    }

    /**
     * Build root page classes.
     *
     * @param string $slug Page slug.
     * @param string $layout Layout key.
     * @param bool $hasaside Whether the page has aside content.
     * @return string
     */
    private static function page_classes(string $slug, string $layout, bool $hasaside): string {
        return self::join_classes([
            'local-uckk',
            'local-uckk-public-page',
            'local-uckk-public-page--' . $slug,
            'local-uckk-public-page--layout-' . $layout,
            $hasaside ? 'local-uckk-public-page--with-aside' : 'local-uckk-public-page--no-aside',
        ]);
    }

    /**
     * Build layout wrapper classes.
     *
     * @param string $layout Layout key.
     * @param bool $hasaside Whether the page has aside content.
     * @return string
     */
    private static function layout_classes(string $layout, bool $hasaside): string {
        return self::join_classes([
            'local-uckk-public-layout',
            'local-uckk-public-layout--' . $layout,
            $hasaside ? 'local-uckk-public-layout--with-aside' : 'local-uckk-public-layout--no-aside',
        ]);
    }

    /**
     * Build public shell classes.
     *
     * @param string $layout Layout key.
     * @param bool $hasaside Whether the page has aside content.
     * @return string
     */
    private static function shell_classes(string $layout, bool $hasaside): string {
        return self::join_classes([
            'local-uckk-public-shell',
            'local-uckk-public-shell--' . $layout,
            $hasaside ? 'local-uckk-public-shell--with-aside' : 'local-uckk-public-shell--no-aside',
        ]);
    }

    /**
     * Build public navigation root classes.
     *
     * @param string $navigationlayout Navigation layout key.
     * @return string
     */
    private static function navigation_classes(string $navigationlayout): string {
        return self::join_classes([
            'local-uckk-public-nav',
            'local-uckk-public-nav--' . $navigationlayout,
        ]);
    }

    /**
     * Build body grid classes.
     *
     * @param string $layout Layout key.
     * @param bool $hasaside Whether the page has aside content.
     * @return string
     */
    private static function body_classes(string $layout, bool $hasaside): string {
        return self::join_classes([
            'local-uckk-public-body',
            'local-uckk-public-body--' . $layout,
            $hasaside ? 'local-uckk-public-body--with-aside' : 'local-uckk-public-body--no-aside',
        ]);
    }

    /**
     * Build main content classes.
     *
     * @param string $layout Layout key.
     * @param bool $hasaside Whether the page has aside content.
     * @return string
     */
    private static function main_classes(string $layout, bool $hasaside): string {
        return self::join_classes([
            'local-uckk-public-main',
            'local-uckk-public-main--' . $layout,
            $hasaside ? 'local-uckk-public-main--with-aside' : 'local-uckk-public-main--no-aside',
        ]);
    }

    /**
     * Build aside classes.
     *
     * @param string $layout Layout key.
     * @return string
     */
    private static function aside_classes(string $layout): string {
        return self::join_classes([
            'local-uckk-public-aside',
            'local-uckk-public-aside--' . $layout,
        ]);
    }

    /**
     * Build central rail classes.
     *
     * @param string $layout Layout key.
     * @return string
     */
    private static function rail_classes(string $layout): string {
        return self::join_classes([
            'local-uckk-public-rail',
            'local-uckk-public-rail--' . $layout,
        ]);
    }

    /**
     * Build public header classes.
     *
     * @param string $layout Layout key.
     * @return string
     */
    private static function header_classes(string $layout): string {
        return self::join_classes([
            'local-uckk-public-header',
            'local-uckk-public-header--' . $layout,
        ]);
    }

    /**
     * Build hero classes.
     *
     * @param string $layout Layout key.
     * @param bool $hasaside Whether the page has aside content.
     * @return string
     */
    private static function hero_classes(string $layout, bool $hasaside): string {
        return self::join_classes([
            'local-uckk-public-hero',
            'local-uckk-public-hero--' . $layout,
            $hasaside ? 'local-uckk-public-hero--with-aside' : 'local-uckk-public-hero--no-aside',
        ]);
    }

    /**
     * Build content grid classes.
     *
     * @param string $layout Layout key.
     * @param bool $hasaside Whether the page has aside content.
     * @return string
     */
    private static function content_classes(string $layout, bool $hasaside): string {
        return self::join_classes([
            'local-uckk-public-content',
            'local-uckk-public-content--' . $layout,
            $hasaside ? 'local-uckk-public-content--with-aside' : 'local-uckk-public-content--no-aside',
        ]);
    }

    /**
     * Build section grid classes.
     *
     * @param string $layout Layout key.
     * @return string
     */
    private static function section_grid_classes(string $layout): string {
        return self::join_classes([
            'local-uckk-public-section-grid',
            'local-uckk-public-section-grid--' . $layout,
        ]);
    }

    /**
     * Build card grid classes.
     *
     * @param string $layout Layout key.
     * @return string
     */
    private static function card_grid_classes(string $layout): string {
        return self::join_classes([
            'local-uckk-public-card-grid',
            'local-uckk-public-card-grid--' . $layout,
        ]);
    }

    /**
     * Build aside inner classes.
     *
     * @param string $layout Layout key.
     * @return string
     */
    private static function aside_inner_classes(string $layout): string {
        return self::join_classes([
            'local-uckk-public-aside__inner',
            'local-uckk-public-aside__inner--' . $layout,
        ]);
    }

    /**
     * Build typography strategy classes.
     *
     * @param array<string, mixed> $definition Page definition.
     * @return string
     */
    private static function typography_classes(array $definition): string {
        $typography = self::clean_modifier($definition['typography'] ?? 'institutional');

        $allowed = [
            'institutional',
            'editorial',
            'display',
        ];

        if (!in_array($typography, $allowed, true)) {
            $typography = 'institutional';
        }

        return self::join_classes([
            'local-uckk-public-typography',
            'local-uckk-public-typography--' . $typography,
        ]);
    }

    /**
     * Get class value with an optional suffix.
     *
     * @param array<string, mixed> $data Data.
     * @param string $base Base class.
     * @return string
     */
    private static function class_value(array $data, string $base): string {
        $modifier = self::clean_modifier($data['type'] ?? $data['modifier'] ?? '');

        return $base . ($modifier !== '' ? ' ' . $base . '--' . $modifier : '');
    }

    /**
     * Get a string value from an array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param string $default Default value.
     * @return string
     */
    private static function value(array $data, string $key, string $default = ''): string {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        $value = self::clean_string($data[$key]);

        return $value !== '' ? $value : $default;
    }

    /**
     * Get an array value from an array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return array<mixed>
     */
    private static function array_value(array $data, string $key): array {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            return [];
        }

        return $data[$key];
    }

    /**
     * Get URL value from an array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return string
     */
    private static function url_value(array $data, string $key): string {
        if (!array_key_exists($key, $data)) {
            return '';
        }

        $url = $data[$key];

        if ($url instanceof moodle_url) {
            return $url->out(false);
        }

        if (!is_scalar($url)) {
            return '';
        }

        $url = trim((string)$url);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/')) {
            return (new moodle_url($url))->out(false);
        }

        return $url;
    }

    /**
     * Merge page definitions without corrupting list arrays.
     *
     * @param array<string, mixed> $base Base definition.
     * @param array<string, mixed> $overrides Overrides.
     * @return array<string, mixed>
     */
    private static function merge_definition(array $base, array $overrides): array {
        foreach ($overrides as $key => $value) {
            if (
                is_array($value)
                && isset($base[$key])
                && is_array($base[$key])
                && !array_is_list($value)
                && !array_is_list($base[$key])
            ) {
                $base[$key] = self::merge_definition($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * Clean layout key.
     *
     * @param array<string, mixed> $definition Page definition.
     * @return string
     */
    private static function layout_value(array $definition): string {
        $layout = self::clean_modifier($definition['layout'] ?? self::LAYOUT_WIDE);

        $allowed = [
            self::LAYOUT_STANDARD,
            self::LAYOUT_WIDE,
            self::LAYOUT_FULL,
        ];

        return in_array($layout, $allowed, true) ? $layout : self::LAYOUT_WIDE;
    }

    /**
     * Clean navigation layout key.
     *
     * @param array<string, mixed> $definition Page definition.
     * @return string
     */
    private static function navigation_layout_value(array $definition): string {
        $layout = self::clean_modifier($definition['navigationlayout'] ?? self::NAV_SINGLELINE);

        $allowed = [
            self::NAV_SINGLELINE,
            self::NAV_WRAP,
        ];

        return in_array($layout, $allowed, true) ? $layout : self::NAV_SINGLELINE;
    }

    /**
     * Clean page slug.
     *
     * @param string $slug Page slug.
     * @return string
     */
    private static function clean_slug(string $slug): string {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_]+/', '_', $slug) ?? '';

        $allowed = [
            self::KEY_HOME,
            self::KEY_ABOUT,
            self::KEY_PROGRAMS,
            self::KEY_COURSES,
            self::KEY_CHALLENGES,
            self::KEY_ASSEMBLIES,
            self::KEY_INTEGRITY,
            self::KEY_ARCHIVES,
            self::KEY_MEDIATHEQUE,
            self::KEY_NEWS,
            self::KEY_CONTACT,
        ];

        if (!in_array($slug, $allowed, true)) {
            return self::KEY_HOME;
        }

        return $slug;
    }

    /**
     * Clean class modifier.
     *
     * @param mixed $modifier Modifier.
     * @return string
     */
    private static function clean_modifier($modifier): string {
        if (!is_scalar($modifier)) {
            return '';
        }

        $modifier = strtolower(trim((string)$modifier));
        $modifier = preg_replace('/[^a-z0-9_-]+/', '-', $modifier) ?? '';
        $modifier = trim($modifier, '-');

        return $modifier;
    }

    /**
     * Clean notice type.
     *
     * @param mixed $type Notice type.
     * @return string
     */
    private static function clean_notice_type($type): string {
        $type = self::clean_modifier($type);

        $allowed = [
            self::NOTICE_INSTITUTIONAL,
            self::NOTICE_INTEGRITY,
            self::NOTICE_WARNING,
            self::NOTICE_LIGHT,
        ];

        if (!in_array($type, $allowed, true)) {
            return self::NOTICE_INSTITUTIONAL;
        }

        return $type;
    }

    /**
     * Join non-empty CSS classes.
     *
     * @param array<int, string|false|null> $classes Classes.
     * @return string
     */
    private static function join_classes(array $classes): string {
        $out = [];

        foreach ($classes as $class) {
            if (!is_string($class)) {
                continue;
            }

            $class = trim($class);

            if ($class !== '') {
                $out[] = $class;
            }
        }

        return implode(' ', $out);
    }

    /**
     * Get boolean value from an array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param bool $default Default.
     * @return bool
     */
    private static function bool_value(array $data, string $key, bool $default = false): bool {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return !empty($data[$key]);
    }

    /**
     * Get safe DOM id value.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param string $default Default.
     * @return string
     */
    private static function dom_id_value(array $data, string $key, string $default): string {
        if (!array_key_exists($key, $data) || !is_scalar($data[$key])) {
            return $default;
        }

        $value = trim((string)$data[$key]);
        $value = preg_replace('/[^A-Za-z0-9_-]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : $default;
    }

    /**
     * Clean string value.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function clean_string($value): string {
        if ($value instanceof moodle_url) {
            return $value->out(false);
        }

        if (!is_scalar($value)) {
            return '';
        }

        return trim((string)$value);
    }
}