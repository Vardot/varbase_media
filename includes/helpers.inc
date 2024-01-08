<?php

/**
 * @file
 * Contains List of all varbase_media helpers.
 *
 * Add custom needed helper functions.
 */

use Drupal\image\Entity\ImageStyle;

/**
 * Managed Varbase Media Library configurations.
 *
 * Fix [Media Library] and the [Media Grid and Media Table]
 * admin pages to work with Drupal ^8.8.x and ^8.7.x .
 */
function varbase_media__managed_media_library_configs() {
  $module_path = Drupal::service('module_handler')->getModule('varbase_media')->getPath();
  $managed_config_path = DRUPAL_ROOT . '/' . $module_path . '/config';

  if (version_compare(Drupal::VERSION, '8.8.0', 'lt') === TRUE) {
    $managed_config_path = $managed_config_path . '/managed/lt80800';
  }
  else {
    // Use the latest managed configs from the managed latest directory.
    $managed_config_path = $managed_config_path . '/managed/latest';
  }

  // Override the media view.
  $media_config_path = $managed_config_path . '/views.view.media.yml';
  if (file_exists($media_config_path)) {
    $media_config_content = file_get_contents($media_config_path);
    $media_config_data = (array) Yaml::parse($media_config_content);
    $media_config_factory = \Drupal::configFactory()->getEditable('views.view.media');
    $media_config_factory->setData($media_config_data)->save(TRUE);
  }

  // Override the media library view.
  $media_library_config_path = $managed_config_path . '/views.view.media_library.yml';
  if (file_exists($media_library_config_path)) {
    $media_library_config_content = file_get_contents($media_library_config_path);
    $media_library_config_data = (array) Yaml::parse($media_library_config_content);
    $media_library_config_factory = \Drupal::configFactory()->getEditable('views.view.media_library');
    $media_library_config_factory->setData($media_library_config_data)->save(TRUE);
  }

}

/**
 * Helper function to rename slick_media config dependencies to slick.
 *
 * Https://git.drupalcode.org/project/slick_media/-/blob/8.x-3.x/slick_media.install.
 *
 * @param string $dependency_type
 *   The type of the dependency, such as "module" or "config".
 * @param string $dependency_id
 *   The name of the dependency to be updated.
 * @param callable $map
 *   A callback to be passed to array_map() to actually perform the config name
 *   substitution.
 */
function varbase_media__slick_media_fix_dependencies($dependency_type, $dependency_id, callable $map) {
  $dependents = \Drupal::service('config.manager')
    ->findConfigEntityDependents($dependency_type, [$dependency_id]);

  $key = 'dependencies.' . $dependency_type;
  $key2 = 'dependencies.enforced.' . $dependency_type;

  foreach (array_keys($dependents) as $name) {
    $config = \Drupal::configFactory()->getEditable($name);
    $dependencies = $config->get($key);
    if (is_array($dependencies)) {
      $config->set($key, array_map($map, $dependencies));
    }

    $dependencies2 = $config->get($key2);
    if (is_array($dependencies2)) {
      $config->set($key2, array_map($map, $dependencies2));
    }

    $config->save();
  }
}

/**
 * Get the URL with image style for a selected media entity.
 */
function varbase_media__image_url($media_entity, $style_name = NULL) {

  $image_field_name = varbase_media__default_image_field_name($media_entity);

  if ($img_entity = $media_entity->get($image_field_name)->first()) {
    if ($file_entity = $img_entity->get('entity')->getTarget()) {
      if (!empty($style_name)) {
        return ImageStyle::load($style_name)
          ->buildUrl($file_entity->get('uri')
            ->first()
            ->getString());
      }
      else {
        return \Drupal::service('file_url_generator')->generateAbsoluteString($file_entity->get('uri')->getString());
      }
    }
  }

  return NULL;
}

/**
 * Get the default image field name for any media entity types.
 */
function varbase_media__default_image_field_name($media_entity) {

  // Media entities with a valid field media image data it will come first.
  if (isset($media_entity->field_media_image)
      && !empty($media_entity->get('field_media_image')->first())) {
    return 'field_media_image';
  }
  // Media entities with a valid field media cover image data it will be used.
  elseif (isset($media_entity->field_media_cover_image)
      && !empty($media_entity->get('field_media_cover_image')->first())) {
    return 'field_media_cover_image';
  }
  // Media entities without field image or cover image, will get the thumbnail.
  else {
    return 'thumbnail';
  }

}
