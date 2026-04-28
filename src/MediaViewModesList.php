<?php

declare(strict_types=1);

namespace Drupal\varbase_media;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

/**
 * Lists media view modes for use in components such as vartheme_bs5:image.
 *
 * The list is consumed by SDC props that need to expose a media view mode
 * selector, and by render helpers that translate (media entity, view mode)
 * pairs to drimage-aware render arrays.
 */
final class MediaViewModesList {

  public function __construct(
    private readonly EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {}

  /**
   * Returns media view modes keyed by machine name.
   *
   * @return array<string, string>
   *   Map of view mode machine name => human label.
   */
  public function getAll(): array {
    return $this->entityDisplayRepository->getViewModeOptions('media');
  }

  /**
   * Returns view mode machine names as a numeric list.
   *
   * @return string[]
   */
  public function getMachineNames(): array {
    return array_keys($this->getAll());
  }

}
