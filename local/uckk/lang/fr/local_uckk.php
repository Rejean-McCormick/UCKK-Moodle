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
 * French language strings for the UCKK institutional core plugin.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin identity.
$string['pluginname'] = 'Noyau UCKK';
$string['uckk'] = 'UCKK';
$string['uckkfullname'] = 'Univers-Cité King Klown';
$string['uckkcampus'] = 'Campus UCKK';
$string['uckkmoodle'] = 'UCKK-Moodle';
$string['uckkcore'] = 'Noyau institutionnel UCKK';
$string['uckkcore_desc'] = 'Registre institutionnel central de l’Univers-Cité King Klown : programmes, parcours, profils Joueurs, rôles symboliques, provenance, visibilité et navigation partagée.';
$string['uckknotaccredited'] = 'Reconnaissance interne UCKK — ne constitue pas un diplôme public accrédité.';
$string['internalrecognition'] = 'Reconnaissance interne';
$string['experimentalstructure'] = 'Structure expérimentale interne';

// Navigation.
$string['nav_campus'] = 'Campus UCKK';
$string['nav_dashboard'] = 'Tableau de bord UCKK';
$string['nav_programs'] = 'Programmes';
$string['nav_pathways'] = 'Parcours';
$string['nav_profiles'] = 'Profils Joueurs';
$string['nav_canon'] = 'Canon UCKK';
$string['nav_provenance'] = 'Provenance';
$string['nav_reflections'] = 'Réflexions';
$string['nav_reports'] = 'Rapports';
$string['nav_settings'] = 'Réglages UCKK';
$string['nav_administration'] = 'Administration UCKK';
$string['nav_integrity'] = 'Intégrité';
$string['nav_archives'] = 'Archives';
$string['nav_assemblies'] = 'Assemblées';
$string['nav_challenges'] = 'Défis';

// General actions.
$string['action_view'] = 'Voir';
$string['action_create'] = 'Créer';
$string['action_edit'] = 'Modifier';
$string['action_update'] = 'Mettre à jour';
$string['action_delete'] = 'Supprimer';
$string['action_archive'] = 'Archiver';
$string['action_restore'] = 'Restaurer';
$string['action_validate'] = 'Valider';
$string['action_cancel'] = 'Annuler';
$string['action_confirm'] = 'Confirmer';
$string['action_save'] = 'Enregistrer';
$string['action_submit'] = 'Soumettre';
$string['action_export'] = 'Exporter';
$string['action_import'] = 'Importer';
$string['action_assign'] = 'Assigner';
$string['action_unassign'] = 'Retirer';
$string['action_continue'] = 'Continuer';
$string['action_back'] = 'Retour';
$string['action_search'] = 'Rechercher';
$string['action_filter'] = 'Filtrer';
$string['action_reset'] = 'Réinitialiser';

// Common labels.
$string['name'] = 'Nom';
$string['shortname'] = 'Nom court';
$string['fullname'] = 'Nom complet';
$string['description'] = 'Description';
$string['summary'] = 'Résumé';
$string['status'] = 'Statut';
$string['visibility'] = 'Visibilité';
$string['sortorder'] = 'Ordre d’affichage';
$string['metadata'] = 'Métadonnées';
$string['timecreated'] = 'Date de création';
$string['timemodified'] = 'Date de modification';
$string['createdby'] = 'Créé par';
$string['modifiedby'] = 'Modifié par';
$string['context'] = 'Contexte';
$string['component'] = 'Composant';
$string['itemtype'] = 'Type d’élément';
$string['itemid'] = 'Identifiant de l’élément';
$string['source'] = 'Source';
$string['state'] = 'État';
$string['date'] = 'Date';
$string['owner'] = 'Responsable';
$string['author'] = 'Auteur';
$string['none'] = 'Aucun';
$string['unknown'] = 'Inconnu';
$string['notavailable'] = 'Non disponible';

// Statuses.
$string['status_draft'] = 'Brouillon';
$string['status_active'] = 'Actif';
$string['status_hidden'] = 'Masqué';
$string['status_pending'] = 'En attente';
$string['status_pending_review'] = 'En attente de révision';
$string['status_submitted'] = 'Soumis';
$string['status_validated'] = 'Validé';
$string['status_rejected'] = 'Rejeté';
$string['status_correction_required'] = 'Correction requise';
$string['status_contested'] = 'Contesté';
$string['status_invalidated'] = 'Invalidé';
$string['status_closed'] = 'Fermé';
$string['status_archived'] = 'Archivé';
$string['status_deleted'] = 'Supprimé';
$string['status_cancelled'] = 'Annulé';
$string['status_recorded'] = 'Enregistré';
$string['status_unverified'] = 'Non vérifié';

// Visibility.
$string['visibility_private'] = 'Privé';
$string['visibility_user'] = 'Utilisateur';
$string['visibility_group'] = 'Groupe';
$string['visibility_course'] = 'Cours';
$string['visibility_cohort'] = 'Cohorte';
$string['visibility_program'] = 'Programme';
$string['visibility_institution'] = 'Institution';
$string['visibility_public'] = 'Public';
$string['visibility_restricted'] = 'Restreint';
$string['visibility_restricted_integrity'] = 'Restreint — intégrité';

// Programs.
$string['program'] = 'Programme';
$string['programs'] = 'Programmes';
$string['program_create'] = 'Créer un programme';
$string['program_edit'] = 'Modifier le programme';
$string['program_delete'] = 'Supprimer le programme';
$string['program_archive'] = 'Archiver le programme';
$string['program_shortname'] = 'Nom court du programme';
$string['program_fullname'] = 'Nom complet du programme';
$string['program_type'] = 'Type de programme';
$string['program_category'] = 'Catégorie Moodle liée';
$string['program_status'] = 'Statut du programme';
$string['program_notfound'] = 'Programme UCKK introuvable.';
$string['programshortnameexists'] = 'Ce nom court de programme UCKK existe déjà : {$a}';

$string['programtype_tronccommun'] = 'Tronc commun';
$string['programtype_baccalaureat'] = 'Baccalauréat interne';
$string['programtype_mineure'] = 'Mineure interne';
$string['programtype_lab'] = 'Laboratoire';
$string['programtype_seminar'] = 'Séminaire';
$string['programtype_transversal'] = 'Programme transversal';

$string['program_tronccommun'] = 'Tronc commun obligatoire';
$string['program_grand_jeu_social'] = 'Baccalauréat du Grand Jeu social';
$string['program_koa_digital'] = 'Baccalauréat en Architecture de l’écosystème digital kOA';
$string['program_sociotechnique'] = 'Baccalauréat en Architecture sociotechnique';
$string['program_sciences_politiques'] = 'Baccalauréat en Sciences politiques';
$string['program_economie'] = 'Baccalauréat en Économie';
$string['program_ecologie'] = 'Baccalauréat en Écologie';
$string['program_metaphysique'] = 'Baccalauréat en Métaphysique';
$string['program_ia_gouvernable'] = 'Baccalauréat en Intelligence artificielle gouvernable';
$string['program_linguistique'] = 'Baccalauréat en Linguistique et architecture du sens';
$string['program_intervention_sociale'] = 'Baccalauréat en Intervention sociale et systèmes humains';
$string['program_medias_vivants'] = 'Mineure Médias vivants et théâtre public responsable';
$string['program_laboratoires'] = 'Séminaires avancés et laboratoires';

// Pathways.
$string['pathway'] = 'Parcours';
$string['pathways'] = 'Parcours';
$string['pathway_create'] = 'Créer un parcours';
$string['pathway_edit'] = 'Modifier le parcours';
$string['pathway_delete'] = 'Supprimer le parcours';
$string['pathway_archive'] = 'Archiver le parcours';
$string['pathway_assign'] = 'Assigner le parcours';
$string['pathway_unassign'] = 'Retirer le parcours';
$string['pathway_status'] = 'Statut du parcours';
$string['pathway_program'] = 'Programme lié';
$string['pathway_requirements'] = 'Exigences du parcours';
$string['pathway_requiredcourses'] = 'Cours requis';
$string['pathway_requiredbadges'] = 'Badges requis';
$string['pathway_requiredcompetencies'] = 'Compétences requises';
$string['pathway_progress'] = 'Progression du parcours';
$string['pathway_completed'] = 'Parcours complété';
$string['pathway_notcompleted'] = 'Parcours non complété';
$string['pathway_notfound'] = 'Parcours UCKK introuvable.';
$string['pathwayshortnameexists'] = 'Ce nom court de parcours UCKK existe déjà : {$a}';
$string['cannotassigninactivepathway'] = 'Impossible d’assigner un parcours UCKK inactif : {$a}';
$string['pathwayassigned'] = 'Parcours assigné.';
$string['pathwayunassigned'] = 'Parcours retiré.';
$string['pathwaycreated'] = 'Parcours créé.';
$string['pathwayupdated'] = 'Parcours mis à jour.';
$string['pathwaydeleted'] = 'Parcours supprimé.';
$string['pathwayarchived'] = 'Parcours archivé.';

// Courses.
$string['course'] = 'Cours';
$string['courses'] = 'Cours';
$string['coursecode'] = 'Code de cours';
$string['course_required'] = 'Cours requis';
$string['course_completed'] = 'Cours complété';
$string['course_notcompleted'] = 'Cours non complété';

$string['tc101'] = 'UCKK-TC101 — Cartographie des idées avec l’IA';
$string['tc102'] = 'UCKK-TC102 — Intelligence collective, expertise située et décision légitime';
$string['tc103'] = 'UCKK-TC103 — Agitation institutionnelle et mesure de l’utilité réelle';
$string['tc104'] = 'UCKK-TC104 — Société des flux : argent, information et pouvoir';
$string['tc105'] = 'UCKK-TC105 — Fiction fondatrice, vérité morale et récits symboliques';
$string['tc106'] = 'UCKK-TC106 — Mobilisation multi-corridor et coopération pratique';
$string['tc107'] = 'UCKK-TC107 — Introduction à kOA : connaissance, décision, action, mémoire';
$string['tc108'] = 'UCKK-TC108 — Éthique, intégrité et Inquisiteur méthodologique';

// Player profiles.
$string['player'] = 'Joueur';
$string['players'] = 'Joueurs';
$string['playerprofile'] = 'Profil Joueur';
$string['playerprofiles'] = 'Profils Joueurs';
$string['playerprofile_create'] = 'Créer un profil Joueur';
$string['playerprofile_edit'] = 'Modifier le profil Joueur';
$string['playerprofile_view'] = 'Voir le profil Joueur';
$string['displaytitle'] = 'Titre affiché';
$string['symbolicroles'] = 'Rôles symboliques';
$string['activepathways'] = 'Parcours actifs';
$string['portfolioarchive'] = 'Archive de portfolio';
$string['integrityflags'] = 'Marqueurs d’intégrité';
$string['profilevisibility'] = 'Visibilité du profil';
$string['profileupdated'] = 'Profil Joueur mis à jour.';
$string['profilenotfound'] = 'Profil Joueur introuvable.';
$string['joueurlucide'] = 'Joueur lucide';

// Symbolic roles.
$string['symbolicrole'] = 'Rôle symbolique';
$string['symbolicroles'] = 'Rôles symboliques';
$string['symbolicrole_joueur'] = 'Joueur';
$string['symbolicrole_joueur_lucide'] = 'Joueur lucide';
$string['symbolicrole_batisseur'] = 'Bâtisseur';
$string['symbolicrole_archiviste'] = 'Archiviste';
$string['symbolicrole_inquisiteur'] = 'Inquisiteur';
$string['symbolicrole_cartographe'] = 'Cartographe';
$string['symbolicrole_architecte_sens'] = 'Architecte du sens';
$string['symbolicrole_architecte_opportunites'] = 'Architecte d’opportunités';
$string['symbolicrole_gardien_systemes_vivants'] = 'Gardien des systèmes vivants';
$string['symbolicrole_gardien_preuve'] = 'Gardien de la preuve';
$string['symbolicroles_notice'] = 'Les rôles symboliques UCKK ne sont pas automatiquement des rôles techniques Moodle.';

// Canon.
$string['canon'] = 'Canon UCKK';
$string['canonitem'] = 'Élément canonique';
$string['canonitems'] = 'Éléments canoniques';
$string['canon_create'] = 'Créer un élément canonique';
$string['canon_edit'] = 'Modifier l’élément canonique';
$string['canon_view'] = 'Voir l’élément canonique';
$string['canon_key'] = 'Clé canonique';
$string['canon_title'] = 'Titre canonique';
$string['canon_body'] = 'Contenu canonique';
$string['canon_status'] = 'Statut canonique';
$string['canonitemupdated'] = 'Élément canonique mis à jour.';

// Provenance.
$string['provenance'] = 'Provenance';
$string['provenancerecord'] = 'Enregistrement de provenance';
$string['provenancerecords'] = 'Enregistrements de provenance';
$string['provenance_action'] = 'Action';
$string['provenance_hash'] = 'Empreinte';
$string['provenance_state'] = 'État de provenance';
$string['provenance_sourcecomponent'] = 'Composant source';
$string['provenance_sourceid'] = 'Identifiant source';
$string['provenance_sourcetext'] = 'Description de la source';
$string['provenance_recorded'] = 'Provenance enregistrée';
$string['provenance_human'] = 'Provenance humaine';
$string['provenance_ai_assisted'] = 'Assisté par IA';
$string['provenance_imported'] = 'Importé';
$string['provenance_system'] = 'Système';
$string['provenance_archive'] = 'Archive';
$string['provenance_assembly'] = 'Assemblée';
$string['provenance_challenge'] = 'Défi';
$string['provenance_integrity'] = 'Intégrité';

// Reflections.
$string['reflection'] = 'Réflexion';
$string['reflections'] = 'Réflexions';
$string['reflection_submit'] = 'Soumettre une réflexion';
$string['reflection_submitted'] = 'Réflexion soumise.';
$string['reflection_title'] = 'Titre de la réflexion';
$string['reflection_body'] = 'Texte de la réflexion';
$string['reflection_type'] = 'Type de réflexion';
$string['reflection_visibility'] = 'Visibilité de la réflexion';
$string['reflection_status'] = 'Statut de la réflexion';
$string['reflection_validationstate'] = 'État de validation';
$string['reflection_aiassisted'] = 'Réflexion assistée par IA';
$string['reflection_metadata'] = 'Métadonnées de la réflexion';
$string['reflection_notfound'] = 'Réflexion introuvable.';
$string['reflection_bodyempty'] = 'Le texte de la réflexion ne peut pas être vide.';

$string['reflectiontype_general'] = 'Générale';
$string['reflectiontype_portfolio'] = 'Portfolio';
$string['reflectiontype_ai_journal'] = 'Journal de collaboration IA';
$string['reflectiontype_assembly'] = 'Assemblée';
$string['reflectiontype_challenge'] = 'Défi';
$string['reflectiontype_course'] = 'Cours';
$string['reflectiontype_integrity'] = 'Intégrité';

// Portfolio.
$string['portfolio'] = 'Portfolio';
$string['portfolio_joueurlucide'] = 'Portfolio de Joueur lucide';
$string['portfolio_centralproof'] = 'Preuve centrale du tronc commun';
$string['portfolio_items'] = 'Éléments du portfolio';
$string['portfolio_addreflection'] = 'Ajouter une réflexion au portfolio';
$string['portfolio_evidence'] = 'Preuves du portfolio';
$string['portfolio_ai_journal'] = 'Journal de collaboration IA';
$string['portfolio_assembly_record'] = 'Compte rendu d’assemblée';
$string['portfolio_challenge_proposal'] = 'Proposition de défi';
$string['portfolio_corrected_work'] = 'Travail corrigé';

// Competencies and badges.
$string['competency'] = 'Compétence';
$string['competencies'] = 'Compétences';
$string['badge'] = 'Badge';
$string['badges'] = 'Badges';
$string['requiredcompetencies'] = 'Compétences requises';
$string['requiredbadges'] = 'Badges requis';

$string['comp_read_game'] = 'Lire le Grand Jeu social';
$string['comp_map_system'] = 'Cartographier un système';
$string['comp_distinguish_claims'] = 'Distinguer fait, hypothèse, interprétation, récit et décision';
$string['comp_use_ai_non_sovereign'] = 'Utiliser l’IA comme outil non souverain';
$string['comp_produce_proof'] = 'Produire une preuve vérifiable';
$string['comp_participate_assembly'] = 'Participer à une assemblée structurée';
$string['comp_responsible_mobilisation'] = 'Concevoir une mobilisation responsable';
$string['comp_document_decision'] = 'Documenter une décision';
$string['comp_archive_learning'] = 'Archiver un apprentissage';
$string['comp_apply_ethics'] = 'Appliquer l’éthique UCKK';
$string['comp_detect_hidden_authority'] = 'Détecter l’autorité cachée';
$string['comp_build_useful_artefact'] = 'Construire un artefact utile';
$string['comp_contestability'] = 'Assurer la contestabilité';
$string['comp_know_choose_act_remember'] = 'Relier connaissance, décision, action et mémoire';

// UCKK cycle and concepts.
$string['cycle_koa'] = 'Connaître → Choisir → Agir → Se souvenir';
$string['know'] = 'Connaître';
$string['choose'] = 'Choisir';
$string['act'] = 'Agir';
$string['remember'] = 'Se souvenir';
$string['grandjeu'] = 'Grand Jeu';
$string['grandjeusocial'] = 'Grand Jeu social';
$string['preuve'] = 'Preuve';
$string['preuves'] = 'Preuves';
$string['archive'] = 'Archive';
$string['archives'] = 'Archives';
$string['assemblee'] = 'Assemblée';
$string['assemblees'] = 'Assemblées';
$string['defi'] = 'Défi';
$string['defis'] = 'Défis';
$string['inquisiteur'] = 'Inquisiteur';
$string['archiviste'] = 'Archiviste';
$string['kristal'] = 'Kristal';
$string['kristals'] = 'Kristals';
$string['koa'] = 'kOA';
$string['koadigitalecosystem'] = 'kOA Digital Ecosystem';
$string['kingklown'] = 'King Klown';

// AI governance.
$string['ai'] = 'IA';
$string['ai_assisted'] = 'Assisté par IA';
$string['ai_nonsovereign'] = 'IA non souveraine';
$string['ai_humanvalidationrequired'] = 'Validation humaine requise';
$string['ai_warning'] = 'L’IA peut aider à formuler, cartographier ou clarifier. Elle ne décide pas, ne valide pas et ne remplace pas la responsabilité humaine.';
$string['ai_policy_non_sovereign'] = 'IA non souveraine — validation humaine requise';

// Settings.
$string['settings'] = 'Réglages UCKK';
$string['settings_general'] = 'Réglages généraux';
$string['settings_general_desc'] = 'Configuration générale du noyau institutionnel UCKK.';
$string['settings_campus'] = 'Campus';
$string['settings_campus_desc'] = 'Réglages liés à l’identité et au fonctionnement du campus UCKK.';
$string['settings_programs'] = 'Programmes et parcours';
$string['settings_programs_desc'] = 'Réglages liés aux programmes, parcours et profils Joueurs.';
$string['settings_provenance'] = 'Provenance';
$string['settings_provenance_desc'] = 'Réglages liés à la traçabilité et à la mémoire institutionnelle.';
$string['settings_ai'] = 'IA gouvernable';
$string['settings_ai_desc'] = 'Réglages liés à l’utilisation de l’IA comme outil non souverain.';

$string['setting_enablecampus'] = 'Activer le campus UCKK';
$string['setting_enablecampus_desc'] = 'Active les fonctions institutionnelles centrales de UCKK-Moodle.';
$string['setting_enableprovenance'] = 'Activer la provenance';
$string['setting_enableprovenance_desc'] = 'Enregistre la provenance des objets UCKK lorsque les tables nécessaires sont disponibles.';
$string['setting_enablereflections'] = 'Activer les réflexions';
$string['setting_enablereflections_desc'] = 'Permet aux Joueurs de soumettre des réflexions liées aux cours, parcours, portfolios et journaux IA.';
$string['setting_enableaiwarnings'] = 'Afficher les avertissements IA';
$string['setting_enableaiwarnings_desc'] = 'Affiche des rappels indiquant que l’IA est assistive et non souveraine.';
$string['setting_defaultvisibility'] = 'Visibilité par défaut';
$string['setting_defaultvisibility_desc'] = 'Visibilité par défaut des profils, réflexions et objets UCKK créés par le noyau.';

// Capabilities.
$string['uckk:viewcampus'] = 'Voir le campus UCKK';
$string['uckk:viewcampus_desc'] = 'Permet de voir les éléments de base du campus UCKK.';

$string['uckk:manageprograms'] = 'Gérer les programmes UCKK';
$string['uckk:manageprograms_desc'] = 'Permet de créer, modifier, archiver ou supprimer les programmes UCKK.';

$string['uckk:managepathways'] = 'Gérer les parcours UCKK';
$string['uckk:managepathways_desc'] = 'Permet de créer, modifier, archiver, assigner ou retirer les parcours UCKK.';

$string['uckk:manageprofiles'] = 'Gérer les profils Joueurs';
$string['uckk:manageprofiles_desc'] = 'Permet de gérer les profils Joueurs, titres affichés, parcours actifs et informations institutionnelles associées.';

$string['uckk:managecanon'] = 'Gérer le canon UCKK';
$string['uckk:managecanon_desc'] = 'Permet de gérer les éléments canoniques UCKK.';

$string['uckk:viewreports'] = 'Voir les rapports UCKK';
$string['uckk:viewreports_desc'] = 'Permet de consulter les rapports institutionnels UCKK.';

$string['uckk:exportdata'] = 'Exporter les données UCKK';
$string['uckk:exportdata_desc'] = 'Permet d’exporter des données UCKK autorisées selon le contexte et les permissions.';

$string['uckk:viewrestrictedprofile'] = 'Voir les profils restreints';
$string['uckk:viewrestrictedprofile_desc'] = 'Permet de voir certaines informations restreintes des profils Joueurs lorsque le contexte l’autorise.';

// External services.
$string['service_submit_reflection'] = 'Soumettre une réflexion UCKK';
$string['service_submit_reflection_desc'] = 'Service externe permettant de soumettre une réflexion UCKK.';
$string['service_get_player_dashboard'] = 'Obtenir le tableau de bord Joueur';
$string['service_get_player_dashboard_desc'] = 'Service externe permettant de récupérer les données du tableau de bord Joueur.';
$string['service_get_programs'] = 'Obtenir les programmes UCKK';
$string['service_get_programs_desc'] = 'Service externe permettant de récupérer la liste des programmes UCKK.';
$string['service_get_pathway_status'] = 'Obtenir l’état d’un parcours';
$string['service_get_pathway_status_desc'] = 'Service externe permettant de récupérer l’état d’un parcours UCKK.';

// Events.
$string['event_pathway_created'] = 'Parcours UCKK créé';
$string['event_pathway_updated'] = 'Parcours UCKK mis à jour';
$string['event_pathway_completed'] = 'Parcours UCKK complété';
$string['event_pathway_assigned'] = 'Parcours UCKK assigné';
$string['event_pathway_unassigned'] = 'Parcours UCKK retiré';
$string['event_pathway_status_changed'] = 'Statut du parcours UCKK modifié';
$string['event_player_profile_updated'] = 'Profil Joueur mis à jour';
$string['event_canon_item_updated'] = 'Élément canonique UCKK mis à jour';
$string['event_reflection_submitted'] = 'Réflexion UCKK soumise';
$string['event_provenance_recorded'] = 'Provenance UCKK enregistrée';

// Privacy.
$string['privacy:metadata'] = 'Le noyau UCKK stocke des données institutionnelles liées aux programmes, parcours, profils Joueurs, rôles symboliques, réflexions, provenance et visibilité.';
$string['privacy:metadata:local_uckk_program'] = 'Informations sur les programmes UCKK.';
$string['privacy:metadata:local_uckk_pathway'] = 'Informations sur les parcours UCKK.';
$string['privacy:metadata:local_uckk_player'] = 'Informations sur les profils Joueurs UCKK.';
$string['privacy:metadata:local_uckk_role'] = 'Informations sur les rôles symboliques UCKK.';
$string['privacy:metadata:local_uckk_canon'] = 'Éléments du canon UCKK.';
$string['privacy:metadata:local_uckk_prov'] = 'Enregistrements de provenance UCKK.';
$string['privacy:metadata:local_uckk_reflect'] = 'Réflexions soumises par les utilisateurs dans UCKK-Moodle.';
$string['privacy:metadata:userid'] = 'Identifiant de l’utilisateur associé.';
$string['privacy:metadata:courseid'] = 'Identifiant du cours Moodle associé.';
$string['privacy:metadata:pathwayid'] = 'Identifiant du parcours UCKK associé.';
$string['privacy:metadata:profileid'] = 'Identifiant du profil Joueur associé.';
$string['privacy:metadata:title'] = 'Titre fourni.';
$string['privacy:metadata:body'] = 'Contenu fourni.';
$string['privacy:metadata:visibility'] = 'Visibilité de l’objet.';
$string['privacy:metadata:status'] = 'Statut de l’objet.';
$string['privacy:metadata:metadata'] = 'Métadonnées structurées.';
$string['privacy:metadata:timecreated'] = 'Date de création.';
$string['privacy:metadata:timemodified'] = 'Date de dernière modification.';
$string['privacy:path:uckk'] = 'UCKK';
$string['privacy:path:profile'] = 'Profil Joueur';
$string['privacy:path:pathways'] = 'Parcours';
$string['privacy:path:reflections'] = 'Réflexions';
$string['privacy:path:provenance'] = 'Provenance';

// Errors.
$string['error_invalidprogramid'] = 'Identifiant de programme UCKK invalide.';
$string['error_invalidpathwayid'] = 'Identifiant de parcours UCKK invalide.';
$string['error_invaliduserid'] = 'Identifiant utilisateur invalide.';
$string['error_invalidstatus'] = 'Statut UCKK invalide.';
$string['error_invalidvisibility'] = 'Visibilité UCKK invalide.';
$string['error_invalidreflectiontype'] = 'Type de réflexion UCKK invalide.';
$string['error_invalidmetadata'] = 'Objet JSON de métadonnées invalide.';
$string['error_missingprogramid'] = 'Identifiant de programme UCKK manquant.';
$string['error_missingpathwayshortname'] = 'Nom court du parcours UCKK manquant.';
$string['error_missingpathwayfullname'] = 'Nom complet du parcours UCKK manquant.';
$string['error_missingreflectionbody'] = 'Le texte de la réflexion est obligatoire.';
$string['error_permissiondenied'] = 'Permission UCKK refusée.';
$string['error_contextrequired'] = 'Contexte Moodle requis.';
$string['error_tablenotready'] = 'La table UCKK requise n’est pas encore disponible.';
$string['error_cannotdeleteactive'] = 'Impossible de supprimer un élément UCKK actif sans l’archiver ou le désactiver.';
$string['error_cannotassigninactivepathway'] = 'Impossible d’assigner un parcours inactif.';
$string['error_pathwaynotfound'] = 'Parcours UCKK introuvable.';
$string['error_programnotfound'] = 'Programme UCKK introuvable.';
$string['error_profilenotfound'] = 'Profil Joueur introuvable.';

// Warnings.
$string['warning_internalrecognition'] = 'Les reconnaissances UCKK sont internes et ne prétendent pas équivaloir automatiquement à des diplômes accrédités.';
$string['warning_ai_nonsovereign'] = 'L’IA est un outil d’assistance. Elle ne valide pas, ne sanctionne pas et ne décide pas.';
$string['warning_symbolicroles'] = 'Les rôles symboliques UCKK ne sont pas des rôles techniques Moodle.';
$string['warning_publicvisibility'] = 'La visibilité publique doit être utilisée avec prudence et validation humaine.';
$string['warning_provenancefailsoft'] = 'La provenance est conçue pour tracer les actions, mais une erreur de provenance ne doit pas corrompre l’opération principale.';

// Success messages.
$string['success_saved'] = 'Enregistré.';
$string['success_created'] = 'Créé.';
$string['success_updated'] = 'Mis à jour.';
$string['success_deleted'] = 'Supprimé.';
$string['success_archived'] = 'Archivé.';
$string['success_assigned'] = 'Assigné.';
$string['success_unassigned'] = 'Retiré.';
$string['success_submitted'] = 'Soumis.';
$string['success_exported'] = 'Exporté.';
$string['success_imported'] = 'Importé.';

// Empty states.
$string['empty_programs'] = 'Aucun programme UCKK disponible.';
$string['empty_pathways'] = 'Aucun parcours UCKK disponible.';
$string['empty_profiles'] = 'Aucun profil Joueur disponible.';
$string['empty_reflections'] = 'Aucune réflexion UCKK disponible.';
$string['empty_provenance'] = 'Aucune provenance enregistrée.';
$string['empty_canon'] = 'Aucun élément canonique disponible.';

// Diagnostics.
$string['diagnostics'] = 'Diagnostics UCKK';
$string['diagnostics_tables'] = 'Tables UCKK';
$string['diagnostics_capabilities'] = 'Capacités UCKK';
$string['diagnostics_services'] = 'Services UCKK';
$string['diagnostics_events'] = 'Événements UCKK';
$string['diagnostics_privacy'] = 'Confidentialité UCKK';
$string['diagnostics_ok'] = 'Diagnostic UCKK réussi.';
$string['diagnostics_warning'] = 'Diagnostic UCKK avec avertissements.';
$string['diagnostics_error'] = 'Diagnostic UCKK en erreur.';