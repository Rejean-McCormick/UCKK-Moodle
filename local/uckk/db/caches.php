<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.

/**
 * Cache definitions for local_uckk.
 *
 * These caches are public Faculty/Atlas caches only. They must not store:
 * - grades;
 * - private feedback;
 * - individual progress;
 * - completion state;
 * - enrolment state;
 * - student lists;
 * - submissions;
 * - private evidence;
 * - votes;
 * - private reports;
 * - integrity cases;
 * - personal Konnaxion data.
 *
 * Cache areas are intentionally limited to the canonical DOC_12 cache names:
 *
 * - faculty_profile
 * - atlas_voie
 * - faculty_page
 * - faculty_dynamic_block
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle Universal Cache definitions.
 *
 * Public cache key contract:
 *
 * - faculty_profile:{slug}:{hash}
 * - atlas_voie:{voie_id}:{hash}
 * - faculty_page:{slug}:{atlas_hash}:{faculty_hash}
 * - dynamic_block:{slug}:{block_id}:{provider}:{hash}
 *
 * Hash contract:
 *
 * - atlas_source_hash = sha256(file contents)
 * - faculty_source_hash = sha256(file contents)
 * - merged_page_hash = sha256(merged public page payload)
 *
 * Invalidation contract:
 *
 * - purge when a voie_*.json file changes;
 * - purge when a *.faculty.json file changes;
 * - purge when faculty_manifest.json changes;
 * - purge when atlas_manifest.json changes;
 * - purge when sync apply runs;
 * - purge on manual admin action.
 */
$definitions = [

    /*
     * Normalised public Faculty Profile JSON.
     *
     * Stores the parsed and normalised content/faculties/*.faculty.json payload.
     * This cache is source-file based and must be keyed with the faculty slug
     * and the sha256 hash of the source file.
     */
    'faculty_profile' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'ttl' => 3600,
    ],

    /*
     * Normalised public Atlas Voie JSON.
     *
     * Stores the parsed and normalised atlas/voies/voie_*.json payload. This
     * cache is source-file based and must be keyed with the voie_id and the
     * sha256 hash of the source file.
     */
    'atlas_voie' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'ttl' => 3600,
    ],

    /*
     * Render-ready public Faculty Page payload.
     *
     * Stores the merged public payload produced by faculty_page_builder after
     * combining the Faculty Profile, the Atlas Voie projection, notices, FAQ,
     * course cards, and render-only template context.
     *
     * This cache must not store user-specific values. Permission-sensitive data
     * must be resolved outside this cache or reduced to public-safe labels
     * before storage.
     */
    'faculty_page' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'ttl' => 1800,
    ],

    /*
     * Public dynamic block payload.
     *
     * Stores the public result of one dynamic block provider, keyed by faculty
     * slug, block id, provider name, and merged/source hash.
     *
     * Providers must fail closed and must export public data only. This cache
     * must not contain user-specific forum visibility, private calendar events,
     * enrolment state, grades, completion, submissions, private evidence, or
     * internal moderation state.
     */
    'faculty_dynamic_block' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'ttl' => 900,
    ],
];