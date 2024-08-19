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
    'module' => 'media',
    'type' => 'url',
  ];

  $info['tokens']['media']['social_medium'] = [
    'name' => t('Social Medium'),
    'description' => t("Social Medium (600x315) image for the selected media type."),
    'module' => 'media',
    'type' => 'url',
  ];

  $info['tokens']['media']['social_small'] = [
    'name' => t('Social Small'),
    'description' => t("Social Small (280x150) image for the selected media type."),
    'module' => 'media',
    'type' => 'url',
  ];

  // Define the new 'share-image' token type.
  $info['types']['share-image'] = [
    'name' => t('Share Image'),
    'description' => t('The social share image for the node. Checks field_media, field_image, field_video, and falls back to the theme default image if none are found.'),
    'needs-data' => 'node',
    'nested' => TRUE,
  ];

  // Define tokens for each field.
  $info['tokens']['share-image']['field_media'] = [
    'name' => t('Share Image from field_media'),
    'description' => t('The share image from field_media, if available.'),
    'type' => 'media'
  ];

  $info['tokens']['share-image']['field_image'] = [
    'name' => t('Share Image from field_image'),
    'description' => t('The share image from field_image, if available.'),
    'type' => 'media'
  ];

  $info['tokens']['share-image']['field_video'] = [
    'name' => t('Share Image from field_video'),
    'description' => t('The share image from field_video, if available.'),
    'type' => 'media'
  ];

  // Add share-image to node.
  $info['tokens']['node']['share-image'] = [
    'name' => t('Share Image'),
    'description' => t('The social share image for the node. Checks field_media, field_image, field_video, and falls back to the theme default image if none are found.'),
    'type' => 'share-image'
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

  if ($type === 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $data['node'];

    $replacements = [];
    foreach ($tokens as $token_name => $original) {
      switch ($token_name) {
        // 
        case 'share-image:field_media':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, NULL, 'field_media');
          break;

        case 'share-image:field_image':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, NULL, 'field_image');
          break;

        case 'share-image:field_video':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, NULL, 'field_video');
          break;

        // Handle specific cases with styles.
        case 'share-image:field_media:social_large':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_large', 'field_media');
          break;

        case 'share-image:field_image:social_large':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_large', 'field_image');
          break;

        case 'share-image:field_video:social_large':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_large', 'field_video');
          break;

        case 'share-image:field_media:social_medium':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_medium', 'field_media');
          break;

        case 'share-image:field_image:social_medium':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_medium', 'field_image');
          break;

        case 'share-image:field_video:social_medium':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_medium', 'field_video');
          break;

        case 'share-image:field_media:social_small':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_small', 'field_media');
          break;

        case 'share-image:field_image:social_small':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_small', 'field_image');
          break;

        case 'share-image:field_video:social_small':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_small', 'field_video');
          break;

        // Handle the default case for 'share-image' when no specific field or style is provided.
        case 'share-image':
          $replacements[$original] = varbase_media__get_node_share_image_url($node, 'social_large');
          break;
      }
    }
  }
  return $replacements;
}
