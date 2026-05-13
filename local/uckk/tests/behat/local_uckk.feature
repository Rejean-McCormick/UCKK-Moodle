@local @local_uckk
Feature: UCKK local campus registry
  In order to operate UCKK-Moodle as a coherent campus
  As a UCKK user
  I need the local UCKK registry to expose programs, pathways, profiles, canon, visibility, and restricted data safely

  Background:
    Given the following "users" exist:
      | username       | firstname | lastname    | email                    |
      | uckkplayer1    | Ariane    | Joueur      | ariane@example.test      |
      | uckkplayer2    | Basile    | Observateur | basile@example.test      |
      | uckkmanager1   | Camille   | Gestionnaire| camille@example.test     |

    And the following UCKK programs exist:
      | shortname       | fullname                         | programtype    | status | visibility  | sortorder |
      | tronc_commun    | Tronc commun obligatoire          | tronc_commun   | active | institution | 10        |
      | grand_jeu_social| Grand Jeu social                  | baccalaureat   | active | institution | 20        |
      | ia_gouvernable  | Intelligence artificielle gouvernable | baccalaureat | active | institution | 30        |

    And the following UCKK pathways exist:
      | program         | shortname             | fullname                    | status | visibility  |
      | tronc_commun    | parcours_tronc_commun | Parcours du tronc commun    | active | institution |
      | grand_jeu_social| parcours_gjs          | Parcours Grand Jeu social   | active | institution |
      | ia_gouvernable  | parcours_ai           | Parcours IA gouvernable     | active | institution |

    And the following UCKK player profiles exist:
      | username     | displaytitle   | symbolicroles                 | activepathways          | visibility | status |
      | uckkplayer1  | Joueuse lucide  | joueur,joueur_lucide          | parcours_tronc_commun   | user       | active |
      | uckkplayer2  | Observateur     | joueur                        | parcours_tronc_commun   | private    | active |

    And the following UCKK canon entries exist:
      | shortname             | title                         | body                                                                                                      | status | visibility  |
      | governance_formula    | Formule de gouvernance         | King Klown attire. L’Inquisiteur vérifie. Les Assemblées légitiment. Les Bâtisseurs réalisent. L’Archiviste se souvient. | active | institution |
      | ai_formula            | Formule IA                     | L’IA aide. Elle ne décide pas.                                                                            | active | institution |
      | relationship_formula  | Relation kOA-UCKK              | kOA est le mouvement. UCKK est l’école. Le kOA Digital Ecosystem est l’infrastructure. King Klown est la figure narrative. | active | institution |

  @javascript
  Scenario: A Joueur can view the UCKK campus without management controls
    Given I am logged in as "uckkplayer1"
    When I am on the UCKK campus page
    Then I should see "UCKK-Moodle"
    And I should see "Univers-Cité King Klown"
    And I should see "Tronc commun obligatoire"
    And I should see "Grand Jeu social"
    And I should see "Intelligence artificielle gouvernable"
    And I should see "kOA est le mouvement. UCKK est l’école."
    And I should not see "Manage programs"
    And I should not see "Manage pathways"
    And I should not see "Manage integrations"
    And I should not see "official university degree"
    And I should not see "state-accredited bachelor's degree"

  @javascript
  Scenario: A Joueur can view their own UCKK profile and symbolic roles
    Given I am logged in as "uckkplayer1"
    When I am on my UCKK player profile page
    Then I should see "Ariane Joueur"
    And I should see "Joueuse lucide"
    And I should see "joueur"
    And I should see "joueur_lucide"
    And I should see "Parcours du tronc commun"
    And I should see "user"
    And I should not see "Manage UCKK player profiles"
    And the UCKK symbolic role "joueur_lucide" should not grant the Moodle capability "local/uckk:manageprofiles"

  Scenario: A Joueur cannot view another private UCKK profile
    Given I am logged in as "uckkplayer1"
    When I am on the UCKK player profile page for "uckkplayer2"
    Then I should see "Sorry, but you do not currently have permissions to do that"
    And I should not see "Basile Observateur"
    And I should not see "Observateur"

  @javascript
  Scenario: A manager can manage UCKK programs and pathways
    Given I am logged in as "admin"
    When I am on the UCKK campus page
    Then I should see "Manage programs"
    And I should see "Manage pathways"

    When I follow "Manage programs"
    Then I should see "UCKK programs"
    And I should see "Tronc commun obligatoire"
    And I should see "Grand Jeu social"
    And I should see "Intelligence artificielle gouvernable"

    When I follow "Manage pathways"
    Then I should see "UCKK pathways"
    And I should see "Parcours du tronc commun"
    And I should see "Parcours Grand Jeu social"
    And I should see "Parcours IA gouvernable"

  @javascript
  Scenario: A manager can view restricted UCKK profile information
    Given the UCKK player profile for "uckkplayer1" has the following integrity flags:
      | flag                  | summary                                      | visibility             |
      | pending_review         | Profile requires mentor review               | restricted_integrity   |
      | correction_required    | Evidence summary requires correction         | restricted_integrity   |

    And I am logged in as "admin"
    When I am on the UCKK player profile page for "uckkplayer1"
    Then I should see "Ariane Joueur"
    And I should see "Integrity markers"
    And I should see "Profile requires mentor review"
    And I should see "Evidence summary requires correction"

  @javascript
  Scenario: Restricted UCKK profile information is hidden from ordinary users
    Given the UCKK player profile for "uckkplayer1" has the following integrity flags:
      | flag                  | summary                                      | visibility             |
      | pending_review         | Profile requires mentor review               | restricted_integrity   |
      | correction_required    | Evidence summary requires correction         | restricted_integrity   |

    And I am logged in as "uckkplayer1"
    When I am on my UCKK player profile page
    Then I should see "Ariane Joueur"
    And I should not see "Integrity markers"
    And I should not see "Profile requires mentor review"
    And I should not see "Evidence summary requires correction"

  @javascript
  Scenario: UCKK canon panels preserve institutional boundaries
    Given I am logged in as "uckkplayer1"
    When I am on the UCKK canon page
    Then I should see "Formule de gouvernance"
    And I should see "King Klown attire. L’Inquisiteur vérifie."
    And I should see "Formule IA"
    And I should see "L’IA aide. Elle ne décide pas."
    And I should see "Relation kOA-UCKK"
    And I should see "kOA est le mouvement. UCKK est l’école."
    And I should not see "King Klown is the institutional sovereign"
    And I should not see "AI final authority"

  Scenario: A non-manager cannot access UCKK program administration
    Given I am logged in as "uckkplayer1"
    When I am on the UCKK program management page
    Then I should see "Sorry, but you do not currently have permissions to do that"
    And I should not see "Create program"
    And I should not see "Archive program"

  Scenario: A non-manager cannot access UCKK pathway administration
    Given I am logged in as "uckkplayer1"
    When I am on the UCKK pathway management page
    Then I should see "Sorry, but you do not currently have permissions to do that"
    And I should not see "Create pathway"
    And I should not see "Archive pathway"

  @javascript
  Scenario: The player dashboard service exposes only the current Joueur summary
    Given I am logged in as "uckkplayer1"
    When I am on the UCKK dashboard page
    Then I should see "My pathway"
    And I should see "Parcours du tronc commun"
    And I should see "My portfolio"
    And I should not see "Basile Observateur"
    And I should not see "Manage programs"

  @javascript
  Scenario: UCKK public language avoids accreditation confusion
    Given I am logged in as "uckkplayer1"
    When I am on the UCKK campus page
    Then I should see "internal recognition"
    And I should see "UCKK pathway"
    And I should see "UCKK badge"
    And I should not see "official university degree"
    And I should not see "recognized university credit"
    And I should not see "state-accredited bachelor's degree"