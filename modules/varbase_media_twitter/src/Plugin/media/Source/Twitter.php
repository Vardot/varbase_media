<?php

namespace Drupal\varbase_media_twitter\Plugin\media\Source;

use Drupal\entity_browser_generic_embed\InputMatchInterface;
use Drupal\entity_browser_generic_embed\ValidationConstraintMatchTrait;
use Drupal\media_entity_twitter\Plugin\media\Source\Twitter as BaseTwitter;

/**
 * Input-matching version of the Twitter media source.
 */
class Twitter extends BaseTwitter implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
