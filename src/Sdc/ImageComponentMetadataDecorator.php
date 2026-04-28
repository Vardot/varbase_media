<?php

declare(strict_types=1);

namespace Drupal\varbase_media\Sdc;

use Drupal\canvas\Plugin\ComponentPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Theme\Component\ComponentValidator;
use Drupal\Core\Theme\ComponentNegotiator;
use Drupal\Core\Theme\Component\SchemaCompatibilityChecker;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Decorates the SDC plugin manager to populate dynamic enums.
 *
 * The vartheme_bs5:image SDC ships with a baseline `view_mode` enum so it
 * passes Canvas's "needs an enum + meta:enum" validation. At discovery time
 * we extend that enum with every media view mode configured on the site, so
 * editors get a true dropdown of the installed view modes inside the Canvas
 * component sidebar.
 *
 * Extends Canvas's plugin manager (which itself extends core's) because the
 * Canvas service swap happens after varbase_media's, and we need to keep
 * Canvas's processDefinition() / JSON-schema $ref resolution intact.
 */
final class ImageComponentMetadataDecorator extends ComponentPluginManager {

  public function __construct(
    ModuleHandlerInterface $module_handler,
    ThemeHandlerInterface $themeHandler,
    CacheBackendInterface $cacheBackend,
    ConfigFactoryInterface $configFactory,
    ThemeManagerInterface $themeManager,
    ComponentNegotiator $componentNegotiator,
    FileSystemInterface $fileSystem,
    SchemaCompatibilityChecker $compatibilityChecker,
    ComponentValidator $componentValidator,
    string $appRoot,
    private readonly EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {
    parent::__construct(
      $module_handler,
      $themeHandler,
      $cacheBackend,
      $configFactory,
      $themeManager,
      $componentNegotiator,
      $fileSystem,
      $compatibilityChecker,
      $componentValidator,
      $appRoot,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    parent::alterDefinitions($definitions);
    if (!isset($definitions['vartheme_bs5:image'])) {
      return;
    }
    $view_modes = $this->collectMediaViewModes();
    if (!$view_modes) {
      return;
    }
    $definitions['vartheme_bs5:image'] = $this->mergeViewModeEnum(
      $definitions['vartheme_bs5:image'],
      $view_modes,
    );
  }

  /**
   * @return array<string, string>
   *   Map of view mode machine name => human label.
   */
  private function collectMediaViewModes(): array {
    try {
      return $this->entityDisplayRepository->getViewModeOptions('media');
    }
    catch (\Throwable) {
      return [];
    }
  }

  /**
   * Merges live media view modes into the SDC view_mode enum.
   *
   * @param array<string, mixed> $definition
   * @param array<string, string> $view_modes
   *
   * @return array<string, mixed>
   */
  private function mergeViewModeEnum(array $definition, array $view_modes): array {
    foreach (['props'] as $key) {
      if (!isset($definition[$key]['properties']['view_mode'])) {
        return $definition;
      }
    }
    $view_mode_def = &$definition['props']['properties']['view_mode'];
    $existing_enum = $view_mode_def['enum'] ?? [];
    $existing_meta = $view_mode_def['meta:enum'] ?? [];
    foreach ($view_modes as $machine => $label) {
      if (!in_array($machine, $existing_enum, TRUE)) {
        $existing_enum[] = $machine;
      }
      $existing_meta[$machine] = (string) $label;
    }
    $view_mode_def['enum'] = $existing_enum;
    $view_mode_def['meta:enum'] = $existing_meta;
    return $definition;
  }

}
