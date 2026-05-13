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
 * Canon panel output class.
 *
 * This class prepares display data for the UCKK canon panel.
 * It must not query the database, mutate canonical records, apply workflow
 * transitions, validate archive records, decide integrity cases, or enforce
 * institutional governance.
 *
 * The class is presentation-oriented:
 * - receive canon items from a page/controller/service;
 * - normalise them for templates;
 * - expose simple values to Mustache;
 * - preserve the UCKK hierarchy and boundary language.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\output;

use local_uckk\local\canon_item;
use moodle_url;
use named_templatable;
use renderable;
use renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Canon panel renderable.
 *
 * Expected template:
 * - local/uckk/templates/canon_panel.mustache
 *
 * Example:
 *
 * ```php
 * $panel = new \local_uckk\output\canon_panel($items, [
 *     'title' => get_string('canon:title', 'local_uckk'),
 *     'intro' => get_string('canon:intro', 'local_uckk'),
 *     'showfilters' => true,
 *     'canmanage' => has_capability('local/uckk:managecanon', $context),
 * ]);
 *
 * echo $OUTPUT->render($panel);
 * ```
 *
 * @package local_uckk
 */
final class canon_panel implements renderable, named_templatable {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Default template name. */
    private const TEMPLATE = 'local_uckk/canon_panel';

    /** @var array<int, canon_item|stdClass|array<string, mixed>> Canon items. */
    private array $items;

    /** @var array<string, mixed> Display options. */
    private array $options;

    /**
     * Constructor.
     *
     * @param array<int, canon_item|stdClass|array<string, mixed>> $items Canon items.
     * @param array<string, mixed> $options Display options.
     */
    public function __construct(array $items = [], array $options = []) {
        $this->items = $items;
        $this->options = $options;
    }

    /**
     * Return the Mustache template used to render this panel.
     *
     * @param renderer_base $renderer Renderer.
     * @return string Template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return self::TEMPLATE;
    }

    /**
     * Export data for Mustache.
     *
     * This method must return only simple types, arrays and stdClass objects.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        $data->title = $this->get_option_string(
            'title',
            self::string('canon:title', 'Canon UCKK')
        );

        $data->subtitle = $this->get_option_string(
            'subtitle',
            self::string('canon:subtitle', 'Bibliothèque canonique de l’Univers-Cité King Klown')
        );

        $data->intro = $this->get_option_string(
            'intro',
            self::string(
                'canon:intro',
                'Le canon UCKK rassemble les définitions, règles, limites, programmes, cours, protocoles et formules qui assurent la cohérence de l’Univers-Cité King Klown.'
            )
        );

        $data->boundarynotice = $this->get_option_string(
            'boundarynotice',
            self::string(
                'canon:boundarynotice',
                'kOA est le mouvement. UCKK est l’école. Le kOA Digital Ecosystem est l’infrastructure. King Klown est la figure narrative. L’Inquisiteur protège. Les Assemblées légitiment. Les Archives conservent la mémoire.'
            )
        );

        $data->formula = $this->get_option_string(
            'formula',
            self::string(
                'canon:formula',
                'Connaître → Choisir → Agir → Se souvenir'
            )
        );

        $data->showintro = $data->intro !== '';
        $data->showboundarynotice = $this->get_option_bool('showboundarynotice', true);
        $data->showformula = $this->get_option_bool('showformula', true);
        $data->showfilters = $this->get_option_bool('showfilters', true);
        $data->showactions = $this->get_option_bool('showactions', true);
        $data->showempty = $this->get_option_bool('showempty', true);
        $data->canmanage = $this->get_option_bool('canmanage', false);
        $data->cancreate = $this->get_option_bool('cancreate', false);
        $data->canexport = $this->get_option_bool('canexport', false);

        $data->contextid = $this->get_option_int('contextid', 0);
        $data->selectedtype = $this->get_option_string('selectedtype', '');
        $data->selectedstatus = $this->get_option_string('selectedstatus', '');
        $data->selectedvisibility = $this->get_option_string('selectedvisibility', '');
        $data->searchquery = $this->get_option_string('searchquery', '');

        $data->items = $this->export_items($output);
        $data->itemcount = count($data->items);
        $data->hasitems = $data->itemcount > 0;

        $data->emptytitle = self::string('canon:emptytitle', 'Aucun élément canonique à afficher');
        $data->emptymessage = self::string(
            'canon:emptymessage',
            'Aucun élément du canon UCKK ne correspond aux filtres sélectionnés.'
        );

        $data->filters = $this->export_filters();
        $data->actions = $this->export_actions();

        $data->hierarchy = $this->export_hierarchy();
        $data->haswarnings = false;
        $data->warnings = [];

        $warnings = $this->get_option_array('warnings', []);
        foreach ($warnings as $warning) {
            $warning = trim((string)$warning);
            if ($warning !== '') {
                $data->warnings[] = [
                    'message' => $warning,
                ];
            }
        }
        $data->haswarnings = !empty($data->warnings);

        $data->attributes = $this->export_attributes();

        return $data;
    }

    /**
     * Export canon items.
     *
     * @param renderer_base $output Renderer.
     * @return array<int, array<string, mixed>>
     */
    private function export_items(renderer_base $output): array {
        $items = [];

        foreach ($this->items as $item) {
            $exported = $this->export_item($item, $output);

            if ($exported !== null) {
                $items[] = $exported;
            }
        }

        return $items;
    }

    /**
     * Export a single canon item.
     *
     * @param canon_item|stdClass|array<string, mixed> $item Canon item.
     * @param renderer_base $output Renderer.
     * @return array<string, mixed>|null
     */
    private function export_item($item, renderer_base $output): ?array {
        if ($item instanceof canon_item) {
            $data = $item->export_for_template();
        } else if ($item instanceof stdClass) {
            $data = $this->normalise_item_data((array)$item);
        } else if (is_array($item)) {
            $data = $this->normalise_item_data($item);
        } else {
            return null;
        }

        $id = (int)($data['id'] ?? 0);
        $shortname = clean_param((string)($data['shortname'] ?? ''), PARAM_ALPHANUMEXT);
        $itemtype = clean_param((string)($data['itemtype'] ?? 'principle'), PARAM_ALPHANUMEXT);
        $status = clean_param((string)($data['status'] ?? 'draft'), PARAM_ALPHANUMEXT);
        $visibility = clean_param((string)($data['visibility'] ?? 'institution'), PARAM_ALPHANUMEXT);

        $data['id'] = $id;
        $data['shortname'] = $shortname;
        $data['title'] = format_string((string)($data['title'] ?? self::string('canon:item', 'Élément canonique')));
        $data['itemtype'] = $itemtype;
        $data['itemtypelabel'] = $data['itemtypelabel'] ?? $this->get_itemtype_label($itemtype);
        $data['status'] = $status;
        $data['statuslabel'] = $data['statuslabel'] ?? $this->get_status_label($status);
        $data['visibility'] = $visibility;
        $data['visibilitylabel'] = $data['visibilitylabel'] ?? $this->get_visibility_label($visibility);

        $data['summary'] = $this->normalise_html_value($data['summary'] ?? '');
        $data['hassummary'] = trim(strip_tags($data['summary'])) !== '';

        $data['body'] = $this->normalise_html_value($data['body'] ?? '');
        $data['hasbody'] = trim(strip_tags($data['body'])) !== '';

        $data['sourcepath'] = clean_param((string)($data['sourcepath'] ?? ''), PARAM_TEXT);
        $data['hassourcepath'] = $data['sourcepath'] !== '';

        $data['versionno'] = (int)($data['versionno'] ?? 1);
        $data['canonicalhash'] = clean_param((string)($data['canonicalhash'] ?? ''), PARAM_ALPHANUMEXT);

        $data['ispublic'] = $visibility === 'public';
        $data['isinstitutional'] = $visibility === 'institution';
        $data['isprivate'] = $visibility === 'private';

        $data['isusable'] = in_array($status, ['active', 'validated'], true);
        $data['needsreview'] = in_array($status, ['draft', 'pending_review', 'contested', 'correction_required'], true);
        $data['iscontested'] = $status === 'contested';
        $data['isinvalidated'] = $status === 'invalidated';
        $data['isarchived'] = $status === 'archived';

        $data['cssclass'] = $this->get_item_css_class($itemtype, $status, $visibility);
        $data['url'] = $data['url'] ?? $this->get_item_url($id, $shortname);
        $data['hasurl'] = $data['url'] !== '';

        $data['editurl'] = $data['editurl'] ?? $this->get_item_edit_url($id);
        $data['hasediturl'] = $data['editurl'] !== '' && $this->get_option_bool('canmanage', false);

        $data['tags'] = $this->normalise_tags($data['tags'] ?? []);
        $data['hastags'] = !empty($data['tags']);

        $data['metadata'] = $this->normalise_metadata($data['metadata'] ?? []);
        $data['hasmetadata'] = !empty($data['metadata']);

        $data['timecreated'] = (int)($data['timecreated'] ?? 0);
        $data['timemodified'] = (int)($data['timemodified'] ?? 0);
        $data['hasmodifiedtime'] = $data['timemodified'] > 0;

        $data['dataattributes'] = [
            [
                'name' => 'data-canon-id',
                'value' => $id,
            ],
            [
                'name' => 'data-canon-shortname',
                'value' => $shortname,
            ],
            [
                'name' => 'data-canon-type',
                'value' => $itemtype,
            ],
            [
                'name' => 'data-canon-status',
                'value' => $status,
            ],
            [
                'name' => 'data-canon-visibility',
                'value' => $visibility,
            ],
        ];

        return $data;
    }

    /**
     * Normalise raw item data.
     *
     * @param array<string, mixed> $item Raw item data.
     * @return array<string, mixed>
     */
    private function normalise_item_data(array $item): array {
        $data = [];

        $data['id'] = (int)($item['id'] ?? 0);
        $data['contextid'] = (int)($item['contextid'] ?? 0);
        $data['parentid'] = (int)($item['parentid'] ?? 0);
        $data['shortname'] = clean_param((string)($item['shortname'] ?? ''), PARAM_ALPHANUMEXT);
        $data['title'] = clean_param((string)($item['title'] ?? $item['fullname'] ?? ''), PARAM_TEXT);
        $data['itemtype'] = clean_param((string)($item['itemtype'] ?? 'principle'), PARAM_ALPHANUMEXT);
        $data['sourcepath'] = clean_param((string)($item['sourcepath'] ?? ''), PARAM_TEXT);
        $data['summary'] = (string)($item['summary'] ?? '');
        $data['body'] = (string)($item['body'] ?? '');
        $data['status'] = clean_param((string)($item['status'] ?? 'draft'), PARAM_ALPHANUMEXT);
        $data['visibility'] = clean_param((string)($item['visibility'] ?? 'institution'), PARAM_ALPHANUMEXT);
        $data['versionno'] = (int)($item['versionno'] ?? 1);
        $data['canonicalhash'] = clean_param((string)($item['canonicalhash'] ?? ''), PARAM_ALPHANUMEXT);
        $data['tags'] = $item['tags'] ?? [];
        $data['metadata'] = $item['metadata'] ?? [];
        $data['createdby'] = (int)($item['createdby'] ?? 0);
        $data['modifiedby'] = (int)($item['modifiedby'] ?? 0);
        $data['timecreated'] = (int)($item['timecreated'] ?? 0);
        $data['timemodified'] = (int)($item['timemodified'] ?? 0);
        $data['sortorder'] = (int)($item['sortorder'] ?? 0);

        return $data;
    }

    /**
     * Export filter data.
     *
     * @return stdClass
     */
    private function export_filters(): stdClass {
        $filters = new stdClass();

        $filters->typeoptions = $this->export_select_options(
            $this->get_itemtype_options(),
            $this->get_option_string('selectedtype', '')
        );

        $filters->statusoptions = $this->export_select_options(
            $this->get_status_options(),
            $this->get_option_string('selectedstatus', '')
        );

        $filters->visibilityoptions = $this->export_select_options(
            $this->get_visibility_options(),
            $this->get_option_string('selectedvisibility', '')
        );

        $filters->searchquery = $this->get_option_string('searchquery', '');
        $filters->actionurl = $this->get_option_string(
            'filterurl',
            (new moodle_url('/local/uckk/canon.php'))->out(false)
        );

        return $filters;
    }

    /**
     * Export panel actions.
     *
     * @return array<int, array<string, mixed>>
     */
    private function export_actions(): array {
        $actions = [];

        if ($this->get_option_bool('cancreate', false)) {
            $actions[] = [
                'key' => 'create',
                'label' => self::string('canon:action:create', 'Créer un élément canonique'),
                'url' => $this->get_option_string(
                    'createurl',
                    (new moodle_url('/local/uckk/canon.php', ['action' => 'create']))->out(false)
                ),
                'primary' => true,
            ];
        }

        if ($this->get_option_bool('canexport', false)) {
            $actions[] = [
                'key' => 'export',
                'label' => self::string('canon:action:export', 'Exporter le canon'),
                'url' => $this->get_option_string(
                    'exporturl',
                    (new moodle_url('/local/uckk/canon.php', ['action' => 'export']))->out(false)
                ),
                'primary' => false,
            ];
        }

        return $actions;
    }

    /**
     * Export the canonical UCKK hierarchy.
     *
     * @return array<int, array<string, string>>
     */
    private function export_hierarchy(): array {
        return [
            [
                'key' => 'koa',
                'label' => self::string('canon:hierarchy:koa', 'kOA'),
                'description' => self::string('canon:hierarchy:koa_desc', 'Mouvement'),
            ],
            [
                'key' => 'uckk',
                'label' => self::string('canon:hierarchy:uckk', 'UCKK'),
                'description' => self::string('canon:hierarchy:uckk_desc', 'École / cité d’apprentissage'),
            ],
            [
                'key' => 'digital_ecosystem',
                'label' => self::string('canon:hierarchy:digitalecosystem', 'kOA Digital Ecosystem'),
                'description' => self::string('canon:hierarchy:digitalecosystem_desc', 'Infrastructure numérique'),
            ],
            [
                'key' => 'king_klown',
                'label' => self::string('canon:hierarchy:kingklown', 'King Klown'),
                'description' => self::string('canon:hierarchy:kingklown_desc', 'Figure narrative de mobilisation'),
            ],
            [
                'key' => 'inquisiteur',
                'label' => self::string('canon:hierarchy:inquisiteur', 'Inquisiteur'),
                'description' => self::string('canon:hierarchy:inquisiteur_desc', 'Garde-fou éthique et méthodologique'),
            ],
            [
                'key' => 'assemblees',
                'label' => self::string('canon:hierarchy:assemblees', 'Assemblées'),
                'description' => self::string('canon:hierarchy:assemblees_desc', 'Légitimité collective'),
            ],
            [
                'key' => 'archives',
                'label' => self::string('canon:hierarchy:archives', 'Archives'),
                'description' => self::string('canon:hierarchy:archives_desc', 'Mémoire'),
            ],
        ];
    }

    /**
     * Export wrapper attributes.
     *
     * @return array<int, array{name: string, value: string|int}>
     */
    private function export_attributes(): array {
        $attributes = [
            'data-region' => 'local-uckk-canon-panel',
            'data-item-count' => count($this->items),
        ];

        $contextid = $this->get_option_int('contextid', 0);
        if ($contextid > 0) {
            $attributes['data-contextid'] = $contextid;
        }

        $exported = [];
        foreach ($attributes as $name => $value) {
            $exported[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        return $exported;
    }

    /**
     * Return item URL.
     *
     * @param int $id Item id.
     * @param string $shortname Item shortname.
     * @return string
     */
    private function get_item_url(int $id, string $shortname): string {
        if ($id > 0) {
            return (new moodle_url('/local/uckk/canon.php', ['id' => $id]))->out(false);
        }

        if ($shortname !== '') {
            return (new moodle_url('/local/uckk/canon.php', ['shortname' => $shortname]))->out(false);
        }

        return '';
    }

    /**
     * Return item edit URL.
     *
     * @param int $id Item id.
     * @return string
     */
    private function get_item_edit_url(int $id): string {
        if ($id <= 0) {
            return '';
        }

        return (new moodle_url('/local/uckk/canon.php', [
            'id' => $id,
            'action' => 'edit',
        ]))->out(false);
    }

    /**
     * Build CSS class for item.
     *
     * @param string $itemtype Item type.
     * @param string $status Status.
     * @param string $visibility Visibility.
     * @return string
     */
    private function get_item_css_class(string $itemtype, string $status, string $visibility): string {
        return implode(' ', [
            'local-uckk-canon-item',
            'local-uckk-canon-itemtype-' . clean_param($itemtype, PARAM_ALPHANUMEXT),
            'local-uckk-canon-status-' . clean_param($status, PARAM_ALPHANUMEXT),
            'local-uckk-canon-visibility-' . clean_param($visibility, PARAM_ALPHANUMEXT),
        ]);
    }

    /**
     * Return item type options.
     *
     * @return array<string, string>
     */
    private function get_itemtype_options(): array {
        if (class_exists(canon_item::class)) {
            return ['' => self::string('canon:filter:alltypes', 'Tous les types')] + canon_item::get_itemtypes();
        }

        return [
            '' => self::string('canon:filter:alltypes', 'Tous les types'),
            'index' => self::string('canon:itemtype:index', 'Index'),
            'glossary' => self::string('canon:itemtype:glossary', 'Glossaire'),
            'architecture' => self::string('canon:itemtype:architecture', 'Architecture'),
            'principle' => self::string('canon:itemtype:principle', 'Principe'),
            'boundary' => self::string('canon:itemtype:boundary', 'Limite'),
            'formula' => self::string('canon:itemtype:formula', 'Formule'),
            'rule' => self::string('canon:itemtype:rule', 'Règle'),
            'program' => self::string('canon:itemtype:program', 'Programme'),
            'course' => self::string('canon:itemtype:course', 'Cours'),
            'protocol' => self::string('canon:itemtype:protocol', 'Protocole'),
            'governance' => self::string('canon:itemtype:governance', 'Gouvernance'),
            'challenge' => self::string('canon:itemtype:challenge', 'Défi'),
            'assembly' => self::string('canon:itemtype:assembly', 'Assemblée'),
            'archive' => self::string('canon:itemtype:archive', 'Archive'),
            'integrity' => self::string('canon:itemtype:integrity', 'Intégrité'),
            'ai' => self::string('canon:itemtype:ai', 'IA gouvernable'),
        ];
    }

    /**
     * Return status options.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        if (class_exists(canon_item::class)) {
            return ['' => self::string('canon:filter:allstatuses', 'Tous les statuts')] + canon_item::get_statuses();
        }

        return [
            '' => self::string('canon:filter:allstatuses', 'Tous les statuts'),
            'draft' => self::string('status:draft', 'Brouillon'),
            'active' => self::string('status:active', 'Actif'),
            'pending_review' => self::string('status:pending_review', 'En attente de révision'),
            'validated' => self::string('status:validated', 'Validé'),
            'contested' => self::string('status:contested', 'Contesté'),
            'correction_required' => self::string('status:correction_required', 'Correction requise'),
            'invalidated' => self::string('status:invalidated', 'Invalidé'),
            'archived' => self::string('status:archived', 'Archivé'),
        ];
    }

    /**
     * Return visibility options.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        if (class_exists(canon_item::class)) {
            return ['' => self::string('canon:filter:allvisibilities', 'Toutes les visibilités')] + canon_item::get_visibilities();
        }

        return [
            '' => self::string('canon:filter:allvisibilities', 'Toutes les visibilités'),
            'private' => self::string('visibility:private', 'Privé'),
            'course' => self::string('visibility:course', 'Cours'),
            'cohort' => self::string('visibility:cohort', 'Cohorte'),
            'institution' => self::string('visibility:institution', 'Institution'),
            'public' => self::string('visibility:public', 'Public'),
        ];
    }

    /**
     * Export select options.
     *
     * @param array<string, string> $options Options.
     * @param string $selected Selected key.
     * @return array<int, array<string, mixed>>
     */
    private function export_select_options(array $options, string $selected): array {
        $exported = [];

        foreach ($options as $value => $label) {
            $value = (string)$value;
            $exported[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $selected,
            ];
        }

        return $exported;
    }

    /**
     * Return item type label.
     *
     * @param string $itemtype Item type.
     * @return string
     */
    private function get_itemtype_label(string $itemtype): string {
        $options = $this->get_itemtype_options();

        return $options[$itemtype] ?? self::humanise_key($itemtype);
    }

    /**
     * Return status label.
     *
     * @param string $status Status.
     * @return string
     */
    private function get_status_label(string $status): string {
        $options = $this->get_status_options();

        return $options[$status] ?? self::humanise_key($status);
    }

    /**
     * Return visibility label.
     *
     * @param string $visibility Visibility.
     * @return string
     */
    private function get_visibility_label(string $visibility): string {
        $options = $this->get_visibility_options();

        return $options[$visibility] ?? self::humanise_key($visibility);
    }

    /**
     * Normalise HTML value.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function normalise_html_value($value): string {
        if ($value === null) {
            return '';
        }

        return format_text((string)$value, FORMAT_HTML);
    }

    /**
     * Normalise tags for template.
     *
     * @param mixed $tags Raw tags.
     * @return array<int, array<string, string>>
     */
    private function normalise_tags($tags): array {
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : [$tags];
        }

        if (!is_array($tags)) {
            return [];
        }

        $normalised = [];

        foreach ($tags as $tag) {
            if (is_array($tag)) {
                $key = clean_param((string)($tag['key'] ?? $tag['label'] ?? ''), PARAM_ALPHANUMEXT);
                $label = clean_param((string)($tag['label'] ?? self::humanise_key($key)), PARAM_TEXT);
            } else {
                $key = clean_param((string)$tag, PARAM_ALPHANUMEXT);
                $label = self::humanise_key($key);
            }

            if ($key === '') {
                continue;
            }

            $normalised[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return $normalised;
    }

    /**
     * Normalise metadata for template.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<int, array<string, string>>
     */
    private function normalise_metadata($metadata): array {
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($metadata)) {
            return [];
        }

        $normalised = [];

        foreach ($metadata as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $key = clean_param((string)$key, PARAM_ALPHANUMEXT);
            $value = clean_param((string)$value, PARAM_TEXT);

            if ($key === '') {
                continue;
            }

            $normalised[] = [
                'key' => $key,
                'label' => self::humanise_key($key),
                'value' => $value,
            ];
        }

        return $normalised;
    }

    /**
     * Get string option.
     *
     * @param string $name Option name.
     * @param string $default Default value.
     * @return string
     */
    private function get_option_string(string $name, string $default = ''): string {
        if (!array_key_exists($name, $this->options)) {
            return $default;
        }

        return clean_param((string)$this->options[$name], PARAM_TEXT);
    }

    /**
     * Get bool option.
     *
     * @param string $name Option name.
     * @param bool $default Default.
     * @return bool
     */
    private function get_option_bool(string $name, bool $default = false): bool {
        if (!array_key_exists($name, $this->options)) {
            return $default;
        }

        return (bool)$this->options[$name];
    }

    /**
     * Get int option.
     *
     * @param string $name Option name.
     * @param int $default Default.
     * @return int
     */
    private function get_option_int(string $name, int $default = 0): int {
        if (!array_key_exists($name, $this->options)) {
            return $default;
        }

        return (int)$this->options[$name];
    }

    /**
     * Get array option.
     *
     * @param string $name Option name.
     * @param array $default Default.
     * @return array
     */
    private function get_option_array(string $name, array $default = []): array {
        if (!array_key_exists($name, $this->options) || !is_array($this->options[$name])) {
            return $default;
        }

        return $this->options[$name];
    }

    /**
     * Convert machine key to readable label.
     *
     * @param string $key Machine key.
     * @return string
     */
    private static function humanise_key(string $key): string {
        $key = str_replace(['_', '-'], ' ', $key);
        $key = trim($key);

        if ($key === '') {
            return '';
        }

        return \core_text::strtotitle($key);
    }

    /**
     * Safe get_string wrapper.
     *
     * @param string $identifier String identifier.
     * @param string $fallback Fallback text.
     * @return string
     */
    private static function string(string $identifier, string $fallback): string {
        if (get_string_manager()->string_exists($identifier, self::COMPONENT)) {
            return get_string($identifier, self::COMPONENT);
        }

        if (get_string_manager()->string_exists($identifier, 'theme_uckk')) {
            return get_string($identifier, 'theme_uckk');
        }

        return $fallback;
    }
}

