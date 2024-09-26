<?php

/**
 * @file
 * Install, update and uninstall module functions.
 */

declare(strict_types=1);

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\block\BlockInterface;
use Drupal\content_translation\BundleTranslationSettingsInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\search_api\Entity\Index;
use Drupal\update_helper\ConfigName;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\views\ViewEntityInterface;
use Drupal\views\Views;

/**
 * Implements hook_removed_post_updates().
 */
function openculturas_removed_post_updates(): array {
  return [
    'openculturas_post_update_0045' => '2.2.0',
    'openculturas_post_update_interaction_button_section' => '2.2.0',
    'openculturas_post_update_password_policy' => '2.2.0',
    'openculturas_post_update_field_block_ref_cleanup' => '2.2.0',
    'openculturas_post_update_viewfield_missing_handler' => '2.2.0',
    'openculturas_post_update_tour_access' => '2.2.0',
    'openculturas_post_update_install_admin_toolbar_links_access_filter' => '2.2.0',
    'openculturas_post_update_formtips_replace_people_reference_selector' => '2.2.0',
    'openculturas_post_update_add_filter_autop_to_minimal_html' => '2.2.0',
    'openculturas_post_update_smart_date_recur_access' => '2.2.0',
    'openculturas_post_update_ckeditor5_migration' => '2.2.0',
    'openculturas_post_update_compact_address_map' => '2.2.0',
    'openculturas_post_update_enable_field_supporters_for_all' => '2.2.0',
    'openculturas_post_update_add_field_groups_to_page' => '2.2.0',
    'openculturas_post_update_moderation_widget_to_content_area' => '2.2.0',
    'openculturas_post_update_related_content_via_term_node_tid_depth' => '2.2.0',
    'openculturas_post_update_enable_media_edit' => '2.2.0',
    'openculturas_post_update_missing_permission_media_entity_download' => '2.2.0',
    'openculturas_post_update_add_field_badges' => '2.2.0',
    'openculturas_post_update_related_date_alternative_pager_offset' => '2.2.0',
    'openculturas_post_update_add_ief_for_location_ref_in_date' => '2.2.0',
    'openculturas_post_update_add_info_block_about_moderation_states_for_date' => '2.2.0',
    'openculturas_post_update_replace_focal_point_with_image_crop' => '2.2.0',
    'openculturas_post_update_enable_default_filename_sanitization_configuration' => '2.2.0',
    'openculturas_post_update_add_missing_default_translation_filter' => '2.2.0',
    'openculturas_post_update_change_field_group_type_type_terms' => '2.2.0',
    'openculturas_post_update_enable_media_edit_2' => '2.2.0',
    'openculturas_post_update_move_field_layout_switcher' => '2.2.0',
    'openculturas_post_update_source_string_spell_corrections' => '2.2.0',
  ];
}

/**
 * No-op update. Replaced by openculturas_post_update_issue_3446002_1.
 */
function openculturas_post_update_issue_3446002(array &$sandbox): void {

}

/**
 * Replaces OC custom views filter plugin with smart date provided filter.
 */
function openculturas_post_update_issue_3446002_1(array &$sandbox): void {
  // Issue https://www.drupal.org/project/openculturas/issues/3446002.
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', static function (ViewEntityInterface $view): bool {
    $displays = $view->get('display');
    if (!is_array($displays)) {
      return FALSE;
    }

    $update = FALSE;
    foreach ($displays as &$display) {
      if (!isset($display['display_options']['filters'])) {
        continue;
      }

      foreach ($display['display_options']['filters'] as &$filter) {
        if ($filter['plugin_id'] !== 'date') {
          continue;
        }

        if ($filter['operator'] === 'starts_on_after' || $filter['operator'] === 'ends_on_after') {
          $filter['operator'] = 'daterange_starts_or_ends';
          $update = TRUE;
        }
      }
    }

    unset($display);
    if ($update) {
      $view->set('display', $displays);
    }

    return $update;
  });

}

/**
 * No-op update. Replaced by openculturas_post_update_issue_3446003_1.
 */
function openculturas_post_update_issue_3446003(array &$sandbox): void {

}

/**
 * Replace http_client_error_status provided condition with core provided condition.
 */
function openculturas_post_update_issue_3446003_1(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'block', static function (BlockInterface $block): bool {
    $visibility = $block->getVisibility();
    if (!isset($visibility['http_client_error_status'])) {
      return FALSE;
    }

    $codes = [];
    if ($visibility['http_client_error_status']['request_403']) {
      $codes[] = 403;
    }

    if ($visibility['http_client_error_status']['request_404']) {
      $codes[] = 404;
    }

    $block->setVisibilityConfig('http_client_error_status', []);
    $block->setVisibilityConfig('response_status', ['status_codes' => $codes]);
    return TRUE;
  });
}

/**
 * Change pager type of view related_article and display related_article_term to views_infinite_scroll.
 */
function openculturas_post_update_related_article_term_pager_views_infinite_scroll(): void {
  $view = Views::getView('related_article');
  if ($view) {
    $display = $view->setDisplay('related_article_term');
    if ($display && !$view->getDisplay()->isDefaulted('pager')) {
      $pager = $view->getDisplay()->getOption('pager');
      $pager['type'] = 'infinite_scroll';
      $pager['options']['views_infinite_scroll'] = [
        'button_text' => 'Show more',
        'automatically_load_content' => FALSE,
        'initially_load_all_pages' => FALSE,
      ];
      unset($pager['options']['quantity'], $pager['options']['tags']['first'], $pager['options']['tags']['last']);
      $view->getDisplay()->setOption('pager', $pager);
      $view->save();
    }
  }
}

/**
 * Disable display feed_1 in view oc_frontpage.
 */
function openculturas_post_update_oc_frontpage_disable_feed(): void {
  $view = Views::getView('oc_frontpage');
  if ($view) {
    $display = $view->setDisplay('feed_1');
    if ($display) {
      $view->getDisplay()->setOption('enabled', FALSE);
      $view->save();
    }
  }
}

/**
 * Replace div with p-element in display upcoming_dates_map of view related_date.
 */
function openculturas_post_update_upcoming_dates_map_local_safe(): void {
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('views.view.related_date');
  if (!$config->isNew() && $config->get('display.upcoming_dates_map.display_options.header.result.content')) {
    $config->set('display.upcoming_dates_map.display_options.header.result.content', '<p class="result-counter">Displaying <strong>@start - @end</strong> of <strong>@total</strong></p>');
    $config->save();
  }
}

/**
 * Move language switcher into default, media_library form mode.
 */
function openculturas_post_update_media_bundles_language_switcher(): void {
  /** @var \Drupal\content_translation\ContentTranslationManagerInterface $contentTranslationManager */
  $contentTranslationManager = \Drupal::service('content_translation.manager');
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository */
  $entityDisplayRepository = \Drupal::service('entity_display.repository');
  $media_types = ['audio', 'document', 'image', 'logo_image', 'remote_video', 'user_profile_picture', 'sponsor'];
  foreach ($media_types as $media_type) {
    if ($contentTranslationManager->isEnabled('media', $media_type)) {
      $formDisplay = $entityDisplayRepository->getFormDisplay('media', $media_type);
      $field_group_id = 'group_administrative';
      /** @var array|null $current_field_group */
      $current_field_group = $formDisplay->getThirdPartySetting('field_group', $field_group_id);

      if (is_array($current_field_group) && isset($current_field_group['region']) && $current_field_group['region'] === 'hidden') {
        if (isset($current_field_group['children']) && is_array($current_field_group['children'])) {
          foreach ($current_field_group['children'] as $child_id) {
            if (!is_string($child_id)) {
              continue;
            }

            $formDisplay->removeComponent($child_id);
          }
        }

        $formDisplay->unsetThirdPartySetting('field_group', $field_group_id);
      }

      $currentSettings = $formDisplay->getComponent('langcode');
      if (!is_array($currentSettings)) {
        $formDisplay->setComponent('langcode');
      }

      $formDisplay->removeComponent('translation');
      $formDisplay->save();
      $formDisplay = $entityDisplayRepository->getFormDisplay('media', $media_type, 'media_library');
      if (!$formDisplay->isNew()) {
        $formDisplay->removeComponent('translation');
        $weight = $formDisplay->getHighestWeight();
        $currentSettings = $formDisplay->getComponent('name');
        if (is_array($currentSettings)) {
          $weight = $currentSettings['weight'] ?? $weight;
        }
        else {
          $formDisplay->setComponent('name', ['weight' => $weight]);
        }

        $formDisplay->setComponent('langcode', ['weight' => ++$weight]);
        $formDisplay->save();
      }
    }
    else {
      // Translations not enabled. Just in case lets remove the components.
      $form_modes = ['default', 'media_library'];
      foreach ($form_modes as $form_mode) {
        $formDisplay = $entityDisplayRepository->getFormDisplay('media', $media_type, $form_mode);
        if (!$formDisplay->isNew()) {
          $formDisplay->removeComponent('translation');
          $formDisplay->removeComponent('langcode');
          $formDisplay->save();
        }
      }
    }
  }
}

/**
 * Disable 'Hide non translatable fields on translation forms'.
 */
function openculturas_post_update_paragraph_member_non_translatable_fields(): void {
  /** @var \Drupal\content_translation\ContentTranslationManagerInterface $contentTranslationManager */
  $contentTranslationManager = \Drupal::service('content_translation.manager');
  if ($contentTranslationManager instanceof BundleTranslationSettingsInterface && $contentTranslationManager->isEnabled('paragraph', 'member')) {
    $settings = $contentTranslationManager->getBundleTranslationSettings('paragraph', 'member');
    $settings['untranslatable_fields_hide'] = '0';
    $contentTranslationManager->setBundleTranslationSettings('paragraph', 'member', $settings);
  }
}

/**
 * Setup 'Search API Exclude Entity' module.
 */
function openculturas_post_update_setup_search_api_exclude_entity(): string {
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
  $entityFieldManager = \Drupal::service('entity_field.manager');
  $field_map = $entityFieldManager->getFieldMapByFieldType('search_api_exclude_entity');
  if ($field_map !== []) {
    return 'Skip setup. Already done?';
  }

  $full_config_names = [
    'field.storage.node.field_search_api_exclude',
    'field.field.node.page.field_search_api_exclude',
  ];
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  foreach ($full_config_names as $full_config_name) {
    $config_name = ConfigName::createByFullName($full_config_name);

    if ($configUpdater->import($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository */
  $entityDisplayRepository = \Drupal::service('entity_display.repository');
  $viewDisplay = $entityDisplayRepository->getViewDisplay('node', 'page');
  $viewDisplay->removeComponent('field_search_api_exclude');
  $viewDisplay->save();

  $formDisplay = $entityDisplayRepository->getFormDisplay('node', 'page');
  $settingsFieldMetaTags = $formDisplay->getComponent('field_meta_tags');
  $settingsFieldSearchApiExclude = $formDisplay->getComponent('field_search_api_exclude');
  $weight = $settingsFieldMetaTags['weight'] ?? $formDisplay->getHighestWeight();
  $settingsFieldSearchApiExclude['weight'] = ++$weight;
  $settingsFieldSearchApiExclude['settings']['field_label'] = 'Yes, exclude this page from the search index.';
  $formDisplay->setComponent('field_search_api_exclude', $settingsFieldSearchApiExclude);
  $formDisplay->save();

  /** @var \Drupal\search_api\Processor\ProcessorPluginManager $processManager */
  $processManager = \Drupal::service('plugin.manager.search_api.processor');
  /** @var \Drupal\search_api_exclude_entity\Plugin\search_api\processor\SearchApiExcludeEntityProcessor $instance */
  $instance = $processManager->createInstance('search_api_exclude_entity_processor', ['fields' => ['node' => ['field_search_api_exclude']]]);

  /** @var \Drupal\search_api\IndexInterface|null $index */
  $index = Index::load('content');
  if ($index) {
    $index->addProcessor($instance);
    $index->save();
  }

  return $logger->output();

}

/**
 * Revert view 'related_sponsor' and add swiffy_slider.settings configuration.
 */
function openculturas_post_update_related_sponsor_more_displays(): string {
  $full_config_names = [
    'views.view.related_sponsor',
  ];
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  foreach ($full_config_names as $full_config_name) {
    $config_name = ConfigName::createByFullName($full_config_name);
    if ($configUpdater->revert($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully reverted.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  $full_config_names = [
    'swiffy_slider.settings',
  ];
  foreach ($full_config_names as $full_config_name) {
    $config_name = ConfigName::createByFullName($full_config_name);
    if ($configUpdater->import($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  return $logger->output();
}

/**
 * Setup 'Simple image rotation' module.
 */
function openculturas_post_update_setup_simple_image_rotate(): void {
  $media_bundles = ['image', 'logo_image', 'user_profile_picture'];
  foreach ($media_bundles as $media_bundle) {
    /** @var \Drupal\Core\Field\FieldConfigInterface|null $field */
    $field = FieldConfig::loadByName('media', $media_bundle, 'field_media_image');
    if ($field instanceof FieldConfigInterface) {
      $field->setThirdPartySetting('simple_image_rotate', 'enable_rotate', TRUE);
      $field->save();
    }
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load(RoleInterface::AUTHENTICATED_ID);
  if ($role instanceof RoleInterface) {
    $role->grantPermission('rotate images');
    $role->save();
  }
}
