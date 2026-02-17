<?php

declare(strict_types=1);

namespace Drupal\varbase_media\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;
use Drupal\media\OEmbed\Provider;

/**
 * Hook implementations for varbase_media.
 */
class VarbaseMediaHooks {

  /**
   * Implements hook_preprocess_field().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    if ($variables['element']['#formatter'] != 'oembed') {
      return;
    }

    // Provide an extra variable to the field template when the field uses
    // a formatter of type 'oembed'.
    $resource_fetcher = \Drupal::service('media.oembed.resource_fetcher');
    $url_resolver = \Drupal::service('media.oembed.url_resolver');
    $iframe_url_helper = \Drupal::service('media.oembed.iframe_url_helper');

    $entity = $variables['element']['#object'];

    $view_mode = $variables['element']['#view_mode'];
    $field_name = $variables['element']['#field_name'];
    $bundle = $variables['element']['#bundle'];

    // Get the field formatter settings...
    $entity_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    $field_display = $entity_display->getComponent($field_name);

    if ($bundle == "remote_video") {
      $max_width = $field_display['settings']['max_width'];
      $max_height = $field_display['settings']['max_height'];
      $item = $variables['element']["#items"]->first();
      $main_property = $item->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
      $value = $item->{$main_property};

      // Get langcode from media entity itself.
      $langcode = $entity->language()->getId();

      // If no lang for the media entity, get current lang as 1st fallback.
      if (empty($langcode) || $langcode == 'und') {
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
      }

      // If no data, use "en" as 2nd fallback option.
      if (empty($langcode) || $langcode == 'und') {
        $langcode = 'en';
      }

      $resource_url = $url_resolver->getResourceUrl($value, $max_width, $max_height);
      $resource = $resource_fetcher->fetchResource($resource_url);
      $provider = $resource->getProvider()->getName();
      $url = Url::fromRoute('media.oembed_iframe', [], [
        'query' => [
          'url' => $value,
          'max_width' => $max_width,
          'max_height' => $max_height,
          'type' => "remote_video",
          'provider' => strtolower($provider ?? ''),
          'view_mode' => $view_mode,
          'langcode' => $langcode,
          'hash' => $iframe_url_helper->getHash($value, $max_width, $max_height, $provider, $view_mode),
        ],
      ]);

      $variables['items'][0]['content']['#attributes']['src'] = $url->toString();
    }
  }

  /**
   * Implements hook_preprocess_media_oembed_iframe().
   */
  #[Hook('preprocess_media_oembed_iframe')]
  public function preprocessMediaOembedIframe(array &$variables): void {
    // Send variables for all oembed iframe theme template.
    $query = \Drupal::request()->query;
    $variables['type'] = $query->get('type');
    $variables['provider'] = $query->get('provider');
    $variables['view_mode'] = $query->get('view_mode');
    $variables['langcode'] = $query->get('langcode');
    $variables['base_path'] = base_path();
    $variables['varbase_media_path'] = \Drupal::service('module_handler')->getModule('varbase_media')->getPath();

    // Add media title from resource if available.
    if (!empty($variables['resource']) && !empty($variables['resource']->getTitle())) {
      $variables['media_title'] = $variables['resource']->getTitle();
    }
  }

  /**
   * Implements hook_theme_suggestions_media_oembed_iframe_alter().
   */
  #[Hook('theme_suggestions_media_oembed_iframe_alter')]
  public function themeSuggestionsMediaOembedIframeAlter(array &$suggestions, array &$vars): void {
    $query = \Drupal::request()->query;
    $type = $query->get('type');
    $provider = $query->get('provider');
    $view_mode = $query->get('view_mode');
    if ($type && $provider) {
      $suggestions[] = "media_oembed_iframe__" . $provider;
      $suggestions[] = "media_oembed_iframe__" . $provider . "__" . $view_mode;
      $suggestions[] = "media_oembed_iframe__" . $view_mode;
      $suggestions[] = "media_oembed_iframe__" . $type;
      $suggestions[] = "media_oembed_iframe__" . $type . "__" . $view_mode;
      $suggestions[] = "media_oembed_iframe__" . $type . "__" . $provider;
      $suggestions[] = "media_oembed_iframe__" . $type . "__" . $provider . "__" . $view_mode;
    }
  }

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {

    if ($entity->getEntityTypeId() == 'media'
      && $build['#view_mode'] != 'field_preview') {

      // Attach the varbase media video library for video embed field.
      if (isset($build['field_media_oembed_video'])
        && isset($build['field_media_oembed_video'][0])) {

        $build['field_media_oembed_video'][0]['#attached']['library'][] = 'varbase_media/varbase_video_player';
      }

      // Attach the varbase media video library for video file field.
      if (isset($build['field_media_video_file'])) {
        $build['field_media_video_file']['#attached']['library'][] = 'varbase_media/varbase_video_player';
      }
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, &$form_state, string $form_id): void {
    if ($form_state->getFormObject() instanceof EntityFormInterface) {
      $entity_type = $form_state->getFormObject()->getEntity()->getEntityTypeId();

      // Only for media entity type.
      if ($entity_type == 'media') {

        // No revision information or revision log message.
        if (isset($form['revision_information'])) {
          $form['revision_information']['#disabled'] = TRUE;
          $form['revision_information']['#attributes']['style'][] = 'display:none;';
          $form['revision_information']['#prefix'] = '<div style="display: none;">';
          $form['revision_information']['#suffix'] = '</div>';
        }

        // Hide revision.
        if (isset($form['revision'])) {
          $form['revision']['#default_value'] = TRUE;
          $form['revision']['#disabled'] = TRUE;
          $form['revision']['#attributes']['style'][] = 'display: none;';
        }

        // Hide revision log message.
        if (isset($form['revision_log_message'])) {
          $form['revision_log_message']['#disabled'] = TRUE;
          $form['revision_log_message']['#attributes']['style'][] = 'display: none;';
        }
      }
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'media_oembed_iframe__remote_video' => [
        'template' => 'media-oembed-iframe--remote-video',
        'variables' => [
          'provider' => NULL,
          'media' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_preprocess_media_oembed_iframe__remote_video().
   */
  #[Hook('preprocess_media_oembed_iframe__remote_video')]
  public function preprocessMediaOembedIframeRemoteVideo(array &$variables): void {
    // Send variables for the remote_video oembed iframe theme template.
    $query = \Drupal::request()->query;
    $variables['type'] = $query->get('type');
    $variables['provider'] = $query->get('provider');
    $variables['view_mode'] = $query->get('view_mode');
    $variables['langcode'] = $query->get('langcode');
    $variables['base_path'] = base_path();
    $variables['varbase_media_path'] = \Drupal::service('module_handler')->getModule('varbase_media')->getPath();

    // Add media title from resource if available.
    if (!empty($variables['resource']) && !empty($variables['resource']->getTitle())) {
      $variables['media_title'] = $variables['resource']->getTitle();
    }
  }

  /**
   * Implements hook_oembed_resource_url_alter().
   */
  #[Hook('oembed_resource_url_alter')]
  public function oembedResourceUrlAlter(array &$parsed_url, Provider $provider): void {
    // Process arguments for vimeo videos to be included in oEmbed.
    if ($provider->getName() == 'Vimeo') {
      $url = $parsed_url['query']['url'];
      // Use '/&' as a separator between arguments.
      $url = str_replace('&', '/&', $url);
      $url = str_replace('?', '/&', $url);
      $parsed_url['query']['url'] = $url;
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'ckeditor5') {
      if (\Drupal::moduleHandler()->moduleExists('drimage_improved')) {
        $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'drimage_improved/drimage_improved';
        $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'varbase_media/ckeditor_drimage';
      }
      $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'varbase_media/varbase_video_player';
      $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'varbase_media/ckeditor_varbase_video_player';
    }
  }

}
