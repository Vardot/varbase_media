<?php

/**
 * @file
 * Contains varbase_media_update_8###(s) hook updates.
 */

use Drupal\file\Entity\File;
use Drupal\embed\Entity\EmbedButton;
use Drupal\Core\File\Exception\FileException;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Issue #3098789: Fix [Media Library] and the [Media Grid and Media Table].
 *
 * Admin pages to work with Drupal ^8.8.x and ^8.7.x .
 */
function varbase_media_update_8701() {
  varbase_media__managed_media_library_configs();
}

/**
 * Issue #3103229: Fix icons for [Embed buttons] for the CKEditor.
 *
 * After the change of Embed API as they had dropped icon_uuid and change of
 * config schema.
 */
function varbase_media_update_8702() {
  varbase_media_update_8701();

  $module_path = Drupal::service('module_handler')->getModule('varbase_media')->getPath();

  // Add Icons for Entity Embed CKEditor Media Library and Gallery.
  $embed_buttons = [
    // CKEditor Embed Media Library icon for the gallery embed entity button.
    'media' => [
      'source' => DRUPAL_ROOT . '/' . $module_path . '/images/ckeditor/hidpi/embed-media.png',
      'destination' => 'public://embed-media.png',
    ],
    // CKEditor Embed Gallery icon for the gallery embed entity button.
    'gallery' => [
      'source' => DRUPAL_ROOT . '/' . $module_path . '/images/ckeditor/hidpi/embed-gallery.png',
      'destination' => 'public://embed-gallery.png',
    ],
  ];

  foreach ($embed_buttons as $embed_button_id => $embed_button_info) {
    $embed_button_list = \Drupal::configFactory()->listAll('embed.button.' . $embed_button_id);
    if (count($embed_button_list) > 0) {
      $target_destination = NULL;
      if (version_compare(Drupal::VERSION, '8.7.0', '>=')) {
        try {
          $target_destination = Drupal::service('file_system')->copy($embed_button_info['source'], $embed_button_info['destination']);
        }
        catch (FileException $e) {
          $target_destination = FALSE;
        }
      }
      else {
        $target_destination = call_user_func('file_unmanaged_copy', $embed_button_info['source'], $embed_button_info['destination']);
      }

      if ($target_destination) {
        $target_file = File::create(['uri' => $target_destination]);
        $target_file->save();

        EmbedButton::load($embed_button_id)->set('icon', EmbedButton::convertImageToEncodedData($target_file->getFileUri()))->save();
      }
      else {
        return t("Unable to copy @source to the public files directory.", [
          '@source' => $embed_button_info['source'],
        ]);
      }
    }
  }
}

/**
 * Follow up fix Issue #3103229: Fix icons for [Embed buttons] for the CKEditor.
 *
 * After the change of Embed API as they had dropped icon_uuid and change of
 * config schema in case for optional updates or buttons were deleted.
 */
function varbase_media_update_8703() {
  varbase_media_update_8702();
}

/**
 * Issue #3115391: Create new field field_provider for remote video media type.
 */
function varbase_media_update_8704() {
  // When Remote Video media type is active.
  $media_bundle_ids = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
  if (isset($media_bundle_ids)
    && is_array($media_bundle_ids)
    && count($media_bundle_ids) > 0
    && isset($media_bundle_ids['remote_video'])) {

    // Configure field storage media field_provider.
    $field_storage_media_field_provider = FieldStorageConfig::loadByName('media', 'field_provider');
    if (!isset($field_storage_media_field_provider)) {
      FieldStorageConfig::create([
        'field_name' => 'field_provider',
        'entity_type' => 'media',
        'type' => 'string',
        'cardinality' => 1,
      ])->save();
    }

    // Configure init field media remote_video field_provider.
    $field_field_media_remote_video_field_provider = FieldConfig::loadByName('media', 'remote_video', 'field_provider');
    if (!isset($field_field_media_remote_video_field_provider)) {
      FieldConfig::create([
        'field_name' => 'field_provider',
        'entity_type' => 'media',
        'bundle' => 'remote_video',
        'label' => t('Provider'),
      ])->save();
    }
  }
}

/**
 * Follow up #3115391: Update all remote videos, set field_provider value.
 */
function varbase_media_update_8705() {
  // When the Remote Video media type is active.
  $media_bundle_ids = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
  if (isset($media_bundle_ids)
    && is_array($media_bundle_ids)
    && count($media_bundle_ids) > 0
    && isset($media_bundle_ids['remote_video'])) {

    $url_resolver = \Drupal::service('media.oembed.url_resolver');
    $resource_fetcher = \Drupal::service('media.oembed.resource_fetcher');
    $entities = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['bundle' => 'remote_video']);

    // When we do have remote video media entities.
    if (isset($entities) && is_array($entities) && count($entities) > 0) {
      // And no syncing for update process.
      if (!\Drupal::isConfigSyncing()) {
        // Fetch the provider name and cache it in the field_provider
        // Not to be changed or fetched again.
        foreach ($entities as $entity) {
          try {
            $resource_url = $url_resolver->getResourceUrl(($entity->field_media_oembed_video->value));
            $resource = $resource_fetcher->fetchResource($resource_url);
            $provider = strtolower($resource->getProvider()->getName() ?? '');

            if ($entity->field_provider->value != $provider) {
              $entity->set('field_provider', $provider);
              $entity->save();
            }
          }
          catch (Exception $e) {
          }

        }
      }
    }
  }
}

/**
 * Follow up #3115391: Update all remote videos, set oembed view mode type.
 */
function varbase_media_update_8706() {
  // When the Remote Video media type is active.
  $media_bundle_ids = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
  if (isset($media_bundle_ids)
    && is_array($media_bundle_ids)
    && count($media_bundle_ids) > 0
    && isset($media_bundle_ids['remote_video'])) {

    $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    $media_view_modes = \Drupal::service('entity_display.repository')->getViewModes('media');

    if (isset($media_view_modes)
      && is_array($media_view_modes)
      && count($media_view_modes) > 0) {

      $media_view_modes['default'] = 'default';
      $media_view_modes = array_keys($media_view_modes);

      foreach ($media_view_modes as $key) {
        $view_display = $storage->load('media.remote_video.' . $key);
        if (isset($view_display)) {
          $config = $view_display->getComponent('field_media_oembed_video');

          if (isset($config['type']) && $config['type'] == 'oembed') {
            $config['type'] = 'varbase_oembed';
            $view_display->setComponent('field_media_oembed_video', $config);
            $view_display->save();
          }
        }
      }
    }
  }
}

/**
 * Issue #3125946: Fix missing varbase media entity pre-save hook.
 *
 * For remote videos after introduced issues on update with [entityqueue]
 * latest release. Please run [ drupal update:entities ] after the update of
 * entityqueue and Varbase Media.
 */
function varbase_media_update_8707() {
  // When the Remote Video media type is active.
  $media_bundle_ids = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
  if (isset($media_bundle_ids)
    && is_array($media_bundle_ids)
    && count($media_bundle_ids) > 0
    && isset($media_bundle_ids['remote_video'])) {

    // Recall for the following update functions as they had changed
    // as hey introduced issues on update with [entityqueue] latest release
    // Issue #3115391: Create new field field_provider for remote video
    // media type.
    // Follow up on Update all remote videos, set field_provider value.
    // Follow up onUpdate all remote videos, set oembed view mode type.
    varbase_media_update_8704();
    varbase_media_update_8705();
    varbase_media_update_8706();
  }
}

/**
 * Issue #3151898: Remove [Slick Media] module dependencies.
 *
 * From [Varbase Media] module and replace it with the main module Slick
 *  and uninstall.
 */
function varbase_media_update_8708() {
  // The Slick module had released the update.
  // It is doing the same update process in the Slick module.
  // --------------------------------------------------------
  // Move all module dependencies on existing config entities from
  // "slick_media" to "slick".
  // Entity updates to clear up any mismatched entity and/or field definitions
  // And Fix changes were detected in the entity type and field definitions.
}

/**
 * Issue #3171237: Fix deprecated function getCurrentUserId.
 *
 * Replaced by getDefaultEntityOwner in all
 *  core.base_field_override.media.MEDIA_TYPES.uid.
 */
function varbase_media_update_8709() {
  $config_factory = \Drupal::service('config.factory');
  $media_uid_configs = $config_factory->listAll('core.base_field_override.media.');

  foreach ($media_uid_configs as $media_uid_config) {
    if (substr($media_uid_config, -4) === ".uid") {

      $media_uid_config_factory = $config_factory->getEditable($media_uid_config);
      $default_value_callback = $media_uid_config_factory->get('default_value_callback');

      if (isset($default_value_callback)
        && !empty($default_value_callback)
        && $default_value_callback === 'Drupal\media\Entity\Media::getCurrentUserId') {

        $media_uid_config_factory->set('default_value_callback', 'Drupal\media\Entity\Media::getDefaultEntityOwner')->save(TRUE);
      }
    }
  }

}
