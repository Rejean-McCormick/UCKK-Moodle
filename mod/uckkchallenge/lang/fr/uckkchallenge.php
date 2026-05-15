<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Défis King Klown';
$string['modulename'] = 'Défi King Klown';
$string['modulenameplural'] = 'Défis King Klown';
$string['pluginadministration'] = 'Administration des Défis King Klown';
$string['uckkchallenge'] = 'Défi King Klown';
$string['uckkchallenge:addinstance'] = 'Ajouter un nouveau Défi King Klown';
$string['uckkchallenge:view'] = 'Voir un Défi King Klown';
$string['uckkchallenge:createchallenge'] = 'Créer et configurer un Défi King Klown';
$string['uckkchallenge:submitproof'] = 'Soumettre une preuve à un Défi King Klown';
$string['uckkchallenge:evaluate'] = 'Évaluer les soumissions d’un Défi King Klown';
$string['uckkchallenge:validateintegrity'] = 'Valider l’intégrité d’un Défi King Klown';
$string['uckkchallenge:archive'] = 'Archiver un Défi King Klown';

// General UI.
$string['challenge'] = 'Défi';
$string['challenges'] = 'Défis';
$string['challengeidentity'] = 'Identité du défi';
$string['challengestatement'] = 'Énoncé du défi';
$string['challengecontext'] = 'Contexte du défi';
$string['challengerules'] = 'Règles du défi';
$string['challengecorridors'] = 'Corridors d’action';
$string['challengeevidence'] = 'Preuves attendues';
$string['challengeevaluation'] = 'Évaluation du défi';
$string['challengeintegrity'] = 'Intégrité du défi';
$string['challengearchive'] = 'Archive du défi';
$string['challengeparticipants'] = 'Participants';
$string['challengetimeline'] = 'Calendrier du défi';
$string['challengefeedback'] = 'Rétroaction du défi';
$string['challengenotavailable'] = 'Ce défi n’est pas disponible.';
$string['challengeoverdue'] = 'Défi en retard';
$string['recentchallengeactivity'] = 'Activité récente des défis';
$string['restrictedintegrity'] = 'Ce défi est restreint pour examen d’intégrité.';
$string['kingklownnotice'] = 'King Klown attire l’attention; il ne remplace pas la validation humaine.';
$string['nonauthoritynotice'] = 'Ce défi soutient l’apprentissage et la gouvernance UCKK. Il ne constitue pas une autorité finale sans validation humaine.';

// Form fields.
$string['name'] = 'Nom du défi';
$string['name_help'] = 'Nom affiché pour ce Défi King Klown.';
$string['challenge_type'] = 'Type de défi';
$string['challenge_type_help'] = 'Type canonique du défi selon la pédagogie UCKK.';
$string['statement'] = 'Énoncé';
$string['statement_help'] = 'Décrivez clairement ce que le défi demande de produire, démontrer ou transformer.';
$string['contexttext'] = 'Contexte';
$string['contexttext_help'] = 'Expliquez le contexte social, pédagogique, institutionnel ou technique du défi.';
$string['expectedoutput'] = 'Production attendue';
$string['expectedoutput_help'] = 'Décrivez les livrables, preuves ou artefacts attendus.';
$string['evaluationcriteria'] = 'Critères d’évaluation';
$string['evaluationcriteria_help'] = 'Décrivez les critères de validation, de qualité, d’utilité réelle et de preuve.';
$string['ethicalconstraints'] = 'Contraintes éthiques';
$string['ethicalconstraints_help'] = 'Décrivez les limites de dignité, sécurité, consentement, non-harcèlement, clarté fiction/fait, et usage responsable de l’IA.';
$string['opensat'] = 'Ouverture';
$string['duedate'] = 'Date limite';
$string['closesat'] = 'Fermeture';
$string['allowsubmissionsfromdate'] = 'Autoriser les soumissions à partir du';
$string['cutoffdate'] = 'Date limite stricte';
$string['submissionmode'] = 'Mode de soumission';
$string['maxsubmissions'] = 'Nombre maximal de soumissions';
$string['requireintegrityreview'] = 'Exiger une validation d’intégrité';
$string['requireintegrityreview_help'] = 'Lorsque cette option est activée, le défi doit passer par une validation d’intégrité avant la validation finale.';
$string['allowpublicsummary'] = 'Autoriser un résumé public';
$string['allowpublicsummary_help'] = 'Autorise la publication d’un résumé public filtré, sans exposer les données restreintes ou personnelles.';
$string['archiveonvalidation'] = 'Archiver après validation';
$string['archiveonvalidation_help'] = 'Crée ou prépare une sortie d’archive lorsque le défi est validé.';
$string['grade'] = 'Note maximale';
$string['metadata'] = 'Métadonnées';
$string['metadata_help'] = 'Métadonnées JSON facultatives pour des informations variables qui ne justifient pas une colonne dédiée.';

// Challenge types.
$string['type:internal_learning'] = 'Apprentissage interne';
$string['type:public_pedagogical'] = 'Pédagogie publique';
$string['type:institutional_audit'] = 'Audit institutionnel';
$string['type:system_mapping'] = 'Cartographie de système';
$string['type:prototype'] = 'Prototype';
$string['type:mobilisation'] = 'Mobilisation';
$string['type:capstone'] = 'Défi synthèse';
$string['type:king_klown_public'] = 'King Klown public';

// Submission modes.
$string['submissionmode:individual'] = 'Individuelle';
$string['submissionmode:group'] = 'En groupe';
$string['submissionmode:assembly'] = 'Par assemblée';
$string['submissionmode:portfolio'] = 'Par portfolio';

// Rules and corridors.
$string['rule'] = 'Règle';
$string['rules'] = 'Règles';
$string['addrule'] = 'Ajouter une règle';
$string['rulename'] = 'Nom de la règle';
$string['ruletext'] = 'Texte de la règle';
$string['corridor'] = 'Corridor d’action';
$string['corridors'] = 'Corridors d’action';
$string['addcorridor'] = 'Ajouter un corridor d’action';
$string['corridorname'] = 'Nom du corridor';
$string['corridortext'] = 'Texte du corridor';

// Statuses.
$string['status'] = 'Statut';
$string['status:draft'] = 'Brouillon';
$string['status:published'] = 'Publié';
$string['status:open'] = 'Ouvert';
$string['status:submitted'] = 'Soumis';
$string['status:under_review'] = 'En évaluation';
$string['status:integrity_review'] = 'En examen d’intégrité';
$string['status:revision_required'] = 'Correction requise';
$string['status:resubmitted'] = 'Resoumis';
$string['status:validated'] = 'Validé';
$string['status:archived'] = 'Archivé';
$string['status:closed'] = 'Fermé';
$string['status:contested'] = 'Contesté';
$string['status:invalidated'] = 'Invalidé';
$string['status:withdrawn'] = 'Retiré';
$string['status:expired'] = 'Expiré';
$string['status:hidden'] = 'Masqué';
$string['status:cancelled'] = 'Annulé';

// Visibility.
$string['visibility'] = 'Visibilité';
$string['visibility:private'] = 'Privée';
$string['visibility:user'] = 'Utilisateur';
$string['visibility:group'] = 'Groupe';
$string['visibility:course'] = 'Cours';
$string['visibility:cohort'] = 'Cohorte';
$string['visibility:program'] = 'Programme';
$string['visibility:institution'] = 'Institution';
$string['visibility:public'] = 'Publique';
$string['visibility:restricted'] = 'Restreinte';
$string['visibility:restricted_integrity'] = 'Restreinte — intégrité';
$string['visibility:hidden'] = 'Masquée';
$string['visibility:archived'] = 'Archivée';

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

// Evidence and proof.
$string['proof'] = 'Preuve';
$string['proofs'] = 'Preuves';
$string['submitproof'] = 'Soumettre une preuve';
$string['submission'] = 'Soumission';
$string['submissions'] = 'Soumissions';
$string['nosubmission'] = 'Aucune soumission n’a été enregistrée.';
$string['submissionstatusx'] = 'Statut de la soumission : {$a}';
$string['evidencerequirements'] = 'Exigences de preuve';
$string['evidencerequirements_help'] = 'Chaque preuve importante doit indiquer sa source, son auteur, sa date, sa visibilité, sa relation aux critères, sa provenance et son état d’intégrité.';
$string['proofsource'] = 'Source de la preuve';
$string['proofauthor'] = 'Auteur de la preuve';
$string['proofdate'] = 'Date de la preuve';
$string['proofrelation'] = 'Relation aux critères';
$string['proofintegritystate'] = 'État d’intégrité de la preuve';
$string['proof:text'] = 'Texte';
$string['proof:file'] = 'Fichier';
$string['proof:url'] = 'URL';
$string['proof:dataset'] = 'Jeu de données';
$string['proof:image'] = 'Image';
$string['proof:video'] = 'Vidéo';
$string['proof:testimony'] = 'Témoignage';
$string['proof:observation'] = 'Observation';
$string['proof:ai_log'] = 'Journal IA';
$string['proof:decision_record'] = 'Compte rendu de décision';

// Evaluation.
$string['evaluation'] = 'Évaluation';
$string['evaluations'] = 'Évaluations';
$string['evaluate'] = 'Évaluer';
$string['mentorfeedback'] = 'Rétroaction du mentor';
$string['privatefeedback'] = 'Rétroaction privée';
$string['publicsummary'] = 'Résumé public';
$string['competencyrating'] = 'Évaluation de compétence';
$string['badgetrigger'] = 'Déclencheur de badge';
$string['integrityvalidation'] = 'Validation d’intégrité';
$string['archiveexport'] = 'Sortie d’archive';
$string['validatedbyhuman'] = 'Validé par une personne humaine';
$string['aicannotvalidate'] = 'L’IA peut assister l’analyse, mais elle ne peut pas valider ce défi.';

// Completion.
$string['completionrequiresubmission'] = 'L’étudiant doit soumettre une preuve';
$string['completionrequiresubmission_desc'] = 'Le défi est marqué comme terminé lorsque l’étudiant a soumis au moins une preuve.';
$string['completionrequirevalidation'] = 'La soumission doit être validée';
$string['completionrequirevalidation_desc'] = 'Le défi est marqué comme terminé lorsque la soumission de l’étudiant a été validée.';
$string['completiondetail:submission'] = 'Soumettre une preuve';
$string['completiondetail:validation'] = 'Obtenir une validation';

// Reset.
$string['reset:submissions'] = 'Supprimer les soumissions et les preuves des défis';
$string['reset:evaluations'] = 'Supprimer les évaluations des défis';
$string['reset:states'] = 'Supprimer l’historique des états des défis';

// Events.
$string['eventchallengecreated'] = 'Défi créé';
$string['eventchallengeupdated'] = 'Défi mis à jour';
$string['eventchallengedeleted'] = 'Défi supprimé';
$string['eventchallengeviewed'] = 'Défi consulté';
$string['eventsubmissioncreated'] = 'Soumission créée';
$string['eventsubmissionupdated'] = 'Soumission mise à jour';
$string['eventproofsubmitted'] = 'Preuve soumise';
$string['eventevaluationcreated'] = 'Évaluation créée';
$string['eventintegrityreviewrequested'] = 'Examen d’intégrité demandé';
$string['eventchallengevalidated'] = 'Défi validé';
$string['eventchallengearchived'] = 'Défi archivé';
$string['eventchallengecontested'] = 'Défi contesté';

// State history.
$string['statehistory'] = 'Historique des états';
$string['statechangedbyform'] = 'État enregistré depuis la mise à jour du formulaire d’activité.';
$string['fromstatus'] = 'Statut précédent';
$string['tostatus'] = 'Nouveau statut';
$string['statenote'] = 'Note d’état';

// Anti-abuse and integrity warnings.
$string['antiabuse'] = 'Garde-fous anti-abus';
$string['antiabuse_help'] = 'Un défi ne doit pas permettre le harcèlement ciblé, l’humiliation, la confusion entre fiction et fait, les preuves fabriquées, l’intimidation coordonnée, le doxxing, la pression publique illimitée ou la décision automatisée par IA.';
$string['dignitynotice'] = 'Le spectacle est permis. L’abus ne l’est pas.';
$string['integritynotice'] = 'Même le Roi-Clown peut être interrogé.';
$string['fictionfactnotice'] = 'Les éléments narratifs doivent rester distinguables des faits vérifiables.';
$string['humanvalidationrequired'] = 'Validation humaine requise.';
$string['restrictedintegritynotice'] = 'Certaines informations sont réservées aux personnes autorisées à traiter l’intégrité.';

// AI policy.
$string['aiassistancenotice'] = 'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.';
$string['ailogrequired'] = 'Les prompts et sorties IA pertinents doivent être journalisés.';
$string['aiprovenancerequired'] = 'Toute contribution IA significative doit être déclarée dans la provenance.';
$string['aidecisionforbidden'] = 'L’IA aide. Elle ne décide pas.';

// Archive.
$string['archive'] = 'Archive';
$string['archivechallenge'] = 'Archiver le défi';
$string['archiveonvalidationlabel'] = 'Archiver après validation';
$string['archivesummary'] = 'Résumé d’archive';
$string['archivecreated'] = 'Sortie d’archive créée.';
$string['archivenotcreated'] = 'Aucune sortie d’archive n’a été créée.';

// Errors.
$string['error:cannotview'] = 'Vous n’avez pas la permission de voir ce défi.';
$string['error:cannotsubmit'] = 'Vous n’avez pas la permission de soumettre une preuve pour ce défi.';
$string['error:cannotevaluate'] = 'Vous n’avez pas la permission d’évaluer ce défi.';
$string['error:cannotvalidateintegrity'] = 'Vous n’avez pas la permission de valider l’intégrité de ce défi.';
$string['error:cannotarchive'] = 'Vous n’avez pas la permission d’archiver ce défi.';
$string['error:invalidstatus'] = 'Statut de défi invalide.';
$string['error:invalidvisibility'] = 'Visibilité de défi invalide.';
$string['error:invalidprovenance'] = 'Provenance de défi invalide.';
$string['error:missingchallenge'] = 'Défi introuvable.';
$string['error:missingcontext'] = 'Contexte du défi introuvable.';
$string['error:duedatebeforeopen'] = 'La date limite ne peut pas précéder l’ouverture du défi.';
$string['error:closebeforedue'] = 'La fermeture ne peut pas précéder la date limite.';
$string['error:cutoffbeforeallow'] = 'La date limite stricte ne peut pas précéder le début autorisé des soumissions.';

// Privacy.
$string['privacy:metadata'] = 'Le module Défis King Klown stocke des données liées aux défis, aux soumissions, aux preuves, aux évaluations, aux états de validation, à la provenance, à la visibilité et aux traces d’intégrité.';
$string['privacy:metadata:uckkchallenge_sub'] = 'Informations sur les soumissions des utilisateurs aux défis.';
$string['privacy:metadata:uckkchallenge_sub:userid'] = 'Identifiant de l’utilisateur qui a soumis la réponse.';
$string['privacy:metadata:uckkchallenge_sub:challengeid'] = 'Identifiant du défi associé.';
$string['privacy:metadata:uckkchallenge_sub:status'] = 'Statut de la soumission.';
$string['privacy:metadata:uckkchallenge_sub:metadata'] = 'Métadonnées variables de la soumission.';
$string['privacy:metadata:uckkchallenge_proof'] = 'Informations sur les preuves associées aux soumissions.';
$string['privacy:metadata:uckkchallenge_proof:userid'] = 'Identifiant de l’utilisateur associé à la preuve.';
$string['privacy:metadata:uckkchallenge_proof:visibility'] = 'Visibilité de la preuve.';
$string['privacy:metadata:uckkchallenge_proof:provenance'] = 'Provenance de la preuve.';
$string['privacy:metadata:uckkchallenge_eval'] = 'Informations sur les évaluations et validations des défis.';
$string['privacy:metadata:uckkchallenge_eval:userid'] = 'Identifiant de l’utilisateur évalué.';
$string['privacy:metadata:uckkchallenge_eval:createdby'] = 'Identifiant de la personne ayant créé l’évaluation.';
$string['privacy:metadata:uckkchallenge_eval:grade'] = 'Note attribuée, si applicable.';
$string['privacy:metadata:uckkchallenge_eval:status'] = 'Statut de l’évaluation.';
$string['privacy:metadata:uckkchallenge_state'] = 'Historique des états et transitions du défi.';
$string['privacy:metadata:uckkchallenge_state:userid'] = 'Utilisateur concerné par la transition, si applicable.';
$string['privacy:metadata:uckkchallenge_state:createdby'] = 'Utilisateur ayant enregistré la transition.';
$string['privacy:metadata:uckkchallenge_state:fromstatus'] = 'Statut précédent.';
$string['privacy:metadata:uckkchallenge_state:tostatus'] = 'Nouveau statut.';

// File areas.
$string['filearea:statement_files'] = 'Fichiers de l’énoncé';
$string['filearea:rule_files'] = 'Fichiers des règles';
$string['filearea:evidence_requirement_files'] = 'Fichiers des exigences de preuve';
$string['filearea:submission_files'] = 'Fichiers de soumission';
$string['filearea:proof_files'] = 'Fichiers de preuve';
$string['filearea:feedback_files'] = 'Fichiers de rétroaction';
$string['filearea:archive_export_files'] = 'Fichiers d’export d’archive';

// Additional challenge identity and governance strings.
$string['backtochallenge'] = 'Retour au défi';
$string['backtoevaluationlist'] = 'Retour à la liste d’évaluation';
$string['challengearchived'] = 'Défi archivé.';
$string['challengecode'] = 'Code du défi';
$string['challengecontested'] = 'Défi contesté.';
$string['challengedesign'] = 'Conception du défi';
$string['challengeintegritystate'] = 'État d’intégrité du défi';
$string['challengeintegritysummary'] = 'Résumé d’intégrité du défi';
$string['challengeintro'] = 'Introduction du défi';
$string['challengeinvalidated'] = 'Défi invalidé.';
$string['challengename'] = 'Nom du défi';
$string['challengestatus'] = 'Statut du défi';
$string['challengetype'] = 'Type de défi';
$string['challengetype:capstone'] = 'Défi de synthèse';
$string['challengetype:institutional_audit'] = 'Audit institutionnel';
$string['challengetype:internal_learning'] = 'Apprentissage interne';
$string['challengetype:king_klown_public'] = 'King Klown public';
$string['challengetype:mobilisation'] = 'Mobilisation';
$string['challengetype:prototype'] = 'Prototype';
$string['challengetype:public_pedagogical'] = 'Pédagogique public';
$string['challengetype:system_mapping'] = 'Cartographie systémique';
$string['confirmarchivechallenge'] = 'Confirmer l’archivage du défi';
$string['confirmation'] = 'Confirmation';
$string['confirmhumanreview'] = 'Confirmer la revue humaine';
$string['confirmhumanreview_desc'] = 'Confirme qu’une personne autorisée a relu la preuve, la provenance, l’évaluation et les risques d’intégrité avant validation.';
$string['confirmnodeletion'] = 'Confirmer qu’aucune donnée non autorisée ne sera supprimée';
$string['defaultaipolicy'] = 'L’IA peut assister la réflexion et la préparation, mais toute preuve, évaluation, validation, reconnaissance ou décision d’intégrité doit rester humaine, traçable et contestable.';
$string['emptychallengeview'] = 'Aucune information de défi à afficher.';
$string['governance'] = 'Gouvernance';
$string['notes'] = 'Notes';
$string['savechallenge'] = 'Enregistrer le défi';
$string['summary'] = 'Résumé';

// Additional archive and recognition strings.
$string['archiveandrecognition'] = 'Archive et reconnaissance';
$string['archivechallengeintro'] = 'Créer une archive du défi, de ses preuves, évaluations, décisions, corrections, provenance et résumés autorisés.';
$string['archiveevaluationcount'] = 'Nombre d’évaluations archivées';
$string['archivepolicy'] = 'Politique d’archive';
$string['archivepolicy:full'] = 'Archive complète';
$string['archivepolicy:none'] = 'Aucune archive';
$string['archivepolicy:summary'] = 'Archive de synthèse';
$string['archiveproofcount'] = 'Nombre de preuves archivées';
$string['archivereason'] = 'Raison de l’archivage';
$string['archivesubmissioncount'] = 'Nombre de soumissions archivées';
$string['archivevisibility'] = 'Visibilité de l’archive';
$string['archivewarnings'] = 'Avertissements d’archive';
$string['badgelinks'] = 'Liens de badges';
$string['competencylinks'] = 'Liens de compétences';
$string['requestarchiveexport'] = 'Demander l’export d’archive';
$string['requestarchiveexport_desc'] = 'Permet de demander un export d’archive pour les preuves, évaluations et validations autorisées.';

// Additional proof and submission strings.
$string['cannotsubmitproof'] = 'Vous ne pouvez pas soumettre une preuve pour ce défi.';
$string['evidenceandevaluation'] = 'Preuve et évaluation';
$string['evidencelinks'] = 'Liens de preuve';
$string['evidencepolicy'] = 'Politique de preuve';
$string['prooffiles'] = 'Fichiers de preuve';
$string['prooflist'] = 'Liste des preuves';
$string['proofrequirescontent'] = 'La preuve doit contenir un texte, un fichier ou une URL.';
$string['proofsubmission'] = 'Soumission de preuve';
$string['proofsubmitted'] = 'Preuve soumise.';
$string['proofsummary'] = 'Résumé de la preuve';
$string['prooftext'] = 'Texte de la preuve';
$string['prooftype'] = 'Type de preuve';
$string['prooftype:ai_log'] = 'Journal IA';
$string['prooftype:dataset'] = 'Jeu de données';
$string['prooftype:decision_record'] = 'Registre de décision';
$string['prooftype:file'] = 'Fichier';
$string['prooftype:image'] = 'Image';
$string['prooftype:observation'] = 'Observation';
$string['prooftype:testimony'] = 'Témoignage';
$string['prooftype:text'] = 'Texte';
$string['prooftype:url'] = 'URL';
$string['prooftype:video'] = 'Vidéo';
$string['proofurl'] = 'URL de preuve';
$string['relationtocriteria'] = 'Lien avec les critères';
$string['sourceauthor'] = 'Auteur de la source';
$string['sourcedate'] = 'Date de la source';
$string['submissionbody'] = 'Texte de la soumission';
$string['submissiondraftsaved'] = 'Brouillon de soumission enregistré.';
$string['submissionintegritynotice'] = 'Les soumissions doivent rester vérifiables, respectueuses, traçables et soumises aux règles d’intégrité UCKK.';
$string['submissionrequiresproof'] = 'La soumission exige une preuve.';
$string['submissiontext'] = 'Texte de soumission';
$string['submissiontitle'] = 'Titre de soumission';
$string['submissionurl'] = 'URL de soumission';
$string['submitchallengeproof'] = 'Soumettre une preuve de défi';
$string['submitter'] = 'Soumissionnaire';
$string['teamsubmissions'] = 'Soumissions d’équipe';

// Additional evaluation strings.
$string['evaluatechallenge'] = 'Évaluer le défi';
$string['evaluatelistintro'] = 'Liste des soumissions et preuves à évaluer pour ce défi.';
$string['evaluatesubmission'] = 'Évaluer la soumission';
$string['evaluation:none'] = 'Aucune évaluation';
$string['evaluationdecision'] = 'Décision d’évaluation';
$string['evaluationnonsovereignnotice'] = 'L’évaluation soutient la validation pédagogique. Elle ne contourne pas la preuve, les permissions, l’intégrité ni la revue humaine requise.';
$string['evaluationpanel'] = 'Panneau d’évaluation';
$string['evaluationsaved'] = 'Évaluation enregistrée.';
$string['feedbackrequired'] = 'La rétroaction est requise.';
$string['gradeoutofrange'] = 'La note est hors limites.';
$string['recommendedchallengestatus'] = 'Statut recommandé du défi';
$string['scoreoutof'] = 'Note sur {$a}';

// Additional integrity and Inquisiteur strings.
$string['caseid'] = 'Identifiant du dossier';
$string['casetype'] = 'Type de dossier';
$string['casetype:ai_misuse'] = 'Mauvais usage de l’IA';
$string['casetype:assessment_dispute'] = 'Contestation d’évaluation';
$string['casetype:authority_capture'] = 'Capture d’autorité';
$string['casetype:challenge_dispute'] = 'Contestation du défi';
$string['casetype:dignity_violation'] = 'Atteinte à la dignité';
$string['casetype:fiction_fact_confusion'] = 'Confusion fiction / faits';
$string['casetype:harassment_or_humiliation'] = 'Harcèlement ou humiliation';
$string['casetype:proof_quality'] = 'Qualité de preuve';
$string['closeintegritycase'] = 'Fermer le dossier d’intégrité';
$string['correctiondue'] = 'Échéance de correction';
$string['correctioninstructions'] = 'Instructions de correction';
$string['correctionissued'] = 'Correction demandée.';
$string['correctionrequest'] = 'Demande de correction';
$string['inquisiteuractions'] = 'Actions de l’Inquisiteur';
$string['integrityaction:close'] = 'Fermer';
$string['integrityaction:contest'] = 'Contester';
$string['integrityaction:correction'] = 'Demander une correction';
$string['integrityaction:invalidate'] = 'Invalider';
$string['integrityaction:open'] = 'Ouvrir';
$string['integrityaction:review'] = 'Réviser';
$string['integrityactions'] = 'Actions d’intégrité';
$string['integrityactions:none'] = 'Aucune action d’intégrité';
$string['integritycase'] = 'Dossier d’intégrité';
$string['integritycaseclosed'] = 'Dossier d’intégrité fermé.';
$string['integritycaseopened'] = 'Dossier d’intégrité ouvert.';
$string['integritycases'] = 'Dossiers d’intégrité';
$string['integritycases:none'] = 'Aucun dossier d’intégrité';
$string['integritydecision'] = 'Décision d’intégrité';
$string['integritynonsovereignnotice'] = 'Les actions d’intégrité documentent une revue procédurale. Elles doivent rester auditables, contestables et validées humainement.';
$string['integritynotes'] = 'Notes d’intégrité';
$string['integritypanel'] = 'Panneau d’intégrité';
$string['integritystate'] = 'État d’intégrité';
$string['integritystate:archived'] = 'Archivé';
$string['integritystate:contested'] = 'Contesté';
$string['integritystate:human_reviewed'] = 'Revu humainement';
$string['integritystate:invalidated'] = 'Invalidé';
$string['integritystate:unverified'] = 'Non vérifié';
$string['integritystate:verified'] = 'Vérifié';
$string['integritywarnings'] = 'Avertissements d’intégrité';
$string['invalidatechallenge'] = 'Invalider le défi';
$string['invalidationandclosure'] = 'Invalidation et clôture';
$string['invalidchallengecode'] = 'Code de défi invalide.';
$string['invalidintegrityaction'] = 'Action d’intégrité invalide.';
$string['invalidintegritytransition'] = 'Transition d’intégrité invalide.';
$string['invalidjsonorlist'] = 'JSON ou liste invalide.';
$string['invalidprooftype'] = 'Type de preuve invalide.';
$string['invalidvisibility'] = 'Visibilité invalide.';
$string['issuecorrection'] = 'Demander une correction';
$string['openintegritycases'] = 'Dossiers d’intégrité ouverts';
$string['pausevalidation'] = 'Suspendre la validation';
$string['submitintegrityreview'] = 'Soumettre la revue d’intégrité';

// Additional contestation and impact strings.
$string['containsrestricteddata'] = 'Contient des données restreintes';
$string['contestchallenge'] = 'Contester le défi';
$string['impactscope'] = 'Portée de l’impact';
$string['impactscope:archive'] = 'Archive';
$string['impactscope:badge'] = 'Badge';
$string['impactscope:challenge'] = 'Défi';
$string['impactscope:competency'] = 'Compétence';
$string['impactscope:multiple'] = 'Multiple';
$string['impactscope:submission'] = 'Soumission';
$string['impactsummary'] = 'Résumé de l’impact';
$string['notifyaffectedusers'] = 'Notifier les utilisateurs concernés';
$string['requireshumanfollowup'] = 'Suivi humain requis';
$string['restrictedproof'] = 'Preuve restreinte';
$string['restrictedproofnotice'] = 'Cette preuve contient des informations restreintes et ne peut être consultée que par les personnes autorisées.';
$string['restrictedreason'] = 'Raison de restriction';
$string['restrictedreasonrequired'] = 'La raison de restriction est requise.';
$string['restrictedvisibility'] = 'Visibilité restreinte';
$string['validationcontested'] = 'Validation contestée';
$string['validationhumanreviewed'] = 'Validation revue humainement';
$string['validationinvalidated'] = 'Validation invalidée';
$string['validationstate'] = 'État de validation';
$string['validationverified'] = 'Validation vérifiée';

// Additional AI and provenance strings.
$string['aiassistednonfinal'] = 'Contenu assisté par IA — non final';
$string['aiassistedreview'] = 'Revue assistée par IA';
$string['ailog'] = 'Journal IA';
$string['ainotauthority'] = 'L’IA n’est pas une autorité';
$string['ainotauthority_desc'] = 'L’IA peut aider à structurer, résumer ou questionner une preuve, mais elle ne peut pas valider l’intégrité, attribuer une reconnaissance, fermer un dossier ou remplacer la revue humaine.';
$string['allowresubmission'] = 'Autoriser une nouvelle soumission';
$string['humanreviewrequired'] = 'Revue humaine requise';
$string['provenancestatement'] = 'Déclaration de provenance';
$string['uncertaintynotes'] = 'Notes d’incertitude';

// Additional status and enum strings.
$string['decision:close'] = 'Fermer';
$string['decision:correction_required'] = 'Correction requise';
$string['decision:invalidate'] = 'Invalider';
$string['decision:no_action'] = 'Aucune action';
$string['decision:pause_validation'] = 'Suspendre la validation';
$string['decision:recommend_invalidation'] = 'Recommander l’invalidation';
$string['decision:validation_allowed'] = 'Validation autorisée';
$string['decisionrationale'] = 'Justification de la décision';
$string['statuscontested'] = 'Contesté';
$string['statusintegrityreview'] = 'Revue d’intégrité';
$string['statusrejected'] = 'Rejeté';
$string['statusrevisionrequired'] = 'Révision requise';
$string['statusunderreview'] = 'En révision';
$string['statusvalidated'] = 'Validé';

// Additional privacy and task strings.
$string['privacy:anonymiseduser'] = 'Utilisateur anonymisé';
$string['privacy:proofs'] = 'Preuves';
$string['privacy:submission'] = 'Soumission';
$string['privacyandprovenance'] = 'Confidentialité et provenance';
$string['task:closeexpiredchallenges'] = 'Fermer les défis expirés';
$string['task:closeexpiredchallengesresult'] = 'Défis expirés fermés : {$a}';

// Additional date/progress strings.
$string['progresscount'] = 'Nombre de progressions';
$string['progresspercent'] = 'Pourcentage de progression';
$string['timeclose'] = 'Date de fermeture';
$string['timeclosemustbeafteropen'] = 'La date de fermeture doit être postérieure à la date d’ouverture.';
$string['timeopen'] = 'Date d’ouverture';
$string['timereviewby'] = 'Date limite de révision';
$string['timereviewmustbeafterclose'] = 'La date de révision doit être postérieure à la date de fermeture.';

// Additional misc strings.
$string['cannotarchivechallenge'] = 'Vous ne pouvez pas archiver ce défi.';
$string['closingsummary'] = 'Résumé de clôture';
$string['eventchallengeevaluated'] = 'Défi évalué';
$string['noproofs'] = 'Aucune preuve.';
$string['nosubmissionstoevaluate'] = 'Aucune soumission à évaluer.';
$string['publicsummaryrequired'] = 'Le résumé public est requis.';
$string['publishpublicsummary'] = 'Publier le résumé public';
$string['requestintegrityreview'] = 'Demander une revue d’intégrité';
$string['requestintegrityreview_desc'] = 'Permet de demander une revue d’intégrité lorsqu’une preuve, évaluation ou reconnaissance présente un risque ou une contestation.';
$string['reviewproofs'] = 'Réviser les preuves';
$string['saveevaluation'] = 'Enregistrer l’évaluation';
$string['unknownuser'] = 'Utilisateur inconnu';
