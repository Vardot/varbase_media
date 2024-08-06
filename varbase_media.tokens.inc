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
 * Implements hook_token_info().
 */
function varbase_media_token_info() {
  $info = [];

  $info['tokens']['media']['social_large'] = [
    'name' => t('Social Large'),
    'description' => t("Social Large (1200x630) image for the selected media type."),
    'needs-data' => 'media',
  ];

  $info['tokens']['media']['social_medium'] = [
    'name' => t('Social Medium'),
    'description' => t("Social Medium (600x315) image for the selected media type."),
    'needs-data' => 'media',
  ];

  $info['tokens']['media']['social_small'] = [
    'name' => t('Social Small'),
    'description' => t("Social Small (280x150) image for the selected media type."),
    'needs-data' => 'media',
  ];

  // Define the new 'share-image' token type.
  $info['types']['share-image'] = [
    'name' => t('Share Image'),
    'description' => t('Tokens related to the share image field on nodes.'),
    'needs-data' => 'node',
    'nested' => TRUE,
  ];

  // Define tokens for 'share-image' type.
  $info['tokens']['share-image']['field_image'] = [
    'name' => t('Image'),
    'description' => t('The image field associated with the share image.'),
    'type' => 'image',
  ];

  $info['tokens']['share-image']['field_media'] = [
    'name' => t('Media'),
    'description' => t('The media field associated with the share image.'),
    'type' => 'media',
  ];

  // Add share-image to node tokens.
  $info['tokens']['node']['share-image'] = [
    'name' => t('Share Image'),
    'description' => t('Tokens related to the share image on nodes.'),
    'type' => 'share-image',
  ];

  return $info;
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
  if ($type === 'media') {
    if (!empty($data['media'])) {
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
  }
  if ($type === 'node') {
    if (!empty($data['node'])) {
      $node = $data['node'];
      // Check if the node has a share image field.
      if ($node->hasField('field_media') && !$node->get('field_media')->isEmpty()) {
        $media = $node->field_media->entity;
        foreach ($tokens as $token_name => $original) {
          switch ($token_name) {
            case 'share-image:field_media:social_large':
              $replacements[$original] = varbase_media__image_url($media, 'social_large');
              break;
            case 'share-image:field_media:social_medium':
              $replacements[$original] = varbase_media__image_url($media, 'social_medium');
              break;
            case 'share-image:field_media:social_small':
              $replacements[$original] = varbase_media__image_url($media, 'social_small');
              break;
          }
        }
      }
      elseif ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $image = $node->field_image->entity;
        foreach ($tokens as $token_name => $original) {
          switch ($token_name) {
            case 'share-image:field_image:social_large':
              $replacements[$original] = varbase_media__image_url($image, 'social_large');
              break;
            case 'share-image:field_image:social_medium':
              $replacements[$original] = varbase_media__image_url($image, 'social_medium');
              break;
            case 'share-image:field_image:social_small':
              $replacements[$original] = varbase_media__image_url($image, 'social_small');
              break;
            case 'share-image:field_image':
              $replacements[$original] = varbase_media__image_url($image);
              break;
          }
        }
      }
      else {
        foreach ($tokens as $token_name => $original) {
          switch ($token_name) {
            case 'share-image:field_media:social_large':
            case 'share-image:field_media:social_medium':
            case 'share-image:field_media:social_small':
            case 'share-image:field_image:social_large':
            case 'share-image:field_image:social_medium':
            case 'share-image:field_image:social_small':
              $replacements[$original] = varbase_media__get_fallback_social_share_image_url();
              break;
          }
        }
      }
    }
  }
  return $replacements;
}
