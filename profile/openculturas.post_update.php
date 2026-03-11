<?php

/**
 * @file
 * Install, update and uninstall module functions.
 */

declare(strict_types=1);

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\update_helper\ConfigName;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\views\Views;

function _openculturas_post_update_import_or_revert_config(array $full_config_names, bool $revert = FALSE): string {
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  $configFactory = \Drupal::configFactory();
  foreach ($full_config_names as $full_config_name) {
    if (!$revert && !$configFactory->get($full_config_name)->isNew()) {
      $logger->warning(sprintf('Unable to import %s config, because configuration file exits already.', $full_config_name));
      // Do not try to import config that exits.
      continue;
    }

    $config_name = ConfigName::createByFullName($full_config_name);
    if (!$revert && $configUpdater->import($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
    elseif ($revert && $configUpdater->revert($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully reverted.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  return $logger->output();
}

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
    'openculturas_post_update_issue_3446002' => '2.5.0',
    'openculturas_post_update_issue_3446002_1' => '2.5.0',
    'openculturas_post_update_issue_3446003' => '2.5.0',
    'openculturas_post_update_issue_3446003_1' => '2.5.0',
    'openculturas_post_update_related_article_term_pager_views_infinite_scroll' => '2.5.0',
    'openculturas_post_update_oc_frontpage_disable_feed' => '2.5.0',
    'openculturas_post_update_upcoming_dates_map_local_safe' => '2.5.0',
    'openculturas_post_update_media_bundles_language_switcher' => '2.5.0',
    'openculturas_post_update_paragraph_member_non_translatable_fields' => '2.5.0',
    'openculturas_post_update_setup_search_api_exclude_entity' => '2.5.0',
    'openculturas_post_update_related_sponsor_more_displays' => '2.5.0',
    'openculturas_post_update_setup_simple_image_rotate' => '2.5.0',
    'openculturas_post_update_swiffyslider_autohide' => '2.5.0',
    'openculturas_post_update_buttons_in_user_dashboard_permanently' => '2.5.0',
    'openculturas_post_update_setup_past_dates_archive' => '2.5.0',
    'openculturas_post_update_remove_term_validation_in_views_displays' => '2.5.0',
    'openculturas_post_update_setup_office_hours' => '2.5.0',
    'openculturas_post_update_pager_id_my_content_block' => '2.5.0',
    'openculturas_post_update_search_input_label' => '2.5.0',
    'openculturas_post_update_user_admin_people_add_realname' => '2.5.0',
    'openculturas_post_update_content_moderation_revision_uid_relationship' => '2.5.0',
    'openculturas_post_update_add_field_alternative_title' => '2.5.0',
    'openculturas_post_update_setup_paragraphs_type_a11y_wheelchair' => '2.5.0',
    'openculturas_post_update_eca_upgrade_2' => '2.5.0',
    'openculturas_post_update_revert_gin_theme_overrides_1' => '2.5.0',
    'openculturas_post_update_import_block_utility_menu_account' => '2.5.0',
    'openculturas_post_update_import_block_language_toggle' => '2.5.0',
  ];
}

/**
 * Imports the new paragraph type text_slider.
 */
function openculturas_post_update_import_text_slider(): string {
  $full_config_names = [
    'core.entity_view_mode.paragraph.slider_multiple',
    'paragraphs.paragraphs_type.text_slider',
    'field.storage.paragraph.field_slider_card',
    'core.base_field_override.paragraph.text_slider.behavior_settings',
    'core.base_field_override.paragraph.text_slider.created',
    'core.entity_form_display.paragraph.text_slider.default',
    'core.entity_view_display.paragraph.text_slider.default',
    'core.entity_view_display.paragraph.text_slider.slider_multiple',
    'field.field.paragraph.text_slider.field_slider_card',
    'field.field.paragraph.text_slider.paragraph_view_mode',
    'language.content_settings.paragraph.text_slider',
  ];

  $output = _openculturas_post_update_import_or_revert_config($full_config_names);
  $bundles = ['article', 'page'];
  foreach ($bundles as $bundle) {
    /** @var \Drupal\Core\Field\FieldConfigInterface|null $field */
    $field = FieldConfig::loadByName('node', $bundle, 'field_content_paragraphs');
    if ($field instanceof FieldConfigInterface) {
      $handler_settings = is_array($field->getSetting('handler_settings')) ? $field->getSetting('handler_settings') : [];
      if ($handler_settings !== [] && array_key_exists('target_bundles_drag_drop', $handler_settings)) {
        $weight = count($handler_settings['target_bundles_drag_drop']) + 1;
        $handler_settings['target_bundles_drag_drop']['text_slider'] = ['enabled' => TRUE, 'weight' => $weight];
        $handler_settings['target_bundles']['text_slider'] = 'text_slider';
        $field->setSetting('handler_settings', $handler_settings);
        $field->save();
      }
    }
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load(RoleInterface::ANONYMOUS_ID);
  if ($role instanceof RoleInterface) {
    $role->grantPermission('view paragraph content text_slider');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load(RoleInterface::AUTHENTICATED_ID);
  if ($role instanceof RoleInterface) {
    $role->grantPermission('view paragraph content text_slider');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = Role::load('magazine_editor');
  if ($role instanceof RoleInterface) {
    $role->grantPermission('create paragraph content text_slider');
    $role->grantPermission('update paragraph content text_slider');
    $role->grantPermission('delete paragraph content text_slider');
    $role->save();
  }

  return $output;
}

/**
 * Adds missing swiffy slider configuration for paragraph type text_slider.
 */
function openculturas_post_update_text_slider_setup_swiffy_slider(): string {
  $full_config_names = [
    'core.entity_form_display.paragraph.text_slider.default',
    'core.entity_view_display.paragraph.text_slider.default',
    'core.entity_view_display.paragraph.text_slider.slider_multiple',
  ];

  return _openculturas_post_update_import_or_revert_config($full_config_names, TRUE);
}

/**
 * Revert gin theme overrides to remove CSS for .paragraphs-tabs-wrapper.
 */
function openculturas_post_update_revert_gin_theme_overrides_2(): string {
  $full_config_names = [
    'asset_injector.css.oc_gin_theme_overrides',
  ];
  return _openculturas_post_update_import_or_revert_config($full_config_names, TRUE);
}

/**
 * Updates the field_mood_image view mode to teaser_image_big in node view mode teaser_unified, teaser_big.
 */
function openculturas_post_update_teaser_unified_teaser_image_big(): string {
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
  $entity_display_repository = \Drupal::service('entity_display.repository');
  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
  $bundle_info = \Drupal::service('entity_type.bundle.info');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $entity_types = [
    'taxonomy_term' => array_keys($bundle_info->getBundleInfo('taxonomy_term')),
    'node' => array_keys($bundle_info->getBundleInfo('node')),
  ];

  foreach ($entity_types as $entity_type => $bundles) {
    foreach ($bundles as $bundle) {
      foreach (['teaser_unified', 'teaser_big'] as $view_mode) {
        $view_display = $entity_display_repository->getViewDisplay($entity_type, $bundle, $view_mode);
        if ($view_display->isNew()) {
          continue;
        }

        $component = $view_display->getComponent('field_mood_image');

        if ($component && isset($component['settings']['view_mode']) && $component['settings']['view_mode'] !== 'teaser_image_big') {
          $component['settings']['view_mode'] = 'teaser_image_big';
          $view_display->setComponent('field_mood_image', $component);
          $view_display->save();
          $logger->info(sprintf('Updated field_mood_image view_mode to teaser_image_big in %s.%s.%s.', $entity_type, $bundle, $view_mode));
        }
      }
    }
  }

  return $logger->output();
}

/**
 * Add missing view mode (compact) for the content type 'page'.
 */
function openculturas_post_update_node_page_view_mode_compact(): string {
  $full_config_names = [
    'core.entity_view_display.node.page.compact',
  ];

  return _openculturas_post_update_import_or_revert_config($full_config_names);
}

/**
 * Location output in view "related_date", "related_dates_archive" in display "related_date_location".
 */
function openculturas_post_update_related_date_location_output(): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  $view_ids = ['related_date', 'related_dates_archive'];
  $display_id = 'related_date_location';
  foreach ($view_ids as $view_id) {
    $view = Views::getView($view_id);
    if ($view && $view->setDisplay($display_id)) {
      $display = $view->getDisplay();
      /** @var \Drupal\views\Plugin\views\field\FieldHandlerInterface|null $handler */
      $handler = $display->getHandler('field', 'nothing');
      if ($handler) {
        $fields = $display->getOption('fields');
        $field_nothing = &$fields['nothing'];
        $field_nothing['alter']['text'] = "{% if field_attendance_mode == 'MixedEventAttendanceMode' %}\r\n<div class=\"location\">\r\n{{ field_attendance_mode_1 }}-{{'Event' | t }}\r\n</div>\r\n{% endif %}";
        $display->setOption('fields', $fields);
        $view->save();
      }
      else {
        $logger->notice(sprintf('SKIPPED. Field nothing not found in view %s and display %s.', $view_id, $display_id));
      }
    }
    else {
      $logger->notice(sprintf('SKIPPED. View (%s) or display (%s) not found.', $view_id, $display_id));
    }
  }

  return $logger->output();
}

/**
 * Add missing paragraph icons.
 */
function openculturas_post_update_paragraph_missing_icons(): string {
  $full_config_names = [
    'paragraphs.paragraphs_type.a11y_wheelchair',
    'paragraphs.paragraphs_type.accessibility',
    'paragraphs.paragraphs_type.address_data',
    'paragraphs.paragraphs_type.block',
    'paragraphs.paragraphs_type.bookable_event',
    'paragraphs.paragraphs_type.contact_data',
    'paragraphs.paragraphs_type.download',
    'paragraphs.paragraphs_type.gallery',
    'paragraphs.paragraphs_type.media',
    'paragraphs.paragraphs_type.media_mention',
    'paragraphs.paragraphs_type.member',
    'paragraphs.paragraphs_type.teaser_external',
    'paragraphs.paragraphs_type.teaser_node',
    'paragraphs.paragraphs_type.teaser_term',
    'paragraphs.paragraphs_type.teaser_wrapper',
    'paragraphs.paragraphs_type.text',
    'paragraphs.paragraphs_type.text_slider',
    'paragraphs.paragraphs_type.view',
  ];

  return _openculturas_post_update_import_or_revert_config($full_config_names, TRUE);
}

/**
 * Fix used view mode in 'field_mood_image' in node.page.teaser.
 */
function openculturas_post_update_content_type_page_field_mood_image_view_mode(): string {
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
  $entity_display_repository = \Drupal::service('entity_display.repository');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $view_display = $entity_display_repository->getViewDisplay('node', 'page', 'teaser');
  if (!$view_display->isNew()) {
    $component = $view_display->getComponent('field_mood_image');

    if ($component && isset($component['settings']['view_mode'])) {
      if ($component['settings']['view_mode'] === 'teaser_image_big') {
        $component['settings']['view_mode'] = 'teaser_image';
        $view_display->setComponent('field_mood_image', $component);
        $view_display->save();
        $logger->info(sprintf('Updated field_mood_image view_mode to teaser_image in %s.%s.%s.', 'node', 'page', 'teaser'));
      }
      else {
        $logger->notice('SKIPPED. Component field_mood_image in node.page.teaser does not need a update.');
      }
    }
    else {
      $logger->notice('SKIPPED. Component field_mood_image in node.page.teaser not found.');
    }
  }
  else {
    $logger->notice('SKIPPED. View display node.page.teaser not found.');
  }

  return $logger->output();
}
