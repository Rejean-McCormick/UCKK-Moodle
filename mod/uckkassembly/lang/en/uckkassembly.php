<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * French strings for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Assemblée UCKK';
$string['pluginadministration'] = 'Administration de l’Assemblée UCKK';
$string['modulename'] = 'Assemblée UCKK';
$string['modulename_help'] = 'Une Assemblée UCKK est une activité de délibération structurée permettant de proposer des motions, de les amender, de voter, de publier des décisions, de contester des décisions et d’archiver les résultats.';
$string['modulename_link'] = 'mod/uckkassembly/view';
$string['modulenameplural'] = 'Assemblées UCKK';

// Capabilities.
$string['uckkassembly:addinstance'] = 'Ajouter une nouvelle Assemblée UCKK';
$string['uckkassembly:view'] = 'Voir une Assemblée UCKK';
$string['uckkassembly:createassembly'] = 'Créer une Assemblée UCKK';
$string['uckkassembly:proposemotion'] = 'Proposer une motion';
$string['uckkassembly:amendmotion'] = 'Amender une motion';
$string['uckkassembly:vote'] = 'Voter dans une Assemblée UCKK';
$string['uckkassembly:publishdecision'] = 'Publier une décision d’Assemblée';
$string['uckkassembly:contestdecision'] = 'Contester une décision d’Assemblée';
$string['uckkassembly:archive'] = 'Archiver une Assemblée UCKK';

// General labels.
$string['actions'] = 'Actions';
$string['active'] = 'Active';
$string['archive'] = 'Archive';
$string['assembliesincourse'] = 'Assemblées dans {$a}';
$string['assembly'] = 'Assemblée';
$string['assemblycode'] = 'Code de l’assemblée';
$string['assemblycode_help'] = 'Code interne stable de l’assemblée. Utilisez des lettres, des chiffres, des tirets ou des traits de soulignement.';
$string['assemblycontext'] = 'Contexte de l’assemblée';
$string['assemblycontext_help'] = 'Décrivez le contexte pédagogique, institutionnel ou communautaire qui justifie cette assemblée.';
$string['assemblydescription'] = 'Description de l’assemblée';
$string['assemblyname'] = 'Assemblée';
$string['assemblytitle'] = 'Titre de l’assemblée';
$string['assemblytype'] = 'Type d’assemblée';
$string['assemblytype_help'] = 'Le type d’assemblée précise la fonction de délibération : savoirs, défis, joueurs, bâtisseurs, inquisiteurs ou Grand Jeu.';
$string['backtoassembly'] = 'Retour à l’assemblée';
$string['backtocourse'] = 'Retour au cours';
$string['cancel'] = 'Annuler';
$string['close'] = 'Fermer';
$string['confirm'] = 'Confirmer';
$string['continue'] = 'Continuer';
$string['course'] = 'Cours';
$string['createdby'] = 'Créée par';
$string['decision'] = 'Décision';
$string['decisions'] = 'Décisions';
$string['description'] = 'Description';
$string['details'] = 'Détails';
$string['edit'] = 'Modifier';
$string['error'] = 'Erreur';
$string['general'] = 'Général';
$string['hiddenfromstudents'] = 'Masquée aux participants';
$string['instance'] = 'Instance';
$string['invalidaction'] = 'Action invalide.';
$string['invalidassembly'] = 'Assemblée invalide.';
$string['invalidassemblytype'] = 'Type d’assemblée invalide.';
$string['invalidvisibility'] = 'Visibilité invalide.';
$string['loading'] = 'Chargement…';
$string['missingassembly'] = 'Assemblée introuvable.';
$string['modifiedby'] = 'Modifiée par';
$string['motion'] = 'Motion';
$string['motions'] = 'Motions';
$string['name'] = 'Nom';
$string['no'] = 'Non';
$string['none'] = 'Aucun';
$string['notavailable'] = 'Non disponible';
$string['notset'] = 'Non défini';
$string['overview'] = 'Vue d’ensemble';
$string['participants'] = 'Participants';
$string['preview'] = 'Aperçu';
$string['required'] = 'Requis';
$string['save'] = 'Enregistrer';
$string['savechanges'] = 'Enregistrer les modifications';
$string['status'] = 'Statut';
$string['summary'] = 'Résumé';
$string['timecreated'] = 'Date de création';
$string['timemodified'] = 'Date de modification';
$string['unknown'] = 'Inconnu';
$string['view'] = 'Voir';
$string['viewall'] = 'Tout voir';
$string['visibility'] = 'Visibilité';
$string['yes'] = 'Oui';

// Assembly types.
$string['assemblytype:savoirs'] = 'Assemblée des savoirs';
$string['assemblytype:defis'] = 'Assemblée des défis';
$string['assemblytype:joueurs'] = 'Assemblée des joueurs';
$string['assemblytype:batisseurs'] = 'Assemblée des bâtisseurs';
$string['assemblytype:inquisiteurs'] = 'Assemblée des inquisiteurs';
$string['assemblytype:grand_jeu'] = 'Assemblée du Grand Jeu';

// Common statuses.
$string['status:draft'] = 'Brouillon';
$string['status:active'] = 'Active';
$string['status:hidden'] = 'Masquée';
$string['status:pending'] = 'En attente';
$string['status:pending_review'] = 'En attente de revue';
$string['status:validated'] = 'Validée';
$string['status:rejected'] = 'Rejetée';
$string['status:correction_required'] = 'Correction requise';
$string['status:contested'] = 'Contestée';
$string['status:invalidated'] = 'Invalidée';
$string['status:closed'] = 'Fermée';
$string['status:archived'] = 'Archivée';
$string['status:cancelled'] = 'Annulée';

// Visibility.
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

// Form sections.
$string['assemblysettings'] = 'Paramètres de l’assemblée';
$string['deliberationsettings'] = 'Paramètres de délibération';
$string['motionsettings'] = 'Paramètres des motions';
$string['votingsettings'] = 'Paramètres de vote';
$string['decisionsettings'] = 'Paramètres de décision';
$string['integritysettings'] = 'Paramètres d’intégrité';
$string['archivesettings'] = 'Paramètres d’archive';

// Form fields.
$string['agenda'] = 'Ordre du jour';
$string['agenda_help'] = 'Listez les sujets, questions, motions attendues ou points de délibération.';
$string['assemblyintro'] = 'Introduction de l’assemblée';
$string['assemblyrules'] = 'Règles de l’assemblée';
$string['assemblyrules_help'] = 'Définissez les règles de parole, de proposition, d’amendement, de vote, de contestation et d’archivage.';
$string['deliberationguidelines'] = 'Directives de délibération';
$string['deliberationguidelines_help'] = 'Indiquez comment les participants doivent argumenter, citer leurs preuves, traiter les désaccords et respecter la dignité des personnes.';
$string['quorum'] = 'Quorum';
$string['quorum_help'] = 'Nombre minimal ou pourcentage minimal de participants requis pour qu’un vote puisse être considéré comme valide.';
$string['decisionthreshold'] = 'Seuil de décision';
$string['decisionthreshold_help'] = 'Seuil minimal requis pour adopter une motion, publier une décision ou valider une orientation collective.';
$string['allowmotions'] = 'Autoriser les motions';
$string['allowamendments'] = 'Autoriser les amendements';
$string['allowvotes'] = 'Autoriser les votes';
$string['allowcontestation'] = 'Autoriser la contestation';
$string['allowpublicsummary'] = 'Autoriser un résumé public';
$string['requirehumanvalidation'] = 'Exiger une validation humaine';
$string['integrityrequired'] = 'Revue d’intégrité requise';
$string['integrityrequired_help'] = 'Exige une revue d’intégrité avant publication finale, archivage ou reconnaissance institutionnelle.';
$string['archivepolicy'] = 'Politique d’archive';
$string['archivepolicy_help'] = 'Détermine si l’assemblée produit une archive, un résumé d’archive ou un dossier complet.';
$string['publicsummary'] = 'Résumé public';
$string['publicsummary_help'] = 'Résumé visible selon la visibilité configurée. Ne doit pas contenir de données privées ou restreintes.';
$string['privateinstructions'] = 'Instructions privées';
$string['privateinstructions_help'] = 'Instructions visibles seulement aux personnes autorisées. Ne remplace pas les dossiers d’intégrité ou d’archive.';
$string['timeopen'] = 'Date d’ouverture';
$string['timeclose'] = 'Date de fermeture';
$string['timeopen_help'] = 'Moment à partir duquel l’assemblée devient active.';
$string['timeclose_help'] = 'Moment à partir duquel les nouvelles motions, amendements ou votes peuvent être fermés selon la configuration.';

// Archive policies.
$string['archivepolicy:none'] = 'Aucune archive';
$string['archivepolicy:summary'] = 'Archive de synthèse';
$string['archivepolicy:full'] = 'Archive complète';
$string['archivepolicy:restricted_integrity'] = 'Archive restreinte à l’intégrité';

// Index page.
$string['noassemblies'] = 'Aucune assemblée n’est disponible dans ce cours.';
$string['noassembliesvisible'] = 'Aucune assemblée visible ne vous est disponible dans ce cours.';

// View page.
$string['assemblyoverview'] = 'Vue d’ensemble de l’assemblée';
$string['assemblyisclosed'] = 'Cette assemblée est fermée.';
$string['assemblyisnotopen'] = 'Cette assemblée n’est pas encore ouverte.';
$string['assemblyclosednotice'] = 'Cette assemblée est fermée. Les actions disponibles peuvent être limitées.';
$string['assemblynonsovereignnotice'] = 'Les assemblées produisent des traces de délibération. Les décisions publiées, les corrections d’intégrité et les archives doivent rester auditables, contestables et validées humainement.';
$string['viewassembly'] = 'Voir l’assemblée';
$string['editassembly'] = 'Modifier l’assemblée';

// Motions.
$string['motiontitle'] = 'Titre de la motion';
$string['motionbody'] = 'Texte de la motion';
$string['motionbody_help'] = 'Formulez clairement la proposition, son contexte, ses effets attendus, ses preuves et ses limites.';
$string['motiontype'] = 'Type de motion';
$string['motionstatus'] = 'Statut de la motion';
$string['motioncreated'] = 'Motion créée.';
$string['motionupdated'] = 'Motion mise à jour.';
$string['motiondeleted'] = 'Motion supprimée.';
$string['motionpublished'] = 'Motion publiée.';
$string['motionclosed'] = 'Motion fermée.';
$string['motionproposedby'] = 'Proposée par';
$string['motionproposedon'] = 'Proposée le';
$string['motionrequirescontext'] = 'La motion doit inclure un contexte.';
$string['motionrequiresbody'] = 'La motion doit inclure un texte.';
$string['motionrequirescapability'] = 'Vous n’avez pas la permission de proposer une motion.';
$string['proposemotion'] = 'Proposer une motion';
$string['editmotion'] = 'Modifier la motion';
$string['viewmotion'] = 'Voir la motion';
$string['nomotions'] = 'Aucune motion n’a encore été proposée.';
$string['nomotionsvisible'] = 'Aucune motion visible ne vous est disponible.';

// Motion types.
$string['motiontype:information'] = 'Information';
$string['motiontype:recommendation'] = 'Recommandation';
$string['motiontype:validation'] = 'Validation';
$string['motiontype:correction'] = 'Correction';
$string['motiontype:rejection'] = 'Rejet';
$string['motiontype:archival'] = 'Archivage';
$string['motiontype:integrity'] = 'Intégrité';

// Amendments.
$string['amendment'] = 'Amendement';
$string['amendments'] = 'Amendements';
$string['amendmenttitle'] = 'Titre de l’amendement';
$string['amendmentbody'] = 'Texte de l’amendement';
$string['amendmentreason'] = 'Raison de l’amendement';
$string['amendmentcreated'] = 'Amendement créé.';
$string['amendmentupdated'] = 'Amendement mis à jour.';
$string['amendmentaccepted'] = 'Amendement accepté.';
$string['amendmentrejected'] = 'Amendement rejeté.';
$string['amendmotion'] = 'Amender la motion';
$string['noamendments'] = 'Aucun amendement n’a encore été proposé.';
$string['cannotamendmotion'] = 'Vous ne pouvez pas amender cette motion.';

// Objects / objections.
$string['objection'] = 'Objection';
$string['objections'] = 'Objections';
$string['objectmotion'] = 'Déposer une objection';
$string['objectionbody'] = 'Texte de l’objection';
$string['objectionreason'] = 'Raison de l’objection';
$string['objectioncreated'] = 'Objection créée.';
$string['objectionresolved'] = 'Objection résolue.';
$string['noobjections'] = 'Aucune objection n’a été déposée.';

// Votes.
$string['vote'] = 'Vote';
$string['votes'] = 'Votes';
$string['votenow'] = 'Voter';
$string['votechoice'] = 'Choix de vote';
$string['votereason'] = 'Raison du vote';
$string['votereason_help'] = 'Expliquez brièvement le raisonnement derrière votre vote. Cette justification peut être incluse dans les traces de délibération selon la visibilité.';
$string['votesubmitted'] = 'Vote enregistré.';
$string['voteupdated'] = 'Vote mis à jour.';
$string['votecancelled'] = 'Vote annulé.';
$string['votingnotopen'] = 'Le vote n’est pas ouvert.';
$string['alreadyvoted'] = 'Vous avez déjà voté.';
$string['cannotvote'] = 'Vous ne pouvez pas voter dans cette assemblée.';
$string['novotes'] = 'Aucun vote n’a encore été enregistré.';
$string['voteresults'] = 'Résultats du vote';
$string['voteresultsrestricted'] = 'Les résultats détaillés du vote sont restreints.';
$string['voterequireschoice'] = 'Veuillez choisir une option de vote.';

// Vote choices.
$string['votechoice:for'] = 'Pour';
$string['votechoice:against'] = 'Contre';
$string['votechoice:abstain'] = 'Abstention';
$string['votechoice:needs_revision'] = 'Révision requise';
$string['votechoice:block'] = 'Blocage motivé';

// Decisions.
$string['decisiontitle'] = 'Titre de la décision';
$string['decisionbody'] = 'Texte de la décision';
$string['decisiontype'] = 'Type de décision';
$string['decisionstatus'] = 'Statut de la décision';
$string['decisioncreated'] = 'Décision créée.';
$string['decisionupdated'] = 'Décision mise à jour.';
$string['decisionpublished'] = 'Décision publiée.';
$string['decisioncontested'] = 'Décision contestée.';
$string['decisionarchived'] = 'Décision archivée.';
$string['publishdecision'] = 'Publier la décision';
$string['viewdecision'] = 'Voir la décision';
$string['nodecisions'] = 'Aucune décision n’a encore été publiée.';
$string['cannotpublishdecision'] = 'Vous ne pouvez pas publier cette décision.';
$string['decisionrequiresbody'] = 'La décision doit inclure un texte.';
$string['decisionrequiresmotion'] = 'La décision doit être liée à une motion ou à un point de délibération.';

// Decision types.
$string['decisiontype:information'] = 'Information';
$string['decisiontype:recommendation'] = 'Recommandation';
$string['decisiontype:validation'] = 'Validation';
$string['decisiontype:correction'] = 'Correction';
$string['decisiontype:rejection'] = 'Rejet';
$string['decisiontype:archival'] = 'Archivage';
$string['decisiontype:integrity'] = 'Intégrité';

// Minutes.
$string['minutes'] = 'Procès-verbal';
$string['minutesplural'] = 'Procès-verbaux';
$string['minutesbody'] = 'Texte du procès-verbal';
$string['minutescreated'] = 'Procès-verbal créé.';
$string['minutesupdated'] = 'Procès-verbal mis à jour.';
$string['minutespublished'] = 'Procès-verbal publié.';
$string['minutesarchived'] = 'Procès-verbal archivé.';
$string['viewminutes'] = 'Voir le procès-verbal';
$string['editminutes'] = 'Modifier le procès-verbal';
$string['nominutes'] = 'Aucun procès-verbal n’a encore été publié.';

// Contestation.
$string['contest'] = 'Contestation';
$string['contests'] = 'Contestations';
$string['contestdecision'] = 'Contester la décision';
$string['contestreason'] = 'Raison de la contestation';
$string['contestbody'] = 'Texte de la contestation';
$string['contestcreated'] = 'Contestation créée.';
$string['contestupdated'] = 'Contestation mise à jour.';
$string['contestresolved'] = 'Contestation résolue.';
$string['contestdismissed'] = 'Contestation rejetée.';
$string['nocontests'] = 'Aucune contestation n’a été déposée.';
$string['cannotcontestdecision'] = 'Vous ne pouvez pas contester cette décision.';
$string['contestrequiresreason'] = 'La contestation doit inclure une raison.';

// Integrity.
$string['integrity'] = 'Intégrité';
$string['integritycase'] = 'Dossier d’intégrité';
$string['integritycases'] = 'Dossiers d’intégrité';
$string['integritynotice'] = 'Avis d’intégrité';
$string['integrityreview'] = 'Revue d’intégrité';
$string['integritystate'] = 'État d’intégrité';
$string['integritysummary'] = 'Synthèse d’intégrité';
$string['integritywarnings'] = 'Avertissements d’intégrité';
$string['openintegritycase'] = 'Ouvrir un dossier d’intégrité';
$string['integritycaseopened'] = 'Dossier d’intégrité ouvert.';
$string['cannotopenintegritycase'] = 'Vous ne pouvez pas ouvrir un dossier d’intégrité pour cette assemblée.';
$string['integritynonsovereignnotice'] = 'Les actions d’intégrité sont des traces procédurales. Elles doivent rester auditables, contestables et soumises à validation humaine.';

// Integrity states.
$string['integritystate:unverified'] = 'Non vérifiée';
$string['integritystate:human_reviewed'] = 'Revue humainement';
$string['integritystate:verified'] = 'Vérifiée';
$string['integritystate:contested'] = 'Contestée';
$string['integritystate:invalidated'] = 'Invalidée';
$string['integritystate:archived'] = 'Archivée';

// Archive.
$string['archiveassembly'] = 'Archiver l’assemblée';
$string['archiveassemblyintro'] = 'Créer une archive de cette assemblée, incluant les motions, amendements, objections, votes, décisions, procès-verbaux, contestations, provenance et synthèse autorisée.';
$string['archiveassemblyconfirm'] = 'Archiver l’assemblée';
$string['assemblyarchived'] = 'Assemblée archivée.';
$string['cannotarchiveassembly'] = 'Vous ne pouvez pas archiver cette assemblée.';
$string['archivevisibility'] = 'Visibilité de l’archive';
$string['archivereason'] = 'Raison de l’archivage';
$string['archivewarnings'] = 'Avertissements d’archive';
$string['archiveitemcreated'] = 'Élément d’archive créé.';
$string['archiveempty'] = 'Aucune archive n’a encore été créée pour cette assemblée.';

// Provenance.
$string['provenance'] = 'Provenance';
$string['provenance:human'] = 'Humaine';
$string['provenance:ai_assisted'] = 'Assistée par IA';
$string['provenance:imported'] = 'Importée';
$string['provenance:system'] = 'Système';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assemblée';
$string['provenance:challenge'] = 'Défi';
$string['provenance:integrity'] = 'Intégrité';
$string['provenancestatement'] = 'Déclaration de provenance';
$string['provenancestatement_help'] = 'Expliquez l’origine de l’information, les transformations effectuées et les éléments vérifiables.';

// AI policy.
$string['aiassisted'] = 'Assisté par IA';
$string['ailog'] = 'Journal de collaboration IA';
$string['aipolicy'] = 'Politique IA';
$string['aipolicynotice'] = 'L’IA peut aider à résumer, comparer, cartographier ou préparer une délibération. Elle ne peut pas publier une décision, valider l’intégrité, fermer un dossier, attribuer une reconnaissance ou remplacer la revue humaine.';
$string['aioutputlabel'] = 'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.';

// Events.
$string['eventassemblyviewed'] = 'Assemblée consultée';
$string['eventmotioncreated'] = 'Motion créée';
$string['eventmotionupdated'] = 'Motion mise à jour';
$string['eventamendmentcreated'] = 'Amendement créé';
$string['eventvotecreated'] = 'Vote enregistré';
$string['eventdecisionpublished'] = 'Décision publiée';
$string['eventdecisioncontested'] = 'Décision contestée';
$string['eventminutespublished'] = 'Procès-verbal publié';
$string['eventassemblyarchived'] = 'Assemblée archivée';
$string['eventintegritycaseopened'] = 'Dossier d’intégrité ouvert';

// External services / AJAX.
$string['ajaxerror'] = 'Erreur lors du traitement de la requête.';
$string['refresh'] = 'Actualiser';
$string['refreshing'] = 'Actualisation…';
$string['refreshed'] = 'Actualisé.';
$string['refreshfailed'] = 'Impossible d’actualiser les données.';
$string['saving'] = 'Enregistrement…';
$string['saved'] = 'Enregistré.';
$string['savefailed'] = 'Impossible d’enregistrer.';
$string['submitting'] = 'Soumission…';
$string['submitted'] = 'Soumis.';
$string['submitfailed'] = 'Impossible de soumettre.';

// Privacy.
$string['privacy:metadata'] = 'Le module Assemblée UCKK stocke des données liées aux assemblées, motions, amendements, objections, votes, décisions, procès-verbaux, contestations, provenance et archives selon les permissions du contexte.';
$string['privacy:metadata:uckkassembly'] = 'Informations principales de l’assemblée.';
$string['privacy:metadata:uckkassembly_motion'] = 'Motions proposées dans une assemblée.';
$string['privacy:metadata:uckkassembly_amend'] = 'Amendements proposés sur les motions.';
$string['privacy:metadata:uckkassembly_object'] = 'Objections ou oppositions déposées dans une assemblée.';
$string['privacy:metadata:uckkassembly_vote'] = 'Votes enregistrés dans une assemblée.';
$string['privacy:metadata:uckkassembly_decision'] = 'Décisions publiées par une assemblée.';
$string['privacy:metadata:uckkassembly_minutes'] = 'Procès-verbaux et synthèses de délibération.';
$string['privacy:metadata:uckkassembly_contest'] = 'Contestations déposées contre des décisions.';
$string['privacy:metadata:userid'] = 'Identifiant de l’utilisateur concerné.';
$string['privacy:metadata:createdby'] = 'Identifiant de l’utilisateur ayant créé l’enregistrement.';
$string['privacy:metadata:modifiedby'] = 'Identifiant de l’utilisateur ayant modifié l’enregistrement.';
$string['privacy:metadata:timecreated'] = 'Date de création.';
$string['privacy:metadata:timemodified'] = 'Date de modification.';
$string['privacy:metadata:visibility'] = 'Visibilité de l’enregistrement.';
$string['privacy:metadata:status'] = 'Statut de l’enregistrement.';
$string['privacy:metadata:metadata'] = 'Métadonnées JSON liées à l’enregistrement.';

// Completion.
$string['completionparticipate'] = 'Participer à l’assemblée';
$string['completionparticipate_desc'] = 'Le participant doit déposer une motion, un amendement, une objection ou un vote.';
$string['completionvote'] = 'Voter dans l’assemblée';
$string['completionvote_desc'] = 'Le participant doit enregistrer un vote.';
$string['completiondecisionviewed'] = 'Voir une décision publiée';
$string['completiondecisionviewed_desc'] = 'Le participant doit consulter une décision publiée.';

// Validation errors.
$string['err_requiredtitle'] = 'Le titre est requis.';
$string['err_requiredbody'] = 'Le texte est requis.';
$string['err_requiredsummary'] = 'Le résumé est requis.';
$string['err_requiredreason'] = 'La raison est requise.';
$string['err_invalidstatus'] = 'Statut invalide.';
$string['err_invalidtype'] = 'Type invalide.';
$string['err_invalidvisibility'] = 'Visibilité invalide.';
$string['err_timeclosebeforeopen'] = 'La date de fermeture doit être postérieure à la date d’ouverture.';
$string['err_quorumnegative'] = 'Le quorum ne peut pas être négatif.';
$string['err_thresholdrange'] = 'Le seuil de décision doit être compris entre 0 et 100.';
