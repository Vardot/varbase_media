<?php

namespace Drupal\varbase_media\Plugin\media\Source;

use Drupal\entity_browser_generic_embed\InputMatchInterface;
use Drupal\entity_browser_generic_embed\ValidationConstraintMatchTrait;
use Drupal\media\Plugin\media\Source\OEmbed as BaseOEmbed;

/**
 * Input-matching version of the oEmbed media source.
 */
class RemoteVideo extends BaseOEmbed implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
