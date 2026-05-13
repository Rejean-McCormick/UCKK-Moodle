<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * French strings for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Graine UCKK';
$string['privacy:metadata'] = 'L’outil de graine UCKK conserve des journaux d’exécution administratifs pour documenter les opérations de validation, de génération, de réinitialisation et d’export de préréglages.';

// Capabilities.
$string['uckkseed:seed'] = 'Générer la distribution UCKK';
$string['uckkseed:reset'] = 'Réinitialiser le contenu généré par la graine UCKK';
$string['uckkseed:validate'] = 'Valider la distribution UCKK';
$string['uckkseed:exportpresets'] = 'Exporter les préréglages UCKK';

// Navigation and pages.
$string['seeduckk'] = 'Générer UCKK';
$string['seeddistribution'] = 'Générer la distribution UCKK';
$string['resetdistribution'] = 'Réinitialiser la distribution UCKK';
$string['validatedistribution'] = 'Valider la distribution UCKK';
$string['exportpreset'] = 'Exporter un préréglage';
$string['settings'] = 'Paramètres de la graine UCKK';
$string['seedsummary'] = 'Résumé de génération';
$string['validationreport'] = 'Rapport de validation';
$string['preset'] = 'Préréglage';
$string['presets'] = 'Préréglages';
$string['presetcard'] = 'Carte de préréglage';
$string['runs'] = 'Exécutions';
$string['logs'] = 'Journaux';
$string['backtoseedtool'] = 'Retour à la graine UCKK';

// Settings.
$string['enabletool'] = 'Activer l’outil de graine UCKK';
$string['enabletool_desc'] = 'Autorise l’utilisation de l’outil d’installation institutionnelle UCKK.';
$string['allowcli'] = 'Autoriser les commandes CLI';
$string['allowcli_desc'] = 'Autorise l’exécution des commandes CLI de génération, validation, réinitialisation et export.';
$string['allowreset'] = 'Autoriser la réinitialisation';
$string['allowreset_desc'] = 'Autorise les opérations de réinitialisation du contenu explicitement généré par la graine UCKK.';
$string['allowdryrun'] = 'Autoriser le mode simulation';
$string['allowdryrun_desc'] = 'Autorise l’exécution sans écriture pour vérifier les changements prévus.';
$string['defaultmode'] = 'Mode par défaut';
$string['defaultmode_desc'] = 'Mode utilisé par défaut lors d’une nouvelle opération de graine.';
$string['presetpath'] = 'Chemin des préréglages';
$string['presetpath_desc'] = 'Chemin relatif contenant les fichiers JSON de préréglage UCKK.';
$string['logretentiondays'] = 'Conservation des journaux';
$string['logretentiondays_desc'] = 'Nombre de jours de conservation des journaux d’exécution de la graine UCKK.';
$string['autoseedoninstall'] = 'Génération automatique à l’installation';
$string['autoseedoninstall_desc'] = 'Autorise une tâche contrôlée à préparer la distribution UCKK après installation, selon les permissions et paramètres du site.';
$string['requireconfirmation'] = 'Exiger une confirmation';
$string['requireconfirmation_desc'] = 'Exige une confirmation explicite avant toute opération qui écrit, réinitialise ou exporte des données.';

// Actions.
$string['action'] = 'Action';
$string['action_seed'] = 'Générer';
$string['action_reset'] = 'Réinitialiser';
$string['action_validate'] = 'Valider';
$string['action_export_preset'] = 'Exporter un préréglage';
$string['seed'] = 'Générer';
$string['reset'] = 'Réinitialiser';
$string['validate'] = 'Valider';
$string['export_preset'] = 'Exporter un préréglage';

// Modes.
$string['mode'] = 'Mode';
$string['mode_apply'] = 'Appliquer';
$string['mode_dry_run'] = 'Simulation';
$string['mode_report'] = 'Rapport';
$string['mode_rollback_plan'] = 'Plan de retour arrière';
$string['dryrun'] = 'Simulation';
$string['dryrunnotice'] = 'Mode simulation : aucune donnée ne sera modifiée.';
$string['rollbackplan'] = 'Plan de retour arrière';
$string['rollbackplannotice'] = 'Ce mode produit un plan de retour arrière sans supprimer de données.';
$string['reportmode'] = 'Mode rapport';

// Statuses.
$string['status'] = 'Statut';
$string['status_pending'] = 'En attente';
$string['status_running'] = 'En cours';
$string['status_completed'] = 'Terminé';
$string['status_failed'] = 'Échoué';
$string['status_cancelled'] = 'Annulé';
$string['status_skipped'] = 'Ignoré';
$string['status_warning'] = 'Avertissement';

// Severities.
$string['severity_info'] = 'Information';
$string['severity_success'] = 'Succès';
$string['severity_warning'] = 'Avertissement';
$string['severity_error'] = 'Erreur';
$string['severity_blocker'] = 'Bloquant';

// Preset names.
$string['preset_categories'] = 'Catégories';
$string['preset_courses'] = 'Cours';
$string['preset_cohorts'] = 'Cohortes';
$string['preset_roles'] = 'Rôles';
$string['preset_capabilities'] = 'Capacités';
$string['preset_competencies'] = 'Compétences';
$string['preset_badges'] = 'Badges';
$string['preset_reports'] = 'Rapports';
$string['preset_course_templates'] = 'Gabarits de cours';
$string['preset_challenge_templates'] = 'Gabarits de défis';
$string['preset_assembly_templates'] = 'Gabarits d’assemblées';
$string['preset_archive_templates'] = 'Gabarits d’archives';

// Preset files.
$string['presetfile_categories'] = 'categories.json';
$string['presetfile_courses'] = 'courses.json';
$string['presetfile_cohorts'] = 'cohorts.json';
$string['presetfile_roles'] = 'roles.json';
$string['presetfile_capabilities'] = 'capabilities.json';
$string['presetfile_competencies'] = 'competencies.json';
$string['presetfile_badges'] = 'badges.json';
$string['presetfile_reports'] = 'reports.json';
$string['presetfile_course_templates'] = 'course_templates.json';
$string['presetfile_challenge_templates'] = 'challenge_templates.json';
$string['presetfile_assembly_templates'] = 'assembly_templates.json';
$string['presetfile_archive_templates'] = 'archive_templates.json';

// Forms.
$string['seedform'] = 'Formulaire de génération UCKK';
$string['resetform'] = 'Formulaire de réinitialisation UCKK';
$string['components'] = 'Composants';
$string['targets'] = 'Cibles';
$string['target'] = 'Cible';
$string['targettype'] = 'Type de cible';
$string['targetkey'] = 'Clé de cible';
$string['dryrunfield'] = 'Exécuter en simulation';
$string['reportfield'] = 'Produire un rapport';
$string['rollbackplanfield'] = 'Produire un plan de retour arrière';
$string['force'] = 'Forcer';
$string['confirm'] = 'Confirmer';
$string['confirmationrequired'] = 'Une confirmation explicite est requise.';
$string['returnurl'] = 'URL de retour';
$string['scope'] = 'Portée';
$string['selectpresets'] = 'Sélectionner les préréglages';
$string['selectcomponents'] = 'Sélectionner les composants';
$string['selectmode'] = 'Sélectionner le mode';
$string['selectaction'] = 'Sélectionner l’action';

// Reset scopes.
$string['reset_scope'] = 'Portée de réinitialisation';
$string['reset_seed_logs'] = 'Réinitialiser les journaux de graine';
$string['reset_seeded_content'] = 'Réinitialiser le contenu généré';
$string['reset_seeded_courses'] = 'Réinitialiser les cours générés';
$string['reset_seeded_roles'] = 'Réinitialiser les rôles générés';
$string['reset_seeded_badges'] = 'Réinitialiser les badges générés';
$string['reset_all_uckk_seeded_content'] = 'Réinitialiser tout le contenu UCKK généré';
$string['resetwarning'] = 'La réinitialisation ne doit viser que les données explicitement créées par la graine UCKK.';
$string['resetrequiresconfirmation'] = 'La réinitialisation exige une confirmation explicite.';

// Counts and summaries.
$string['created'] = 'Créé';
$string['updated'] = 'Mis à jour';
$string['skipped'] = 'Ignoré';
$string['failed'] = 'Échoué';
$string['warnings'] = 'Avertissements';
$string['errors'] = 'Erreurs';
$string['createdcount'] = 'Créés : {$a}';
$string['updatedcount'] = 'Mis à jour : {$a}';
$string['skippedcount'] = 'Ignorés : {$a}';
$string['failedcount'] = 'Échecs : {$a}';
$string['warningscount'] = 'Avertissements : {$a}';
$string['errorscount'] = 'Erreurs : {$a}';
$string['summary'] = 'Résumé';
$string['details'] = 'Détails';
$string['metadata'] = 'Métadonnées';
$string['message'] = 'Message';
$string['messages'] = 'Messages';
$string['nomessages'] = 'Aucun message.';
$string['nopresets'] = 'Aucun préréglage disponible.';
$string['noruns'] = 'Aucune exécution enregistrée.';
$string['nologs'] = 'Aucun journal enregistré.';

// Result labels.
$string['result_ok'] = 'Résultat valide';
$string['result_haserrors'] = 'Résultat avec erreurs';
$string['result_haswarnings'] = 'Résultat avec avertissements';
$string['validationpassed'] = 'Validation réussie.';
$string['validationfailed'] = 'Validation échouée.';
$string['seedcompleted'] = 'Génération terminée.';
$string['seedfailed'] = 'Génération échouée.';
$string['resetcompleted'] = 'Réinitialisation terminée.';
$string['resetfailed'] = 'Réinitialisation échouée.';
$string['exportcompleted'] = 'Export terminé.';
$string['exportfailed'] = 'Export échoué.';

// Preset validation.
$string['presetmissing'] = 'Préréglage manquant : {$a}';
$string['presetinvalid'] = 'Préréglage invalide : {$a}';
$string['presetvalid'] = 'Préréglage valide : {$a}';
$string['presetloaded'] = 'Préréglage chargé : {$a}';
$string['presetexported'] = 'Préréglage exporté : {$a}';
$string['presetnotfound'] = 'Préréglage introuvable.';
$string['presetempty'] = 'Le préréglage ne contient aucun élément.';
$string['presetschema'] = 'Schéma de préréglage';
$string['presetschemaexpected'] = 'Le schéma attendu est uckkseed.preset.v1.';
$string['invalidjson'] = 'JSON invalide.';
$string['invalidpresetitems'] = 'Le préréglage doit contenir une liste items.';
$string['invalidpresetcomponent'] = 'Le composant du préréglage doit être tool_uckkseed.';

// Preset card.
$string['presetlabel'] = 'Libellé du préréglage';
$string['filename'] = 'Nom de fichier';
$string['itemcount'] = 'Nombre d’éléments';
$string['enabled'] = 'Activé';
$string['required'] = 'Requis';
$string['optional'] = 'Optionnel';

// Components.
$string['component_local_uckk'] = 'Services UCKK partagés';
$string['component_theme_uckk'] = 'Thème UCKK';
$string['component_format_uckk'] = 'Format de cours UCKK';
$string['component_block_uckk_dashboard'] = 'Bloc tableau de bord UCKK';
$string['component_mod_uckkchallenge'] = 'Défis UCKK';
$string['component_mod_uckkassembly'] = 'Assemblées UCKK';
$string['component_mod_uckkarchive'] = 'Archives UCKK';
$string['component_tool_uckkintegrity'] = 'Intégrité UCKK';
$string['component_report_uckk'] = 'Rapports UCKK';

// Seeder messages.
$string['seederstarted'] = 'Exécution de graine démarrée.';
$string['seederfinished'] = 'Exécution de graine terminée.';
$string['seederfailed'] = 'L’exécution de graine a échoué.';
$string['runcreated'] = 'Exécution créée.';
$string['runfinished'] = 'Exécution terminée.';
$string['runfailed'] = 'Exécution échouée.';
$string['stepcreated'] = 'Étape créée.';
$string['stepupdated'] = 'Étape mise à jour.';
$string['stepskipped'] = 'Étape ignorée.';
$string['stepfailed'] = 'Étape échouée.';
$string['idempotentmatch'] = 'Objet déjà aligné avec le préréglage.';
$string['idempotentupdate'] = 'Objet existant mis à jour pour correspondre au préréglage.';
$string['dryrunwouldcreate'] = 'Simulation : l’objet serait créé.';
$string['dryrunwouldupdate'] = 'Simulation : l’objet serait mis à jour.';
$string['dryrunwouldskip'] = 'Simulation : l’objet serait ignoré.';
$string['dryrunwouldreset'] = 'Simulation : l’objet serait réinitialisé.';

// Seed object labels.
$string['category'] = 'Catégorie';
$string['course'] = 'Cours';
$string['cohort'] = 'Cohorte';
$string['role'] = 'Rôle';
$string['capability'] = 'Capacité';
$string['competency'] = 'Compétence';
$string['badge'] = 'Badge';
$string['report'] = 'Rapport';
$string['template'] = 'Gabarit';
$string['course_template'] = 'Gabarit de cours';
$string['challenge_template'] = 'Gabarit de défi';
$string['assembly_template'] = 'Gabarit d’assemblée';
$string['archive_template'] = 'Gabarit d’archive';

// Technical role labels.
$string['role_uckkmanager'] = 'Gestionnaire UCKK';
$string['role_uckkmentor'] = 'Mentor UCKK';
$string['role_uckkplayer'] = 'Joueur UCKK';
$string['role_uckkarchivist'] = 'Archiviste UCKK';
$string['role_uckkinquisitor'] = 'Inquisiteur UCKK';
$string['role_uckkobserver'] = 'Observateur UCKK';
$string['role_uckkpublicguest'] = 'Invité public UCKK limité';

// CLI.
$string['cli_seed_help'] = 'Génère la distribution UCKK à partir des préréglages.';
$string['cli_reset_help'] = 'Réinitialise les données explicitement générées par la graine UCKK.';
$string['cli_validate_help'] = 'Valide les préréglages et l’état de la distribution UCKK.';
$string['cli_export_preset_help'] = 'Exporte un préréglage UCKK au format canonique.';
$string['cli_option_dryrun'] = 'Exécuter sans écriture.';
$string['cli_option_report'] = 'Produire un rapport.';
$string['cli_option_rollbackplan'] = 'Produire un plan de retour arrière.';
$string['cli_option_preset'] = 'Limiter l’opération à un préréglage.';
$string['cli_option_component'] = 'Limiter l’opération à un composant.';
$string['cli_option_target'] = 'Limiter l’opération à une cible.';
$string['cli_option_force'] = 'Forcer l’opération autorisée.';
$string['cli_option_json'] = 'Afficher le résultat en JSON.';
$string['cli_tool_disabled'] = 'L’outil de graine UCKK est désactivé.';
$string['cli_disabled'] = 'Les commandes CLI de la graine UCKK sont désactivées.';
$string['cli_reset_disabled'] = 'La réinitialisation UCKK est désactivée.';
$string['cli_invalid_action'] = 'Action CLI invalide.';
$string['cli_invalid_mode'] = 'Mode CLI invalide.';

// Scheduled task.
$string['task_seed_distribution'] = 'Générer la distribution UCKK';
$string['task_seed_distribution_started'] = 'La tâche de génération UCKK a démarré.';
$string['task_seed_distribution_completed'] = 'La tâche de génération UCKK est terminée.';
$string['task_seed_distribution_skipped'] = 'La tâche de génération UCKK a été ignorée.';
$string['task_seed_distribution_failed'] = 'La tâche de génération UCKK a échoué.';

// Output actions.
$string['viewsummary'] = 'Voir le résumé';
$string['viewvalidationreport'] = 'Voir le rapport de validation';
$string['downloadpreset'] = 'Télécharger le préréglage';
$string['exportjson'] = 'Exporter en JSON';
$string['rundryrun'] = 'Lancer une simulation';
$string['runseed'] = 'Lancer la génération';
$string['runvalidation'] = 'Lancer la validation';
$string['runreset'] = 'Lancer la réinitialisation';

// Notices.
$string['governancenotice'] = 'La graine UCKK installe la structure institutionnelle initiale. Les workflows, validations, décisions, archives, rapports, badges et compétences restent gouvernés par leurs plugins propriétaires.';
$string['symbolicrolesnotice'] = 'Les rôles symboliques UCKK ne sont pas créés comme rôles Moodle techniques. Ils doivent être représentés par badges, cohortes, profils, compétences, portfolios et distinctions d’archive.';
$string['humanvalidationnotice'] = 'La graine peut préparer les objets de reconnaissance, mais l’attribution significative de badges et de compétences exige des preuves et une validation humaine.';
$string['idempotencenotice'] = 'Les opérations de graine doivent être idempotentes : relancer la génération ne doit pas créer de doublons.';
$string['seedownershipnotice'] = 'Les objets générés par la graine sont ensuite possédés par leurs composants Moodle respectifs.';

// Errors.
$string['error_tooldisabled'] = 'L’outil de graine UCKK est désactivé.';
$string['error_missingcapability'] = 'Vous n’avez pas la capacité requise pour cette opération.';
$string['error_invalidpreset'] = 'Préréglage invalide.';
$string['error_missingpreset'] = 'Préréglage manquant.';
$string['error_missingconfirmation'] = 'Confirmation manquante.';
$string['error_resetnotallowed'] = 'La réinitialisation n’est pas autorisée.';
$string['error_dryrunnotallowed'] = 'Le mode simulation n’est pas autorisé.';
$string['error_invalidmode'] = 'Mode invalide.';
$string['error_invalidaction'] = 'Action invalide.';
$string['error_invalidscope'] = 'Portée invalide.';
$string['error_invalidcomponent'] = 'Composant invalide.';
$string['error_invalidtarget'] = 'Cible invalide.';
$string['error_jsonencodefailed'] = 'Impossible d’encoder le JSON.';
$string['error_jsondecodefailed'] = 'Impossible de décoder le JSON.';
$string['error_writefailed'] = 'Impossible d’écrire le fichier ou l’enregistrement.';
$string['error_readfailed'] = 'Impossible de lire le fichier ou l’enregistrement.';
$string['error_transactionfailed'] = 'La transaction de graine a échoué.';

// Privacy metadata for run table.
$string['privacy:metadata:tool_uckkseed_run'] = 'Informations sur les exécutions de génération, validation, réinitialisation et export UCKK.';
$string['privacy:metadata:tool_uckkseed_run:userid'] = 'Utilisateur qui a lancé l’exécution.';
$string['privacy:metadata:tool_uckkseed_run:action'] = 'Action exécutée.';
$string['privacy:metadata:tool_uckkseed_run:mode'] = 'Mode d’exécution.';
$string['privacy:metadata:tool_uckkseed_run:status'] = 'Statut de l’exécution.';
$string['privacy:metadata:tool_uckkseed_run:summary'] = 'Résumé de l’exécution.';
$string['privacy:metadata:tool_uckkseed_run:metadata'] = 'Métadonnées JSON de l’exécution.';
$string['privacy:metadata:tool_uckkseed_run:timecreated'] = 'Date de création de l’exécution.';
$string['privacy:metadata:tool_uckkseed_run:timemodified'] = 'Date de modification de l’exécution.';

// Privacy metadata for log table.
$string['privacy:metadata:tool_uckkseed_log'] = 'Journaux détaillés des étapes de graine UCKK.';
$string['privacy:metadata:tool_uckkseed_log:runid'] = 'Exécution associée au journal.';
$string['privacy:metadata:tool_uckkseed_log:userid'] = 'Utilisateur associé au journal.';
$string['privacy:metadata:tool_uckkseed_log:level'] = 'Niveau du message de journal.';
$string['privacy:metadata:tool_uckkseed_log:message'] = 'Message de journal.';
$string['privacy:metadata:tool_uckkseed_log:metadata'] = 'Métadonnées JSON du journal.';
$string['privacy:metadata:tool_uckkseed_log:timecreated'] = 'Date de création du journal.';