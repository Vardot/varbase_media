<?php

namespace Drupal\varbase_media\Plugin\media\Source;

use Drupal\entity_browser_generic_embed\FileInputExtensionMatchTrait;
use Drupal\entity_browser_generic_embed\InputMatchInterface;
use Drupal\media\Plugin\media\Source\AudioFile as DrupalCoreMeidaAudioFile;

/**
 * Input-matching version of the Varbase Media Audio File media source.
 */
class VarbaseMediaAudioFile extends DrupalCoreMeidaAudioFile implements InputMatchInterface {

  use FileInputExtensionMatchTrait;

}