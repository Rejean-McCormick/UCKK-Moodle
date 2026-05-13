<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Decision form for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkassembly\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use context_module;
use moodleform;
use stdClass;

/**
 * Server-side form for creating or editing an Assembly decision.
 *
 * This form collects structured decision data only. Publication, archive
 * export, contestation windows, integrity cases, and minutes updates must be
 * performed by services after capability checks.
 */
final class decision_form extends moodleform {
    /** Decision type: information. */
    private const TYPE_INFORMATION = 'information';

    /** Decision type: recommendation. */
    private const TYPE_RECOMMENDATION = 'recommendation';

    /** Decision type: validation. */
    private const TYPE_VALIDATION = 'validation';

    /** Decision type: correction. */
    private const TYPE_CORRECTION = 'correction';

    /** Decision type: rejection. */
    private const TYPE_REJECTION = 'rejection';

    /** Decision type: archival. */
    private const TYPE_ARCHIVAL = 'archival';

    /** Decision type: integrity. */
    private const TYPE_INTEGRITY = 'integrity';

    /** Status: draft. */
    private const STATUS_DRAFT = 'draft';

    /** Status: pending review. */
    private const STATUS_PENDING_REVIEW = 'pending_review';

    /** Status: validated. */
    private const STATUS_VALIDATED = 'validated';

    /** Status: rejected. */
    private const STATUS_REJECTED = 'rejected';

    /** Status: contested. */
    private const STATUS_CONTESTED = 'contested';

    /** Status: closed. */
    private const STATUS_CLOSED = 'closed';

    /** Status: archived. */
    private const STATUS_ARCHIVED = 'archived';

    /**
     * Define form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $assembly = $this->get_custom_record('assembly');
        $motion = $this->get_custom_record('motion');
        $decision = $this->get_custom_record('decision');
        $context = $this->get_context();

        $this->add_identity_section($mform, $assembly, $motion);
        $this->add_decision_content_section($mform, $context);
        $this->add_vote_and_legitimacy_section($mform);
        $this->add_publication_section($mform);
        $this->add_integrity_section($mform);
        $this->add_archive_section($mform, $context);
        $this->add_hidden_fields($mform, $decision);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'savedraft', get_string('savedraft', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('submit', 'submitreview', get_string('submitdecisionreview', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('submit', 'publishdecision', get_string('publishdecision', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        if (empty($this->_customdata['canpublish'])) {
            $mform->hardFreeze('publishdecision');
        }
    }

    /**
     * Add decision identity fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param stdClass $assembly Assembly record.
     * @param stdClass $motion Motion record.
     */
    private function add_identity_section(\MoodleQuickForm $mform, stdClass $assembly, stdClass $motion): void {
        $mform->addElement('header', 'identityheader', get_string('decisionidentity', 'uckkassembly'));

        if (!empty($assembly->name)) {
            $mform->addElement(
                'static',
                'assemblyname',
                get_string('assembly', 'uckkassembly'),
                format_string($assembly->name)
            );
        }

        if (!empty($motion->title)) {
            $mform->addElement(
                'static',
                'motiontitle',
                get_string('motion', 'uckkassembly'),
                format_string($motion->title)
            );
        }

        $mform->addElement('text', 'title', get_string('decisiontitle', 'uckkassembly'), [
            'size' => 80,
            'maxlength' => 255,
        ]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('select', 'decisiontype', get_string('decisiontype', 'uckkassembly'), $this->get_decision_type_options());
        $mform->setDefault('decisiontype', self::TYPE_RECOMMENDATION);
        $mform->addRule('decisiontype', null, 'required', null, 'client');

        $mform->addElement('select', 'status', get_string('decisionstatus', 'uckkassembly'), $this->get_status_options());
        $mform->setDefault('status', self::STATUS_DRAFT);
        $mform->addRule('status', null, 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkassembly'), $this->get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');
    }

    /**
     * Add main decision content fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param context_module $context Module context.
     */
    private function add_decision_content_section(\MoodleQuickForm $mform, context_module $context): void {
        $mform->addElement('header', 'contentheader', get_string('decisioncontent', 'uckkassembly'));

        $mform->addElement('textarea', 'summary', get_string('decisionsummary', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('summary', PARAM_TEXT);
        $mform->addRule('summary', null, 'required', null, 'client');
        $mform->addHelpButton('summary', 'decisionsummary', 'uckkassembly');

        $mform->addElement(
            'editor',
            'decisiontext_editor',
            get_string('decisiontext', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('decisiontext_editor', PARAM_RAW);
        $mform->addRule('decisiontext_editor', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'rationale_editor',
            get_string('decisionrationale', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('rationale_editor', PARAM_RAW);
        $mform->addHelpButton('rationale_editor', 'decisionrationale', 'uckkassembly');

        $mform->addElement(
            'editor',
            'consequences_editor',
            get_string('decisionconsequences', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('consequences_editor', PARAM_RAW);
        $mform->addHelpButton('consequences_editor', 'decisionconsequences', 'uckkassembly');
    }

    /**
     * Add vote and legitimacy fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_vote_and_legitimacy_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'legitimacyheader', get_string('decisionlegitimacy', 'uckkassembly'));

        $mform->addElement('advcheckbox', 'quorumreached', get_string('quorumreached', 'uckkassembly'));
        $mform->setDefault('quorumreached', 0);

        $mform->addElement('text', 'quorumpercent', get_string('quorumpercent', 'uckkassembly'), [
            'size' => 8,
            'maxlength' => 8,
        ]);
        $mform->setType('quorumpercent', PARAM_FLOAT);

        $mform->addElement('text', 'approvalpercent', get_string('approvalpercent', 'uckkassembly'), [
            'size' => 8,
            'maxlength' => 8,
        ]);
        $mform->setType('approvalpercent', PARAM_FLOAT);

        $mform->addElement('textarea', 'voteresultsummary', get_string('voteresultsummary', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('voteresultsummary', PARAM_RAW);
        $mform->addHelpButton('voteresultsummary', 'voteresultsummary', 'uckkassembly');

        $mform->addElement('advcheckbox', 'minorityreportallowed', get_string('minorityreportallowed', 'uckkassembly'));
        $mform->setDefault('minorityreportallowed', 1);

        $mform->addElement('textarea', 'minorityreportsummary', get_string('minorityreportsummary', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('minorityreportsummary', PARAM_RAW);
    }

    /**
     * Add publication and contestability fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_publication_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'publicationheader', get_string('decisionpublication', 'uckkassembly'));

        $mform->addElement('date_time_selector', 'decisiondate', get_string('decisiondate', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('decisiondate', 0);

        $mform->addElement('date_time_selector', 'timepublished', get_string('timepublished', 'uckkassembly'), [
            'optional' => true,
        ]);
        $mform->setDefault('timepublished', 0);

        $mform->addElement('advcheckbox', 'allowcontest', get_string('allowcontest', 'uckkassembly'));
        $mform->setDefault('allowcontest', 1);
        $mform->addHelpButton('allowcontest', 'allowcontest', 'uckkassembly');

        $mform->addElement('text', 'contestabilitydays', get_string('contestabilitydays', 'uckkassembly'), [
            'size' => 8,
            'maxlength' => 8,
        ]);
        $mform->setType('contestabilitydays', PARAM_INT);
        $mform->setDefault('contestabilitydays', 14);
        $mform->hideIf('contestabilitydays', 'allowcontest', 'notchecked');

        $mform->addElement('advcheckbox', 'publicsummaryenabled', get_string('publicsummaryenabled', 'uckkassembly'));
        $mform->setDefault('publicsummaryenabled', 1);
    }

    /**
     * Add integrity fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_integrity_section(\MoodleQuickForm $mform): void {
        $mform->addElement('header', 'integrityheader', get_string('decisionintegrity', 'uckkassembly'));

        $mform->addElement('advcheckbox', 'integrityrequired', get_string('integrityrequired', 'uckkassembly'));
        $mform->setDefault('integrityrequired', 0);
        $mform->addHelpButton('integrityrequired', 'integrityrequired', 'uckkassembly');

        $mform->addElement('select', 'integritystate', get_string('integritystate', 'uckkassembly'), [
            'unverified' => get_string('integritystate:unverified', 'uckkassembly'),
            'human_reviewed' => get_string('integritystate:humanreviewed', 'uckkassembly'),
            'verified' => get_string('integritystate:verified', 'uckkassembly'),
            'contested' => get_string('integritystate:contested', 'uckkassembly'),
            'invalidated' => get_string('integritystate:invalidated', 'uckkassembly'),
            'archived' => get_string('integritystate:archived', 'uckkassembly'),
        ]);
        $mform->setDefault('integritystate', 'unverified');

        $mform->addElement('textarea', 'integritynotes', get_string('integritynotes', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('integritynotes', PARAM_RAW);
        $mform->addHelpButton('integritynotes', 'integritynotes', 'uckkassembly');

        $mform->addElement('advcheckbox', 'requestintegritycase', get_string('requestintegritycase', 'uckkassembly'));
        $mform->setDefault('requestintegritycase', 0);
        $mform->hideIf('requestintegritycase', 'integrityrequired', 'notchecked');
    }

    /**
     * Add archive and public summary fields.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param context_module $context Module context.
     */
    private function add_archive_section(\MoodleQuickForm $mform, context_module $context): void {
        $mform->addElement('header', 'archiveheader', get_string('decisionarchive', 'uckkassembly'));

        $mform->addElement('select', 'archivepolicy', get_string('archivepolicy', 'uckkassembly'), [
            'none' => get_string('archivepolicy:none', 'uckkassembly'),
            'summary' => get_string('archivepolicy:summary', 'uckkassembly'),
            'full' => get_string('archivepolicy:full', 'uckkassembly'),
            'restricted_integrity' => get_string('archivepolicy:restrictedintegrity', 'uckkassembly'),
        ]);
        $mform->setDefault('archivepolicy', 'summary');

        $mform->addElement(
            'editor',
            'publicsummary_editor',
            get_string('publicsummary', 'uckkassembly'),
            null,
            $this->get_editor_options($context)
        );
        $mform->setType('publicsummary_editor', PARAM_RAW);
        $mform->hideIf('publicsummary_editor', 'publicsummaryenabled', 'notchecked');

        $mform->addElement('textarea', 'archivenotes', get_string('archivenotes', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('archivenotes', PARAM_RAW);
    }

    /**
     * Add hidden identifiers.
     *
     * @param \MoodleQuickForm $mform Form object.
     * @param stdClass $decision Existing decision record.
     */
    private function add_hidden_fields(\MoodleQuickForm $mform, stdClass $decision): void {
        foreach ([
            'id',
            'assemblyid',
            'motionid',
            'decisionid',
            'courseid',
            'cmid',
            'contextid',
            'returnurl',
        ] as $field) {
            $mform->addElement('hidden', $field);
        }

        $mform->setType('id', PARAM_INT);
        $mform->setType('assemblyid', PARAM_INT);
        $mform->setType('motionid', PARAM_INT);
        $mform->setType('decisionid', PARAM_INT);
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('cmid', PARAM_INT);
        $mform->setType('contextid', PARAM_INT);
        $mform->setType('returnurl', PARAM_LOCALURL);

        if (!empty($decision->id)) {
            $mform->setDefault('decisionid', (int)$decision->id);
        }
    }

    /**
     * Validate submitted decision data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (trim((string)($data['title'] ?? '')) === '') {
            $errors['title'] = get_string('required');
        }

        if (trim((string)($data['summary'] ?? '')) === '') {
            $errors['summary'] = get_string('required');
        }

        $decisiontext = trim((string)($data['decisiontext_editor']['text'] ?? ''));
        if ($decisiontext === '') {
            $errors['decisiontext_editor'] = get_string('required');
        }

        if (isset($data['quorumpercent']) && $data['quorumpercent'] !== '') {
            $quorum = (float)$data['quorumpercent'];
            if ($quorum < 0 || $quorum > 100) {
                $errors['quorumpercent'] = get_string('percentmustbeinrange', 'uckkassembly');
            }
        }

        if (isset($data['approvalpercent']) && $data['approvalpercent'] !== '') {
            $approval = (float)$data['approvalpercent'];
            if ($approval < 0 || $approval > 100) {
                $errors['approvalpercent'] = get_string('percentmustbeinrange', 'uckkassembly');
            }
        }

        if (!empty($data['allowcontest'])) {
            $days = (int)($data['contestabilitydays'] ?? 0);
            if ($days < 0) {
                $errors['contestabilitydays'] = get_string('contestabilitydaysinvalid', 'uckkassembly');
            }
        }

        if (!empty($data['timepublished']) && !empty($data['decisiondate']) && $data['timepublished'] < $data['decisiondate']) {
            $errors['timepublished'] = get_string('timepublishedafterdecision', 'uckkassembly');
        }

        if (!empty($data['integrityrequired']) && trim((string)($data['integritynotes'] ?? '')) === '') {
            $errors['integritynotes'] = get_string('integritynotesrequired', 'uckkassembly');
        }

        if (!empty($data['publishdecision']) && empty($data['quorumreached'])) {
            $errors['quorumreached'] = get_string('quorumrequiredtopublish', 'uckkassembly');
        }

        return $errors;
    }

    /**
     * Whether this submission is only a draft save.
     *
     * @return bool
     */
    public function is_draft_save(): bool {
        $data = $this->get_data();

        return $data !== null && !empty($data->savedraft)
            && empty($data->submitreview)
            && empty($data->publishdecision);
    }

    /**
     * Whether this submission requests publication.
     *
     * @return bool
     */
    public function is_publish_request(): bool {
        $data = $this->get_data();

        return $data !== null && !empty($data->publishdecision);
    }

    /**
     * Get module context from customdata.
     *
     * @return context_module
     */
    private function get_context(): context_module {
        if (!empty($this->_customdata['context']) && $this->_customdata['context'] instanceof context_module) {
            return $this->_customdata['context'];
        }

        throw new \coding_exception('decision_form requires a module context in customdata.');
    }

    /**
     * Get custom data record.
     *
     * @param string $key Custom data key.
     * @return stdClass
     */
    private function get_custom_record(string $key): stdClass {
        if (empty($this->_customdata[$key])) {
            return new stdClass();
        }

        return (object)$this->_customdata[$key];
    }

    /**
     * Editor options.
     *
     * @param context_module $context Module context.
     * @return array<string, mixed>
     */
    private function get_editor_options(context_module $context): array {
        return [
            'context' => $context,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => get_max_upload_file_size(),
            'trusttext' => false,
            'noclean' => false,
            'subdirs' => false,
        ];
    }

    /**
     * Decision type options.
     *
     * @return array<string, string>
     */
    private function get_decision_type_options(): array {
        return [
            self::TYPE_INFORMATION => get_string('decisiontype:information', 'uckkassembly'),
            self::TYPE_RECOMMENDATION => get_string('decisiontype:recommendation', 'uckkassembly'),
            self::TYPE_VALIDATION => get_string('decisiontype:validation', 'uckkassembly'),
            self::TYPE_CORRECTION => get_string('decisiontype:correction', 'uckkassembly'),
            self::TYPE_REJECTION => get_string('decisiontype:rejection', 'uckkassembly'),
            self::TYPE_ARCHIVAL => get_string('decisiontype:archival', 'uckkassembly'),
            self::TYPE_INTEGRITY => get_string('decisiontype:integrity', 'uckkassembly'),
        ];
    }

    /**
     * Decision status options.
     *
     * @return array<string, string>
     */
    private function get_status_options(): array {
        return [
            self::STATUS_DRAFT => get_string('status:draft', 'uckkassembly'),
            self::STATUS_PENDING_REVIEW => get_string('status:pendingreview', 'uckkassembly'),
            self::STATUS_VALIDATED => get_string('status:validated', 'uckkassembly'),
            self::STATUS_REJECTED => get_string('status:rejected', 'uckkassembly'),
            self::STATUS_CONTESTED => get_string('status:contested', 'uckkassembly'),
            self::STATUS_CLOSED => get_string('status:closed', 'uckkassembly'),
            self::STATUS_ARCHIVED => get_string('status:archived', 'uckkassembly'),
        ];
    }

    /**
     * Visibility options.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        return [
            'private' => get_string('visibility:private', 'uckkassembly'),
            'course' => get_string('visibility:course', 'uckkassembly'),
            'cohort' => get_string('visibility:cohort', 'uckkassembly'),
            'program' => get_string('visibility:program', 'uckkassembly'),
            'institution' => get_string('visibility:institution', 'uckkassembly'),
            'public' => get_string('visibility:public', 'uckkassembly'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkassembly'),
        ];
    }
}