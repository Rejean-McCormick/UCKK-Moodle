<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Capability definitions for UCKK Archives.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    /*
     * Add a UCKK Archive activity to a course.
     */
    'mod/uckkarchive:addinstance' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:manageactivities',
    ],

    /*
     * View the archive activity and non-restricted archive items.
     */
    'mod/uckkarchive:view' => [
        'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'guest' => CAP_PREVENT,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Add an archive item, proof record, Kristal, or portfolio-linked memory
     * item inside the archive activity.
     *
     * Services must still enforce item ownership, context, provenance,
     * visibility, and workflow rules.
     */
    'mod/uckkarchive:additem' => [
        'riskbitmask' => RISK_XSS | RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Validate archive items and mark evidence as human-reviewed or verified.
     *
     * This is intended for Archiviste, Mentor, Gestionnaire UCKK, or similarly
     * trusted technical roles configured by administrators.
     */
    'mod/uckkarchive:validateitem' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Revise archive items and create new archive revision records.
     *
     * Students may receive this by default so they can revise their own
     * submitted archive records. Service code must still check ownership and
     * workflow state before accepting revisions.
     */
    'mod/uckkarchive:reviseitem' => [
        'riskbitmask' => RISK_XSS | RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * View restricted, private, hidden, or restricted-integrity archive data.
     *
     * This capability must not be granted broadly. It protects sensitive
     * evidence, integrity-linked records, privacy-sensitive material, and
     * restricted institutional memory.
     */
    'mod/uckkarchive:viewrestricted' => [
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    /*
     * Export archive items, evidence packages, or public/restricted archive
     * bundles.
     *
     * Export services must still enforce validation state, provenance,
     * visibility, retention, and privacy rules.
     */
    'mod/uckkarchive:export' => [
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
```

Add these strings to `mod/uckkarchive/lang/en/uckkarchive.php`:

```php
$string['uckkarchive:addinstance'] = 'Add a UCKK Archive activity';
$string['uckkarchive:view'] = 'View UCKK Archive';
$string['uckkarchive:additem'] = 'Add archive items';
$string['uckkarchive:validateitem'] = 'Validate archive items';
$string['uckkarchive:reviseitem'] = 'Revise archive items';
$string['uckkarchive:viewrestricted'] = 'View restricted archive records';
$string['uckkarchive:export'] = 'Export archive records';
```

Add these strings to `mod/uckkarchive/lang/fr/uckkarchive.php`:

```php
$string['uckkarchive:addinstance'] = 'Ajouter une activité Archive UCKK';
$string['uckkarchive:view'] = 'Voir l’Archive UCKK';
$string['uckkarchive:additem'] = 'Ajouter des éléments d’archive';
$string['uckkarchive:validateitem'] = 'Valider des éléments d’archive';
$string['uckkarchive:reviseitem'] = 'Réviser des éléments d’archive';
$string['uckkarchive:viewrestricted'] = 'Voir les traces d’archive restreintes';
$string['uckkarchive:export'] = 'Exporter des traces d’archive';

