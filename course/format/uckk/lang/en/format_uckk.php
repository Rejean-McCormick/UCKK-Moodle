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
 * French language strings for the UCKK course format.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Required Moodle strings.
$string['pluginname'] = 'Format UCKK';
$string['sectionname'] = 'Section UCKK';
$string['privacy:metadata'] = 'Le format de cours UCKK ne stocke aucune donnée personnelle.';

// Page type strings.
$string['page-course-view-uckk'] = 'Toute page principale de cours au format UCKK';
$string['page-course-view-uckk-x'] = 'Toute page de cours au format UCKK';

// Core course format actions.
$string['addsections'] = 'Ajouter des sections UCKK';
$string['currentsection'] = 'Section actuelle';
$string['editsection'] = 'Modifier la section';
$string['editsectionname'] = 'Modifier le nom de la section';
$string['deletesection'] = 'Supprimer la section';
$string['newsectionname'] = 'Nouveau nom de section : {$a}';
$string['hidefromothers'] = 'Masquer la section';
$string['showfromothers'] = 'Afficher la section';
$string['section0name'] = 'Orientation';
$string['sectionnamecustom'] = 'Section personnalisée';
$string['sectionnotavailable'] = 'Cette section UCKK n’est pas disponible.';
$string['sectionhidden'] = 'Section masquée';
$string['sectionvisible'] = 'Section visible';

// Format identity.
$string['formatname'] = 'Format UCKK';
$string['formatfullname'] = 'Format de cours de l’Univers-Cité King Klown';
$string['formatshortname'] = 'UCKK';
$string['formatdescription'] = 'Structure pédagogique UCKK : orientation, concepts, matière canonique, atelier, preuves, délibération, livrable, évaluation et archive.';
$string['formatpurpose'] = 'Ce format transforme un cours Moodle en parcours UCKK orienté vers la preuve, la délibération, l’intégrité, l’action et la mémoire.';
$string['formatnotetheme'] = 'Ce format organise le cours. Le thème UCKK gère l’apparence visuelle.';
$string['formatnotcore'] = 'Ce format n’altère pas le cœur Moodle.';
$string['formatnotdegree'] = 'Les reconnaissances UCKK sont internes, sauf reconnaissance officielle future.';

// Canonical course sections.
$string['section_orientation'] = 'Orientation';
$string['section_concepts'] = 'Concepts clés';
$string['section_canon'] = 'Matière canonique';
$string['section_workshop'] = 'Atelier';
$string['section_proofs'] = 'Preuves';
$string['section_deliberation'] = 'Délibération';
$string['section_deliverable'] = 'Livrable';
$string['section_evaluation'] = 'Évaluation';
$string['section_archive'] = 'Archive';

$string['section_orientation_desc'] = 'Entrée dans le cours, intention pédagogique, règles, attentes, parcours et avertissements institutionnels.';
$string['section_concepts_desc'] = 'Définitions, distinctions, vocabulaire, modèles et concepts nécessaires pour lire le problème étudié.';
$string['section_canon_desc'] = 'Textes, ressources, documents, références et éléments canoniques du cours.';
$string['section_workshop_desc'] = 'Ateliers d’analyse, exercices, simulations, cartes, laboratoires et travaux préparatoires.';
$string['section_proofs_desc'] = 'Production, dépôt, discussion et validation des preuves pédagogiques.';
$string['section_deliberation_desc'] = 'Discussion structurée, assemblée, objections, interprétations et décisions collectives.';
$string['section_deliverable_desc'] = 'Production finale, artefact, dossier, carte, rapport, stratégie ou prototype.';
$string['section_evaluation_desc'] = 'Évaluation, rétroaction, critères, compétences, badges et décisions pédagogiques.';
$string['section_archive_desc'] = 'Mémoire du cours : preuves, décisions, versions, corrections, Kristals et traces réutilisables.';

// Section short labels.
$string['section_orientation_short'] = 'Orientation';
$string['section_concepts_short'] = 'Concepts';
$string['section_canon_short'] = 'Canon';
$string['section_workshop_short'] = 'Atelier';
$string['section_proofs_short'] = 'Preuves';
$string['section_deliberation_short'] = 'Délibération';
$string['section_deliverable_short'] = 'Livrable';
$string['section_evaluation_short'] = 'Évaluation';
$string['section_archive_short'] = 'Archive';

// Section help labels.
$string['sectionhelp_orientation'] = 'Présente le cadre du cours et les règles du jeu pédagogique.';
$string['sectionhelp_concepts'] = 'Clarifie les mots, modèles et distinctions à maîtriser.';
$string['sectionhelp_canon'] = 'Rassemble la matière de référence du cours.';
$string['sectionhelp_workshop'] = 'Transforme la matière en exercices, cartes, simulations et analyses.';
$string['sectionhelp_proofs'] = 'Rassemble les preuves produites par l’étudiant ou le groupe.';
$string['sectionhelp_deliberation'] = 'Permet de discuter, objecter, interpréter et décider avec méthode.';
$string['sectionhelp_deliverable'] = 'Accueille le livrable final ou l’artefact principal du cours.';
$string['sectionhelp_evaluation'] = 'Rend visibles les critères, rétroactions, compétences et reconnaissances.';
$string['sectionhelp_archive'] = 'Stabilise la mémoire du cours et les traces réutilisables.';

// Course structure.
$string['coursestructure'] = 'Structure du cours UCKK';
$string['coursestructuredefault'] = 'Structure UCKK par défaut';
$string['coursestructurecustom'] = 'Structure UCKK personnalisée';
$string['coursestructurereset'] = 'Réinitialiser la structure UCKK';
$string['coursestructurelocked'] = 'Structure verrouillée';
$string['coursestructureunlocked'] = 'Structure modifiable';
$string['coursestructurelocked_desc'] = 'Lorsque la structure est verrouillée, les sections canoniques UCKK ne peuvent pas être renommées automatiquement par le format.';
$string['coursestructureunlocked_desc'] = 'Lorsque la structure est modifiable, les enseignants peuvent adapter les titres de section selon le cours.';
$string['applydefaultstructure'] = 'Appliquer la structure UCKK par défaut';
$string['applydefaultstructureconfirm'] = 'Voulez-vous appliquer les sections UCKK par défaut à ce cours?';
$string['defaultstructureapplied'] = 'La structure UCKK par défaut a été appliquée.';
$string['defaultstructurealreadyapplied'] = 'La structure UCKK par défaut est déjà présente.';

// Course display.
$string['coursedisplay'] = 'Affichage du cours UCKK';
$string['coursedisplay_singlepage'] = 'Toutes les sections sur une page';
$string['coursedisplay_multipage'] = 'Une section par page';
$string['coursedisplay_cards'] = 'Cartes UCKK';
$string['coursedisplay_timeline'] = 'Parcours UCKK';
$string['coursedisplay_compact'] = 'Compact';
$string['coursedisplay_full'] = 'Complet';
$string['coursedisplay_help'] = 'Détermine comment le parcours UCKK est affiché aux participants.';

// Course index and navigation.
$string['courseindex'] = 'Index du cours UCKK';
$string['courseindex_show'] = 'Afficher l’index UCKK';
$string['courseindex_hide'] = 'Masquer l’index UCKK';
$string['courseindex_desc'] = 'L’index UCKK aide les participants à naviguer entre orientation, concepts, preuves, délibération, livrable, évaluation et archive.';
$string['coursemap'] = 'Carte du cours';
$string['coursemap_show'] = 'Afficher la carte du cours';
$string['coursemap_hide'] = 'Masquer la carte du cours';
$string['coursemap_desc'] = 'La carte du cours donne une vue synthétique du parcours pédagogique UCKK.';
$string['previoussection'] = 'Section précédente';
$string['nextsection'] = 'Section suivante';
$string['returntocourse'] = 'Retour au cours';
$string['gotosection'] = 'Aller à la section';
$string['gotosectionname'] = 'Aller à la section : {$a}';

// UCKK course identity.
$string['uckkcourse'] = 'Cours UCKK';
$string['uckkcoursecode'] = 'Code du cours UCKK';
$string['uckkcoursetype'] = 'Type de cours UCKK';
$string['uckkcoursekind'] = 'Nature du cours UCKK';
$string['uckkcoursekind_standard'] = 'Cours standard';
$string['uckkcoursekind_tronccommun'] = 'Tronc commun';
$string['uckkcoursekind_program'] = 'Programme interne';
$string['uckkcoursekind_lab'] = 'Laboratoire';
$string['uckkcoursekind_seminar'] = 'Séminaire';
$string['uckkcoursekind_challenge'] = 'Défi';
$string['uckkcoursekind_assembly'] = 'Assemblée';
$string['uckkcoursekind_archive'] = 'Archive';
$string['uckkcoursekind_integrity'] = 'Intégrité';
$string['uckkcourseidentity'] = 'Identité UCKK du cours';
$string['uckkcourseidentity_desc'] = 'Décrit le rôle du cours dans l’architecture pédagogique UCKK.';

// Tronc commun.
$string['tronccommun'] = 'Tronc commun';
$string['tronccommun_desc'] = 'Le tronc commun forme la culture commune UCKK : systèmes, preuves, IA non souveraine, délibération, mobilisation responsable, intégrité et mémoire.';
$string['tronccommun_course'] = 'Cours du tronc commun';
$string['tronccommun_required'] = 'Obligatoire dans le tronc commun';
$string['tronccommun_notrequired'] = 'Non obligatoire dans le tronc commun';
$string['tronccommun_progress'] = 'Progression dans le tronc commun';
$string['tronccommun_portfolio'] = 'Portfolio de joueur lucide';
$string['tronccommun_proof'] = 'Preuve du tronc commun';

// UCKK cycle.
$string['cycle'] = 'Cycle UCKK';
$string['cycle_know'] = 'Connaître';
$string['cycle_choose'] = 'Choisir';
$string['cycle_act'] = 'Agir';
$string['cycle_remember'] = 'Se souvenir';
$string['cycle_full'] = 'Connaître → Choisir → Agir → Se souvenir';
$string['cycle_desc'] = 'Le cours doit relier connaissance, décision, action et mémoire.';

// Proofs.
$string['proof'] = 'Preuve';
$string['proofs'] = 'Preuves';
$string['proofsection'] = 'Section des preuves';
$string['proofrequired'] = 'Preuve requise';
$string['proofoptional'] = 'Preuve optionnelle';
$string['proofmissing'] = 'Preuve manquante';
$string['proofsubmitted'] = 'Preuve déposée';
$string['proofvalidated'] = 'Preuve validée';
$string['proofcontested'] = 'Preuve contestée';
$string['proofinvalidated'] = 'Preuve invalidée';
$string['proofrequirements'] = 'Exigences de preuve';
$string['proofrequirements_desc'] = 'Décrit les traces attendues : texte, fichier, carte, journal IA, rapport, décision, archive ou autre artefact vérifiable.';
$string['proofsummary'] = 'Synthèse des preuves';
$string['proofstatus'] = 'Statut des preuves';
$string['proofsource'] = 'Source de la preuve';
$string['proofprovenance'] = 'Provenance de la preuve';

// Deliberation and assemblies.
$string['deliberation'] = 'Délibération';
$string['deliberationsection'] = 'Section de délibération';
$string['deliberationrequired'] = 'Délibération requise';
$string['assembly'] = 'Assemblée';
$string['assemblies'] = 'Assemblées';
$string['assemblylinked'] = 'Assemblée liée';
$string['assemblymissing'] = 'Aucune assemblée liée';
$string['assemblyrequired'] = 'Assemblée requise';
$string['motion'] = 'Proposition';
$string['objection'] = 'Objection';
$string['amendment'] = 'Amendement';
$string['decision'] = 'Décision';
$string['decisionrecord'] = 'Trace de décision';
$string['minorityreport'] = 'Rapport minoritaire';
$string['contestability'] = 'Contestabilité';
$string['deliberation_desc'] = 'La délibération rend visibles les arguments, objections, décisions, désaccords persistants et traces de mémoire.';

// Deliverable.
$string['deliverable'] = 'Livrable';
$string['deliverables'] = 'Livrables';
$string['finaldeliverable'] = 'Livrable final';
$string['deliverablerequired'] = 'Livrable requis';
$string['deliverablemissing'] = 'Livrable manquant';
$string['deliverablesubmitted'] = 'Livrable déposé';
$string['deliverablevalidated'] = 'Livrable validé';
$string['deliverable_desc'] = 'Le livrable final transforme l’apprentissage en artefact : carte, analyse, stratégie, dossier, prototype, rapport ou portfolio.';

// Evaluation.
$string['evaluation'] = 'Évaluation';
$string['evaluationcriteria'] = 'Critères d’évaluation';
$string['evaluationrubric'] = 'Grille d’évaluation';
$string['evaluationfeedback'] = 'Rétroaction';
$string['evaluationpending'] = 'Évaluation en attente';
$string['evaluationcompleted'] = 'Évaluation terminée';
$string['evaluation_desc'] = 'L’évaluation relie les critères, les preuves, les compétences, les badges, la rétroaction et la possibilité de correction.';

// Archive.
$string['archive'] = 'Archive';
$string['archives'] = 'Archives';
$string['archivesection'] = 'Section d’archive';
$string['archiveitem'] = 'Élément d’archive';
$string['archiveitems'] = 'Éléments d’archive';
$string['archiveenabled'] = 'Archive activée';
$string['archivedisabled'] = 'Archive désactivée';
$string['archiverequired'] = 'Archive requise';
$string['archiveoptional'] = 'Archive optionnelle';
$string['archivecreated'] = 'Archive créée';
$string['archivemissing'] = 'Archive manquante';
$string['archivelinked'] = 'Archive liée';
$string['archivereusable'] = 'Archive réutilisable';
$string['archive_desc'] = 'L’archive conserve preuves, décisions, corrections, versions, apprentissages et Kristals pédagogiques.';
$string['kristal'] = 'Kristal pédagogique';
$string['kristals'] = 'Kristals pédagogiques';
$string['kristal_desc'] = 'Un Kristal stabilise un apprentissage, une décision, une définition ou une preuve réutilisable.';

// Integrity.
$string['integrity'] = 'Intégrité';
$string['integritysection'] = 'Intégrité du cours';
$string['integrityrequired'] = 'Vérification d’intégrité requise';
$string['integrityoptional'] = 'Vérification d’intégrité optionnelle';
$string['integritynotice'] = 'Avis d’intégrité';
$string['integritynotice_desc'] = 'Rappelle les limites UCKK : dignité, vérité, non-manipulation, justice procédurale et contestabilité.';
$string['integrityok'] = 'Aucun problème d’intégrité signalé';
$string['integritypending'] = 'Révision d’intégrité en attente';
$string['integritycaseopen'] = 'Cas d’intégrité ouvert';
$string['integritycaseclosed'] = 'Cas d’intégrité fermé';
$string['integritycorrectionrequired'] = 'Correction d’intégrité requise';
$string['integrityblocked'] = 'Progression bloquée par un cas d’intégrité';
$string['inquisiteur'] = 'Inquisiteur';
$string['inquisiteur_desc'] = 'Fonction de vérification éthique et méthodologique : protéger les faits, la dignité, les règles, les preuves et la confiance collective.';

// AI.
$string['ai'] = 'IA';
$string['aiuse'] = 'Usage de l’IA';
$string['aijournal'] = 'Journal de collaboration IA';
$string['aiwarning'] = 'L’IA aide. Elle ne décide pas.';
$string['aiwarning_desc'] = 'Toute sortie IA doit être vérifiée, située, critiquée et assumée par un humain.';
$string['aiprovenance'] = 'Provenance IA';
$string['aireviewrequired'] = 'Critique de sortie IA requise';
$string['ainonsovereign'] = 'IA non souveraine';
$string['humanvalidationrequired'] = 'Validation humaine requise';

// Completion and progress.
$string['completion'] = 'Achèvement';
$string['completionuckk'] = 'Achèvement UCKK';
$string['completionprogress'] = 'Progression UCKK';
$string['completionnotstarted'] = 'Non commencé';
$string['completioninprogress'] = 'En cours';
$string['completiondone'] = 'Terminé';
$string['completionblocked'] = 'Bloqué';
$string['completionwaitingreview'] = 'En attente de révision';
$string['completionrequiresproof'] = 'Nécessite une preuve';
$string['completionrequiresdeliberation'] = 'Nécessite une délibération';
$string['completionrequiresarchive'] = 'Nécessite une archive';
$string['completionrequiresintegrity'] = 'Nécessite une vérification d’intégrité';
$string['completionrequiresdeliverable'] = 'Nécessite un livrable';
$string['completionrequiresfeedback'] = 'Nécessite une rétroaction';

// Status.
$string['status'] = 'Statut';
$string['status_draft'] = 'Brouillon';
$string['status_active'] = 'Actif';
$string['status_pending'] = 'En attente';
$string['status_pendingreview'] = 'En attente de révision';
$string['status_validated'] = 'Validé';
$string['status_rejected'] = 'Rejeté';
$string['status_correctionrequired'] = 'Correction requise';
$string['status_contested'] = 'Contesté';
$string['status_invalidated'] = 'Invalidé';
$string['status_closed'] = 'Fermé';
$string['status_archived'] = 'Archivé';
$string['status_cancelled'] = 'Annulé';

// Visibility.
$string['visibility'] = 'Visibilité';
$string['visibility_private'] = 'Privé';
$string['visibility_user'] = 'Utilisateur';
$string['visibility_group'] = 'Groupe';
$string['visibility_course'] = 'Cours';
$string['visibility_cohort'] = 'Cohorte';
$string['visibility_institution'] = 'Institution';
$string['visibility_public'] = 'Public';

// Settings.
$string['settings_general'] = 'Réglages généraux du format UCKK';
$string['settings_structure'] = 'Structure du cours';
$string['settings_navigation'] = 'Navigation UCKK';
$string['settings_completion'] = 'Achèvement UCKK';
$string['settings_integrity'] = 'Intégrité';
$string['settings_archive'] = 'Archive';
$string['settings_advanced'] = 'Réglages avancés';

$string['settings_general_desc'] = 'Réglages généraux du format de cours UCKK.';
$string['settings_structure_desc'] = 'Détermine comment les sections canoniques UCKK sont appliquées au cours.';
$string['settings_navigation_desc'] = 'Contrôle l’affichage des éléments de navigation propres au format UCKK.';
$string['settings_completion_desc'] = 'Contrôle l’affichage des indicateurs de progression UCKK.';
$string['settings_integrity_desc'] = 'Contrôle les rappels visuels et pédagogiques liés à l’intégrité.';
$string['settings_archive_desc'] = 'Contrôle les éléments de mémoire et d’archivage.';
$string['settings_advanced_desc'] = 'Réglages destinés aux administrateurs et intégrateurs.';

// Course format options.
$string['coursedisplaymode'] = 'Mode d’affichage UCKK';
$string['coursedisplaymode_desc'] = 'Choisit le style d’affichage principal du cours UCKK.';
$string['showcoursemap'] = 'Afficher la carte du cours';
$string['showcoursemap_desc'] = 'Affiche une carte synthétique des sections UCKK en haut du cours.';
$string['showsectiondescriptions'] = 'Afficher les descriptions des sections';
$string['showsectiondescriptions_desc'] = 'Affiche une courte description pédagogique sous chaque titre de section.';
$string['showintegritynotice'] = 'Afficher l’avis d’intégrité';
$string['showintegritynotice_desc'] = 'Affiche un rappel des limites éthiques UCKK dans le cours.';
$string['showarchivestatus'] = 'Afficher le statut d’archive';
$string['showarchivestatus_desc'] = 'Affiche si les preuves, décisions ou livrables du cours ont été archivés.';
$string['showproofstatus'] = 'Afficher le statut des preuves';
$string['showproofstatus_desc'] = 'Affiche l’état des preuves attendues ou déposées.';
$string['showaigovernance'] = 'Afficher le rappel IA non souveraine';
$string['showaigovernance_desc'] = 'Affiche un rappel indiquant que l’IA est un outil de clarification, non une autorité finale.';
$string['lockcanonicalsections'] = 'Verrouiller les sections canoniques';
$string['lockcanonicalsections_desc'] = 'Empêche la modification automatique des sections UCKK par défaut.';
$string['defaultsectionlayout'] = 'Disposition par défaut des sections';
$string['defaultsectionlayout_desc'] = 'Définit la disposition visuelle des sections UCKK.';

// Section options.
$string['sectiontype'] = 'Type de section UCKK';
$string['sectiontype_desc'] = 'Définit le rôle pédagogique de cette section dans le format UCKK.';
$string['sectionvisibilityrule'] = 'Règle de visibilité de section';
$string['sectionvisibilityrule_desc'] = 'Détermine si la section est visible selon le contexte pédagogique, l’achèvement ou les permissions.';
$string['sectionrequiresproof'] = 'Cette section exige une preuve';
$string['sectionrequiresproof_desc'] = 'Indique que cette section doit conduire à une preuve ou à une trace vérifiable.';
$string['sectionrequiresarchive'] = 'Cette section exige une archive';
$string['sectionrequiresarchive_desc'] = 'Indique que cette section doit produire ou lier un élément d’archive.';
$string['sectionrequiresdeliberation'] = 'Cette section exige une délibération';
$string['sectionrequiresdeliberation_desc'] = 'Indique que cette section doit inclure une discussion structurée ou une assemblée.';
$string['sectionrequiresintegrity'] = 'Cette section exige une vérification d’intégrité';
$string['sectionrequiresintegrity_desc'] = 'Indique que cette section peut nécessiter une révision par l’Inquisiteur.';

// Renderer labels.
$string['renderer_courseheader'] = 'En-tête du cours UCKK';
$string['renderer_coursemap'] = 'Carte du cours UCKK';
$string['renderer_sectionheader'] = 'En-tête de section UCKK';
$string['renderer_sectionfooter'] = 'Pied de section UCKK';
$string['renderer_progressbar'] = 'Barre de progression UCKK';
$string['renderer_archivemarker'] = 'Marqueur d’archive';
$string['renderer_integritymarker'] = 'Marqueur d’intégrité';
$string['renderer_proofmarker'] = 'Marqueur de preuve';
$string['renderer_aimarker'] = 'Marqueur IA';

// Template labels.
$string['template_viewsection'] = 'Voir la section';
$string['template_viewcoursemap'] = 'Voir la carte du cours';
$string['template_hidesectiondetails'] = 'Masquer les détails de la section';
$string['template_showsectiondetails'] = 'Afficher les détails de la section';
$string['template_continue'] = 'Continuer';
$string['template_backtocourse'] = 'Retour au cours';
$string['template_emptysection'] = 'Cette section ne contient pas encore d’activité.';
$string['template_lockedsection'] = 'Cette section est verrouillée.';
$string['template_restrictedsection'] = 'Cette section est restreinte.';
$string['template_archivedsection'] = 'Cette section est archivée.';
$string['template_integritywarning'] = 'Cette section comporte un rappel d’intégrité.';
$string['template_aiwarning'] = 'Cette section peut impliquer l’usage de l’IA sous responsabilité humaine.';

// Notifications.
$string['notice_structuremissing'] = 'La structure UCKK complète n’est pas encore appliquée à ce cours.';
$string['notice_structurepartial'] = 'La structure UCKK est partielle.';
$string['notice_structureready'] = 'La structure UCKK est prête.';
$string['notice_proofmissing'] = 'Une ou plusieurs preuves attendues sont manquantes.';
$string['notice_archivemissing'] = 'Aucun élément d’archive n’est encore lié.';
$string['notice_integritypending'] = 'Une révision d’intégrité est en attente.';
$string['notice_aiused'] = 'Ce cours contient des activités où l’IA peut être utilisée comme outil non souverain.';
$string['notice_internalrecognition'] = 'Ce cours contribue à une reconnaissance interne UCKK.';

// Errors.
$string['error_invalidsectiontype'] = 'Type de section UCKK invalide.';
$string['error_invalidcoursedisplaymode'] = 'Mode d’affichage UCKK invalide.';
$string['error_missingcanonicalsection'] = 'Section canonique manquante : {$a}.';
$string['error_missingcoursecontext'] = 'Contexte de cours introuvable.';
$string['error_cannotapplystructure'] = 'Impossible d’appliquer la structure UCKK à ce cours.';
$string['error_cannotupdatesection'] = 'Impossible de mettre à jour la section UCKK.';
$string['error_cannotreadformatoptions'] = 'Impossible de lire les options du format UCKK.';
$string['error_cannotsaveformatoptions'] = 'Impossible d’enregistrer les options du format UCKK.';

// Capabilities.
$string['uckk:viewcourseformat'] = 'Voir le format de cours UCKK';
$string['uckk:configurecourseformat'] = 'Configurer le format de cours UCKK';
$string['uckk:applydefaultstructure'] = 'Appliquer la structure UCKK par défaut';
$string['uckk:lockcanonicalsections'] = 'Verrouiller les sections canoniques UCKK';
$string['uckk:viewintegritymarkers'] = 'Voir les marqueurs d’intégrité UCKK';
$string['uckk:viewarchivemarkers'] = 'Voir les marqueurs d’archive UCKK';

// Help strings.
$string['help_formatpurpose'] = 'Le format UCKK organise le cours autour du cycle : connaître, choisir, agir, se souvenir.';
$string['help_proof'] = 'Une preuve est une trace vérifiable : texte, fichier, carte, décision, journal IA, artefact, observation ou archive.';
$string['help_archive'] = 'Une archive conserve la mémoire du cours : preuves, décisions, versions, corrections et apprentissages.';
$string['help_integrity'] = 'L’intégrité protège la dignité, la vérité, la non-manipulation, la justice procédurale et la contestabilité.';
$string['help_ai'] = 'L’IA peut aider à clarifier, synthétiser et cartographier. Elle ne remplace pas le jugement humain.';
$string['help_deliberation'] = 'Une délibération structurée rend visibles les arguments, objections, critères et décisions.';
$string['help_deliverable'] = 'Le livrable final doit démontrer une compétence par un artefact utile, vérifiable et archivable.';

// Course creation / seed compatibility.
$string['seed_defaultcoursename'] = 'Cours UCKK';
$string['seed_defaultcourseshortname'] = 'UCKK-COURSE';
$string['seed_defaultsummary'] = 'Cours créé avec le format UCKK.';
$string['seed_structurecreated'] = 'Structure de cours UCKK créée.';
$string['seed_structureupdated'] = 'Structure de cours UCKK mise à jour.';
$string['seed_structureskipped'] = 'Structure de cours UCKK déjà présente.';
$string['seed_sectioncreated'] = 'Section UCKK créée : {$a}.';
$string['seed_sectionupdated'] = 'Section UCKK mise à jour : {$a}.';

// Accessibility.
$string['aria_coursemap'] = 'Carte du cours UCKK';
$string['aria_sectionprogress'] = 'Progression de la section';
$string['aria_sectionstatus'] = 'Statut de la section';
$string['aria_proofstatus'] = 'Statut des preuves';
$string['aria_integritystatus'] = 'Statut d’intégrité';
$string['aria_archivestatus'] = 'Statut d’archive';
$string['aria_cycle'] = 'Cycle UCKK : connaître, choisir, agir, se souvenir';

// Boundary reminders.
$string['boundary_course'] = 'Ce cours appartient au campus pédagogique UCKK.';
$string['boundary_koa'] = 'kOA est le mouvement; UCKK est l’école; le kOA Digital Ecosystem est l’infrastructure.';
$string['boundary_kingklown'] = 'King Klown est une figure narrative de mobilisation, non une autorité académique finale.';
$string['boundary_inquisiteur'] = 'L’Inquisiteur protège la méthode, les faits, la dignité et la contestabilité.';
$string['boundary_archive'] = 'L’Archive conserve la mémoire et les preuves du parcours.';