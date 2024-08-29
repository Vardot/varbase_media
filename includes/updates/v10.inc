<?php

/**
 * @file
 * Contains varbase_media_update_10###(s) hook updates.
 */

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;

/**
 * Issue #3441752: Allow to upload webp file extensions.
 *
 * In the media image entity type.
 */
function varbase_media_update_100001() {
  $module_path = Drupal::service('module_handler')->getModule('varbase_media')->getPath();
  $recipe = Recipe::createFromDirectory($module_path . '/recipes/updates/varbase_media_update_100001');
  RecipeRunner::processRecipe($recipe);
}

/**
 * Issue #3394223: Fix Status Report for updates with Embed button.
 *
 * CKEditor5 compatibility after updating.
 */
function varbase_media_update_100002() {
  $module_path = Drupal::service('module_handler')->getModule('varbase_media')->getPath();
  $recipe = Recipe::createFromDirectory($module_path . '/recipes/updates/varbase_media_update_100002');
  RecipeRunner::processRecipe($recipe);
}

/**
 * Issue #3451094: Switch from Drimage to Drimage Improved in Varbase Media.
 *
 * Uninstall ImageAPI Optimize WebP no longer needed module.
 */
function varbase_media_update_100003() {

  // Only install Drimage Improved when Drimage is enable.
  if (\Drupal::moduleHandler()->moduleExists('drimage')
    && !\Drupal::moduleHandler()->moduleExists('drimage_improved')) {

    $module_path = Drupal::service('module_handler')->getModule('varbase_media')->getPath();
    $recipe = Recipe::createFromDirectory($module_path . '/recipes/updates/varbase_media_update_100003');
    RecipeRunner::processRecipe($recipe);
  }

  // Uninstall ImageAPI Optimize WebP no longer needed module.
  if (\Drupal::moduleHandler()->moduleExists('imageapi_optimize_webp')) {
    \Drupal::service('module_installer')->uninstall(['imageapi_optimize_webp'], FALSE);
  }
}

/**
 * Issue #3470160: Fix the exposed date filters widget for Media to use the default settings.
 *
 * With Better Exposed Filters in Drupal 10.3
 */
function varbase_media_update_100004() {

  // Change the Media view to use "Default" plugin id with the Created date (from) and Created date (to) date filters.
  $config_factory = \Drupal::configFactory()->getEditable('views.view.media');
  if ($config_factory->get('display.default.display_options.exposed_form.options.bef.filter.created.plugin_id') != 'default') {
    $config_factory->set('display.default.display_options.exposed_form.options.bef.filter.created.plugin_id', 'default');
  }
  if ($config_factory->get('display.default.display_options.exposed_form.options.bef.filter.created_1.plugin_id') != 'default') {
    $config_factory->set('display.default.display_options.exposed_form.options.bef.filter.created_1.plugin_id', 'default');
  }
  $config_factory->save(TRUE);

  // Change the Media Library view to use "Default" plugin id with the Created date (from) and Created date (to) date filters.
  $config_factory = \Drupal::configFactory()->getEditable('views.view.media_library');
  if ($config_factory->get('display.default.display_options.exposed_form.options.bef.filter.created.plugin_id') != 'default') {
    $config_factory->set('display.default.display_options.exposed_form.options.bef.filter.created.plugin_id', 'default');
  }
  if ($config_factory->get('display.default.display_options.exposed_form.options.bef.filter.created_1.plugin_id') != 'default') {
    $config_factory->set('display.default.display_options.exposed_form.options.bef.filter.created_1.plugin_id', 'default');
  }
  $config_factory->save(TRUE);

}

/**
 * Issue #3470979: Grant Access to Entity Usage Statistics.
 *
 * For Site Admins and Editorial Team.
 */
function varbase_media_update_100005() {
  $module_path = Drupal::service('module_handler')->getModule('varbase_media')->getPath();
  $recipe = Recipe::createFromDirectory($module_path . '/recipes/updates/varbase_media_update_100005');
  RecipeRunner::processRecipe($recipe);
}
