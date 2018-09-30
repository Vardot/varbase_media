<?php

namespace Drupal\varbase_media\Plugin\media\Source;

use Drupal\media\Plugin\media\Source;
use Drupal\media\Plugin\media\Source\OEmbed;
use Drupal\entity_browser_generic_embed\InputMatchInterface;
use Drupal\entity_browser_generic_embed\ValidationConstraintMatchTrait;


/**
 * Input-matching version of the OEmbed media source.
 */
class RemoteEmbed extends OEmbed implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
