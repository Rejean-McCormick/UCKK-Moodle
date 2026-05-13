<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk\local;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use moodle_exception;
use stdClass;

/**
 * Human validation guard for UCKK AI outputs.
 *
 * UCKK AI may assist with drafting, summarising, mapping, uncertainty
 * extraction and comparison. It must never become final authority for grades,
 * integrity, archives, badges, competencies, assembly decisions, sanctions, or
 * evidence erasure.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class human_validation {
    /** AI output is only a draft. */
    public const STATUS_DRAFT = 'draft';

    /** AI output is awaiting human review. */
    public const STATUS_AWAITING_REVIEW = 'awaiting_review';

    /** Human reviewer accepted the AI-assisted output. */
    public const STATUS_VALIDATED = 'validated';

    /** Human reviewer rejected the AI-assisted output. */
    public const STATUS_REJECTED = 'rejected';

    /** Human reviewer requested correction. */
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';

    /** Human reviewer escalated the output to integrity review. */
    public const STATUS_ESCALATED = 'escalated';

    /** Human decision: accept. */
    public const DECISION_ACCEPT = 'accept';

    /** Human decision: reject. */
    public const DECISION_REJECT = 'reject';

    /** Human decision: request correction. */
    public const DECISION_CORRECT = 'correct';

    /** Human decision: escalate. */
    public const DECISION_ESCALATE = 'escalate';

    /** Assistive AI action: summarize course material. */
    public const ACTION_SUMMARISE_COURSE_MATERIAL = 'summarise_course_material';

    /** Assistive AI action: map a problem. */
    public const ACTION_MAP_PROBLEM = 'map_problem';

    /** Assistive AI action: extract uncertainties. */
    public const ACTION_EXTRACT_UNCERTAINTIES = 'extract_uncertainties';

    /** Assistive AI action: draft reflection. */
    public const ACTION_DRAFT_REFLECTION = 'draft_reflection';

    /** Assistive AI action: summarize assembly. */
    public const ACTION_SUMMARISE_ASSEMBLY = 'summarise_assembly';

    /** Assistive AI action: critique AI output. */
    public const ACTION_CRITIQUE_AI_OUTPUT = 'critique_ai_output';

    /** Assistive AI action: prepare integrity review. */
    public const ACTION_PREPARE_INTEGRITY_REVIEW = 'prepare_integrity_review';

    /** Forbidden final-authority action: grade final work. */
    public const FINAL_GRADE = 'grade_final_work';

    /** Forbidden final-authority action: validate integrity. */
    public const FINAL_VALIDATE_INTEGRITY = 'validate_integrity';

    /** Forbidden final-authority action: close integrity case. */
    public const FINAL_CLOSE_INTEGRITY_CASE = 'close_integrity_case';

    /** Forbidden final-authority action: publish assembly decision. */
    public const FINAL_PUBLISH_ASSEMBLY_DECISION = 'publish_assembly_decision';

    /** Forbidden final-authority action: award badge. */
    public const FINAL_AWARD_BADGE = 'award_badge';

    /** Forbidden final-authority action: certify competency. */
    public const FINAL_CERTIFY_COMPETENCY = 'certify_competency';

    /** Forbidden final-authority action: validate archive item. */
    public const FINAL_VALIDATE_ARCHIVE_ITEM = 'validate_archive_item';

    /** Forbidden final-authority action: erase evidence. */
    public const FINAL_ERASE_EVIDENCE = 'erase_evidence';

    /**
     * Return allowed assistive AI actions.
     *
     * @return string[]
     */
    public static function assistive_actions(): array {
        return [
            self::ACTION_SUMMARISE_COURSE_MATERIAL,
            self::ACTION_MAP_PROBLEM,
            self::ACTION_EXTRACT_UNCERTAINTIES,
            self::ACTION_DRAFT_REFLECTION,
            self::ACTION_SUMMARISE_ASSEMBLY,
            self::ACTION_CRITIQUE_AI_OUTPUT,
            self::ACTION_PREPARE_INTEGRITY_REVIEW,
        ];
    }

    /**
     * Return actions AI must never perform as final authority.
     *
     * @return string[]
     */
    public static function final_authority_actions(): array {
        return [
            self::FINAL_GRADE,
            self::FINAL_VALIDATE_INTEGRITY,
            self::FINAL_CLOSE_INTEGRITY_CASE,
            self::FINAL_PUBLISH_ASSEMBLY_DECISION,
            self::FINAL_AWARD_BADGE,
            self::FINAL_CERTIFY_COMPETENCY,
            self::FINAL_VALIDATE_ARCHIVE_ITEM,
            self::FINAL_ERASE_EVIDENCE,
        ];
    }

    /**
     * Return valid human review decisions.
     *
     * @return string[]
     */
    public static function decisions(): array {
        return [
            self::DECISION_ACCEPT,
            self::DECISION_REJECT,
            self::DECISION_CORRECT,
            self::DECISION_ESCALATE,
        ];
    }

    /**
     * Return valid validation statuses.
     *
     * @return string[]
     */
    public static function statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_AWAITING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_ESCALATED,
        ];
    }

    /**
     * Check whether an AI action is assistive and allowed.
     *
     * @param string $action AI action.
     * @return bool
     */
    public static function is_assistive_action(string $action): bool {
        return in_array($action, self::assistive_actions(), true);
    }

    /**
     * Check whether an action is forbidden final authority.
     *
     * @param string $action Action key.
     * @return bool
     */
    public static function is_final_authority_action(string $action): bool {
        return in_array($action, self::final_authority_actions(), true);
    }

    /**
     * Require that the user can request AI assistance in this context.
     *
     * @param context $context Moodle context.
     * @return void
     */
    public static function require_can_use_ai(context $context): void {
        require_capability('aiprovider/uckk:use', $context);
    }

    /**
     * Require that the user can review or validate an AI-assisted result.
     *
     * Site managers/configurers and AI log reviewers may review AI traces.
     * Domain-specific final actions must still be validated by their owning
     * plugin capabilities before they are applied.
     *
     * @param context $context Moodle context.
     * @return void
     */
    public static function require_can_validate(context $context): void {
        if (
            has_capability('aiprovider/uckk:configure', $context) ||
            has_capability('aiprovider/uckk:viewlogs', $context)
        ) {
            return;
        }

        require_capability('aiprovider/uckk:configure', $context);
    }

    /**
     * Validate that an AI action is permitted as assistance.
     *
     * @param string $action AI action.
     * @param context|null $context Moodle context.
     * @return void
     */
    public static function require_assistive_action(string $action, ?context $context = null): void {
        $action = self::normalize_key($action);

        if (self::is_final_authority_action($action)) {
            throw new moodle_exception(
                'error:finalauthorityaction',
                'aiprovider_uckk',
                '',
                $action
            );
        }

        if (!self::is_assistive_action($action)) {
            throw new moodle_exception(
                'error:unknownaiaction',
                'aiprovider_uckk',
                '',
                $action
            );
        }

        if ($context !== null) {
            self::require_can_use_ai($context);
        }
    }

    /**
     * Enforce restricted-context settings before an AI request is sent.
     *
     * @param string $action AI action.
     * @param context $context Moodle context.
     * @param string $origincomponent Originating component.
     * @return void
     */
    public static function require_context_allowed(
        string $action,
        context $context,
        string $origincomponent = ''
    ): void {
        self::require_assistive_action($action, $context);

        $origincomponent = clean_param($origincomponent, PARAM_COMPONENT);

        if (
            $origincomponent === 'tool_uckkintegrity' &&
            !get_config('aiprovider_uckk', 'allow_in_integrity_contexts')
        ) {
            throw new moodle_exception('error:aiintegritydisabled', 'aiprovider_uckk');
        }

        if (
            $origincomponent === 'mod_uckkchallenge' &&
            !get_config('aiprovider_uckk', 'allow_in_public_challenges')
        ) {
            throw new moodle_exception('error:aipublicchallengedisabled', 'aiprovider_uckk');
        }

        if (
            self::is_restricted_context($context) &&
            !get_config('aiprovider_uckk', 'allow_in_integrity_contexts')
        ) {
            throw new moodle_exception('error:airestrictedcontextdisabled', 'aiprovider_uckk');
        }
    }

    /**
     * Whether the action requires later human validation before any final use.
     *
     * Assistive actions can always produce drafts, but final institutional use
     * requires human validation.
     *
     * @param string $action AI action.
     * @param string|null $targetpurpose Intended final purpose.
     * @return bool
     */
    public static function requires_human_validation(string $action, ?string $targetpurpose = null): bool {
        $action = self::normalize_key($action);
        $targetpurpose = $targetpurpose === null ? '' : self::normalize_key($targetpurpose);

        if (self::is_final_authority_action($action) || self::is_final_authority_action($targetpurpose)) {
            return true;
        }

        return in_array($action, [
            self::ACTION_DRAFT_REFLECTION,
            self::ACTION_SUMMARISE_ASSEMBLY,
            self::ACTION_PREPARE_INTEGRITY_REVIEW,
            self::ACTION_CRITIQUE_AI_OUTPUT,
        ], true);
    }

    /**
     * Attach the mandatory non-authority label to an AI output.
     *
     * @param string $text AI response text.
     * @param string $lang Language code.
     * @return string
     */
    public static function label_output(string $text, string $lang = 'en'): string {
        $label = self::output_label($lang);
        $text = trim($text);

        if ($text === '') {
            return $label;
        }

        if (str_contains($text, $label)) {
            return $text;
        }

        return $label . "\n\n" . $text;
    }

    /**
     * Return the mandatory non-authority label.
     *
     * @param string $lang Language code.
     * @return string
     */
    public static function output_label(string $lang = 'en'): string {
        if (str_starts_with(strtolower($lang), 'fr')) {
            return 'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.';
        }

        return 'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';
    }

    /**
     * Create a normalized AI output record requiring human review.
     *
     * This method does not persist data. Persistence belongs to the AI request
     * or log service so privacy settings can control whether prompts/responses
     * are stored.
     *
     * @param string $action AI action.
     * @param string $prompt Prompt text.
     * @param string $response AI response text.
     * @param context|null $context Moodle context.
     * @param string $origincomponent Origin component.
     * @param int $originitemid Origin item ID.
     * @param string|null $targetpurpose Intended final purpose.
     * @return stdClass
     */
    public static function prepare_review_record(
        string $action,
        string $prompt,
        string $response,
        ?context $context = null,
        string $origincomponent = '',
        int $originitemid = 0,
        ?string $targetpurpose = null
    ): stdClass {
        $action = self::normalize_key($action);
        self::require_assistive_action($action, $context);

        $record = new stdClass();
        $record->action = $action;
        $record->origincomponent = clean_param($origincomponent, PARAM_COMPONENT);
        $record->originitemid = max(0, $originitemid);
        $record->contextid = $context ? $context->id : context_system::instance()->id;
        $record->targetpurpose = $targetpurpose === null ? '' : self::normalize_key($targetpurpose);
        $record->prompt = clean_text($prompt, FORMAT_PLAIN);
        $record->response = self::label_output(clean_text($response, FORMAT_PLAIN));
        $record->validationstatus = self::requires_human_validation($action, $targetpurpose)
            ? self::STATUS_AWAITING_REVIEW
            : self::STATUS_DRAFT;
        $record->humanvalidated = 0;
        $record->humanvalidatorid = null;
        $record->validationdecision = null;
        $record->validationnotes = null;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $record->timevalidated = null;

        return $record;
    }

    /**
     * Apply a human validation decision to a review record.
     *
     * @param stdClass $record AI output or log record.
     * @param string $decision Human validation decision.
     * @param string $notes Reviewer notes.
     * @param int|null $validatorid Validator user ID. Defaults to current user.
     * @param context|null $context Moodle context for capability check.
     * @return stdClass Updated record.
     */
    public static function apply_validation(
        stdClass $record,
        string $decision,
        string $notes = '',
        ?int $validatorid = null,
        ?context $context = null
    ): stdClass {
        global $USER;

        $context = $context ?? context_system::instance();
        self::require_can_validate($context);

        $decision = self::normalize_key($decision);
        if (!in_array($decision, self::decisions(), true)) {
            throw new moodle_exception('error:invalidvalidationdecision', 'aiprovider_uckk', '', $decision);
        }

        $status = match ($decision) {
            self::DECISION_ACCEPT => self::STATUS_VALIDATED,
            self::DECISION_REJECT => self::STATUS_REJECTED,
            self::DECISION_CORRECT => self::STATUS_CORRECTION_REQUIRED,
            self::DECISION_ESCALATE => self::STATUS_ESCALATED,
            default => self::STATUS_AWAITING_REVIEW,
        };

        $record->validationstatus = $status;
        $record->humanvalidated = $status === self::STATUS_VALIDATED ? 1 : 0;
        $record->humanvalidatorid = $validatorid ?? (int)$USER->id;
        $record->validationdecision = $decision;
        $record->validationnotes = clean_text($notes, FORMAT_PLAIN);
        $record->timemodified = time();
        $record->timevalidated = $record->timemodified;

        return $record;
    }

    /**
     * Require that a record is human-validated before final institutional use.
     *
     * @param stdClass $record AI output or log record.
     * @param string $purpose Intended final purpose.
     * @return void
     */
    public static function require_validated_for_final_use(stdClass $record, string $purpose): void {
        $purpose = self::normalize_key($purpose);

        if (!self::is_final_authority_action($purpose)) {
            return;
        }

        if (
            empty($record->humanvalidated) ||
            empty($record->validationstatus) ||
            $record->validationstatus !== self::STATUS_VALIDATED
        ) {
            throw new moodle_exception('error:humanvalidationrequired', 'aiprovider_uckk', '', $purpose);
        }
    }

    /**
     * Return whether a record has been accepted by a human reviewer.
     *
     * @param stdClass $record AI output or log record.
     * @return bool
     */
    public static function is_human_validated(stdClass $record): bool {
        return !empty($record->humanvalidated)
            && !empty($record->validationstatus)
            && $record->validationstatus === self::STATUS_VALIDATED;
    }

    /**
     * Return a compact validation summary for UI/export use.
     *
     * @param stdClass $record AI output or log record.
     * @return array<string,scalar|null>
     */
    public static function summary(stdClass $record): array {
        return [
            'action' => $record->action ?? '',
            'targetpurpose' => $record->targetpurpose ?? '',
            'validationstatus' => $record->validationstatus ?? self::STATUS_DRAFT,
            'humanvalidated' => !empty($record->humanvalidated) ? 1 : 0,
            'humanvalidatorid' => $record->humanvalidatorid ?? null,
            'validationdecision' => $record->validationdecision ?? null,
            'timevalidated' => $record->timevalidated ?? null,
        ];
    }

    /**
     * Detect restricted context types where AI use must be guarded by settings.
     *
     * @param context $context Moodle context.
     * @return bool
     */
    private static function is_restricted_context(context $context): bool {
        return in_array($context->contextlevel, [CONTEXT_MODULE, CONTEXT_COURSE, CONTEXT_USER], true);
    }

    /**
     * Normalize a machine key.
     *
     * @param string $key Input key.
     * @return string
     */
    private static function normalize_key(string $key): string {
        return clean_param(core_text::strtolower(trim($key)), PARAM_ALPHANUMEXT);
    }
}