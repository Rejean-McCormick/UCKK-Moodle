<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Intégrité UCKK';

$string['privacy:metadata:case'] = 'Les dossiers d’intégrité conservent les traces de révision liées aux preuves, défis, assemblées et archives.';
$string['privacy:metadata:case:openedby'] = 'L’utilisateur ayant ouvert le dossier.';
$string['privacy:metadata:case:assignedto'] = 'L’Inquisiteur assigné au dossier.';
$string['privacy:metadata:case:summary'] = 'Le résumé du dossier.';
$string['privacy:metadata:case:decision'] = 'La décision du dossier.';
$string['privacy:metadata:case:correction'] = 'Les corrections demandées pour le dossier.';
$string['privacy:metadata:note'] = 'Les notes de dossier d’intégrité conservent les observations, preuves, réponses, corrections et décisions.';
$string['privacy:metadata:note:userid'] = 'L’auteur de la note.';
$string['privacy:metadata:note:body'] = 'Le contenu de la note.';
$string['privacy:metadata:appeal'] = 'Les appels conservent les contestations des décisions d’intégrité.';
$string['privacy:metadata:appeal:userid'] = 'L’utilisateur ayant soumis l’appel.';
$string['privacy:metadata:case:metadata'] = 'Les métadonnées structurées du dossier.';
$string['privacy:metadata:appeal:body'] = 'Le contenu de l’appel.';
$string['privacy:pathcases'] = 'Dossiers d’intégrité';
$string['privacy:pathnotes'] = 'Notes d’intégrité';
$string['privacy:pathappeals'] = 'Appels d’intégrité';

$string['settings:general'] = 'Paramètres d’intégrité';
$string['settings:general_desc'] = 'Configurer le flux de travail d’intégrité de l’Inquisiteur UCKK.';
$string['settings:enabled'] = 'Activer le flux de travail d’intégrité';
$string['settings:enabled_desc'] = 'Permettre l’ouverture et la révision des dossiers d’intégrité.';
$string['settings:defaultseverity'] = 'Gravité par défaut';
$string['settings:defaultseverity_desc'] = 'Gravité utilisée par défaut lors de l’ouverture d’un dossier.';
$string['settings:appealwindow'] = 'Délai d’appel';
$string['settings:appealwindow_desc'] = 'Durée par défaut pendant laquelle une décision peut être contestée.';
$string['settings:restrictpublicsummaries'] = 'Restreindre les résumés publics avant clôture';
$string['settings:restrictpublicsummaries_desc'] = 'Les résumés publics ne sont visibles qu’après la clôture du dossier par un réviseur d’intégrité.';
$string['settings:retentiondays'] = 'Durée de conservation en jours';
$string['settings:retentiondays_desc'] = 'Durée de conservation recommandée des dossiers d’intégrité avant révision.';

$string['cases'] = 'Dossiers d’intégrité';
$string['case'] = 'Dossier';
$string['case:view'] = 'Voir le dossier d’intégrité';
$string['opencase'] = 'Ouvrir un dossier';
$string['caseopened'] = 'Dossier d’intégrité ouvert.';
$string['reviewcase'] = 'Réviser le dossier';
$string['reviewrecorded'] = 'Révision enregistrée.';
$string['decision'] = 'Décision';
$string['decisionrecorded'] = 'Décision enregistrée.';
$string['appeal'] = 'Appel';
$string['appealrecorded'] = 'Appel enregistré.';
$string['report'] = 'Rapport d’intégrité';

$string['casetype'] = 'Type de dossier';
$string['subjectcomponent'] = 'Composant visé';
$string['subjectid'] = 'ID de l’objet visé';
$string['severity'] = 'Gravité';
$string['status'] = 'Statut';
$string['summary'] = 'Résumé';
$string['decisiontext'] = 'Texte de la décision';
$string['correction'] = 'Correction';
$string['appealpath'] = 'Voie d’appel';
$string['archivesummary'] = 'Résumé d’archive';
$string['archiveitemid'] = 'ID de l’élément d’archive';
$string['assignedto'] = 'ID utilisateur de l’Inquisiteur assigné';
$string['visibility'] = 'Visibilité';
$string['metadata'] = 'Métadonnées';
$string['notetype'] = 'Type de note';
$string['body'] = 'Contenu';
$string['casefiles'] = 'Fichiers de preuve du dossier';
$string['save'] = 'Enregistrer';
$string['created'] = 'Créé';
$string['modified'] = 'Modifié';
$string['openedby'] = 'Ouvert par';
$string['actions'] = 'Actions';
$string['notes'] = 'Notes';
$string['noresults'] = 'Aucun dossier d’intégrité trouvé.';
$string['statuscounts'] = 'Dossiers par statut';
$string['severitycounts'] = 'Dossiers par gravité';
$string['recentcases'] = 'Dossiers récents';
$string['restricted'] = 'Restreint';
$string['parties'] = 'Parties';
$string['public_summary'] = 'Résumé public';
$string['viewcase'] = 'Voir le dossier';
$string['recorddecision'] = 'Enregistrer la décision';
$string['submitappeal'] = 'Soumettre un appel';
$string['caseid'] = 'ID du dossier';
$string['noappealpath'] = 'Aucune voie d’appel n’est enregistrée pour ce dossier.';
$string['appealnotice'] = 'Vous pouvez soumettre un appel pendant la fenêtre de contestation prévue.';
$string['appealnotavailable'] = 'L’appel n’est pas disponible pour ce dossier.';
$string['nodecisionrecorded'] = 'Aucune décision n’a encore été enregistrée.';
$string['unknownuser'] = 'Utilisateur inconnu';
$string['evidencelinks'] = 'Liens de preuve';
$string['invalidjson'] = 'Le JSON fourni est invalide.';
$string['appealunderreview'] = 'Appel en cours de révision.';
$string['appealwithdrawn'] = 'Appel retiré.';
$string['caseclosed'] = 'Dossier d’intégrité fermé.';
$string['correctionrequested'] = 'Correction d’intégrité demandée.';
$string['iteminvalidated'] = 'Élément invalidé pour raison d’intégrité.';
$string['contextid'] = 'ID du contexte';
$string['timeclosed'] = 'Date de clôture';
$string['invalidseverity'] = 'Gravité d’intégrité invalide.';
$string['invalidvisibility'] = 'Visibilité d’intégrité invalide.';

// Report labels.
$string['all'] = 'Tous';
$string['filter'] = 'Filtrer';
$string['total'] = 'Total';
$string['downloadtext'] = 'Télécharger';
$string['report:totalcases'] = 'Nombre total de dossiers';
$string['report:opencases'] = 'Dossiers ouverts';
$string['report:closedcases'] = 'Dossiers fermés';
$string['report:criticalcases'] = 'Dossiers critiques';
$string['report:overduecases'] = 'Dossiers en retard';

$string['invalidcaseid'] = 'ID de dossier d’intégrité invalide.';
$string['notpermitted'] = 'Vous n’avez pas l’autorisation d’accéder à ce dossier d’intégrité.';
$string['invalidtransition'] = 'Ce dossier ne peut pas passer de {$a->from} à {$a->to}.';
$string['unknownstatus'] = 'Statut d’intégrité inconnu.';
$string['unknowntype'] = 'Type de dossier d’intégrité inconnu.';

$string['eventcaseopened'] = 'Dossier d’intégrité ouvert';
$string['eventcasereviewed'] = 'Dossier d’intégrité révisé';
$string['eventcorrectionrequested'] = 'Correction d’intégrité demandée';
$string['eventcorrectionissued'] = 'Correction d’intégrité émise';
$string['eventiteminvalidated'] = 'Élément invalidé pour raison d’intégrité';
$string['eventcaseclosed'] = 'Dossier d’intégrité fermé';

$string['uckkintegrity:view'] = 'Voir les dossiers d’intégrité';
$string['uckkintegrity:opencase'] = 'Ouvrir des dossiers d’intégrité';
$string['uckkintegrity:reviewcase'] = 'Réviser des dossiers d’intégrité';
$string['uckkintegrity:assigncase'] = 'Assigner des dossiers d’intégrité';
$string['uckkintegrity:issuecorrection'] = 'Émettre des corrections d’intégrité';
$string['uckkintegrity:invalidate'] = 'Invalider des éléments sensibles à l’intégrité';
$string['uckkintegrity:closecase'] = 'Fermer des dossiers d’intégrité';
$string['uckkintegrity:viewrestricted'] = 'Voir les données d’intégrité restreintes';
// Canonical capability names.
$string['tool/uckkintegrity:view'] = 'Voir les dossiers d’intégrité';
$string['tool/uckkintegrity:opencase'] = 'Ouvrir des dossiers d’intégrité';
$string['tool/uckkintegrity:reviewcase'] = 'Réviser des dossiers d’intégrité';
$string['tool/uckkintegrity:assigncase'] = 'Assigner des dossiers d’intégrité';
$string['tool/uckkintegrity:issuecorrection'] = 'Émettre des corrections d’intégrité';
$string['tool/uckkintegrity:invalidate'] = 'Invalider des éléments sensibles à l’intégrité';
$string['tool/uckkintegrity:closecase'] = 'Fermer des dossiers d’intégrité';
$string['tool/uckkintegrity:viewrestricted'] = 'Voir les données d’intégrité restreintes';

$string['severity:low'] = 'Faible';
$string['severity:normal'] = 'Normale';
$string['severity:high'] = 'Élevée';
$string['severity:critical'] = 'Critique';

$string['type:proof_quality'] = 'Qualité de la preuve';
$string['type:fiction_fact_confusion'] = 'Confusion entre fiction et faits';
$string['type:ai_misuse'] = 'Mésusage de l’IA';
$string['type:harassment_or_humiliation'] = 'Harcèlement ou humiliation';
$string['type:dignity_violation'] = 'Atteinte à la dignité';
$string['type:authority_capture'] = 'Capture d’autorité';
$string['type:assessment_dispute'] = 'Contestation d’évaluation';
$string['type:challenge_dispute'] = 'Contestation de défi';
$string['type:assembly_dispute'] = 'Contestation d’assemblée';
$string['type:archive_correction'] = 'Correction d’archive';
$string['type:privacy_concern'] = 'Préoccupation de confidentialité';

$string['status:opened'] = 'Ouvert';
$string['status:triaged'] = 'Trié';
$string['status:assigned'] = 'Assigné';
$string['status:under_review'] = 'En révision';
$string['status:waiting_for_response'] = 'En attente de réponse';
$string['status:correction_required'] = 'Correction requise';
$string['status:resolved'] = 'Résolu';
$string['status:archived'] = 'Archivé';
$string['status:dismissed'] = 'Rejeté';
$string['status:escalated'] = 'Escaladé';
$string['status:paused'] = 'Suspendu';
$string['status:reopened'] = 'Rouvert';
$string['status:closed'] = 'Fermé';

$string['note:observation'] = 'Observation';
$string['note:evidence'] = 'Preuve';
$string['note:response'] = 'Réponse';
$string['note:decision'] = 'Décision';
$string['note:correction'] = 'Correction';
$string['note:appeal'] = 'Appel';
