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
 * Issue #3451094: Switch from Drimage to Drimage Improved.
 *
 * In Varbase Media.
 */
function varbase_media_update_100003() {
  $module_path = Drupal::service('module_handler')->getModule('varbase_media')->getPath();
  $recipe = Recipe::createFromDirectory($module_path . '/recipes/updates/varbase_media_update_100003');
  RecipeRunner::processRecipe($recipe);
}
