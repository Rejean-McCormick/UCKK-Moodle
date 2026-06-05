<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Public Médiathèque page for UCKK.
 *
 * This controller is intentionally thin:
 * - it owns the public route `/local/uckk/mediatheque.php`;
 * - it uses the shared `local_uckk` public page shell;
 * - it uses the centralized public page definition registry;
 * - it passes initial explorer state to AMD;
 * - it can route `?item={uuid}` to a public detail DTO;
 * - it adds authenticated shortcut links without exposing private controls to guests;
 * - it does not query media tables directly;
 * - it does not decide access, visibility, cultural protocol, file access, or export rights.
 *
 * Media data and public filtering belong to `mod_uckkarchive`.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

global $PAGE, $OUTPUT, $USER;

$context = context_system::instance();
$pagekey = 'mediatheque';

// Site-wide public Médiathèque by default.
// `cmid = 0` and `archiveid = 0` mean: search all public, policy-filtered media.
$cmid = optional_param('cmid', 0, PARAM_INT);
$archiveid = optional_param('archiveid', 0, PARAM_INT);

$urlparams = [
    'q' => optional_param('q', '', PARAM_TEXT),
    'type' => optional_param('type', 'all', PARAM_ALPHANUMEXT),
    'mediatype' => optional_param('mediatype', 'all', PARAM_ALPHANUMEXT),
    'collection' => optional_param('collection', '', PARAM_ALPHANUMEXT),
    'tag' => optional_param('tag', '', PARAM_TEXT),
    'source' => optional_param('source', '', PARAM_ALPHANUMEXT),
    'advisory' => optional_param('advisory', 'all', PARAM_ALPHANUMEXT),
    'cultural' => optional_param('cultural', 'all', PARAM_ALPHANUMEXT),
    'audience' => optional_param('audience', 'all', PARAM_ALPHANUMEXT),
    'lang' => optional_param('lang', '', PARAM_ALPHANUMEXT),
    'validation' => optional_param('validation', 'all', PARAM_ALPHANUMEXT),
    'sort' => optional_param('sort', 'relevance', PARAM_ALPHANUMEXT),
    'page' => optional_param('page', 1, PARAM_INT),
    'perpage' => optional_param('perpage', 12, PARAM_INT),
    'item' => optional_param('item', '', PARAM_ALPHANUMEXT),
];

$isitemrequest = $urlparams['item'] !== '';

$urlvalues = array_merge([
    'cmid' => $cmid,
    'archiveid' => $archiveid,
], $urlparams);

$urlparamsfiltered = array_filter(
    $urlvalues,
    static function($value, string $key): bool {
        if ($value === '' || $value === null || $value === 'all') {
            return false;
        }

        if (($key === 'cmid' || $key === 'archiveid') && (int)$value === 0) {
            return false;
        }

        if ($key === 'page' && (int)$value === 1) {
            return false;
        }

        if ($key === 'perpage' && (int)$value === 12) {
            return false;
        }

        return true;
    },
    ARRAY_FILTER_USE_BOTH
);

$url = new moodle_url('/local/uckk/mediatheque.php', $urlparamsfiltered);

if (class_exists('\local_uckk\local\public_pages')) {
    \local_uckk\local\public_pages::setup_page($pagekey, $context);
} else {
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('mediatheque_title', 'local_uckk'));
    $PAGE->set_heading(get_string('mediatheque_title', 'local_uckk'));
    $PAGE->requires->css(new moodle_url('/local/uckk/styles.css'));
}

$PAGE->set_url($url);

$explorerid = html_writer::random_id('local-uckk-mediatheque-explorer-');

$page = max(1, $urlparams['page']);
$perpage = min(48, max(1, $urlparams['perpage']));

$filters = [
    'type' => $urlparams['type'],
    'mediatype' => $urlparams['mediatype'],
    'collection' => $urlparams['collection'],
    'tag' => $urlparams['tag'],
    'source' => $urlparams['source'],
    'advisory' => $urlparams['advisory'],
    'cultural' => $urlparams['cultural'],
    'audience' => $urlparams['audience'],
    'lang' => $urlparams['lang'],
    'validation' => $urlparams['validation'],
    'item' => $urlparams['item'],
];

$initialstate = [
    'rootId' => $explorerid,
    'service' => 'mod_uckkarchive_search_mediatheque',
    'cmid' => max(0, $cmid),
    'archiveid' => max(0, $archiveid),
    'query' => $urlparams['q'],
    'filters' => $filters,
    'page' => $page,
    'perpage' => $perpage,
    'sort' => $urlparams['sort'],
    'sitewide' => $cmid <= 0 && $archiveid <= 0,
];

$itempayload = [];
$mediathequeitem = [];
$mediathequeitemerror = '';

$stringorfallback = static function(string $identifier, string $fallback): string {
    return get_string_manager()->string_exists($identifier, 'local_uckk')
        ? get_string($identifier, 'local_uckk')
        : $fallback;
};

if ($isitemrequest) {
    $detailtype = $urlparams['type'] === 'all' ? 'media' : $urlparams['type'];
    $serviceclass = '\mod_uckkarchive\local\public_mediatheque_service';

    if (!class_exists($serviceclass)) {
        $mediathequeitemerror = $stringorfallback(
            'mediatheque_item_service_missing',
            'Le service public de détail de la Médiathèque n’est pas disponible.'
        );
    } else {
        $service = new $serviceclass();

        if (!method_exists($service, 'get_item')) {
            $mediathequeitemerror = $stringorfallback(
                'mediatheque_item_service_missing',
                'Le service public de détail de la Médiathèque n’est pas encore implémenté.'
            );
        } else {
            try {
                $detailrequest = [
                    'cmid' => max(0, $cmid),
                    'archiveid' => max(0, $archiveid),
                    'item' => $urlparams['item'],
                    'uuid' => $urlparams['item'],
                    'type' => $detailtype,
                ];

                $itempayload = $service->get_item($detailrequest, null, $USER ?? null);

                if (is_array($itempayload) && array_key_exists('item', $itempayload)) {
                    $mediathequeitem = is_array($itempayload['item']) ? $itempayload['item'] : [];
                } else {
                    $mediathequeitem = is_array($itempayload) ? $itempayload : [];
                    $itempayload = [
                        'item' => $mediathequeitem,
                    ];
                }

                if (empty($mediathequeitem)) {
                    $mediathequeitemerror = $stringorfallback(
                        'mediatheque_item_not_found',
                        'Ce contenu public est introuvable ou n’est pas disponible publiquement.'
                    );
                }
            } catch (Throwable $exception) {
                debugging(
                    'Public mediatheque item load failed: ' . $exception->getMessage(),
                    DEBUG_DEVELOPER
                );

                $mediathequeitemerror = $stringorfallback(
                    'mediatheque_item_error',
                    'Impossible de charger ce contenu public.'
                );
            }
        }
    }

    if (!empty($mediathequeitem['title'])) {
        $PAGE->set_title(format_string((string)$mediathequeitem['title']) . ' | ' . get_string('mediatheque_title', 'local_uckk'));
    }
}

// The explorer AMD is only needed for the search/listing view.
// Detail rendering is server-side and policy-filtered by mod_uckkarchive.
if (!$isitemrequest) {
    $PAGE->requires->js_call_amd('local_uckk/mediatheque_explorer', 'init', [$initialstate]);
}

echo $OUTPUT->header();

if (class_exists('\local_uckk\output\public_page')) {
    if (class_exists('\local_uckk\local\public_pages')) {
        $definition = \local_uckk\local\public_pages::definition($pagekey);
    } else {
        $definition = [];
    }

    $backparams = $urlparamsfiltered;
    unset($backparams['item']);

    $backurl = new moodle_url('/local/uckk/mediatheque.php', $backparams);

    // Keep the centralized page definition as the source of truth.
    // Only the runtime explorer/detail state is overridden here.
    $definition['mediatheque_explorer_id'] = $explorerid;
    $definition['mediatheque_initial_state'] = $initialstate;

    $definition['has_mediatheque_item'] = $isitemrequest && !empty($mediathequeitem);
    $definition['mediatheque_item'] = $mediathequeitem;
    $definition['mediatheque_item_payload'] = $itempayload;
    $definition['has_mediatheque_item_error'] = $isitemrequest && $mediathequeitemerror !== '';
    $definition['mediatheque_item_error'] = $mediathequeitemerror;
    $definition['mediatheque_item_back_url'] = $backurl->out(false);
    $definition['mediatheque_item_requested_uuid'] = $urlparams['item'];
    $definition['mediatheque_item_requested_type'] = $urlparams['type'] === 'all' ? 'media' : $urlparams['type'];

    // Search explorer is shown for normal listing only.
    // The detail page is rendered by the future `mediatheque_item` partial.
    $definition['has_mediatheque_explorer'] = !$isitemrequest;

    /*
     * Add authenticated Médiathèque shortcuts without adding private controls
     * to the static public page definition.
     *
     * The target route performs the real course/module resolution and checks
     * mod/uckkarchive:addmedia before redirecting to the canonical media editor.
     */
    if (isloggedin() && !isguestuser()) {
        if (!isset($definition['quicklinks']) || !is_array($definition['quicklinks'])) {
            $definition['quicklinks'] = [];
        }

        $definition['quicklinks'][] = [
            'label' => 'Ajouter un média',
            'description' => 'Ajouter directement un média à la Médiathèque centrale UCKK.',
            'url' => '/local/uckk/mediatheque_add.php',
        ];
    }

    echo $OUTPUT->render(new \local_uckk\output\public_page($pagekey, $definition));
} else {
    echo html_writer::start_div('local-uckk local-uckk-public-page local-uckk-public-page--mediatheque');

    echo html_writer::tag('p', get_string('mediatheque_eyebrow', 'local_uckk'), [
        'class' => 'local-uckk-public-eyebrow',
    ]);

    echo html_writer::tag('h1', get_string('mediatheque_title', 'local_uckk'), [
        'class' => 'local-uckk-public-title',
    ]);

    echo html_writer::tag('p', get_string('mediatheque_summary', 'local_uckk'), [
        'class' => 'local-uckk-public-summary',
    ]);

    if ($isitemrequest) {
        if (!empty($mediathequeitem)) {
            echo html_writer::start_div('local-uckk-mediatheque-item');

            echo html_writer::tag(
                'h2',
                format_string((string)($mediathequeitem['title'] ?? get_string('untitled', 'moodle'))),
                ['class' => 'local-uckk-mediatheque-item__title']
            );

            if (!empty($mediathequeitem['summary'])) {
                echo html_writer::tag(
                    'p',
                    format_text((string)$mediathequeitem['summary'], FORMAT_PLAIN),
                    ['class' => 'local-uckk-mediatheque-item__summary']
                );
            }

            echo html_writer::link(
                new moodle_url('/local/uckk/mediatheque.php', array_diff_key($urlparamsfiltered, ['item' => true])),
                'Retour à la recherche',
                ['class' => 'local-uckk-mediatheque-item__back']
            );

            echo html_writer::end_div();
        } else {
            echo html_writer::div(
                s($mediathequeitemerror),
                'local-uckk-public-notice local-uckk-public-notice--warning'
            );

            echo html_writer::link(
                new moodle_url('/local/uckk/mediatheque.php', array_diff_key($urlparamsfiltered, ['item' => true])),
                'Retour à la recherche',
                ['class' => 'local-uckk-mediatheque-item__back']
            );
        }
    } else {
        if (isloggedin() && !isguestuser()) {
            echo html_writer::link(
                new moodle_url('/local/uckk/mediatheque_add.php'),
                'Ajouter un média',
                [
                    'class' => 'btn btn-primary local-uckk-mediatheque-add',
                ]
            );
        }

        echo html_writer::div('', 'local-uckk-mediatheque-explorer', [
            'id' => $explorerid,
            'data-region' => 'mediatheque-explorer',
        ]);
    }

    echo html_writer::end_div();
}

echo $OUTPUT->footer();