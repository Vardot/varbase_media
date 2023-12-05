<?php

/**
 * @file
 * Contains varbase_media.tokens.inc.
 */

/**
 * To have all Varbase Media general and global tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Language\LanguageInterface;

/**
 * Implements hook_token_info_alter().
 */
function varbase_media_token_info_alter(&$data) {

  $data['tokens']['media']['social_large'] = [
    'name' => t('Social Large'),
    'description' => t("Social Large (1200x630) image for the selected media type."),
    'module' => 'media',
    'type' => 'url',
  ];

  $data['tokens']['media']['social_medium'] = [
    'name' => t('Social Medium'),
    'description' => t("Social Medium (600x315) image for the selected media type."),
    'module' => 'media',
    'type' => 'url',
  ];

  $data['tokens']['media']['social_small'] = [
    'name' => t('Social Small'),
    'description' => t("Social Small (280x150) image for the selected media type."),
    'module' => 'media',
    'type' => 'url',
  ];
}

/**
 * Implements hook_tokens().
 */
function varbase_media_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  if (isset($options['langcode'])) {
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  $replacements = [];
  if ($type === 'media' && !empty($data['media'])) {
    /** @var \Drupal\media\MediaInterface $media_entity */
    $media_entity = \Drupal::service('entity.repository')->getTranslationFromContext($data['media'], $langcode, ['operation' => 'media_entity_tokens']);

    foreach ($tokens as $token_name => $original) {
      switch ($token_name) {

        // Social Large (1200x630) image for the selected media type.
        case 'social_large':
          $replacements[$original] = varbase_media__image_url($media_entity, 'social_large');
          break;

        // Social Medium (600x315) image for the selected media type.
        case 'social_medium':
          $replacements[$original] = varbase_media__image_url($media_entity, 'social_medium');
          break;

        // Social Small (280x150) image for the selected media type.
        case 'social_small':
          $replacements[$original] = varbase_media__image_url($media_entity, 'social_small');
          break;
      }
    }
  }

  return $replacements;
}
