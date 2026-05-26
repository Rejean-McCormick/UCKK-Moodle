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
 * Dedicated public-page layout for local_uckk institutional pages.
 *
 * This layout intentionally avoids Boost drawers, side regions and card wrappers.
 * It keeps the Moodle document shell, head assets, body hooks, navbar,
 * breadcrumb/page header and footer hooks, then lets local_uckk render the
 * public institutional content directly.
 *
 * @package    theme_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$bodyclasses = [
    'theme-uckk-public-layout',
    'theme-uckk-public-layout--local-uckk',
];

$bodyattributes = $OUTPUT->body_attributes($bodyclasses);

$templatecontext = [
    'sitename' => format_string(
        $SITE->shortname,
        true,
        ['context' => context_course::instance(SITEID)]
    ),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'hasnavbar' => empty($PAGE->layout_options['nonavbar']),
    'hasfooter' => empty($PAGE->layout_options['nofooter']),
];

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

<div id="page-wrapper" class="theme-uckk-public-page-wrapper">
    <div id="page" class="theme-uckk-public-page">

        <header id="page-header" class="theme-uckk-public-header">
            <?php if ($templatecontext['hasnavbar']) { ?>
                <div id="page-navbar" class="theme-uckk-public-navbar">
                    <nav aria-label="<?php echo get_string('breadcrumb', 'access'); ?>">
                        <?php echo $OUTPUT->navbar(); ?>
                    </nav>
                </div>
            <?php } ?>

            <div class="theme-uckk-public-heading">
                <div class="page-context-header">
                    <div class="page-header-headings">
                        <h1><?php echo $OUTPUT->page_heading(); ?></h1>
                    </div>
                </div>
            </div>
        </header>

        <main id="page-content" class="theme-uckk-public-content">
            <div id="region-main-box" class="theme-uckk-public-region-box">
                <section id="region-main" class="theme-uckk-public-region" aria-label="<?php echo get_string('content'); ?>">
                    <?php echo $OUTPUT->main_content(); ?>
                </section>
            </div>
        </main>

        <?php if ($templatecontext['hasfooter']) { ?>
            <footer id="page-footer" class="theme-uckk-public-footer">
                <?php echo $OUTPUT->standard_footer_html(); ?>
            </footer>
        <?php } ?>
    </div>
</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>