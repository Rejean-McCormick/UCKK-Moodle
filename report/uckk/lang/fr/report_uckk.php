<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Rapports UCKK';

$string['settings'] = 'Paramètres des rapports UCKK';
$string['settings:allowjsonexport'] = 'Autoriser l’export JSON';
$string['settings:allowjsonexport_desc'] = 'Permettre aux utilisateurs autorisés d’exporter les lignes de rapport en JSON pour l’audit interne.';
$string['settings:showemptyreports'] = 'Afficher les cartes de rapport vides';
$string['settings:showemptyreports_desc'] = 'Afficher les cartes même lorsque la table source ne contient pas encore de ligne.';
$string['settings:defaultlimit'] = 'Limite de lignes par défaut';
$string['settings:defaultlimit_desc'] = 'Nombre maximal de lignes affichées ou exportées par défaut.';

$string['dashboard'] = 'Tableau de bord des rapports institutionnels';
$string['reports'] = 'Rapports';
$string['report'] = 'Rapport';
$string['filters'] = 'Filtres';
$string['applyfilters'] = 'Appliquer les filtres';
$string['clearfilters'] = 'Effacer les filtres';
$string['exportcsv'] = 'Exporter CSV';
$string['exportjson'] = 'Exporter JSON';
$string['exporthtml'] = 'Vue HTML';
$string['norows'] = 'Aucun enregistrement ne correspond aux filtres sélectionnés.';
$string['totalrows'] = '{$a} lignes correspondantes';
$string['viewreport'] = 'Voir le rapport';
$string['activefilters'] = 'Filtres actifs';
$string['all'] = 'Tous';
$string['from'] = 'Depuis l’horodatage';
$string['to'] = 'Jusqu’à l’horodatage';
$string['limit'] = 'Limite';
$string['status'] = 'Statut';
$string['visibility'] = 'Visibilité';
$string['user'] = 'ID utilisateur';
$string['cohort'] = 'ID cohorte';
$string['program'] = 'ID programme';
$string['course'] = 'ID cours';
$string['category'] = 'ID catégorie';
$string['competency'] = 'ID compétence';
$string['badge'] = 'ID badge';
$string['challengetype'] = 'Type de défi';
$string['assemblytype'] = 'Type d’assemblée';
$string['integritytype'] = 'Type d’intégrité';

$string['privacy:metadata'] = 'Le plugin de rapports UCKK ne stocke aucune donnée personnelle primaire. Il affiche des données dérivées d’autres composants UCKK.';

$string['eventreportexported'] = 'Rapport UCKK exporté';

$string['notavailable'] = 'Non disponible';
$string['notinstalled'] = 'La table source requise n’est pas encore installée.';
$string['unknownreport'] = 'Rapport inconnu.';
$string['unknown'] = 'Inconnu';
$string['generatedat'] = 'Généré le';
$string['generatedby'] = 'Généré par';
$string['source'] = 'Source';
$string['summary'] = 'Résumé';
$string['count'] = 'Nombre';
$string['records'] = 'Enregistrements';
$string['actions'] = 'Actions';

$string['report:player_progress'] = 'Progression des Joueurs';
$string['report:cohort_progress'] = 'Progression des cohortes';
$string['report:program_progress'] = 'Progression des programmes';
$string['report:competency_matrix'] = 'Matrice des compétences';
$string['report:badge_awards'] = 'Attribution des badges';
$string['report:challenge_status'] = 'Statut des défis';
$string['report:assembly_decisions'] = 'Décisions des Assemblées';
$string['report:archive_production'] = 'Production des Archives';
$string['report:integrity_cases'] = 'Dossiers d’intégrité';

$string['reportdesc:player_progress'] = 'Parcours, portfolio, visibilité du profil et indicateurs d’intégrité des Joueurs.';
$string['reportdesc:cohort_progress'] = 'Effectifs des cohortes et contexte de progression.';
$string['reportdesc:program_progress'] = 'Programmes UCKK, catégories, parcours et statut.';
$string['reportdesc:competency_matrix'] = 'Preuves de compétences et niveaux dans le référentiel UCKK.';
$string['reportdesc:badge_awards'] = 'Nombre d’attributions de badges et dates d’attribution.';
$string['reportdesc:challenge_status'] = 'Statut des Défis, soumissions, validations et indicateurs d’intégrité.';
$string['reportdesc:assembly_decisions'] = 'Motions, décisions, contestabilité et liens d’archive des Assemblées.';
$string['reportdesc:archive_production'] = 'Éléments d’archive par type, état de validation, visibilité et source de provenance.';
$string['reportdesc:integrity_cases'] = 'Dossiers de l’Inquisiteur par type, gravité, statut, assignation et résumé d’archive.';

$string['column:id'] = 'ID';
$string['column:name'] = 'Nom';
$string['column:user'] = 'Utilisateur';
$string['column:email'] = 'Courriel';
$string['column:course'] = 'Cours';
$string['column:category'] = 'Catégorie';
$string['column:cohort'] = 'Cohorte';
$string['column:program'] = 'Programme';
$string['column:pathways'] = 'Parcours';
$string['column:portfolio'] = 'Archive du portfolio';
$string['column:integrity'] = 'Indicateurs d’intégrité';
$string['column:visibility'] = 'Visibilité';
$string['column:status'] = 'Statut';
$string['column:total'] = 'Total';
$string['column:members'] = 'Membres';
// Additional report column strings discovered by the string inventory.
$string['column:players'] = 'Joueurs';
$string['column:programtype'] = 'Type de programme';
$string['column:courses'] = 'Cours';
$string['column:competency'] = 'Compétence';
$string['column:rating'] = 'Niveau';
$string['column:ratings'] = 'Évaluations';
$string['column:badge'] = 'Badge';
$string['column:issued'] = 'Attribué';
$string['column:lastissued'] = 'Dernière attribution';
$string['column:challenge'] = 'Défi';
$string['column:challengetype'] = 'Type de défi';
$string['column:submissions'] = 'Soumissions';
$string['column:validated'] = 'Validées';
$string['column:contested'] = 'Contestées';
$string['column:assembly'] = 'Assemblée';
$string['column:assemblytype'] = 'Type d’assemblée';
$string['column:motions'] = 'Motions';
$string['column:decisions'] = 'Décisions';
$string['column:archiveitem'] = 'Élément d’archive';
$string['column:itemtype'] = 'Type d’élément';
$string['column:sourcecomponent'] = 'Composant source';
$string['column:validationstate'] = 'État de validation';
$string['column:casetype'] = 'Type de dossier';
$string['column:severity'] = 'Gravité';
$string['column:assignedto'] = 'Assigné à';
$string['column:openedby'] = 'Ouvert par';
$string['column:created'] = 'Créé';
$string['column:modified'] = 'Modifié';
$string['column:archive'] = 'Archive';

$string['report/uckk:view'] = 'Voir les rapports UCKK';
$string['report/uckk:viewall'] = 'Voir toutes les données des rapports UCKK';
$string['report/uckk:export'] = 'Exporter les rapports UCKK';