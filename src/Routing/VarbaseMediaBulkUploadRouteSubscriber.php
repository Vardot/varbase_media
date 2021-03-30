<?php

namespace Drupal\varbase_media\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Varbase Media Builk Upload dynamic route events.
 */
class VarbaseMediaBulkUploadRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change the route of the (/media/bulk-upload/media_bulk_upload) page
    // to be (/admin/content/media/bulk-upload) instead.
    if ($route = $collection->get('media_bulk_upload.upload_form')) {
      $route->setPath('/admin/content/media/bulk-upload/{media_bulk_config}');
    }
  }

}
