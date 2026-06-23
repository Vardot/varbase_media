<?php

namespace Drupal\varbase_media\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Psr\Container\ContainerInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\ckeditor_media_resize\Plugin\Filter\FilterResizeMedia;

/**
 * Provides a filter to apply resizing of media images.
 *
 * @Filter(
 *   id = "filter_resize_media",
 *   title = @Translation("Resize media images"),
 *   description = @Translation("Uses a <code>data-media-width</code> attribute on <code>&lt;drupal-media&gt;</code> tags to apply resizing of media images. This filter needs to run before the <strong>Embed media</strong> filter and requires the <strong>Limit allowed HTML tags and correct faulty HTML</strong> to be active."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 90,
 * )
 */
class VarbaseFilterResizeMedia extends FilterResizeMedia {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $ckeditor5_plugin = $this->ckeditor5PluginManager
      ->createInstance('ckeditor_media_resize_mediaResize');
    $ckeditor5_plugin_config = $ckeditor5_plugin->getConfiguration();
    $this->resizeWidthAttribute = $ckeditor5_plugin->getPluginDefinition()->toArray()['ckeditor5']['config']['drupalMedia']['dataAttribute'];
    $result = new FilterProcessResult($text);

    // If the current route is 'media.filter.preview' the filter processing is
    // applying for media upcasted during loading inside the editor.
    $processing_in_editor = $this->routeMatch->getCurrentRouteMatch()->getRouteName() == 'media.filter.preview';

    // Apply image styles only if the corresponding setting in the text format
    // configuration is enabled and if the filter is NOT processing during the
    // loading of the text inside the ckeditor.
    $apply_image_styles = !empty($ckeditor5_plugin_config['apply_image_styles']) && !$processing_in_editor;

    if (stristr($text, $this->resizeWidthAttribute) !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      /** @var \DOMNode $node */
      foreach ($xpath->query('//*[@' . $this->resizeWidthAttribute . ']') as $node) {
        $this->processMediaDom($processing_in_editor, $node, $apply_image_styles, $ckeditor5_plugin_config);
      }
      /** @var \DOMNode $node */
      foreach ($xpath->query('//figure/drupal-media[@' . $this->resizeWidthAttribute . ']') as $node) {
        $this->processMediaDom($processing_in_editor, $node, $apply_image_styles, $ckeditor5_plugin_config);
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * Applies width, class and data attributes to passed media DOMNodes.
   */
  private function processMediaDom(bool $processing_in_editor, \DOMNode $node, bool $apply_image_styles, array $ckeditor5_plugin_config) : void {
    $width = '';
    if (!$processing_in_editor) {
      [
        $width,
        $attribute_value,
      ] = $this->getStyleAttributeFromNode($node);

      // If the parent DOM node of the current media wrapper is a <figure>,
      // set it as 'figure-resized' and apply the resize styles.
      if ($node->parentNode->nodeName == 'figure') {
        $node->parentNode->setAttribute('style', $attribute_value);
        $parentNodeCssClasses = $node->parentNode->getAttribute('class');
        $parentNodeAttributeClassValue = 'figure-resized';
        if (isset($parentNodeCssClasses)) {
          if (strpos($parentNodeCssClasses, 'figure-resized') == FALSE) {
            $parentNodeAttributeClassValue = $parentNodeCssClasses . ' figure-resized';
          }
          else {
            $parentNodeAttributeClassValue = $parentNodeCssClasses;
          }
        }

        // Set the width to the figure element.
        $node->parentNode->setAttribute('class', $parentNodeAttributeClassValue);

      }
      else {
        // Apply the resize style directly to the current node.
        $node->setAttribute('style', $attribute_value);
      }

      // Set a class that allows to style resized media.
      $node->setAttribute(
        'class',
        $node->getAttribute('class')
          ? $node->getAttribute('class') . ' media-embed-resized'
          : 'media-embed-resized'
      );
    }
    if ($apply_image_styles) {
      $view_mode = $this->getViewModeByWidth($width, $ckeditor5_plugin_config);
      if (!($this->moduleHandler->moduleExists('drimage_improved') || $this->moduleHandler->moduleExists('drimage'))) {
        if ($view_mode) {
          $node->setAttribute('data-view-mode', $view_mode);
        }
      }
    }
  }

  /**
   * Gets appended $width style attribute and for given node and attribute.
   */
  private function getStyleAttributeFromNode(\DOMNode $node): array {
    $width = $node->getAttribute($this->resizeWidthAttribute);
    $attribute_value = $node->getAttribute('style');

    // Replace existing width style with new one.
    $styles = \explode(';', $attribute_value);
    $to_replace = '';
    foreach ($styles as $style) {
      if (\mb_strpos($style, 'width') === 0) {
        $to_replace = $style;
        break;
      }
    }
    if ($to_replace) {
      $attribute_value = \str_replace($to_replace, 'width:' . $width, $attribute_value);
    }
    else {
      $attribute_value .= 'width:' . $width . ';';
    }

    return [(int) $width, $attribute_value];
  }

  /**
   * Determines which view mode the resized media image should be rendered with.
   */
  private function getViewModeByWidth(int $width, array $ckeditor5_plugin_config): string {
    $image_styles = $this->entityTypeManager->getStorage('image_style')
      ->loadMultiple($ckeditor5_plugin_config['image_styles']);
    $image_styles_widths_map = $this->getImageStyleWidths($image_styles);
    foreach ($image_styles_widths_map as $image_style_name => $image_style_width) {
      if ($image_style_width >= $width) {
        return $image_style_name;
      }
    }
    return '';
  }

  /**
   * Extracts width options from given image styles and their effects.
   */
  private function getImageStyleWidths(array $image_styles): array {
    $widths = [];
    foreach ($image_styles as $name => $style) {
      $width = 0;
      foreach ($style->getEffects() as $effect) {
        $effect_config = $effect->getConfiguration();
        if (!empty($effect_config['data']['width'])) {
          $width = \max($width, $effect_config['data']['width']);
        }
      }
      $widths[$name] = $width;
    }
    \asort($widths, \SORT_NUMERIC);
    return \array_filter($widths);
  }

}
