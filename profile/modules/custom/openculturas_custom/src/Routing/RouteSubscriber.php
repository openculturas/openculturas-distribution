<?php

declare(strict_types = 1);

namespace Drupal\openculturas_custom\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
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
    if (($route = $collection->get('entity.user.edit_form')) instanceof Route) {
      $route->setOption('_admin_route', FALSE);
      return;
    }
    if (($route = $collection->get('entity.user.cancel_form')) instanceof Route) {
      $route->setOption('_admin_route', FALSE);
      return;
    }
    if (($route = $collection->get('user.register')) instanceof Route) {
      $route->setRequirement('_user_is_logged_in', 'FALSE');
    }

    if (($route = $collection->get('checklistapi.checklists.update_helper_checklist')) instanceof Route) {
      $route->setDefault('_title', 'OpenCulturas update instructions');
    }

    // Always deny access to '/admin/config/development/update-helper/clear'.
    if (($route = $collection->get('checklistapi.checklists.update_helper_checklist.clear')) instanceof Route) {
      $route->setRequirement('_access', 'FALSE');
    }

  }

}
