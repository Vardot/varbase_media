<?php

namespace Drupal\Tests\varbase_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Varbase Media tests.
 *
 * @group varbase_media
 */
class VarbaseMediaTests extends WebDriverTestBase {

  use StringTranslationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'metatag',
    'metatag_views',
    'better_exposed_filters',
    'varbase_media',
    'varbase_media_instagram',
    'varbase_media_twitter',
  ];

  /**
   * Specify the theme to be used in testing.
   *
   * @var string
   */
  protected $defaultTheme = 'vartheme_bs4';

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Insall the Claro admin theme.
    $this->container->get('theme_installer')->install(['claro']);

    // Set the Claro theme as the default admin theme.
    $this->config('system.theme')->set('admin', 'claro')->save();

  }

  /**
   * Check Varbase Media Types.
   */
  public function testCheckVarbaseMediaTypesPage() {

    // Given that the root super user was logged in to the site.
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/structure/media');

    $this->assertSession()->pageTextContains($this->t('Image'));
    $this->assertSession()->pageTextContains($this->t('Remote video'));
    $this->assertSession()->pageTextContains($this->t('Video'));
    $this->assertSession()->pageTextContains($this->t('Gallery'));
    $this->assertSession()->pageTextContains($this->t('Instagram'));
    $this->assertSession()->pageTextContains($this->t('Tweet'));
    $this->assertSession()->pageTextContains($this->t('Document'));

  }

  /**
   * Check Varbase Media settings page.
   */
  public function testCheckVarbaseMediaSettings() {

    // Given that the root super user was logged in to the site.
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/people/permissions#module-varbase_media');
    $this->assertSession()->pageTextContains($this->t('Administer Varbase Media settings'));
  }

  /**
   * Check permissions to Administer Varbase Media settings.
   */
  public function testCheckVarbaseMediaSettingsPermissions() {

    // Given that the root super user was logged in to the site.
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/admin/people/permissions');
    $this->assertSession()->pageTextContains($this->t('Administer Varbase Media settings'));
  }

}
