<?php

declare(strict_types=1);

namespace Drupal\varbase_media;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\varbase_media\Sdc\ImageComponentMetadataDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider tweaks for varbase_media.
 *
 * Swaps the SDC plugin manager class so that vartheme_bs5:image's view_mode
 * enum can be augmented with the site's live media view modes. Drupal core's
 * ComponentPluginManager intentionally skips its alter hook, so a class swap
 * is the supported path to mutate plugin definitions.
 */
final class VarbaseMediaServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    // No-op. The class swap happens in alter() so it runs after Canvas's own
    // ServiceProvider, which also rebinds plugin.manager.sdc.
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if (!$container->hasDefinition('plugin.manager.sdc')) {
      return;
    }
    $definition = $container->getDefinition('plugin.manager.sdc');
    $definition->setClass(ImageComponentMetadataDecorator::class);
    $definition->addArgument(new Reference('entity_display.repository'));
  }

}
