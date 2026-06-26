@varbase_media @content
Feature: Varbase Media - create an image media item
  As a site administrator
  I want to create an image media item with Varbase Media enabled

  Scenario: Create an image media item from an uploaded file
    Given I am a logged in user with the "Webmaster" user
    When I am on "/media/add/image"
    Then I should see "Add Image"
    And I should see "Allowed types"
    When I attach the media file "flag-earth.jpg" to "#edit-field-media-image-0-upload"
    And I fill in "Flag Earth in space" for "field_media_image[0][alt]"
    And I press the "Save" button
    Then I should not see "Access denied"
    And I should not see "The website encountered an unexpected error"
    When I open the administration page "/admin/content/media"
    Then I should see "flag-earth"
