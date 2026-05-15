<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'UCKK AI provider';
$string['privacy:metadata'] = 'The UCKK AI provider may store prompt and response logs when logging is enabled by site configuration.';
$string['privacy:metadata:log'] = 'AI prompt and response logs.';
$string['privacy:metadata:log:userid'] = 'The user who requested the AI action.';
$string['privacy:metadata:log:contextid'] = 'The Moodle context where the AI action was requested.';
$string['privacy:metadata:log:action'] = 'The UCKK AI action requested.';
$string['privacy:metadata:log:prompt'] = 'The prompt sent to the configured AI service, depending on redaction settings.';
$string['privacy:metadata:log:response'] = 'The AI response returned by the configured AI service, depending on logging settings.';
$string['privacy:metadata:log:model'] = 'The provider model used for the AI action.';
$string['privacy:metadata:log:timecreated'] = 'The time when the AI request was created.';
$string['privacy:pathlogs'] = 'UCKK AI logs';

$string['settings'] = 'UCKK AI provider settings';
$string['settings:general'] = 'General settings';
$string['settings:general_desc'] = 'Configure the governed AI bridge for UCKK-Moodle.';
$string['settings:enable_provider'] = 'Enable UCKK AI provider';
$string['settings:enable_provider_desc'] = 'Allow authorized users to request governed AI assistance.';
$string['settings:provider_endpoint'] = 'Provider endpoint';
$string['settings:provider_endpoint_desc'] = 'The endpoint used by the configured external AI service.';
$string['settings:provider_model'] = 'Provider model';
$string['settings:provider_model_desc'] = 'The model identifier used for UCKK AI requests.';
$string['settings:api_key'] = 'API key';
$string['settings:api_key_desc'] = 'Secret key used to authenticate with the configured AI provider.';
$string['settings:log_prompts'] = 'Log prompts';
$string['settings:log_prompts_desc'] = 'Store prompts sent to the AI provider for audit and review.';
$string['settings:log_responses'] = 'Log responses';
$string['settings:log_responses_desc'] = 'Store responses returned by the AI provider for audit and review.';
$string['settings:allow_in_integrity_contexts'] = 'Allow in integrity contexts';
$string['settings:allow_in_integrity_contexts_desc'] = 'Allow AI assistance in integrity-related workflows. AI still cannot validate integrity, close cases, or make final decisions.';
$string['settings:allow_in_public_challenges'] = 'Allow in public challenges';
$string['settings:allow_in_public_challenges_desc'] = 'Allow AI assistance in public challenge contexts when users have the required capability.';
$string['settings:redact_user_data_before_send'] = 'Redact user data before sending';
$string['settings:redact_user_data_before_send_desc'] = 'Attempt to remove directly identifying user data before sending prompts to the configured AI service.';
$string['settings:max_tokens'] = 'Maximum tokens';
$string['settings:max_tokens_desc'] = 'Maximum response size requested from the AI provider.';
$string['settings:retention_days'] = 'Log retention days';
$string['settings:retention_days_desc'] = 'Number of days to keep AI logs before retention cleanup.';

$string['action:summarise_course_material'] = 'Summarize course material';
$string['action:map_problem'] = 'Map a problem';
$string['action:extract_uncertainties'] = 'Extract uncertainties';
$string['action:draft_reflection'] = 'Draft a reflection';
$string['action:summarise_assembly'] = 'Summarize assembly discussion';
$string['action:critique_ai_output'] = 'Critique AI output';
$string['action:prepare_integrity_review'] = 'Prepare integrity review';

$string['actiondesc:summarise_course_material'] = 'Create a non-authoritative summary of selected course material.';
$string['actiondesc:map_problem'] = 'Organize a problem into actors, forces, assumptions, risks, and possible corridors of action.';
$string['actiondesc:extract_uncertainties'] = 'Identify claims, missing evidence, unresolved questions, and uncertainty signals.';
$string['actiondesc:draft_reflection'] = 'Draft a reflective text that the user must verify and revise.';
$string['actiondesc:summarise_assembly'] = 'Summarize assembly discussion without replacing minutes, votes, or final decisions.';
$string['actiondesc:critique_ai_output'] = 'Review an AI-generated text for unsupported claims, hidden assumptions, and factual uncertainty.';
$string['actiondesc:prepare_integrity_review'] = 'Prepare a structured review brief for an authorized human integrity reviewer.';

$string['outputlabel'] = 'AI-assisted draft. Not a final authority. Validate facts, evidence, and decisions before use.';
$string['nonsovereignnotice'] = 'AI can assist, summarize, draft, compare, and identify uncertainty. It cannot grade final work, validate integrity, close cases, publish assembly decisions, award badges, certify competencies, erase evidence, or replace human review.';
$string['restrictedcontextnotice'] = 'This context is restricted. AI assistance is available only when explicitly enabled and permissioned.';
$string['integritycontextnotice'] = 'AI may help prepare an integrity review brief, but only an authorized human reviewer can record an integrity decision.';
$string['publicchallengenotice'] = 'Public challenge AI assistance must not fabricate evidence, target individuals, enable harassment, or confuse fiction with fact.';

$string['request'] = 'Request AI assistance';
$string['response'] = 'AI response';
$string['prompt'] = 'Prompt';
$string['model'] = 'Model';
$string['action'] = 'Action';
$string['context'] = 'Context';
$string['generatedat'] = 'Generated at';
$string['generatedby'] = 'Generated by';
$string['copyresponse'] = 'Copy response';
$string['clearresponse'] = 'Clear response';
$string['viewlogs'] = 'View AI logs';
$string['logs'] = 'AI logs';
$string['nologs'] = 'No AI logs found.';
$string['redacted'] = 'Redacted';
$string['notredacted'] = 'Not redacted';

$string['error:providernotenabled'] = 'The UCKK AI provider is not enabled.';
$string['error:missingendpoint'] = 'The UCKK AI provider endpoint is not configured.';
$string['error:missingapikey'] = 'The UCKK AI provider API key is not configured.';
$string['error:unknownaction'] = 'Unknown UCKK AI action.';
$string['error:actionnotallowed'] = 'This AI action is not allowed in the current context.';
$string['error:integritydisabled'] = 'AI assistance is disabled in integrity contexts.';
$string['error:publicchallengedisabled'] = 'AI assistance is disabled in public challenge contexts.';
$string['error:providerrequestfailed'] = 'The AI provider request failed.';
$string['error:emptyresponse'] = 'The AI provider returned an empty response.';
$string['error:permissiondenied'] = 'You do not have permission to use the UCKK AI provider.';

// Additional settings and privacy strings discovered by the string inventory.
$string['privacy:path:ailogs'] = 'UCKK AI logs';
$string['privacy:promptloggingdisabled'] = 'Prompt logging is disabled. Stored prompt content is not available for export.';
$string['privacy:responseloggingdisabled'] = 'Response logging is disabled. Stored response content is not available for export.';
$string['settings:provider_apikey'] = 'Provider API key';
$string['settings:provider_apikey_desc'] = 'Secret key used to authenticate requests to the configured AI provider.';
$string['settings:governance'] = 'Governance settings';
$string['settings:governance_desc'] = 'Configure the non-sovereignty, human validation, and integrity boundaries for UCKK AI assistance.';
$string['settings:limits'] = 'Limits and retention';
$string['settings:limits_desc'] = 'Configure request limits, timeouts, and retention for UCKK AI assistance.';
$string['settings:require_non_authority_label'] = 'Require non-authority label';
$string['settings:require_non_authority_label_desc'] = 'Require every AI-assisted response to display a notice stating that AI output is not a final authority.';
$string['settings:request_timeout'] = 'Request timeout';
$string['settings:request_timeout_desc'] = 'Maximum number of seconds to wait for the configured AI provider before the request fails.';
$string['settings:actions'] = 'Available AI actions';
$string['settings:actions_desc'] = 'Enable or disable each governed UCKK AI action.';
$string['settings:action_summarise_course_material'] = 'Enable course material summaries';
$string['settings:action_summarise_course_material_desc'] = 'Allow authorized users to request non-authoritative summaries of selected course material.';
$string['settings:action_map_problem'] = 'Enable problem mapping';
$string['settings:action_map_problem_desc'] = 'Allow authorized users to map a problem into actors, assumptions, risks, forces, and possible corridors of action.';
$string['settings:action_extract_uncertainties'] = 'Enable uncertainty extraction';
$string['settings:action_extract_uncertainties_desc'] = 'Allow authorized users to identify claims, missing evidence, unresolved questions, and uncertainty signals.';
$string['settings:action_draft_reflection'] = 'Enable reflection drafting';
$string['settings:action_draft_reflection_desc'] = 'Allow authorized users to draft reflective text that must be verified and revised by the user.';
$string['settings:action_summarise_assembly'] = 'Enable assembly discussion summaries';
$string['settings:action_summarise_assembly_desc'] = 'Allow authorized users to summarize assembly discussion without replacing minutes, votes, or final decisions.';
$string['settings:action_critique_ai_output'] = 'Enable AI output critique';
$string['settings:action_critique_ai_output_desc'] = 'Allow authorized users to review AI-generated text for unsupported claims, hidden assumptions, and factual uncertainty.';
$string['settings:action_prepare_integrity_review'] = 'Enable integrity review preparation';
$string['settings:action_prepare_integrity_review_desc'] = 'Allow authorized users to prepare a structured brief for an authorized human integrity reviewer.';

$string['aiprovider/uckk:configure'] = 'Configure the UCKK AI provider';
$string['aiprovider/uckk:use'] = 'Use the UCKK AI provider';
$string['aiprovider/uckk:viewlogs'] = 'View UCKK AI logs';