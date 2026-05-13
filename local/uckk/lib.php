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
 * Library callbacks for local_uckk.
 *
 * This file must remain small. Moodle core may load lib.php files frequently,
 * so only Moodle callbacks and bridge functions belong here.
 *
 * UCKK business logic belongs in autoloaded classes under:
 * - local/uckk/classes/api/
 * - local/uckk/classes/local/
 * - local/uckk/classes/output/
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend Moodle global navigation with the UCKK campus entry points.
 *
 * This callback adds lightweight navigation links only. It does not create
 * programs, assign pathways, enrol users, assign roles, validate evidence,
 * publish assembly decisions, validate archive items or decide integrity cases.
 *
 * @param global_navigation $navigation Global navigation tree.
 * @return void
 */
function local_uckk_extend_navigation(global_navigation $navigation): void {
    global $PAGE, $SITE;

    if (local_uckk_skip_runtime_callbacks()) {
        return;
    }

    if (!local_uckk_is_enabled()) {
        return;
    }

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $systemcontext = context_system::instance();

    if (!has_capability('local/uckk:viewcampus', $systemcontext)
            && !has_capability('local/uckk:manageprograms', $systemcontext)
            && !has_capability('local/uckk:viewreports', $systemcontext)) {
        return;
    }

    $rootlabel = get_string('pluginname', 'local_uckk');
    $rooturl = new moodle_url('/local/uckk/index.php');

    $rootnode = navigation_node::create(
        $rootlabel,
        $rooturl,
        navigation_node::TYPE_CUSTOM,
        $rootlabel,
        'local_uckk',
        new pix_icon('i/navigationitem', $rootlabel)
    );

    if ($PAGE->url->compare($rooturl, URL_MATCH_BASE)) {
        $rootnode->make_active();
    }

    local_uckk_add_navigation_child(
        $rootnode,
        'nav_dashboard',
        '/local/uckk/index.php',
        'local_uckk_dashboard',
        'local/uckk:viewcampus',
        $systemcontext
    );

    local_uckk_add_navigation_child(
        $rootnode,
        'nav_programs',
        '/local/uckk/programs.php',
        'local_uckk_programs',
        'local/uckk:manageprograms',
        $systemcontext
    );

    local_uckk_add_navigation_child(
        $rootnode,
        'nav_pathways',
        '/local/uckk/pathways.php',
        'local_uckk_pathways',
        'local/uckk:managepathways',
        $systemcontext
    );

    local_uckk_add_navigation_child(
        $rootnode,
        'nav_profiles',
        '/local/uckk/profiles.php',
        'local_uckk_profiles',
        'local/uckk:manageprofiles',
        $systemcontext
    );

    local_uckk_add_navigation_child(
        $rootnode,
        'nav_canon',
        '/local/uckk/canon.php',
        'local_uckk_canon',
        'local/uckk:managecanon',
        $systemcontext
    );

    if (core_component::get_plugin_directory('mod', 'uckkchallenge') !== null) {
        local_uckk_add_navigation_child(
            $rootnode,
            'nav_challenges',
            '/mod/uckkchallenge/index.php',
            'local_uckk_challenges',
            'local/uckk:viewcampus',
            $systemcontext
        );
    }

    if (core_component::get_plugin_directory('mod', 'uckkassembly') !== null) {
        local_uckk_add_navigation_child(
            $rootnode,
            'nav_assemblies',
            '/mod/uckkassembly/index.php',
            'local_uckk_assemblies',
            'local/uckk:viewcampus',
            $systemcontext
        );
    }

    if (core_component::get_plugin_directory('mod', 'uckkarchive') !== null) {
        local_uckk_add_navigation_child(
            $rootnode,
            'nav_archives',
            '/mod/uckkarchive/index.php',
            'local_uckk_archives',
            'local/uckk:viewcampus',
            $systemcontext
        );
    }

    if (core_component::get_plugin_directory('tool', 'uckkintegrity') !== null
            && has_capability('tool/uckkintegrity:view', $systemcontext)) {
        local_uckk_add_navigation_child(
            $rootnode,
            'nav_integrity',
            '/admin/tool/uckkintegrity/index.php',
            'local_uckk_integrity',
            'tool/uckkintegrity:view',
            $systemcontext
        );
    }

    if (core_component::get_plugin_directory('report', 'uckk') !== null
            && has_capability('report/uckk:view', $systemcontext)) {
        local_uckk_add_navigation_child(
            $rootnode,
            'nav_reports',
            '/report/uckk/index.php',
            'local_uckk_reports',
            'report/uckk:view',
            $systemcontext
        );
    }

    $navigation->add_node($rootnode);
}

/**
 * Extend settings navigation with course-level UCKK links.
 *
 * This callback adds course-sensitive links when the user is inside a real
 * course. It does not change course settings, course format, enrolments,
 * roles, reports, archives or integrity data.
 *
 * @param settings_navigation $settingsnav Settings navigation tree.
 * @param context $context Current context.
 * @return void
 */
function local_uckk_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    global $PAGE, $COURSE;

    if (local_uckk_skip_runtime_callbacks()) {
        return;
    }

    if (!local_uckk_is_enabled()) {
        return;
    }

    if (empty($COURSE->id) || (int)$COURSE->id === SITEID) {
        return;
    }

    $coursecontext = context_course::instance($COURSE->id, IGNORE_MISSING);
    if (!$coursecontext) {
        return;
    }

    if (!has_capability('local/uckk:viewcampus', $coursecontext)
            && !has_capability('local/uckk:viewcampus', context_system::instance())) {
        return;
    }

    $coursenode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
    if (!$coursenode) {
        return;
    }

    $rootlabel = get_string('uckkcourseadmin', 'local_uckk');
    $rootnode = navigation_node::create(
        $rootlabel,
        new moodle_url('/local/uckk/index.php', ['courseid' => $COURSE->id]),
        navigation_node::NODETYPE_BRANCH,
        $rootlabel,
        'local_uckk_courseadmin',
        new pix_icon('i/settings', $rootlabel)
    );

    local_uckk_add_settings_child(
        $rootnode,
        'nav_courseoverview',
        '/local/uckk/index.php',
        'local_uckk_course_overview',
        ['courseid' => $COURSE->id],
        'local/uckk:viewcampus',
        $coursecontext
    );

    if (has_capability('local/uckk:managepathways', $coursecontext)
            || has_capability('local/uckk:managepathways', context_system::instance())) {
        local_uckk_add_settings_child(
            $rootnode,
            'nav_pathways',
            '/local/uckk/pathways.php',
            'local_uckk_course_pathways',
            ['courseid' => $COURSE->id],
            'local/uckk:managepathways',
            $coursecontext
        );
    }

    if (core_component::get_plugin_directory('mod', 'uckkchallenge') !== null) {
        local_uckk_add_settings_child(
            $rootnode,
            'nav_challenges',
            '/mod/uckkchallenge/index.php',
            'local_uckk_course_challenges',
            ['id' => $COURSE->id],
            'local/uckk:viewcampus',
            $coursecontext
        );
    }

    if (core_component::get_plugin_directory('mod', 'uckkassembly') !== null) {
        local_uckk_add_settings_child(
            $rootnode,
            'nav_assemblies',
            '/mod/uckkassembly/index.php',
            'local_uckk_course_assemblies',
            ['id' => $COURSE->id],
            'local/uckk:viewcampus',
            $coursecontext
        );
    }

    if (core_component::get_plugin_directory('mod', 'uckkarchive') !== null) {
        local_uckk_add_settings_child(
            $rootnode,
            'nav_archives',
            '/mod/uckkarchive/index.php',
            'local_uckk_course_archives',
            ['id' => $COURSE->id],
            'local/uckk:viewcampus',
            $coursecontext
        );
    }

    if (core_component::get_plugin_directory('tool', 'uckkintegrity') !== null
            && has_capability('tool/uckkintegrity:view', $coursecontext)) {
        local_uckk_add_settings_child(
            $rootnode,
            'nav_integrity',
            '/admin/tool/uckkintegrity/index.php',
            'local_uckk_course_integrity',
            ['courseid' => $COURSE->id],
            'tool/uckkintegrity:view',
            $coursecontext
        );
    }

    if ($rootnode->has_children()) {
        $coursenode->add_node($rootnode);
    }
}

/**
 * Serve files stored by local_uckk.
 *
 * Supported file areas:
 * - profile: user-owned profile artifacts;
 * - canon: internal canon attachments;
 * - reflection: user reflection files;
 * - map: cartography or system-map artifacts.
 *
 * This callback must remain conservative. File ownership and richer workflows
 * belong to the relevant plugins, especially mod_uckkarchive.
 *
 * @param stdClass $course Course record.
 * @param stdClass|null $cm Course module, unused for local plugin files.
 * @param context $context File context.
 * @param string $filearea File area.
 * @param array $args Path arguments.
 * @param bool $forcedownload Whether download is forced.
 * @param array $options File serving options.
 * @return bool
 */
function local_uckk_pluginfile(
    stdClass $course,
    ?stdClass $cm,
    context $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    if (local_uckk_skip_runtime_callbacks()) {
        return false;
    }

    if (!local_uckk_is_enabled()) {
        return false;
    }

    $allowedareas = [
        'profile',
        'canon',
        'reflection',
        'map',
    ];

    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    if (!local_uckk_can_access_filearea($context, $filearea)) {
        return false;
    }

    if (empty($args)) {
        return false;
    }

    $itemid = array_shift($args);
    $itemid = clean_param($itemid, PARAM_INT);

    if ($itemid < 0) {
        return false;
    }

    $filename = array_pop($args);
    if ($filename === null || $filename === '') {
        return false;
    }

    $filepath = '/';
    if (!empty($args)) {
        $filepath .= implode('/', array_map(static function($arg): string {
            return clean_param((string)$arg, PARAM_PATH);
        }, $args));
        $filepath .= '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file(
        $context->id,
        'local_uckk',
        $filearea,
        $itemid,
        $filepath,
        $filename
    );

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Return supported file areas for local_uckk.
 *
 * @param context $context Moodle context.
 * @return array<string, string>
 */
function local_uckk_get_file_areas(context $context): array {
    return [
        'profile' => get_string('filearea_profile', 'local_uckk'),
        'canon' => get_string('filearea_canon', 'local_uckk'),
        'reflection' => get_string('filearea_reflection', 'local_uckk'),
        'map' => get_string('filearea_map', 'local_uckk'),
    ];
}

/**
 * Return plugin feature support flags.
 *
 * Local plugins usually have little to declare here, but this callback gives
 * Moodle a stable answer for feature probes.
 *
 * @param string $feature Feature name.
 * @return mixed
 */
function local_uckk_supports(string $feature) {
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}

/**
 * Add one child to global UCKK navigation when capability allows it.
 *
 * @param navigation_node $parent Parent navigation node.
 * @param string $stringkey Language string key.
 * @param string $path URL path.
 * @param string $nodekey Stable node key.
 * @param string $capability Required capability.
 * @param context $context Capability context.
 * @return void
 */
function local_uckk_add_navigation_child(
    navigation_node $parent,
    string $stringkey,
    string $path,
    string $nodekey,
    string $capability,
    context $context
): void {
    global $PAGE;

    if (!has_capability($capability, $context)) {
        return;
    }

    $label = local_uckk_get_string_or_fallback($stringkey);
    $url = new moodle_url($path);

    $node = navigation_node::create(
        $label,
        $url,
        navigation_node::TYPE_CUSTOM,
        $label,
        $nodekey,
        new pix_icon('i/navigationitem', $label)
    );

    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $node->make_active();
    }

    $parent->add_node($node);
}

/**
 * Add one child to the course UCKK settings navigation.
 *
 * @param navigation_node $parent Parent node.
 * @param string $stringkey Language string key.
 * @param string $path URL path.
 * @param string $nodekey Stable node key.
 * @param array $params URL parameters.
 * @param string $capability Required capability.
 * @param context $context Capability context.
 * @return void
 */
function local_uckk_add_settings_child(
    navigation_node $parent,
    string $stringkey,
    string $path,
    string $nodekey,
    array $params,
    string $capability,
    context $context
): void {
    global $PAGE;

    if (!has_capability($capability, $context)) {
        return;
    }

    $label = local_uckk_get_string_or_fallback($stringkey);
    $url = new moodle_url($path, $params);

    $node = navigation_node::create(
        $label,
        $url,
        navigation_node::NODETYPE_LEAF,
        $label,
        $nodekey,
        new pix_icon('i/settings', $label)
    );

    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $node->make_active();
    }

    $parent->add_node($node);
}

/**
 * Determine whether local_uckk runtime callbacks should be skipped.
 *
 * @return bool
 */
function local_uckk_skip_runtime_callbacks(): bool {
    global $CFG;

    if (function_exists('during_initial_install') && during_initial_install()) {
        return true;
    }

    if (!empty($CFG->upgraderunning)) {
        return true;
    }

    return false;
}

/**
 * Determine whether local_uckk is enabled.
 *
 * The plugin defaults to enabled when the setting is not yet present, which
 * avoids hiding navigation immediately after installation.
 *
 * @return bool
 */
function local_uckk_is_enabled(): bool {
    $enabled = get_config('local_uckk', 'enabled');

    if ($enabled === false || $enabled === null || $enabled === '') {
        return true;
    }

    return (bool)$enabled;
}

/**
 * Determine whether current user may access a local_uckk file area.
 *
 * @param context $context File context.
 * @param string $filearea File area.
 * @return bool
 */
function local_uckk_can_access_filearea(context $context, string $filearea): bool {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return false;
    }

    if (has_capability('local/uckk:manageprofiles', $context)
            || has_capability('local/uckk:managecanon', $context)
            || has_capability('local/uckk:viewcampus', $context)) {
        return true;
    }

    if ($context->contextlevel === CONTEXT_USER && (int)$context->instanceid === (int)$USER->id) {
        return in_array($filearea, ['profile', 'reflection', 'map'], true);
    }

    if ($filearea === 'canon') {
        return has_capability('local/uckk:viewcampus', context_system::instance());
    }

    return false;
}

/**
 * Resolve a local_uckk language string with a safe fallback.
 *
 * This keeps navigation usable while language files are being generated.
 *
 * @param string $identifier String identifier.
 * @return string
 */
function local_uckk_get_string_or_fallback(string $identifier): string {
    if (get_string_manager()->string_exists($identifier, 'local_uckk')) {
        return get_string($identifier, 'local_uckk');
    }

    $fallbacks = [
        'nav_dashboard' => 'UCKK',
        'nav_programs' => 'Programmes',
        'nav_pathways' => 'Parcours',
        'nav_profiles' => 'Profils',
        'nav_canon' => 'Canon',
        'nav_challenges' => 'Défis',
        'nav_assemblies' => 'Assemblées',
        'nav_archives' => 'Archives',
        'nav_integrity' => 'Inquisiteur',
        'nav_reports' => 'Rapports',
        'nav_courseoverview' => 'Vue UCKK du cours',
    ];

    return $fallbacks[$identifier] ?? ucfirst(str_replace('_', ' ', $identifier));
}