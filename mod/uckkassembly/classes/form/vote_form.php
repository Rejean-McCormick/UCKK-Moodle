<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Vote or reading form for UCKK assemblies.
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
 * Server-side form for Assembly votes and readings.
 *
 * This form records one participant position on a motion or decision. It does
 * not publish decisions, compute legitimacy, close debates, archive records, or
 * validate integrity. Those actions must stay in capability-checked services.
 */
final class vote_form extends moodleform {
    /** Voting method: readings. */
    private const METHOD_READINGS = 'readings';

    /** Voting method: consent. */
    private const METHOD_CONSENT = 'consent';

    /** Voting method: majority. */
    private const METHOD_MAJORITY = 'majority';

    /** Voting method: supermajority. */
    private const METHOD_SUPERMAJORITY = 'supermajority';

    /** Voting method: consensus. */
    private const METHOD_CONSENSUS = 'consensus';

    /** Voting method: advisory. */
    private const METHOD_ADVISORY = 'advisory';

    /** Vote value: for. */
    private const VOTE_FOR = 'for';

    /** Vote value: against. */
    private const VOTE_AGAINST = 'against';

    /** Vote value: abstain. */
    private const VOTE_ABSTAIN = 'abstain';

    /** Vote value: block. */
    private const VOTE_BLOCK = 'block';

    /** Vote value: reading recorded. */
    private const VOTE_READING = 'reading';

    /** Vote value: consent. */
    private const VOTE_CONSENT = 'consent';

    /** Vote value: needs work. */
    private const VOTE_NEEDSWORK = 'needswork';

    /**
     * Define the vote form.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $assembly = $this->get_custom_record('assembly');
        $motion = $this->get_custom_record('motion');
        $decision = $this->get_custom_record('decision');
        $context = $this->get_context();

        $votingmethod = $this->get_voting_method($assembly);

        $mform->addElement('header', 'voteheader', get_string('vote', 'uckkassembly'));

        if (!empty($assembly->name)) {
            $mform->addElement(
                'static',
                'assemblyname',
                get_string('assemblyname', 'uckkassembly'),
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

        if (!empty($decision->title)) {
            $mform->addElement(
                'static',
                'decisiontitle',
                get_string('decision', 'uckkassembly'),
                format_string($decision->title)
            );
        }

        $mform->addElement(
            'static',
            'votingmethodlabel',
            get_string('votingmethod', 'uckkassembly'),
            get_string('votingmethod:' . $votingmethod, 'uckkassembly')
        );

        $mform->addElement(
            'select',
            'votevalue',
            get_string('votevalue', 'uckkassembly'),
            $this->get_vote_options($votingmethod)
        );
        $mform->addRule('votevalue', null, 'required', null, 'client');
        $mform->setType('votevalue', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('votevalue', 'votevalue', 'uckkassembly');

        $mform->addElement('textarea', 'rationale', get_string('voterationale', 'uckkassembly'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('rationale', PARAM_RAW);
        $mform->addHelpButton('rationale', 'voterationale', 'uckkassembly');

        $mform->addElement('textarea', 'conditions', get_string('voteconditions', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('conditions', PARAM_RAW);
        $mform->addHelpButton('conditions', 'voteconditions', 'uckkassembly');

        $mform->hideIf('conditions', 'votevalue', 'neq', self::VOTE_NEEDSWORK);

        $mform->addElement('textarea', 'blockreason', get_string('blockreason', 'uckkassembly'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('blockreason', PARAM_RAW);
        $mform->addHelpButton('blockreason', 'blockreason', 'uckkassembly');

        $mform->hideIf('blockreason', 'votevalue', 'neq', self::VOTE_BLOCK);

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkassembly'), $this->get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->setType('visibility', PARAM_ALPHANUMEXT);
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('select', 'provenance', get_string('provenance', 'uckkassembly'), $this->get_provenance_options());
        $mform->setDefault('provenance', 'human');
        $mform->setType('provenance', PARAM_ALPHANUMEXT);
        $mform->addRule('provenance', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'acknowledgecontestability', get_string('acknowledgecontestability', 'uckkassembly'));
        $mform->setDefault('acknowledgecontestability', 1);
        $mform->addHelpButton('acknowledgecontestability', 'acknowledgecontestability', 'uckkassembly');

        $this->add_hidden_fields($mform);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('castvote', 'uckkassembly'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Validate submitted vote data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $votevalue = clean_param((string)($data['votevalue'] ?? ''), PARAM_ALPHANUMEXT);
        $rationale = trim((string)($data['rationale'] ?? ''));
        $conditions = trim((string)($data['conditions'] ?? ''));
        $blockreason = trim((string)($data['blockreason'] ?? ''));

        $allowedvalues = array_keys($this->get_vote_options($this->get_voting_method($this->get_custom_record('assembly'))));

        if ($votevalue === '' || !in_array($votevalue, $allowedvalues, true)) {
            $errors['votevalue'] = get_string('invalidvotevalue', 'uckkassembly');
        }

        if (in_array($votevalue, [self::VOTE_AGAINST, self::VOTE_BLOCK, self::VOTE_NEEDSWORK], true) && $rationale === '') {
            $errors['rationale'] = get_string('voterationalerequired', 'uckkassembly');
        }

        if ($votevalue === self::VOTE_BLOCK && $blockreason === '') {
            $errors['blockreason'] = get_string('blockreasonrequired', 'uckkassembly');
        }

        if ($votevalue === self::VOTE_NEEDSWORK && $conditions === '') {
            $errors['conditions'] = get_string('conditionsrequired', 'uckkassembly');
        }

        if (empty($data['acknowledgecontestability'])) {
            $errors['acknowledgecontestability'] = get_string('acknowledgecontestabilityrequired', 'uckkassembly');
        }

        if (empty($data['assemblyid'])) {
            $errors['assemblyid'] = get_string('missingassembly', 'uckkassembly');
        }

        if (empty($data['motionid']) && empty($data['decisionid'])) {
            $errors['motionid'] = get_string('missingvotetarget', 'uckkassembly');
        }

        return $errors;
    }

    /**
     * Prepare default values from an existing vote record.
     *
     * @param stdClass|array $defaultvalues Default values.
     */
    public function data_preprocessing(&$defaultvalues): void {
        $defaults = (array)$defaultvalues;

        $defaults['votevalue'] = $defaults['votevalue'] ?? '';
        $defaults['rationale'] = $defaults['rationale'] ?? '';
        $defaults['conditions'] = $defaults['conditions'] ?? '';
        $defaults['blockreason'] = $defaults['blockreason'] ?? '';
        $defaults['visibility'] = $defaults['visibility'] ?? 'course';
        $defaults['provenance'] = $defaults['provenance'] ?? 'human';
        $defaults['acknowledgecontestability'] = $defaults['acknowledgecontestability'] ?? 1;

        if (is_object($defaultvalues)) {
            foreach ($defaults as $key => $value) {
                $defaultvalues->{$key} = $value;
            }
        } else {
            $defaultvalues = $defaults;
        }
    }

    /**
     * Add hidden identifiers.
     *
     * @param \MoodleQuickForm $mform Form object.
     */
    private function add_hidden_fields(\MoodleQuickForm $mform): void {
        $fields = [
            'id' => PARAM_INT,
            'assemblyid' => PARAM_INT,
            'motionid' => PARAM_INT,
            'decisionid' => PARAM_INT,
            'voteid' => PARAM_INT,
            'userid' => PARAM_INT,
            'groupid' => PARAM_INT,
            'returnurl' => PARAM_LOCALURL,
        ];

        foreach ($fields as $field => $type) {
            $mform->addElement('hidden', $field);
            $mform->setType($field, $type);
        }
    }

    /**
     * Return module context from custom data.
     *
     * @return context_module
     */
    private function get_context(): context_module {
        if (!empty($this->_customdata['context']) && $this->_customdata['context'] instanceof context_module) {
            return $this->_customdata['context'];
        }

        throw new \coding_exception('vote_form requires a module context in customdata.');
    }

    /**
     * Return one custom record.
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
     * Determine the voting method.
     *
     * @param stdClass $assembly Assembly record.
     * @return string
     */
    private function get_voting_method(stdClass $assembly): string {
        $method = clean_param((string)($this->_customdata['votingmethod'] ?? $assembly->votingmethod ?? self::METHOD_READINGS), PARAM_ALPHANUMEXT);

        $allowed = [
            self::METHOD_READINGS,
            self::METHOD_CONSENT,
            self::METHOD_MAJORITY,
            self::METHOD_SUPERMAJORITY,
            self::METHOD_CONSENSUS,
            self::METHOD_ADVISORY,
        ];

        return in_array($method, $allowed, true) ? $method : self::METHOD_READINGS;
    }

    /**
     * Return vote options for the selected voting method.
     *
     * @param string $votingmethod Voting method.
     * @return array<string, string>
     */
    private function get_vote_options(string $votingmethod): array {
        switch ($votingmethod) {
            case self::METHOD_READINGS:
                return [
                    self::VOTE_READING => get_string('vote:reading', 'uckkassembly'),
                    self::VOTE_NEEDSWORK => get_string('vote:needswork', 'uckkassembly'),
                    self::VOTE_BLOCK => get_string('vote:block', 'uckkassembly'),
                ];

            case self::METHOD_CONSENT:
                return [
                    self::VOTE_CONSENT => get_string('vote:consent', 'uckkassembly'),
                    self::VOTE_NEEDSWORK => get_string('vote:needswork', 'uckkassembly'),
                    self::VOTE_BLOCK => get_string('vote:block', 'uckkassembly'),
                    self::VOTE_ABSTAIN => get_string('vote:abstain', 'uckkassembly'),
                ];

            case self::METHOD_CONSENSUS:
                return [
                    self::VOTE_CONSENT => get_string('vote:consent', 'uckkassembly'),
                    self::VOTE_NEEDSWORK => get_string('vote:needswork', 'uckkassembly'),
                    self::VOTE_BLOCK => get_string('vote:block', 'uckkassembly'),
                    self::VOTE_ABSTAIN => get_string('vote:abstain', 'uckkassembly'),
                ];

            case self::METHOD_ADVISORY:
                return [
                    self::VOTE_FOR => get_string('vote:for', 'uckkassembly'),
                    self::VOTE_AGAINST => get_string('vote:against', 'uckkassembly'),
                    self::VOTE_ABSTAIN => get_string('vote:abstain', 'uckkassembly'),
                    self::VOTE_NEEDSWORK => get_string('vote:needswork', 'uckkassembly'),
                ];

            case self::METHOD_MAJORITY:
            case self::METHOD_SUPERMAJORITY:
            default:
                return [
                    self::VOTE_FOR => get_string('vote:for', 'uckkassembly'),
                    self::VOTE_AGAINST => get_string('vote:against', 'uckkassembly'),
                    self::VOTE_ABSTAIN => get_string('vote:abstain', 'uckkassembly'),
                ];
        }
    }

    /**
     * Return visibility options.
     *
     * @return array<string, string>
     */
    private function get_visibility_options(): array {
        return [
            'private' => get_string('visibility:private', 'uckkassembly'),
            'user' => get_string('visibility:user', 'uckkassembly'),
            'group' => get_string('visibility:group', 'uckkassembly'),
            'course' => get_string('visibility:course', 'uckkassembly'),
            'cohort' => get_string('visibility:cohort', 'uckkassembly'),
            'program' => get_string('visibility:program', 'uckkassembly'),
            'institution' => get_string('visibility:institution', 'uckkassembly'),
            'public' => get_string('visibility:public', 'uckkassembly'),
            'restricted' => get_string('visibility:restricted', 'uckkassembly'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkassembly'),
        ];
    }

    /**
     * Return provenance options.
     *
     * @return array<string, string>
     */
    private function get_provenance_options(): array {
        return [
            'human' => get_string('provenance:human', 'uckkassembly'),
            'ai_assisted' => get_string('provenance:ai_assisted', 'uckkassembly'),
            'imported' => get_string('provenance:imported', 'uckkassembly'),
            'system' => get_string('provenance:system', 'uckkassembly'),
        ];
    }
}