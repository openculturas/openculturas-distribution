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

function _openculturas_post_update_import_or_revert_config(array $full_config_names, bool $revert = FALSE): string {
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  foreach ($full_config_names as $full_config_name) {
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
