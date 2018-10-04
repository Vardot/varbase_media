<?php

namespace Drupal\varbase_media\Plugin\media\Source;

use Drupal\entity_browser_generic_embed\FileInputExtensionMatchTrait;
use Drupal\entity_browser_generic_embed\InputMatchInterface;
use Drupal\media\Plugin\media\Source\VideoFile as DrupalCoreMeidaVideoFile;

/**
 * Input-matching version of the Varbase Media Video File media source.
 */
class VarbaseMediaVideoFile extends DrupalCoreMeidaVideoFile implements InputMatchInterface {

  use FileInputExtensionMatchTrait;

}