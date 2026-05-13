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

    return file_get_contents($presetpath);
}

/**
 * Return SCSS variables injected before the main SCSS is compiled.
 *
 * These variables express the UCKK visual canon:
 *
 * - deep black: stage, seriousness, authority;
 * - ivory: paper, knowledge, readability;
 * - cardinal red: theatre, energy, signal;
 * - aged gold: crest, prestige, renewed tradition;
 * - night blue: intelligence, depth;
 * - acid green: experimentation and living systems.
 *
 * Administrators may override the values from theme settings when those
 * settings exist. Defaults are safe and self-contained so the theme remains
 * installable before full configuration.
 *
 * @param theme_config $theme The theme configuration object.
 * @return string SCSS variable declarations.
 */
function theme_uckk_get_pre_scss(theme_config $theme): string {
    $defaults = [
        'uckkcolorblack' => '#070707',
        'uckkcolorivory' => '#f7f1df',
        'uckkcolorred' => '#9f1730',
        'uckkcolorgold' => '#b28a3e',
        'uckkcolorblue' => '#111a33',
        'uckkcolorgreen' => '#8cc63f',
        'uckkfontinstitutional' => 'Georgia, "Times New Roman", serif',
        'uckkfontdisplay' => '"Trebuchet MS", Arial, sans-serif',
        'uckkfonttechnical' => '"Courier New", monospace',
        'uckkfontbody' => 'Inter, "Helvetica Neue", Arial, sans-serif',
    ];

    $scss = [];

    foreach ($defaults as $settingname => $defaultvalue) {
        $value = theme_uckk_get_setting($settingname);

        if ($value === '') {
            $value = $defaultvalue;
        }

        $variable = str_replace('uckk', 'uckk-', $settingname);
        $scss[] = '$' . $variable . ': ' . theme_uckk_scss_value($value, $defaultvalue) . ';';
    }

    $brandcolor = theme_uckk_get_setting('brandcolor');

    if ($brandcolor === '') {
        $brandcolor = $defaults['uckkcolorred'];
    }

    $scss[] = '$primary: ' . theme_uckk_scss_value($brandcolor, $defaults['uckkcolorred']) . ';';
    $scss[] = '$body-bg: ' . theme_uckk_scss_value($defaults['uckkcolorivory'], $defaults['uckkcolorivory']) . ';';
    $scss[] = '$body-color: ' . theme_uckk_scss_value($defaults['uckkcolorblack'], $defaults['uckkcolorblack']) . ';';

    return implode("\n", $scss) . "\n";
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
    $scss[] = '.uckk-integrity-signal { border-inline-start: .25rem solid $uckk-colorred; }';
    $scss[] = '.uckk-assembly-signal { border-inline-start: .25rem solid $uckk-colorgold; }';
    $scss[] = '.uckk-archive-signal { border-inline-start: .25rem solid $uckk-colorblue; }';
    $scss[] = '.uckk-experiment-signal { border-inline-start: .25rem solid $uckk-colorgreen; }';

    $rawscss = theme_uckk_get_setting('rawscss', FORMAT_RAW);

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
 * - heroimage
 * - sealimage
 *
 * These file areas are expected to be used by admin_setting_configstoredfile
 * settings in theme/uckk/settings.php.
 *
 * @param stdClass $course Course object.
 * @param stdClass|null $cm Course module object, unused for theme files.
 * @param context $context Moodle context.
 * @param string $filearea Requested file area.
 * @param array $args Request path arguments.
 * @param bool $forcedownload Whether download should be forced.
 * @param array $options Additional file serving options.
 * @return bool False when the file is not found or access is denied.
 */
function theme_uckk_pluginfile(
    stdClass $course,
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
        'heroimage',
        'sealimage',
    ];

    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    if (empty($args)) {
        return false;
    }

    $filename = array_pop($args);

    if ($filename === null || $filename === '') {
        return false;
    }

    $filepath = '/';

    if (!empty($args)) {
        $filepath = '/' . implode('/', array_map('clean_param', $args, array_fill(0, count($args), PARAM_PATH))) . '/';
        $filepath = preg_replace('#/+#', '/', $filepath);
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'theme_uckk', $filearea, 0, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    $options = array_merge([
        'cacheability' => 'public',
        'immutable' => false,
    ], $options);

    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
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

    if ($format === FORMAT_RAW) {
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