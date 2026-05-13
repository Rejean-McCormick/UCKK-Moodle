<?php
// This file is part of UCKK-Moodle - https://moodle.org/
//
// UCKK-Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// UCKK-Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with UCKK-Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Two-column layout for the UCKK theme.
 *
 * This layout is intended for general Moodle pages with a main content region
 * and one optional block region. It provides the visual page frame for the
 * Univers-Cité King Klown Moodle distribution, but it must remain purely
 * presentational.
 *
 * UCKK institutional workflows are handled by their own plugins:
 * - local_uckk for institutional registry and shared services.
 * - mod_uckkchallenge for Défis King Klown.
 * - mod_uckkassembly for Assemblées.
 * - mod_uckkarchive for Archives and Kristals.
 * - tool_uckkintegrity for Inquisiteur/integrity cases.
 * - report_uckk for reports.
 * - aiprovider_uckk for governed AI provider integration.
 *
 * @package    theme_uckk
 * @copyright  2026 Momus et Bouche Cousue
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$extraclasses = [
    'theme-uckk',
    'theme-uckk-layout-columns2',
];

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

$sidepreblocks = $OUTPUT->blocks('side-pre');
$hassidepre = strpos($sidepreblocks, 'data-block=') !== false;

$hasnavbar = empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar();
$hasfooter = empty($PAGE->layout_options['nofooter']);

$regionsclasses = [];
if ($hassidepre) {
    $regionsclasses[] = 'has-side-pre';
} else {
    $regionsclasses[] = 'no-side-pre';
}

?>
<!DOCTYPE html>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $bodyattributes; ?>>
<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<div id="page-wrapper" class="theme-uckk-page-wrapper d-print-block">

    <header id="page-header" class="theme-uckk-page-header navbar navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <div class="theme-uckk-page-branding d-flex align-items-center">
                <a class="theme-uckk-site-link navbar-brand mb-0" href="<?php echo $CFG->wwwroot; ?>">
                    <span class="theme-uckk-site-name">
                        <?php echo format_string($SITE->shortname, true, ['context' => context_system::instance()]); ?>
                    </span>
                </a>
            </div>

            <div class="theme-uckk-page-header-actions">
                <?php echo $OUTPUT->user_menu(); ?>
            </div>
        </div>
    </header>

    <div id="page" class="theme-uckk-page <?php echo implode(' ', $regionsclasses); ?>">

        <?php if ($hasnavbar) { ?>
            <div class="theme-uckk-navbar border-bottom bg-light">
                <div class="container-fluid">
                    <nav aria-label="<?php echo get_string('breadcrumb', 'moodle'); ?>">
                        <?php echo $OUTPUT->navbar(); ?>
                    </nav>
                </div>
            </div>
        <?php } ?>

        <div id="page-content" class="theme-uckk-page-content container-fluid py-4">
            <div class="row">

                <main id="region-main"
                    class="theme-uckk-main-region col-12 <?php echo $hassidepre ? 'col-lg-9' : 'col-lg-12'; ?>"
                    aria-label="<?php echo get_string('content'); ?>">

                    <span class="notifications" id="user-notifications">
                        <?php echo $OUTPUT->main_content(); ?>
                    </span>
                </main>

                <?php if ($hassidepre) { ?>
                    <aside id="block-region-side-pre"
                        class="theme-uckk-block-region col-12 col-lg-3 d-print-none"
                        data-region="blocks-column"
                        aria-label="<?php echo get_string('blocks'); ?>">
                        <?php echo $sidepreblocks; ?>
                    </aside>
                <?php } ?>

            </div>
        </div>

    </div>

    <?php if ($hasfooter) { ?>
        <footer id="page-footer" class="theme-uckk-page-footer border-top py-3">
            <div class="container-fluid">
                <div class="theme-uckk-footer-content">
                    <?php echo $OUTPUT->standard_footer_html(); ?>
                </div>
            </div>
        </footer>
    <?php } ?>

</div>

<?php echo $OUTPUT->standard_after_main_region_html(); ?>
<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>