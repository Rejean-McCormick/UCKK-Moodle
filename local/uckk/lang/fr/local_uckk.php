<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the technical foundation of the
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
$string['uckkcampus'] = 'Établissement virtuel UCKK';
$string['uckkmoodle'] = 'Socle technique UCKK';
$string['uckkcore'] = 'Noyau institutionnel UCKK';
$string['uckkcore_desc'] = 'Registre institutionnel central de l’Univers-Cité King Klown : voies, cours, défis, assemblées, preuves, portfolios, archives, provenance, visibilité et navigation partagée.';
$string['uckknotaccredited'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['internalrecognition'] = 'Repère interne';
$string['experimentalstructure'] = 'Structure pédagogique émergente';

// Navigation.
$string['nav_campus'] = 'Espace UCKK';
$string['nav_dashboard'] = 'Tableau de bord UCKK';
$string['nav_programs'] = 'Voies';
$string['nav_pathways'] = 'Parcours';
$string['nav_profiles'] = 'Profils Joueurs';
$string['nav_canon'] = 'Canon UCKK';
$string['nav_provenance'] = 'Provenance';
$string['nav_reflections'] = 'Réflexions';
$string['nav_reports'] = 'Rapports';
$string['nav_settings'] = 'Réglages UCKK';
$string['nav_administration'] = 'Administration UCKK';
$string['nav_integrity'] = 'Intégrité';
$string['nav_archives'] = 'Registraire';
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
$string['visibility_program'] = 'Voie';
$string['visibility_institution'] = 'Institution';
$string['visibility_public'] = 'Public';
$string['visibility_restricted'] = 'Restreint';
$string['visibility_restricted_integrity'] = 'Restreint — intégrité';

// Voies.
$string['program'] = 'Voie';
$string['programs'] = 'Voies';
$string['program_create'] = 'Créer une voie';
$string['program_edit'] = 'Modifier la voie';
$string['program_delete'] = 'Supprimer la voie';
$string['program_archive'] = 'Archiver la voie';
$string['program_shortname'] = 'Nom court de la voie';
$string['program_fullname'] = 'Nom complet de la voie';
$string['program_type'] = 'Type de voie';
$string['program_category'] = 'Espace de cours associé';
$string['program_status'] = 'Statut de la voie';
$string['program_notfound'] = 'Voie UCKK introuvable.';
$string['programshortnameexists'] = 'Ce nom court de voie UCKK existe déjà : {$a}';

$string['programtype_tronccommun'] = 'Tronc commun';
$string['programtype_baccalaureat'] = 'Voie UCKK — Niveau visé : Puissance opératoire';
$string['programtype_mineure'] = 'Voie UCKK — Niveau visé : Initiation';
$string['programtype_lab'] = 'Laboratoire';
$string['programtype_seminar'] = 'Séminaire';
$string['programtype_transversal'] = 'Voie transversale';

$string['program_tronccommun'] = 'Tronc commun obligatoire';
$string['program_grand_jeu_social'] = 'Voie du Grand Jeu social';
$string['program_koa_digital'] = 'Voie de l’Architecture de l’écosystème digital kOA';
$string['program_sociotechnique'] = 'Voie de l’Architecture sociotechnique';
$string['program_sciences_politiques'] = 'Voie des Sciences politiques';
$string['program_economie'] = 'Voie de l’Économie';
$string['program_ecologie'] = 'Voie de l’Écologie';
$string['program_metaphysique'] = 'Voie de la Métaphysique';
$string['program_ia_gouvernable'] = 'Voie de la Production augmentée par l’IA';
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
$string['pathway_program'] = 'Voie liée';
$string['pathway_requirements'] = 'Exigences du parcours';
$string['pathway_requiredcourses'] = 'Cours requis';
$string['pathway_requiredbadges'] = 'Parchemins requis';
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
$string['portfolioarchive'] = 'Registre de portfolio';
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
$string['provenance_archive'] = 'Registre';
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

// Competencies and parchments.
$string['competency'] = 'Compétence';
$string['competencies'] = 'Compétences';
$string['badge'] = 'Parchemin';
$string['badges'] = 'Parchemins';
$string['requiredcompetencies'] = 'Compétences requises';
$string['requiredbadges'] = 'Parchemins requis';

$string['comp_read_game'] = 'Lire le Grand Jeu social';
$string['comp_map_system'] = 'Cartographier un système';
$string['comp_distinguish_claims'] = 'Distinguer fait, hypothèse, interprétation, récit et décision';
$string['comp_use_ai_non_sovereign'] = 'Utiliser l’IA comme outil non souverain';
$string['comp_produce_proof'] = 'Produire une preuve vérifiable';
$string['comp_participate_assembly'] = 'Participer à une assemblée structurée';
$string['comp_responsible_mobilisation'] = 'Concevoir une mobilisation responsable';
$string['comp_document_decision'] = 'Documenter une décision';
$string['comp_archive_learning'] = 'Archiver une trace de formation';
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
$string['archive'] = 'Registre';
$string['archives'] = 'Registraire';
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
$string['settings_campus'] = 'Espace UCKK';
$string['settings_campus_desc'] = 'Réglages liés à l’identité et au fonctionnement de l’UCKK.';
$string['settings_programs'] = 'Voies et parcours';
$string['settings_programs_desc'] = 'Réglages liés aux voies, parcours et profils Joueurs.';
$string['settings_provenance'] = 'Provenance';
$string['settings_provenance_desc'] = 'Réglages liés à la traçabilité et à la mémoire institutionnelle.';
$string['settings_ai'] = 'Production IA';
$string['settings_ai_desc'] = 'Réglages liés à l’utilisation de l’IA comme outil non souverain.';

$string['setting_enablecampus'] = 'Activer l’environnement UCKK';
$string['setting_enablecampus_desc'] = 'Active les fonctions institutionnelles centrales de l’UCKK.';
$string['setting_enableprovenance'] = 'Activer la provenance';
$string['setting_enableprovenance_desc'] = 'Enregistre la provenance des objets UCKK lorsque les tables nécessaires sont disponibles.';
$string['setting_enablereflections'] = 'Activer les réflexions';
$string['setting_enablereflections_desc'] = 'Permet aux Joueurs de soumettre des réflexions liées aux cours, parcours, portfolios et journaux IA.';
$string['setting_enableaiwarnings'] = 'Afficher les avertissements IA';
$string['setting_enableaiwarnings_desc'] = 'Affiche des rappels indiquant que l’IA est assistive et non souveraine.';
$string['setting_defaultvisibility'] = 'Visibilité par défaut';
$string['setting_defaultvisibility_desc'] = 'Visibilité par défaut des profils, réflexions et objets UCKK créés par le noyau.';

// Capabilities.
$string['uckk:viewcampus'] = 'Voir l’espace UCKK';
$string['uckk:viewcampus_desc'] = 'Permet de voir les éléments de base de l’UCKK.';

$string['uckk:manageprograms'] = 'Gérer les voies UCKK';
$string['uckk:manageprograms_desc'] = 'Permet de créer, modifier, archiver ou supprimer les voies UCKK.';

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
$string['service_get_programs'] = 'Obtenir les voies UCKK';
$string['service_get_programs_desc'] = 'Service externe permettant de récupérer la liste des voies UCKK.';
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
$string['privacy:metadata'] = 'Le noyau UCKK stocke des données institutionnelles liées aux voies, parcours, profils Joueurs, rôles symboliques, réflexions, provenance et visibilité.';
$string['privacy:metadata:local_uckk_program'] = 'Informations sur les voies UCKK.';
$string['privacy:metadata:local_uckk_pathway'] = 'Informations sur les parcours UCKK.';
$string['privacy:metadata:local_uckk_player'] = 'Informations sur les profils Joueurs UCKK.';
$string['privacy:metadata:local_uckk_role'] = 'Informations sur les rôles symboliques UCKK.';
$string['privacy:metadata:local_uckk_canon'] = 'Éléments du canon UCKK.';
$string['privacy:metadata:local_uckk_prov'] = 'Enregistrements de provenance UCKK.';
$string['privacy:metadata:local_uckk_reflect'] = 'Réflexions soumises par les utilisateurs dans l’UCKK.';
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
$string['error_invalidprogramid'] = 'Identifiant de voie UCKK invalide.';
$string['error_invalidpathwayid'] = 'Identifiant de parcours UCKK invalide.';
$string['error_invaliduserid'] = 'Identifiant utilisateur invalide.';
$string['error_invalidstatus'] = 'Statut UCKK invalide.';
$string['error_invalidvisibility'] = 'Visibilité UCKK invalide.';
$string['error_invalidreflectiontype'] = 'Type de réflexion UCKK invalide.';
$string['error_invalidmetadata'] = 'Objet JSON de métadonnées invalide.';
$string['error_missingprogramid'] = 'Identifiant de voie UCKK manquant.';
$string['error_missingpathwayshortname'] = 'Nom court du parcours UCKK manquant.';
$string['error_missingpathwayfullname'] = 'Nom complet du parcours UCKK manquant.';
$string['error_missingreflectionbody'] = 'Le texte de la réflexion est obligatoire.';
$string['error_permissiondenied'] = 'Permission UCKK refusée.';
$string['error_contextrequired'] = 'Contexte Moodle requis.';
$string['error_tablenotready'] = 'La table UCKK requise n’est pas encore disponible.';
$string['error_cannotdeleteactive'] = 'Impossible de supprimer un élément UCKK actif sans l’archiver ou le désactiver.';
$string['error_cannotassigninactivepathway'] = 'Impossible d’assigner un parcours inactif.';
$string['error_pathwaynotfound'] = 'Parcours UCKK introuvable.';
$string['error_programnotfound'] = 'Voie UCKK introuvable.';
$string['error_profilenotfound'] = 'Profil Joueur introuvable.';

// Warnings.
$string['warning_internalrecognition'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
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
$string['empty_programs'] = 'Aucune voie UCKK disponible.';
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
$string['allowpublicarchives'] = 'Autoriser les registres publics';
$string['allowpublicarchives_desc'] = 'Permet la publication de registres avec visibilité publique lorsque les permissions, la confidentialité et la validation humaine le permettent.';
$string['allowpublicevidence'] = 'Autoriser les preuves publiques';
$string['allowpublicevidence_desc'] = 'Permet la publication de preuves avec visibilité publique après validation et contrôle de confidentialité.';
$string['allowpublicpathways'] = 'Autoriser les parcours publics';
$string['allowpublicpathways_desc'] = 'Permet de rendre certains parcours visibles publiquement lorsque la politique institutionnelle l’autorise.';
$string['autocreateplayerprofile'] = 'Créer automatiquement le profil Joueur';
$string['autocreateplayerprofile_desc'] = 'Crée un profil Joueur UCKK lorsqu’un utilisateur accède à l’UCKK et qu’aucun profil n’existe encore.';
$string['back'] = 'Retour';
$string['boundarynotice'] = 'Note institutionnelle';
$string['boundarynotice_desc'] = 'Affiche une note courte sur le statut institutionnel de l’UCKK sans centrer la page publique sur les limites d’accréditation.';
$string['boundarynotice_short'] = 'Bibliothèque publique UCKK — cadre d’apprentissage ouvert.';
$string['cachettl'] = 'Durée du cache';
$string['cachettl_desc'] = 'Durée, en secondes, pendant laquelle les données institutionnelles UCKK peuvent être conservées en cache.';
$string['components'] = 'Composants';
$string['dashboard'] = 'Tableau de bord';
$string['debugmode'] = 'Mode diagnostic';
$string['debugmode_desc'] = 'Active des informations de diagnostic supplémentaires pour les administrateurs UCKK.';
$string['default_aiwarning'] = 'L’IA est assistive et non souveraine. Une validation humaine reste requise.';
$string['default_boundarynotice'] = 'L’UCKK est une bibliothèque publique vivante et un établissement virtuel de puissance opératoire consacré à la diffusion du savoir.';
$string['default_internalrecognitionnotice'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['enabled_desc'] = 'Active ou désactive cette fonction UCKK.';
$string['installed'] = 'Installé';
$string['invalidurl'] = 'URL invalide.';
$string['managecanon'] = 'Gérer le canon';
$string['managepathways'] = 'Gérer les parcours';
$string['manageprograms'] = 'Gérer les voies';
$string['maximumchars'] = 'Nombre maximal de caractères';
$string['missing'] = 'Manquant';
$string['nothingtodisplay'] = 'Rien à afficher.';
$string['path'] = 'Chemin';
$string['progress'] = 'Progression';
$string['required'] = 'Requis';
$string['savechanges'] = 'Enregistrer les modifications';
$string['viewprogram'] = 'Voir la voie';
$string['uckkcourseadmin'] = 'Administration des cours UCKK';
$string['validation_humanreviewed'] = 'Validation humaine effectuée';
$string['status_available'] = 'Disponible';
$string['status_missingplugin'] = 'Plugin manquant';
$string['status_proposed'] = 'Proposé';
$string['status_published'] = 'Publié';
$string['status_superseded'] = 'Remplacé';
$string['status_underreview'] = 'En cours de révision';
$string['campus_archives_desc'] = 'Consulter le Registraire, les preuves, les Kristals et les enregistrements de provenance validés.';
$string['campus_archives_title'] = 'Registraire et mémoire';
$string['campus_assemblies_desc'] = 'Participer aux Assemblées, motions, votes, décisions et contestations.';
$string['campus_assemblies_title'] = 'Assemblées';
$string['campus_boundary_notice'] = 'Le socle UCKK est autonome. Konnaxion, Smart Vote et l’IA sont optionnels et non souverains.';
$string['campus_canon_desc'] = 'Lire les principes, règles, limites et repères canoniques de l’UCKK.';
$string['campus_canon_title'] = 'Canon UCKK';
$string['campus_challenges_desc'] = 'Explorer les Défis King Klown, les preuves attendues et les validations associées.';
$string['campus_challenges_title'] = 'Défis King Klown';
$string['campus_dashboard_desc'] = 'Accéder à la synthèse personnelle du Joueur, aux parcours, preuves, alertes et actions utiles.';
$string['campus_dashboard_title'] = 'Tableau de bord Joueur';
$string['campus_format_desc'] = 'Parcourir les cours selon la structure UCKK.';
$string['campus_format_title'] = 'Format de cours UCKK';
$string['campus_formula'] = 'Connaître → Choisir → Agir → Se souvenir';
$string['campus_integrity_desc'] = 'Suivre les garde-fous d’intégrité, les corrections et les révisions Inquisiteur.';
$string['campus_integrity_title'] = 'Intégrité et Inquisiteur';
$string['campus_intro'] = 'Bienvenue dans l’Univers-Cité King Klown, bibliothèque publique vivante et établissement virtuel de puissance opératoire consacré au Grand Jeu social : lire les systèmes, relier les savoirs, pratiquer, délibérer et tenir mémoire.';
$string['campus_pathways_desc'] = 'Explorer les parcours, cours, pratiques, traces et repères associés.';
$string['campus_pathways_title'] = 'Parcours UCKK';
$string['campus_programs_desc'] = 'Consulter les voies ouvertes, les cours associés et les repères de progression.';
$string['campus_programs_title'] = 'Voies UCKK';
$string['campus_recognition_notice'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['campus_reports_desc'] = 'Consulter les rapports institutionnels selon vos permissions.';
$string['campus_reports_title'] = 'Rapports institutionnels';
$string['campus_seed_desc'] = 'Installer ou vérifier les catégories, cours, rôles, parcours, repères UCKK et presets UCKK.';
$string['campus_seed_title'] = 'Installation et presets';
$string['campus_subtitle'] = 'Bibliothèque publique vivante et cadre d’apprentissage ouvert pour comprendre, pratiquer, délibérer et tenir mémoire.';
$string['campus_tagline'] = 'Comprendre le jeu. Jouer avec lucidité. Changer les règles.';
$string['campus_title'] = 'Univers-Cité King Klown';
$string['campusdescription'] = 'Description de l’UCKK';
$string['campusdescription_desc'] = 'Texte de présentation affiché sur les pages institutionnelles UCKK.';
$string['campusshortname'] = 'Nom court de l’UCKK';
$string['campusshortname_desc'] = 'Nom court utilisé pour identifier l’UCKK.';
$string['campustagline'] = 'Slogan de l’UCKK';
$string['campustagline_desc'] = 'Phrase courte affichée dans les interfaces UCKK.';
$string['campustitle'] = 'Titre de l’UCKK';
$string['campustitle_desc'] = 'Titre principal affiché pour l’UCKK.';
$string['default_campusdescription'] = 'Bibliothèque publique vivante et établissement virtuel de puissance opératoire consacré au Grand Jeu social.';
$string['default_campustagline'] = 'Comprendre le jeu. Jouer avec lucidité. Changer les règles.';
$string['default_campustitle'] = 'Univers-Cité King Klown';
$string['canon:intro'] = 'Principes, limites et repères institutionnels UCKK.';
$string['canon:title'] = 'Canon UCKK';
$string['canon_archiveonpublish'] = 'Inscrire au Registraire à la publication';
$string['canon_archiveonpublish_label'] = 'Créer une trace de registre lorsque cet élément canonique est publié.';
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
$string['canon_published_requires_archive'] = 'Un élément canonique publié doit disposer d’une trace de registre.';
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
$string['canon_type_archive_policy'] = 'Politique de registre';
$string['canon_type_course_reference'] = 'Référence de cours';
$string['canon_type_definition'] = 'Définition';
$string['canon_type_governance'] = 'Gouvernance';
$string['canon_type_integrity_policy'] = 'Politique d’intégrité';
$string['canon_type_kristal'] = 'Kristal';
$string['canon_type_method'] = 'Méthode';
$string['canon_type_principle'] = 'Principe';
$string['canon_type_program'] = 'Voie';
$string['canon_type_rule'] = 'Règle';
$string['canon_type_symbolic_boundary'] = 'Frontière symbolique';
$string['canon_visibility_invalid'] = 'La visibilité canonique est invalide.';
$string['canonrefreshed'] = 'Canon actualisé.';
$string['canonrefreshfailed'] = 'Impossible d’actualiser le canon.';
$string['canonrefreshing'] = 'Actualisation du canon…';
$string['canonurl'] = 'URL du canon';
$string['canonurl_desc'] = 'URL de la page ou ressource canonique principale.';
$string['defaultarchivevisibility'] = 'Visibilité par défaut des registres';
$string['defaultarchivevisibility_desc'] = 'Visibilité appliquée par défaut aux nouveaux objets du Registraire UCKK.';
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
$string['enablememorylayer_desc'] = 'Active les liens de mémoire, de registre et de provenance entre objets UCKK.';
$string['enablenavigationregistry'] = 'Activer le registre de navigation';
$string['enablenavigationregistry_desc'] = 'Expose les destinations UCKK communes aux plugins autorisés.';
$string['enableplayerprofiles'] = 'Activer les profils Joueurs';
$string['enableplayerprofiles_desc'] = 'Permet la création et l’affichage des profils Joueurs UCKK.';
$string['enableprogramregistry'] = 'Activer le registre des voies';
$string['enableprogramregistry_desc'] = 'Active le registre institutionnel des voies et parcours UCKK.';
$string['enablesymbolicroles'] = 'Activer les rôles symboliques';
$string['enablesymbolicroles_desc'] = 'Permet l’affichage des titres et rôles symboliques sans leur donner d’autorité Moodle.';
$string['enforceinstitutionalclarity'] = 'Maintenir la clarté institutionnelle';
$string['enforceinstitutionalclarity_desc'] = 'Affiche une note courte de clarté institutionnelle sans répéter les limites d’accréditation sur chaque page.';
$string['internalrecognitionnotice'] = 'Note institutionnelle';
$string['internalrecognitionnotice_desc'] = 'Texte affiché pour préciser brièvement le statut des éventuelles reconnaissances UCKK.';
$string['internalrecognitionnotice_short'] = 'Repères UCKK internes.';
$string['koadigitalecosystemurl'] = 'URL de l’écosystème digital kOA';
$string['koadigitalecosystemurl_desc'] = 'Lien optionnel vers une ressource externe liée à l’écosystème digital kOA.';
$string['logaiprovenance'] = 'Journaliser la provenance IA';
$string['logaiprovenance_desc'] = 'Enregistre les marqueurs de provenance lorsque l’IA assiste un contenu ou une action.';
$string['preventsilentdeletion'] = 'Empêcher les suppressions silencieuses';
$string['preventsilentdeletion_desc'] = 'Exige une trace ou une justification lorsqu’un objet institutionnel UCKK est supprimé.';
$string['publicarchiveurl'] = 'URL publique du Registraire';
$string['publicarchiveurl_desc'] = 'URL utilisée pour le Registraire public lorsque cette visibilité est activée.';
$string['requireaihumandecision'] = 'Exiger une décision humaine après IA';
$string['requireaihumandecision_desc'] = 'Empêche les sorties IA de devenir des décisions finales sans validation humaine.';
$string['requirearchiveprovenance'] = 'Exiger la provenance de registre';
$string['requirearchiveprovenance_desc'] = 'Exige une provenance documentée pour les objets inscrits au Registraire.';
$string['requirecontestability'] = 'Exiger la contestabilité';
$string['requirecontestability_desc'] = 'Exige un chemin de contestation pour les décisions ou objets institutionnels concernés.';
$string['requirehumanvalidation'] = 'Exiger une validation humaine';
$string['requirehumanvalidation_desc'] = 'Impose une validation humaine avant publication ou reconnaissance institutionnelle.';
$string['requirelevelnaming'] = 'Exiger la nomenclature des niveaux';
$string['requirelevelnaming_desc'] = 'Vérifie que les niveaux, parcours et voies utilisent la nomenclature UCKK attendue.';
$string['requireprovenance'] = 'Exiger la provenance';
$string['requireprovenance_desc'] = 'Exige une source ou trace de provenance pour les objets UCKK concernés.';
$string['separatesymbolicandtechnicalroles'] = 'Séparer rôles symboliques et rôles techniques';
$string['separatesymbolicandtechnicalroles_desc'] = 'Rappelle que les titres UCKK ne remplacent jamais les rôles et capacités Moodle.';
$string['settings_advanced'] = 'Réglages avancés';
$string['settings_advanced_desc'] = 'Réglages techniques avancés du noyau UCKK.';
$string['settings_archives_desc'] = 'Réglages liés au Registraire, aux preuves, aux Kristals et aux exportations.';
$string['settings_domain'] = 'Domaine UCKK';
$string['settings_domain_desc'] = 'Réglages liés aux frontières de domaine et à la clarté institutionnelle.';
$string['settings_external'] = 'Systèmes externes';
$string['settings_external_desc'] = 'Réglages liés aux liens externes et intégrations optionnelles.';
$string['settings_integrations'] = 'Intégrations';
$string['settings_integrations_desc'] = 'Réglages liés à la coordination entre plugins UCKK et systèmes optionnels.';
$string['settings_integrity_desc'] = 'Réglages liés à l’intégrité, aux avertissements et aux révisions.';
$string['settings_navigation'] = 'Navigation';
$string['settings_navigation_desc'] = 'Réglages liés aux liens et destinations partagées de l’UCKK.';
$string['settings_pathways_desc'] = 'Réglages liés aux voies, parcours et profils Joueurs.';
$string['settings_symbolicroles'] = 'Rôles symboliques';
$string['settings_symbolicroles_desc'] = 'Réglages d’affichage des rôles symboliques UCKK.';
$string['showaiwarning'] = 'Afficher l’avertissement IA';
$string['showaiwarning_desc'] = 'Affiche un rappel indiquant que l’IA est assistive et non souveraine.';
$string['showbatisseur'] = 'Afficher le rôle Bâtisseur';
$string['showbatisseur_desc'] = 'Permet l’affichage du titre symbolique Bâtisseur.';
$string['showboundarynotice'] = 'Afficher la note institutionnelle';
$string['showboundarynotice_desc'] = 'Affiche la note institutionnelle UCKK.';
$string['showcartographe'] = 'Afficher le rôle Cartographe';
$string['showcartographe_desc'] = 'Permet l’affichage du titre symbolique Cartographe.';
$string['showdashboardarchivecard'] = 'Afficher la carte Registraire';
$string['showdashboardarchivecard_desc'] = 'Affiche la carte Registraire dans le tableau de bord UCKK.';
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
$string['showinternalrecognitionnotice'] = 'Afficher la note institutionnelle';
$string['showinternalrecognitionnotice_desc'] = 'Affiche une note courte sur le statut des éventuelles reconnaissances UCKK.';
$string['showjoueurlucide'] = 'Afficher le titre Joueur lucide';
$string['showjoueurlucide_desc'] = 'Permet l’affichage du titre symbolique Joueur lucide.';
$string['symbolicrole_permission_warning'] = 'Les rôles symboliques ne donnent aucune permission Moodle par eux-mêmes.';
$string['integrateai'] = 'Intégrer la Production IA';
$string['integrateai_desc'] = 'Active les liens vers le fournisseur IA UCKK lorsque disponible.';
$string['integratearchive'] = 'Intégrer le Registraire';
$string['integratearchive_desc'] = 'Active les liens et indicateurs vers le Registraire.';
$string['integrateassembly'] = 'Intégrer les Assemblées';
$string['integrateassembly_desc'] = 'Active les liens et indicateurs vers le module Assemblées.';
$string['integratechallenge'] = 'Intégrer les Défis';
$string['integratechallenge_desc'] = 'Active les liens et indicateurs vers le module Défis.';
$string['integrateintegrity'] = 'Intégrer l’Intégrité';
$string['integrateintegrity_desc'] = 'Active les liens et indicateurs vers l’outil Inquisiteur.';
$string['integratereports'] = 'Intégrer les rapports';
$string['integratereports_desc'] = 'Active les liens vers les rapports institutionnels UCKK.';
$string['integration_ai'] = 'Production IA';
$string['integration_archive'] = 'Registraire';
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
$string['privacy:path:programs'] = 'Voies';
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
$string['dashboard_card_archives_desc'] = 'Consulter les preuves, registres et traces de provenance.';
$string['dashboard_card_archives_title'] = 'Registraire';
$string['dashboard_card_assemblies_desc'] = 'Suivre les Assemblées, motions, votes et décisions.';
$string['dashboard_card_assemblies_title'] = 'Assemblées';
$string['dashboard_card_badges_desc'] = 'Voir les repères, traces et jalons de progression obtenus.';
$string['dashboard_card_badges_title'] = 'Repères';
$string['dashboard_card_challenges_desc'] = 'Suivre les Défis King Klown et leurs preuves.';
$string['dashboard_card_challenges_title'] = 'Défis';
$string['dashboard_card_courses_desc'] = 'Accéder aux cours et activités de l’UCKK.';
$string['dashboard_card_courses_title'] = 'Cours';
$string['dashboard_card_pathway_desc'] = 'Voir la progression dans les parcours UCKK.';
$string['dashboard_card_pathway_title'] = 'Parcours';
$string['dashboard_empty_text'] = 'Aucune donnée de tableau de bord n’est disponible pour le moment.';
$string['dashboard_empty_title'] = 'Tableau de bord vide';
$string['dashboard_integrity_notice'] = 'Certaines informations peuvent être restreintes par les règles d’intégrité.';
$string['dashboard_intro'] = 'Synthèse de votre progression, de vos traces, parcours et actions UCKK.';
$string['dashboard_subtitle'] = 'Votre état courant dans l’UCKK.';
$string['dashboard_title'] = 'Tableau de bord UCKK';
$string['program:category'] = 'Espace de cours associé';
$string['program:category_none'] = 'Aucun espace de cours associé';
$string['program:cohortidnumber'] = 'Identifiant de cohorte';
$string['program:color'] = 'Couleur';
$string['program:description'] = 'Description de la voie';
$string['program:display'] = 'Affichage';
$string['program:error_category'] = 'La catégorie de la voie est invalide.';
$string['program:error_color'] = 'La couleur de la voie est invalide.';
$string['program:error_csvfield'] = 'Le champ CSV de la voie est invalide.';
$string['program:error_idnumber'] = 'L’identifiant de la voie est invalide.';
$string['program:error_metadatajson'] = 'Les métadonnées JSON de la voie sont invalides.';
$string['program:error_programtype'] = 'Le type de voie est invalide.';
$string['program:error_shortname'] = 'Le nom court de la voie est invalide.';
$string['program:error_sortorder'] = 'L’ordre d’affichage de la voie est invalide.';
$string['program:error_status'] = 'Le statut de la voie est invalide.';
$string['program:error_visibility'] = 'La visibilité de la voie est invalide.';
$string['program:fullname'] = 'Nom complet de la voie';
$string['program:general'] = 'Informations générales';
$string['program:governance'] = 'Gouvernance';
$string['program:icon'] = 'Icône';
$string['program:idnumber'] = 'Identifiant de la voie';
$string['program:internalrecognition'] = 'Repère interne';
$string['program:internalrecognition_desc'] = 'Texte indiquant la nature interne du repère associé à la voie.';
$string['program:limitsnotice'] = 'Note institutionnelle';
$string['program:limitsnotice_default'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['program:metadata'] = 'Métadonnées';
$string['program:metadatajson'] = 'Métadonnées JSON';
$string['program:outcomes'] = 'Résultats attendus';
$string['program:recognition'] = 'Repère de progression';
$string['program:requiredbadges'] = 'Parchemins requis';
$string['program:requiredcompetencies'] = 'Compétences requises';
$string['program:requiredcourses'] = 'Cours requis';
$string['program:requiresarchive'] = 'Registre requis';
$string['program:requiresarchive_desc'] = 'Exige un objet inscrit au Registraire pour compléter ou documenter cette voie.';
$string['program:requiresintegrityreview'] = 'Révision d’intégrité requise';
$string['program:requiresintegrityreview_desc'] = 'Exige une révision d’intégrité avant publication ou validation institutionnelle.';
$string['program:requiresportfolio'] = 'Portfolio requis';
$string['program:requiresportfolio_desc'] = 'Exige un portfolio associé à la voie.';
$string['program:shortname'] = 'Nom court de la voie';
$string['program:sortorder'] = 'Ordre d’affichage';
$string['program:status'] = 'Statut de la voie';
$string['program:structure'] = 'Structure';
$string['program:type'] = 'Type de voie';
$string['program:type_baccalaureat'] = 'Voie UCKK — Niveau visé : Puissance opératoire';
$string['program:type_certificat'] = 'Parcours d’initiation';
$string['program:type_laboratoire'] = 'Laboratoire';
$string['program:type_mineure'] = 'Voie UCKK — Niveau visé : Initiation';
$string['program:type_seminaire'] = 'Séminaire';
$string['program:type_tronccommun'] = 'Tronc commun';
$string['program:visibility'] = 'Visibilité de la voie';
$string['local/uckk:viewcampus'] = 'Voir l’espace UCKK';
$string['local/uckk:manageprograms'] = 'Gérer les voies UCKK';
$string['local/uckk:managepathways'] = 'Gérer les parcours UCKK';
$string['local/uckk:manageprofiles'] = 'Gérer les profils Joueurs UCKK';
$string['local/uckk:managecanon'] = 'Gérer le canon UCKK';
$string['local/uckk:viewreports'] = 'Voir les rapports UCKK';
$string['local/uckk:exportdata'] = 'Exporter les données UCKK';
$string['local/uckk:viewrestricted'] = 'Voir les informations restreintes UCKK';
$string['local/uckk:manageintegrations'] = 'Gérer les intégrations UCKK';


// Public pages — shared public vocabulary.
$string['public_metadata_heading'] = 'Repères publics';
$string['public_metadata_registry_heading'] = 'État public';
$string['public_metadata_nature'] = 'Nature';
$string['public_metadata_role'] = 'Rôle';
$string['public_metadata_recognition'] = 'Repères';
$string['public_metadata_public_limit'] = 'Note institutionnelle';
$string['public_metadata_active_pathways'] = 'Voies ouvertes publiées';
$string['public_metadata_internal_parchments'] = 'Repères, traces et jalons UCKK';
$string['public_metadata_not_accredited'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['public_nature_value'] = 'Établissement virtuel de puissance opératoire';
$string['public_role_value'] = 'Branche éducative du mouvement kOA';

// Public home page.
$string['public_home_title'] = 'Univers-Cité King Klown';
$string['public_home_eyebrow'] = 'Bibliothèque publique vivante';
$string['public_home_subtitle'] = 'Comprendre le jeu. Jouer avec lucidité. Changer les règles.';
$string['public_home_summary'] = 'L’UCKK relie cours, voies, médiathèque, archives, défis et assemblées pour diffuser le savoir dans un cadre d’apprentissage familier, modernisé, ouvert et praticable.';
$string['public_home_quicklink_pathways_label'] = 'Explorer les voies';
$string['public_home_quicklink_pathways_desc'] = 'Situer les grands chemins d’exploration, les domaines d’action et les parcours possibles.';
$string['public_home_quicklink_courses_label'] = 'Voir les cours';
$string['public_home_quicklink_courses_desc'] = 'Accéder aux cours, aux ressources, aux exercices et aux repères disponibles.';
$string['public_home_quicklink_mediatheque_label'] = 'Consulter la médiathèque';
$string['public_home_quicklink_mediatheque_desc'] = 'Parcourir les contenus publics, collections, références, archives et traces documentées.';
$string['public_home_orientation_eyebrow'] = 'Orientation';
$string['public_home_orientation_title'] = 'Une bibliothèque publique pour lire, pratiquer et tenir mémoire';
$string['public_home_orientation_body'] = 'L’Univers-Cité King Klown organise la diffusion du savoir comme un cycle complet : connaître, choisir, agir, documenter, corriger et transmettre.';
$string['public_home_orientation_item_observe'] = 'Observer les règles, récits, institutions, technologies et pouvoirs qui structurent le monde social.';
$string['public_home_orientation_item_choose'] = 'Choisir des chemins d’exploration reliés à des voies, des cours, des défis et des traces.';
$string['public_home_orientation_item_act'] = 'Agir avec méthode par des productions, enquêtes, prototypes, interventions ou contributions.';
$string['public_home_orientation_item_archive'] = 'Conserver les traces utiles dans des portfolios, archives, assemblées et unités de mémoire.';
$string['public_home_boundary_eyebrow'] = 'Accès public';
$string['public_home_boundary_title'] = 'Ce que la page publique ouvre';
$string['public_home_boundary_body'] = 'Les pages publiques ouvrent une bibliothèque vivante : voies, cours, défis, assemblées, médiathèque, archives et repères d’intégrité. Elles ne remplacent pas les espaces privés, les inscriptions, les rôles, les permissions ou les dossiers internes.';
$string['public_home_cards_heading'] = 'Portes d’entrée';
$string['public_home_notice_title'] = 'Note institutionnelle';
$string['public_home_notice_body'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['public_home_cta_title'] = 'Explorer les cours';
$string['public_home_cta_body'] = 'Commencer par les voies, ouvrir les cours associés, puis relier les savoirs aux défis, aux assemblées, aux archives et à la médiathèque.';
$string['public_home_cta_label'] = 'Explorer les cours';

// Public about page.
$string['public_about_title'] = 'À propos';
$string['public_about_eyebrow'] = 'Bibliothèque publique et cadre d’apprentissage';
$string['public_about_subtitle'] = 'L’Univers-Cité King Klown est la branche éducative ouverte du mouvement kOA.';
$string['public_about_summary'] = 'L’UCKK est une bibliothèque publique vivante et un établissement virtuel de puissance opératoire consacré au Grand Jeu social. Elle organise, documente, met en scène et transmet des savoirs liés aux systèmes humains, aux technologies, aux preuves et à la gouvernance collective.';
$string['public_about_what_title'] = 'Ce qu’est l’UCKK';
$string['public_about_what_body'] = 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire en émergence. Elle rassemble des cours, des voies, des défis, des assemblées, un Registraire, des archives, une médiathèque, des preuves et des repères de progression. Sa fonction immédiate est la diffusion publique du savoir dans un cadre d’apprentissage familier, modernisé et ouvert.';
$string['public_about_campus_title'] = 'Ce que l’espace public ouvre';
$string['public_about_campus_body'] = 'L’espace public donne accès aux repères institutionnels de l’UCKK : voies, cours, défis, assemblées, médiathèque, Registraire, règles d’intégrité et informations publiques. Les espaces internes, inscriptions, rôles, permissions, validations et dossiers privés restent gérés dans les espaces appropriés.';
$string['public_about_not_title'] = 'Note institutionnelle';
$string['public_about_not_body'] = 'Il n’y a pas de projet à court terme d’offrir une certification, un diplôme ou une reconnaissance formelle. Le but immédiat de l’UCKK est la diffusion publique du savoir dans un cadre d’apprentissage familier, modernisé et ouvert.';
$string['public_about_roles_title'] = 'King Klown, Inquisiteur, Assemblées et Archives';
$string['public_about_roles_body'] = 'King Klown ouvre la scène pédagogique et rend les systèmes visibles. Il attire l’attention, rend les situations mémorables et invite à apprendre par le théâtre public, sans devenir l’autorité finale. L’Inquisiteur protège l’intégrité du jeu : il interroge les faits, les preuves, les limites et les risques de confusion. Les Assemblées donnent une forme collective à l’orientation, à la contestation, à l’arbitrage et aux décisions. Les Archives conservent les traces utiles, les versions, les preuves, les décisions et les corrections.';
$string['public_about_cards_heading'] = 'Repères institutionnels';
$string['public_about_card_koa_title'] = 'kOA';
$string['public_about_card_koa_body'] = 'Le mouvement large : vision, culture, principes, stratégie et transformation des règles.';
$string['public_about_card_uckk_title'] = 'UCKK';
$string['public_about_card_uckk_body'] = 'La branche éducative ouverte : voies, cours, défis, preuves, assemblées, médiathèque, archives et puissance opératoire.';
$string['public_about_card_kingklown_title'] = 'King Klown';
$string['public_about_card_kingklown_body'] = 'La figure narrative : il attire l’attention, rend les systèmes mémorables et ouvre des scènes de lucidité.';
$string['public_about_card_inquisiteur_title'] = 'Inquisiteur';
$string['public_about_card_inquisiteur_body'] = 'Le garde-fou éthique : il protège la preuve, la dignité, la clarté et la critique.';
$string['public_about_card_assemblies_title'] = 'Assemblées';
$string['public_about_card_assemblies_body'] = 'La légitimité collective : discussion, arbitrage, décisions, contestations et mémoire institutionnelle.';
$string['public_about_card_archives_title'] = 'Archives';
$string['public_about_card_archives_body'] = 'La mémoire : conservation des traces, versions, preuves, corrections et décisions.';
$string['public_about_notice_body'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['public_about_cta_title'] = 'Comprendre l’architecture UCKK';
$string['public_about_cta_body'] = 'Commencer par les voies, puis explorer les cours, les défis, les assemblées, les archives et la médiathèque.';
$string['public_about_cta_label'] = 'Voir les voies';

// Public programs page.
$string['public_programs_title'] = 'Voies UCKK';
$string['public_programs_eyebrow'] = 'Bibliothèque publique vivante';
$string['public_programs_subtitle'] = 'Parcours ouverts pour explorer, relier et pratiquer les savoirs du Grand Jeu social.';
$string['public_programs_summary'] = 'Les Voies UCKK organisent la diffusion du savoir en parcours lisibles : cours, repères, pratiques, archives, médiathèque, défis et assemblées.';
$string['public_programs_orientation_eyebrow'] = 'Orientation';
$string['public_programs_orientation_title'] = 'Circuler dans les Voies UCKK';
$string['public_programs_orientation_body'] = 'Les Voies UCKK sont des cartes d’exploration et de pratique. Elles relient les cours, les notions, les lectures, les exercices, les défis, les archives, les assemblées et les repères de progression. Une Voie n’est pas une promesse de diplôme : c’est un chemin public pour organiser l’apprentissage, relier les savoirs et construire une compréhension opératoire.';
$string['public_programs_registry_eyebrow'] = 'Voies ouvertes';
$string['public_programs_registry_title'] = 'Tronc commun et voies ouvertes';
$string['public_programs_registry_body'] = 'Chaque carte présente une Voie ou un bloc d’apprentissage visible publiquement. Elle peut ouvrir vers les espaces de cours associés lorsque ceux-ci sont publiés. Les Voies servent d’orientation dans la bibliothèque UCKK : elles aident à trouver les cours, ressources, défis, archives et repères utiles.';
$string['public_programs_cards_heading'] = 'Voies ouvertes';
$string['public_programs_action_courses'] = 'Voir les cours ouverts';
$string['public_programs_course_space'] = 'Espace d’apprentissage associé';
$string['public_programs_active_metadata'] = 'Voies ouvertes publiées';
$string['public_programs_recognition_metadata'] = 'Repères, traces et jalons UCKK';
$string['public_programs_limit_metadata'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['public_programs_notice_body'] = 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.';
$string['public_programs_cta_title'] = 'Passer des Voies aux cours';
$string['public_programs_cta_body'] = 'Ouvrir les cours associés pour consulter les modules, ressources, exercices, lectures et repères disponibles.';
$string['public_programs_cta_label'] = 'Voir les cours ouverts';

// Public Médiathèque.
$string['nav_mediatheque'] = 'Médiathèque';

$string['mediatheque_title'] = 'Médiathèque UCKK';
$string['mediatheque_eyebrow'] = 'Médiathèque publique';
$string['mediatheque_summary'] = 'Explorer la bibliothèque publique UCKK : médias, collections, références externes, archives et passages documentés.';
$string['mediatheque_boundarynotice'] = 'Cette page affiche seulement les contenus que les politiques de publication et d’accès autorisent à exposer publiquement. Certains médias, passages, fichiers ou détails peuvent être masqués selon les droits, les avis de contenu, la visibilité et les protocoles culturels.';

$string['mediatheque_explorer_title'] = 'Recherche publique';
$string['mediatheque_explorer_summary'] = 'Rechercher dans la bibliothèque publique UCKK : contenus publics, collections accessibles, références externes et passages documentés.';
$string['mediatheque_explorer_loading'] = 'Chargement des contenus…';
$string['mediatheque_explorer_error'] = 'Les contenus publics n’ont pas pu être chargés.';
$string['mediatheque_explorer_empty'] = 'Aucun contenu public ne correspond aux filtres actuels.';
$string['mediatheque_explorer_results'] = 'Résultats';
$string['mediatheque_explorer_result_count_suffix'] = 'résultat(s)';
$string['mediatheque_explorer_load_more'] = 'Charger plus de résultats';

$string['mediatheque_search_label'] = 'Rechercher dans les contenus publics';
$string['mediatheque_search_placeholder'] = 'Titre, mot-clé, source, collection ou référence';
$string['mediatheque_search_button'] = 'Rechercher';
$string['mediatheque_filters_title'] = 'Filtres';
$string['mediatheque_apply_filters'] = 'Appliquer les filtres';
$string['mediatheque_clear_filters'] = 'Réinitialiser';

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

$string['mediatheque_mediatype_all'] = 'Tous les formats';
$string['mediatheque_mediatype_video'] = 'Vidéo';
$string['mediatheque_mediatype_audio'] = 'Audio';
$string['mediatheque_mediatype_text'] = 'Texte';
$string['mediatheque_mediatype_code'] = 'Code';
$string['mediatheque_mediatype_image'] = 'Image';
$string['mediatheque_mediatype_pdf'] = 'PDF';
$string['mediatheque_mediatype_document'] = 'Document';
$string['mediatheque_mediatype_book'] = 'Livre';
$string['mediatheque_mediatype_external_reference'] = 'Référence externe';
$string['mediatheque_mediatype_other'] = 'Autre';

$string['mediatheque_sort_relevance'] = 'Pertinence';
$string['mediatheque_sort_newest'] = 'Plus récent';
$string['mediatheque_sort_title'] = 'Titre';
$string['mediatheque_sort_type'] = 'Type';
$string['mediatheque_sort_collection'] = 'Collection';
$string['mediatheque_sort_validated'] = 'Validation';

$string['mediatheque_type_all'] = 'Tous';
$string['mediatheque_type_media'] = 'Médias';
$string['mediatheque_type_collection'] = 'Collections';
$string['mediatheque_type_external_work'] = 'Références externes';
$string['mediatheque_type_archive_item'] = 'Documents publics';
$string['mediatheque_type_content_marker'] = 'Passages documentés';

$string['mediatheque_sitewide'] = 'Recherche publique globale';
$string['mediatheque_archive_scoped'] = 'Recherche limitée à un registre';
$string['mediatheque_module_scoped'] = 'Recherche limitée à une activité de registre';

$string['mediatheque_notice_public_only'] = 'Seuls les contenus publics autorisés sont affichés.';
$string['mediatheque_notice_policy_filtered'] = 'Les résultats sont filtrés par les politiques de publication et d’accès.';
$string['mediatheque_notice_no_direct_files'] = 'Les fichiers originaux ne sont pas exposés directement depuis cette page.';

$string['mediatheque_action_open'] = 'Ouvrir';
$string['mediatheque_action_view_details'] = 'Voir les détails';
$string['mediatheque_action_view_file'] = 'Voir le fichier autorisé';
$string['mediatheque_action_download_disabled'] = 'Téléchargement non disponible publiquement';

$string['mediatheque_metadata_surface'] = 'Surface publique';
$string['mediatheque_metadata_policy_owner'] = 'Données et politiques';
$string['mediatheque_metadata_surface_value'] = 'local_uckk';
$string['mediatheque_metadata_policy_owner_value'] = 'Moteur média interne';

// Faculty public pages.
$string['faculty'] = 'Voie';
$string['facultyprofile'] = 'Profil de la faculté';
$string['facultyatlasprogram'] = 'Programme Atlas';
$string['facultycourses'] = 'Cours de la Voie';
$string['facultyprojectfinal'] = 'Projet final';
$string['facultyethicallimits'] = 'Limites éthiques';
$string['facultyrelations'] = 'Relations inter-voies';
$string['title'] = 'Titre';
$string['level'] = 'Niveau';
$string['category'] = 'Catégorie';
$string['navigation'] = 'Navigation';
$string['role'] = 'Rôle';
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


// Public courses explorer.
$string['course_explorer_title'] = 'Explorer les cours';
$string['course_explorer_summary'] = 'Filtrer les cours publics par mot-clé, voie associée et ordre d’affichage.';
$string['course_explorer_search_label'] = 'Rechercher dans les cours';
$string['course_explorer_search_placeholder'] = 'Rechercher un cours, un code ou un thème';
$string['course_explorer_search_button'] = 'Rechercher';
$string['course_explorer_filters_title'] = 'Filtres';
$string['course_explorer_filter_category'] = 'Voie';
$string['course_explorer_filter_sort'] = 'Tri';
$string['course_explorer_filter_all_categories'] = 'Toutes les voies';
$string['course_explorer_sort_pedagogical'] = 'Ordre pédagogique';
$string['course_explorer_sort_title'] = 'Titre A-Z';
$string['course_explorer_sort_category'] = 'Voie';
$string['course_explorer_results'] = 'Cours trouvés';
$string['course_explorer_result_count_suffix'] = 'cours';
$string['course_explorer_empty_title'] = 'Aucun cours trouvé';
$string['course_explorer_empty_body'] = 'Aucun cours public ne correspond aux filtres actuels.';
$string['course_explorer_reset'] = 'Réinitialiser';
$string['course_explorer_loading'] = 'Chargement des cours…';
$string['course_explorer_error'] = 'Les cours n’ont pas pu être chargés.';
$string['course_explorer_index_label'] = 'Ouvrir l’index des cours';
$string['course_explorer_public_only_notice'] = 'Seuls les cours ouverts publiquement et rattachés à des voies ou espaces visibles sont affichés.';
$string['course_explorer_category_all'] = 'Toutes les voies';
$string['course_explorer_category_uncategorized'] = 'Sans voie publique';
$string['course_explorer_metadata_total'] = 'Cours ouverts affichés';
$string['course_explorer_metadata_source'] = 'Source';
$string['course_explorer_metadata_source_value'] = 'Espaces de cours visibles';
$string['course_explorer_metadata_filter'] = 'Filtre public';
$string['course_explorer_metadata_filter_value'] = 'Cours visibles dans des voies ou espaces publics visibles';
$string['course_explorer_service_name'] = 'Rechercher les cours publics UCKK';
$string['course_explorer_service_desc'] = 'Service externe permettant de rechercher les cours publics UCKK.';

// Course explorer — keys actually called by AMD JS.
$string['courseexplorerloading'] = 'Chargement des cours…';
$string['courseexplorerready'] = 'Liste des cours mise à jour.';
$string['courseexplorerempty'] = 'Aucun cours ne correspond aux filtres actuels.';
$string['courseexplorererror'] = 'Impossible de mettre à jour la liste des cours.';

// Public Faculty page generic labels.
$string['announcements'] = 'Annonces';
$string['highlights'] = 'Points saillants';
$string['faq'] = 'Questions fréquentes';

// Public Faculty pages — DOC_12 public labels.
$string['faculties'] = 'Voies';
$string['facultyannouncements'] = 'Annonces de la Voie';
$string['facultyevents'] = 'Événements de la Voie';
$string['facultypublicpage'] = 'Page publique de Voie';
$string['facultypublicpages'] = 'Pages publiques des Voies';
$string['facultyoverview'] = 'Vue d’ensemble de la Voie';
$string['facultyidentity'] = 'Identité de la Voie';
$string['facultysections'] = 'Sections de la Voie';
$string['facultyfeaturedblocks'] = 'Blocs mis en avant';
$string['facultydynamicblocks'] = 'Blocs dynamiques de la Voie';
$string['facultyfaq'] = 'Questions et réponses';
$string['facultycontact'] = 'Contact de la Voie';
$string['facultynotices'] = 'Avis publics';
$string['facultymetadata'] = 'Repères de la Voie';
$string['facultyemptycourses'] = 'Aucun cours public associé pour le moment.';
$string['facultyemptyannouncements'] = 'Aucune annonce publique pour le moment.';
$string['facultyemptyevents'] = 'Aucun événement public pour le moment.';
$string['facultyemptyrelations'] = 'Aucune relation inter-Voies déclarée pour le moment.';
$string['facultyemptyfaq'] = 'Aucune question publiée pour le moment.';
$string['facultyreadmore'] = 'Lire la suite';
$string['facultyviewcourses'] = 'Explorer les cours';
$string['facultyviewprogram'] = 'Voir l’Atlas des Voies';
$string['facultyviewannouncements'] = 'Voir les annonces';
$string['facultyviewevents'] = 'Voir les événements';
$string['facultybacktofaculties'] = 'Retour aux Voies';
$string['facultyrestricted'] = 'Voie restreinte';
$string['facultyhidden'] = 'Voie masquée';
$string['facultydraft'] = 'Voie en brouillon';
$string['facultynotfound'] = 'Voie introuvable.';
$string['facultyinvalidslug'] = 'Identifiant de Voie invalide.';
$string['facultycanonicalsource'] = 'Source canonique';
$string['facultyatlasprojection'] = 'Projection de l’Atlas des Voies';
$string['facultycoursecode'] = 'Code de cours';
$string['facultyconcept'] = 'Concept de la Voie';
$string['facultyartefact'] = 'Artefact';
$string['facultyartefacts'] = 'Artefacts';
$string['facultycompetency'] = 'Compétence';
$string['facultyprogression'] = 'Progression';
$string['facultydefinition'] = 'Définition';
$string['facultyangle'] = 'Angle de lecture';
$string['facultyguardrails'] = 'Garde-fous';
$string['facultysourceatlas'] = 'Source Atlas';
$string['facultysourceprofile'] = 'Source profil de Voie';
$string['coreconcept'] = 'Concept central';
$string['featuredblocks'] = 'Blocs mis en avant';
$string['questionsandanswers'] = 'Questions et réponses';
$string['publicnotices'] = 'Avis publics';
$string['finalproject'] = 'Projet intégrateur';
$string['ethicallimits'] = 'Limites éthiques';
$string['intervoierelations'] = 'Relations avec les autres Voies';

// Public Voie pages — terminology alignment.
$string['core_concept'] = 'Concept central';
$string['relationswithothervoies'] = 'Relations avec les autres Voies';
$string['voieprogram'] = 'Programme de la Voie';
$string['publicfacultyprofile'] = 'Profil public de Voie';
