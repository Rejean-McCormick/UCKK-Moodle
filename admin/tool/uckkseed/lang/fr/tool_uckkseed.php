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
$string['privacy:metadata'] = 'L’outil de graine UCKK conserve des journaux d’exécution administratifs pour documenter les opérations de validation, de génération, de réinitialisation et d’export du registre académique JSON.';

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
$string['presetpath'] = 'Chemin du registre académique JSON';
$string['presetpath_desc'] = 'Chemin relatif vers le dossier contenant les fichiers JSON du registre académique UCKK. Par défaut : academic_registry_json à la racine Moodle.';
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
$string['preset_programs'] = 'Programmes';
$string['preset_pathways'] = 'Parcours';
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
$string['presetfile_programs'] = 'programs.json';
$string['presetfile_pathways'] = 'pathways.json';
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
$string['confirmseed'] = 'Confirmer la génération';
$string['confirmationrequired'] = 'Une confirmation explicite est requise.';
$string['returnurl'] = 'URL de retour';
$string['scope'] = 'Portée';
$string['selectpresets'] = 'Sélectionner les sections du registre académique JSON';
$string['selectcomponents'] = 'Sélectionner les composants';
$string['selectmode'] = 'Sélectionner le mode';
$string['selectaction'] = 'Sélectionner l’action';

// Reset scopes.
$string['reset_scope'] = 'Portée de réinitialisation';
$string['resetscope'] = 'Portée de réinitialisation';
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
$string['nopresets'] = 'Aucune section du registre académique JSON disponible.';
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
$string['program'] = 'Programme';
$string['pathway'] = 'Parcours';
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
$string['cli_seed_help'] = 'Génère la distribution UCKK à partir du registre académique JSON.';
$string['cli_reset_help'] = 'Réinitialise les données explicitement générées par la graine UCKK.';
$string['cli_validate_help'] = 'Valide le registre académique JSON et l’état de la distribution UCKK.';
$string['cli_export_preset_help'] = 'Exporte une section du registre académique JSON au format canonique.';
$string['cli_option_dryrun'] = 'Exécuter sans écriture.';
$string['cli_option_report'] = 'Produire un rapport.';
$string['cli_option_rollbackplan'] = 'Produire un plan de retour arrière.';
$string['cli_option_preset'] = 'Limiter l’opération à une section du registre académique JSON.';
$string['cli_option_presetpath'] = 'Chemin vers le dossier du registre académique JSON.';
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

// Additional strings discovered by the string inventory.
$string['allcomponents'] = 'Tous les composants';
$string['allpresets'] = 'Tous les préréglages';
$string['auditlogenabled'] = 'Activer le journal d’audit';
$string['auditlogenabled_desc'] = 'Enregistre les opérations de génération, validation, réinitialisation et export pour audit administratif.';
$string['authoritynotice'] = 'La graine prépare la distribution UCKK, mais ne remplace pas les permissions Moodle, la validation humaine ni les workflows propriétaires des plugins.';
$string['badgesdisabled'] = 'La génération des badges est désactivée.';
$string['badgesdryruncomplete'] = 'Simulation des badges terminée.';
$string['badgeseedalreadyabsent'] = 'Badge déjà absent.';
$string['badgeseedcompetenciescriterion'] = 'Critère de compétences du badge.';
$string['badgeseedcreated'] = 'Badge créé.';
$string['badgeseedcriterianotice'] = 'Les critères de badge doivent rester alignés avec les preuves, compétences et validations humaines.';
$string['badgeseeddefaultmessage'] = 'Votre badge UCKK a été attribué.';
$string['badgeseeddefaultsubject'] = 'Badge UCKK attribué';
$string['badgeseedduplicatekey'] = 'Clé de badge en double.';
$string['badgeseedimagecaption'] = 'Image du badge UCKK.';
$string['badgeseedinvalidtype'] = 'Type de badge invalide.';
$string['badgeseedmissingcourse'] = 'Cours associé au badge manquant.';
$string['badgeseedmissingcriterion'] = 'Critère de badge manquant.';
$string['badgeseedmissingdescription'] = 'Description du badge manquante.';
$string['badgeseedmissingkey'] = 'Clé de badge manquante.';
$string['badgeseedmissingname'] = 'Nom de badge manquant.';
$string['badgeseednoncanonical'] = 'Badge non canonique ignoré.';
$string['badgeseedremoved'] = 'Badge supprimé.';
$string['badgeseedrequiresarchive'] = 'Ce badge exige une archive de provenance.';
$string['badgeseedrequireshumanvalidation'] = 'Ce badge exige une validation humaine.';
$string['badgeseedsymbolicroleprotected'] = 'Les badges de rôle symbolique sont protégés.';
$string['badgeseedunknowncompetency'] = 'Compétence de badge inconnue.';
$string['badgeseedupdated'] = 'Badge mis à jour.';
$string['badgeseedwouldcreate'] = 'Simulation : le badge serait créé.';
$string['badgeseedwouldremove'] = 'Simulation : le badge serait supprimé.';
$string['badgeseedwouldupdate'] = 'Simulation : le badge serait mis à jour.';
$string['badgesresetcomplete'] = 'Réinitialisation des badges terminée.';
$string['badgesresetdryruncomplete'] = 'Simulation de réinitialisation des badges terminée.';
$string['badgesseeded'] = 'Badges générés.';
$string['badgesvalidated'] = 'Badges validés.';
$string['capabilityassigned'] = 'Capacité assignée.';
$string['capabilityinvalidcontext'] = 'Contexte de capacité invalide.';
$string['capabilityinvalidpermission'] = 'Permission de capacité invalide.';
$string['capabilitymissing'] = 'Capacité manquante.';
$string['capabilitynotuckk'] = 'Cette capacité n’appartient pas à la distribution UCKK.';
$string['capabilityreset'] = 'Capacité réinitialisée.';
$string['capabilitywouldreset'] = 'Simulation : la capacité serait réinitialisée.';
$string['cohortsdryruncomplete'] = 'Simulation des cohortes terminée.';
$string['cohortseedalreadyabsent'] = 'Cohorte déjà absente.';
$string['cohortseedcategorymissing'] = 'Catégorie de cohorte manquante.';
$string['cohortseedcomponentmissing'] = 'Composant de cohorte manquant.';
$string['cohortseedcontextunresolved'] = 'Contexte de cohorte impossible à résoudre.';
$string['cohortseedcreated'] = 'Cohorte créée.';
$string['cohortseedduplicateidnumber'] = 'Identifiant de cohorte en double.';
$string['cohortseedduplicatekey'] = 'Clé de cohorte en double.';
$string['cohortseedidnumberprefixwarning'] = 'L’identifiant de cohorte ne respecte pas le préfixe attendu.';
$string['cohortseedinvalidcontext'] = 'Contexte de cohorte invalide.';
$string['cohortseedinvalidsymbolicrole'] = 'Rôle symbolique de cohorte invalide.';
$string['cohortseedinvalidtechnicalrole'] = 'Rôle technique de cohorte invalide.';
$string['cohortseedmissingcategory'] = 'Catégorie de cohorte manquante.';
$string['cohortseedmissingidnumber'] = 'Identifiant de cohorte manquant.';
$string['cohortseedmissingkey'] = 'Clé de cohorte manquante.';
$string['cohortseedmissingname'] = 'Nom de cohorte manquant.';
$string['cohortseednotmanagedskip'] = 'Cohorte ignorée parce qu’elle n’est pas gérée par la graine UCKK.';
$string['cohortseedremoved'] = 'Cohorte supprimée.';
$string['cohortseedsymbolicnotrolewarning'] = 'Un rôle symbolique de cohorte ne doit pas devenir un rôle Moodle technique.';
$string['cohortseedunknownprogram'] = 'Programme de cohorte inconnu.';
$string['cohortseedupdated'] = 'Cohorte mise à jour.';
$string['cohortseedwouldcreate'] = 'Simulation : la cohorte serait créée.';
$string['cohortseedwouldremove'] = 'Simulation : la cohorte serait supprimée.';
$string['cohortseedwouldupdate'] = 'Simulation : la cohorte serait mise à jour.';
$string['cohortsresetcomplete'] = 'Réinitialisation des cohortes terminée.';
$string['cohortsresetdryruncomplete'] = 'Simulation de réinitialisation des cohortes terminée.';
$string['cohortsseeded'] = 'Cohortes générées.';
$string['cohortsvalidated'] = 'Cohortes validées.';
$string['confirmreset_desc'] = 'Confirmez que vous voulez réinitialiser uniquement les données explicitement générées par la graine UCKK.';
$string['continue'] = 'Continuer';
$string['course_seed_apply_dryrun_summary'] = 'Simulation de génération des cours terminée.';
$string['course_seed_apply_summary'] = 'Génération des cours terminée.';
$string['course_seed_created'] = 'Cours créé.';
$string['course_seed_dryrun_create'] = 'Simulation : le cours serait créé.';
$string['course_seed_dryrun_update'] = 'Simulation : le cours serait mis à jour.';
$string['course_seed_duplicate_idnumber'] = 'Identifiant de cours en double.';
$string['course_seed_duplicate_key'] = 'Clé de cours en double.';
$string['course_seed_duplicate_shortname'] = 'Nom court de cours en double.';
$string['course_seed_existing_not_seeded'] = 'Cours existant non marqué comme généré par UCKK.';
$string['course_seed_format_forced'] = 'Format de cours forcé selon le préréglage.';
$string['course_seed_missing_category'] = 'Catégorie de cours manquante.';
$string['course_seed_missing_fullname'] = 'Nom complet du cours manquant.';
$string['course_seed_missing_key'] = 'Clé de cours manquante.';
$string['course_seed_missing_shortname'] = 'Nom court du cours manquant.';
$string['course_seed_reset_deleted'] = 'Cours généré supprimé.';
$string['course_seed_reset_dryrun_delete'] = 'Simulation : le cours généré serait supprimé.';
$string['course_seed_reset_not_confirmed'] = 'Réinitialisation des cours non confirmée.';
$string['course_seed_reset_requires_confirm'] = 'La réinitialisation des cours exige une confirmation.';
$string['course_seed_reset_skip_site_course'] = 'Le cours site ne peut pas être réinitialisé.';
$string['course_seed_reset_skip_unmanaged'] = 'Cours ignoré parce qu’il n’est pas géré par la graine UCKK.';
$string['course_seed_reset_summary'] = 'Résumé de réinitialisation des cours.';
$string['course_seed_unknown_category'] = 'Catégorie de cours inconnue.';
$string['course_seed_unknown_template'] = 'Gabarit de cours inconnu.';
$string['course_seed_updated'] = 'Cours mis à jour.';
$string['course_seed_validation_ok'] = 'Validation des cours réussie.';
$string['course_seed_validation_summary'] = 'Résumé de validation des cours.';
$string['dryrun_desc'] = 'Exécute l’opération en simulation afin de produire un rapport sans modifier les données.';
$string['enabledpresets'] = 'Sections du registre académique activées';
$string['enabledpresets_desc'] = 'Liste des sections du registre académique JSON UCKK disponibles pour les opérations de génération, validation, réinitialisation ou export.';
$string['executionmode'] = 'Mode d’exécution';
$string['executionoptions'] = 'Options d’exécution';
$string['exportpreset_desc'] = 'Exporter une section du registre académique JSON UCKK au format canonique.';
$string['force_desc'] = 'Force l’opération lorsque celle-ci est autorisée et explicitement confirmée.';
$string['forceunavailable'] = 'L’option forcer n’est pas disponible pour cette opération.';
$string['idempotency'] = 'Idempotence';
$string['invalidcomponentselection'] = 'Sélection de composant invalide.';
$string['invalidpresetid'] = 'Identifiant de préréglage invalide.';
$string['invalidpresetschema'] = 'Schéma de préréglage invalide.';
$string['invalidpresetselection'] = 'Sélection de préréglage invalide.';
$string['invalidresetscope'] = 'Portée de réinitialisation invalide.';
$string['modecheckboxconflict'] = 'Sélectionnez un seul mode d’exécution.';
$string['norecentruns'] = 'Aucune exécution récente.';
$string['presetexportcompleted'] = 'Export du préréglage terminé.';
$string['presetexportunsupported'] = 'L’export de ce préréglage n’est pas pris en charge.';
$string['presethandlermissing'] = 'Gestionnaire de préréglage manquant.';
$string['presetitemsmissing'] = 'Le préréglage ne contient aucun élément à traiter.';
$string['presetmethodmissing'] = 'La méthode de préréglage requise est manquante.';
$string['presetrunning'] = 'Préréglage en cours de traitement.';
$string['presetschemainvalid'] = 'Le schéma du préréglage est invalide.';
$string['presetschemavalid'] = 'Le schéma du préréglage est valide.';
$string['protectnonseededcontent'] = 'Protéger le contenu non généré';
$string['protectnonseededcontent_desc'] = 'Empêche la graine de modifier ou supprimer les objets qui ne sont pas explicitement marqués comme générés par UCKK.';
$string['recentruns'] = 'Exécutions récentes';
$string['reportseed:created'] = 'Rapport créé.';
$string['reportseed:default_archive_production'] = 'Production d’archives';
$string['reportseed:default_archive_production_desc'] = 'Rapport UCKK par défaut : production d’archives.';
$string['reportseed:default_assembly_decisions'] = 'Décisions d’assemblée';
$string['reportseed:default_assembly_decisions_desc'] = 'Rapport UCKK par défaut : décisions d’assemblée.';
$string['reportseed:default_badge_awards'] = 'Attribution des badges';
$string['reportseed:default_badge_awards_desc'] = 'Rapport UCKK par défaut : attribution des badges.';
$string['reportseed:default_challenge_status'] = 'Statut des défis';
$string['reportseed:default_challenge_status_desc'] = 'Rapport UCKK par défaut : statut des défis.';
$string['reportseed:default_cohort_progress'] = 'Progression des cohortes';
$string['reportseed:default_cohort_progress_desc'] = 'Rapport UCKK par défaut : progression des cohortes.';
$string['reportseed:default_competency_matrix'] = 'Matrice des compétences';
$string['reportseed:default_competency_matrix_desc'] = 'Rapport UCKK par défaut : matrice des compétences.';
$string['reportseed:default_integrity_cases'] = 'Dossiers d’intégrité';
$string['reportseed:default_integrity_cases_desc'] = 'Rapport UCKK par défaut : dossiers d’intégrité.';
$string['reportseed:default_player_progress'] = 'Progression des Joueurs';
$string['reportseed:default_player_progress_desc'] = 'Rapport UCKK par défaut : progression des joueurs.';
$string['reportseed:default_program_progress'] = 'Progression des programmes';
$string['reportseed:default_program_progress_desc'] = 'Rapport UCKK par défaut : progression des programmes.';
$string['reportseed:default_seed_execution'] = 'Exécution de la graine';
$string['reportseed:default_seed_execution_desc'] = 'Rapport UCKK par défaut : exécution de la graine.';
$string['reportseed:error_duplicatekey'] = 'Clé de rapport en double.';
$string['reportseed:error_invalidcapability'] = 'Capacité de rapport invalide.';
$string['reportseed:error_invalidenabled'] = 'État activé/désactivé du rapport invalide.';
$string['reportseed:error_missingcapability'] = 'Capacité de rapport manquante.';
$string['reportseed:error_missingcomponent'] = 'Composant de rapport manquant.';
$string['reportseed:error_missingkey'] = 'Clé de rapport manquante.';
$string['reportseed:error_missingname'] = 'Nom de rapport manquant.';
$string['reportseed:error_missingsource'] = 'Source de rapport manquante.';
$string['reportseed:reset'] = 'Rapport réinitialisé.';
$string['reportseed:updated'] = 'Rapport mis à jour.';
$string['reportseed:validationok'] = 'Validation des rapports réussie.';
$string['reportseed:warning_componentmissing'] = 'Composant de rapport manquant ou indisponible.';
$string['reportseed:warning_unknownsource'] = 'Source de rapport inconnue.';
$string['resetallrequiresconfirmation'] = 'La réinitialisation complète exige une confirmation explicite.';
$string['resetallrequiresforce'] = 'La réinitialisation complète exige l’option forcer.';
$string['resetblocked'] = 'Réinitialisation bloquée.';
$string['resetcompletedwitherrors'] = 'Réinitialisation terminée avec erreurs.';
$string['resetconfirmationrequired'] = 'Confirmation de réinitialisation requise.';
$string['resetdisabled'] = 'La réinitialisation est désactivée.';
$string['resetdistribution_desc'] = 'Réinitialiser les données explicitement générées par la graine UCKK selon la portée sélectionnée.';
$string['resetformnotice'] = 'La réinitialisation ne doit jamais supprimer de contenu non marqué comme généré par la graine UCKK.';
$string['resetstarted'] = 'Réinitialisation démarrée.';
$string['rolecontextlevelsmissing'] = 'Niveaux de contexte du rôle manquants.';
$string['rolecontextlevelsupdated'] = 'Niveaux de contexte du rôle mis à jour.';
$string['roleduplicated'] = 'Rôle en double.';
$string['roleinvalidcontextlevel'] = 'Niveau de contexte de rôle invalide.';
$string['rolenamemissing'] = 'Nom de rôle manquant.';
$string['rolenotcanonicaltechnical'] = 'Le rôle n’est pas un rôle technique canonique UCKK.';
$string['rolenotfoundskipped'] = 'Rôle introuvable; opération ignorée.';
$string['rolepresetvalid'] = 'Préréglage de rôle valide.';
$string['rolesdryruncomplete'] = 'Simulation des rôles terminée.';
$string['roleseeded'] = 'Rôles générés.';
$string['roleshortnamemissing'] = 'Nom court de rôle manquant.';
$string['rolespresetempty'] = 'Le préréglage de rôles est vide.';
$string['rolesresetcomplete'] = 'Réinitialisation des rôles terminée.';
$string['rolesresetrequiresconfirmation'] = 'La réinitialisation des rôles exige une confirmation.';
$string['rolesseedcomplete'] = 'Génération des rôles terminée.';
$string['rolesvalidationcomplete'] = 'Validation des rôles terminée.';
$string['rolesymbolicnotallowed'] = 'Les rôles symboliques ne peuvent pas être créés comme rôles Moodle techniques.';
$string['rolewouldseed'] = 'Simulation : le rôle serait généré.';
$string['rollbackplan_desc'] = 'Produit un plan de retour arrière sans exécuter la suppression ou la modification des données.';
$string['rollbackplanrequired'] = 'Un plan de retour arrière est requis pour cette opération.';
$string['scope:reset_all_uckk_seeded_content'] = 'Tout le contenu UCKK généré';
$string['scope:reset_seed_logs'] = 'Journaux de graine';
$string['scope:reset_seeded_badges'] = 'Badges générés';
$string['scope:reset_seeded_content'] = 'Contenu généré';
$string['scope:reset_seeded_courses'] = 'Cours générés';
$string['scope:reset_seeded_roles'] = 'Rôles générés';
$string['seedauthoritynotice'] = 'La graine UCKK n’accorde pas d’autorité par elle-même. Les permissions, validations, badges, compétences et archives restent contrôlés par Moodle et les plugins propriétaires.';
$string['seedcategorycreated'] = 'Catégorie créée.';
$string['seedcategorydeleted'] = 'Catégorie supprimée.';
$string['seedcategorydeletefailed'] = 'Impossible de supprimer la catégorie.';
$string['seedcategoryduplicateidnumber'] = 'Identifiant de catégorie déjà utilisé.';
$string['seedcategoryduplicatekey'] = 'Clé de catégorie en double.';
$string['seedcategoryfailed'] = 'Le traitement de la catégorie a échoué.';
$string['seedcategorymissingidnumber'] = 'Identifiant de catégorie manquant.';
$string['seedcategorymissingkey'] = 'Clé de catégorie manquante.';
$string['seedcategorymissingname'] = 'Nom de catégorie manquant.';
$string['seedcategorynotfound'] = 'Catégorie introuvable.';
$string['seedcategoryparentnotinpreset'] = 'La catégorie parente n’est pas présente dans le préréglage.';
$string['seedcategoryresetblockednotempty'] = 'La réinitialisation de la catégorie est bloquée parce qu’elle n’est pas vide.';
$string['seedcategoryunchanged'] = 'Catégorie inchangée.';
$string['seedcategoryupdated'] = 'Catégorie mise à jour.';
$string['seedcategoryvalidationok'] = 'Catégorie validée.';
$string['seedcategorywouldcreate'] = 'Simulation : la catégorie serait créée.';
$string['seedcategorywoulddelete'] = 'Simulation : la catégorie serait supprimée.';
$string['seedcategorywouldupdate'] = 'Simulation : la catégorie serait mise à jour.';
$string['seedcompletedwitherrors'] = 'Génération terminée avec erreurs.';
$string['seeddistribution_desc'] = 'Créer ou aligner les objets UCKK initiaux à partir des préréglages activés.';
$string['seeddistributionnotice'] = 'La génération est idempotente : relancer l’opération ne doit pas créer de doublons.';
$string['seedidempotencynotice'] = 'Les objets existants sont comparés aux préréglages avant création ou mise à jour.';
$string['seedpresetempty'] = 'Le préréglage de graine est vide.';
$string['seedtooldisabled'] = 'L’outil de graine UCKK est désactivé.';
$string['seedtoolintro'] = 'Utilisez cet outil pour générer, valider, réinitialiser ou exporter la structure UCKK initiale.';
$string['settings_cli'] = 'Commandes CLI';
$string['settings_cli_desc'] = 'Réglages liés à l’utilisation des commandes en ligne de la graine UCKK.';
$string['settings_general'] = 'Réglages généraux';
$string['settings_general_desc'] = 'Réglages principaux de l’outil de graine UCKK.';
$string['settings_presets'] = 'Registre académique JSON';
$string['settings_presets_desc'] = 'Réglages liés au dossier academic_registry_json et aux fichiers JSON du registre académique UCKK.';
$string['settings_safety'] = 'Sécurité des opérations';
$string['settings_safety_desc'] = 'Réglages qui limitent les réinitialisations, forçages et modifications sensibles.';
$string['tooldisablednotice'] = 'Cet outil est désactivé par les réglages du site.';
$string['validatedistribution_desc'] = 'Vérifier les préréglages et l’état de la distribution UCKK sans écrire de données.';
$string['validationcompletedwithwarnings'] = 'Validation terminée avec avertissements.';
$string['validationreport_haserrors'] = 'Le rapport de validation contient des erreurs.';
$string['validationreport_haswarnings'] = 'Le rapport de validation contient des avertissements.';
$string['validationreport_ok'] = 'Le rapport de validation ne contient pas d’erreur.';
$string['validationstarted'] = 'Validation démarrée.';

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
