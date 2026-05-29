<?php
// This file is part of Moodle - http://moodle.org/
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
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme settings for theme_uckk.
 *
 * @package    theme_uckk
 * @copyright  2026 Réjean McCormick
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'themesettinguckk',
        get_string('configtitle', 'theme_uckk')
    );
    $ADMIN->add('themes', $settings);

    if ($ADMIN->fulltree) {
    /*
     * UCKK identity.
     *
     * These settings are deliberately stored under the plugin component
     * "theme_uckk/..." so Moodle stores them in config_plugins and they
     * can be retrieved with get_config('theme_uckk', 'settingname').
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/identityheading',
        get_string('identityheading', 'theme_uckk'),
        get_string('identityheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/brandname',
        get_string('brandname', 'theme_uckk'),
        get_string('brandname_desc', 'theme_uckk'),
        'Univers-Cité King Klown',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/shortbrandname',
        get_string('shortbrandname', 'theme_uckk'),
        get_string('shortbrandname_desc', 'theme_uckk'),
        'UCKK',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/internationalbrandname',
        get_string('internationalbrandname', 'theme_uckk'),
        get_string('internationalbrandname_desc', 'theme_uckk'),
        'King Klown Univers-City',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/tagline',
        get_string('tagline', 'theme_uckk'),
        get_string('tagline_desc', 'theme_uckk'),
        'Comprendre le jeu. Jouer avec lucidité. Changer les règles.',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtextarea(
        'theme_uckk/institutionalstatement',
        get_string('institutionalstatement', 'theme_uckk'),
        get_string('institutionalstatement_desc', 'theme_uckk'),
        'UCKK-Moodle est le campus pédagogique de l’Univers-Cité King Klown. '
            . 'Il sert à former, documenter, délibérer, archiver et reconnaître les apprentissages internes.',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtextarea(
        'theme_uckk/canonicalboundary',
        get_string('canonicalboundary', 'theme_uckk'),
        get_string('canonicalboundary_desc', 'theme_uckk'),
        "kOA = mouvement\n"
            . "UCKK = école / cité d’apprentissage\n"
            . "kOA Digital Ecosystem = infrastructure numérique\n"
            . "King Klown = figure narrative de mobilisation\n"
            . "L’Inquisiteur = garde-fou éthique\n"
            . "Les Assemblées = légitimité collective\n"
            . "Les Archives = mémoire",
        PARAM_TEXT
    ));

    /*
     * Brand assets.
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/assetsheading',
        get_string('assetsheading', 'theme_uckk'),
        get_string('assetsheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configstoredfile(
        'theme_uckk/logo',
        get_string('logo', 'theme_uckk'),
        get_string('logo_desc', 'theme_uckk'),
        'logo',
        0,
        [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.svg', '.png', '.jpg', '.jpeg', '.webp'],
        ]
    ));

    $settings->add(new admin_setting_configstoredfile(
        'theme_uckk/logocompact',
        get_string('logocompact', 'theme_uckk'),
        get_string('logocompact_desc', 'theme_uckk'),
        'logocompact',
        0,
        [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.svg', '.png', '.jpg', '.jpeg', '.webp'],
        ]
    ));

    $settings->add(new admin_setting_configstoredfile(
        'theme_uckk/frontpagebackground',
        get_string('frontpagebackground', 'theme_uckk'),
        get_string('frontpagebackground_desc', 'theme_uckk'),
        'frontpagebackground',
        0,
        [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.svg', '.png', '.jpg', '.jpeg', '.webp'],
        ]
    ));

    $settings->add(new admin_setting_configstoredfile(
        'theme_uckk/loginbackground',
        get_string('loginbackground', 'theme_uckk'),
        get_string('loginbackground_desc', 'theme_uckk'),
        'loginbackground',
        0,
        [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.svg', '.png', '.jpg', '.jpeg', '.webp'],
        ]
    ));

    /*
     * Time-aware login backgrounds.
     *
     * These images are stored as Moodle theme files, not as fixed files under pix/.
     * The existing loginbackground setting remains available as the fallback.
     */
    $settings->add(new admin_setting_configstoredfile(
        'theme_uckk/loginbackgroundday',
        get_string('loginbackgroundday', 'theme_uckk'),
        get_string('loginbackgroundday_desc', 'theme_uckk'),
        'loginbackgroundday',
        0,
        [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.svg', '.png', '.jpg', '.jpeg', '.webp'],
        ]
    ));

    $settings->add(new admin_setting_configstoredfile(
        'theme_uckk/loginbackgroundbetween',
        get_string('loginbackgroundbetween', 'theme_uckk'),
        get_string('loginbackgroundbetween_desc', 'theme_uckk'),
        'loginbackgroundbetween',
        0,
        [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.svg', '.png', '.jpg', '.jpeg', '.webp'],
        ]
    ));

    $settings->add(new admin_setting_configstoredfile(
        'theme_uckk/loginbackgroundnight',
        get_string('loginbackgroundnight', 'theme_uckk'),
        get_string('loginbackgroundnight_desc', 'theme_uckk'),
        'loginbackgroundnight',
        0,
        [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.svg', '.png', '.jpg', '.jpeg', '.webp'],
        ]
    ));

    $settings->add(new admin_setting_configstoredfile(
        'theme_uckk/favicon',
        get_string('favicon', 'theme_uckk'),
        get_string('favicon_desc', 'theme_uckk'),
        'favicon',
        0,
        [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.ico', '.svg', '.png'],
        ]
    ));

    /*
     * Colour system.
     *
     * Defaults follow the UCKK visual style:
     * deep petroleum green, parchment ivory, aged gold/bronze,
     * blue-green shadow and dark institutional ink.
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/coloursheading',
        get_string('coloursheading', 'theme_uckk'),
        get_string('coloursheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/brandprimary',
        get_string('brandprimary', 'theme_uckk'),
        get_string('brandprimary_desc', 'theme_uckk'),
        '#1e6864'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/brandsecondary',
        get_string('brandsecondary', 'theme_uckk'),
        get_string('brandsecondary_desc', 'theme_uckk'),
        '#54736f'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/brandaccent',
        get_string('brandaccent', 'theme_uckk'),
        get_string('brandaccent_desc', 'theme_uckk'),
        '#b9934b'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/surfacebackground',
        get_string('surfacebackground', 'theme_uckk'),
        get_string('surfacebackground_desc', 'theme_uckk'),
        '#f7f1df'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/surfacetext',
        get_string('surfacetext', 'theme_uckk'),
        get_string('surfacetext_desc', 'theme_uckk'),
        '#172321'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/integritycolour',
        get_string('integritycolour', 'theme_uckk'),
        get_string('integritycolour_desc', 'theme_uckk'),
        '#8f2f2f'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/archivecolour',
        get_string('archivecolour', 'theme_uckk'),
        get_string('archivecolour_desc', 'theme_uckk'),
        '#b9934b'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/assemblycolour',
        get_string('assemblycolour', 'theme_uckk'),
        get_string('assemblycolour_desc', 'theme_uckk'),
        '#1e6864'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'theme_uckk/challengecolour',
        get_string('challengecolour', 'theme_uckk'),
        get_string('challengecolour_desc', 'theme_uckk'),
        '#54736f'
    ));

    /*
     * Front page.
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/frontpageheading',
        get_string('frontpageheading', 'theme_uckk'),
        get_string('frontpageheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showfrontpagehero',
        get_string('showfrontpagehero', 'theme_uckk'),
        get_string('showfrontpagehero_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/frontpageherotitle',
        get_string('frontpageherotitle', 'theme_uckk'),
        get_string('frontpageherotitle_desc', 'theme_uckk'),
        'Entrer dans l’Univers-Cité King Klown',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtextarea(
        'theme_uckk/frontpageherosubtitle',
        get_string('frontpageherosubtitle', 'theme_uckk'),
        get_string('frontpageherosubtitle_desc', 'theme_uckk'),
        'Un campus Moodle pour apprendre à lire le Grand Jeu social, produire des preuves, '
            . 'participer aux Assemblées, relever des Défis et garder la mémoire.',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/frontpageprimarybuttontext',
        get_string('frontpageprimarybuttontext', 'theme_uckk'),
        get_string('frontpageprimarybuttontext_desc', 'theme_uckk'),
        'Commencer le parcours',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/frontpageprimarybuttonurl',
        get_string('frontpageprimarybuttonurl', 'theme_uckk'),
        get_string('frontpageprimarybuttonurl_desc', 'theme_uckk'),
        '/course/view.php?id=1',
        PARAM_LOCALURL
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/frontpagesecondarybuttontext',
        get_string('frontpagesecondarybuttontext', 'theme_uckk'),
        get_string('frontpagesecondarybuttontext_desc', 'theme_uckk'),
        'Voir les Défis',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/frontpagesecondarybuttonurl',
        get_string('frontpagesecondarybuttonurl', 'theme_uckk'),
        get_string('frontpagesecondarybuttonurl_desc', 'theme_uckk'),
        '/course/index.php?categoryid=90',
        PARAM_LOCALURL
    ));

    /*
     * Navigation.
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/navigationheading',
        get_string('navigationheading', 'theme_uckk'),
        get_string('navigationheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showuckknavigation',
        get_string('showuckknavigation', 'theme_uckk'),
        get_string('showuckknavigation_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configtextarea(
        'theme_uckk/quicklinks',
        get_string('quicklinks', 'theme_uckk'),
        get_string('quicklinks_desc', 'theme_uckk'),
        "Mon parcours|/my/\n"
            . "Cours|/course/\n"
            . "Défis|/course/index.php?categoryid=90\n"
            . "Assemblées|/course/index.php?categoryid=91\n"
            . "Archives|/course/index.php?categoryid=92\n"
            . "Inquisiteur|/admin/tool/uckkintegrity/index.php",
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showcanonicalboundary',
        get_string('showcanonicalboundary', 'theme_uckk'),
        get_string('showcanonicalboundary_desc', 'theme_uckk'),
        1
    ));

    /*
     * UCKK feature visibility.
     *
     * These are presentation toggles only. They do not grant capabilities
     * and do not replace access checks in the dedicated UCKK plugins.
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/featuresheading',
        get_string('featuresheading', 'theme_uckk'),
        get_string('featuresheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showdashboardcards',
        get_string('showdashboardcards', 'theme_uckk'),
        get_string('showdashboardcards_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showchallengecards',
        get_string('showchallengecards', 'theme_uckk'),
        get_string('showchallengecards_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showassemblycards',
        get_string('showassemblycards', 'theme_uckk'),
        get_string('showassemblycards_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showarchivecards',
        get_string('showarchivecards', 'theme_uckk'),
        get_string('showarchivecards_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showintegritynotices',
        get_string('showintegritynotices', 'theme_uckk'),
        get_string('showintegritynotices_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showailabels',
        get_string('showailabels', 'theme_uckk'),
        get_string('showailabels_desc', 'theme_uckk'),
        1
    ));

    /*
     * Public theatre and ethical notice.
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/ethicsheading',
        get_string('ethicsheading', 'theme_uckk'),
        get_string('ethicsheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showpublictheatrenotice',
        get_string('showpublictheatrenotice', 'theme_uckk'),
        get_string('showpublictheatrenotice_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configtextarea(
        'theme_uckk/publictheatrenotice',
        get_string('publictheatrenotice', 'theme_uckk'),
        get_string('publictheatrenotice_desc', 'theme_uckk'),
        "Ceci est du théâtre.\n"
            . "Ceci est pédagogique.\n"
            . "Ceci est public.\n"
            . "Ceci est vivant.\n"
            . "Ceci reste responsable.",
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/ethicalmaxim',
        get_string('ethicalmaxim', 'theme_uckk'),
        get_string('ethicalmaxim_desc', 'theme_uckk'),
        'Le spectacle est permis. L’abus ne l’est pas.',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'theme_uckk/methodologicalmaxim',
        get_string('methodologicalmaxim', 'theme_uckk'),
        get_string('methodologicalmaxim_desc', 'theme_uckk'),
        'La foi peut inspirer. Les faits doivent convaincre. La méthode doit pouvoir être vérifiée.',
        PARAM_TEXT
    ));

    /*
     * Footer.
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/footerheading',
        get_string('footerheading', 'theme_uckk'),
        get_string('footerheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'theme_uckk/showfooterboundary',
        get_string('showfooterboundary', 'theme_uckk'),
        get_string('showfooterboundary_desc', 'theme_uckk'),
        1
    ));

    $settings->add(new admin_setting_configtextarea(
        'theme_uckk/footertext',
        get_string('footertext', 'theme_uckk'),
        get_string('footertext_desc', 'theme_uckk'),
        'UCKK-Moodle est un campus pédagogique expérimental. '
            . 'Les reconnaissances UCKK sont internes, sauf reconnaissance officielle future.',
        PARAM_TEXT
    ));

    /*
     * Advanced SCSS.
     *
     * These settings are intentionally raw because Moodle themes commonly
     * use SCSS snippets. Administrators should restrict access to trusted
     * users only through Moodle site configuration permissions.
     */

    $settings->add(new admin_setting_heading(
        'theme_uckk/advancedheading',
        get_string('advancedheading', 'theme_uckk'),
        get_string('advancedheading_desc', 'theme_uckk')
    ));

    $settings->add(new admin_setting_configtextarea(
        'theme_uckk/rawscsspre',
        get_string('rawscsspre', 'theme_uckk'),
        get_string('rawscsspre_desc', 'theme_uckk'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtextarea(
        'theme_uckk/rawscss',
        get_string('rawscss', 'theme_uckk'),
        get_string('rawscss_desc', 'theme_uckk'),
        '',
        PARAM_RAW
    ));
}
}
