<?php

declare(strict_types=1);

namespace Drupal\varbase_media\Twig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig helpers exposing drimage data for Canvas-supplied image URLs.
 *
 * Canvas resolves image-uri props server-side into URLs that point at the
 * canvas_parametrized_width image style. SDC consumers therefore only have a
 * raw `src` available, not a media entity reference. This extension reverses
 * the URL back to a managed file (and parent media), then returns the drimage
 * data object that vartheme_bs5:dynamic-responsive-image needs for client-side
 * responsive rendering via drimage_improved.
 */
final class VarbaseMediaTwigExtension extends AbstractExtension {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly StreamWrapperManagerInterface $streamWrapperManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  public function getFunctions(): array {
    return [
      new TwigFunction('varbase_media_drimage', $this->getDrimageData(...)),
    ];
  }

  /**
   * Resolves an image URL to a drimage_improved data array.
   *
   * @return array|null
   *   Drimage data suitable for vartheme_bs5:dynamic-responsive-image, or NULL
   *   when no matching managed file exists. NULL signals the calling template
   *   to fall back to a plain <img> tag.
   */
  public function getDrimageData(?string $src): ?array {
    if ($src === NULL || $src === '') {
      return NULL;
    }

    $file = $this->loadFileFromUrl($src);
    if (!$file instanceof FileInterface) {
      return NULL;
    }

    // Split file URI into scheme and relative path.
    $file_uri = $file->getFileUri();
    [$scheme, $relative] = explode(':', $file_uri, 2);
    $relative = ltrim($relative, '/');

    // Public files directory (e.g. sites/default/files).
    $subdir = $this->streamWrapperManager
      ->getViaScheme('public')
      ?->getDirectoryPath() ?? 'sites/default/files';

    $config = $this->configFactory->get('drimage_improved.settings');

    // Get image dimensions from the media entity's source field.
    $width = NULL;
    $height = NULL;
    $media = $this->loadMediaFromFile($file);
    if ($media instanceof MediaInterface) {
      try {
        $source_field = $media->getSource()
          ->getSourceFieldDefinition($media->bundle->entity)
          ->getName();
        $item = $media->get($source_field)->first();
        if ($item) {
          $values = $item->getValue();
          $width = $values['width'] ?? NULL;
          $height = $values['height'] ?? NULL;
        }
      }
      catch (\Throwable) {
        // Dimensions are optional; JS will still function without them.
      }
    }

    return [
      'fid' => (int) $file->id(),
      'subdir' => $subdir,
      'scheme' => $scheme,
      'original_source' => $relative,
      'original_width' => $width,
      'original_height' => $height,
      'filename' => pathinfo($file_uri)['basename'],
      'core_webp' => (int) ($config->get('core_webp') ?? 0),
      'imageapi_optimize_webp' => (int) ($config->get('imageapi_optimize_webp') ?? 0),
      'threshold' => (int) ($config->get('threshold') ?? 20),
      'upscale' => (int) ($config->get('upscale') ?? 0),
      'downscale' => (int) ($config->get('downscale') ?? 0),
      'multiplier' => (float) ($config->get('multiplier') ?? 1.0),
      'lazy_offset' => (int) ($config->get('lazy_offset') ?? 100),
      'lazyload' => 'lazy',
      'image_handling' => 'scale',
      'focal_point' => \Drupal::moduleHandler()->moduleExists('focal_point'),
    ];
  }

  /**
   * Resolves a URL back to a managed File entity.
   */
  private function loadFileFromUrl(string $src): ?FileInterface {
    $path = parse_url($src, PHP_URL_PATH) ?: $src;
    $public_path = $this->streamWrapperManager
      ->getViaScheme('public')
      ?->getDirectoryPath() ?? 'sites/default/files';

    $needle = '/' . trim($public_path, '/') . '/';
    $position = strpos($path, $needle);
    if ($position === FALSE) {
      return NULL;
    }

    $relative = substr($path, $position + strlen($needle));
    $relative = preg_replace('#^styles/[^/]+/public/#', '', $relative);
    if ($relative === NULL || $relative === '') {
      return NULL;
    }
    $uri = 'public://' . ltrim($relative, '/');

    $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $uri]);
    if (!$files) {
      return NULL;
    }
    return reset($files);
  }

  /**
   * Finds an image media entity that references the given file.
   */
  private function loadMediaFromFile(FileInterface $file): ?MediaInterface {
    $storage = $this->entityTypeManager->getStorage('media');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'image')
      ->condition('field_media_image.target_id', (int) $file->id())
      ->range(0, 1)
      ->execute();
    if (!$ids) {
      return NULL;
    }
    return $storage->load(reset($ids));
  }

}
