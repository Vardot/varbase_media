<?php

namespace Drupal\varbase_media\Plugin\media\Source;

use Drupal\entity_browser_generic_embed\FileInputExtensionMatchTrait;
use Drupal\entity_browser_generic_embed\InputMatchInterface;
use Drupal\media\Plugin\media\Source\File as DrupalCoreMeidaFile;

/**
 * Input-matching version of the Varbase Media File media source.
 */
class VarbaseMediaFile extends DrupalCoreMeidaFile implements InputMatchInterface {

  use FileInputExtensionMatchTrait;

}