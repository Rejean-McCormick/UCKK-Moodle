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
 * UCKK player profile form.
 *
 * This form edits the UCKK-specific player profile layer:
 * display title, symbolic roles, active pathways, portfolio archive link,
 * visibility and profile notes.
 *
 * It must not modify Moodle core user profile fields, assign Moodle roles,
 * award badges, validate competencies, enrol users, close integrity cases,
 * publish archive records or alter course membership.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Player profile form.
 *
 * Expected customdata:
 *
 * ```php
 * [
 *     'context' => context_user::instance($userid),
 *     'targetuser' => $userrecord,
 *     'profile' => $profilerecord,
 *     'pathways' => [
 *         1 => 'Tronc commun obligatoire',
 *         2 => 'Baccalauréat du Grand Jeu social',
 *     ],
 *     'canmanage' => true,
 *     'returnurl' => new moodle_url('/local/uckk/profile.php', ['userid' => $userid]),
 * ]
 * ```
 *
 * @package local_uckk
 */
final class player_profile_form extends \moodleform {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Maximum display title length. */
    private const DISPLAY_TITLE_MAX_LENGTH = 255;

    /** Maximum profile note length. */
    private const PROFILE_NOTE_MAX_LENGTH = 2000;

    /**
     * Define form elements.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $customdata = $this->_customdata ?? [];
        $targetuser = $customdata['targetuser'] ?? null;
        $profile = $customdata['profile'] ?? null;
        $canmanage = !empty($customdata['canmanage']);

        $userid = $this->get_target_userid($targetuser, $profile);
        $profileid = isset($profile->id) ? (int)$profile->id : 0;
        $returnurl = $this->get_return_url();

        // Hidden technical fields.
        $mform->addElement('hidden', 'id', $profileid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl', $returnurl);
        $mform->setType('returnurl', PARAM_LOCALURL);

        // Identity header.
        $mform->addElement(
            'header',
            'identityhdr',
            self::string('playerprofile:identityhdr', 'Identité UCKK')
        );

        if ($targetuser !== null && !empty($targetuser->id)) {
            $mform->addElement(
                'static',
                'targetuserdisplay',
                self::string('playerprofile:user', 'Utilisateur Moodle'),
                fullname($targetuser)
            );
        }

        $mform->addElement(
            'text',
            'displaytitle',
            self::string('playerprofile:displaytitle', 'Titre public UCKK'),
            [
                'maxlength' => self::DISPLAY_TITLE_MAX_LENGTH,
                'size' => 64,
            ]
        );
        $mform->setType('displaytitle', PARAM_TEXT);
        $mform->addRule(
            'displaytitle',
            get_string('maximumchars', '', self::DISPLAY_TITLE_MAX_LENGTH),
            'maxlength',
            self::DISPLAY_TITLE_MAX_LENGTH,
            'client'
        );
        $mform->addHelpButton('displaytitle', 'playerprofile:displaytitle', self::COMPONENT);

        $mform->addElement(
            'select',
            'visibility',
            self::string('playerprofile:visibility', 'Visibilité du profil'),
            self::get_visibility_options()
        );
        $mform->setType('visibility', PARAM_ALPHANUMEXT);
        $mform->setDefault('visibility', 'private');
        $mform->addHelpButton('visibility', 'playerprofile:visibility', self::COMPONENT);

        // Symbolic layer.
        $mform->addElement(
            'header',
            'symbolichdr',
            self::string('playerprofile:symbolichdr', 'Rôles symboliques')
        );

        $mform->addElement(
            'autocomplete',
            'symbolicroles',
            self::string('playerprofile:symbolicroles', 'Rôles symboliques UCKK'),
            self::get_symbolic_role_options(),
            [
                'multiple' => true,
                'noselectionstring' => self::string(
                    'playerprofile:nosymbolicroles',
                    'Aucun rôle symbolique sélectionné'
                ),
            ]
        );
        $mform->setType('symbolicroles', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('symbolicroles', 'playerprofile:symbolicroles', self::COMPONENT);

        $mform->addElement(
            'static',
            'symbolicrolesnotice',
            '',
            self::string(
                'playerprofile:symbolicrolesnotice',
                'Les rôles symboliques UCKK décrivent une identité pédagogique ou narrative. Ils ne donnent pas automatiquement des permissions Moodle.'
            )
        );

        // Pathways.
        $mform->addElement(
            'header',
            'pathwayhdr',
            self::string('playerprofile:pathwayhdr', 'Parcours UCKK')
        );

        $mform->addElement(
            'autocomplete',
            'activepathwayids',
            self::string('playerprofile:activepathways', 'Parcours actifs'),
            $this->get_pathway_options(),
            [
                'multiple' => true,
                'noselectionstring' => self::string(
                    'playerprofile:noactivepathways',
                    'Aucun parcours actif'
                ),
            ]
        );
        $mform->setType('activepathwayids', PARAM_INT);
        $mform->addHelpButton('activepathwayids', 'playerprofile:activepathways', self::COMPONENT);

        // Portfolio and archive.
        $mform->addElement(
            'header',
            'portfoliohdr',
            self::string('playerprofile:portfoliohdr', 'Portfolio et archive')
        );

        $mform->addElement(
            'text',
            'portfolioarchiveid',
            self::string('playerprofile:portfolioarchiveid', 'Identifiant d’archive du portfolio'),
            [
                'maxlength' => 10,
                'size' => 12,
            ]
        );
        $mform->setType('portfolioarchiveid', PARAM_INT);
        $mform->setDefault('portfolioarchiveid', 0);
        $mform->addHelpButton('portfolioarchiveid', 'playerprofile:portfolioarchiveid', self::COMPONENT);

        $mform->addElement(
            'textarea',
            'profilenote',
            self::string('playerprofile:profilenote', 'Note de profil UCKK'),
            [
                'rows' => 6,
                'cols' => 80,
                'maxlength' => self::PROFILE_NOTE_MAX_LENGTH,
            ]
        );
        $mform->setType('profilenote', PARAM_TEXT);
        $mform->addRule(
            'profilenote',
            get_string('maximumchars', '', self::PROFILE_NOTE_MAX_LENGTH),
            'maxlength',
            self::PROFILE_NOTE_MAX_LENGTH,
            'client'
        );
        $mform->addHelpButton('profilenote', 'playerprofile:profilenote', self::COMPONENT);

        // Governance metadata.
        $mform->addElement(
            'header',
            'governancehdr',
            self::string('playerprofile:governancehdr', 'Gouvernance du profil')
        );

        $mform->addElement(
            'select',
            'profileorigin',
            self::string('playerprofile:profileorigin', 'Origine de la mise à jour'),
            self::get_profile_origin_options()
        );
        $mform->setType('profileorigin', PARAM_ALPHANUMEXT);
        $mform->setDefault('profileorigin', 'profile_form');

        $mform->addElement(
            'advcheckbox',
            'requiresreview',
            self::string('playerprofile:requiresreview', 'Demander une révision'),
            self::string(
                'playerprofile:requiresreview_desc',
                'Signaler ce profil pour une révision méthodologique ou d’intégrité.'
            ),
            [],
            [0, 1]
        );
        $mform->setType('requiresreview', PARAM_BOOL);

        if (!$canmanage) {
            $mform->hardFreeze('profileorigin');
            $mform->hardFreeze('requiresreview');
        }

        $mform->addElement(
            'static',
            'integritynotice',
            self::string('playerprofile:integritynotice', 'Rappel d’intégrité'),
            self::string(
                'playerprofile:integritynotice_text',
                'Le profil UCKK doit rester vérifiable, non trompeur et distinct des permissions techniques Moodle.'
            )
        );

        $this->add_action_buttons(
            true,
            self::string('playerprofile:save', 'Enregistrer le profil UCKK')
        );

        $this->apply_defaults_from_profile($profile, $userid);
    }

    /**
     * Validate form data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array<string, string>
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $userid = isset($data['userid']) ? (int)$data['userid'] : 0;
        if ($userid <= 0) {
            $errors['userid'] = self::string(
                'playerprofile:error:missinguserid',
                'Le profil doit être associé à un utilisateur Moodle valide.'
            );
        }

        $displaytitle = trim((string)($data['displaytitle'] ?? ''));
        if ($displaytitle !== '' && \core_text::strlen($displaytitle) > self::DISPLAY_TITLE_MAX_LENGTH) {
            $errors['displaytitle'] = get_string('maximumchars', '', self::DISPLAY_TITLE_MAX_LENGTH);
        }

        $visibility = (string)($data['visibility'] ?? '');
        if (!array_key_exists($visibility, self::get_visibility_options())) {
            $errors['visibility'] = self::string(
                'playerprofile:error:invalidvisibility',
                'La visibilité sélectionnée n’est pas valide.'
            );
        }

        $symbolicroles = $this->normalise_array_value($data['symbolicroles'] ?? []);
        $allowedroles = self::get_symbolic_role_options();
        foreach ($symbolicroles as $role) {
            if (!array_key_exists($role, $allowedroles)) {
                $errors['symbolicroles'] = self::string(
                    'playerprofile:error:invalidsymbolicrole',
                    'Un des rôles symboliques sélectionnés n’est pas valide.'
                );
                break;
            }
        }

        $activepathwayids = $this->normalise_array_value($data['activepathwayids'] ?? []);
        $allowedpathways = $this->get_pathway_options();
        foreach ($activepathwayids as $pathwayid) {
            if (!ctype_digit((string)$pathwayid) || !array_key_exists((int)$pathwayid, $allowedpathways)) {
                $errors['activepathwayids'] = self::string(
                    'playerprofile:error:invalidpathway',
                    'Un des parcours sélectionnés n’est pas valide.'
                );
                break;
            }
        }

        $portfolioarchiveid = isset($data['portfolioarchiveid']) ? (int)$data['portfolioarchiveid'] : 0;
        if ($portfolioarchiveid < 0) {
            $errors['portfolioarchiveid'] = self::string(
                'playerprofile:error:invalidportfolioarchiveid',
                'L’identifiant d’archive du portfolio doit être un nombre positif.'
            );
        }

        $profilenote = trim((string)($data['profilenote'] ?? ''));
        if ($profilenote !== '' && \core_text::strlen($profilenote) > self::PROFILE_NOTE_MAX_LENGTH) {
            $errors['profilenote'] = get_string('maximumchars', '', self::PROFILE_NOTE_MAX_LENGTH);
        }

        $profileorigin = (string)($data['profileorigin'] ?? '');
        if (!array_key_exists($profileorigin, self::get_profile_origin_options())) {
            $errors['profileorigin'] = self::string(
                'playerprofile:error:invalidorigin',
                'L’origine de mise à jour sélectionnée n’est pas valide.'
            );
        }

        return $errors;
    }

    /**
     * Return cleaned data ready for storage in local_uckk_player.
     *
     * Call this after get_data().
     *
     * @param \stdClass $data Raw form data.
     * @return \stdClass
     */
    public static function normalise_for_storage(\stdClass $data): \stdClass {
        $record = new \stdClass();

        $record->id = isset($data->id) ? (int)$data->id : 0;
        $record->userid = isset($data->userid) ? (int)$data->userid : 0;
        $record->displaytitle = trim((string)($data->displaytitle ?? ''));
        $record->visibility = clean_param((string)($data->visibility ?? 'private'), PARAM_ALPHANUMEXT);
        $record->portfolioarchiveid = isset($data->portfolioarchiveid) ? (int)$data->portfolioarchiveid : 0;
        $record->profilenote = trim((string)($data->profilenote ?? ''));
        $record->profileorigin = clean_param((string)($data->profileorigin ?? 'profile_form'), PARAM_ALPHANUMEXT);
        $record->requiresreview = !empty($data->requiresreview) ? 1 : 0;

        $symbolicroles = self::normalise_static_array_value($data->symbolicroles ?? []);
        $activepathwayids = self::normalise_static_array_value($data->activepathwayids ?? []);

        $record->symbolicroles = json_encode(array_values($symbolicroles), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $record->activepathwayids = json_encode(
            array_values(array_map('intval', $activepathwayids)),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return $record;
    }

    /**
     * Apply defaults from an existing profile record.
     *
     * @param \stdClass|null $profile Existing profile record.
     * @param int $userid User id.
     * @return void
     */
    private function apply_defaults_from_profile(?\stdClass $profile, int $userid): void {
        $defaults = new \stdClass();

        $defaults->id = isset($profile->id) ? (int)$profile->id : 0;
        $defaults->userid = $userid;
        $defaults->displaytitle = $profile->displaytitle ?? '';
        $defaults->visibility = $profile->visibility ?? 'private';
        $defaults->portfolioarchiveid = isset($profile->portfolioarchiveid) ? (int)$profile->portfolioarchiveid : 0;
        $defaults->profilenote = $profile->profilenote ?? '';
        $defaults->profileorigin = 'profile_form';
        $defaults->requiresreview = 0;

        $defaults->symbolicroles = $this->decode_json_list($profile->symbolicroles ?? '');
        $defaults->activepathwayids = array_map('intval', $this->decode_json_list($profile->activepathwayids ?? ''));

        $this->set_data($defaults);
    }

    /**
     * Return target user id.
     *
     * @param \stdClass|null $targetuser Target user.
     * @param \stdClass|null $profile Existing profile.
     * @return int
     */
    private function get_target_userid(?\stdClass $targetuser, ?\stdClass $profile): int {
        if ($targetuser !== null && !empty($targetuser->id)) {
            return (int)$targetuser->id;
        }

        if ($profile !== null && !empty($profile->userid)) {
            return (int)$profile->userid;
        }

        return 0;
    }

    /**
     * Return return URL as a local path.
     *
     * @return string
     */
    private function get_return_url(): string {
        $customdata = $this->_customdata ?? [];
        $returnurl = $customdata['returnurl'] ?? null;

        if ($returnurl instanceof \moodle_url) {
            return $returnurl->out_as_local_url(false);
        }

        if (is_string($returnurl) && $returnurl !== '') {
            return (new \moodle_url($returnurl))->out_as_local_url(false);
        }

        return (new \moodle_url('/local/uckk/index.php'))->out_as_local_url(false);
    }

    /**
     * Return pathway options.
     *
     * @return array<int, string>
     */
    private function get_pathway_options(): array {
        global $DB;

        $customdata = $this->_customdata ?? [];
        $pathways = $customdata['pathways'] ?? null;

        if (is_array($pathways)) {
            return $this->normalise_pathway_options($pathways);
        }

        if ($DB->get_manager()->table_exists('local_uckk_pathway')) {
            $records = $DB->get_records_select(
                'local_uckk_pathway',
                'status <> :archived',
                ['archived' => 'archived'],
                'fullname ASC',
                'id, fullname, shortname'
            );

            $options = [];
            foreach ($records as $record) {
                $options[(int)$record->id] = format_string($record->fullname ?? $record->shortname ?? '');
            }

            return $options;
        }

        return [];
    }

    /**
     * Normalise pathway options.
     *
     * @param array $pathways Raw pathway options.
     * @return array<int, string>
     */
    private function normalise_pathway_options(array $pathways): array {
        $options = [];

        foreach ($pathways as $key => $value) {
            if (is_object($value)) {
                $id = isset($value->id) ? (int)$value->id : (int)$key;
                $label = $value->fullname ?? $value->name ?? $value->shortname ?? '';
            } else {
                $id = (int)$key;
                $label = (string)$value;
            }

            if ($id > 0 && trim($label) !== '') {
                $options[$id] = format_string($label);
            }
        }

        return $options;
    }

    /**
     * Decode a JSON list safely.
     *
     * @param string|null $json JSON string.
     * @return array<int, string>
     */
    private function decode_json_list(?string $json): array {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static function($value): string {
            return clean_param((string)$value, PARAM_ALPHANUMEXT);
        }, $decoded)));
    }

    /**
     * Normalise a potentially scalar or array value.
     *
     * @param mixed $value Raw value.
     * @return array<int, string>
     */
    private function normalise_array_value($value): array {
        return self::normalise_static_array_value($value);
    }

    /**
     * Normalise a potentially scalar or array value.
     *
     * @param mixed $value Raw value.
     * @return array<int, string>
     */
    private static function normalise_static_array_value($value): array {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $clean = [];

        foreach ($value as $item) {
            if ($item === null || $item === '') {
                continue;
            }

            $item = clean_param((string)$item, PARAM_ALPHANUMEXT);
            if ($item !== '') {
                $clean[] = $item;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * Return visibility options.
     *
     * @return array<string, string>
     */
    private static function get_visibility_options(): array {
        return [
            'private' => self::string('visibility:private', 'Privé'),
            'user' => self::string('visibility:user', 'Utilisateur seulement'),
            'course' => self::string('visibility:course', 'Cours'),
            'cohort' => self::string('visibility:cohort', 'Cohorte'),
            'institution' => self::string('visibility:institution', 'Institution UCKK'),
            'public' => self::string('visibility:public', 'Public'),
        ];
    }

    /**
     * Return symbolic role options.
     *
     * These are symbolic/pedagogical roles only. They are not Moodle roles.
     *
     * @return array<string, string>
     */
    private static function get_symbolic_role_options(): array {
        return [
            'joueur' => self::string('symbolicrole:joueur', 'Joueur'),
            'joueur_lucide' => self::string('symbolicrole:joueur_lucide', 'Joueur lucide'),
            'batisseur' => self::string('symbolicrole:batisseur', 'Bâtisseur'),
            'archiviste' => self::string('symbolicrole:archiviste', 'Archiviste'),
            'inquisiteur' => self::string('symbolicrole:inquisiteur', 'Inquisiteur'),
            'cartographe' => self::string('symbolicrole:cartographe', 'Cartographe'),
            'architecte_sens' => self::string('symbolicrole:architecte_sens', 'Architecte du sens'),
            'architecte_opportunites' => self::string('symbolicrole:architecte_opportunites', 'Architecte d’opportunités'),
            'gardien_systemes_vivants' => self::string(
                'symbolicrole:gardien_systemes_vivants',
                'Gardien des systèmes vivants'
            ),
        ];
    }

    /**
     * Return update origin options.
     *
     * @return array<string, string>
     */
    private static function get_profile_origin_options(): array {
        return [
            'profile_form' => self::string('profileorigin:profile_form', 'Formulaire de profil'),
            'admin_action' => self::string('profileorigin:admin_action', 'Action administrative'),
            'seed_tool' => self::string('profileorigin:seed_tool', 'Outil de génération UCKK'),
            'external_service' => self::string('profileorigin:external_service', 'Service externe'),
            'pathway_sync' => self::string('profileorigin:pathway_sync', 'Synchronisation de parcours'),
        ];
    }

    /**
     * Safe wrapper around get_string() with fallback.
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