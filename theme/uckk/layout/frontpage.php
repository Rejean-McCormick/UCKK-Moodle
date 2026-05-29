<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Frontpage layout adapter for the UCKK theme.
 *
 * The site root (/) is a public UCKK home gate, not Moodle's native
 * frontpage body. It must never expose Moodle's configured frontpage payload,
 * because that can be the full course list.
 *
 * The canonical public UCKK home controller is:
 * /local/uckk/index.php
 *
 * This layout renders the same local_uckk public home page while keeping
 * Moodle's main_content() placeholder hidden as a layout contract fallback.
 *
 * @package    theme_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$slug = 'home';

$PAGE->requires->css(new moodle_url('/local/uckk/styles.css'));

$definition = \local_uckk\local\public_pages::definition($slug);
$publiccontent = $OUTPUT->render(new \local_uckk\output\public_page($slug, $definition));

// Keep Moodle's required main content placeholder, but never expose it.
$moodlemaincontent = $OUTPUT->main_content();

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <?php echo $OUTPUT->standard_head_html(); ?>
</head>
<body <?php echo $OUTPUT->body_attributes(['theme-uckk', 'theme-uckk-public-layout--frontpage']); ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div id="page-wrapper" class="theme-uckk-public-frontpage-wrapper">
    <div id="page" class="theme-uckk-public-frontpage-page">
        <main id="page-content" class="theme-uckk-public-frontpage-content" aria-label="<?php echo s(get_string('home')); ?>">
            <section id="region-main" class="theme-uckk-public-frontpage-region">
                <?php echo $publiccontent; ?>

                <div class="theme-uckk-hidden-main-content" hidden aria-hidden="true">
                    <?php echo $moodlemaincontent; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>