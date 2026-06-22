@local @local_uckk @uckk @faculty @faculty_sync_dryrun
Feature: UCKK faculty Atlas Moodle sync dry-run
  In order to preview Moodle changes required by Atlas and Faculty JSON without mutating Moodle data
  As a UCKK sync administrator
  I need the faculty sync dry-run to produce a safe report and never apply changes

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email                  |
      | syncadmin | Sync      | Admin    | syncadmin@example.test |
      | player1   | Public    | Player   | player1@example.test   |
    And the following "role assigns" exist:
      | user      | role    | contextlevel | reference |
      | syncadmin | manager | System       |           |
    And the following "categories" exist:
      | name                              | category | idnumber |
      | UCKK Grand Jeu social             | 0        | UCKK-GJS |
      | UCKK Grand Jeu social à corriger  | 0        | UCKK-GJS-OLD |
    And the following "courses" exist:
      | fullname                                      | shortname | idnumber | category     | format | visible |
      | GJS101 — Cartographie du Grand Jeu social     | GJS101    | GJS101   | UCKK-GJS     | topics | 1       |
      | GJS102 — Ancien titre manuel                  | GJS102    | GJS102   | UCKK-GJS     | topics | 1       |
    And I purge all Moodle caches

  @javascript
  Scenario: Anonymous visitors cannot open the sync dry-run page
    When I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    Then I should not see "Synchronisation Atlas Moodle"
    And I should not see "Dry-run"
    And I should not see "Lancer le dry-run"
    And ".local-uckk-faculty-sync" "css_element" should not exist

  @javascript
  Scenario: Logged-in users without sync capability cannot open the sync dry-run page
    Given I log in as "player1"
    When I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    Then I should not see "Synchronisation Atlas Moodle"
    And I should not see "Dry-run"
    And I should not see "Lancer le dry-run"
    And ".local-uckk-faculty-sync" "css_element" should not exist

  @javascript
  Scenario: A sync administrator can open the sync dry-run page
    Given I log in as "syncadmin"
    When I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    Then I should see "Synchronisation Atlas Moodle"
    And I should see "Dry-run"
    And I should see "Rapport de synchronisation"
    And I should see "Lancer le dry-run"
    And ".local-uckk-faculty-sync" "css_element" should exist
    And "form[action*=\"faculty_sync.php\"]" "css_element" should exist

  @javascript
  Scenario: A sync administrator can run a global dry-run
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "Dry-run terminé"
    And I should see "Rapport de synchronisation"
    And I should see "mode"
    And I should see "dry_run"
    And I should see "status"
    And I should see "counts"
    And I should see "hash"
    And I should not see "Coding error"
    And I should not see "Exception"
    And I should not see "Debug info"

  @javascript
  Scenario: A global dry-run reports categories that would be created
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "catégories à créer"
    And I should see "UCKK-EC"
    And I should see "UCKK-ECL"
    And I should see "UCKK-SP"
    And I should see "UCKK-LI"
    And I should see "UCKK-ME"
    And I should see "UCKK-IA"
    And I should see "UCKK-IS"
    And I should see "UCKK-AS"
    And I should see "UCKK-KOA"

  @javascript
  Scenario: A global dry-run reports courses that would be created
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "cours à créer"
    And I should see "GJS103"
    And I should see "GJS104"
    And I should see "GJS105"
    And I should see "GJS106"
    And I should see "GJS107"
    And I should see "GJS108"
    And I should see "GJS109"
    And I should see "GJS110"
    And I should see "EC101"
    And I should see "ECL101"

  @javascript
  Scenario: A global dry-run reports courses that would be updated
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "cours à mettre à jour"
    And I should see "GJS102"
    And I should see "Ancien titre manuel"
    And I should see "changed_in_moodle"
    And I should see "diffs de hash"

  @javascript
  Scenario: A global dry-run reports missing custom fields and badges
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "champs custom manquants"
    And I should see "uckk_faculty_id"
    And I should see "uckk_voie_id"
    And I should see "uckk_cours_id"
    And I should see "uckk_sync_status"
    And I should see "badges à créer"
    And I should see "UCKK-BADGE-GJS-PO"

  @javascript
  Scenario: A global dry-run reports validation errors without applying changes
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "erreurs de validation"
    And I should see "missing_in_moodle"
    And I should see "not_synced"
    And I should see "Aucune modification appliquée"
    And I should not see "Sync appliquée"
    And I should not see "Moodle modifié"

  @javascript
  Scenario: A single faculty can be dry-run by slug
    Given I log in as "syncadmin"
    When I am on "/local/uckk/faculty_sync.php?mode=dryrun&slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should see "Dry-run terminé"
    And I should see "grand-jeu-social"
    And I should see "faculty_grand_jeu_social"
    And I should see "voie_grand_jeu_social"
    And I should see "UCKK-GJS"
    And I should see "GJS"
    And I should see "GJS101"
    And I should not see "faculty_economie"
    And I should not see "voie_economie"
    And I should not see "UCKK-EC"

  @javascript
  Scenario: Dry-run by slug rejects file-path-like values
    Given I log in as "syncadmin"
    When I am on "/local/uckk/faculty_sync.php?mode=dryrun&slug=../content/faculties/grand-jeu-social.faculty"
    And I wait until the page is ready
    Then I should not see "Dry-run terminé"
    And I should not see "faculty_grand_jeu_social"
    And I should not see "voie_grand_jeu_social"
    And I should not see "GJS101"
    And ".local-uckk-faculty-sync-report" "css_element" should not exist

  @javascript
  Scenario: Dry-run by voie rejects file-path-like values
    Given I log in as "syncadmin"
    When I am on "/local/uckk/faculty_sync.php?mode=dryrun&voie=../atlas/voies/voie_grand_jeu_social"
    And I wait until the page is ready
    Then I should not see "Dry-run terminé"
    And I should not see "faculty_grand_jeu_social"
    And I should not see "voie_grand_jeu_social"
    And I should not see "GJS101"
    And ".local-uckk-faculty-sync-report" "css_element" should not exist

  @javascript
  Scenario: Dry-run does not expose complete raw Atlas or Faculty JSON
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "Rapport de synchronisation"
    And I should not see "\"cours_conceptuels\""
    And I should not see "\"concepts_associes\""
    And I should not see "\"criteres_passage\""
    And I should not see "\"dynamic_blocks\""
    And I should not see "\"public_claims_guardrails\""
    And I should not see "{"
    And I should not see "}"

  @javascript
  Scenario: Dry-run report does not expose learner-specific Moodle data
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "Rapport de synchronisation"
    And I should not see "player1@example.test"
    And I should not see "Public Player"
    And I should not see "Participants"
    And I should not see "Progression personnelle"
    And I should not see "Statut d’achèvement privé"
    And I should not see "Note personnelle"
    And I should not see "Badge personnel obtenu"
    And I should not see "Données privées d’étudiant"

  @javascript
  Scenario: Dry-run report exposes safe sync statuses only
    Given I log in as "syncadmin"
    And I am on "/local/uckk/faculty_sync.php"
    And I wait until the page is ready
    When I press "Lancer le dry-run"
    And I wait until the page is ready
    Then I should see "not_synced"
    And I should see "changed_in_moodle"
    And I should see "missing_in_moodle"
    And I should not see "Sync appliquée"
    And I should not see "apply"
    And I should not see "write"
    And I should not see "delete"

  @javascript
  Scenario: The dry-run page does not provide an apply action to unauthorized users
    Given I log in as "player1"
    When I am on "/local/uckk/faculty_sync.php?mode=apply&slug=grand-jeu-social"
    And I wait until the page is ready
    Then I should not see "Sync appliquée"
    And I should not see "GJS103"
    And I should not see "cours créé"
    And ".local-uckk-faculty-sync-report" "css_element" should not exist

  @javascript
  Scenario: The dry-run page never applies sync from a public page route
    When I am on "/local/uckk/faculty.php?slug=grand-jeu-social&mode=apply"
    And I wait until the page is ready
    Then I should see "Voie du Grand Jeu social"
    And I should not see "Synchronisation Atlas Moodle"
    And I should not see "Sync appliquée"
    And I should not see "cours créé"
    And ".local-uckk-faculty-sync-report" "css_element" should not exist