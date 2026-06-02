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
 * Library callbacks for the UCKK theme.
 *
 * Component: theme_uckk
 *
 * This file intentionally keeps theme_uckk limited to visual identity,
 * theme settings, SCSS generation, and controlled serving of theme-managed
 * files. UCKK institutional workflows remain owned by the relevant Moodle
 * plugins:
 *
 * - local_uckk: institutional registry and shared services.
 * - mod_uckkchallenge: Défis King Klown.
 * - mod_uckkassembly: Assemblées.
 * - mod_uckkarchive: Archives.
 * - tool_uckkintegrity: Inquisiteur / integrity workflow.
 * - report_uckk: institutional reports.
 *
 * The theme may express the UCKK identity, but it must not become the place
 * where grading, permissions, integrity decisions, assembly decisions, archive
 * validation, or AI authority are implemented.
 *
 * @package    theme_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return the main SCSS content for the UCKK theme.
 *
 * The theme supports a configurable preset. The value is expected to be the
 * basename of a SCSS file inside theme/uckk/scss/preset. If the configured
 * preset is missing or unsafe, the default preset is used.
 *
 * Expected preset path:
 *
 *     theme/uckk/scss/preset/default.scss
 *
 * @param theme_config $theme The theme configuration object.
 * @return string SCSS content.
 */
function theme_uckk_get_main_scss_content(theme_config $theme): string {
    global $CFG;

    $preset = theme_uckk_get_setting('preset');
    $preset = theme_uckk_clean_preset_name($preset);

    if ($preset === '') {
        $preset = 'default.scss';
    }

    $presetpath = $CFG->dirroot . '/theme/uckk/scss/preset/' . $preset;

    if (!is_readable($presetpath)) {
        $presetpath = $CFG->dirroot . '/theme/uckk/scss/preset/default.scss';
    }

    if (!is_readable($presetpath)) {
        debugging('theme_uckk: Missing default SCSS preset.', DEBUG_DEVELOPER);
        return '';
    }

    $scss = file_get_contents($presetpath);

    /*
     * Keep Boost imports in the preset, but inline UCKK local SCSS files.
     * Moodle's SCSS importer can resolve these paths but is refusing to import
     * them in this Windows/junction development tree.
     */
    $localfiles = [
        'uckk',
        'components',
        'navigation',
        'dashboard',
        'course',
        'challenge',
        'assembly',
        'archive',
    ];

    foreach ($localfiles as $name) {
        $pattern = '/^\s*@import\s+["\']' . preg_quote($name, '/') . '["\']\s*;\s*$/m';
        $scss = preg_replace($pattern, '', $scss);
    }

    foreach ($localfiles as $name) {
        $file = $CFG->dirroot . '/theme/uckk/scss/' . $name . '.scss';

        if (!is_readable($file)) {
            debugging('theme_uckk: Missing SCSS file ' . $file, DEBUG_DEVELOPER);
            continue;
        }

        $scss .= "\n\n/* Inlined theme_uckk/scss/" . $name . ".scss */\n";
        $scss .= file_get_contents($file);
    }

    return $scss;
}
/**
 * Return SCSS variables injected before the main SCSS is compiled.
 *
 * These variables express the UCKK visual canon:
 *
 * - petroleum green: institutional authority, continuity, civic calm;
 * - parchment ivory: paper, knowledge, readability;
 * - aged gold: crest, prestige, renewed tradition;
 * - blue-green grey: architectural shadow and secondary structure;
 * - dark ink: text, contrast, administrative seriousness;
 * - burgundy: alert, integrity signal, theatrical energy.
 *
 * Administrators may override the values from theme settings when those
 * settings exist. Defaults are safe and self-contained so the theme remains
 * installable before full configuration.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string SCSS variable declarations.
 */
function theme_uckk_get_pre_scss(theme_config $theme): string {
    /*
     * Canonical UCKK design tokens.
     *
     * This function is the bridge between:
     *
     * - Moodle / Bootstrap SCSS variables;
     * - legacy theme_uckk SCSS variables;
     * - public CSS custom properties consumed by local_uckk/styles.css.
     *
     * Keep legacy setting keys and variable names for compatibility, but make
     * the semantic palette the single source of truth.
     */
    $defaults = [
        'uckkcolorblack' => '#172321',
        'uckkcolorivory' => '#f6f0df',
        'uckkcolorred' => '#8f2f2f',
        'uckkcolorgold' => '#b99045',
        'uckkcolorblue' => '#5f7876',
        'uckkcolorgreen' => '#1e6864',
        'uckkfontinstitutional' => 'Georgia, "Times New Roman", serif',
        'uckkfontdisplay' => 'Cambria, Georgia, "Times New Roman", serif',
        'uckkfonttechnical' => '"Courier New", Consolas, monospace',
        'uckkfontbody' => 'system-ui, -apple-system, "Segoe UI", Arial, sans-serif',
    ];

    $values = [];
    $scss = [];

    foreach ($defaults as $settingname => $defaultvalue) {
        $value = theme_uckk_get_setting($settingname);

        if ($value === '') {
            $value = $defaultvalue;
        }

        $safevalue = theme_uckk_scss_value($value, $defaultvalue);
        $values[$settingname] = $safevalue;

        /*
         * Legacy variable names used by existing SCSS:
         *
         * - uckkcolorblack  -> $uckk-colorblack
         * - uckkfontbody    -> $uckk-fontbody
         */
        $variable = str_replace('uckk', 'uckk-', $settingname);
        $scss[] = '$' . $variable . ': ' . $safevalue . ';';
    }

    /*
     * Semantic aliases.
     *
     * These are the preferred names for all new SCSS. Existing older variables
     * remain available below so old partials still compile.
     */
    $tokens = [
        'ink' => $values['uckkcolorblack'],
        'ink-soft' => '#2f3f3b',
        'petrol' => $values['uckkcolorgreen'],
        'petrol-dark' => '#164c49',
        'petrol-soft' => '#dbecea',
        'parchment' => $values['uckkcolorivory'],
        'parchment-light' => '#fbf7eb',
        'parchment-deep' => '#e6dcc2',
        'gold' => $values['uckkcolorgold'],
        'gold-dark' => '#7f642f',
        'gold-soft' => '#efe0b2',
        'bronze' => '#9b7441',
        'bluegrey' => $values['uckkcolorblue'],
        'burgundy' => $values['uckkcolorred'],
        'burgundy-dark' => '#6f2424',
        'card' => '#fffaf0',
        'white' => '#ffffff',
        'line' => 'rgba(23, 35, 33, 0.16)',
        'line-strong' => 'rgba(23, 35, 33, 0.28)',
        'shadow' => '0 18px 42px rgba(23, 35, 33, 0.12)',
        'shadow-soft' => '0 8px 20px rgba(23, 35, 33, 0.08)',
        'shadow-focus' => '0 0 0 .2rem rgba(185, 144, 69, .28)',
        'radius' => '0.9rem',
        'radius-small' => '0.55rem',
        'radius-lg' => '1.15rem',
        'radius-xl' => '1.5rem',
        'max-width' => '1180px',
    ];

    foreach ($tokens as $name => $value) {
        $scss[] = '$uckk-' . $name . ': ' . $value . ';';
    }

    /*
     * Compatibility aliases for the current theme partials.
     */
    $aliases = [
        'uckk-black' => '$uckk-ink',
        'uckk-muted' => '$uckk-ink-soft',
        'uckk-border' => '$uckk-line',
        'uckk-border-strong' => '$uckk-line-strong',
        'uckk-surface' => '$uckk-parchment',
        'uckk-surface-soft' => '$uckk-parchment-light',
        'uckk-surface-warm' => '$uckk-card',
        'uckk-red' => '$uckk-burgundy',
        'uckk-red-soft' => '#f4e3df',
        'uckk-blue' => '$uckk-bluegrey',
        'uckk-blue-soft' => '$uckk-petrol-soft',
        'uckk-green' => '$uckk-petrol',
        'uckk-green-soft' => '$uckk-petrol-soft',
        'uckk-purple' => '#6d28d9',
        'uckk-purple-soft' => '#ede9fe',
        'uckk-slate' => '$uckk-bluegrey',
        'uckk-slate-soft' => '#eef4f3',
        'uckk-radius-sm' => '$uckk-radius-small',
        'uckk-radius-md' => '$uckk-radius',
        'uckk-shadow-sm' => '$uckk-shadow-soft',
        'uckk-shadow-md' => '$uckk-shadow',
        'uckk-shadow-focus' => '$uckk-shadow-focus',

        'theme-uckk-black' => '$uckk-ink',
        'theme-uckk-black-soft' => '$uckk-ink-soft',
        'theme-uckk-ivory' => '$uckk-parchment',
        'theme-uckk-ivory-muted' => '$uckk-parchment-deep',
        'theme-uckk-red' => '$uckk-burgundy',
        'theme-uckk-red-dark' => '$uckk-burgundy-dark',
        'theme-uckk-gold' => '$uckk-gold',
        'theme-uckk-gold-soft' => '$uckk-gold-soft',
        'theme-uckk-blue' => '$uckk-bluegrey',
        'theme-uckk-blue-soft' => '$uckk-petrol-soft',
        'theme-uckk-green' => '$uckk-petrol',
        'theme-uckk-green-soft' => '$uckk-petrol-soft',
        'theme-uckk-white' => '$uckk-white',
        'theme-uckk-grey-100' => '#f7f4ea',
        'theme-uckk-grey-200' => '#ece5d2',
        'theme-uckk-grey-300' => '#d8cfb8',
        'theme-uckk-grey-600' => '$uckk-bluegrey',
        'theme-uckk-grey-800' => '$uckk-ink',
        'theme-uckk-radius-sm' => '$uckk-radius-small',
        'theme-uckk-radius' => '$uckk-radius',
        'theme-uckk-radius-lg' => '$uckk-radius-lg',
        'theme-uckk-radius-xl' => '$uckk-radius-xl',
        'theme-uckk-shadow-sm' => '$uckk-shadow-soft',
        'theme-uckk-shadow' => '$uckk-shadow',
        'theme-uckk-shadow-lg' => '0 28px 60px rgba(23, 35, 33, 0.18)',
        'theme-uckk-border-soft' => '1px solid rgba(251, 247, 235, .32)',
        'theme-uckk-border-dark' => '1px solid rgba(23, 35, 33, .16)',
        'theme-uckk-transition' => '160ms ease-in-out',
    ];

    foreach ($aliases as $name => $value) {
        $scss[] = '$' . $name . ': ' . $value . ';';
    }

    /*
     * Theme settings may use either brandprimary or brandcolor. Prefer
     * brandprimary because settings.php exposes it in current UCKK builds.
     */
    $brandcolor = theme_uckk_get_setting('brandprimary');

    if ($brandcolor === '') {
        $brandcolor = theme_uckk_get_setting('brandcolor');
    }

    if ($brandcolor === '') {
        $brandcolor = $values['uckkcolorgreen'];
    }

    $brandcolor = theme_uckk_scss_value($brandcolor, $values['uckkcolorgreen']);

    /*
     * Bootstrap / Moodle variables.
     */
    $scss[] = '$primary: ' . $brandcolor . ';';
    $scss[] = '$secondary: $uckk-bluegrey;';
    $scss[] = '$success: $uckk-petrol;';
    $scss[] = '$info: $uckk-bluegrey;';
    $scss[] = '$warning: $uckk-gold;';
    $scss[] = '$danger: $uckk-burgundy;';
    $scss[] = '$light: $uckk-parchment-light;';
    $scss[] = '$dark: $uckk-ink;';
    $scss[] = '$body-bg: $uckk-parchment;';
    $scss[] = '$body-color: $uckk-ink;';
    $scss[] = '$link-color: ' . $brandcolor . ';';
    $scss[] = '$link-hover-color: $uckk-petrol-dark;';
    $scss[] = '$border-color: $uckk-line;';
    $scss[] = '$border-radius: $uckk-radius-small;';
    $scss[] = '$border-radius-lg: $uckk-radius;';
    $scss[] = '$border-radius-sm: .25rem;';
    $scss[] = '$card-border-color: $uckk-line;';
    $scss[] = '$card-border-radius: $uckk-radius;';
    $scss[] = '$card-cap-bg: $uckk-parchment-light;';
    $scss[] = '$navbar-light-color: $uckk-ink;';
    $scss[] = '$navbar-light-hover-color: $uckk-petrol-dark;';
    $scss[] = '$navbar-light-active-color: $uckk-petrol;';
    $scss[] = '$dropdown-link-hover-bg: $uckk-petrol-soft;';
    $scss[] = '$dropdown-link-active-bg: $uckk-petrol;';
    $scss[] = '$dropdown-link-active-color: $uckk-parchment-light;';
    $scss[] = '$input-border-color: $uckk-line-strong;';
    $scss[] = '$input-focus-border-color: $uckk-gold;';
    $scss[] = '$input-focus-box-shadow: $uckk-shadow-focus;';
    $scss[] = '$btn-focus-box-shadow: $uckk-shadow-focus;';
    $scss[] = '$progress-bg: #eef4f3;';
    $scss[] = '$progress-bar-bg: $uckk-petrol;';
    $scss[] = '$breadcrumb-bg: transparent;';
    $scss[] = '$breadcrumb-divider-color: $uckk-ink-soft;';
    $scss[] = '$breadcrumb-active-color: $uckk-ink-soft;';
    $scss[] = '$badge-font-weight: 700;';
    $scss[] = '$font-family-sans-serif: ' . $values['uckkfontbody'] . ';';
    $scss[] = '$headings-font-family: ' . $values['uckkfontinstitutional'] . ';';

    /*
     * CSS custom properties consumed by both theme_uckk and local_uckk.
     *
     * New local plugin styles should map their local --uckk-* variables from
     * --theme-uckk-* values. Legacy theme partials still consume --uckk-*.
     */
    $scss[] = '';
    $scss[] = ':root {';

    $customproperties = [
        'ink' => '$uckk-ink',
        'ink-soft' => '$uckk-ink-soft',
        'petrol' => '$uckk-petrol',
        'petrol-dark' => '$uckk-petrol-dark',
        'petrol-soft' => '$uckk-petrol-soft',
        'parchment' => '$uckk-parchment',
        'parchment-light' => '$uckk-parchment-light',
        'parchment-deep' => '$uckk-parchment-deep',
        'gold' => '$uckk-gold',
        'gold-dark' => '$uckk-gold-dark',
        'gold-soft' => '$uckk-gold-soft',
        'bronze' => '$uckk-bronze',
        'bluegrey' => '$uckk-bluegrey',
        'burgundy' => '$uckk-burgundy',
        'burgundy-dark' => '$uckk-burgundy-dark',
        'card' => '$uckk-card',
        'white' => '$uckk-white',
        'line' => '$uckk-line',
        'line-strong' => '$uckk-line-strong',
        'shadow' => '$uckk-shadow',
        'shadow-soft' => '$uckk-shadow-soft',
        'shadow-focus' => '$uckk-shadow-focus',
        'radius' => '$uckk-radius',
        'radius-small' => '$uckk-radius-small',
        'radius-lg' => '$uckk-radius-lg',
        'radius-xl' => '$uckk-radius-xl',
        'max-width' => '$uckk-max-width',
    ];

    foreach ($customproperties as $name => $variable) {
        $scss[] = '    --theme-uckk-' . $name . ': #{' . $variable . '};';
    }

    /*
     * Legacy CSS custom properties used by existing theme SCSS.
     */
    $legacycustomproperties = [
        'black' => '$uckk-ink',
        'black-soft' => '$uckk-ink-soft',
        'ivory' => '$uckk-parchment',
        'ivory-muted' => '$uckk-parchment-deep',
        'red' => '$uckk-burgundy',
        'red-dark' => '$uckk-burgundy-dark',
        'gold' => '$uckk-gold',
        'gold-soft' => '$uckk-gold-soft',
        'blue' => '$uckk-bluegrey',
        'blue-soft' => '$uckk-petrol-soft',
        'green' => '$uckk-petrol',
        'green-soft' => '$uckk-petrol-soft',
        'white' => '$uckk-white',
        'grey-100' => '$theme-uckk-grey-100',
        'grey-200' => '$theme-uckk-grey-200',
        'grey-300' => '$theme-uckk-grey-300',
        'grey-600' => '$theme-uckk-grey-600',
        'grey-800' => '$theme-uckk-grey-800',
        'radius-sm' => '$uckk-radius-small',
        'radius' => '$uckk-radius',
        'radius-lg' => '$uckk-radius-lg',
        'radius-xl' => '$uckk-radius-xl',
        'shadow-sm' => '$uckk-shadow-soft',
        'shadow' => '$uckk-shadow',
        'shadow-lg' => '$theme-uckk-shadow-lg',
    ];

    foreach ($legacycustomproperties as $name => $variable) {
        $scss[] = '    --uckk-' . $name . ': #{' . $variable . '};';
    }

    $scss[] = '}';

    return implode("\n", $scss) . "\n";
}
/**
 * Return the public URL for a file stored in a theme file setting.
 *
 * @param string $filearea Theme file area name.
 * @return string Public pluginfile URL, or an empty string when no file exists.
 */
function theme_uckk_get_setting_file_url(string $filearea): string {
    $allowedareas = [
        'logo',
        'logocompact',
        'favicon',
        'frontpagebackground',
        'loginbackground',
        'loginbackgroundday',
        'loginbackgroundbetween',
        'loginbackgroundnight',
        'heroimage',
        'sealimage',
    ];

    if (!in_array($filearea, $allowedareas, true)) {
        return '';
    }

    $context = context_system::instance();
    $fs = get_file_storage();

    $files = $fs->get_area_files(
        $context->id,
        'theme_uckk',
        $filearea,
        0,
        'filename',
        false
    );

    foreach ($files as $file) {
        if (!$file->is_directory()) {
            return moodle_url::make_pluginfile_url(
                $context->id,
                'theme_uckk',
                $filearea,
                0,
                '/',
                $file->get_filename()
            )->out(false);
        }
    }

    return '';
}

/**
 * Return the configured login background image URLs.
 *
 * The old loginbackground setting is kept as a fallback. The three new file
 * areas support the day / between / night visual cycle on the login page.
 *
 * @return array<string, string>
 */
function theme_uckk_get_login_background_urls(): array {
    $legacy = theme_uckk_get_setting_file_url('loginbackground');

    $day = theme_uckk_get_setting_file_url('loginbackgroundday');
    $between = theme_uckk_get_setting_file_url('loginbackgroundbetween');
    $night = theme_uckk_get_setting_file_url('loginbackgroundnight');

    if ($day === '') {
        $day = $legacy;
    }

    if ($between === '') {
        $between = $day !== '' ? $day : $legacy;
    }

    if ($night === '') {
        $night = $legacy !== '' ? $legacy : $day;
    }

    $fallback = $legacy !== '' ? $legacy : ($night !== '' ? $night : ($between !== '' ? $between : $day));

    return [
        'day' => $day,
        'between' => $between,
        'night' => $night,
        'fallback' => $fallback,
    ];
}

/**
 * Return login background configuration for the AMD module.
 *
 * The location is intentionally institutional, not user-specific. No browser
 * geolocation permission and no IP geolocation are required.
 *
 * @return array<string, mixed>
 */
function theme_uckk_get_login_background_config(): array {
    return [
        'images' => theme_uckk_get_login_background_urls(),
        'targetSelector' => '.login-layout-left',
        'periodClasses' => [
            'day' => 'theme-uckk-login-background--day',
            'between' => 'theme-uckk-login-background--between',
            'night' => 'theme-uckk-login-background--night',
        ],
        'solar' => [
            'latitude' => 45.5017,
            'longitude' => -73.5673,
            'twilightMinutes' => 60,
        ],
        'fallbackWindows' => [
            'morningBetweenStart' => '06:00',
            'dayStart' => '07:00',
            'eveningBetweenStart' => '18:00',
            'nightStart' => '19:00',
        ],
    ];
}

/**
 * Return extra SCSS appended after the main SCSS.
 *
 * This supports administrator-controlled custom CSS through the `rawscss`
 * setting. It must remain visual only. Business logic must not be encoded
 * through CSS classes or CSS-generated behavior.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string Extra SCSS.
 */
function theme_uckk_get_extra_scss(theme_config $theme): string {
    $scss = [];

    $scss[] = '';
    $scss[] = '/* UCKK semantic identity helpers. */';
    $scss[] = '.uckk-boundary-note { font-size: .875rem; opacity: .85; }';
    $scss[] = '.uckk-kingklown-layer { position: relative; }';
    $scss[] = '.uckk-institutional-surface { background: $uckk-paper; border: 1px solid rgba(185, 147, 75, .32); box-shadow: 0 .75rem 2rem rgba(23, 35, 33, .08); }';
    $scss[] = '.uckk-official-label { color: $uckk-petrol; font-size: .75rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }';
    $scss[] = '.uckk-integrity-signal { border-inline-start: .25rem solid $uckk-burgundy; }';
    $scss[] = '.uckk-assembly-signal { border-inline-start: .25rem solid $uckk-gold; }';
    $scss[] = '.uckk-archive-signal { border-inline-start: .25rem solid $uckk-bluegrey; }';
    $scss[] = '.uckk-experiment-signal { border-inline-start: .25rem solid $uckk-petrol; }';

    $loginbackgrounds = theme_uckk_get_login_background_urls();
    $loginbackgroundurl = $loginbackgrounds['fallback'];

    if ($loginbackgroundurl !== '') {
        $scss[] = '';
        $scss[] = '/* UCKK login page background. */';
        $scss[] = 'body.pagelayout-login {';
        $scss[] = '    min-height: 100vh;';
        $scss[] = '    background: #071514 !important;';

        foreach ([
            'day' => 'day',
            'between' => 'between',
            'night' => 'night',
            'fallback' => 'fallback',
        ] as $key => $csskey) {
            if (!empty($loginbackgrounds[$key])) {
                $url = str_replace("'", "\\'", $loginbackgrounds[$key]);
                $scss[] = "    --theme-uckk-login-background-{$csskey}: url('{$url}');";
            }
        }

        $scss[] = '}';
        $scss[] = 'body.pagelayout-login #page,';
        $scss[] = 'body.pagelayout-login #page-wrapper,';
        $scss[] = 'body.pagelayout-login .login-wrapper {';
        $scss[] = '    background: transparent !important;';
        $scss[] = '}';
        $scss[] = 'body.pagelayout-login .login-layout-left,';
        $scss[] = 'body.pagelayout-login #page .login-layout-left,';
        $scss[] = 'body#page-login-index .login-layout-left,';
        $scss[] = 'body#page-login-index #page .login-layout-left {';
        $scss[] = '    background-image: var(--theme-uckk-login-background-fallback, var(--theme-uckk-login-background-night, var(--theme-uckk-login-background-day))) !important;';
        $scss[] = '    background-position: right center !important;';
        $scss[] = '    background-size: cover !important;';
        $scss[] = '    background-repeat: no-repeat !important;';
        $scss[] = '    background-color: transparent !important;';
        $scss[] = '    background-blend-mode: normal !important;';
        $scss[] = '    opacity: 1 !important;';
        $scss[] = '    filter: none !important;';
        $scss[] = '    mix-blend-mode: normal !important;';
        $scss[] = '}';
        $scss[] = 'body.pagelayout-login.theme-uckk-login-background--day .login-layout-left,';
        $scss[] = 'body.pagelayout-login.theme-uckk-login-background--day #page .login-layout-left,';
        $scss[] = 'body#page-login-index.theme-uckk-login-background--day .login-layout-left,';
        $scss[] = 'body#page-login-index.theme-uckk-login-background--day #page .login-layout-left {';
        $scss[] = '    background-image: var(--theme-uckk-login-background-day, var(--theme-uckk-login-background-fallback)) !important;';
        $scss[] = '}';
        $scss[] = 'body.pagelayout-login.theme-uckk-login-background--between .login-layout-left,';
        $scss[] = 'body.pagelayout-login.theme-uckk-login-background--between #page .login-layout-left,';
        $scss[] = 'body#page-login-index.theme-uckk-login-background--between .login-layout-left,';
        $scss[] = 'body#page-login-index.theme-uckk-login-background--between #page .login-layout-left {';
        $scss[] = '    background-image: var(--theme-uckk-login-background-between, var(--theme-uckk-login-background-day, var(--theme-uckk-login-background-fallback))) !important;';
        $scss[] = '}';
        $scss[] = 'body.pagelayout-login.theme-uckk-login-background--night .login-layout-left,';
        $scss[] = 'body.pagelayout-login.theme-uckk-login-background--night #page .login-layout-left,';
        $scss[] = 'body#page-login-index.theme-uckk-login-background--night .login-layout-left,';
        $scss[] = 'body#page-login-index.theme-uckk-login-background--night #page .login-layout-left {';
        $scss[] = '    background-image: var(--theme-uckk-login-background-night, var(--theme-uckk-login-background-fallback, var(--theme-uckk-login-background-day))) !important;';
        $scss[] = '}';
        $scss[] = 'body.pagelayout-login .login-layout-left::before,';
        $scss[] = 'body.pagelayout-login .login-layout-left::after,';
        $scss[] = 'body.pagelayout-login #page .login-layout-left::before,';
        $scss[] = 'body.pagelayout-login #page .login-layout-left::after,';
        $scss[] = 'body#page-login-index .login-layout-left::before,';
        $scss[] = 'body#page-login-index .login-layout-left::after,';
        $scss[] = 'body#page-login-index #page .login-layout-left::before,';
        $scss[] = 'body#page-login-index #page .login-layout-left::after {';
        $scss[] = '    display: none !important;';
        $scss[] = '    content: none !important;';
        $scss[] = '    opacity: 0 !important;';
        $scss[] = '    visibility: hidden !important;';
        $scss[] = '    background: none !important;';
        $scss[] = '    background-image: none !important;';
        $scss[] = '    box-shadow: none !important;';
        $scss[] = '    filter: none !important;';
        $scss[] = '}';
        $scss[] = 'body.pagelayout-login .login-layout-left-content {';
        $scss[] = '    background: transparent !important;';
        $scss[] = '}';
        $scss[] = 'body.pagelayout-login .login-layout-right {';
        $scss[] = '    background: #f7f1df !important;';
        $scss[] = '}';
        $scss[] = 'body.pagelayout-login .login-container,';
        $scss[] = 'body.pagelayout-login .card {';
        $scss[] = '    background: rgba(247, 241, 223, .96) !important;';
        $scss[] = '    border: 2px solid rgba(185, 147, 75, .55);';
        $scss[] = '    box-shadow: 0 1rem 3rem rgba(7, 7, 7, .28);';
        $scss[] = '}';
    }

    $rawscss = theme_uckk_get_setting('rawscss', -1);

    if ($rawscss !== '') {
        $scss[] = '';
        $scss[] = '/* Administrator custom SCSS. */';
        $scss[] = $rawscss;
    }

    return implode("\n", $scss) . "\n";
}

/**
 * Serve files stored by the UCKK theme settings.
 *
 * Supported file areas:
 *
 * - logo
 * - logocompact
 * - favicon
 * - frontpagebackground
 * - loginbackground
 * - loginbackgroundday
 * - loginbackgroundbetween
 * - loginbackgroundnight
 * - heroimage
 * - sealimage
 *
 * These file areas are expected to be used by admin_setting_configstoredfile
 * settings in theme/uckk/settings.php.
 *
 * @param stdClass|null $course Course object, or null for theme-level pluginfile requests.
 * @param stdClass|null $cm Course module object, unused for theme files.
 * @param context $context Moodle context.
 * @param string $filearea Requested file area.
 * @param array $args Request path arguments.
 * @param bool $forcedownload Whether download should be forced.
 * @param array $options Additional file serving options.
 * @return bool False when the file is not found or access is denied.
 */
function theme_uckk_pluginfile(
    ?stdClass $course,
    ?stdClass $cm,
    context $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return false;
    }

    $allowedareas = [
        'logo',
        'logocompact',
        'favicon',
        'frontpagebackground',
        'loginbackground',
        'loginbackgroundday',
        'loginbackgroundbetween',
        'loginbackgroundnight',
        'heroimage',
        'sealimage',
    ];

    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    /*
     * Moodle pluginfile URLs use this structure after the file area:
     *
     *     /itemid/optional/path/filename.ext
     *
     * For admin_setting_configstoredfile theme settings, itemid is normally 0.
     * The previous implementation treated itemid as part of the filepath,
     * which made Moodle look for the file in /0/ instead of / and returned 404.
     */
    if (count($args) < 2) {
        return false;
    }

    $itemid = clean_param(array_shift($args), PARAM_INT);
    $filename = array_pop($args);

    if ($filename === null || $filename === '') {
        return false;
    }

    $filepath = '/';

    if (!empty($args)) {
        $pathparts = array_map(static function($pathpart): string {
            return clean_param($pathpart, PARAM_PATH);
        }, $args);

        $filepath = '/' . implode('/', $pathparts) . '/';
        $filepath = preg_replace('#/+#', '/', $filepath);
    }

    $fs = get_file_storage();
    $file = $fs->get_file(
        $context->id,
        'theme_uckk',
        $filearea,
        $itemid,
        $filepath,
        $filename
    );

    if (!$file || $file->is_directory()) {
        return false;
    }

    $options = array_merge([
        'cacheability' => 'public',
        'immutable' => false,
    ], $options);

    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);

    return true;
}

/**
 * Get a UCKK theme setting safely.
 *
 * @param string $setting The setting name without theme prefix.
 * @param int $format Text format to apply.
 * @return string The setting value, or an empty string when unavailable.
 */
function theme_uckk_get_setting(string $setting, int $format = FORMAT_PLAIN): string {
    $theme = theme_config::load('uckk');

    if (empty($theme->settings) || !property_exists($theme->settings, $setting)) {
        return '';
    }

    $value = $theme->settings->{$setting};

    if ($value === null) {
        return '';
    }

    if (!is_string($value)) {
        $value = (string) $value;
    }

    if ($format === -1) {
        return $value;
    }

    return format_string($value, true, ['escape' => false]);
}

/**
 * Clean a preset file name.
 *
 * The preset setting must resolve to a local SCSS file name only. Directory
 * traversal and remote references are rejected.
 *
 * @param string $preset Preset setting value.
 * @return string Safe preset file name, or an empty string.
 */
function theme_uckk_clean_preset_name(string $preset): string {
    $preset = trim($preset);

    if ($preset === '') {
        return '';
    }

    $preset = basename($preset);

    if (!preg_match('/^[a-zA-Z0-9_-]+\.scss$/', $preset)) {
        return '';
    }

    return $preset;
}

/**
 * Convert a theme setting into a safe SCSS literal.
 *
 * Hex colors and font stacks are both supported. The function rejects
 * dangerous characters and falls back to the provided default when a value is
 * not acceptable.
 *
 * @param string $value Raw setting value.
 * @param string $default Fallback value.
 * @return string Safe SCSS value.
 */
function theme_uckk_scss_value(string $value, string $default): string {
    $value = trim($value);

    if ($value === '') {
        $value = $default;
    }

    if (preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value)) {
        return $value;
    }

    if (preg_match('/^[a-zA-Z0-9\s,"\-_]+$/', $value)) {
        return $value;
    }

    return $default;
}

