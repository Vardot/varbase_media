<?php

declare(strict_types=1);

namespace Drupal\varbase_media\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;

/**
 * Token hook implementations for varbase_media.
 */
class VarbaseMediaTokens {

  use StringTranslationTrait;

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $info = [];

    $info['tokens']['media']['social_large'] = [
      'name' => $this->t('Social Large'),
      'description' => $this->t("Social Large (1200x630) image for the selected media type."),
      'module' => 'media',
      'type' => 'url',
    ];

    $info['tokens']['media']['social_medium'] = [
      'name' => $this->t('Social Medium'),
      'description' => $this->t("Social Medium (600x315) image for the selected media type."),
      'module' => 'media',
      'type' => 'url',
    ];

    $info['tokens']['media']['social_small'] = [
      'name' => $this->t('Social Small'),
      'description' => $this->t("Social Small (280x150) image for the selected media type."),
      'module' => 'media',
      'type' => 'url',
    ];

    // Define the new 'share-image' token type.
    $info['types']['share-image'] = [
      'name' => $this->t('Share Image'),
      'description' => $this->t('The social share image for the node. Checks field_media, field_image, field_video, and falls back to the theme default image if none are found.'),
      'needs-data' => 'node',
      'nested' => TRUE,
    ];

    // Define tokens for each field.
    $info['tokens']['share-image']['field_media'] = [
      'name' => $this->t('Share Image from field_media'),
      'description' => $this->t('The share image from field_media, if available.'),
      'type' => 'media',
    ];

    $info['tokens']['share-image']['field_image'] = [
      'name' => $this->t('Share Image from field_image'),
      'description' => $this->t('The share image from field_image, if available.'),
      'type' => 'media',
    ];

    $info['tokens']['share-image']['field_video'] = [
      'name' => $this->t('Share Image from field_video'),
      'description' => $this->t('The share image from field_video, if available.'),
      'type' => 'media',
    ];

    // Add share-image to node.
    $info['tokens']['node']['share-image'] = [
      'name' => $this->t('Share Image'),
      'description' => $this->t('The social share image for the node. Checks field_media, field_image, field_video, and falls back to the theme default image if none are found.'),
      'type' => 'share-image',
    ];

    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
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
            $replacements[$original] = $this->imageUrl($media_entity, 'social_large');
            break;

          // Social Medium (600x315) image for the selected media type.
          case 'social_medium':
            $replacements[$original] = $this->imageUrl($media_entity, 'social_medium');
            break;

          // Social Small (280x150) image for the selected media type.
          case 'social_small':
            $replacements[$original] = $this->imageUrl($media_entity, 'social_small');
            break;
        }
      }
    }

    if ($type === 'node' && !empty($data['node'])) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = $data['node'];

      $replacements = [];
      foreach ($tokens as $token_name => $original) {

        // When only using the smart [node:share-image] token.
        if ($token_name == 'share-image') {
          $replacements[$original] = $this->getNodeShareImageUrl($node, 'social_large');
        }
        // When the the smart [node:share-image:#######:#####] has extra specific options.
        elseif ($token_name == 'share-image:') {

          // Get the array of token options.
          $token_options = explode(":", $token_name);

          // Get the field name.
          $field_name = '';
          if (isset($token_options[1]) && $token_options[1] != '') {
            $field_name = $token_options[1];
          }

          // Get the social image style name.
          $style_name = 'social_large';
          if (isset($token_options[2]) && $token_options[2] != '') {
            $style_name = $token_options[2];
          }

          $replacements[$original] = $this->getNodeShareImageUrl($node, $style_name, $field_name);

        }
      }
    }

    return $replacements;
  }

  /**
   * Get the URL with image style for a selected media entity.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   The entity object for media with image.
   * @param string|null $style_name
   *   The name of the image style.
   *
   * @return string|null
   *   The image url by media entity and image style name.
   */
  protected function imageUrl(MediaInterface $media_entity, ?string $style_name = NULL): ?string {

    $image_field_name = $this->defaultImageFieldName($media_entity);

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

    return $this->getFallbackSocialShareImageUrl();
  }

  /**
   * Get the default image field name for any media entity types.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   The entity object for media with image.
   *
   * @return string
   *   The field name for the image for a media type.
   */
  protected function defaultImageFieldName(MediaInterface $media_entity): string {

    // Media entities with a valid field media image data it will come first.
    if (isset($media_entity->field_media_image)
        && !empty($media_entity->get('field_media_image')->first())) {
      return 'field_media_image';
    }
    // Media entities without field image or cover image, will get the thumbnail.
    else {
      return 'thumbnail';
    }
  }

  /**
   * Get a url for the fallback social share image from the active theme.
   *
   * @return string
   *   The URL of the fallback social share image.
   */
  protected function getFallbackSocialShareImageUrl(): string {
    $active_theme = \Drupal::theme()->getActiveTheme()->getPath();
    $origin_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $share_image_url = $origin_url . '/' . $active_theme . '/share-image.png';
    return $share_image_url;
  }

  /**
   * Get the share image URL for a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   * @param string|null $style_name
   *   (optional) The image style to apply. Defaults to NULL.
   * @param string|null $field_name
   *   (optional) The specific field name to check for the share image.
   *
   * @return string
   *   The URL of the share image, or the fallback URL if not found.
   */
  protected function getNodeShareImageUrl(Node $node, ?string $style_name = 'social_large', ?string $field_name = ''): string {

    $image_url = '';

    // When no filed name.
    if ($field_name == '') {
      // Check if the node has field_media and it has data.
      if ($node->hasField('field_media') && !$node->get('field_media')->isEmpty()) {
        $entity = $node->get('field_media')->entity;
        if ($entity instanceof MediaInterface) {
          // Set the social share image from the field_media field.
          $image_url = $this->imageUrl($entity, $style_name);
        }
      }
      // When no field_media check if the node has field_image and it has data.
      elseif ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $entity = $node->get('field_image')->entity;
        if ($entity instanceof MediaInterface) {
          // Set the social share image from the field_image field.
          $image_url = $this->imageUrl($entity, $style_name);
        }
      }
      // When no field_image check if the node has field_video and it has data.
      elseif ($node->hasField('field_video') && !$node->get('field_video')->isEmpty()) {
        $entity = $node->get('field_video')->entity;
        if ($entity instanceof MediaInterface) {
          // Set the social share image from the field_video field.
          $image_url = $this->imageUrl($entity, $style_name);
        }
      }
    }
    // When the field name is provided.
    elseif ($field_name != '') {
      // Check if the node has the filed and it has data.
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $entity = $node->get($field_name)->entity;
        if ($entity instanceof MediaInterface) {
          // Set the social share image from the field.
          $image_url = $this->imageUrl($entity, $style_name);
        }
      }
    }

    // When image url still empty, then set the fallback social share image
    // from the default active theme.
    if ($image_url === '') {
      $image_url = $this->getFallbackSocialShareImageUrl();
    }

    return $image_url;
  }

}
