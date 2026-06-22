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
 * UCKK canonical item value object.
 *
 * A canonical item represents a stable institutional reference in UCKK:
 * a definition, rule, principle, formula, boundary, program, course, protocol,
 * archive reference, governance note or source document.
 *
 * This class is a local domain object. It must not perform permission checks,
 * update Moodle roles, publish archive records, decide integrity cases, mutate
 * course content, or call external services. Persistence belongs to services
 * and repositories; this object normalises, validates and exports data.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

use context;
use context_system;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical item.
 *
 * Expected backing table: local_uckk_canon
 *
 * Suggested fields:
 * - id
 * - contextid
 * - parentid
 * - shortname
 * - title
 * - itemtype
 * - sourcepath
 * - summary
 * - body
 * - status
 * - visibility
 * - versionno
 * - canonicalhash
 * - tags
 * - metadata
 * - createdby
 * - modifiedby
 * - timecreated
 * - timemodified
 * - sortorder
 *
 * @package local_uckk
 */
final class canon_item {
    /** Component name. */
    public const COMPONENT = 'local_uckk';

    /** Backing table name. */
    public const TABLE = 'local_uckk_canon';

    /** Item type: index. */
    public const TYPE_INDEX = 'index';

    /** Item type: glossary. */
    public const TYPE_GLOSSARY = 'glossary';

    /** Item type: architecture. */
    public const TYPE_ARCHITECTURE = 'architecture';

    /** Item type: principle. */
    public const TYPE_PRINCIPLE = 'principle';

    /** Item type: boundary. */
    public const TYPE_BOUNDARY = 'boundary';

    /** Item type: formula. */
    public const TYPE_FORMULA = 'formula';

    /** Item type: rule. */
    public const TYPE_RULE = 'rule';

    /** Item type: program. */
    public const TYPE_PROGRAM = 'program';

    /** Item type: course. */
    public const TYPE_COURSE = 'course';

    /** Item type: protocol. */
    public const TYPE_PROTOCOL = 'protocol';

    /** Item type: governance. */
    public const TYPE_GOVERNANCE = 'governance';

    /** Item type: challenge. */
    public const TYPE_CHALLENGE = 'challenge';

    /** Item type: assembly. */
    public const TYPE_ASSEMBLY = 'assembly';

    /** Item type: archive. */
    public const TYPE_ARCHIVE = 'archive';

    /** Item type: integrity. */
    public const TYPE_INTEGRITY = 'integrity';

    /** Item type: AI governance. */
    public const TYPE_AI = 'ai';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: pending review. */
    public const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: validated. */
    public const STATUS_VALIDATED = 'validated';

    /** Status: contested. */
    public const STATUS_CONTESTED = 'contested';

    /** Status: correction required. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Status: invalidated. */
    public const STATUS_INVALIDATED = 'invalidated';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: course. */
    public const VISIBILITY_COURSE = 'course';

    /** Visibility: cohort. */
    public const VISIBILITY_COHORT = 'cohort';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /** Default version number. */
    public const DEFAULT_VERSION = 1;

    /** Maximum shortname length. */
    public const SHORTNAME_MAX_LENGTH = 100;

    /** Maximum title length. */
    public const TITLE_MAX_LENGTH = 255;

    /** Maximum source path length. */
    public const SOURCEPATH_MAX_LENGTH = 512;

    /** @var int Canon item id. */
    private int $id;

    /** @var int Moodle context id. */
    private int $contextid;

    /** @var int Parent canonical item id. */
    private int $parentid;

    /** @var string Stable machine name. */
    private string $shortname;

    /** @var string Human title. */
    private string $title;

    /** @var string Canon item type. */
    private string $itemtype;

    /** @var string Source path or canonical reference. */
    private string $sourcepath;

    /** @var string Short summary. */
    private string $summary;

    /** @var string Main body. */
    private string $body;

    /** @var string Workflow status. */
    private string $status;

    /** @var string Visibility level. */
    private string $visibility;

    /** @var int Version number. */
    private int $versionno;

    /** @var string Canonical hash. */
    private string $canonicalhash;

    /** @var string[] Tags. */
    private array $tags;

    /** @var array<string, mixed> Metadata. */
    private array $metadata;

    /** @var int User id that created the item. */
    private int $createdby;

    /** @var int User id that last modified the item. */
    private int $modifiedby;

    /** @var int Creation timestamp. */
    private int $timecreated;

    /** @var int Last modification timestamp. */
    private int $timemodified;

    /** @var int Sort order. */
    private int $sortorder;

    /**
     * Constructor.
     *
     * Prefer using from_record(), from_array(), or create_new().
     *
     * @param int $id Canon item id.
     * @param int $contextid Context id.
     * @param int $parentid Parent item id.
     * @param string $shortname Stable shortname.
     * @param string $title Human title.
     * @param string $itemtype Item type.
     * @param string $sourcepath Source path.
     * @param string $summary Summary.
     * @param string $body Body.
     * @param string $status Status.
     * @param string $visibility Visibility.
     * @param int $versionno Version number.
     * @param string $canonicalhash Hash.
     * @param array $tags Tags.
     * @param array $metadata Metadata.
     * @param int $createdby Created by.
     * @param int $modifiedby Modified by.
     * @param int $timecreated Time created.
     * @param int $timemodified Time modified.
     * @param int $sortorder Sort order.
     */
    private function __construct(
        int $id,
        int $contextid,
        int $parentid,
        string $shortname,
        string $title,
        string $itemtype,
        string $sourcepath,
        string $summary,
        string $body,
        string $status,
        string $visibility,
        int $versionno,
        string $canonicalhash,
        array $tags,
        array $metadata,
        int $createdby,
        int $modifiedby,
        int $timecreated,
        int $timemodified,
        int $sortorder
    ) {
        $this->id = max(0, $id);
        $this->contextid = $contextid > 0 ? $contextid : context_system::instance()->id;
        $this->parentid = max(0, $parentid);
        $this->shortname = self::normalise_shortname($shortname);
        $this->title = self::normalise_title($title);
        $this->itemtype = self::normalise_itemtype($itemtype);
        $this->sourcepath = self::normalise_sourcepath($sourcepath);
        $this->summary = trim($summary);
        $this->body = trim($body);
        $this->status = self::normalise_status($status);
        $this->visibility = self::normalise_visibility($visibility);
        $this->versionno = max(1, $versionno);
        $this->tags = self::normalise_tags($tags);
        $this->metadata = self::normalise_metadata($metadata);
        $this->createdby = max(0, $createdby);
        $this->modifiedby = max(0, $modifiedby);
        $this->timecreated = max(0, $timecreated);
        $this->timemodified = max(0, $timemodified);
        $this->sortorder = $sortorder;

        $this->canonicalhash = $canonicalhash !== ''
            ? clean_param($canonicalhash, PARAM_ALPHANUMEXT)
            : self::calculate_hash_from_values(
                $this->shortname,
                $this->title,
                $this->itemtype,
                $this->sourcepath,
                $this->summary,
                $this->body,
                $this->versionno
            );
    }

    /**
     * Create a canon item from a database record.
     *
     * @param stdClass $record Database record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self(
            (int)($record->id ?? 0),
            (int)($record->contextid ?? 0),
            (int)($record->parentid ?? 0),
            (string)($record->shortname ?? ''),
            (string)($record->title ?? ''),
            (string)($record->itemtype ?? self::TYPE_PRINCIPLE),
            (string)($record->sourcepath ?? ''),
            (string)($record->summary ?? ''),
            (string)($record->body ?? ''),
            (string)($record->status ?? self::STATUS_DRAFT),
            (string)($record->visibility ?? self::VISIBILITY_INSTITUTION),
            (int)($record->versionno ?? self::DEFAULT_VERSION),
            (string)($record->canonicalhash ?? ''),
            self::decode_json_list($record->tags ?? ''),
            self::decode_json_object($record->metadata ?? ''),
            (int)($record->createdby ?? 0),
            (int)($record->modifiedby ?? 0),
            (int)($record->timecreated ?? 0),
            (int)($record->timemodified ?? 0),
            (int)($record->sortorder ?? 0)
        );
    }

    /**
     * Create a canon item from an array.
     *
     * @param array<string, mixed> $data Input data.
     * @return self
     */
    public static function from_array(array $data): self {
        return self::from_record((object)$data);
    }

    /**
     * Create a new canonical item.
     *
     * @param string $shortname Stable shortname.
     * @param string $title Title.
     * @param string $itemtype Item type.
     * @param string $body Body.
     * @param array<string, mixed> $options Optional values.
     * @return self
     */
    public static function create_new(
        string $shortname,
        string $title,
        string $itemtype,
        string $body,
        array $options = []
    ): self {
        global $USER;

        $now = time();
        $userid = isset($USER->id) ? (int)$USER->id : 0;

        return new self(
            0,
            (int)($options['contextid'] ?? context_system::instance()->id),
            (int)($options['parentid'] ?? 0),
            $shortname,
            $title,
            $itemtype,
            (string)($options['sourcepath'] ?? ''),
            (string)($options['summary'] ?? ''),
            $body,
            (string)($options['status'] ?? self::STATUS_DRAFT),
            (string)($options['visibility'] ?? self::VISIBILITY_INSTITUTION),
            (int)($options['versionno'] ?? self::DEFAULT_VERSION),
            (string)($options['canonicalhash'] ?? ''),
            (array)($options['tags'] ?? []),
            (array)($options['metadata'] ?? []),
            (int)($options['createdby'] ?? $userid),
            (int)($options['modifiedby'] ?? $userid),
            (int)($options['timecreated'] ?? $now),
            (int)($options['timemodified'] ?? $now),
            (int)($options['sortorder'] ?? 0)
        );
    }

    /**
     * Return a canonical item representing the UCKK hierarchy formula.
     *
     * @return self
     */
    public static function hierarchy_formula(): self {
        return self::create_new(
            'uckk_hierarchy_formula',
            'Hiérarchie canonique UCKK',
            self::TYPE_FORMULA,
            'kOA est le mouvement. UCKK est l’école. Le kOA Digital Ecosystem est l’infrastructure. King Klown est la figure narrative. L’Inquisiteur protège. Les Assemblées légitiment. L’Archiviste conserve la mémoire.',
            [
                'sourcepath' => 'UCKK_Canon/01_glossaire.md',
                'summary' => 'Formule de séparation entre mouvement, école, infrastructure, récit, gouvernance et mémoire.',
                'status' => self::STATUS_VALIDATED,
                'visibility' => self::VISIBILITY_PUBLIC,
                'tags' => ['hierarchy', 'glossary', 'identity'],
            ]
        );
    }

    /**
     * Return id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Return context id.
     *
     * @return int
     */
    public function get_contextid(): int {
        return $this->contextid;
    }

    /**
     * Return context instance if it exists.
     *
     * @return context|null
     */
    public function get_context(): ?context {
        return context::instance_by_id($this->contextid, IGNORE_MISSING);
    }

    /**
     * Return parent id.
     *
     * @return int
     */
    public function get_parentid(): int {
        return $this->parentid;
    }

    /**
     * Return shortname.
     *
     * @return string
     */
    public function get_shortname(): string {
        return $this->shortname;
    }

    /**
     * Return title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Return item type.
     *
     * @return string
     */
    public function get_itemtype(): string {
        return $this->itemtype;
    }

    /**
     * Return source path.
     *
     * @return string
     */
    public function get_sourcepath(): string {
        return $this->sourcepath;
    }

    /**
     * Return summary.
     *
     * @return string
     */
    public function get_summary(): string {
        return $this->summary;
    }

    /**
     * Return body.
     *
     * @return string
     */
    public function get_body(): string {
        return $this->body;
    }

    /**
     * Return status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Return visibility.
     *
     * @return string
     */
    public function get_visibility(): string {
        return $this->visibility;
    }

    /**
     * Return version number.
     *
     * @return int
     */
    public function get_versionno(): int {
        return $this->versionno;
    }

    /**
     * Return canonical hash.
     *
     * @return string
     */
    public function get_canonicalhash(): string {
        return $this->canonicalhash;
    }

    /**
     * Return tags.
     *
     * @return string[]
     */
    public function get_tags(): array {
        return $this->tags;
    }

    /**
     * Return metadata.
     *
     * @return array<string, mixed>
     */
    public function get_metadata(): array {
        return $this->metadata;
    }

    /**
     * Return created by user id.
     *
     * @return int
     */
    public function get_createdby(): int {
        return $this->createdby;
    }

    /**
     * Return modified by user id.
     *
     * @return int
     */
    public function get_modifiedby(): int {
        return $this->modifiedby;
    }

    /**
     * Return creation timestamp.
     *
     * @return int
     */
    public function get_timecreated(): int {
        return $this->timecreated;
    }

    /**
     * Return modification timestamp.
     *
     * @return int
     */
    public function get_timemodified(): int {
        return $this->timemodified;
    }

    /**
     * Return sort order.
     *
     * @return int
     */
    public function get_sortorder(): int {
        return $this->sortorder;
    }

    /**
     * Determine whether item is public.
     *
     * @return bool
     */
    public function is_public(): bool {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Determine whether item is active or validated.
     *
     * @return bool
     */
    public function is_usable(): bool {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_VALIDATED], true);
    }

    /**
     * Determine whether item needs review.
     *
     * @return bool
     */
    public function needs_review(): bool {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_CONTESTED,
            self::STATUS_CORRECTION_REQUIRED,
        ], true);
    }

    /**
     * Determine whether item is contested.
     *
     * @return bool
     */
    public function is_contested(): bool {
        return $this->status === self::STATUS_CONTESTED;
    }

    /**
     * Determine whether item is invalidated.
     *
     * @return bool
     */
    public function is_invalidated(): bool {
        return $this->status === self::STATUS_INVALIDATED;
    }

    /**
     * Determine whether item has body text.
     *
     * @return bool
     */
    public function has_body(): bool {
        return trim($this->body) !== '';
    }

    /**
     * Determine whether item has summary text.
     *
     * @return bool
     */
    public function has_summary(): bool {
        return trim($this->summary) !== '';
    }

    /**
     * Determine whether item has source path.
     *
     * @return bool
     */
    public function has_sourcepath(): bool {
        return trim($this->sourcepath) !== '';
    }

    /**
     * Determine whether item has tags.
     *
     * @return bool
     */
    public function has_tags(): bool {
        return !empty($this->tags);
    }

    /**
     * Return a new instance marked with a new status.
     *
     * This object is immutable; this method returns a new instance.
     *
     * @param string $status New status.
     * @param int|null $modifiedby User id.
     * @return self
     */
    public function with_status(string $status, ?int $modifiedby = null): self {
        $record = $this->to_record();
        $record->status = self::normalise_status($status);
        $record->modifiedby = $modifiedby ?? $this->modifiedby;
        $record->timemodified = time();

        return self::from_record($record);
    }

    /**
     * Return a new instance marked with a new visibility.
     *
     * @param string $visibility New visibility.
     * @param int|null $modifiedby User id.
     * @return self
     */
    public function with_visibility(string $visibility, ?int $modifiedby = null): self {
        $record = $this->to_record();
        $record->visibility = self::normalise_visibility($visibility);
        $record->modifiedby = $modifiedby ?? $this->modifiedby;
        $record->timemodified = time();

        return self::from_record($record);
    }

    /**
     * Return a new instance with updated content and incremented version.
     *
     * @param string $title New title.
     * @param string $summary New summary.
     * @param string $body New body.
     * @param int|null $modifiedby User id.
     * @return self
     */
    public function with_updated_content(
        string $title,
        string $summary,
        string $body,
        ?int $modifiedby = null
    ): self {
        $record = $this->to_record();
        $record->title = self::normalise_title($title);
        $record->summary = trim($summary);
        $record->body = trim($body);
        $record->versionno = $this->versionno + 1;
        $record->canonicalhash = self::calculate_hash_from_values(
            $record->shortname,
            $record->title,
            $record->itemtype,
            $record->sourcepath,
            $record->summary,
            $record->body,
            $record->versionno
        );
        $record->modifiedby = $modifiedby ?? $this->modifiedby;
        $record->timemodified = time();

        return self::from_record($record);
    }

    /**
     * Convert object to database record.
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        return (object)[
            'id' => $this->id,
            'contextid' => $this->contextid,
            'parentid' => $this->parentid,
            'shortname' => $this->shortname,
            'title' => $this->title,
            'itemtype' => $this->itemtype,
            'sourcepath' => $this->sourcepath,
            'summary' => $this->summary,
            'body' => $this->body,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'versionno' => $this->versionno,
            'canonicalhash' => $this->canonicalhash,
            'tags' => json_encode($this->tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'metadata' => json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'createdby' => $this->createdby,
            'modifiedby' => $this->modifiedby,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'sortorder' => $this->sortorder,
        ];
    }

    /**
     * Export item for Mustache templates.
     *
     * @param array<string, mixed> $overrides Optional prepared display data.
     * @return array<string, mixed>
     */
    public function export_for_template(array $overrides = []): array {
        $data = [
            'id' => $this->id,
            'contextid' => $this->contextid,
            'parentid' => $this->parentid,
            'shortname' => $this->shortname,
            'title' => format_string($this->title),
            'itemtype' => $this->itemtype,
            'itemtypelabel' => self::get_itemtype_label($this->itemtype),
            'sourcepath' => $this->sourcepath,
            'hassourcepath' => $this->has_sourcepath(),
            'summary' => format_text($this->summary, FORMAT_HTML),
            'hassummary' => $this->has_summary(),
            'body' => format_text($this->body, FORMAT_HTML),
            'hasbody' => $this->has_body(),
            'status' => $this->status,
            'statuslabel' => self::get_status_label($this->status),
            'visibility' => $this->visibility,
            'visibilitylabel' => self::get_visibility_label($this->visibility),
            'versionno' => $this->versionno,
            'canonicalhash' => $this->canonicalhash,
            'tags' => array_map(static function(string $tag): array {
                return [
                    'key' => $tag,
                    'label' => self::humanise_key($tag),
                ];
            }, $this->tags),
            'hastags' => $this->has_tags(),
            'metadata' => $this->metadata,
            'createdby' => $this->createdby,
            'modifiedby' => $this->modifiedby,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'sortorder' => $this->sortorder,
            'ispublic' => $this->is_public(),
            'isusable' => $this->is_usable(),
            'needsreview' => $this->needs_review(),
            'iscontested' => $this->is_contested(),
            'isinvalidated' => $this->is_invalidated(),
        ];

        return array_merge($data, $overrides);
    }

    /**
     * Export item for external services.
     *
     * @return array<string, mixed>
     */
    public function export_for_external(): array {
        return [
            'id' => $this->id,
            'contextid' => $this->contextid,
            'parentid' => $this->parentid,
            'shortname' => $this->shortname,
            'title' => $this->title,
            'itemtype' => $this->itemtype,
            'sourcepath' => $this->sourcepath,
            'summary' => $this->summary,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'versionno' => $this->versionno,
            'canonicalhash' => $this->canonicalhash,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'sortorder' => $this->sortorder,
        ];
    }

    /**
     * Validate item for storage.
     *
     * @return array<string, string> Field => error message.
     */
    public function validate(): array {
        $errors = [];

        if ($this->shortname === '') {
            $errors['shortname'] = self::string('canon:error:missingshortname', 'Le nom court canonique est requis.');
        }

        if (\core_text::strlen($this->shortname) > self::SHORTNAME_MAX_LENGTH) {
            $errors['shortname'] = self::string('canon:error:shortnametoolong', 'Le nom court canonique est trop long.');
        }

        if ($this->title === '') {
            $errors['title'] = self::string('canon:error:missingtitle', 'Le titre canonique est requis.');
        }

        if (\core_text::strlen($this->title) > self::TITLE_MAX_LENGTH) {
            $errors['title'] = self::string('canon:error:titletoolong', 'Le titre canonique est trop long.');
        }

        if (!array_key_exists($this->itemtype, self::get_itemtypes())) {
            $errors['itemtype'] = self::string('canon:error:invaliditemtype', 'Le type d’élément canonique n’est pas valide.');
        }

        if (!array_key_exists($this->status, self::get_statuses())) {
            $errors['status'] = self::string('canon:error:invalidstatus', 'Le statut canonique n’est pas valide.');
        }

        if (!array_key_exists($this->visibility, self::get_visibilities())) {
            $errors['visibility'] = self::string('canon:error:invalidvisibility', 'La visibilité canonique n’est pas valide.');
        }

        if (\core_text::strlen($this->sourcepath) > self::SOURCEPATH_MAX_LENGTH) {
            $errors['sourcepath'] = self::string('canon:error:sourcepathtoolong', 'Le chemin source est trop long.');
        }

        if ($this->versionno < 1) {
            $errors['versionno'] = self::string('canon:error:invalidversion', 'La version doit être supérieure ou égale à 1.');
        }

        return $errors;
    }

    /**
     * Return supported item types.
     *
     * @return array<string, string>
     */
    public static function get_itemtypes(): array {
        return [
            self::TYPE_INDEX => self::string('canon:itemtype:index', 'Index'),
            self::TYPE_GLOSSARY => self::string('canon:itemtype:glossary', 'Glossaire'),
            self::TYPE_ARCHITECTURE => self::string('canon:itemtype:architecture', 'Architecture'),
            self::TYPE_PRINCIPLE => self::string('canon:itemtype:principle', 'Principe'),
            self::TYPE_BOUNDARY => self::string('canon:itemtype:boundary', 'Limite'),
            self::TYPE_FORMULA => self::string('canon:itemtype:formula', 'Formule'),
            self::TYPE_RULE => self::string('canon:itemtype:rule', 'Règle'),
            self::TYPE_PROGRAM => self::string('canon:itemtype:program', 'Programme'),
            self::TYPE_COURSE => self::string('canon:itemtype:course', 'Cours'),
            self::TYPE_PROTOCOL => self::string('canon:itemtype:protocol', 'Protocole'),
            self::TYPE_GOVERNANCE => self::string('canon:itemtype:governance', 'Gouvernance'),
            self::TYPE_CHALLENGE => self::string('canon:itemtype:challenge', 'Défi'),
            self::TYPE_ASSEMBLY => self::string('canon:itemtype:assembly', 'Assemblée'),
            self::TYPE_ARCHIVE => self::string('canon:itemtype:archive', 'Archive'),
            self::TYPE_INTEGRITY => self::string('canon:itemtype:integrity', 'Intégrité'),
            self::TYPE_AI => self::string('canon:itemtype:ai', 'IA gouvernable'),
        ];
    }

    /**
     * Return supported statuses.
     *
     * @return array<string, string>
     */
    public static function get_statuses(): array {
        return [
            self::STATUS_DRAFT => self::string('status:draft', 'Brouillon'),
            self::STATUS_ACTIVE => self::string('status:active', 'Actif'),
            self::STATUS_PENDING_REVIEW => self::string('status:pending_review', 'En attente de révision'),
            self::STATUS_VALIDATED => self::string('status:validated', 'Validé'),
            self::STATUS_CONTESTED => self::string('status:contested', 'Contesté'),
            self::STATUS_CORRECTION_REQUIRED => self::string('status:correction_required', 'Correction requise'),
            self::STATUS_INVALIDATED => self::string('status:invalidated', 'Invalidé'),
            self::STATUS_ARCHIVED => self::string('status:archived', 'Archivé'),
        ];
    }

    /**
     * Return supported visibilities.
     *
     * @return array<string, string>
     */
    public static function get_visibilities(): array {
        return [
            self::VISIBILITY_PRIVATE => self::string('visibility:private', 'Privé'),
            self::VISIBILITY_COURSE => self::string('visibility:course', 'Cours'),
            self::VISIBILITY_COHORT => self::string('visibility:cohort', 'Cohorte'),
            self::VISIBILITY_INSTITUTION => self::string('visibility:institution', 'Institution'),
            self::VISIBILITY_PUBLIC => self::string('visibility:public', 'Public'),
        ];
    }

    /**
     * Return item type label.
     *
     * @param string $itemtype Item type.
     * @return string
     */
    public static function get_itemtype_label(string $itemtype): string {
        $itemtype = self::normalise_itemtype($itemtype);
        $types = self::get_itemtypes();

        return $types[$itemtype] ?? self::humanise_key($itemtype);
    }

    /**
     * Return status label.
     *
     * @param string $status Status.
     * @return string
     */
    public static function get_status_label(string $status): string {
        $status = self::normalise_status($status);
        $statuses = self::get_statuses();

        return $statuses[$status] ?? self::humanise_key($status);
    }

    /**
     * Return visibility label.
     *
     * @param string $visibility Visibility.
     * @return string
     */
    public static function get_visibility_label(string $visibility): string {
        $visibility = self::normalise_visibility($visibility);
        $visibilities = self::get_visibilities();

        return $visibilities[$visibility] ?? self::humanise_key($visibility);
    }

    /**
     * Normalise shortname.
     *
     * @param string $shortname Raw shortname.
     * @return string
     */
    private static function normalise_shortname(string $shortname): string {
        $shortname = trim(\core_text::strtolower($shortname));
        $shortname = str_replace([' ', '-', '/', '\\', ':'], '_', $shortname);
        $shortname = clean_param($shortname, PARAM_ALPHANUMEXT);

        return \core_text::substr($shortname, 0, self::SHORTNAME_MAX_LENGTH);
    }

    /**
     * Normalise title.
     *
     * @param string $title Raw title.
     * @return string
     */
    private static function normalise_title(string $title): string {
        $title = trim(clean_param($title, PARAM_TEXT));

        return \core_text::substr($title, 0, self::TITLE_MAX_LENGTH);
    }

    /**
     * Normalise item type.
     *
     * @param string $itemtype Raw type.
     * @return string
     */
    private static function normalise_itemtype(string $itemtype): string {
        $itemtype = clean_param(\core_text::strtolower(trim($itemtype)), PARAM_ALPHANUMEXT);

        if ($itemtype === '') {
            return self::TYPE_PRINCIPLE;
        }

        return array_key_exists($itemtype, self::get_itemtypes()) ? $itemtype : self::TYPE_PRINCIPLE;
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private static function normalise_status(string $status): string {
        $status = clean_param(\core_text::strtolower(trim($status)), PARAM_ALPHANUMEXT);

        if ($status === '') {
            return self::STATUS_DRAFT;
        }

        return array_key_exists($status, self::get_statuses()) ? $status : self::STATUS_DRAFT;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private static function normalise_visibility(string $visibility): string {
        $visibility = clean_param(\core_text::strtolower(trim($visibility)), PARAM_ALPHANUMEXT);

        if ($visibility === '') {
            return self::VISIBILITY_INSTITUTION;
        }

        return array_key_exists($visibility, self::get_visibilities()) ? $visibility : self::VISIBILITY_INSTITUTION;
    }

    /**
     * Normalise source path.
     *
     * @param string $sourcepath Raw source path.
     * @return string
     */
    private static function normalise_sourcepath(string $sourcepath): string {
        $sourcepath = trim(clean_param($sourcepath, PARAM_TEXT));

        return \core_text::substr($sourcepath, 0, self::SOURCEPATH_MAX_LENGTH);
    }

    /**
     * Normalise tags.
     *
     * @param array $tags Raw tags.
     * @return string[]
     */
    private static function normalise_tags(array $tags): array {
        $normalised = [];

        foreach ($tags as $tag) {
            $tag = trim(\core_text::strtolower((string)$tag));
            $tag = str_replace([' ', '-'], '_', $tag);
            $tag = clean_param($tag, PARAM_ALPHANUMEXT);

            if ($tag !== '') {
                $normalised[] = $tag;
            }
        }

        return array_values(array_unique($normalised));
    }

    /**
     * Normalise metadata.
     *
     * @param array $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private static function normalise_metadata(array $metadata): array {
        $clean = [];

        foreach ($metadata as $key => $value) {
            $key = clean_param((string)$key, PARAM_ALPHANUMEXT);

            if ($key === '') {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            } else if (is_array($value)) {
                $clean[$key] = self::normalise_metadata($value);
            }
        }

        return $clean;
    }

    /**
     * Decode a JSON list safely.
     *
     * @param mixed $json JSON value.
     * @return array<int, string>
     */
    private static function decode_json_list($json): array {
        if (is_array($json)) {
            return self::normalise_tags($json);
        }

        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::normalise_tags($decoded);
    }

    /**
     * Decode a JSON object safely.
     *
     * @param mixed $json JSON value.
     * @return array<string, mixed>
     */
    private static function decode_json_object($json): array {
        if (is_array($json)) {
            return self::normalise_metadata($json);
        }

        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::normalise_metadata($decoded);
    }

    /**
     * Calculate canonical hash.
     *
     * @param string $shortname Shortname.
     * @param string $title Title.
     * @param string $itemtype Item type.
     * @param string $sourcepath Source path.
     * @param string $summary Summary.
     * @param string $body Body.
     * @param int $versionno Version number.
     * @return string
     */
    private static function calculate_hash_from_values(
        string $shortname,
        string $title,
        string $itemtype,
        string $sourcepath,
        string $summary,
        string $body,
        int $versionno
    ): string {
        return hash('sha256', json_encode([
            'shortname' => $shortname,
            'title' => $title,
            'itemtype' => $itemtype,
            'sourcepath' => $sourcepath,
            'summary' => $summary,
            'body' => $body,
            'versionno' => $versionno,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
     * @param string $identifier String id.
     * @param string $fallback Fallback.
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

