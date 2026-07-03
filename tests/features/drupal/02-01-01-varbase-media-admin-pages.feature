@varbase_media @admin
Feature: Varbase Media - administration pages
  As a site administrator
  I want the core and media administration pages to be reachable with Varbase
  Media and all of its submodules enabled

  Scenario: The administration pages are reachable for the administrator
    Given I am a logged in user with the "Webmaster" user
    When I open the administration page "/admin/content"
    Then I should not see "Page not found"
    When I open the administration page "/admin/content/media"
    Then I should not see "Page not found"
    When I open the administration page "/admin/config"
    Then I should not see "Page not found"
    When I open the administration page "/admin/config/media"
    Then I should not see "Page not found"
    When I open the administration page "/admin/structure"
    Then I should not see "Page not found"
    When I open the administration page "/admin/structure/media"
    Then I should not see "Page not found"
    When I open the administration page "/admin/people"
    Then I should not see "Page not found"
    When I open the administration page "/admin/reports/status"
    Then I should not see "Page not found"
