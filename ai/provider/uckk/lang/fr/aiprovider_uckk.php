<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Fournisseur IA UCKK';
$string['privacy:metadata'] = 'Le fournisseur IA UCKK peut stocker des journaux de requêtes et de réponses lorsque la journalisation est activée.';
$string['privacy:metadata:aiprovider_uckk_log'] = 'Journal des requêtes et réponses IA UCKK.';
$string['privacy:metadata:aiprovider_uckk_log:userid'] = 'L’utilisateur ayant demandé l’action IA.';
$string['privacy:metadata:aiprovider_uckk_log:contextid'] = 'Le contexte Moodle dans lequel l’action IA a été demandée.';
$string['privacy:metadata:aiprovider_uckk_log:actionname'] = 'Le nom de l’action IA demandée.';
$string['privacy:metadata:aiprovider_uckk_log:prompttext'] = 'Le texte transmis au fournisseur IA.';
$string['privacy:metadata:aiprovider_uckk_log:responsetext'] = 'La réponse retournée par le fournisseur IA.';
$string['privacy:metadata:aiprovider_uckk_log:metadata'] = 'Métadonnées techniques de la requête IA.';
$string['privacy:metadata:aiprovider_uckk_log:timecreated'] = 'La date de création du journal IA.';
$string['privacy:pathlogs'] = 'Journaux IA UCKK';

$string['settings'] = 'Paramètres du fournisseur IA UCKK';
$string['settings_desc'] = 'Configurer le pont IA gouverné utilisé par UCKK-Moodle.';
$string['enable_provider'] = 'Activer le fournisseur IA UCKK';
$string['enable_provider_desc'] = 'Permettre aux actions IA UCKK d’être utilisées par les utilisateurs autorisés.';
$string['provider_endpoint'] = 'Point d’accès du fournisseur';
$string['provider_endpoint_desc'] = 'URL du service IA externe utilisé par UCKK-Moodle.';
$string['provider_model'] = 'Modèle du fournisseur';
$string['provider_model_desc'] = 'Nom du modèle IA à utiliser par défaut.';
$string['api_key'] = 'Clé API';
$string['api_key_desc'] = 'Clé d’authentification utilisée pour communiquer avec le fournisseur IA externe.';
$string['log_prompts'] = 'Journaliser les requêtes';
$string['log_prompts_desc'] = 'Conserver les textes envoyés au fournisseur IA, selon la politique de confidentialité du site.';
$string['log_responses'] = 'Journaliser les réponses';
$string['log_responses_desc'] = 'Conserver les réponses retournées par le fournisseur IA, selon la politique de confidentialité du site.';
$string['allow_in_integrity_contexts'] = 'Autoriser l’IA dans les contextes d’intégrité';
$string['allow_in_integrity_contexts_desc'] = 'Permettre l’assistance IA dans les dossiers d’intégrité. Les réponses restent non décisionnelles et doivent être validées humainement.';
$string['allow_in_public_challenges'] = 'Autoriser l’IA dans les défis publics';
$string['allow_in_public_challenges_desc'] = 'Permettre l’assistance IA dans les défis publics lorsque les règles du défi l’autorisent.';
$string['redact_user_data_before_send'] = 'Caviarder les données utilisateur avant envoi';
$string['redact_user_data_before_send_desc'] = 'Réduire ou masquer les données personnelles avant de transmettre une requête au fournisseur IA externe.';
$string['max_tokens'] = 'Nombre maximal de jetons';
$string['max_tokens_desc'] = 'Limite maximale de génération pour une réponse IA.';
$string['retention_days'] = 'Durée de conservation des journaux';
$string['retention_days_desc'] = 'Nombre de jours pendant lesquels les journaux IA sont conservés avant suppression ou anonymisation.';

$string['aiprovider/uckk:configure'] = 'Configurer le fournisseur IA UCKK';
$string['aiprovider/uckk:use'] = 'Utiliser les actions IA UCKK';
$string['aiprovider/uckk:viewlogs'] = 'Voir les journaux IA UCKK';

$string['action:summarise_course_material'] = 'Résumer le matériel de cours';
$string['action:map_problem'] = 'Cartographier un problème';
$string['action:extract_uncertainties'] = 'Extraire les incertitudes';
$string['action:draft_reflection'] = 'Rédiger un brouillon de réflexion';
$string['action:summarise_assembly'] = 'Résumer une assemblée';
$string['action:critique_ai_output'] = 'Critiquer une sortie IA';
$string['action:prepare_integrity_review'] = 'Préparer une révision d’intégrité';

$string['actiondesc:summarise_course_material'] = 'Produire un résumé de soutien pour du matériel de cours.';
$string['actiondesc:map_problem'] = 'Aider à représenter les acteurs, flux, contraintes, preuves et zones d’incertitude d’un problème.';
$string['actiondesc:extract_uncertainties'] = 'Repérer les affirmations fragiles, hypothèses, ambiguïtés et informations manquantes.';
$string['actiondesc:draft_reflection'] = 'Produire un brouillon de réflexion que le Joueur doit réviser, vérifier et assumer.';
$string['actiondesc:summarise_assembly'] = 'Résumer les motions, arguments, objections, lectures et points de décision d’une Assemblée.';
$string['actiondesc:critique_ai_output'] = 'Analyser une réponse IA afin d’en repérer les limites, erreurs possibles, angles morts et affirmations non vérifiées.';
$string['actiondesc:prepare_integrity_review'] = 'Préparer une synthèse non décisionnelle pour aider un Inquisiteur à examiner un dossier.';

$string['non_authority_label'] = 'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.';
$string['non_authority_short'] = 'Contenu assisté par IA — non final.';
$string['human_review_required'] = 'Validation humaine requise';
$string['human_review_required_desc'] = 'Cette sortie IA ne peut pas être utilisée comme décision, note, sanction, validation d’intégrité ou preuve finale.';

$string['cannot_grade'] = 'L’IA ne peut pas attribuer une note finale.';
$string['cannot_validate_integrity'] = 'L’IA ne peut pas valider un dossier d’intégrité.';
$string['cannot_close_integrity_case'] = 'L’IA ne peut pas fermer un dossier d’intégrité.';
$string['cannot_publish_assembly_decision'] = 'L’IA ne peut pas publier une décision d’Assemblée.';
$string['cannot_award_badge'] = 'L’IA ne peut pas attribuer un badge.';
$string['cannot_certify_competency'] = 'L’IA ne peut pas certifier une compétence.';
$string['cannot_erase_evidence'] = 'L’IA ne peut pas effacer une preuve.';
$string['cannot_replace_human_review'] = 'L’IA ne peut pas remplacer la révision humaine.';

$string['request'] = 'Requête IA';
$string['response'] = 'Réponse IA';
$string['prompt'] = 'Invite';
$string['prompt_redacted'] = 'Invite caviardée';
$string['response_redacted'] = 'Réponse caviardée';
$string['logs'] = 'Journaux IA';
$string['viewlogs'] = 'Voir les journaux IA';
$string['nologs'] = 'Aucun journal IA trouvé.';
$string['actionname'] = 'Action IA';
$string['model'] = 'Modèle';
$string['endpoint'] = 'Point d’accès';
$string['status'] = 'Statut';
$string['timecreated'] = 'Créé le';
$string['timemodified'] = 'Modifié le';
$string['userid'] = 'ID utilisateur';
$string['context'] = 'Contexte';
$string['metadata'] = 'Métadonnées';
$string['duration'] = 'Durée';
$string['tokens'] = 'Jetons';
$string['prompttokens'] = 'Jetons de requête';
$string['responsetokens'] = 'Jetons de réponse';

$string['status:success'] = 'Succès';
$string['status:error'] = 'Erreur';
$string['status:blocked'] = 'Bloqué';
$string['status:redacted'] = 'Caviardé';

$string['error:providernotenabled'] = 'Le fournisseur IA UCKK n’est pas activé.';
$string['error:missingendpoint'] = 'Le point d’accès du fournisseur IA n’est pas configuré.';
$string['error:missingmodel'] = 'Le modèle IA n’est pas configuré.';
$string['error:missingapikey'] = 'La clé API du fournisseur IA n’est pas configurée.';
$string['error:actionnotallowed'] = 'Cette action IA n’est pas autorisée dans ce contexte.';
$string['error:integritycontextdisabled'] = 'L’utilisation de l’IA est désactivée dans les contextes d’intégrité.';
$string['error:publicchallengedisabled'] = 'L’utilisation de l’IA est désactivée dans les défis publics.';
$string['error:permissiondenied'] = 'Vous n’avez pas l’autorisation d’utiliser cette action IA.';
$string['error:providerrequestfailed'] = 'La requête au fournisseur IA a échoué.';
$string['error:invalidresponse'] = 'Le fournisseur IA a retourné une réponse invalide.';

$string['eventactionrequested'] = 'Action IA UCKK demandée';
$string['eventactioncompleted'] = 'Action IA UCKK terminée';
$string['eventactionblocked'] = 'Action IA UCKK bloquée';
$string['eventlogviewed'] = 'Journal IA UCKK consulté';

$string['confirm_use_title'] = 'Utiliser l’assistance IA';
$string['confirm_use_message'] = 'L’IA peut aider à clarifier, résumer ou préparer un brouillon, mais elle ne remplace pas la preuve, la méthode, la décision humaine ou la responsabilité institutionnelle.';
$string['submitprompt'] = 'Envoyer à l’IA';
$string['retry'] = 'Réessayer';
$string['copyresponse'] = 'Copier la réponse';
$string['downloadlog'] = 'Télécharger le journal';
$string['deleteexpiredlogs'] = 'Supprimer les journaux expirés';
$string['redactionenabled'] = 'Caviardage activé';
$string['redactiondisabled'] = 'Caviardage désactivé';