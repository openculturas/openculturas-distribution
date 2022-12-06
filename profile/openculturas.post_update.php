<?php

/**
 * @file
 * Install, update and uninstall module functions.
 */

use Drupal\views\Views;

/**
 * Views: Replaces the Content: publish filter with Content: Published status or admin user
 */
function openculturas_post_update_0001() {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $no_warnings = $updater->executeUpdate('openculturas', 'openculturas_post_update_0001');
  if ($no_warnings) {
    $view = Views::getView('entity_reference_node');
    if ($view) {
      if ($view->getHandler('default', 'filter', 'status')) {
        $view->removeHandler('default', 'filter', 'status');
      }
      if ($view->getHandler('er_organizer', 'filter', 'status')) {
        $view->removeHandler('er_organizer', 'filter', 'status');
      }
      if ($view->getHandler('er_organizer', 'filter', 'status_1')) {
        $view->removeHandler('er_organizer', 'filter', 'status_1');
      }
      $view->save();
    }
  }

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}
