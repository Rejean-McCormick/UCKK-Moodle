<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Admin settings for the UCKK Archive activity module.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // -------------------------------------------------------------------------
    // General settings.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/generalsettings',
        get_string('settings:general', 'mod_uckkarchive'),
        get_string('settings:general_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enabled',
        get_string('settings:enabled', 'mod_uckkarchive'),
        get_string('settings:enabled_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkarchive/defaultvisibility',
        get_string('settings:defaultvisibility', 'mod_uckkarchive'),
        get_string('settings:defaultvisibility_desc', 'mod_uckkarchive'),
        'course',
        [
            'private' => get_string('visibility:private', 'mod_uckkarchive'),
            'user' => get_string('visibility:user', 'mod_uckkarchive'),
            'group' => get_string('visibility:group', 'mod_uckkarchive'),
            'course' => get_string('visibility:course', 'mod_uckkarchive'),
            'cohort' => get_string('visibility:cohort', 'mod_uckkarchive'),
            'program' => get_string('visibility:program', 'mod_uckkarchive'),
            'institution' => get_string('visibility:institution', 'mod_uckkarchive'),
            'public' => get_string('visibility:public', 'mod_uckkarchive'),
            'restricted' => get_string('visibility:restricted', 'mod_uckkarchive'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'mod_uckkarchive'),
            'hidden' => get_string('visibility:hidden', 'mod_uckkarchive'),
            'archived' => get_string('visibility:archived', 'mod_uckkarchive'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkarchive/defaultprovenance',
        get_string('settings:defaultprovenance', 'mod_uckkarchive'),
        get_string('settings:defaultprovenance_desc', 'mod_uckkarchive'),
        'human',
        [
            'human' => get_string('provenance:human', 'mod_uckkarchive'),
            'ai_assisted' => get_string('provenance:ai_assisted', 'mod_uckkarchive'),
            'imported' => get_string('provenance:imported', 'mod_uckkarchive'),
            'system' => get_string('provenance:system', 'mod_uckkarchive'),
            'archive' => get_string('provenance:archive', 'mod_uckkarchive'),
            'assembly' => get_string('provenance:assembly', 'mod_uckkarchive'),
            'challenge' => get_string('provenance:challenge', 'mod_uckkarchive'),
            'integrity' => get_string('provenance:integrity', 'mod_uckkarchive'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkarchive/defaultvalidationstate',
        get_string('settings:defaultvalidationstate', 'mod_uckkarchive'),
        get_string('settings:defaultvalidationstate_desc', 'mod_uckkarchive'),
        'unverified',
        [
            'unverified' => get_string('validation:unverified', 'mod_uckkarchive'),
            'human_reviewed' => get_string('validation:human_reviewed', 'mod_uckkarchive'),
            'verified' => get_string('validation:verified', 'mod_uckkarchive'),
            'contested' => get_string('validation:contested', 'mod_uckkarchive'),
            'invalidated' => get_string('validation:invalidated', 'mod_uckkarchive'),
            'archived' => get_string('validation:archived', 'mod_uckkarchive'),
        ]
    ));

    // -------------------------------------------------------------------------
    // Archive item policy.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/itempolicysettings',
        get_string('settings:itempolicy', 'mod_uckkarchive'),
        get_string('settings:itempolicy_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requireprovenance',
        get_string('settings:requireprovenance', 'mod_uckkarchive'),
        get_string('settings:requireprovenance_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requirecontext',
        get_string('settings:requirecontext', 'mod_uckkarchive'),
        get_string('settings:requirecontext_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requirevisibility',
        get_string('settings:requirevisibility', 'mod_uckkarchive'),
        get_string('settings:requirevisibility_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requirehumanvalidation',
        get_string('settings:requirehumanvalidation', 'mod_uckkarchive'),
        get_string('settings:requirehumanvalidation_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/allowpublicitems',
        get_string('settings:allowpublicitems', 'mod_uckkarchive'),
        get_string('settings:allowpublicitems_desc', 'mod_uckkarchive'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/protectrestricteditems',
        get_string('settings:protectrestricteditems', 'mod_uckkarchive'),
        get_string('settings:protectrestricteditems_desc', 'mod_uckkarchive'),
        1
    ));

    // -------------------------------------------------------------------------
    // Validation and revision policy.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/validationsettings',
        get_string('settings:validation', 'mod_uckkarchive'),
        get_string('settings:validation_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enablevalidation',
        get_string('settings:enablevalidation', 'mod_uckkarchive'),
        get_string('settings:enablevalidation_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enablerevisions',
        get_string('settings:enablerevisions', 'mod_uckkarchive'),
        get_string('settings:enablerevisions_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requirechangereason',
        get_string('settings:requirechangereason', 'mod_uckkarchive'),
        get_string('settings:requirechangereason_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/lockvalidateditems',
        get_string('settings:lockvalidateditems', 'mod_uckkarchive'),
        get_string('settings:lockvalidateditems_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/pausevalidationonintegritycase',
        get_string('settings:pausevalidationonintegritycase', 'mod_uckkarchive'),
        get_string('settings:pausevalidationonintegritycase_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->hide_if(
        'mod_uckkarchive/lockvalidateditems',
        'mod_uckkarchive/enablerevisions',
        'eq',
        0
    );

    // -------------------------------------------------------------------------
    // Kristal policy.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/kristalsettings',
        get_string('settings:kristal', 'mod_uckkarchive'),
        get_string('settings:kristal_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enablekristals',
        get_string('settings:enablekristals', 'mod_uckkarchive'),
        get_string('settings:enablekristals_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/kristalsrequirevalidation',
        get_string('settings:kristalsrequirevalidation', 'mod_uckkarchive'),
        get_string('settings:kristalsrequirevalidation_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkarchive/maxkristalitems',
        get_string('settings:maxkristalitems', 'mod_uckkarchive'),
        get_string('settings:maxkristalitems_desc', 'mod_uckkarchive'),
        50,
        PARAM_INT
    ));

    $settings->hide_if(
        'mod_uckkarchive/kristalsrequirevalidation',
        'mod_uckkarchive/enablekristals',
        'eq',
        0
    );

    $settings->hide_if(
        'mod_uckkarchive/maxkristalitems',
        'mod_uckkarchive/enablekristals',
        'eq',
        0
    );

    // -------------------------------------------------------------------------
    // Export policy.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/exportsettings',
        get_string('settings:export', 'mod_uckkarchive'),
        get_string('settings:export_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enableexports',
        get_string('settings:enableexports', 'mod_uckkarchive'),
        get_string('settings:enableexports_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkarchive/defaultexportformat',
        get_string('settings:defaultexportformat', 'mod_uckkarchive'),
        get_string('settings:defaultexportformat_desc', 'mod_uckkarchive'),
        'json',
        [
            'json' => get_string('exportformat:json', 'mod_uckkarchive'),
            'html' => get_string('exportformat:html', 'mod_uckkarchive'),
            'csv' => get_string('exportformat:csv', 'mod_uckkarchive'),
            'mbz_manifest' => get_string('exportformat:mbz_manifest', 'mod_uckkarchive'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/includefilesinexports',
        get_string('settings:includefilesinexports', 'mod_uckkarchive'),
        get_string('settings:includefilesinexports_desc', 'mod_uckkarchive'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/blockrestrictedexports',
        get_string('settings:blockrestrictedexports', 'mod_uckkarchive'),
        get_string('settings:blockrestrictedexports_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkarchive/maxexportitems',
        get_string('settings:maxexportitems', 'mod_uckkarchive'),
        get_string('settings:maxexportitems_desc', 'mod_uckkarchive'),
        500,
        PARAM_INT
    ));

    $settings->hide_if(
        'mod_uckkarchive/defaultexportformat',
        'mod_uckkarchive/enableexports',
        'eq',
        0
    );

    $settings->hide_if(
        'mod_uckkarchive/includefilesinexports',
        'mod_uckkarchive/enableexports',
        'eq',
        0
    );

    $settings->hide_if(
        'mod_uckkarchive/blockrestrictedexports',
        'mod_uckkarchive/enableexports',
        'eq',
        0
    );

    $settings->hide_if(
        'mod_uckkarchive/maxexportitems',
        'mod_uckkarchive/enableexports',
        'eq',
        0
    );

    // -------------------------------------------------------------------------
    // Scheduled tasks.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/tasksettings',
        get_string('settings:tasks', 'mod_uckkarchive'),
        get_string('settings:tasks_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/validatependingitems',
        get_string('settings:validatependingitems', 'mod_uckkarchive'),
        get_string('settings:validatependingitems_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/generateexports',
        get_string('settings:generateexports', 'mod_uckkarchive'),
        get_string('settings:generateexports_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkarchive/taskbatchsize',
        get_string('settings:taskbatchsize', 'mod_uckkarchive'),
        get_string('settings:taskbatchsize_desc', 'mod_uckkarchive'),
        50,
        PARAM_INT
    ));

    // -------------------------------------------------------------------------
    // AI governance.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/aisettings',
        get_string('settings:ai', 'mod_uckkarchive'),
        get_string('settings:ai_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/allowaiassistance',
        get_string('settings:allowaiassistance', 'mod_uckkarchive'),
        get_string('settings:allowaiassistance_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/logaiuse',
        get_string('settings:logaiuse', 'mod_uckkarchive'),
        get_string('settings:logaiuse_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requireaiuncertaintylabel',
        get_string('settings:requireaiuncertaintylabel', 'mod_uckkarchive'),
        get_string('settings:requireaiuncertaintylabel_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/allowaivalidation',
        get_string('settings:allowaivalidation', 'mod_uckkarchive'),
        get_string('settings:allowaivalidation_desc', 'mod_uckkarchive'),
        0
    ));

    $settings->hide_if(
        'mod_uckkarchive/allowaivalidation',
        'mod_uckkarchive/requirehumanvalidation',
        'eq',
        1
    );
}
```

Add these strings to `mod/uckkarchive/lang/en/uckkarchive.php`:

```php id="zgfi4s"
$string['settings:general'] = 'General settings';
$string['settings:general_desc'] = 'Configure the general behaviour of UCKK Archives.';
$string['settings:enabled'] = 'Enable UCKK archives';
$string['settings:enabled_desc'] = 'Allow UCKK Archive activities to be used.';
$string['settings:defaultvisibility'] = 'Default visibility';
$string['settings:defaultvisibility_desc'] = 'Default visibility assigned to new archive records.';
$string['settings:defaultprovenance'] = 'Default provenance';
$string['settings:defaultprovenance_desc'] = 'Default provenance assigned to new archive records.';
$string['settings:defaultvalidationstate'] = 'Default validation state';
$string['settings:defaultvalidationstate_desc'] = 'Default validation state assigned to new archive records.';

$string['settings:itempolicy'] = 'Archive item policy';
$string['settings:itempolicy_desc'] = 'Configure provenance, visibility, public access, and restricted archive behaviour.';
$string['settings:requireprovenance'] = 'Require provenance';
$string['settings:requireprovenance_desc'] = 'Require archive items and proofs to include provenance information.';
$string['settings:requirecontext'] = 'Require context';
$string['settings:requirecontext_desc'] = 'Require archive items to identify their Moodle or UCKK context.';
$string['settings:requirevisibility'] = 'Require visibility';
$string['settings:requirevisibility_desc'] = 'Require archive items to carry an explicit visibility value.';
$string['settings:requirehumanvalidation'] = 'Require human validation';
$string['settings:requirehumanvalidation_desc'] = 'Require authorised human validation before archive records can become verified.';
$string['settings:allowpublicitems'] = 'Allow public archive items';
$string['settings:allowpublicitems_desc'] = 'Allow archive items to be marked public when permitted by capability and workflow rules.';
$string['settings:protectrestricteditems'] = 'Protect restricted items';
$string['settings:protectrestricteditems_desc'] = 'Keep restricted and restricted-integrity items hidden from ordinary archive views and exports.';

$string['settings:validation'] = 'Validation and revision';
$string['settings:validation_desc'] = 'Configure archive validation, revision, and integrity pause behaviour.';
$string['settings:enablevalidation'] = 'Enable validation workflow';
$string['settings:enablevalidation_desc'] = 'Allow archive items to move through validation states.';
$string['settings:enablerevisions'] = 'Enable revisions';
$string['settings:enablerevisions_desc'] = 'Allow validated archive items to be revised through versioned records.';
$string['settings:requirechangereason'] = 'Require change reason';
$string['settings:requirechangereason_desc'] = 'Require a reason when revising, invalidating, or archiving archive records.';
$string['settings:lockvalidateditems'] = 'Lock validated items';
$string['settings:lockvalidateditems_desc'] = 'Prevent direct editing of validated archive items. Changes must create revisions.';
$string['settings:pausevalidationonintegritycase'] = 'Pause validation during integrity cases';
$string['settings:pausevalidationonintegritycase_desc'] = 'Pause archive validation when a related integrity case is open.';

$string['settings:kristal'] = 'Kristal settings';
$string['settings:kristal_desc'] = 'Configure Kristal records as special archive crystallisations.';
$string['settings:enablekristals'] = 'Enable Kristals';
$string['settings:enablekristals_desc'] = 'Allow archive records to be promoted or grouped as Kristals.';
$string['settings:kristalsrequirevalidation'] = 'Kristals require validation';
$string['settings:kristalsrequirevalidation_desc'] = 'Require human validation before a Kristal becomes visible as validated.';
$string['settings:maxkristalitems'] = 'Maximum Kristal items';
$string['settings:maxkristalitems_desc'] = 'Maximum number of archive items that may be attached to one Kristal by default.';

$string['settings:export'] = 'Export settings';
$string['settings:export_desc'] = 'Configure archive export defaults.';
$string['settings:enableexports'] = 'Enable archive exports';
$string['settings:enableexports_desc'] = 'Allow permitted users to export archive packages.';
$string['settings:defaultexportformat'] = 'Default export format';
$string['settings:defaultexportformat_desc'] = 'Default format for archive export packages.';
$string['settings:includefilesinexports'] = 'Include files in exports';
$string['settings:includefilesinexports_desc'] = 'Include file payloads in archive exports when allowed by capability and visibility rules.';
$string['settings:blockrestrictedexports'] = 'Block restricted exports';
$string['settings:blockrestrictedexports_desc'] = 'Prevent restricted and restricted-integrity records from being exported through ordinary archive exports.';
$string['settings:maxexportitems'] = 'Maximum export items';
$string['settings:maxexportitems_desc'] = 'Maximum number of archive items included in one export package by default.';

$string['settings:tasks'] = 'Scheduled task settings';
$string['settings:tasks_desc'] = 'Configure archive scheduled task behaviour.';
$string['settings:validatependingitems'] = 'Validate pending items task';
$string['settings:validatependingitems_desc'] = 'Allow the scheduled task to scan pending archive items and prepare validation reminders or queues.';
$string['settings:generateexports'] = 'Generate export packages task';
$string['settings:generateexports_desc'] = 'Allow the scheduled task to generate queued archive export packages.';
$string['settings:taskbatchsize'] = 'Task batch size';
$string['settings:taskbatchsize_desc'] = 'Maximum number of archive records processed by one scheduled task run.';

$string['settings:ai'] = 'AI governance';
$string['settings:ai_desc'] = 'Configure AI assistance boundaries for archive work.';
$string['settings:allowaiassistance'] = 'Allow AI assistance';
$string['settings:allowaiassistance_desc'] = 'Allow AI to assist with drafting, summarising, classification, and uncertainty detection.';
$string['settings:logaiuse'] = 'Log AI use';
$string['settings:logaiuse_desc'] = 'Require AI-assisted archive actions to preserve prompt/output/provenance logs where applicable.';
$string['settings:requireaiuncertaintylabel'] = 'Require AI uncertainty label';
$string['settings:requireaiuncertaintylabel_desc'] = 'Require AI-assisted archive summaries to identify uncertainty and human validation state.';
$string['settings:allowaivalidation'] = 'Allow AI validation';
$string['settings:allowaivalidation_desc'] = 'Allow AI to validate archive records. This should remain disabled because archive validation requires authorised human review.';

$string['visibility:private'] = 'Private';
$string['visibility:user'] = 'User';
$string['visibility:group'] = 'Group';
$string['visibility:course'] = 'Course';
$string['visibility:cohort'] = 'Cohort';
$string['visibility:program'] = 'Program';
$string['visibility:institution'] = 'Institution';
$string['visibility:public'] = 'Public';
$string['visibility:restricted'] = 'Restricted';
$string['visibility:restricted_integrity'] = 'Restricted integrity';
$string['visibility:hidden'] = 'Hidden';
$string['visibility:archived'] = 'Archived';

$string['provenance:human'] = 'Human';
$string['provenance:ai_assisted'] = 'AI-assisted';
$string['provenance:imported'] = 'Imported';
$string['provenance:system'] = 'System';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assembly';
$string['provenance:challenge'] = 'Challenge';
$string['provenance:integrity'] = 'Integrity';

$string['validation:unverified'] = 'Unverified';
$string['validation:human_reviewed'] = 'Human reviewed';
$string['validation:verified'] = 'Verified';
$string['validation:contested'] = 'Contested';
$string['validation:invalidated'] = 'Invalidated';
$string['validation:archived'] = 'Archived';

$string['exportformat:json'] = 'JSON';
$string['exportformat:html'] = 'HTML';
$string['exportformat:csv'] = 'CSV';
$string['exportformat:mbz_manifest'] = 'Moodle backup manifest';
```

Add these strings to `mod/uckkarchive/lang/fr/uckkarchive.php`:

```php id="fc05rt"
$string['settings:general'] = 'Paramètres généraux';
$string['settings:general_desc'] = 'Configurer le comportement général des Archives UCKK.';
$string['settings:enabled'] = 'Activer les archives UCKK';
$string['settings:enabled_desc'] = 'Permettre l’utilisation des activités Archive UCKK.';
$string['settings:defaultvisibility'] = 'Visibilité par défaut';
$string['settings:defaultvisibility_desc'] = 'Visibilité attribuée par défaut aux nouveaux enregistrements d’archive.';
$string['settings:defaultprovenance'] = 'Provenance par défaut';
$string['settings:defaultprovenance_desc'] = 'Provenance attribuée par défaut aux nouveaux enregistrements d’archive.';
$string['settings:defaultvalidationstate'] = 'État de validation par défaut';
$string['settings:defaultvalidationstate_desc'] = 'État de validation attribué par défaut aux nouveaux enregistrements d’archive.';

$string['settings:itempolicy'] = 'Politique des éléments d’archive';
$string['settings:itempolicy_desc'] = 'Configurer la provenance, la visibilité, l’accès public et le comportement des archives restreintes.';
$string['settings:requireprovenance'] = 'Exiger la provenance';
$string['settings:requireprovenance_desc'] = 'Exiger que les éléments d’archive et les preuves incluent des informations de provenance.';
$string['settings:requirecontext'] = 'Exiger le contexte';
$string['settings:requirecontext_desc'] = 'Exiger que les éléments d’archive identifient leur contexte Moodle ou UCKK.';
$string['settings:requirevisibility'] = 'Exiger la visibilité';
$string['settings:requirevisibility_desc'] = 'Exiger que les éléments d’archive portent une valeur de visibilité explicite.';
$string['settings:requirehumanvalidation'] = 'Exiger une validation humaine';
$string['settings:requirehumanvalidation_desc'] = 'Exiger une validation humaine autorisée avant qu’un enregistrement d’archive puisse devenir vérifié.';
$string['settings:allowpublicitems'] = 'Permettre les éléments publics';
$string['settings:allowpublicitems_desc'] = 'Permettre aux éléments d’archive d’être publics lorsque les capacités et le flux de validation l’autorisent.';
$string['settings:protectrestricteditems'] = 'Protéger les éléments restreints';
$string['settings:protectrestricteditems_desc'] = 'Masquer les éléments restreints et restreints à l’intégrité des vues et exports ordinaires.';

$string['settings:validation'] = 'Validation et révision';
$string['settings:validation_desc'] = 'Configurer la validation, la révision et la pause liée à l’intégrité.';
$string['settings:enablevalidation'] = 'Activer le flux de validation';
$string['settings:enablevalidation_desc'] = 'Permettre aux éléments d’archive de circuler entre les états de validation.';
$string['settings:enablerevisions'] = 'Activer les révisions';
$string['settings:enablerevisions_desc'] = 'Permettre aux éléments validés d’être modifiés par des traces versionnées.';
$string['settings:requirechangereason'] = 'Exiger une raison de changement';
$string['settings:requirechangereason_desc'] = 'Exiger une raison lors d’une révision, invalidation ou archive.';
$string['settings:lockvalidateditems'] = 'Verrouiller les éléments validés';
$string['settings:lockvalidateditems_desc'] = 'Empêcher l’édition directe des éléments validés. Les changements doivent créer des révisions.';
$string['settings:pausevalidationonintegritycase'] = 'Suspendre la validation pendant les dossiers d’intégrité';
$string['settings:pausevalidationonintegritycase_desc'] = 'Suspendre la validation d’archive lorsqu’un dossier d’intégrité lié est ouvert.';

$string['settings:kristal'] = 'Paramètres des Kristals';
$string['settings:kristal_desc'] = 'Configurer les Kristals comme cristallisations particulières d’archive.';
$string['settings:enablekristals'] = 'Activer les Kristals';
$string['settings:enablekristals_desc'] = 'Permettre aux enregistrements d’archive d’être promus ou groupés comme Kristals.';
$string['settings:kristalsrequirevalidation'] = 'Les Kristals exigent une validation';
$string['settings:kristalsrequirevalidation_desc'] = 'Exiger une validation humaine avant qu’un Kristal devienne visible comme validé.';
$string['settings:maxkristalitems'] = 'Nombre maximal d’éléments par Kristal';
$string['settings:maxkristalitems_desc'] = 'Nombre maximal d’éléments d’archive pouvant être attachés par défaut à un Kristal.';

$string['settings:export'] = 'Paramètres d’export';
$string['settings:export_desc'] = 'Configurer les valeurs par défaut des exports d’archive.';
$string['settings:enableexports'] = 'Activer les exports d’archive';
$string['settings:enableexports_desc'] = 'Permettre aux utilisateurs autorisés d’exporter des paquets d’archive.';
$string['settings:defaultexportformat'] = 'Format d’export par défaut';
$string['settings:defaultexportformat_desc'] = 'Format par défaut des paquets d’export d’archive.';
$string['settings:includefilesinexports'] = 'Inclure les fichiers dans les exports';
$string['settings:includefilesinexports_desc'] = 'Inclure les fichiers dans les exports lorsque les capacités et la visibilité l’autorisent.';
$string['settings:blockrestrictedexports'] = 'Bloquer les exports restreints';
$string['settings:blockrestrictedexports_desc'] = 'Empêcher les données restreintes et restreintes à l’intégrité d’être exportées par les exports ordinaires.';
$string['settings:maxexportitems'] = 'Nombre maximal d’éléments exportés';
$string['settings:maxexportitems_desc'] = 'Nombre maximal d’éléments d’archive inclus par défaut dans un paquet d’export.';

$string['settings:tasks'] = 'Paramètres des tâches planifiées';
$string['settings:tasks_desc'] = 'Configurer le comportement des tâches planifiées d’archive.';
$string['settings:validatependingitems'] = 'Tâche de validation des éléments en attente';
$string['settings:validatependingitems_desc'] = 'Permettre à la tâche planifiée de préparer les files ou rappels de validation.';
$string['settings:generateexports'] = 'Tâche de génération des exports';
$string['settings:generateexports_desc'] = 'Permettre à la tâche planifiée de générer les paquets d’export en attente.';
$string['settings:taskbatchsize'] = 'Taille de lot des tâches';
$string['settings:taskbatchsize_desc'] = 'Nombre maximal d’enregistrements traités par une exécution de tâche planifiée.';

$string['settings:ai'] = 'Gouvernance IA';
$string['settings:ai_desc'] = 'Configurer les limites de l’assistance IA pour le travail d’archive.';
$string['settings:allowaiassistance'] = 'Permettre l’assistance IA';
$string['settings:allowaiassistance_desc'] = 'Permettre à l’IA d’aider à rédiger, résumer, classer et détecter l’incertitude.';
$string['settings:logaiuse'] = 'Journaliser l’usage de l’IA';
$string['settings:logaiuse_desc'] = 'Exiger que les actions d’archive assistées par IA conservent les traces de prompts, sorties et provenance lorsque pertinent.';
$string['settings:requireaiuncertaintylabel'] = 'Exiger une étiquette d’incertitude IA';
$string['settings:requireaiuncertaintylabel_desc'] = 'Exiger que les résumés assistés par IA indiquent l’incertitude et l’état de validation humaine.';
$string['settings:allowaivalidation'] = 'Permettre la validation IA';
$string['settings:allowaivalidation_desc'] = 'Permettre à l’IA de valider des archives. Cette option doit rester désactivée car la validation d’archive exige une revue humaine autorisée.';

$string['visibility:private'] = 'Privée';
$string['visibility:user'] = 'Utilisateur';
$string['visibility:group'] = 'Groupe';
$string['visibility:course'] = 'Cours';
$string['visibility:cohort'] = 'Cohorte';
$string['visibility:program'] = 'Programme';
$string['visibility:institution'] = 'Institution';
$string['visibility:public'] = 'Publique';
$string['visibility:restricted'] = 'Restreinte';
$string['visibility:restricted_integrity'] = 'Restreinte à l’intégrité';
$string['visibility:hidden'] = 'Masquée';
$string['visibility:archived'] = 'Archivée';

$string['provenance:human'] = 'Humaine';
$string['provenance:ai_assisted'] = 'Assistée par IA';
$string['provenance:imported'] = 'Importée';
$string['provenance:system'] = 'Système';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assemblée';
$string['provenance:challenge'] = 'Défi';
$string['provenance:integrity'] = 'Intégrité';

$string['validation:unverified'] = 'Non vérifiée';
$string['validation:human_reviewed'] = 'Révisée humainement';
$string['validation:verified'] = 'Vérifiée';
$string['validation:contested'] = 'Contestée';
$string['validation:invalidated'] = 'Invalidée';
$string['validation:archived'] = 'Archivée';

$string['exportformat:json'] = 'JSON';
$string['exportformat:html'] = 'HTML';
$string['exportformat:csv'] = 'CSV';
$string['exportformat:mbz_manifest'] = 'Manifeste de sauvegarde Moodle';

