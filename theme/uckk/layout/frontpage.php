<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Frontpage layout for the UCKK theme.
 *
 * This layout is the public campus gate for UCKK-Moodle.
 *
 * It is intentionally presentation-only:
 * - no grading logic;
 * - no permission decisions beyond Moodle rendering conventions;
 * - no challenge workflow logic;
 * - no assembly workflow logic;
 * - no archive validation logic;
 * - no integrity case logic;
 * - no AI authority decision.
 *
 * @package    theme_uckk
 * @copyright  2026 Réjean McCormick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$extraclasses = [
    'theme-uckk',
    'theme-uckk-frontpage',
];

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

$siteurl = new moodle_url('/');
$coursesurl = new moodle_url('/course/');
$dashboardurl = new moodle_url('/my/');
$loginurl = get_login_url();

$programsurl = new moodle_url('/local/uckk/programs.php');
$pathwaysurl = new moodle_url('/local/uckk/pathways.php');
$canonurl = new moodle_url('/local/uckk/canon.php');
$reportsurl = new moodle_url('/report/uckk/index.php');

$challengeurl = new moodle_url('/mod/uckkchallenge/index.php');
$assemblyurl = new moodle_url('/mod/uckkassembly/index.php');
$archiveurl = new moodle_url('/mod/uckkarchive/index.php');
$integrityurl = new moodle_url('/admin/tool/uckkintegrity/index.php');

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = trim($blockshtml) !== '';

$maincontent = $OUTPUT->main_content();

$herotitle = get_string('frontpageherotitle', 'theme_uckk');
$herosubtitle = get_string('frontpageherosubtitle', 'theme_uckk');
$herosummary = get_string('frontpageherosummary', 'theme_uckk');

$boundarytitle = get_string('frontpageboundarytitle', 'theme_uckk');
$boundarytext = get_string('frontpageboundarytext', 'theme_uckk');

$integritytext = get_string('frontpageintegritytext', 'theme_uckk');
$assemblytext = get_string('frontpageassemblytext', 'theme_uckk');
$archivetext = get_string('frontpagearchivetext', 'theme_uckk');

$nonaccreditationnotice = get_string('frontpagenonaccreditationnotice', 'theme_uckk');

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

<div id="page-wrapper" class="d-print-block">

    <div>
        <?php echo $OUTPUT->navbar(); ?>
    </div>

    <div id="page" class="container-fluid d-print-block">

        <header id="page-header" class="uckk-frontpage-header py-5" role="banner">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">

                        <div class="uckk-frontpage-kicker text-uppercase mb-2">
                            <?php echo format_string(get_string('pluginname', 'theme_uckk')); ?>
                        </div>

                        <h1 class="display-4 uckk-frontpage-title mb-3">
                            <?php echo format_string($herotitle); ?>
                        </h1>

                        <p class="lead uckk-frontpage-subtitle mb-3">
                            <?php echo format_text($herosubtitle, FORMAT_PLAIN); ?>
                        </p>

                        <div class="uckk-frontpage-summary mb-4">
                            <?php echo format_text($herosummary, FORMAT_MARKDOWN); ?>
                        </div>

                        <div class="uckk-frontpage-actions d-flex flex-wrap">
                            <?php if (isloggedin() && !isguestuser()) { ?>
                                <a class="btn btn-primary btn-lg mr-2 mb-2" href="<?php echo $dashboardurl; ?>">
                                    <?php echo get_string('frontpageactiondashboard', 'theme_uckk'); ?>
                                </a>
                            <?php } else { ?>
                                <a class="btn btn-primary btn-lg mr-2 mb-2" href="<?php echo $loginurl; ?>">
                                    <?php echo get_string('login'); ?>
                                </a>
                            <?php } ?>

                            <a class="btn btn-outline-primary btn-lg mr-2 mb-2" href="<?php echo $coursesurl; ?>">
                                <?php echo get_string('frontpageactioncourses', 'theme_uckk'); ?>
                            </a>

                            <a class="btn btn-outline-secondary btn-lg mb-2" href="<?php echo $programsurl; ?>">
                                <?php echo get_string('frontpageactionprograms', 'theme_uckk'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4 mt-4 mt-lg-0">
                        <section class="card uckk-frontpage-boundary-card" aria-labelledby="uckk-frontpage-boundary-title">
                            <div class="card-body">
                                <h2 id="uckk-frontpage-boundary-title" class="h5 card-title">
                                    <?php echo format_string($boundarytitle); ?>
                                </h2>
                                <div class="card-text">
                                    <?php echo format_text($boundarytext, FORMAT_MARKDOWN); ?>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </header>

        <main id="page-content" class="pb-5">
            <div id="region-main-box" class="container">
                <div class="row">

                    <section id="region-main"
                             class="<?php echo $hasblocks ? 'col-12 col-lg-9' : 'col-12'; ?>"
                             aria-label="<?php echo get_string('content'); ?>">

                        <span class="notifications" id="user-notifications">
                            <?php echo $OUTPUT->context_header(); ?>
                            <?php echo $OUTPUT->page_heading_menu(); ?>
                            <?php echo $OUTPUT->full_header(); ?>
                            <?php echo $OUTPUT->course_content_header(); ?>
                            <?php echo $OUTPUT->main_content(); ?>
                        </span>

                        <section class="uckk-frontpage-campus mt-5" aria-labelledby="uckk-campus-entry-title">
                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                                <div>
                                    <h2 id="uckk-campus-entry-title" class="h3 mb-1">
                                        <?php echo get_string('frontpagecampustitle', 'theme_uckk'); ?>
                                    </h2>
                                    <p class="text-muted mb-0">
                                        <?php echo get_string('frontpagecampussubtitle', 'theme_uckk'); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-xl-4 mb-3">
                                    <?php
                                    echo $OUTPUT->render_from_template('theme_uckk/uckk_card', [
                                        'type' => 'programs',
                                        'id' => 'uckk-frontpage-programs',
                                        'title' => get_string('frontpagecardprogramstitle', 'theme_uckk'),
                                        'subtitle' => get_string('frontpagecardprogramssubtitle', 'theme_uckk'),
                                        'bodyhtml' => format_text(
                                            get_string('frontpagecardprogramsbody', 'theme_uckk'),
                                            FORMAT_MARKDOWN
                                        ),
                                        'url' => $programsurl->out(false),
                                        'urltext' => get_string('frontpagecardprogramsaction', 'theme_uckk'),
                                        'icon' => 'icon',
                                        'iconcomponent' => 'theme_uckk',
                                        'iconalt' => '',
                                        'level' => 'UCKK',
                                        'actions' => [
                                            [
                                                'url' => $programsurl->out(false),
                                                'label' => get_string('frontpagecardprogramsaction', 'theme_uckk'),
                                                'classes' => 'btn btn-primary',
                                            ],
                                        ],
                                    ]);
                                    ?>
                                </div>

                                <div class="col-md-6 col-xl-4 mb-3">
                                    <?php
                                    echo $OUTPUT->render_from_template('theme_uckk/uckk_card', [
                                        'type' => 'pathways',
                                        'id' => 'uckk-frontpage-pathways',
                                        'title' => get_string('frontpagecardpathwaystitle', 'theme_uckk'),
                                        'subtitle' => get_string('frontpagecardpathwayssubtitle', 'theme_uckk'),
                                        'bodyhtml' => format_text(
                                            get_string('frontpagecardpathwaysbody', 'theme_uckk'),
                                            FORMAT_MARKDOWN
                                        ),
                                        'url' => $pathwaysurl->out(false),
                                        'urltext' => get_string('frontpagecardpathwaysaction', 'theme_uckk'),
                                        'icon' => 'icon',
                                        'iconcomponent' => 'theme_uckk',
                                        'iconalt' => '',
                                        'level' => get_string('frontpagelevelpathway', 'theme_uckk'),
                                        'actions' => [
                                            [
                                                'url' => $pathwaysurl->out(false),
                                                'label' => get_string('frontpagecardpathwaysaction', 'theme_uckk'),
                                                'classes' => 'btn btn-primary',
                                            ],
                                        ],
                                    ]);
                                    ?>
                                </div>

                                <div class="col-md-6 col-xl-4 mb-3">
                                    <?php
                                    echo $OUTPUT->render_from_template('theme_uckk/uckk_card', [
                                        'type' => 'canon',
                                        'id' => 'uckk-frontpage-canon',
                                        'title' => get_string('frontpagecardcanontitle', 'theme_uckk'),
                                        'subtitle' => get_string('frontpagecardcanonsubtitle', 'theme_uckk'),
                                        'bodyhtml' => format_text(
                                            get_string('frontpagecardcanonbody', 'theme_uckk'),
                                            FORMAT_MARKDOWN
                                        ),
                                        'url' => $canonurl->out(false),
                                        'urltext' => get_string('frontpagecardcanonaction', 'theme_uckk'),
                                        'icon' => 'archive',
                                        'iconcomponent' => 'theme_uckk',
                                        'iconalt' => '',
                                        'level' => get_string('frontpagelevelcanon', 'theme_uckk'),
                                        'actions' => [
                                            [
                                                'url' => $canonurl->out(false),
                                                'label' => get_string('frontpagecardcanonaction', 'theme_uckk'),
                                                'classes' => 'btn btn-primary',
                                            ],
                                        ],
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </section>

                        <section class="uckk-frontpage-pillars mt-5" aria-labelledby="uckk-frontpage-pillars-title">
                            <h2 id="uckk-frontpage-pillars-title" class="h3 mb-3">
                                <?php echo get_string('frontpagepillarstitle', 'theme_uckk'); ?>
                            </h2>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <?php
                                    echo $OUTPUT->render_from_template('theme_uckk/uckk_card', [
                                        'type' => 'challenge',
                                        'id' => 'uckk-frontpage-challenge',
                                        'title' => get_string('frontpagecardchallengetitle', 'theme_uckk'),
                                        'subtitle' => get_string('frontpagecardchallengesubtitle', 'theme_uckk'),
                                        'bodyhtml' => format_text(
                                            get_string('frontpagecardchallengebody', 'theme_uckk'),
                                            FORMAT_MARKDOWN
                                        ),
                                        'url' => $challengeurl->out(false),
                                        'urltext' => get_string('frontpagecardchallengeaction', 'theme_uckk'),
                                        'icon' => 'challenge',
                                        'iconcomponent' => 'theme_uckk',
                                        'iconalt' => '',
                                        'level' => get_string('frontpagelevelchallenge', 'theme_uckk'),
                                        'showboundarynote' => true,
                                        'boundarynote' => get_string('frontpagecardchallengeboundary', 'theme_uckk'),
                                        'actions' => [
                                            [
                                                'url' => $challengeurl->out(false),
                                                'label' => get_string('frontpagecardchallengeaction', 'theme_uckk'),
                                                'classes' => 'btn btn-outline-primary',
                                            ],
                                        ],
                                    ]);
                                    ?>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <?php
                                    echo $OUTPUT->render_from_template('theme_uckk/uckk_card', [
                                        'type' => 'assembly',
                                        'id' => 'uckk-frontpage-assembly',
                                        'title' => get_string('frontpagecardassemblytitle', 'theme_uckk'),
                                        'subtitle' => get_string('frontpagecardassemblysubtitle', 'theme_uckk'),
                                        'bodyhtml' => format_text($assemblytext, FORMAT_MARKDOWN),
                                        'url' => $assemblyurl->out(false),
                                        'urltext' => get_string('frontpagecardassemblyaction', 'theme_uckk'),
                                        'icon' => 'assembly',
                                        'iconcomponent' => 'theme_uckk',
                                        'iconalt' => '',
                                        'level' => get_string('frontpagelevelassembly', 'theme_uckk'),
                                        'actions' => [
                                            [
                                                'url' => $assemblyurl->out(false),
                                                'label' => get_string('frontpagecardassemblyaction', 'theme_uckk'),
                                                'classes' => 'btn btn-outline-primary',
                                            ],
                                        ],
                                    ]);
                                    ?>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <?php
                                    echo $OUTPUT->render_from_template('theme_uckk/uckk_card', [
                                        'type' => 'archive',
                                        'id' => 'uckk-frontpage-archive',
                                        'title' => get_string('frontpagecardarchivetitle', 'theme_uckk'),
                                        'subtitle' => get_string('frontpagecardarchivesubtitle', 'theme_uckk'),
                                        'bodyhtml' => format_text($archivetext, FORMAT_MARKDOWN),
                                        'url' => $archiveurl->out(false),
                                        'urltext' => get_string('frontpagecardarchiveaction', 'theme_uckk'),
                                        'icon' => 'archive',
                                        'iconcomponent' => 'theme_uckk',
                                        'iconalt' => '',
                                        'level' => get_string('frontpagelevelarchive', 'theme_uckk'),
                                        'actions' => [
                                            [
                                                'url' => $archiveurl->out(false),
                                                'label' => get_string('frontpagecardarchiveaction', 'theme_uckk'),
                                                'classes' => 'btn btn-outline-primary',
                                            ],
                                        ],
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </section>

                        <section class="uckk-frontpage-integrity mt-5" aria-labelledby="uckk-frontpage-integrity-title">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-lg-8">
                                            <h2 id="uckk-frontpage-integrity-title" class="h3">
                                                <?php echo get_string('frontpageintegritytitle', 'theme_uckk'); ?>
                                            </h2>
                                            <div class="mb-0">
                                                <?php echo format_text($integritytext, FORMAT_MARKDOWN); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 mt-3 mt-lg-0 text-lg-right">
                                            <a class="btn btn-outline-secondary" href="<?php echo $integrityurl; ?>">
                                                <?php echo get_string('frontpageintegrityaction', 'theme_uckk'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="uckk-frontpage-nonaccreditation mt-4" aria-label="<?php echo get_string('frontpagenonaccreditationtitle', 'theme_uckk'); ?>">
                            <div class="alert alert-light border mb-0">
                                <strong><?php echo get_string('frontpagenonaccreditationtitle', 'theme_uckk'); ?></strong>
                                <div class="mt-1">
                                    <?php echo format_text($nonaccreditationnotice, FORMAT_MARKDOWN); ?>
                                </div>
                            </div>
                        </section>

                        <?php echo $OUTPUT->course_content_footer(); ?>

                    </section>

                    <?php if ($hasblocks) { ?>
                        <aside id="block-region-side-pre"
                               class="col-12 col-lg-3 d-print-none"
                               data-blockregion="side-pre"
                               data-droptarget="1"
                               aria-label="<?php echo get_string('blocks'); ?>">
                            <?php echo $blockshtml; ?>
                        </aside>
                    <?php } ?>

                </div>
            </div>
        </main>

    </div>

    <footer id="page-footer" class="py-4 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="small text-muted">
                        <?php echo get_string('frontpagefooterformula', 'theme_uckk'); ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    <a class="small mr-3" href="<?php echo $reportsurl; ?>">
                        <?php echo get_string('frontpagefooterreports', 'theme_uckk'); ?>
                    </a>
                    <a class="small" href="<?php echo $siteurl; ?>">
                        <?php echo format_string($SITE->shortname); ?>
                    </a>
                </div>
            </div>

            <?php echo $OUTPUT->standard_footer_html(); ?>
        </div>
    </footer>

    <?php echo $OUTPUT->standard_after_main_region_html(); ?>

</div>

<?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>