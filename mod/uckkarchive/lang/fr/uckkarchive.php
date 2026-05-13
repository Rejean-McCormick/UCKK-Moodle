<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Chaînes françaises du module d’activité Archives UCKK.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// -----------------------------------------------------------------------------
// Identité du plugin.
// -----------------------------------------------------------------------------

$string['pluginname'] = 'Archive UCKK';
$string['pluginadministration'] = 'Administration de l’Archive UCKK';
$string['modulename'] = 'Archive UCKK';
$string['modulenameplural'] = 'Archives UCKK';
$string['modulename_help'] = 'L’activité Archive UCKK conserve les preuves, décisions, travaux de cours, Kristals, éléments de portfolio, synthèses d’intégrité, versions et paquets exportables avec provenance, visibilité, validation et historique de révision.';
$string['modulename_link'] = 'mod/uckkarchive/view';
$string['uckkarchive:addinstance'] = 'Ajouter une nouvelle activité Archive UCKK';
$string['uckkarchive:view'] = 'Voir l’Archive UCKK';
$string['uckkarchive:additem'] = 'Ajouter un élément d’archive';
$string['uckkarchive:validateitem'] = 'Valider un élément d’archive';
$string['uckkarchive:reviseitem'] = 'Réviser un élément d’archive';
$string['uckkarchive:versionitem'] = 'Versionner un élément d’archive';
$string['uckkarchive:viewrestricted'] = 'Voir les éléments d’archive restreints';
$string['uckkarchive:export'] = 'Exporter des paquets d’archive';

// -----------------------------------------------------------------------------
// Libellés généraux.
// -----------------------------------------------------------------------------

$string['activity'] = 'Activité';
$string['actions'] = 'Actions';
$string['add'] = 'Ajouter';
$string['additem'] = 'Ajouter un élément d’archive';
$string['archive'] = 'Archive';
$string['archiveactivity'] = 'Activité d’archive';
$string['archiveitem'] = 'Élément d’archive';
$string['archiveitems'] = 'Éléments d’archive';
$string['archiveitemdetails'] = 'Détails de l’élément d’archive';
$string['archiveoverview'] = 'Vue d’ensemble de l’archive';
$string['archivepolicy'] = 'Politique d’archive';
$string['archives'] = 'Archives';
$string['backtoarchive'] = 'Retour à l’archive';
$string['backtoarchives'] = 'Retour aux archives';
$string['cancel'] = 'Annuler';
$string['close'] = 'Fermer';
$string['confirm'] = 'Confirmer';
$string['continue'] = 'Continuer';
$string['course'] = 'Cours';
$string['created'] = 'Créé';
$string['createdby'] = 'Créé par';
$string['description'] = 'Description';
$string['details'] = 'Détails';
$string['download'] = 'Télécharger';
$string['edit'] = 'Modifier';
$string['empty'] = 'Rien à afficher.';
$string['export'] = 'Exporter';
$string['files'] = 'Fichiers';
$string['filter'] = 'Filtrer';
$string['item'] = 'Élément';
$string['items'] = 'Éléments';
$string['metadata'] = 'Métadonnées';
$string['modified'] = 'Modifié';
$string['modifiedby'] = 'Modifié par';
$string['name'] = 'Nom';
$string['none'] = 'Aucun';
$string['notes'] = 'Notes';
$string['open'] = 'Ouvrir';
$string['owner'] = 'Propriétaire';
$string['preview'] = 'Aperçu';
$string['provenance'] = 'Provenance';
$string['reason'] = 'Raison';
$string['records'] = 'Traces';
$string['restricted'] = 'Restreint';
$string['restricted_integrity'] = 'Restreint à l’intégrité';
$string['revision'] = 'Révision';
$string['revisions'] = 'Révisions';
$string['save'] = 'Enregistrer';
$string['search'] = 'Rechercher';
$string['source'] = 'Source';
$string['status'] = 'Statut';
$string['submit'] = 'Soumettre';
$string['summary'] = 'Résumé';
$string['title'] = 'Titre';
$string['type'] = 'Type';
$string['validation'] = 'Validation';
$string['visibility'] = 'Visibilité';
$string['version'] = 'Version';
$string['versionno'] = 'Version {$a}';
$string['view'] = 'Voir';
$string['viewdetails'] = 'Voir les détails';

// -----------------------------------------------------------------------------
// Formulaire d’instance d’activité.
// -----------------------------------------------------------------------------

$string['archivename'] = 'Nom de l’archive';
$string['archivename_help'] = 'Nom de cette activité Archive UCKK.';
$string['archiveintro'] = 'Introduction de l’archive';
$string['archivecode'] = 'Code de l’archive';
$string['archivecode_help'] = 'Code interne stable pour cette archive. Utilisez uniquement des lettres, chiffres, traits de soulignement ou traits d’union.';
$string['archivecontext'] = 'Contexte de l’archive';
$string['archivecontext_help'] = 'Décrivez ce que cette archive doit préserver et qui peut l’utiliser.';
$string['archivepurpose'] = 'Finalité de l’archive';
$string['archivepurpose_help'] = 'Expliquez pourquoi cette archive existe : preuves, décisions, portfolio, mémoire de cours, Kristals, synthèses d’intégrité ou mémoire institutionnelle.';
$string['defaultvisibility'] = 'Visibilité par défaut';
$string['defaultvisibility_help'] = 'Visibilité appliquée par défaut aux nouveaux éléments, sauf remplacement dans le formulaire de l’élément.';
$string['allowpublicitems'] = 'Autoriser les éléments publics';
$string['allowpublicitems_help'] = 'Autoriser les éléments d’archive validés manuellement à devenir publics.';
$string['requirevalidation'] = 'Exiger la validation avant publication';
$string['requirevalidation_help'] = 'Exiger qu’un Archiviste ou une personne autorisée valide les éléments avant qu’ils soient considérés comme vérifiés ou publics.';
$string['allowkristals'] = 'Autoriser les Kristals';
$string['allowkristals_help'] = 'Autoriser cette archive à stocker des Kristals : fragments distillés d’apprentissage, décision, preuve ou mémoire.';
$string['allowexports'] = 'Autoriser les exports';
$string['allowexports_help'] = 'Autoriser les personnes habilitées à exporter des paquets d’archive.';
$string['completionrequirevalidateditem'] = 'L’étudiant doit avoir un élément d’archive validé';
$string['completionrequireitem'] = 'L’étudiant doit ajouter un élément d’archive';
$string['completionrequirekristal'] = 'L’étudiant doit créer un Kristal';

// -----------------------------------------------------------------------------
// Paramètres.
// -----------------------------------------------------------------------------

$string['settings:general'] = 'Paramètres généraux des archives';
$string['settings:general_desc'] = 'Configurer les valeurs par défaut des Archives UCKK.';
$string['settings:defaultvisibility'] = 'Visibilité d’archive par défaut';
$string['settings:defaultvisibility_desc'] = 'Visibilité utilisée par défaut lorsqu’un nouvel élément d’archive ne précise pas sa visibilité.';
$string['settings:requirevalidation'] = 'Exiger la validation';
$string['settings:requirevalidation_desc'] = 'Exiger une validation avant que les éléments d’archive deviennent vérifiés ou publics.';
$string['settings:allowpublicarchives'] = 'Autoriser les éléments d’archive publics';
$string['settings:allowpublicarchives_desc'] = 'Autoriser les éléments d’archive à devenir publics après validation manuelle.';
$string['settings:allowrestrictedintegrity'] = 'Autoriser les archives restreintes à l’intégrité';
$string['settings:allowrestrictedintegrity_desc'] = 'Autoriser les éléments d’archive à être marqués comme restreints aux personnes disposant de capacités d’intégrité ou d’archive restreinte.';
$string['settings:enablekristals'] = 'Activer les Kristals';
$string['settings:enablekristals_desc'] = 'Activer la création et l’affichage de Kristals dans les Archives UCKK.';
$string['settings:enableexports'] = 'Activer les paquets d’export';
$string['settings:enableexports_desc'] = 'Autoriser les personnes habilitées à générer des paquets d’export.';
$string['settings:retentiondays'] = 'Durée de conservation par défaut';
$string['settings:retentiondays_desc'] = 'Durée de conservation par défaut en jours. Utiliser 0 pour aucune expiration automatique.';
$string['settings:maxexportitems'] = 'Nombre maximal d’éléments par export';
$string['settings:maxexportitems_desc'] = 'Nombre maximal d’éléments d’archive inclus dans un paquet d’export.';
$string['settings:provenance'] = 'Paramètres de provenance';
$string['settings:provenance_desc'] = 'Configurer la provenance et le comportement de validation des archives.';
$string['settings:requireprovenance'] = 'Exiger la provenance';
$string['settings:requireprovenance_desc'] = 'Exiger une déclaration de provenance pour chaque élément d’archive.';
$string['settings:requirevalidationnotes'] = 'Exiger des notes de validation';
$string['settings:requirevalidationnotes_desc'] = 'Exiger que les validateurs fournissent des notes lors de la validation, du rejet ou de l’invalidation d’un élément.';

// -----------------------------------------------------------------------------
// Formulaire d’élément.
// -----------------------------------------------------------------------------

$string['archiveitemform'] = 'Formulaire d’élément d’archive';
$string['itemtitle'] = 'Titre de l’élément';
$string['itemtitle_help'] = 'Titre clair pour l’élément d’archive.';
$string['itemsummary'] = 'Résumé de l’élément';
$string['itemsummary_help'] = 'Résumé court et sûr pour le niveau de visibilité choisi.';
$string['itemcontent'] = 'Contenu de l’élément';
$string['itemcontent_help'] = 'Contenu principal de l’archive. N’incluez pas de détails restreints sauf si la visibilité de l’élément le permet.';
$string['itemtype'] = 'Type d’élément';
$string['itemtype_help'] = 'Choisissez le type qui décrit le mieux l’élément d’archive.';
$string['itemstatus'] = 'Statut de l’élément';
$string['itemvisibility'] = 'Visibilité de l’élément';
$string['itemvisibility_help'] = 'Contrôle qui peut voir cet élément d’archive. Les éléments restreints à l’intégrité exigent une capacité explicite.';
$string['itemprovenance'] = 'Provenance de l’élément';
$string['itemprovenance_help'] = 'Expliquez d’où vient l’élément, qui l’a produit, ce qui a été transformé et ce qui peut être vérifié.';
$string['itemsource'] = 'Source';
$string['itemsource_help'] = 'Source originale, système, activité, personne, archive ou référence externe de cet élément.';
$string['sourcecomponent'] = 'Composant source';
$string['sourcecomponent_help'] = 'Composant Moodle ayant généré l’élément, si connu.';
$string['sourceid'] = 'ID source';
$string['sourceid_help'] = 'ID de l’enregistrement dans le composant source, si connu.';
$string['sourceurl'] = 'URL source';
$string['sourceurl_help'] = 'URL optionnelle pointant vers la source.';
$string['sourceauthor'] = 'Source ou auteur';
$string['sourceauthor_help'] = 'Personne, groupe, système, activité ou source d’archive derrière l’élément.';
$string['sourcedate'] = 'Date de la source';
$string['sourcedate_help'] = 'Date associée à la source originale.';
$string['license'] = 'Licence';
$string['license_help'] = 'Licence ou condition de réutilisation attachée à l’élément, le cas échéant.';
$string['tags'] = 'Étiquettes';
$string['tags_help'] = 'Étiquettes optionnelles séparées par des virgules pour le filtrage et les rapports.';
$string['files_help'] = 'Fichiers attachés à cet élément d’archive. Les fichiers héritent de la visibilité de l’élément sauf si un service applique une règle plus stricte.';
$string['prooffiles'] = 'Fichiers de preuve';
$string['decisionattachments'] = 'Pièces jointes de décision';
$string['minutesfiles'] = 'Fichiers de procès-verbal';
$string['kristalfiles'] = 'Fichiers de Kristal';
$string['portfoliofiles'] = 'Fichiers de portfolio';
$string['integrityexports'] = 'Exports d’intégrité';
$string['addarchiveitem'] = 'Ajouter un élément d’archive';
$string['editarchiveitem'] = 'Modifier l’élément d’archive';
$string['archiveitemsaved'] = 'Élément d’archive enregistré.';
$string['archiveitemcreated'] = 'Élément d’archive créé.';
$string['archiveitemupdated'] = 'Élément d’archive mis à jour.';
$string['cannotadditem'] = 'Vous ne pouvez pas ajouter d’éléments d’archive ici.';
$string['cannotedititem'] = 'Vous ne pouvez pas modifier cet élément d’archive.';
$string['cannotviewitem'] = 'Vous ne pouvez pas voir cet élément d’archive.';
$string['missingarchiveitem'] = 'Élément d’archive introuvable.';
$string['invaliditemtype'] = 'Type d’élément d’archive invalide.';
$string['invaliditemstatus'] = 'Statut d’élément d’archive invalide.';
$string['invaliditemvisibility'] = 'Visibilité d’élément d’archive invalide.';
$string['provenancerequired'] = 'Une déclaration de provenance est requise.';
$string['contentorsourcefilesrequired'] = 'Ajoutez du contenu, une URL source ou au moins un fichier.';

// -----------------------------------------------------------------------------
// Kristals.
// -----------------------------------------------------------------------------

$string['kristal'] = 'Kristal';
$string['kristals'] = 'Kristals';
$string['kristalform'] = 'Formulaire de Kristal';
$string['addkristal'] = 'Ajouter un Kristal';
$string['editkristal'] = 'Modifier le Kristal';
$string['kristaltitle'] = 'Titre du Kristal';
$string['kristaltitle_help'] = 'Titre court de ce Kristal.';
$string['kristalstatement'] = 'Énoncé du Kristal';
$string['kristalstatement_help'] = 'Énoncé distillé d’apprentissage, décision, preuve, intuition ou mémoire.';
$string['kristalcontext'] = 'Contexte du Kristal';
$string['kristalcontext_help'] = 'Expliquez le contexte à partir duquel ce Kristal a été distillé.';
$string['kristalproof'] = 'Preuve du Kristal';
$string['kristalproof_help'] = 'Preuve ou références soutenant le Kristal.';
$string['kristaltype'] = 'Type de Kristal';
$string['kristalvisibility'] = 'Visibilité du Kristal';
$string['kristalcreated'] = 'Kristal créé.';
$string['kristalupdated'] = 'Kristal mis à jour.';
$string['kristalempty'] = 'Aucun Kristal disponible.';
$string['cannotaddkristal'] = 'Vous ne pouvez pas ajouter de Kristals ici.';
$string['cannoteditkristal'] = 'Vous ne pouvez pas modifier ce Kristal.';
$string['cannotviewkristal'] = 'Vous ne pouvez pas voir ce Kristal.';
$string['invalidkristaltype'] = 'Type de Kristal invalide.';

// -----------------------------------------------------------------------------
// Validation.
// -----------------------------------------------------------------------------

$string['validate'] = 'Valider';
$string['validateitem'] = 'Valider l’élément d’archive';
$string['validationform'] = 'Formulaire de validation';
$string['validationstate'] = 'État de validation';
$string['validationnotes'] = 'Notes de validation';
$string['validationnotes_help'] = 'Expliquez la décision de validation, l’incertitude, les corrections requises ou la raison du rejet.';
$string['validationdecision'] = 'Décision de validation';
$string['validationdecision_help'] = 'Choisissez comment cet élément d’archive doit être traité après revue.';
$string['validatedby'] = 'Validé par';
$string['timevalidated'] = 'Moment de validation';
$string['archiveitemvalidated'] = 'Élément d’archive validé.';
$string['archiveitemrejected'] = 'Élément d’archive rejeté.';
$string['archiveiteminvalidated'] = 'Élément d’archive invalidé.';
$string['archiveitemcorrectionrequired'] = 'Correction requise pour l’élément d’archive.';
$string['archiveitemcontested'] = 'Élément d’archive contesté.';
$string['cannotvalidateitem'] = 'Vous ne pouvez pas valider cet élément d’archive.';
$string['validationrequiresnotes'] = 'Des notes de validation sont requises pour cette décision.';
$string['manualvalidationrequired'] = 'Validation manuelle requise';
$string['publicvalidationrequired'] = 'Les éléments d’archive publics doivent être validés manuellement.';
$string['restrictedvalidationrequired'] = 'Les éléments d’archive restreints à l’intégrité exigent une validation explicite.';

$string['validationdecision:validate'] = 'Valider';
$string['validationdecision:reject'] = 'Rejeter';
$string['validationdecision:correction_required'] = 'Correction requise';
$string['validationdecision:contest'] = 'Contester';
$string['validationdecision:invalidate'] = 'Invalider';

// -----------------------------------------------------------------------------
// Révision et versioning.
// -----------------------------------------------------------------------------

$string['revise'] = 'Réviser';
$string['reviseitem'] = 'Réviser l’élément d’archive';
$string['revisionform'] = 'Formulaire de révision';
$string['revisionreason'] = 'Raison de la révision';
$string['revisionreason_help'] = 'Expliquez pourquoi cette révision est nécessaire. L’historique d’archive doit rester traçable.';
$string['revisionnotes'] = 'Notes de révision';
$string['revisioncreated'] = 'Révision créée.';
$string['archiveitemrevised'] = 'Élément d’archive révisé.';
$string['previousversion'] = 'Version précédente';
$string['currentversion'] = 'Version actuelle';
$string['versionhistory'] = 'Historique des versions';
$string['versionrecord'] = 'Trace de version';
$string['cannotreviseitem'] = 'Vous ne pouvez pas réviser cet élément d’archive.';
$string['cannotversionitem'] = 'Vous ne pouvez pas créer de trace de version pour cet élément.';
$string['revisionrequiresreason'] = 'Une raison de révision est requise.';
$string['revisionnonsovereignnotice'] = 'Une révision préserve l’historique d’archive. Elle ne doit pas effacer silencieusement les preuves ou la provenance.';

// -----------------------------------------------------------------------------
// Export.
// -----------------------------------------------------------------------------

$string['exportarchive'] = 'Exporter l’archive';
$string['exportform'] = 'Formulaire d’export';
$string['exportpackage'] = 'Paquet d’export';
$string['exportpackages'] = 'Paquets d’export';
$string['exportformat'] = 'Format d’export';
$string['exportscope'] = 'Portée de l’export';
$string['exportvisibility'] = 'Visibilité de l’export';
$string['exportreason'] = 'Raison de l’export';
$string['exportreason_help'] = 'Expliquez pourquoi cet export d’archive est nécessaire.';
$string['exportincludeproofs'] = 'Inclure les fichiers de preuve';
$string['exportincludeprovenance'] = 'Inclure la provenance';
$string['exportincludehistory'] = 'Inclure l’historique des révisions';
$string['exportincludeintegrity'] = 'Inclure les synthèses d’intégrité';
$string['exportredactrestricted'] = 'Caviarder les détails restreints';
$string['exportredactrestricted_help'] = 'Retirer ou résumer les détails restreints d’intégrité dans le paquet d’export, sauf si la personne dispose d’une permission explicite.';
$string['exportgenerated'] = 'Export d’archive généré.';
$string['archiveitemexported'] = 'Élément d’archive exporté.';
$string['cannotexportarchive'] = 'Vous ne pouvez pas exporter cette archive.';
$string['cannotexportitem'] = 'Vous ne pouvez pas exporter cet élément d’archive.';
$string['exportrequiresreason'] = 'Une raison d’export est requise.';
$string['exportempty'] = 'Aucun paquet d’export disponible.';
$string['downloadexport'] = 'Télécharger l’export';
$string['exportqueued'] = 'Export mis en file.';
$string['exportfailed'] = 'Échec de l’export.';
$string['exportsucceeded'] = 'Export terminé.';

$string['exportformat:json'] = 'JSON';
$string['exportformat:zip'] = 'Paquet ZIP';
$string['exportformat:csv'] = 'CSV';
$string['exportformat:pdf'] = 'Résumé PDF';
$string['exportscope:item'] = 'Élément unique';
$string['exportscope:archive'] = 'Archive complète';
$string['exportscope:course'] = 'Archive de cours';
$string['exportscope:user'] = 'Archive de portfolio utilisateur';
$string['exportscope:integrity'] = 'Archive d’intégrité';

// -----------------------------------------------------------------------------
// Types d’éléments.
// -----------------------------------------------------------------------------

$string['itemtype:proof'] = 'Preuve';
$string['itemtype:decision'] = 'Décision';
$string['itemtype:course_work'] = 'Travail de cours';
$string['itemtype:challenge_result'] = 'Résultat de défi';
$string['itemtype:assembly_minutes'] = 'Procès-verbal d’assemblée';
$string['itemtype:integrity_case_summary'] = 'Synthèse de dossier d’intégrité';
$string['itemtype:kristal'] = 'Kristal';
$string['itemtype:reflection'] = 'Réflexion';
$string['itemtype:portfolio_item'] = 'Élément de portfolio';
$string['itemtype:version_record'] = 'Trace de version';
$string['itemtype:archive_item'] = 'Élément d’archive';

// -----------------------------------------------------------------------------
// Types de Kristals.
// -----------------------------------------------------------------------------

$string['kristaltype:learning'] = 'Kristal d’apprentissage';
$string['kristaltype:decision'] = 'Kristal de décision';
$string['kristaltype:proof'] = 'Kristal de preuve';
$string['kristaltype:reflection'] = 'Kristal de réflexion';
$string['kristaltype:method'] = 'Kristal de méthode';
$string['kristaltype:governance'] = 'Kristal de gouvernance';
$string['kristaltype:integrity'] = 'Kristal d’intégrité';
$string['kristaltype:memory'] = 'Kristal de mémoire';

// -----------------------------------------------------------------------------
// Types de preuves.
// -----------------------------------------------------------------------------

$string['prooftype'] = 'Type de preuve';
$string['prooftype:text'] = 'Texte';
$string['prooftype:file'] = 'Fichier';
$string['prooftype:url'] = 'URL';
$string['prooftype:dataset'] = 'Jeu de données';
$string['prooftype:image'] = 'Image';
$string['prooftype:video'] = 'Vidéo';
$string['prooftype:testimony'] = 'Témoignage';
$string['prooftype:observation'] = 'Observation';
$string['prooftype:ai_log'] = 'Journal IA';
$string['prooftype:decision_record'] = 'Compte rendu de décision';
$string['prooftype:archive_item'] = 'Élément d’archive';

// -----------------------------------------------------------------------------
// Statuts.
// -----------------------------------------------------------------------------

$string['status:draft'] = 'Brouillon';
$string['status:active'] = 'Actif';
$string['status:hidden'] = 'Masqué';
$string['status:pending'] = 'En attente';
$string['status:pending_review'] = 'En attente de revue';
$string['status:validated'] = 'Validé';
$string['status:rejected'] = 'Rejeté';
$string['status:correction_required'] = 'Correction requise';
$string['status:contested'] = 'Contesté';
$string['status:invalidated'] = 'Invalidé';
$string['status:closed'] = 'Fermé';
$string['status:archived'] = 'Archivé';
$string['status:cancelled'] = 'Annulé';

$string['statuslabel:draft'] = 'Brouillon';
$string['statuslabel:active'] = 'Actif';
$string['statuslabel:hidden'] = 'Masqué';
$string['statuslabel:pending'] = 'En attente';
$string['statuslabel:pendingreview'] = 'En attente de revue';
$string['statuslabel:validated'] = 'Validé';
$string['statuslabel:rejected'] = 'Rejeté';
$string['statuslabel:correctionrequired'] = 'Correction requise';
$string['statuslabel:contested'] = 'Contesté';
$string['statuslabel:invalidated'] = 'Invalidé';
$string['statuslabel:closed'] = 'Fermé';
$string['statuslabel:archived'] = 'Archivé';
$string['statuslabel:cancelled'] = 'Annulé';

// -----------------------------------------------------------------------------
// Visibilité.
// -----------------------------------------------------------------------------

$string['visibility:private'] = 'Privée';
$string['visibility:user'] = 'Utilisateur';
$string['visibility:group'] = 'Groupe';
$string['visibility:course'] = 'Cours';
$string['visibility:cohort'] = 'Cohorte';
$string['visibility:program'] = 'Programme';
$string['visibility:institution'] = 'Institution';
$string['visibility:institutional'] = 'Institutionnelle';
$string['visibility:public'] = 'Publique';
$string['visibility:restricted'] = 'Restreinte';
$string['visibility:restricted_integrity'] = 'Restreinte à l’intégrité';
$string['visibility:hidden'] = 'Masquée';
$string['visibility:archived'] = 'Archivée';

$string['visibility_help:private'] = 'Visible uniquement par les propriétaires et réviseurs autorisés.';
$string['visibility_help:course'] = 'Visible dans le contexte du cours pour les personnes autorisées.';
$string['visibility_help:program'] = 'Visible dans le programme UCKK lié.';
$string['visibility_help:institution'] = 'Visible aux personnes institutionnelles autorisées.';
$string['visibility_help:public'] = 'Publique uniquement après validation manuelle.';
$string['visibility_help:restricted_integrity'] = 'Visible uniquement par les personnes disposant des permissions d’intégrité ou d’archive restreinte.';

// -----------------------------------------------------------------------------
// Provenance.
// -----------------------------------------------------------------------------

$string['provenance:human'] = 'Humaine';
$string['provenance:ai_assisted'] = 'Assistée par IA';
$string['provenance:imported'] = 'Importée';
$string['provenance:system'] = 'Système';
$string['provenance:archive'] = 'Archive';
$string['provenance:assembly'] = 'Assemblée';
$string['provenance:challenge'] = 'Défi';
$string['provenance:integrity'] = 'Intégrité';

$string['provenancehash'] = 'Hash de provenance';
$string['provenancehash_help'] = 'Hash optionnel utilisé pour détecter ou documenter les changements de provenance.';
$string['provenancepanel'] = 'Panneau de provenance';
$string['provenancesource'] = 'Source de provenance';
$string['provenancestatement'] = 'Déclaration de provenance';
$string['provenancestatement_help'] = 'Expliquez l’origine, l’auteur, les transformations, l’incertitude et le chemin de vérification.';
$string['provenanceverified'] = 'Provenance vérifiée';
$string['provenanceunverified'] = 'Provenance non vérifiée';
$string['provenancewarning'] = 'Avertissement de provenance';

// -----------------------------------------------------------------------------
// États de validation.
// -----------------------------------------------------------------------------

$string['validationstate:unverified'] = 'Non vérifié';
$string['validationstate:human_reviewed'] = 'Revu humainement';
$string['validationstate:verified'] = 'Vérifié';
$string['validationstate:contested'] = 'Contesté';
$string['validationstate:invalidated'] = 'Invalidé';
$string['validationstate:archived'] = 'Archivé';

$string['validation:unverified'] = 'Non vérifié';
$string['validation:human_reviewed'] = 'Revu humainement';
$string['validation:verified'] = 'Vérifié';
$string['validation:contested'] = 'Contesté';
$string['validation:invalidated'] = 'Invalidé';
$string['validation:archived'] = 'Archivé';

// -----------------------------------------------------------------------------
// Politiques d’archive.
// -----------------------------------------------------------------------------

$string['archivepolicy:none'] = 'Aucune politique d’archive';
$string['archivepolicy:summary'] = 'Archive synthétique';
$string['archivepolicy:full'] = 'Archive complète';
$string['archivepolicy:restricted_integrity'] = 'Archive restreinte à l’intégrité';
$string['archivepolicy:portfolio'] = 'Archive de portfolio';
$string['archivepolicy:institutional_memory'] = 'Mémoire institutionnelle';

// -----------------------------------------------------------------------------
// Pages et panneaux.
// -----------------------------------------------------------------------------

$string['archiveview'] = 'Vue de l’archive';
$string['archiveviewempty'] = 'Aucun élément d’archive n’est disponible pour cette vue.';
$string['archiveitemcard'] = 'Carte d’élément d’archive';
$string['kristalcard'] = 'Carte de Kristal';
$string['proofcard'] = 'Carte de preuve';
$string['validationpanel'] = 'Panneau de validation';
$string['archiveactions'] = 'Actions d’archive';
$string['itemactions'] = 'Actions de l’élément';
$string['kristalactions'] = 'Actions du Kristal';
$string['validationactions'] = 'Actions de validation';
$string['exportactions'] = 'Actions d’export';
$string['viewarchiveitem'] = 'Voir l’élément d’archive';
$string['viewkristal'] = 'Voir le Kristal';
$string['viewprovenance'] = 'Voir la provenance';
$string['viewrevisionhistory'] = 'Voir l’historique des révisions';
$string['viewvalidation'] = 'Voir la validation';
$string['viewexport'] = 'Voir l’export';
$string['emptyarchive'] = 'Cette archive ne contient aucun élément visible.';
$string['emptykristals'] = 'Cette archive ne contient aucun Kristal visible.';
$string['emptyproofs'] = 'Aucune trace de preuve disponible.';
$string['emptyrevisions'] = 'Aucune révision disponible.';
$string['emptyexports'] = 'Aucun export disponible.';
$string['restrictednotice'] = 'Cet élément contient des données d’archive restreintes.';
$string['publicnotice'] = 'La visibilité publique d’une archive exige une validation manuelle.';
$string['archivenonsovereignnotice'] = 'Les traces d’archive préservent la mémoire et les preuves. La validation, les décisions d’intégrité, la visibilité publique et les exports exigent une revue humaine autorisée.';
$string['archivegovernancenotice'] = 'L’Archive UCKK préserve les preuves, décisions, Kristals, portfolios et historiques de version. Elle ne doit pas servir à effacer silencieusement des preuves ou à contourner la revue d’intégrité.';

// -----------------------------------------------------------------------------
// Messages de contrôleurs.
// -----------------------------------------------------------------------------

$string['invalidarchiveaction'] = 'Action d’archive invalide.';
$string['invalidvalidationaction'] = 'Action de validation invalide.';
$string['invalidexportaction'] = 'Action d’export invalide.';
$string['invalidrevisionaction'] = 'Action de révision invalide.';
$string['invalidvisibility'] = 'Visibilité invalide.';
$string['invalidstatus'] = 'Statut invalide.';
$string['invalidprovenance'] = 'Provenance invalide.';
$string['cannotviewarchive'] = 'Vous ne pouvez pas voir cette archive.';
$string['cannotviewrestricted'] = 'Vous ne pouvez pas voir les données d’archive restreintes.';
$string['cannotmanagearchive'] = 'Vous ne pouvez pas gérer cette archive.';
$string['cannotdeleteitem'] = 'Vous ne pouvez pas supprimer cet élément d’archive.';
$string['deleteitemnotallowed'] = 'Les éléments d’archive doivent être révisés ou invalidés, et non supprimés silencieusement.';
$string['itemnotfound'] = 'Élément d’archive introuvable.';
$string['archivenotfound'] = 'Archive introuvable.';
$string['kristalnotfound'] = 'Kristal introuvable.';
$string['exportnotfound'] = 'Paquet d’export introuvable.';
$string['nopermission'] = 'Vous n’avez pas la permission d’effectuer cette action d’archive.';

// -----------------------------------------------------------------------------
// Événements.
// -----------------------------------------------------------------------------

$string['eventarchiveitemcreated'] = 'Élément d’archive créé';
$string['eventarchiveitemvalidated'] = 'Élément d’archive validé';
$string['eventarchiveitemrevised'] = 'Élément d’archive révisé';
$string['eventarchiveitemexported'] = 'Élément d’archive exporté';
$string['eventkristalcreated'] = 'Kristal créé';
$string['eventkristalupdated'] = 'Kristal mis à jour';
$string['eventarchiveviewed'] = 'Archive consultée';
$string['eventarchiveitemviewed'] = 'Élément d’archive consulté';
$string['eventexportgenerated'] = 'Export d’archive généré';

// -----------------------------------------------------------------------------
// Tâches planifiées.
// -----------------------------------------------------------------------------

$string['task:validate_pending_items'] = 'Valider les éléments d’archive en attente';
$string['task:generate_archive_exports'] = 'Générer les exports d’archive';
$string['task:cleanup_expired_exports'] = 'Nettoyer les exports d’archive expirés';

// -----------------------------------------------------------------------------
// Services externes et Ajax.
// -----------------------------------------------------------------------------

$string['service:get_archive_view'] = 'Obtenir la vue d’archive';
$string['service:get_archive_item'] = 'Obtenir l’élément d’archive';
$string['service:save_archive_item'] = 'Enregistrer l’élément d’archive';
$string['service:validate_archive_item'] = 'Valider l’élément d’archive';
$string['service:revise_archive_item'] = 'Réviser l’élément d’archive';
$string['service:create_kristal'] = 'Créer un Kristal';
$string['service:generate_export'] = 'Générer un export d’archive';

$string['archive:refreshing'] = 'Actualisation de l’archive…';
$string['archive:refreshed'] = 'Archive actualisée.';
$string['archive:refreshfailed'] = 'Impossible d’actualiser l’archive.';
$string['archive:saving'] = 'Enregistrement de l’élément d’archive…';
$string['archive:saved'] = 'Élément d’archive enregistré.';
$string['archive:savefailed'] = 'Impossible d’enregistrer l’élément d’archive.';
$string['archive:validating'] = 'Validation de l’élément d’archive…';
$string['archive:validated'] = 'Élément d’archive validé.';
$string['archive:validationfailed'] = 'Impossible de valider l’élément d’archive.';
$string['archive:exporting'] = 'Génération de l’export d’archive…';
$string['archive:exported'] = 'Export d’archive généré.';
$string['archive:exportfailed'] = 'Impossible de générer l’export d’archive.';

$string['kristal:refreshing'] = 'Actualisation du Kristal…';
$string['kristal:refreshed'] = 'Kristal actualisé.';
$string['kristal:refreshfailed'] = 'Impossible d’actualiser le Kristal.';
$string['kristal:saving'] = 'Enregistrement du Kristal…';
$string['kristal:saved'] = 'Kristal enregistré.';
$string['kristal:savefailed'] = 'Impossible d’enregistrer le Kristal.';

// -----------------------------------------------------------------------------
// Achèvement.
// -----------------------------------------------------------------------------

$string['completiondetail:items'] = 'Ajouter un élément d’archive';
$string['completiondetail:validateditems'] = 'Avoir un élément d’archive validé';
$string['completiondetail:kristals'] = 'Créer un Kristal';
$string['completionitems'] = 'Éléments d’archive requis';
$string['completionvalidateditems'] = 'Éléments d’archive validés requis';
$string['completionkristals'] = 'Kristals requis';

// -----------------------------------------------------------------------------
// Confidentialité.
// -----------------------------------------------------------------------------

$string['privacy:metadata'] = 'L’activité Archive UCKK stocke des traces d’archive, preuves, provenances, décisions de validation, révisions, Kristals et traces d’export.';
$string['privacy:metadata:uckkarchive'] = 'Paramètres de l’instance d’activité Archive.';
$string['privacy:metadata:uckkarchive_item'] = 'Éléments d’archive créés ou validés par des utilisateurs.';
$string['privacy:metadata:uckkarchive_item:userid'] = 'L’utilisateur associé à l’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:createdby'] = 'L’utilisateur qui a créé l’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:modifiedby'] = 'L’utilisateur qui a modifié l’élément d’archive en dernier.';
$string['privacy:metadata:uckkarchive_item:title'] = 'Le titre de l’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:summary'] = 'Le résumé de l’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:content'] = 'Le contenu de l’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:status'] = 'Le statut de l’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:visibility'] = 'La visibilité de l’élément d’archive.';
$string['privacy:metadata:uckkarchive_item:metadata'] = 'Les métadonnées supplémentaires de l’élément d’archive.';
$string['privacy:metadata:uckkarchive_kristal'] = 'Traces de Kristals créées à partir d’éléments d’archive ou de preuves d’apprentissage.';
$string['privacy:metadata:uckkarchive_proof'] = 'Traces de preuve associées aux éléments d’archive.';
$string['privacy:metadata:uckkarchive_prov'] = 'Traces de provenance associées aux éléments d’archive.';
$string['privacy:metadata:uckkarchive_rev'] = 'Traces de révision associées aux éléments d’archive.';
$string['privacy:metadata:uckkarchive_export'] = 'Traces de paquets d’export.';
$string['privacy:metadata:files'] = 'Fichiers attachés aux éléments d’archive, preuves, décisions, procès-verbaux, Kristals, portfolios ou exports d’intégrité.';
$string['privacy:path:archives'] = 'Traces d’archive UCKK';
$string['privacy:path:kristals'] = 'Kristals d’archive UCKK';
$string['privacy:path:proofs'] = 'Preuves d’archive UCKK';
$string['privacy:path:revisions'] = 'Révisions d’archive UCKK';
$string['privacy:path:exports'] = 'Exports d’archive UCKK';

// -----------------------------------------------------------------------------
// Sauvegarde et restauration.
// -----------------------------------------------------------------------------

$string['backupincludeitems'] = 'Inclure les éléments d’archive';
$string['backupincludeproofs'] = 'Inclure les fichiers de preuve';
$string['backupincludekristals'] = 'Inclure les Kristals';
$string['backupincludeprovenance'] = 'Inclure la provenance';
$string['backupincluderevisions'] = 'Inclure l’historique des révisions';
$string['restorearchiveitems'] = 'Restaurer les éléments d’archive';

// -----------------------------------------------------------------------------
// Erreurs.
// -----------------------------------------------------------------------------

$string['error:missingcontext'] = 'Contexte d’archive manquant.';
$string['error:missingcourse'] = 'Cours manquant.';
$string['error:missingcm'] = 'Module de cours manquant.';
$string['error:missingitem'] = 'Élément d’archive manquant.';
$string['error:missingarchive'] = 'Archive manquante.';
$string['error:missingkristal'] = 'Kristal manquant.';
$string['error:missingexport'] = 'Paquet d’export manquant.';
$string['error:invalidjson'] = 'Métadonnées JSON invalides.';
$string['error:invalidsource'] = 'Référence source invalide.';
$string['error:invalidfilearea'] = 'Zone de fichiers d’archive invalide.';
$string['error:restricted'] = 'Cet élément d’archive est restreint.';
$string['error:publicrequiresvalidation'] = 'Les éléments d’archive publics exigent une validation manuelle.';
$string['error:cannotautomatevalidation'] = 'La validation d’archive ne peut pas être automatisée.';
$string['error:cannotdeletehistory'] = 'L’historique d’archive ne peut pas être supprimé silencieusement.';
$string['error:exporttoolarge'] = 'Ce paquet d’export est trop volumineux.';
$string['error:nothingtoexport'] = 'Il n’y a rien à exporter.';

// -----------------------------------------------------------------------------
// Zones de fichiers.
// -----------------------------------------------------------------------------

$string['filearea:proof_files'] = 'Fichiers de preuve';
$string['filearea:decision_attachments'] = 'Pièces jointes de décision';
$string['filearea:minutes_files'] = 'Fichiers de procès-verbal';
$string['filearea:kristal_files'] = 'Fichiers de Kristal';
$string['filearea:portfolio_files'] = 'Fichiers de portfolio';
$string['filearea:integrity_exports'] = 'Exports d’intégrité';
$string['filearea:item_content'] = 'Contenu d’élément d’archive';
$string['filearea:export_packages'] = 'Paquets d’export';

// -----------------------------------------------------------------------------
// Rapports et filtres.
// -----------------------------------------------------------------------------

$string['filter:itemtype'] = 'Filtrer par type d’élément';
$string['filter:status'] = 'Filtrer par statut';
$string['filter:visibility'] = 'Filtrer par visibilité';
$string['filter:validationstate'] = 'Filtrer par état de validation';
$string['filter:provenance'] = 'Filtrer par provenance';
$string['filter:createdby'] = 'Filtrer par créateur';
$string['filter:datefrom'] = 'Date de début';
$string['filter:dateto'] = 'Date de fin';
$string['report:archiveproduction'] = 'Production d’archives';
$string['report:validateditems'] = 'Éléments validés';
$string['report:pendingitems'] = 'Éléments en attente';
$string['report:restricteditems'] = 'Éléments restreints';
$string['report:exports'] = 'Exports';

// -----------------------------------------------------------------------------
// UI divers.
// -----------------------------------------------------------------------------

$string['confirmvalidate'] = 'Valider cet élément d’archive ?';
$string['confirmreject'] = 'Rejeter cet élément d’archive ?';
$string['confirminvalidate'] = 'Invalider cet élément d’archive ?';
$string['confirmrevision'] = 'Créer une nouvelle révision pour cet élément d’archive ?';
$string['confirmexport'] = 'Générer cet export d’archive ?';
$string['confirmpublicvisibility'] = 'Rendre cet élément d’archive public après validation ?';
$string['yesvalidate'] = 'Oui, valider';
$string['yesreject'] = 'Oui, rejeter';
$string['yesinvalidate'] = 'Oui, invalider';
$string['yesrevise'] = 'Oui, réviser';
$string['yesexport'] = 'Oui, exporter';

