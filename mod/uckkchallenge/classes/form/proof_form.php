<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Proof submission form for UCKK challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Proof form for Défis King Klown.
 *
 * This form collects evidence and provenance metadata only. It does not grade,
 * validate integrity, award badges, certify competencies, or archive records.
 */
final class proof_form extends \moodleform {
    /**
     * Define the form.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $context = $customdata['context'];
        $challenge = $customdata['challenge'] ?? null;
        $draftitemid = (int)($customdata['draftitemid'] ?? 0);
        $submissionid = (int)($customdata['submissionid'] ?? 0);
        $cmid = (int)($customdata['cmid'] ?? 0);
        $showdraftbutton = (bool)($customdata['showdraftbutton'] ?? true);

        $mform->addElement('header', 'proofheader', get_string('proofsubmission', 'uckkchallenge'));

        if ($challenge !== null && !empty($challenge->name)) {
            $mform->addElement(
                'static',
                'challengename',
                get_string('challenge', 'uckkchallenge'),
                format_string($challenge->name)
            );
        }

        $mform->addElement('select', 'prooftype', get_string('prooftype', 'uckkchallenge'), self::get_proof_type_options());
        $mform->setDefault('prooftype', 'text');
        $mform->addRule('prooftype', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'prooftext_editor',
            get_string('prooftext', 'uckkchallenge'),
            null,
            self::get_editor_options($context)
        );
        $mform->setType('prooftext_editor', PARAM_RAW);
        $mform->addHelpButton('prooftext_editor', 'prooftext', 'uckkchallenge');

        $mform->addElement('url', 'proofurl', get_string('proofurl', 'uckkchallenge'), [
            'size' => 80,
        ]);
        $mform->setType('proofurl', PARAM_URL);
        $mform->addHelpButton('proofurl', 'proofurl', 'uckkchallenge');

        $mform->addElement(
            'filemanager',
            'prooffiles',
            get_string('prooffiles', 'uckkchallenge'),
            null,
            self::get_filemanager_options($context)
        );
        $mform->setDefault('prooffiles', $draftitemid);
        $mform->addHelpButton('prooffiles', 'prooffiles', 'uckkchallenge');

        $mform->addElement('textarea', 'relationtocriteria', get_string('relationtocriteria', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('relationtocriteria', PARAM_RAW);
        $mform->addRule('relationtocriteria', null, 'required', null, 'client');
        $mform->addHelpButton('relationtocriteria', 'relationtocriteria', 'uckkchallenge');

        $mform->addElement('textarea', 'provenancestatement', get_string('provenancestatement', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('provenancestatement', PARAM_RAW);
        $mform->addRule('provenancestatement', null, 'required', null, 'client');
        $mform->addHelpButton('provenancestatement', 'provenancestatement', 'uckkchallenge');

        $mform->addElement('text', 'sourceauthor', get_string('sourceauthor', 'uckkchallenge'), [
            'size' => 80,
            'maxlength' => 255,
        ]);
        $mform->setType('sourceauthor', PARAM_TEXT);
        $mform->addHelpButton('sourceauthor', 'sourceauthor', 'uckkchallenge');

        $mform->addElement('date_time_selector', 'sourcedate', get_string('sourcedate', 'uckkchallenge'), [
            'optional' => true,
        ]);
        $mform->setDefault('sourcedate', 0);

        $mform->addElement('select', 'visibility', get_string('visibility', 'uckkchallenge'), self::get_visibility_options());
        $mform->setDefault('visibility', 'course');
        $mform->addRule('visibility', null, 'required', null, 'client');

        $mform->addElement('select', 'integritystate', get_string('integritystate', 'uckkchallenge'), self::get_integrity_state_options());
        $mform->setDefault('integritystate', 'unverified');
        $mform->freeze('integritystate');

        $mform->addElement('advcheckbox', 'aiassisted', get_string('aiassisted', 'uckkchallenge'));
        $mform->setDefault('aiassisted', 0);
        $mform->addHelpButton('aiassisted', 'aiassisted', 'uckkchallenge');

        $mform->addElement('textarea', 'ailog', get_string('ailog', 'uckkchallenge'), [
            'rows' => 5,
            'cols' => 80,
        ]);
        $mform->setType('ailog', PARAM_RAW);
        $mform->hideIf('ailog', 'aiassisted', 'notchecked');
        $mform->addHelpButton('ailog', 'ailog', 'uckkchallenge');

        $mform->addElement('textarea', 'uncertaintynotes', get_string('uncertaintynotes', 'uckkchallenge'), [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('uncertaintynotes', PARAM_RAW);
        $mform->addHelpButton('uncertaintynotes', 'uncertaintynotes', 'uckkchallenge');

        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'submissionid', $submissionid);
        $mform->setType('submissionid', PARAM_INT);

        $mform->addElement('hidden', 'draftitemid', $draftitemid);
        $mform->setType('draftitemid', PARAM_INT);

        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'submitbutton', get_string('submitproof', 'uckkchallenge'));

        if ($showdraftbutton) {
            $buttons[] = $mform->createElement('submit', 'savedraft', get_string('savedraft'));
        }

        $buttons[] = $mform->createElement('cancel');

        $mform->addGroup($buttons, 'buttonar', '', [' '], false);
    }

    /**
     * Validate submitted form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $prooftext = '';
        if (!empty($data['prooftext_editor']['text'])) {
            $prooftext = trim((string)$data['prooftext_editor']['text']);
        }

        $proofurl = trim((string)($data['proofurl'] ?? ''));
        $draftitemid = (int)($data['prooffiles'] ?? 0);
        $hasfiles = $draftitemid > 0 && self::draft_area_has_files($draftitemid);

        if ($prooftext === '' && $proofurl === '' && !$hasfiles) {
            $errors['prooftext_editor'] = get_string('proofrequirescontent', 'uckkchallenge');
        }

        if (trim((string)($data['relationtocriteria'] ?? '')) === '') {
            $errors['relationtocriteria'] = get_string('required');
        }

        if (trim((string)($data['provenancestatement'] ?? '')) === '') {
            $errors['provenancestatement'] = get_string('required');
        }

        if (!empty($data['aiassisted']) && trim((string)($data['ailog'] ?? '')) === '') {
            $errors['ailog'] = get_string('ailogrequired', 'uckkchallenge');
        }

        $visibility = (string)($data['visibility'] ?? '');
        if (!array_key_exists($visibility, self::get_visibility_options())) {
            $errors['visibility'] = get_string('invalidvisibility', 'uckkchallenge');
        }

        $prooftype = (string)($data['prooftype'] ?? '');
        if (!array_key_exists($prooftype, self::get_proof_type_options())) {
            $errors['prooftype'] = get_string('invalidprooftype', 'uckkchallenge');
        }

        return $errors;
    }

    /**
     * Check whether a draft item area contains user files.
     *
     * @param int $draftitemid Draft item id.
     * @return bool
     */
    private static function draft_area_has_files(int $draftitemid): bool {
        global $USER;

        if ($draftitemid <= 0) {
            return false;
        }

        $fs = get_file_storage();
        $usercontext = \context_user::instance((int)$USER->id);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

        return !empty($files);
    }

    /**
     * Editor options for proof text.
     *
     * @param \context_module $context Module context.
     * @return array<string, mixed>
     */
    public static function get_editor_options(\context_module $context): array {
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
     * File manager options for proof files.
     *
     * @param \context_module $context Module context.
     * @return array<string, mixed>
     */
    public static function get_filemanager_options(\context_module $context): array {
        return [
            'context' => $context,
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size(),
            'maxfiles' => 20,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        ];
    }

    /**
     * Proof type options.
     *
     * @return array<string, string>
     */
    public static function get_proof_type_options(): array {
        return [
            'text' => get_string('prooftype:text', 'uckkchallenge'),
            'file' => get_string('prooftype:file', 'uckkchallenge'),
            'url' => get_string('prooftype:url', 'uckkchallenge'),
            'dataset' => get_string('prooftype:dataset', 'uckkchallenge'),
            'image' => get_string('prooftype:image', 'uckkchallenge'),
            'video' => get_string('prooftype:video', 'uckkchallenge'),
            'testimony' => get_string('prooftype:testimony', 'uckkchallenge'),
            'observation' => get_string('prooftype:observation', 'uckkchallenge'),
            'ai_log' => get_string('prooftype:ai_log', 'uckkchallenge'),
            'decision_record' => get_string('prooftype:decision_record', 'uckkchallenge'),
        ];
    }

    /**
     * Visibility options.
     *
     * @return array<string, string>
     */
    public static function get_visibility_options(): array {
        return [
            'private' => get_string('visibility:private', 'uckkchallenge'),
            'user' => get_string('visibility:user', 'uckkchallenge'),
            'group' => get_string('visibility:group', 'uckkchallenge'),
            'course' => get_string('visibility:course', 'uckkchallenge'),
            'cohort' => get_string('visibility:cohort', 'uckkchallenge'),
            'program' => get_string('visibility:program', 'uckkchallenge'),
            'institution' => get_string('visibility:institution', 'uckkchallenge'),
            'public' => get_string('visibility:public', 'uckkchallenge'),
            'restricted' => get_string('visibility:restricted', 'uckkchallenge'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkchallenge'),
        ];
    }

    /**
     * Integrity state options available to learners.
     *
     * Learner-submitted proof starts unverified. Human review updates this later.
     *
     * @return array<string, string>
     */
    public static function get_integrity_state_options(): array {
        return [
            'unverified' => get_string('integritystate:unverified', 'uckkchallenge'),
        ];
    }
}