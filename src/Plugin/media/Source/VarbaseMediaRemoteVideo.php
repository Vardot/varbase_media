<?php

namespace Drupal\varbase_media\Plugin\media\Source;

use Drupal\media\MediaTypeInterface;
use Drupal\entity_browser_generic_embed\InputMatchInterface;
use Drupal\media\OEmbed\ProviderException;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\Plugin\media\Source\OEmbed as DrupalCoreOEmbed;

/**
 * Input-matching version of the Varbase Media Remote Video media source.
 */
class VarbaseMediaRemoteVideo extends DrupalCoreOEmbed implements InputMatchInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo($value, MediaTypeInterface $bundle) {
    $url = $this->toString($value);

    // Ensure that the URL matches a provider.
    try {
      $provider = $this->urlResolver->getProviderByUrl($url);
    }
    catch (ResourceException | ProviderException) {
      // The URL does not resolve to a known/available oEmbed provider, so this
      // media source does not apply to it.
      return FALSE;
    }

    // Ensure that the provider is allowed.
    if (!in_array($provider->getName(), $this->getProviders(), TRUE)) {
      return FALSE;
    }

    try {
      $endpoints = $provider->getEndpoints();
      $resource_url = reset($endpoints)->buildResourceUrl($url);
      $this->resourceFetcher->fetchResource($resource_url);

      return TRUE;
    }
    catch (ResourceException) {
      // The resource could not be fetched, so this source does not apply.
    }

    return FALSE;

  }

  /**
   * Safely converts a value to a string.
   *
   * The value is converted if it is either scalar, or an object with a
   * __toString() method.
   *
   * @param mixed $value
   *   The value to convert.
   *
   * @return string|null
   *   The string representation of the value, or NULL if the value cannot be
   *   converted to a string.
   */
  protected function toString($value) {
    return is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))
      ? (string) $value
      : NULL;
  }

}
