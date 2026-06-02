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
$string['programtype_baccalaureat'] = 'Voie UCKK — Niveau visé : Puissance opératoire';
$string['programtype_mineure'] = 'Voie UCKK — Niveau visé : Initiation';
$string['programtype_lab'] = 'Laboratoire';
$string['programtype_seminar'] = 'Séminaire';
$string['programtype_transversal'] = 'Programme transversal';

$string['program_tronccommun'] = 'Tronc commun obligatoire';
$string['program_grand_jeu_social'] = 'Voie du Grand Jeu social';
$string['program_koa_digital'] = 'Voie de l’Architecture de l’écosystème digital kOA';
$string['program_sociotechnique'] = 'Voie de l’Architecture sociotechnique';
$string['program_sciences_politiques'] = 'Voie des Sciences politiques';
$string['program_economie'] = 'Voie de l’Économie';
$string['program_ecologie'] = 'Voie de l’Écologie';
$string['program_metaphysique'] = 'Voie de la Métaphysique';
$string['program_ia_gouvernable'] = 'Voie de l’Intelligence artificielle gouvernable';
$string['program_linguistique'] = 'Voie de la Linguistique et de l’architecture du sens';
$string['program_intervention_sociale'] = 'Voie de l’Intervention sociale et des systèmes humains';
$string['program_medias_vivants'] = 'Voie des Médias vivants et du théâtre public responsable';
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

// Additional strings discovered by the string inventory.
$string['ainonsovereign'] = 'IA non souveraine';
$string['ainonsovereign_desc'] = 'Rappelle que l’IA peut assister, mais ne peut pas décider, valider, sanctionner ou remplacer la responsabilité humaine.';
$string['aiwarning'] = 'Avertissement IA';
$string['aiwarning_desc'] = 'Message affiché lorsque des fonctions assistées par IA sont disponibles.';
$string['allowpublicarchives'] = 'Autoriser les archives publiques';
$string['allowpublicarchives_desc'] = 'Permet la publication d’archives avec visibilité publique lorsque les permissions, la confidentialité et la validation humaine le permettent.';
$string['allowpublicevidence'] = 'Autoriser les preuves publiques';
$string['allowpublicevidence_desc'] = 'Permet la publication de preuves avec visibilité publique après validation et contrôle de confidentialité.';
$string['allowpublicpathways'] = 'Autoriser les parcours publics';
$string['allowpublicpathways_desc'] = 'Permet de rendre certains parcours visibles publiquement lorsque la politique institutionnelle l’autorise.';
$string['autocreateplayerprofile'] = 'Créer automatiquement le profil Joueur';
$string['autocreateplayerprofile_desc'] = 'Crée un profil Joueur UCKK lorsqu’un utilisateur accède au campus et qu’aucun profil n’existe encore.';
$string['back'] = 'Retour';
$string['boundarynotice'] = 'Avis de frontière institutionnelle';
$string['boundarynotice_desc'] = 'Affiche un rappel indiquant que UCKK-Moodle est un campus Moodle autonome et non une accréditation publique ni l’ensemble de l’écosystème kOA.';
$string['boundarynotice_short'] = 'Campus expérimental UCKK — reconnaissance interne.';
$string['cachettl'] = 'Durée du cache';
$string['cachettl_desc'] = 'Durée, en secondes, pendant laquelle les données institutionnelles UCKK peuvent être conservées en cache.';
$string['components'] = 'Composants';
$string['dashboard'] = 'Tableau de bord';
$string['debugmode'] = 'Mode diagnostic';
$string['debugmode_desc'] = 'Active des informations de diagnostic supplémentaires pour les administrateurs UCKK.';
$string['default_aiwarning'] = 'L’IA est assistive et non souveraine. Une validation humaine reste requise.';
$string['default_boundarynotice'] = 'UCKK-Moodle est le campus Moodle de l’Univers-Cité King Klown. Les reconnaissances sont internes sauf accréditation formelle explicite.';
$string['default_internalrecognitionnotice'] = 'Reconnaissance interne UCKK — ne constitue pas un diplôme public accrédité.';
$string['enabled_desc'] = 'Active ou désactive cette fonction UCKK.';
$string['installed'] = 'Installé';
$string['invalidurl'] = 'URL invalide.';
$string['managecanon'] = 'Gérer le canon';
$string['managepathways'] = 'Gérer les parcours';
$string['manageprograms'] = 'Gérer les programmes';
$string['maximumchars'] = 'Nombre maximal de caractères';
$string['missing'] = 'Manquant';
$string['nothingtodisplay'] = 'Rien à afficher.';
$string['path'] = 'Chemin';
$string['progress'] = 'Progression';
$string['required'] = 'Requis';
$string['savechanges'] = 'Enregistrer les modifications';
$string['viewprogram'] = 'Voir le programme';
$string['uckkcourseadmin'] = 'Administration des cours UCKK';
$string['validation_humanreviewed'] = 'Validation humaine effectuée';
$string['status_available'] = 'Disponible';
$string['status_missingplugin'] = 'Plugin manquant';
$string['status_proposed'] = 'Proposé';
$string['status_published'] = 'Publié';
$string['status_superseded'] = 'Remplacé';
$string['status_underreview'] = 'En cours de révision';
$string['campus_archives_desc'] = 'Consulter les archives, preuves, Kristals et enregistrements de provenance validés.';
$string['campus_archives_title'] = 'Archives et mémoire';
$string['campus_assemblies_desc'] = 'Participer aux Assemblées, motions, votes, décisions et contestations.';
$string['campus_assemblies_title'] = 'Assemblées';
$string['campus_boundary_notice'] = 'UCKK-Moodle est autonome. Konnaxion, Smart Vote et l’IA sont optionnels et non souverains.';
$string['campus_canon_desc'] = 'Lire les principes, règles, limites et repères canoniques du campus UCKK.';
$string['campus_canon_title'] = 'Canon UCKK';
$string['campus_challenges_desc'] = 'Explorer les Défis King Klown, les preuves attendues et les validations associées.';
$string['campus_challenges_title'] = 'Défis King Klown';
$string['campus_dashboard_desc'] = 'Accéder à la synthèse personnelle du Joueur, aux parcours, preuves, alertes et actions utiles.';
$string['campus_dashboard_title'] = 'Tableau de bord Joueur';
$string['campus_format_desc'] = 'Parcourir les cours selon la structure pédagogique UCKK.';
$string['campus_format_title'] = 'Format de cours UCKK';
$string['campus_formula'] = 'Connaître → Choisir → Agir → Se souvenir';
$string['campus_integrity_desc'] = 'Suivre les garde-fous d’intégrité, les corrections et les révisions Inquisiteur.';
$string['campus_integrity_title'] = 'Intégrité et Inquisiteur';
$string['campus_intro'] = 'Bienvenue dans l’Univers-Cité King Klown, campus Moodle expérimental de formation, délibération, preuve et mémoire.';
$string['campus_pathways_desc'] = 'Explorer les parcours, exigences, cours, badges et compétences associés.';
$string['campus_pathways_title'] = 'Parcours UCKK';
$string['campus_programs_desc'] = 'Consulter les programmes internes et leurs structures pédagogiques.';
$string['campus_programs_title'] = 'Programmes UCKK';
$string['campus_recognition_notice'] = 'Les parcours, badges et reconnaissances UCKK sont internes sauf accréditation formelle explicite.';
$string['campus_reports_desc'] = 'Consulter les rapports institutionnels selon vos permissions.';
$string['campus_reports_title'] = 'Rapports institutionnels';
$string['campus_seed_desc'] = 'Installer ou vérifier les catégories, cours, rôles, parcours, badges et presets UCKK.';
$string['campus_seed_title'] = 'Installation et presets';
$string['campus_subtitle'] = 'Campus Moodle expérimental pour apprendre, décider, agir et archiver.';
$string['campus_tagline'] = 'Connaître, choisir, agir, se souvenir.';
$string['campus_title'] = 'Univers-Cité King Klown — Moodle Campus';
$string['campusdescription'] = 'Description du campus';
$string['campusdescription_desc'] = 'Texte de présentation affiché sur les pages institutionnelles UCKK.';
$string['campusshortname'] = 'Nom court du campus';
$string['campusshortname_desc'] = 'Nom court utilisé pour identifier le campus UCKK.';
$string['campustagline'] = 'Slogan du campus';
$string['campustagline_desc'] = 'Phrase courte affichée dans les interfaces du campus UCKK.';
$string['campustitle'] = 'Titre du campus';
$string['campustitle_desc'] = 'Titre principal affiché pour le campus UCKK.';
$string['default_campusdescription'] = 'Campus Moodle de l’Univers-Cité King Klown.';
$string['default_campustagline'] = 'Connaître, choisir, agir, se souvenir.';
$string['default_campustitle'] = 'Univers-Cité King Klown — Moodle Campus';
$string['canon:intro'] = 'Principes, limites et repères institutionnels UCKK.';
$string['canon:title'] = 'Canon UCKK';
$string['canon_archiveonpublish'] = 'Archiver à la publication';
$string['canon_archiveonpublish_label'] = 'Créer une trace d’archive lorsque cet élément canonique est publié.';
$string['canon_changereason'] = 'Raison du changement';
$string['canon_content'] = 'Contenu canonique';
$string['canon_contestable'] = 'Contestable';
$string['canon_contestable_label'] = 'Cet élément peut être contesté ou révisé selon la procédure UCKK.';
$string['canon_createitem'] = 'Créer un élément canonique';
$string['canon_evidencenote'] = 'Note de preuve';
$string['canon_form_content'] = 'Contenu';
$string['canon_form_governance'] = 'Gouvernance';
$string['canon_form_identity'] = 'Identité';
$string['canon_form_metadata'] = 'Métadonnées';
$string['canon_form_provenance'] = 'Provenance';
$string['canon_form_versioning'] = 'Versionnement';
$string['canon_interpretationnotes'] = 'Notes d’interprétation';
$string['canon_itemkey'] = 'Clé de l’élément canonique';
$string['canon_itemkey_invalid'] = 'La clé canonique est invalide.';
$string['canon_itemtype'] = 'Type d’élément canonique';
$string['canon_itemtype_invalid'] = 'Le type d’élément canonique est invalide.';
$string['canon_limits'] = 'Limites';
$string['canon_metadatajson'] = 'Métadonnées JSON';
$string['canon_metadatajson_invalid'] = 'Les métadonnées JSON du canon sont invalides.';
$string['canon_minorchange'] = 'Changement mineur';
$string['canon_minorchange_label'] = 'Marquer cette modification comme mineure.';
$string['canon_parentid'] = 'Élément parent';
$string['canon_public_integrity_conflict'] = 'Un élément public ne peut pas contourner les règles d’intégrité.';
$string['canon_published_requires_archive'] = 'Un élément canonique publié doit disposer d’une trace d’archive.';
$string['canon_published_requires_source'] = 'Un élément canonique publié doit déclarer une source.';
$string['canon_requiresassembly'] = 'Assemblée requise';
$string['canon_requiresassembly_label'] = 'Une décision d’Assemblée est requise pour modifier ou publier cet élément.';
$string['canon_requiresintegrityreview'] = 'Révision d’intégrité requise';
$string['canon_requiresintegrityreview_label'] = 'Une révision d’intégrité est requise avant publication.';
$string['canon_saveitem'] = 'Enregistrer l’élément canonique';
$string['canon_sourcecomponent'] = 'Composant source';
$string['canon_sourceid'] = 'Identifiant source';
$string['canon_sourcetext'] = 'Texte de source';
$string['canon_sourceurl'] = 'URL de source';
$string['canon_status_invalid'] = 'Le statut canonique est invalide.';
$string['canon_summary'] = 'Résumé canonique';
$string['canon_type_archive_policy'] = 'Politique d’archive';
$string['canon_type_course_reference'] = 'Référence de cours';
$string['canon_type_definition'] = 'Définition';
$string['canon_type_governance'] = 'Gouvernance';
$string['canon_type_integrity_policy'] = 'Politique d’intégrité';
$string['canon_type_kristal'] = 'Kristal';
$string['canon_type_method'] = 'Méthode';
$string['canon_type_principle'] = 'Principe';
$string['canon_type_program'] = 'Programme';
$string['canon_type_rule'] = 'Règle';
$string['canon_type_symbolic_boundary'] = 'Frontière symbolique';
$string['canon_visibility_invalid'] = 'La visibilité canonique est invalide.';
$string['canonrefreshed'] = 'Canon actualisé.';
$string['canonrefreshfailed'] = 'Impossible d’actualiser le canon.';
$string['canonrefreshing'] = 'Actualisation du canon…';
$string['canonurl'] = 'URL du canon';
$string['canonurl_desc'] = 'URL de la page ou ressource canonique principale.';
$string['defaultarchivevisibility'] = 'Visibilité par défaut des archives';
$string['defaultarchivevisibility_desc'] = 'Visibilité appliquée par défaut aux nouveaux objets d’archive UCKK.';
$string['defaultintegrityvisibility'] = 'Visibilité par défaut de l’intégrité';
$string['defaultintegrityvisibility_desc'] = 'Visibilité appliquée par défaut aux informations d’intégrité.';
$string['defaultpagesize'] = 'Taille de page par défaut';
$string['defaultpagesize_desc'] = 'Nombre d’éléments affichés par page dans les listes UCKK.';
$string['defaultpathwayvisibility'] = 'Visibilité par défaut des parcours';
$string['defaultpathwayvisibility_desc'] = 'Visibilité appliquée par défaut aux parcours UCKK.';
$string['defaultvalidationstate'] = 'État de validation par défaut';
$string['defaultvalidationstate_desc'] = 'État initial appliqué aux objets UCKK nécessitant une validation.';
$string['enableaigovernance'] = 'Activer la gouvernance IA';
$string['enableaigovernance_desc'] = 'Active les rappels, limites et journaux liés à l’utilisation assistive de l’IA.';
$string['enabledashboardintegration'] = 'Activer l’intégration tableau de bord';
$string['enabledashboardintegration_desc'] = 'Permet aux composants UCKK d’alimenter le tableau de bord.';
$string['enableintegrityguardrails'] = 'Activer les garde-fous d’intégrité';
$string['enableintegrityguardrails_desc'] = 'Active les avertissements, contrôles et liens vers les workflows Inquisiteur.';
$string['enablekingklownlayer'] = 'Activer la couche King Klown';
$string['enablekingklownlayer_desc'] = 'Active les éléments narratifs et symboliques King Klown dans les interfaces autorisées.';
$string['enablekristals'] = 'Activer les Kristals';
$string['enablekristals_desc'] = 'Permet l’utilisation des Kristals comme objets de mémoire et de synthèse.';
$string['enablememorylayer'] = 'Activer la couche mémoire';
$string['enablememorylayer_desc'] = 'Active les liens de mémoire, d’archive et de provenance entre objets UCKK.';
$string['enablenavigationregistry'] = 'Activer le registre de navigation';
$string['enablenavigationregistry_desc'] = 'Expose les destinations UCKK communes aux plugins autorisés.';
$string['enableplayerprofiles'] = 'Activer les profils Joueurs';
$string['enableplayerprofiles_desc'] = 'Permet la création et l’affichage des profils Joueurs UCKK.';
$string['enableprogramregistry'] = 'Activer le registre des programmes';
$string['enableprogramregistry_desc'] = 'Active le registre institutionnel des programmes et parcours UCKK.';
$string['enablesymbolicroles'] = 'Activer les rôles symboliques';
$string['enablesymbolicroles_desc'] = 'Permet l’affichage des titres et rôles symboliques sans leur donner d’autorité Moodle.';
$string['enforceinstitutionalclarity'] = 'Imposer la clarté institutionnelle';
$string['enforceinstitutionalclarity_desc'] = 'Affiche les avis empêchant la confusion entre reconnaissance interne et accréditation publique.';
$string['internalrecognitionnotice'] = 'Avis de reconnaissance interne';
$string['internalrecognitionnotice_desc'] = 'Texte affiché pour rappeler que les reconnaissances UCKK sont internes.';
$string['internalrecognitionnotice_short'] = 'Reconnaissance interne UCKK.';
$string['koadigitalecosystemurl'] = 'URL de l’écosystème digital kOA';
$string['koadigitalecosystemurl_desc'] = 'Lien optionnel vers une ressource externe liée à l’écosystème digital kOA.';
$string['logaiprovenance'] = 'Journaliser la provenance IA';
$string['logaiprovenance_desc'] = 'Enregistre les marqueurs de provenance lorsque l’IA assiste un contenu ou une action.';
$string['preventsilentdeletion'] = 'Empêcher les suppressions silencieuses';
$string['preventsilentdeletion_desc'] = 'Exige une trace ou une justification lorsqu’un objet institutionnel UCKK est supprimé.';
$string['publicarchiveurl'] = 'URL publique des archives';
$string['publicarchiveurl_desc'] = 'URL utilisée pour les archives publiques lorsque cette visibilité est activée.';
$string['requireaihumandecision'] = 'Exiger une décision humaine après IA';
$string['requireaihumandecision_desc'] = 'Empêche les sorties IA de devenir des décisions finales sans validation humaine.';
$string['requirearchiveprovenance'] = 'Exiger la provenance d’archive';
$string['requirearchiveprovenance_desc'] = 'Exige une provenance documentée pour les objets archivés.';
$string['requirecontestability'] = 'Exiger la contestabilité';
$string['requirecontestability_desc'] = 'Exige un chemin de contestation pour les décisions ou objets institutionnels concernés.';
$string['requirehumanvalidation'] = 'Exiger une validation humaine';
$string['requirehumanvalidation_desc'] = 'Impose une validation humaine avant publication ou reconnaissance institutionnelle.';
$string['requirelevelnaming'] = 'Exiger la nomenclature des niveaux';
$string['requirelevelnaming_desc'] = 'Vérifie que les niveaux, parcours et programmes utilisent la nomenclature UCKK attendue.';
$string['requireprovenance'] = 'Exiger la provenance';
$string['requireprovenance_desc'] = 'Exige une source ou trace de provenance pour les objets UCKK concernés.';
$string['separatesymbolicandtechnicalroles'] = 'Séparer rôles symboliques et rôles techniques';
$string['separatesymbolicandtechnicalroles_desc'] = 'Rappelle que les titres UCKK ne remplacent jamais les rôles et capacités Moodle.';
$string['settings_advanced'] = 'Réglages avancés';
$string['settings_advanced_desc'] = 'Réglages techniques avancés du noyau UCKK.';
$string['settings_archives_desc'] = 'Réglages liés aux archives, preuves, Kristals et exportations.';
$string['settings_domain'] = 'Domaine UCKK';
$string['settings_domain_desc'] = 'Réglages liés aux frontières de domaine et à la clarté institutionnelle.';
$string['settings_external'] = 'Systèmes externes';
$string['settings_external_desc'] = 'Réglages liés aux liens externes et intégrations optionnelles.';
$string['settings_integrations'] = 'Intégrations';
$string['settings_integrations_desc'] = 'Réglages liés à la coordination entre plugins UCKK et systèmes optionnels.';
$string['settings_integrity_desc'] = 'Réglages liés à l’intégrité, aux avertissements et aux révisions.';
$string['settings_navigation'] = 'Navigation';
$string['settings_navigation_desc'] = 'Réglages liés aux liens et destinations partagées du campus.';
$string['settings_pathways_desc'] = 'Réglages liés aux programmes, parcours et profils Joueurs.';
$string['settings_symbolicroles'] = 'Rôles symboliques';
$string['settings_symbolicroles_desc'] = 'Réglages d’affichage des rôles symboliques UCKK.';
$string['showaiwarning'] = 'Afficher l’avertissement IA';
$string['showaiwarning_desc'] = 'Affiche un rappel indiquant que l’IA est assistive et non souveraine.';
$string['showbatisseur'] = 'Afficher le rôle Bâtisseur';
$string['showbatisseur_desc'] = 'Permet l’affichage du titre symbolique Bâtisseur.';
$string['showboundarynotice'] = 'Afficher l’avis de frontière';
$string['showboundarynotice_desc'] = 'Affiche l’avis de frontière institutionnelle UCKK.';
$string['showcartographe'] = 'Afficher le rôle Cartographe';
$string['showcartographe_desc'] = 'Permet l’affichage du titre symbolique Cartographe.';
$string['showdashboardarchivecard'] = 'Afficher la carte Archives';
$string['showdashboardarchivecard_desc'] = 'Affiche la carte Archives dans le tableau de bord UCKK.';
$string['showdashboardassemblycard'] = 'Afficher la carte Assemblées';
$string['showdashboardassemblycard_desc'] = 'Affiche la carte Assemblées dans le tableau de bord UCKK.';
$string['showdashboardchallengecard'] = 'Afficher la carte Défis';
$string['showdashboardchallengecard_desc'] = 'Affiche la carte Défis dans le tableau de bord UCKK.';
$string['showdashboardintegritycard'] = 'Afficher la carte Intégrité';
$string['showdashboardintegritycard_desc'] = 'Affiche la carte Intégrité dans le tableau de bord UCKK.';
$string['showdashboardpathwaycard'] = 'Afficher la carte Parcours';
$string['showdashboardpathwaycard_desc'] = 'Affiche la carte Parcours dans le tableau de bord UCKK.';
$string['showdiagnostics'] = 'Afficher les diagnostics';
$string['showdiagnostics_desc'] = 'Affiche les informations de diagnostic aux utilisateurs autorisés.';
$string['showintegritynotices'] = 'Afficher les avis d’intégrité';
$string['showintegritynotices_desc'] = 'Affiche les avis liés à l’intégrité et aux garde-fous UCKK.';
$string['showinternalrecognitionnotice'] = 'Afficher l’avis de reconnaissance interne';
$string['showinternalrecognitionnotice_desc'] = 'Affiche un avis indiquant que les reconnaissances UCKK sont internes.';
$string['showjoueurlucide'] = 'Afficher le titre Joueur lucide';
$string['showjoueurlucide_desc'] = 'Permet l’affichage du titre symbolique Joueur lucide.';
$string['symbolicrole_permission_warning'] = 'Les rôles symboliques ne donnent aucune permission Moodle par eux-mêmes.';
$string['integrateai'] = 'Intégrer l’IA gouvernable';
$string['integrateai_desc'] = 'Active les liens vers le fournisseur IA UCKK lorsque disponible.';
$string['integratearchive'] = 'Intégrer les Archives';
$string['integratearchive_desc'] = 'Active les liens et indicateurs vers le module Archives.';
$string['integrateassembly'] = 'Intégrer les Assemblées';
$string['integrateassembly_desc'] = 'Active les liens et indicateurs vers le module Assemblées.';
$string['integratechallenge'] = 'Intégrer les Défis';
$string['integratechallenge_desc'] = 'Active les liens et indicateurs vers le module Défis.';
$string['integrateintegrity'] = 'Intégrer l’Intégrité';
$string['integrateintegrity_desc'] = 'Active les liens et indicateurs vers l’outil Inquisiteur.';
$string['integratereports'] = 'Intégrer les rapports';
$string['integratereports_desc'] = 'Active les liens vers les rapports institutionnels UCKK.';
$string['integration_ai'] = 'IA gouvernable';
$string['integration_archive'] = 'Archives';
$string['integration_assembly'] = 'Assemblées';
$string['integration_challenge'] = 'Défis';
$string['integration_courseformat'] = 'Format de cours UCKK';
$string['integration_dashboardblock'] = 'Bloc tableau de bord UCKK';
$string['integration_integrity'] = 'Intégrité';
$string['integration_reports'] = 'Rapports';
$string['integration_theme'] = 'Thème UCKK';
$string['filearea_canon'] = 'Fichiers du canon';
$string['filearea_map'] = 'Fichiers de cartographie';
$string['filearea_profile'] = 'Fichiers de profil';
$string['filearea_reflection'] = 'Fichiers de réflexion';
$string['privacy:path:canon'] = 'Canon';
$string['privacy:path:maps'] = 'Cartographies';
$string['privacy:path:programs'] = 'Programmes';
$string['privacy:path:symbolicroles'] = 'Rôles symboliques';
$string['profileeditcancelled'] = 'Modification du profil annulée.';
$string['profileediting'] = 'Modification du profil';
$string['profileerror'] = 'Erreur de profil';
$string['profilesaved'] = 'Profil enregistré.';
$string['profilesaving'] = 'Enregistrement du profil…';
$string['quicklink_export'] = 'Exporter mes données';
$string['quicklink_mycourses'] = 'Mes cours';
$string['quicklink_preferences'] = 'Préférences';
$string['refreshfailed'] = 'L’actualisation a échoué.';
$string['dashboard_ai_notice'] = 'L’IA peut aider, mais les décisions et validations restent humaines.';
$string['dashboard_card_archives_desc'] = 'Consulter les preuves, archives et traces de provenance.';
$string['dashboard_card_archives_title'] = 'Archives';
$string['dashboard_card_assemblies_desc'] = 'Suivre les Assemblées, motions, votes et décisions.';
$string['dashboard_card_assemblies_title'] = 'Assemblées';
$string['dashboard_card_badges_desc'] = 'Voir les badges et reconnaissances internes obtenus.';
$string['dashboard_card_badges_title'] = 'Badges';
$string['dashboard_card_challenges_desc'] = 'Suivre les Défis King Klown et leurs preuves.';
$string['dashboard_card_challenges_title'] = 'Défis';
$string['dashboard_card_courses_desc'] = 'Accéder aux cours et activités du campus.';
$string['dashboard_card_courses_title'] = 'Cours';
$string['dashboard_card_pathway_desc'] = 'Voir la progression dans les parcours UCKK.';
$string['dashboard_card_pathway_title'] = 'Parcours';
$string['dashboard_empty_text'] = 'Aucune donnée de tableau de bord n’est disponible pour le moment.';
$string['dashboard_empty_title'] = 'Tableau de bord vide';
$string['dashboard_integrity_notice'] = 'Certaines informations peuvent être restreintes par les règles d’intégrité.';
$string['dashboard_intro'] = 'Synthèse de votre progression, de vos preuves, parcours et actions UCKK.';
$string['dashboard_subtitle'] = 'Votre état courant dans le campus UCKK.';
$string['dashboard_title'] = 'Tableau de bord UCKK';
$string['program:category'] = 'Catégorie Moodle';
$string['program:category_none'] = 'Aucune catégorie liée';
$string['program:cohortidnumber'] = 'Identifiant de cohorte';
$string['program:color'] = 'Couleur';
$string['program:description'] = 'Description du programme';
$string['program:display'] = 'Affichage';
$string['program:error_category'] = 'La catégorie du programme est invalide.';
$string['program:error_color'] = 'La couleur du programme est invalide.';
$string['program:error_csvfield'] = 'Le champ CSV du programme est invalide.';
$string['program:error_idnumber'] = 'L’identifiant du programme est invalide.';
$string['program:error_metadatajson'] = 'Les métadonnées JSON du programme sont invalides.';
$string['program:error_programtype'] = 'Le type de programme est invalide.';
$string['program:error_shortname'] = 'Le nom court du programme est invalide.';
$string['program:error_sortorder'] = 'L’ordre d’affichage du programme est invalide.';
$string['program:error_status'] = 'Le statut du programme est invalide.';
$string['program:error_visibility'] = 'La visibilité du programme est invalide.';
$string['program:fullname'] = 'Nom complet du programme';
$string['program:general'] = 'Informations générales';
$string['program:governance'] = 'Gouvernance';
$string['program:icon'] = 'Icône';
$string['program:idnumber'] = 'Identifiant du programme';
$string['program:internalrecognition'] = 'Reconnaissance interne';
$string['program:internalrecognition_desc'] = 'Texte indiquant la nature interne de la reconnaissance associée au programme.';
$string['program:limitsnotice'] = 'Avis de limites';
$string['program:limitsnotice_default'] = 'Ce programme relève d’une reconnaissance interne UCKK.';
$string['program:metadata'] = 'Métadonnées';
$string['program:metadatajson'] = 'Métadonnées JSON';
$string['program:outcomes'] = 'Résultats attendus';
$string['program:recognition'] = 'Reconnaissance';
$string['program:requiredbadges'] = 'Badges requis';
$string['program:requiredcompetencies'] = 'Compétences requises';
$string['program:requiredcourses'] = 'Cours requis';
$string['program:requiresarchive'] = 'Archive requise';
$string['program:requiresarchive_desc'] = 'Exige un objet d’archive pour compléter ou reconnaître ce programme.';
$string['program:requiresintegrityreview'] = 'Révision d’intégrité requise';
$string['program:requiresintegrityreview_desc'] = 'Exige une révision d’intégrité avant reconnaissance ou publication.';
$string['program:requiresportfolio'] = 'Portfolio requis';
$string['program:requiresportfolio_desc'] = 'Exige un portfolio associé au programme.';
$string['program:shortname'] = 'Nom court du programme';
$string['program:sortorder'] = 'Ordre d’affichage';
$string['program:status'] = 'Statut du programme';
$string['program:structure'] = 'Structure';
$string['program:type'] = 'Type de programme';
$string['program:type_baccalaureat'] = 'Voie UCKK — Niveau visé : Puissance opératoire';
$string['program:type_certificat'] = 'Reconnaissance interne — Initiation';
$string['program:type_laboratoire'] = 'Laboratoire';
$string['program:type_mineure'] = 'Voie UCKK — Niveau visé : Initiation';
$string['program:type_seminaire'] = 'Séminaire';
$string['program:type_tronccommun'] = 'Tronc commun';
$string['program:visibility'] = 'Visibilité du programme';
$string['local/uckk:viewcampus'] = 'Voir le campus UCKK';
$string['local/uckk:manageprograms'] = 'Gérer les programmes UCKK';
$string['local/uckk:managepathways'] = 'Gérer les parcours UCKK';
$string['local/uckk:manageprofiles'] = 'Gérer les profils Joueurs UCKK';
$string['local/uckk:managecanon'] = 'Gérer le canon UCKK';
$string['local/uckk:viewreports'] = 'Voir les rapports UCKK';
$string['local/uckk:exportdata'] = 'Exporter les données UCKK';
$string['local/uckk:viewrestricted'] = 'Voir les informations restreintes UCKK';
$string['local/uckk:manageintegrations'] = 'Gérer les intégrations UCKK';

// Public Médiathèque.
$string['nav_mediatheque'] = 'Médiathèque';

$string['mediatheque_title'] = 'Médiathèque UCKK';
$string['mediatheque_eyebrow'] = 'Archives publiques';
$string['mediatheque_summary'] = 'Explorer les médias, collections, œuvres externes et passages documentés de l’archive UCKK.';
$string['mediatheque_boundarynotice'] = 'La Médiathèque publique affiche seulement les contenus que les politiques de l’archive autorisent à exposer publiquement. Certains médias, passages ou détails peuvent être masqués selon les droits, avis de contenu et protocoles culturels.';

$string['mediatheque_explorer_title'] = 'Explorateur Médiathèque';
$string['mediatheque_explorer_summary'] = 'Rechercher, filtrer et parcourir les médias publics de l’archive UCKK.';
$string['mediatheque_explorer_loading'] = 'Chargement de la Médiathèque…';
$string['mediatheque_explorer_error'] = 'La Médiathèque n’a pas pu être chargée.';
$string['mediatheque_explorer_empty'] = 'Aucun média public ne correspond aux filtres.';
$string['mediatheque_explorer_results'] = 'Résultats de la Médiathèque';

$string['mediatheque_search_label'] = 'Recherche';
$string['mediatheque_search_placeholder'] = 'Rechercher dans la médiathèque';
$string['mediatheque_search_button'] = 'Rechercher';
$string['mediatheque_filters_title'] = 'Filtres';
$string['mediatheque_apply_filters'] = 'Appliquer les filtres';
$string['mediatheque_clear_filters'] = 'Réinitialiser les filtres';

$string['mediatheque_filter_type'] = 'Type';
$string['mediatheque_filter_mediatype'] = 'Format';
$string['mediatheque_filter_collection'] = 'Collection';
$string['mediatheque_filter_tag'] = 'Mot-clé';
$string['mediatheque_filter_source'] = 'Source';
$string['mediatheque_filter_advisory'] = 'Avis de contenu';
$string['mediatheque_filter_cultural'] = 'Protocole culturel';
$string['mediatheque_filter_audience'] = 'Public';
$string['mediatheque_filter_language'] = 'Langue';
$string['mediatheque_filter_validation'] = 'Validation';
$string['mediatheque_filter_sort'] = 'Tri';

$string['mediatheque_sort_relevance'] = 'Pertinence';
$string['mediatheque_sort_newest'] = 'Plus récents';
$string['mediatheque_sort_title'] = 'Titre';
$string['mediatheque_sort_type'] = 'Type';
$string['mediatheque_sort_collection'] = 'Collection';
$string['mediatheque_sort_validated'] = 'Validation';

$string['mediatheque_type_all'] = 'Tous';
$string['mediatheque_type_media'] = 'Médias';
$string['mediatheque_type_collection'] = 'Collections';
$string['mediatheque_type_external_work'] = 'Œuvres externes';
$string['mediatheque_type_archive_item'] = 'Objets d’archive';
$string['mediatheque_type_content_marker'] = 'Passages ciblés';

$string['mediatheque_sitewide'] = 'Recherche publique globale';
$string['mediatheque_archive_scoped'] = 'Recherche limitée à une archive';
$string['mediatheque_module_scoped'] = 'Recherche limitée à une activité archive';

$string['mediatheque_notice_public_only'] = 'Seuls les contenus publics autorisés sont affichés.';
$string['mediatheque_notice_policy_filtered'] = 'Les résultats sont filtrés par les politiques de mod_uckkarchive.';
$string['mediatheque_notice_no_direct_files'] = 'Les fichiers originaux ne sont pas exposés directement depuis cette page.';

$string['mediatheque_action_open'] = 'Ouvrir';
$string['mediatheque_action_view_details'] = 'Voir les détails';
$string['mediatheque_action_view_file'] = 'Voir le fichier autorisé';
$string['mediatheque_action_download_disabled'] = 'Téléchargement non disponible publiquement';

$string['mediatheque_metadata_surface'] = 'Surface publique';
$string['mediatheque_metadata_policy_owner'] = 'Données et politiques';
$string['mediatheque_metadata_surface_value'] = 'local_uckk';
$string['mediatheque_metadata_policy_owner_value'] = 'mod_uckkarchive';


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