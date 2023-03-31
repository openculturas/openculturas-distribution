<?php

/**
 * @file
 * Install, update and uninstall module functions.
 */

declare(strict_types=1);

use Drupal\user\Entity\Role;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\workflows\Entity\Workflow;

/**
 * Views: Replaces the Content: publish filter with Content: Published status or admin user.
 */
function openculturas_post_update_0001(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $no_warnings = $updater->executeUpdate('openculturas', 'openculturas_post_update_0001');
  if ($no_warnings) {
    $view = Views::getView('entity_reference_node');
    if ($view !== NULL) {
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

/**
 * Views: Set granularity to hour for the not exposed start/end date filter.
 */
function openculturas_post_update_0002(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0002');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Adds missing unpublish moderation state option in bulk edit.
 */
function openculturas_post_update_0003(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0003');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Installs and places the field field_main_profile into user account.
 */
function openculturas_post_update_0004(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0004');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Maps: add pager below map, add result counter, expose no/page option.
 */
function openculturas_post_update_0005(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0005');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Deprecate wrong machine name for state (to_review)/transition (review) and add new one.
 */
function openculturas_post_update_0006(): void {
  $workflows = ['draften', 'magazine_article'];
  foreach ($workflows as $id) {
    /** @var \Drupal\workflows\WorkflowInterface|null $workflow */
    $workflow = Workflow::load($id);
    if ($workflow !== NULL) {
      $workflow->getTypePlugin()->setStateWeight('draft', -2);
      $workflow->getTypePlugin()->addState('review', 'In review');
      $workflow->getTypePlugin()->setStateWeight('review', -1);
      $workflow->getTypePlugin()->setStateWeight('published', 0);
      $workflow->getTypePlugin()->setStateWeight('unpublished', 1);
      if ($workflow->getTypePlugin()->hasState('to_review')) {
        $workflow->getTypePlugin()->setStateLabel('to_review', 'In review (deprecated)');
        $workflow->getTypePlugin()->setStateWeight('to_review', 2);
      }

      $workflow->getTypePlugin()->addTransition('to_review', 'To review', ['draft', 'published', 'review', 'to_review', 'unpublished'], 'review');
      $workflow->getTypePlugin()->deleteTransition('review');
      $workflow->getTypePlugin()->setTransitionFromStates('create_new_draft', ['draft', 'published', 'review', 'to_review']);
      $workflow->getTypePlugin()->setTransitionFromStates('publish', ['draft', 'published', 'review', 'to_review', 'unpublished']);
      $workflow->getTypePlugin()->setTransitionFromStates('unpublish', ['draft', 'published', 'review', 'to_review']);
      $workflow->getTypePlugin()->setTransitionWeight('create_new_draft', -2);
      $workflow->getTypePlugin()->setTransitionWeight('to_review', -1);
      $workflow->getTypePlugin()->setTransitionWeight('publish', 0);
      $workflow->getTypePlugin()->setTransitionWeight('unpublish', 1);
      $workflow->save();
    }
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('authenticated');
  if ($role !== NULL) {
    $role->grantPermission('use draften transition to_review');
    $role->revokePermission('use draften transition review');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('oc_organizer');
  if ($role !== NULL) {
    $role->revokePermission('use draften transition review');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('magazine_editor');
  if ($role !== NULL) {
    $role->grantPermission('use magazine_article transition to_review');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('oc_admin');
  if ($role !== NULL) {
    $role->grantPermission('use magazine_article transition to_review');
    $role->save();
  }
  $view = Views::getView('moderated_content');
  if ($view !== NULL && ($handler_configuration = $view->getHandler('default', 'filter', 'moderation_state'))) {
    foreach ($handler_configuration['group_info']['group_items'] as &$group_item) {
      $value = &$group_item['value'];
      if (isset($value['draften-to_review'])) {
        unset($value['draften-to_review']);
        $value['draften-review'] = 'draften-review';
      }
      if (isset($value['magazine_article-to_review'])) {
        unset($value['magazine_article-to_review']);
        $value['magazine_article-review'] = 'magazine_article-review';
      }
    }
    unset($group_item);
    $handler_configuration['group_info']['group_items'][] = [
      'operator' => 'in',
      'title' => 'In review (deprecated)',
      'value' => ['draften-to_review' => 'draften-to_review', 'magazine_article-to_review' => 'magazine_article-to_review'],
    ];
    $view->setHandler('default', 'filter', 'moderation_state', $handler_configuration);
    $view->save();
  }
}

/**
 * ECA: notification models.
 */
function openculturas_post_update_0007(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0007');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Display revision user and hide author in content moderation view.
 */
function openculturas_post_update_0008(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $no_warnings = $updater->executeUpdate('openculturas', 'openculturas_post_update_0008');
  if ($no_warnings) {
    $view = Views::getView('moderated_content');
    if ($view !== NULL) {
      $display = $view->getDisplay();
      $old_field = $display->getOption('fields');
      $new_field = [];
      $style_option = $display->getOption('style');
      $columns = [];
      $info = [];

      $new_order_keys = [
        'views_bulk_operations_bulk_form',
        'title',
        'type',
        'name',
        'revision_uid',
        'moderation_state',
        'changed',
        'revision_log',
        'operations',
      ];
      foreach ($new_order_keys as $id) {
        if (!isset($old_field[$id])) {
          continue;
        }
        $new_field[$id] = $old_field[$id];
        $columns[$id] = $style_option['options']['columns'][$id];
        $info[$id] = $style_option['options']['info'][$id];
      }
      $style_option['options']['info'] = $info;
      $style_option['options']['columns'] = $columns;
      $display->setOption('style', $style_option);
      $display->setOption('fields', $new_field);
      $view->save();
    }
  }
  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Set the no-snap option for swiffy slider.
 */
function openculturas_post_update_0009(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0009');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Setup user dashboard.
 */
function openculturas_post_update_0010(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $no_warnings = $updater->executeUpdate('openculturas', 'openculturas_post_update_0010');
  if ($no_warnings) {
    $view = Views::getView('my_bookmarks');
    if ($view !== NULL) {
      $display = $view->getDisplay();
      $old_items = $display->getOption('fields');
      $new_items = [];
      $new_order_keys = [
        'link_flag',
        'rendered_entity',
      ];
      $style_option = $display->getOption('style');
      $columns = [];
      $info = [];
      foreach ($new_order_keys as $id) {
        if (!isset($old_items[$id])) {
          continue;
        }
        $new_items[$id] = $old_items[$id];
        $columns[$id] = $style_option['options']['columns'][$id];
        $info[$id] = $style_option['options']['info'][$id];
      }
      $style_option['options']['info'] = $info;
      $style_option['options']['columns'] = $columns;
      $display->setOption('style', $style_option);
      $display->setOption('fields', $new_items);
      _openculturas_post_update_0010_filters($view);
      $display = $view->getDisplay();
      $old_items = $display->getOption('sorts');
      $new_items = [];
      $new_order_keys = [
        'created_1',
        'created',
      ];
      foreach ($new_order_keys as $id) {
        if (!isset($old_items[$id])) {
          continue;
        }
        $new_items[$id] = $old_items[$id];
      }
      $display->setOption('sorts', $new_items);
      $view->save();
    }
    $view = Views::getView('my_bookmarks_taxonomy');
    if ($view !== NULL) {
      $view->setDisplay('all');
      $display = $view->getDisplay();
      $old_items = $display->getOption('fields');
      $new_items = [];
      $new_order_keys = [
        'name',
        'link_flag',
        'view_taxonomy_term',
      ];
      foreach ($new_order_keys as $id) {
        if (!isset($old_items[$id])) {
          continue;
        }
        $new_items[$id] = $old_items[$id];
      }
      $display->setOption('fields', $new_items);
      _openculturas_post_update_0010_filters($view);
      $view->save();
    }

    $view = Views::getView('my_content');
    if ($view !== NULL) {
      $view->setDisplay('my_content_block');
      $display = $view->getDisplay();
      $old_items = $display->getOption('filters');
      $new_items = [];
      $new_order_keys = [
        'type',
        'default_langcode',
        'combine',
        'moderation_state',
        'type_1',
      ];
      foreach ($new_order_keys as $id) {
        if (!isset($old_items[$id])) {
          continue;
        }
        $new_items[$id] = $old_items[$id];
      }
      $display->setOption('filters', $new_items);
      $view->save();
    }

  }
  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

function _openculturas_post_update_0010_filters(ViewExecutable $view): void {
  $view->setDisplay('all');
  $display = $view->getDisplay();
  $old_filters = $display->getOption('filters');
  $new_filters = [];
  $new_order_keys = [
    'status',
    'default_langcode',
  ];
  foreach ($new_order_keys as $id) {
    if (!isset($old_filters[$id])) {
      continue;
    }
    $new_filters[$id] = $old_filters[$id];
  }
  $display->setOption('filters', $new_filters);
}

/**
 * Replace deprecated CamelCase.
 */
function openculturas_post_update_0011(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0011');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}
