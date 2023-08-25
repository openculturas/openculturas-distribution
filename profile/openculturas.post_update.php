<?php

/**
 * @file
 * Install, update and uninstall module functions.
 */

declare(strict_types = 1);

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\asset_injector\AssetInjectorInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\update_helper\ConfigName;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\WorkflowInterface;

/**
 * Views: Replaces views filter.
 *
 * Filter: "Content: publish filter" with "Content: Published status or admin user".
 */
function openculturas_post_update_0001(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0001');

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
    if ($workflow instanceof WorkflowInterface) {
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
  foreach (\Drupal::configFactory()->listAll('workflows.workflow.') as $config_name) {
    \Drupal::configFactory()->reset($config_name);
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('authenticated');
  if ($role instanceof RoleInterface) {
    $role->grantPermission('use draften transition to_review');
    $role->revokePermission('use draften transition review');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('oc_organizer');
  if ($role instanceof RoleInterface) {
    $role->revokePermission('use draften transition review');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('magazine_editor');
  if ($role instanceof RoleInterface) {
    $role->grantPermission('use magazine_article transition to_review');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('oc_admin');
  if ($role instanceof RoleInterface) {
    $role->grantPermission('use magazine_article transition to_review');
    $role->save();
  }
  $view = Views::getView('moderated_content');
  if ($view instanceof ViewExecutable && ($handler_configuration = $view->getHandler('default', 'filter', 'moderation_state'))) {
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
    if ($view instanceof ViewExecutable) {
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
    if ($view instanceof ViewExecutable) {
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
    if ($view instanceof ViewExecutable) {
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
    if ($view instanceof ViewExecutable) {
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

/**
 * Fix language handling for the notification recipient.
 */
function openculturas_post_update_0012(): string {
  \Drupal::configFactory()->getEditable('eca.eca.process_zo0uuo3')->delete();
  \Drupal::configFactory()->getEditable('eca.model.process_zo0uuo3')->delete();
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0012');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Limit type filter options for my content table.
 */
function openculturas_post_update_0013(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0013');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Disable snap in the swiffy slider formatter.
 */
function openculturas_post_update_0014(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0014');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Enable ajax in view my_recommendations.
 */
function openculturas_post_update_0015(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0015');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Cleanup view locations and remove perf. bottleneck.
 */
function openculturas_post_update_0016(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0016');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Configuration update.
 */
function openculturas_post_update_0017(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0017');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Update all entity view displays that contain date_augmenter third party settings.
 */
function openculturas_post_update_0018(?array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityDisplayInterface $display): bool {
    if ($display instanceof EntityViewDisplayInterface) {
      $components = $display->getComponents();
      foreach ($components as $options) {
        if (isset($options['third_party_settings']['date_augmenter'])) {
          $display->save();
          return FALSE;
        }
      }
    }
    return FALSE;
  };

  $config_entity_updater->update($sandbox, 'entity_view_display', $callback);
}

/**
 * Updates the oc_gin_theme_overrides asset.
 */
function openculturas_post_update_oc_gin_theme_overrides_paragraphs_behaviors(?array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (AssetInjectorInterface $asset): bool {
    if ($asset->id() !== 'oc_gin_theme_overrides') {
      return FALSE;
    }
    $code = $asset->getCode();
    if (str_contains($code, 'Paragraphs behaviors tabs highlight')) {
      return FALSE;
    }
    $new_code = <<<CSS

/* Paragraphs behaviors tabs highlight + fix border */

.is-horizontal .paragraphs-tabs:first-of-type {
  background-color: #fdf7ebdd; /* keeping opacity, using  color of --gin-bg-warning-light */
}

.is-horizontal .paragraphs-tabs:first-of-type::after {
  bottom: 0;
}

CSS;
    $asset->set('code', $code . PHP_EOL . $new_code . PHP_EOL);
    return TRUE;
  };

  $config_entity_updater->update($sandbox, 'asset_injector_css', $callback);
}

/**
 * Enable mastodon.
 */
function openculturas_post_update_0019(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0019');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Adds the oembed_provider bucket video.
 */
function openculturas_post_update_0020(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0020');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Installs view display for node types,vocabularies.
 */
function openculturas_post_update_0021(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $no_warning = $updater->executeUpdate('openculturas', 'openculturas_post_update_0021');
  if ($no_warning) {
    $config_list = [
      'page_type' => 'core.entity_view_display.taxonomy_term.page_type.teaser_unified',
      'location_type' => 'core.entity_view_display.taxonomy_term.location_type.teaser_unified',
      'event_type' => 'core.entity_view_display.taxonomy_term.event_type.teaser_unified',
      'article_type' => 'core.entity_view_display.taxonomy_term.article_type.teaser_unified',
    ];
    // Import configurations.
    $configFactory = \Drupal::configFactory();
    /** @var \Drupal\config_update\ConfigReverter $configUpdater */
    $configUpdater = \Drupal::service('config_update.config_update');
    /** @var \Drupal\update_helper\UpdateLogger $logger */
    $logger = \Drupal::service('update_helper.logger');
    foreach ($config_list as $vocabulary_id => $full_config_name) {
      $vocabulary = Vocabulary::load($vocabulary_id);
      if (!$vocabulary instanceof VocabularyInterface) {
        $logger->warning(sprintf('Unable to import %s config, because vocabulary %s not found.', $full_config_name, $vocabulary_id));
        continue;
      }
      $config_name = ConfigName::createByFullName($full_config_name);

      if (!empty($configFactory->get($full_config_name)->getRawData())) {
        $logger->warning(sprintf('Importing of %s config will be skipped, because configuration already exists.', $full_config_name));
        continue;
      }

      if (!$configUpdater->import($config_name->getType(), $config_name->getName())) {
        $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
        continue;
      }
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
  }

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Installs paragraphs_type_permissions and the permissions.
 */
function openculturas_post_update_0022(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0022');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Installs openculturas_teaser.
 */
function openculturas_post_update_0023(): string {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->install(['paragraph_view_mode']);

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0023');
  $bundles = ['page', 'faq', 'article'];
  foreach ($bundles as $bundle) {
    /** @var \Drupal\Core\Field\FieldConfigInterface|null $field */
    $field = FieldConfig::loadByName('node', $bundle, 'field_content_paragraphs');
    if (!$field instanceof FieldConfigInterface) {
      continue;
    }
    $handler_settings = $field->getSetting('handler_settings');
    if (!isset($handler_settings['target_bundles']['teaser_wrapper'])) {
      $handler_settings['target_bundles']['teaser_wrapper'] = 'teaser_wrapper';
    }
    ksort($handler_settings['target_bundles']);
    $field->setSetting('handler_settings', $handler_settings);
    $field->save();
  }
  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Allows p-Element in advanced_html.
 */
function openculturas_post_update_0024(): string {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->install(['paragraph_view_mode']);

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0024');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Backport of view-displays (vocabulary, related_article, related_date, related_event, related_location)
 */
function openculturas_post_update_0025(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0025');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Build always the view output.
 */
function openculturas_post_update_views_refactor_0001(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0001');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Disable view content_recent, who_s_new, who_s_online.
 */
function openculturas_post_update_views_refactor_0002(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0002');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Change texts, rendering language of views and view-displays.
 */
function openculturas_post_update_views_refactor_0003(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $no_warnings = $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0003');

  if ($no_warnings) {
    $view = Views::getView('vocabulary');
    if ($view instanceof ViewExecutable) {
      $view->initDisplay();
      if ($view->displayHandlers->has('block_filter_vocabulary')) {
        $view->setDisplay('block_filter_vocabulary');
        $display = $view->getDisplay();
        $display->display['display_title'] = 'Select vocabulary for term grid';
        $display->setOption('block_description', '');
        $view->save();
      }
    }
  }

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Add new filter type filter to view event_catalogue, locations.
 */
function openculturas_post_update_views_refactor_0004(): string {
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $fields = $entity_field_manager->getFieldStorageDefinitions('node');
  if (!isset($fields['field_sub_type'])) {
    return '[Skipped] field field_sub_type (node) is not installed';
  }
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0004');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Add default_langcode filter to frontpage view.
 */
function openculturas_post_update_views_refactor_0005(): string {
  $view = Views::getView('frontpage');
  if ($view instanceof ViewExecutable) {
    $view->initDisplay();
    $displays_ids = [
      'block_front_all',
      'block_front_articles_x',
      'block_front_events',
      'block_front_locations',
      'block_front_profiles',
    ];
    foreach ($displays_ids as $displays_id) {
      if (!$view->displayHandlers->has($displays_id)) {
        return sprintf('[Skipped] Display %s in view frontpage is not installed', $displays_id);
      }
    }
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0005');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Change rendering_language and display title of display by_location_type in view related_location.
 */
function openculturas_post_update_views_refactor_0006(): string {
  $view = Views::getView('related_location');
  if ($view instanceof ViewExecutable) {
    $view->initDisplay();
    $displays_ids = [
      'by_location_type',
    ];
    foreach ($displays_ids as $displays_id) {
      if (!$view->displayHandlers->has($displays_id)) {
        return sprintf('[Skipped] Display %s in view related_location is not installed', $displays_id);
      }
    }
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0006');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Change rendering_language and display title of display related_event_by_type in view related_event.
 */
function openculturas_post_update_views_refactor_0007(): string {
  $view = Views::getView('related_event');
  if ($view instanceof ViewExecutable) {
    $view->initDisplay();
    $displays_ids = [
      'related_event_by_type',
    ];
    foreach ($displays_ids as $displays_id) {
      if (!$view->displayHandlers->has($displays_id)) {
        return sprintf('[Skipped] Display %s in view related_event is not installed', $displays_id);
      }
    }
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0007');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Change empty content of display block_front_dates in view related_date.
 */
function openculturas_post_update_views_refactor_0008(): string {
  $view = Views::getView('related_date');
  if ($view instanceof ViewExecutable) {
    $view->initDisplay();
    $displays_ids = [
      'block_front_dates',
    ];
    foreach ($displays_ids as $displays_id) {
      if (!$view->displayHandlers->has($displays_id)) {
        return sprintf('[Skipped] Display %s in view related_date is not installed', $displays_id);
      }
    }
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0008');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Change rendering_language and display title of display block_1 in view related_article.
 */
function openculturas_post_update_views_refactor_0009(): string {
  $view = Views::getView('related_article');
  if ($view instanceof ViewExecutable) {
    $view->initDisplay();
    $displays_ids = [
      'block_1',
    ];
    foreach ($displays_ids as $displays_id) {
      if (!$view->displayHandlers->has($displays_id)) {
        return sprintf('[Skipped] Display %s in view related_article is not installed', $displays_id);
      }
    }
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0009');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Change rendering_language and display title of the displays in view related_pages.
 */
function openculturas_post_update_views_refactor_0010(): string {
  $view = Views::getView('related_pages');
  if (!$view instanceof ViewExecutable) {
    return '[Skipped] View related_pages does not exists';
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_views_refactor_0010');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Limit allowed_views in field_view.
 */
function openculturas_post_update_views_refactor_0011(): string {
  /** @var \Drupal\Core\Field\FieldConfigInterface|null $field */
  $field = FieldConfig::loadByName('paragraph', 'view', 'field_view');
  if (!$field instanceof FieldConfigInterface) {
    return '[Skipped] Field field_view not fround';
  }
  $allowed_views_setting = $field->getSetting('allowed_views');

  $allowed_views = [
    'event_catalogue',
    'faq',
    'oc_frontpage',
    'frontpage',
    'locations',
    'related_article',
    'related_date',
    'related_profile',
    'search',
    'related_sponsor',
    'vocabulary',
  ];
  foreach ($allowed_views as $view_name) {
    $allowed_views_setting[$view_name] = $view_name;
  }
  $views = Views::getEnabledViews();

  $allowed_views_setting = array_filter($allowed_views_setting, static fn($view_name): bool => isset($views[$view_name]), ARRAY_FILTER_USE_KEY);
  $field->setSetting('allowed_views', $allowed_views_setting);
  $field->save();
  return sprintf('Limited allowed views in field_view to: %s', implode(',', array_filter($allowed_views_setting)));
}

/**
 * Add event type filter to upcoming dates display (calendar).
 */
function openculturas_post_update_0026(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $no_warnings = $updater->executeUpdate('openculturas', 'openculturas_post_update_0026');
  if ($no_warnings) {
    $view = Views::getView('related_date');
    if ($view instanceof ViewExecutable) {
      $view->initDisplay();
      $view->setDisplay('upcoming_dates');
      $display = $view->getDisplay();
      $old_filters = $display->getOption('filters');
      $new_filters = [];
      $new_order_keys = [
        'status',
        'type',
        'field_category_target_id',
        'field_sub_type_target_id',
        'field_date_value_1',
        'field_date_end_value',
        'field_a11y_features_target_id',
        'field_address_location_proximity',
        'title',
        'title_1',
        'field_date_duration',
        'field_date_value',
        'field_date_end_value_1',
      ];
      foreach ($new_order_keys as $id) {
        if (!isset($old_filters[$id])) {
          continue;
        }
        $new_filters[$id] = $old_filters[$id];
      }
      $display->setOption('filters', $new_filters);
      $view->save();
    }
  }

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Disable grouping in upcoming dates map.
 */
function openculturas_post_update_0027(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0027');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Show related content details-container as open by default.
 */
function openculturas_post_update_0028(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0028');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Show Sub-categories details-container as open by default.
 */
function openculturas_post_update_0029(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0029');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Add missing related content details-container.
 */
function openculturas_post_update_0030(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0030');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Allows to change type,focus,column via bulk edit.
 */
function openculturas_post_update_0031(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0031');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Index type taxonomies.
 */
function openculturas_post_update_0032(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0032');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Install missing by_term display in view related_date.
 */
function openculturas_post_update_0033(): string {
  $view = Views::getView('related_date');
  if ($view instanceof ViewExecutable) {
    $view->initDisplay();
    $displays_ids = [
      'by_term',
    ];
    foreach ($displays_ids as $displays_id) {
      if ($view->displayHandlers->has($displays_id)) {
        return sprintf('[Skipped] Display %s in view related_date is already installed', $displays_id);
      }
    }
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0033');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Add field sub_type into default form of node type article, event, location and page.
 */
function openculturas_post_update_0034(): string {
  /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository */
  $entity_display_repository = \Drupal::service('entity_display.repository');
  $formDisplay = $entity_display_repository->getFormDisplay('node', 'article');
  if ($formDisplay->getComponent('field_sub_type') !== NULL) {
    return '[Skipped] field_sub_type is already not hidden.';
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0034');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}
