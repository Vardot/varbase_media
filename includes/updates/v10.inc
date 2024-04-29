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
