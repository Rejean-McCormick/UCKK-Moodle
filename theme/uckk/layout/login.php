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
 * Login layout for theme_uckk.
 *
 * This layout owns only the visual shell around Moodle's standard login form.
 * It does not override the core login form, guest form, authentication logic,
 * session handling, token handling, redirects or permissions.
 *
 * The left panel is the target for theme_uckk/login_background, which selects
 * the configured day / between / night login image. The right panel renders
 * Moodle's normal login content through $OUTPUT->main_content().
 *
 * @package    theme_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$sitecontext = context_course::instance(SITEID);

$sitename = format_string(
    $SITE->fullname,
    true,
    ['context' => $sitecontext]
);

$bodyclasses = [
    'theme-uckk',
    'theme-uckk-login-layout',
];

$bodyattributes = $OUTPUT->body_attributes($bodyclasses);

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $bodyattributes; ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div id="page-wrapper" class="theme-uckk-login-page-wrapper">
    <div id="page" class="theme-uckk-login-page">
        <div id="page-content" class="theme-uckk-login-shell">

            <aside
                id="theme-uckk-login-visual"
                class="login-layout-left"
                data-region="theme-uckk-login-visual"
                aria-labelledby="theme-uckk-login-title"
            >
                <div class="login-layout-left__overlay" aria-hidden="true"></div>

                <div class="login-layout-left-content">
                    <p class="theme-uckk-eyebrow">
                        Univers-Cité King Klown
                    </p>

                    <h1 id="theme-uckk-login-title">
                        <?php echo $sitename; ?>
                    </h1>

                    <p class="login-layout-left__summary">
                        Campus pédagogique UCKK — espace de parcours, de cours,
                        de preuves et de participation institutionnelle.
                    </p>

                    <div class="login-layout-stats" aria-label="Repères institutionnels">
                        <p>
                            <strong>Rigueur.</strong>
                            Parcours documentés, traces conservées, reconnaissances internes.
                        </p>
                        <p>
                            <strong>Accès public.</strong>
                            Certains cours peuvent être ouverts aux visiteurs anonymes.
                        </p>
                    </div>
                </div>
            </aside>

            <main
                id="region-main"
                class="login-layout-right"
                data-region="theme-uckk-login-main"
                aria-label="<?php echo s(get_string('login')); ?>"
            >
                <div class="login-layout-right__inner">
                    <?php echo $OUTPUT->main_content(); ?>
                </div>
            </main>

        </div>
    </div>
</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>