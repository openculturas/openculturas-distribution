<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Display account edit in frontend theme.
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setOption('_admin_route', FALSE);
      return;
    }
    if ($route = $collection->get('entity.user.cancel_form')) {
      $route->setOption('_admin_route', FALSE);
      return;
    }
    if ($route = $collection->get('user.register')) {
      $route->setRequirement('_user_is_logged_in', 'FALSE');
    }
  }

}
