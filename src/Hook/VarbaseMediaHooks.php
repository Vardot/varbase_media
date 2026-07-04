<?php

namespace Drupal\varbase_media\Hook;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\entity_browser_generic_embed\OverrideHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\varbase_media\Plugin\media\Source\VarbaseMediaRemoteVideo;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;

/**
 * Object-oriented hook implementations for Varbase Media.
 *
 * Drupal 11 replaces procedural hooks with methods that carry the #[Hook]
 * attribute (https://www.drupal.org/node/3442349). The real logic lives here
 * and uses dependency injection; the procedural functions in
 * varbase_media.module are kept only as #[LegacyHook] shims delegating to this
 * service.
 */
class VarbaseMediaHooks {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Constructs a VarbaseMediaHooks object.
   *
   * @param \Drupal\media\IFrameUrlHelper $iframeUrlHelper
   *   The oEmbed iframe URL helper.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resourceFetcher
   *   The oEmbed resource fetcher.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $urlResolver
   *   The oEmbed URL resolver.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   */
  public function __construct(
    protected IFrameUrlHelper $iframeUrlHelper,
    protected ResourceFetcherInterface $resourceFetcher,
    protected UrlResolverInterface $urlResolver,
    protected LanguageManagerInterface $languageManager,
    protected ConfigFactoryInterface $configFactory,
    protected RequestStack $requestStack,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
    protected AccountProxyInterface $currentUser,
    protected EntityRepositoryInterface $entityRepository,
    protected RouteMatchInterface $routeMatch,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected ThemeManagerInterface $themeManager,
    protected ConfigManagerInterface $configManager,
  ) {}

  /**
   * Implements hook_preprocess_field().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(&$variables): void {

    if ($variables['element']['#formatter'] == 'varbase_oembed') {

      // Provide an extra variable to the field template when the field uses
      // a formatter of type 'varbase_oembed'.
      $iframe_url_helper = $this->iframeUrlHelper;

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
        $provider = $entity->field_provider->value;

        // Get langcode from media entity itself.
        $langcode = $entity->language()->getId();

        // If no lang for the media entity, get current lang as 1st fallback.
        if (empty($langcode) || $langcode == 'und') {
          $langcode = $this->languageManager->getCurrentLanguage()->getId();
        }

        // If no data, use "en" as 2nd fallback option.
        if (empty($langcode) || $langcode == 'und') {
          $langcode = 'en';
        }

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
    elseif ($variables['element']['#formatter'] == 'oembed') {
      // Fallback option for oembed old way, In case of change back to oembed.
      // -------
      // Provide an extra variable to the field template when the field uses
      // a formatter of type 'oembed'.
      $resource_fetcher = $this->resourceFetcher;
      $url_resolver = $this->urlResolver;
      $iframe_url_helper = $this->iframeUrlHelper;

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
          $langcode = $this->languageManager->getCurrentLanguage()->getId();
        }

        // If no data, use "en" as 2nd fallback option.
        if (empty($langcode) || $langcode == 'und') {
          $langcode = 'en';
        }

        // Fallback option for oembed old way, In case of change back to oembed
        // fetch resource way.
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

    // Add the Varbase Media Blazy Blurry behaviour.
    $use_blazy_blurry = $this->configFactory->get('varbase_media.settings')->get('use_blazy_blurry');
    if (isset($use_blazy_blurry) && $use_blazy_blurry === TRUE) {

      if (!empty($variables['items'])) {
        foreach ($variables['items'] as &$item) {
          if (!empty($item['content']['#theme'])
            && $item['content']['#theme'] === 'blazy') {

            $entity = $variables['element']['#object'];
            $view_mode = $variables['element']['#view_mode'];
            $field_name = $variables['element']['#field_name'];

            $entity_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
            $field_display = $entity_display->getComponent($field_name);

            if (isset($item['content']['#build'])) {
              $value = [];

              if (isset($item['content']['#build']['item'])) {
                $value = $item['content']['#build']['item']->getValue();
              }
              else {
                $value = $item['content']['#build']['#item']->getValue();
              }

              if (isset($value['target_id']) && isset($value['_attributes'])) {
                $file = File::load($value['target_id']);
                $value['_attributes']['uri'] = $file->getFileUri();

                if (isset($item['content']['#build'])
                  && isset($item['content']['#build']['settings'])
                  && isset($item['content']['#build']['settings']['responsive_image_style'])) {
                  $value['_attributes']['responsive_image_style'] = $item['content']['#build']['settings']['responsive_image_style'];
                }

                if (!empty($item['content']['#image_style'])) {
                  $value['_attributes']['image_style'] = $item['content']['#image_style'];
                }

                if (isset($item['content']['#build']['item'])) {
                  $item['content']['#build']['item']->setValue($value);
                }
                else {
                  $item['content']['#build']['#item']->setValue($value);
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    if ($entity->getEntityType()->id() == 'media' && $entity->bundle->target_id == 'remote_video') {
      // If the field_provider exists in the remote video media type.
      $field_field_media_remote_video_field_provider = FieldConfig::loadByName('media', 'remote_video', 'field_provider');
      if (isset($field_field_media_remote_video_field_provider)) {
        // Fetch the resource from the URL and save in the field_provider.
        $url_resolver = $this->urlResolver;
        $resource_fetcher = $this->resourceFetcher;
        $resource_url = $url_resolver->getResourceUrl(($entity->field_media_oembed_video->value));
        $resource = $resource_fetcher->fetchResource($resource_url);
        $provider = strtolower($resource->getProvider()->getName() ?? '');
        if ($entity->field_provider->value != $provider) {
          $entity->set('field_provider', $provider);
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_media_oembed_iframe')]
  public function preprocessMediaOembedIframe(&$variables): void {
    // Send variables for all oembed iframe theme template.
    $query = $this->requestStack->getCurrentRequest()->query;
    $variables['type'] = $query->get('type');
    $variables['provider'] = $query->get('provider');
    $variables['view_mode'] = $query->get('view_mode');
    $variables['langcode'] = $query->get('langcode');
    $variables['base_path'] = base_path();
    $variables['varbase_media_path'] = $this->moduleHandler->getModule('varbase_media')->getPath();

    // Add media title from resource if available.
    if (!empty($variables['resource']) && !empty($variables['resource']->getTitle())) {
      $variables['media_title'] = $variables['resource']->getTitle();
    }
  }

  /**
   * Implements hook_theme_suggestions_media_oembed_iframe_alter().
   */
  #[Hook('theme_suggestions_media_oembed_iframe_alter')]
  public function themeSuggestionsMediaOembedIframeAlter(&$suggestions, &$vars): void {
    // Suggestions go here.
    $query = $this->requestStack->getCurrentRequest()->query;
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
   * Implements hook_ckeditor_css_alter().
   */
  #[Hook('ckeditor_css_alter')]
  public function ckeditorCssAlter(array &$css, Editor $editor): void {

    // Varbase media path.
    $varbase_media_path = $this->moduleHandler->getModule('varbase_media')->getPath();

    // Attached the varbase media common style.
    $css[] = $varbase_media_path . '/css/varbase_media.common.css';

    // Attached the varbase media common logged in users style.
    $css[] = $varbase_media_path . '/css/varbase_media.common_logged.css';
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_entity_embed_dialog_alter')]
  public function formEntityEmbedDialogAlter(&$form, FormStateInterface $form_state, $form_id): void {
    // Only at the embed step.
    if ($form_state->get('step') == 'embed') {

      // Get the entity values and attributes.
      $entity_element = [];
      $entity_element += $form_state->get('entity_element');
      $form_state->set('entity_element', $entity_element);
      $entity = $form_state->get('entity');

      // Get the entity bundle type.
      $bundle_type = $entity->bundle();
      $builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());

      // Restrict the logic only to the media entity type.
      if ($entity->getEntityTypeId() == "media") {
        switch ($bundle_type) {
          // ----------------------------------------------------------------------.
          case "image":

            // Image Media for review.
            $media_view_mode = $builder->view($entity, 's03');
            $media_markup = $this->renderer->renderRoot($media_view_mode);

            // Render the Embed entity.
            $form['entity'] = [
              '#type' => 'item',
              '#markup' => $media_markup,
            ];

            // Change the "data align" field from radio buttons to Select list.
            $form['attributes']['data-align']['#type'] = 'select';
            $form['attributes']['data-align']['#wrapper_attributes'] = '';
            $form['attributes']['data-align']['#description'] = $this->t('Choose the positioning of the image.');
            $form['attributes']['data-align']['#weight'] = -10;

            // Add description for the caption field.
            $form['attributes']['data-caption'] += [
              '#description' => $this->t('A caption will be displayed under the image, to describe it in context of your content.'),
            ];

            // Adding the updated alt text to the media entity.
            if (isset($entity_element['alt'])) {
              $entity->field_media_image->alt = $entity_element['alt'];
            }

            // Adding the updated title text to the media entity.
            if (isset($entity_element['title'])) {
              $entity->field_media_image->title = $entity_element['title'];
            }

            if (isset($form['attributes']['data-entity-embed-display-settings'])) {
              $form['attributes']['data-entity-embed-display-settings']['link_url']['#description'] = $this->t('Start typing the title of a piece of content to select it. You can also enter an <br /> internal path such as /node/add or an external URL such as http://example.com.');
            }

            $entity->save();
            break;

          // ----------------------------------------------------------------------.
          case "video":
          case "remote_video":

            // Video Media for review.
            $media_view_mode = $builder->view($entity, 's06');
            $media_markup = $this->renderer->renderRoot($media_view_mode);

            // Render the Embed entity.
            $form['entity'] = [
              '#type' => 'item',
              '#markup' => $media_markup,
            ];

            if (isset($form['attributes']['data-align'])) {
              unset($form['attributes']['data-align']);
            }

            if (isset($form['attributes']['data-entity-embed-display-settings'])) {
              unset($form['attributes']['data-entity-embed-display-settings']);
            }

            if (isset($form['attributes']['data-caption'])) {
              unset($form['attributes']['data-caption']);
            }

            if (isset($form['attributes']['data-entity-embed-display'])) {
              $form['attributes']['data-entity-embed-display']['#access'] = FALSE;
              $form['attributes']['data-entity-embed-display']['#default_value'] = 'view_mode:media.original';
            }

            break;

          // ----------------------------------------------------------------------.
          case "gallery":

            // Gallery Media for review.
            $media_view_mode = $builder->view($entity, 'browser_teaser');
            $media_markup = $this->renderer->renderRoot($media_view_mode);

            // Render the Embed entity.
            $form['entity'] = [
              '#type' => 'item',
              '#markup' => '<div class="gallery-entity-embed-dialog-step--embed"><div class="media-library-item">' . $media_markup . '</div></div>',
            ];

            // Render the Embed entity.
            if (isset($form['attributes']['data-align'])) {
              unset($form['attributes']['data-align']);
            }

            if (isset($form['attributes']['data-entity-embed-display-settings'])) {
              unset($form['attributes']['data-entity-embed-display-settings']);
            }

            if (isset($form['attributes']['data-caption'])) {
              unset($form['attributes']['data-caption']);
            }

            if (isset($form['attributes']['data-entity-embed-display'])) {
              $form['attributes']['data-entity-embed-display']['#access'] = FALSE;
              $form['attributes']['data-entity-embed-display']['#default_value'] = 'view_mode:media.full';
            }
            break;

          default:
            if (isset($form['attributes']['data-entity-embed-display-settings'])) {
              unset($form['attributes']['data-entity-embed-display-settings']);
            }
        }

        if ($this->moduleHandler->moduleExists('blazy')) {
          // Attach the Blazy library.
          $form['#attached']['library'][] = 'blazy/load';
        }

        // No revision information or revision log message.
        if (isset($form['revision_information'])) {
          $form['revision_information']['#disabled'] = TRUE;
          $form['revision_information']['#attributes']['style'][] = 'display: none;';
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
      else {
        // Remove all media fields for other entity types like ( node, term, or user).
        if (isset($form['attributes']['data-caption'])) {
          unset($form['attributes']['data-caption']);
        }
        if (isset($form['attributes']['data-align'])) {
          unset($form['attributes']['data-align']);
        }
      }
    }
  }

  /**
   * Implements hook_inline_entity_form_entity_form_alter().
   */
  #[Hook('inline_entity_form_entity_form_alter')]
  public function inlineEntityFormEntityFormAlter(&$entity_form, &$form_state): void {

    // No revision information or revision log message.
    if (isset($entity_form['revision_information'])) {
      $entity_form['revision_information']['#disabled'] = TRUE;
      $entity_form['revision_information']['#attributes']['style'][] = 'display:none;';
      $entity_form['revision_information']['#prefix'] = '<div style="display: none;">';
      $entity_form['revision_information']['#suffix'] = '</div>';
    }

    // Hide revision.
    if (isset($entity_form['revision'])) {
      $entity_form['revision']['#default_value'] = TRUE;
      $entity_form['revision']['#disabled'] = TRUE;
      $entity_form['revision']['#attributes']['style'][] = 'display: none;';
    }

    // Hide revision log message.
    if (isset($entity_form['revision_log_message'])) {
      $entity_form['revision_log_message']['#disabled'] = TRUE;
      $entity_form['revision_log_message']['#attributes']['style'][] = 'display: none;';
    }
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(&$theme_registry): void {
    // Varbase Media path.
    if (isset($theme_registry['entity_embed_container'])) {
      $varbase_media_path = $this->moduleHandler->getModule('varbase_media')->getPath();
      $theme_registry['entity_embed_container']['path'] = $varbase_media_path . '/templates';
    }

    if (isset($theme_registry['fieldset__media_library_widget'])) {
      $varbase_media_path = $this->moduleHandler->getModule('varbase_media')->getPath();
      $theme_registry['fieldset__media_library_widget']['path'] = $varbase_media_path . '/templates';
    }

    if (isset($theme_registry['media_library_item'])) {
      $media_library_path = $this->moduleHandler->getModule('media_library')->getPath();
      $theme_registry['media_library_item']['path'] = $media_library_path . '/templates';
    }

    if (isset($theme_registry['media_library_item__widget'])) {
      $media_library_path = $this->moduleHandler->getModule('media_library')->getPath();
      $theme_registry['media_library_item__widget']['path'] = $media_library_path . '/templates';
    }

  }

  /**
   * Implements hook_preprocess_media_library_item__widget().
   */
  #[Hook('preprocess_media_library_item__widget')]
  public function preprocessMediaLibraryItemWidget(array &$variables): void {
    $variables['content']['remove_button']['#attributes']['class'][] = 'media-library-item__remove';
    $variables['content']['remove_button']['#attributes']['class'][] = 'icon-link';
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
   * Implements hook_preprocess_fieldset__media_library_widget().
   */
  #[Hook('preprocess_fieldset__media_library_widget')]
  public function preprocessFieldsetMediaLibraryWidget(array &$variables): void {
    $variables['attributes']['class'][] = 'media-library-widget';
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_entity_embed_container')]
  public function preprocessEntityEmbedContainer(&$variables): void {
    $variables['url'] = isset($variables['element']['#context']['data-entity-embed-display-settings']['link_url']) ? UrlHelper::filterBadProtocol($variables['element']['#context']['data-entity-embed-display-settings']['link_url']) : '';
  }

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {

    if ($entity->getEntityTypeId() == 'media'
      && $build['#view_mode'] != 'field_preview') {

      // Attached the varbase media common library.
      $build['#attached']['library'][] = 'varbase_media/common';

      if (!($this->currentUser->isAnonymous())) {
        // Attached the varbase media common logged in users library.
        $build['#attached']['library'][] = 'varbase_media/common_logged';
      }

      if ($this->moduleHandler->moduleExists('blazy')) {
        // Attach the Blazy load library.
        $build['#attached']['library'][] = 'blazy/load';

        // Add the Varbase Media Blazy Blurry behaviour.
        $use_blazy_blurry = $this->configFactory->get('varbase_media.settings')->get('use_blazy_blurry');
        if (isset($use_blazy_blurry) && $use_blazy_blurry === TRUE) {
          $build['#attached']['library'][] = 'varbase_media/blazy_blurry';
        }
      }

      if (isset($build['field_media_cover_image'])
        && isset($build['field_media_cover_image']['#items'])) {

        $fields = $build['field_media_cover_image']['#items'];

        if (is_object($fields)) {

          // Hide thumbnail of media if we do have cover image data.
          if (isset($build['thumbnail'])) {
            $build['thumbnail']['#access'] = FALSE;
          }

          $build['field_media_cover_image']['#attached']['library'][] = 'varbase_media/varbase_video_player';
        }
      }

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
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources): void {

    // Remote Video.
    // --------------------------------------------------------------------------.
    $sources['oembed:video']['input_match'] = [
      'constraint' => 'oembed_resource',
      'field_types' => [
        'link',
        'string',
        'string_long',
      ],
    ];
    $sources['oembed:video']['preview'] = TRUE;
    OverrideHelper::pluginClass($sources['oembed:video'], VarbaseMediaRemoteVideo::class);

  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, &$form_state, $form_id): void {
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

    if ($form_id == 'entity_browser_image_browser_form'
        || $form_id == 'entity_browser_media_browser_form'
        || $form_id == 'entity_browser_editor_media_browser_form') {
      $form['#attached']['library'][] = 'varbase_media/auto_fill_media_data';
    }

    if ($form_id === "entity_browser_widgets_config_form") {

      // Attach the varbase media common library.
      $form['#attached']['library'][] = 'varbase_media/common';

      // Add the Varbase Media Blazy Blurry behavior.
      $use_blazy_blurry = $this->configFactory->get('varbase_media.settings')->get('use_blazy_blurry');
      if (isset($use_blazy_blurry) && $use_blazy_blurry === TRUE) {
        $form['#attached']['library'][] = 'varbase_media/blazy_blurry';
      }

      if (!($this->currentUser->isAnonymous())) {
        // Attached the varbase media common logged in users library.
        $form['#attached']['library'][] = 'varbase_media/common_logged';
      }
    }

    if ($form_id === 'editor_media_dialog') {
      if (isset($form_state->getUserInput()['editor_object'])) {
        $editor_object = $form_state->getUserInput()['editor_object'];
        $media_embed_element = $editor_object['attributes'];

        if (isset($media_embed_element['data-entity-type'])
         && $media_embed_element['data-entity-type'] == 'media') {
          $media = $this->entityRepository->loadEntityByUuid('media', $media_embed_element['data-entity-uuid']);

          if (isset($media)) {
            $media_type = $media->bundle();

            switch ($media_type) {
              case 'image':
                if (isset($form['alt'])) {
                  $form['alt']['#required'] = TRUE;

                  $field_definition = $media->getSource()->getSourceFieldDefinition($media->bundle->entity);
                  $item_class = $field_definition->getItemDefinition()->getClass();
                  if (is_a($item_class, ImageItem::class, TRUE)) {
                    $image_field_name = $field_definition->getName();

                    // We'll want the alt text from the same language as the host.
                    if (!empty($editor_object['hostEntityLangcode']) && $media->hasTranslation($editor_object['hostEntityLangcode'])) {
                      $media = $media->getTranslation($editor_object['hostEntityLangcode']);
                    }
                    $alt = $media_embed_element['alt'] ?? $media->{$image_field_name}->alt;
                    $form['alt']['#default_value'] = $alt;
                    $form['alt']['#placeholder'] = $media->{$image_field_name}->alt;
                  }
                }
                break;

              case 'audio':
              case 'file':
                if (isset($form['view_mode'])) {
                  $form['view_mode']['#access'] = FALSE;
                }
                break;

              case 'video':
              case 'remote_video':
                if (isset($form['caption'])) {
                  $form['caption']['#access'] = FALSE;
                  $form['caption']['#default_value'] = FALSE;
                }
                break;
            }
          }
        }
      }
    }

    if ($form_id === 'media_bulk_upload_form') {
      $form['#submit'][] = [$this, 'bulkUploadFormSubmitHandler'];
    }

  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {

    return [
      'media_oembed_iframe__remote_video' => [
        'template' => 'media-oembed-iframe--remote-video',
        'variables' => [
          'provider' => NULL,
          'media' => NULL,
        ],
      ],
      'media_entity_gallery_post' => [
        'variables' => [
          'post' => NULL,
          'shortcode' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_media_oembed_iframe__remote_video')]
  public function preprocessMediaOembedIframeRemoteVideo(&$variables): void {
    // Send variables for the remote_video oembed iframe theme template.
    $query = $this->requestStack->getCurrentRequest()->query;
    $variables['type'] = $query->get('type');
    $variables['provider'] = $query->get('provider');
    $variables['view_mode'] = $query->get('view_mode');
    $variables['langcode'] = $query->get('langcode');
    $variables['base_path'] = base_path();
    $variables['varbase_media_path'] = $this->moduleHandler->getModule('varbase_media')->getPath();

    // Add media title from resource if available.
    if (!empty($variables['resource']) && !empty($variables['resource']->getTitle())) {
      $variables['media_title'] = $variables['resource']->getTitle();
    }
  }

  /**
   * Implements hook_entity_embed_alter().
   */
  #[Hook('entity_embed_alter')]
  public function entityEmbedAlter(array &$build, EntityInterface $entity, array &$context): void {

    // Only for entity embed review inside the CKEditor.
    $preview_route_name = $this->routeMatch->getRouteName();
    if ($preview_route_name == 'embed.preview' || $preview_route_name == 'entity_embed.preview') {

      // Switch view mode for gallery in the CKEditor to show the Browser Teaser.
      if (isset($context['data-embed-button'])
          && $context['data-embed-button'] == 'gallery') {

        // Remove the contextual links.
        if (isset($build['#contextual_links'])) {
          unset($build['#contextual_links']);
        }

        if ($build['#context']['data-entity-embed-display'] == 'view_mode:media.full') {
          $build['#context']['data-entity-embed-display'] = 'view_mode:media.browser_teaser';
          $build['entity']['#view_mode'] = 'browser_teaser';
        }
      }

    }

  }

  /**
   * Implements hook_preprocess_image().
   */
  #[Hook('preprocess_image')]
  public function preprocessImage(&$variables): void {

    // Add the Varbase Media Blazy Blurry behaviour.
    $use_blazy_blurry = $this->configFactory->get('varbase_media.settings')->get('use_blazy_blurry');
    if (isset($use_blazy_blurry) && $use_blazy_blurry === TRUE) {

      if (!empty($variables['attributes']['class'])
        && !empty($variables['attributes']['uri'])) {

        $uri = $variables['attributes']['uri'];
        $current_uri_with_style = $uri;

        if (!empty($variables['attributes']['responsive_image_style'])) {
          $responsive_image_style = ResponsiveImageStyle::load($variables['attributes']['responsive_image_style']);
          // Create Uri from the fallback image style.
          $fallback_image_style_name = $responsive_image_style->getFallbackImageStyle();
          $fallback_image_style = ImageStyle::load($fallback_image_style_name);

          // Set up derivative file information.
          $current_uri_with_style = $fallback_image_style->buildUri($uri);

          // Create responsive image style derivative if necessary.
          if (!file_exists($current_uri_with_style)) {
            $fallback_image_style->createDerivative($uri, $current_uri_with_style);
          }
        }
        elseif (!empty($variables['attributes']['image_style'])) {
          $image_style = ImageStyle::load($variables['attributes']['image_style']);

          // Set up derivative file information.
          $current_uri_with_style = $image_style->buildUri($uri);

          // Create image style derivative if necessary.
          if (!file_exists($current_uri_with_style)) {
            $image_style->createDerivative($uri, $current_uri_with_style);
          }
        }

        $image_style_blazy_blurry = ImageStyle::load('blazy_blurry');
        // Set up derivative file information.
        $blazy_blurry_image = $image_style_blazy_blurry->buildUri($current_uri_with_style);

        // Create Blazy Blurry derivative if necessary.
        if (!file_exists($blazy_blurry_image)) {
          $image_style_blazy_blurry->createDerivative($current_uri_with_style, $blazy_blurry_image);
        }

        if (file_exists($blazy_blurry_image)) {

          // Encode the image in base64 to speed up loading times.
          $file_type = mime_content_type($blazy_blurry_image);
          $blazy_blurry_image_base64 = 'data:' . $file_type . ';base64,' . base64_encode(file_get_contents($blazy_blurry_image));

          // Update the placeholder URI.
          $variables['attributes']['src'] = $blazy_blurry_image_base64;

          unset($variables['attributes']['responsive_image_style']);
          unset($variables['attributes']['image_style']);
          unset($variables['attributes']['uri']);
        }
      }
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
      // Use '/&' as a seporator between arguments.
      $url = str_replace('&', '/&', $url);
      $url = str_replace('?', '/&', $url);
      $parsed_url['query']['url'] = $url;
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, $module): void {
    if ($module === 'media_library' && isset($libraries['widget'])) {
      $libraries['widget']['dependencies'][] = 'varbase_media/media_library_enhancements';
    }
    elseif ($module == 'blazy' && isset($libraries['loading'])) {
      $use_blazy_blurry = $this->configFactory->get('varbase_media.settings')->get('use_blazy_blurry');
      if (isset($use_blazy_blurry) && $use_blazy_blurry === TRUE) {
        unset($libraries['loading']['css']['component']['css/components/blazy.loading.css']);
      }
    }
  }

  /**
   * Varbase media bulk upload form submit handler.
   *
   * Redirect the user back to the Media page (/admin/content/media)
   * after successful bulk upload.
   */
  public function bulkUploadFormSubmitHandler(&$form, &$form_state): void {
    if ($this->moduleHandler->moduleExists('media_library')) {
      $media_library_grid_url = Url::fromRoute('view.media_library.page');
      $form_state->setRedirectUrl($media_library_grid_url);
    }
    else {
      $media_library_table_url = Url::fromRoute('entity.media.collection');
      $form_state->setRedirectUrl($media_library_table_url);
    }
  }

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

  public function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    if (isset($options['langcode'])) {
      $langcode = $options['langcode'];
    }
    else {
      $langcode = LanguageInterface::LANGCODE_DEFAULT;
    }

    $replacements = [];
    if ($type === 'media' && !empty($data['media'])) {
      /** @var \Drupal\media\MediaInterface $media_entity */
      $media_entity = $this->entityRepository->getTranslationFromContext($data['media'], $langcode, ['operation' => 'media_entity_tokens']);

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
        // When the smart [node:share-image:#######:#####] has extra options.
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
   * Managed Varbase Media Library configurations.
   *
   * Fix [Media Library] and the [Media Grid and Media Table]
   * admin pages to work with Drupal ^8.8.x and ^8.7.x .
   */
  public function managedMediaLibraryConfigs(): void {
    $module_path = $this->moduleHandler->getModule('varbase_media')->getPath();
    $managed_config_path = DRUPAL_ROOT . '/' . $module_path . '/config';

    if (version_compare(\Drupal::VERSION, '8.8.0', 'lt') === TRUE) {
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
      $media_config_data = (array) Yaml::decode($media_config_content);
      $media_config_factory = $this->configFactory->getEditable('views.view.media');
      $media_config_factory->setData($media_config_data)->save(TRUE);
    }

    // Override the media library view.
    $media_library_config_path = $managed_config_path . '/views.view.media_library.yml';
    if (file_exists($media_library_config_path)) {
      $media_library_config_content = file_get_contents($media_library_config_path);
      $media_library_config_data = (array) Yaml::decode($media_library_config_content);
      $media_library_config_factory = $this->configFactory->getEditable('views.view.media_library');
      $media_library_config_factory->setData($media_library_config_data)->save(TRUE);
    }

  }

  /**
   * Helper function to rename slick_media config dependencies to slick.
   *
   * @param string $dependency_type
   *   The type of the dependency, such as "module" or "config".
   * @param string $dependency_id
   *   The name of the dependency to be updated.
   * @param callable $map
   *   A callback to be passed to array_map() to actually perform the config
   *   name substitution.
   */
  public function slickMediaFixDependencies($dependency_type, $dependency_id, callable $map): void {
    $dependents = $this->configManager
      ->findConfigEntityDependents($dependency_type, [$dependency_id]);

    $key = 'dependencies.' . $dependency_type;
    $key2 = 'dependencies.enforced.' . $dependency_type;

    foreach (array_keys($dependents) as $name) {
      $config = $this->configFactory->getEditable($name);
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
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   The entity object for media with image.
   * @param string|null $style_name
   *   The name of the image style.
   *
   * @return string|null
   *   The image url by media entity and image style name.
   */
  public function imageUrl(MediaInterface $media_entity, ?string $style_name = NULL) {

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
          return $this->fileUrlGenerator->generateAbsoluteString($file_entity->get('uri')->getString());
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
  public function defaultImageFieldName(MediaInterface $media_entity): string {

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
    // Media entities without field image or cover image, get the thumbnail.
    else {
      return 'thumbnail';
    }

  }

  /**
   * Get a url for the fullback social share image from the active theme.
   *
   * @return string
   *   The URL of the fallback social share image.
   */
  public function getFallbackSocialShareImageUrl(): string {
    $active_theme = $this->themeManager->getActiveTheme()->getPath();
    $request = $this->requestStack->getCurrentRequest();
    $origin_url = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
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
   *   (optional) The specific field name to check for the share image. If not
   *   provided, the function will check fields in a priority order.
   *
   * @return string
   *   The URL of the share image, or the fallback URL if not found.
   */
  public function getNodeShareImageUrl(Node $node, ?string $style_name = 'social_large', ?string $field_name = ''): string {

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

    // When image url still empty, set the fallback social share image from the
    // default active theme.
    if ($image_url === '') {
      $image_url = $this->getFallbackSocialShareImageUrl();
    }

    return $image_url;
  }

}
