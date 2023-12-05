<?php

/**
 * @file
 * Contains varbase_media_update_9###(s) hook updates.
 */

use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Config\InstallStorage;
use Vardot\Installer\ModuleInstallerFactory;

/**
 * Issue #3192175: Add WebP module ~1.0 and enable by default.
 */
function varbase_media_update_9001() {
  if (!\Drupal::moduleHandler()->moduleExists('webp')) {
    \Drupal::service('module_installer')->install(['webp'], FALSE);
  }
}

/**
 * Issue #3206185: Add Media Bulk Upload module.
 */
function varbase_media_update_9002() {
  if (!\Drupal::moduleHandler()->moduleExists('media_bulk_upload')) {
    // Enable Media upload module.
    \Drupal::service('module_installer')->install(['media_bulk_upload'], FALSE);

    $module_path = Drupal::service('module_handler')->getModule('varbase_media')->getPath();
    $optional_install_path = $module_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;

    // List of media bulk upload configs needed for Varbase media.
    $media_bulk_upload_configs = [
      'core.entity_form_mode.media.bulk_upload',
      'core.entity_form_display.media.audio.bulk_upload',
      'core.entity_form_display.media.file.bulk_upload',
      'core.entity_form_display.media.image.bulk_upload',
      'core.entity_form_display.media.video.bulk_upload',
      'media_bulk_upload.media_bulk_config.media_bulk_upload',
    ];

    // Import list of needed configs for Media bulk Upload.
    foreach ($media_bulk_upload_configs as $media_bulk_upload_config) {
      $media_config_path = $optional_install_path . '/' . $media_bulk_upload_config . '.yml';

      if (file_exists($media_config_path)) {
        $media_config_content = file_get_contents($media_config_path);
        $media_config_data = (array) Yaml::parse($media_config_content);
        $media_config_factory = \Drupal::configFactory()->getEditable($media_bulk_upload_config);
        $media_config_factory->setData($media_config_data)->save(TRUE);
      }
    }
  }
}

/**
 * Issue #3271439: Fix Entity Browser bundle media type filter.
 *
 * Not to use and empty exposed operator.
 */
function varbase_media_update_9003() {

  $media_browsers_config_factory = \Drupal::configFactory()->getEditable('views.view.media_browsers');
  $media_browsers_view_display = $media_browsers_config_factory->get('display');

  if (isset($media_browsers_view_display)
    && isset($media_browsers_view_display['default'])
    && isset($media_browsers_view_display['default']['display_options'])
    && isset($media_browsers_view_display['default']['display_options']['filters'])
    && isset($media_browsers_view_display['default']['display_options']['filters']['entity_browser_bundle'])
    && isset($media_browsers_view_display['default']['display_options']['filters']['entity_browser_bundle']['expose'])
    && isset($media_browsers_view_display['default']['display_options']['filters']['entity_browser_bundle']['expose']['use_operator'])
    && $media_browsers_view_display['default']['display_options']['filters']['entity_browser_bundle']['expose']['use_operator'] == TRUE) {
    $media_browsers_view_display['default']['display_options']['filters']['entity_browser_bundle']['expose']['use_operator'] = FALSE;
    $media_browsers_config_factory->set('display', $media_browsers_view_display)->save(TRUE);
  }
}

/**
 * Issue #3274535: Update Media Bulk Upload module.
 *
 * From ~1.0 to ~3.0 new stable and supported branch
 * And enable the Media Bulk Upload Dropzone.js sub-module.
 */
function varbase_media_update_90004() {
  if (\Drupal::moduleHandler()->moduleExists('media_bulk_upload')
    && !\Drupal::moduleHandler()->moduleExists('media_bulk_upload_dropzonejs')) {
    // Enable Media upload DropzoneJs sub module.
    \Drupal::service('module_installer')->install(['media_bulk_upload_dropzonejs'], FALSE);
  }
}

/**
 * Issue #3272289: Switch to use the Module Installer Factory.
 *
 * In the Varbase Media module.
 */
function varbase_media_update_90005() {
  // Set the weight of the module after installation of list of modules.
  // To make sure that any hook or event subscriber worker
  // after all used modules.
  ModuleInstallerFactory::setModuleWeightAfterInstallation('varbase_media');
}

/**
 * Issue #3398691: Fix not rendered selection checkbox.
 *
 * For Media Library grid widget after searching.
 */
function varbase_media_update_90006() {

  $config_factory = \Drupal::configFactory()->getEditable('views.view.media_library');
  $config_factory->set('display.default.display_options.sorts.created.expose.field_identifier', 'created');
  $config_factory->set('display.default.display_options.sorts.name.expose.field_identifier', 'name');
  $config_factory->set('display.default.display_options.sorts.name_1.expose.field_identifier', 'name_1');

  $config_factory->set('display.page.display_options.sorts.created.expose.field_identifier', 'created');
  $config_factory->set('display.page.display_options.sorts.name.expose.field_identifier', 'name');
  $config_factory->set('display.page.display_options.sorts.name_1.expose.field_identifier', 'name_1');

  $config_factory->set('display.widget.display_options.sorts.created.expose.field_identifier', 'created');
  $config_factory->set('display.widget.display_options.sorts.name.expose.field_identifier', 'name');
  $config_factory->set('display.widget.display_options.sorts.name_1.expose.field_identifier', 'name_1');

  $config_factory->set('display.widget_table.display_options.sorts.created.expose.field_identifier', 'created');
  $config_factory->set('display.widget_table.display_options.sorts.name.expose.field_identifier', 'name');
  $config_factory->set('display.widget_table.display_options.sorts.name_1.expose.field_identifier', 'name_1');

  $config_factory->save(TRUE);
}
