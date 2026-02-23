<?php

declare(strict_types=1);

namespace Drupal\varbase_media\Hook;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;
use Drupal\media\OEmbed\Provider;
use Drupal\varbase_media\Plugin\Filter\VarbaseFilterResizeMedia;
use Drupal\views\ViewExecutable;

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

      // Attach the varbase media common library.
      $build['#attached']['library'][] = 'varbase_media/common';

      if (!(\Drupal::currentUser()->isAnonymous())) {
        // Attach the varbase media common logged in users library.
        $build['#attached']['library'][] = 'varbase_media/common_logged';
      }

      // Attach the varbase media video library for video embed field.
      if (isset($build['field_media_oembed_video'])
        && isset($build['field_media_oembed_video'][0])) {

        $build['#attached']['library'][] = 'varbase_media/varbase_video_player';
        $build['field_media_oembed_video']['#prefix'] = '<div class="varbase-video-player">';
        $build['field_media_oembed_video']['#suffix'] = '</div>';
      }

      // Attach the varbase media video library for video file field.
      if (isset($build['field_media_video_file'])) {
        $build['#attached']['library'][] = 'varbase_media/varbase_video_player';
        $build['field_media_video_file']['#prefix'] = '<div class="varbase-video-player">';
        $build['field_media_video_file']['#suffix'] = '</div>';
      }

      // Add overlay CSS classes to the cover image field so it acts as a
      // clickable play-button icon positioned over the video player.
      // Only wrap when the cover image field has actual content.
      if (isset($build['field_media_cover_image'])
        && isset($build['field_media_cover_image']['#items'])
        && !$build['field_media_cover_image']['#items']->isEmpty()) {

        $build['#attached']['library'][] = 'varbase_media/varbase_video_player';
        $build['field_media_cover_image']['#prefix'] = '<div class="media-cover-image video-player-icon js-video-player-icon">';
        $build['field_media_cover_image']['#suffix'] = '</div>';
      }

      // For video/remote_video in thumbnail-only view modes (e.g., media_library),
      // add a static play icon overlay on the thumbnail as a visual indicator.
      // Also overlay the cover image (if set) on top of the thumbnail.
      if (in_array($entity->bundle(), ['video', 'remote_video'])
        && isset($build['thumbnail'])
        && !isset($build['field_media_oembed_video'])
        && !isset($build['field_media_video_file'])) {

        $build['#attached']['library'][] = 'varbase_media/varbase_video_player';

        $has_cover_image = isset($build['field_media_cover_image'])
          && isset($build['field_media_cover_image']['#items'])
          && !$build['field_media_cover_image']['#items']->isEmpty();

        if ($has_cover_image) {
          // Open .video-player-icon but do NOT close it on the thumbnail —
          // the cover image div is nested inside so its suffix closes both.
          $build['thumbnail']['#prefix'] = '<div class="video-player-icon">';
          $build['thumbnail']['#suffix'] = '';
          // Nest cover image inside .video-player-icon; suffix closes both divs.
          $build['field_media_cover_image']['#prefix'] = '<div class="media-library-cover-image-overlay">';
          $build['field_media_cover_image']['#suffix'] = '</div></div>';
        }
        else {
          // No cover image: simply wrap the thumbnail and hide the empty field.
          $build['thumbnail']['#prefix'] = '<div class="video-player-icon">';
          $build['thumbnail']['#suffix'] = '</div>';
          if (isset($build['field_media_cover_image'])) {
            $build['field_media_cover_image']['#access'] = FALSE;
          }
        }
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
    if ($extension === 'media_library' && isset($libraries['widget'])) {
      $libraries['widget']['dependencies'][] = 'varbase_media/media_library_enhancements';
    }

    if ($extension === 'ckeditor5') {
      if (\Drupal::moduleHandler()->moduleExists('drimage_improved')) {
        $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'drimage_improved/drimage_improved';
        $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'varbase_media/ckeditor_drimage';
      }
      $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'varbase_media/common';
      $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'varbase_media/common_logged';
      $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'varbase_media/varbase_video_player';
      $libraries['internal.drupal.ckeditor5.media']['dependencies'][] = 'varbase_media/ckeditor_varbase_video_player';

      // Inject the CKEditor 5 media-resize admin CSS.
      if (isset($libraries['internal.drupal.ckeditor5.stylesheets'])) {
        $libraries['internal.drupal.ckeditor5.stylesheets']['dependencies'][] = 'varbase_media/ckeditor5';
      }
    }

    if ($extension === 'ckeditor_media_resize' && isset($libraries['editor'])) {
      $libraries['editor']['dependencies'][] = 'varbase_media/ckeditor5_media_resize';
    }
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry): void {
    $varbase_media_path = \Drupal::service('module_handler')->getModule('varbase_media')->getPath();

    if (isset($theme_registry['entity_embed_container'])) {
      $theme_registry['entity_embed_container']['path'] = $varbase_media_path . '/templates';
    }

  }

  /**
   * Implements hook_preprocess_media_library_item().
   */
  #[Hook('preprocess_media_library_item')]
  public function preprocessMediaLibraryItem(array &$variables): void {
    $variables['attributes']['class'][] = 'media-library-item';
    $variables['attributes']['class'][] = 'media-library-item--grid';
  }

  /**
   * Implements hook_preprocess_entity_embed_container().
   */
  #[Hook('preprocess_entity_embed_container')]
  public function preprocessEntityEmbedContainer(array &$variables): void {
    $variables['url'] = isset($variables['element']['#context']['data-entity-embed-display-settings']['link_url'])
      ? UrlHelper::filterBadProtocol($variables['element']['#context']['data-entity-embed-display-settings']['link_url'])
      : '';
  }

  /**
   * Implements hook_filter_info_alter().
   *
   * Replaces the ckeditor_media_resize module's FilterResizeMedia class with
   * VarbaseFilterResizeMedia, which adds drimage_improved / drimage awareness.
   */
  #[Hook('filter_info_alter')]
  public function filterInfoAlter(array &$info): void {
    if (isset($info['filter_resize_media'])) {
      $info['filter_resize_media']['class'] = VarbaseFilterResizeMedia::class;
    }
  }

  /**
   * Implements hook_editor_js_settings_alter().
   *
   * Changes the CKEditor media resize unit to '%' and adds named resize options
   * (Large 100%, Medium 50%, Small 25%) for the full_html text format.
   */
  #[Hook('editor_js_settings_alter')]
  public function editorJsSettingsAlter(array &$settings): void {
    if (!\Drupal::moduleHandler()->moduleExists('ckeditor_media_resize')) {
      return;
    }
    if (!isset($settings['editor']['formats']['full_html']['editorSettings']['config']['drupalMedia'])) {
      return;
    }

    $settings['editor']['formats']['full_html']['editorSettings']['config']['drupalMedia']['resizeUnit'] = '%';

    if (isset($settings['editor']['formats']['full_html']['editorSettings']['config']['drupalMedia']['resizeOptions'])) {
      $settings['editor']['formats']['full_html']['editorSettings']['config']['drupalMedia']['resizeOptions'][] = [
        'name' => 'resizeMediaImage:100',
        'value' => 100,
        'label' => t('Large'),
      ];
      $settings['editor']['formats']['full_html']['editorSettings']['config']['drupalMedia']['resizeOptions'][] = [
        'name' => 'resizeMediaImage:50',
        'value' => 50,
        'label' => t('Medium'),
      ];
      $settings['editor']['formats']['full_html']['editorSettings']['config']['drupalMedia']['resizeOptions'][] = [
        'name' => 'resizeMediaImage:25',
        'value' => 25,
        'label' => t('Small'),
      ];
    }
  }

  /**
   * Implements hook_preprocess_image().
   *
   * Removes the standalone width/height/sizes Twig variables after they have
   * already been copied into the HTML attributes by ImagePreprocess. This
   * prevents them from leaking into SDC component contexts (e.g. vartheme_bs5
   * :image) where the 'width' prop expects a Bootstrap utility string, not a
   * pixel integer, causing an InvalidComponentException.
   */
  #[Hook('preprocess_image')]
  public function preprocessImage(array &$variables): void {
    // width and height are already in $variables['attributes'] at this point.
    // Removing the standalone variables prevents them from being picked up as
    // SDC component props (which have a different schema than HTML attributes).
    unset($variables['width'], $variables['height']);

    // Unset sizes if it is NULL to avoid NULL-value SDC validation errors.
    if (!isset($variables['sizes'])) {
      unset($variables['sizes']);
    }
  }

  /**
   * Implements hook_views_pre_render().
   *
   * Attaches the varbase_video_player library to any view that has
   * a table display showing media entities, so the play icon CSS is available.
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    // Check if any result row is a media entity with a video/remote_video bundle.
    if (empty($view->result)) {
      return;
    }
    $video_bundles = ['video', 'remote_video'];
    foreach ($view->result as $row) {
      if (isset($row->_entity)
        && $row->_entity->getEntityTypeId() === 'media'
        && in_array($row->_entity->bundle(), $video_bundles)) {
        $view->element['#attached']['library'][] = 'varbase_media/varbase_video_player';
        $view->element['#attached']['library'][] = 'varbase_media/common_logged';
        break;
      }
    }
  }

  /**
   * Implements hook_preprocess_views_view_table().
   *
   * Adds media bundle classes to table rows so that CSS can target
   * video/remote_video rows to show a play icon on their thumbnail.
   */
  #[Hook('preprocess_views_view_table')]
  public function preprocessViewsViewTable(array &$variables): void {
    $view = $variables['view'];
    if (!isset($view->result)) {
      return;
    }
    foreach ($variables['rows'] as $row_index => &$row) {
      if (!isset($view->result[$row_index])) {
        continue;
      }
      $result_row = $view->result[$row_index];
      if (isset($result_row->_entity) && $result_row->_entity->getEntityTypeId() === 'media') {
        $bundle = $result_row->_entity->bundle();
        $row['attributes']->addClass('media-bundle--' . str_replace('_', '-', $bundle));
      }
    }
  }

}
