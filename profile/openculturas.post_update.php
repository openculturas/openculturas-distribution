<?php

/**
 * @file
 * Install, update and uninstall module functions.
 */

declare(strict_types=1);

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\update_helper\ConfigName;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Implements hook_removed_post_updates().
 */
function openculturas_removed_post_updates(): array {
  return [
    'openculturas_post_update_0001' => '2.0.0',
    'openculturas_post_update_0002' => '2.0.0',
    'openculturas_post_update_0003' => '2.0.0',
    'openculturas_post_update_0004' => '2.0.0',
    'openculturas_post_update_0005' => '2.0.0',
    'openculturas_post_update_0006' => '2.0.0',
    'openculturas_post_update_0007' => '2.0.0',
    'openculturas_post_update_0008' => '2.0.0',
    'openculturas_post_update_0009' => '2.0.0',
    'openculturas_post_update_0010' => '2.0.0',
    'openculturas_post_update_0011' => '2.0.0',
    'openculturas_post_update_0012' => '2.0.0',
    'openculturas_post_update_0013' => '2.0.0',
    'openculturas_post_update_0014' => '2.0.0',
    'openculturas_post_update_0015' => '2.0.0',
    'openculturas_post_update_0016' => '2.0.0',
    'openculturas_post_update_0017' => '2.0.0',
    'openculturas_post_update_0018' => '2.0.0',
    'openculturas_post_update_0019' => '2.0.0',
    'openculturas_post_update_0020' => '2.0.0',
    'openculturas_post_update_0021' => '2.0.0',
    'openculturas_post_update_0022' => '2.0.0',
    'openculturas_post_update_0023' => '2.0.0',
    'openculturas_post_update_0024' => '2.0.0',
    'openculturas_post_update_0025' => '2.0.0',
    'openculturas_post_update_0026' => '2.0.0',
    'openculturas_post_update_0027' => '2.0.0',
    'openculturas_post_update_0028' => '2.0.0',
    'openculturas_post_update_0029' => '2.0.0',
    'openculturas_post_update_0030' => '2.0.0',
    'openculturas_post_update_0031' => '2.0.0',
    'openculturas_post_update_0032' => '2.0.0',
    'openculturas_post_update_0033' => '2.0.0',
    'openculturas_post_update_0034' => '2.0.0',
    'openculturas_post_update_0035' => '2.0.0',
    'openculturas_post_update_0036' => '2.0.0',
    'openculturas_post_update_0037' => '2.0.0',
    'openculturas_post_update_0038' => '2.0.0',
    'openculturas_post_update_0039' => '2.0.0',
    'openculturas_post_update_0040' => '2.0.0',
    'openculturas_post_update_0041' => '2.0.0',
    'openculturas_post_update_0042' => '2.0.0',
    'openculturas_post_update_0043' => '2.0.0',
    'openculturas_post_update_0044' => '2.0.0',
    'openculturas_post_update_oc_gin_theme_overrides_paragraphs_behaviors' => '2.0.0',
    'openculturas_post_update_views_refactor_0001' => '2.0.0',
    'openculturas_post_update_views_refactor_0002' => '2.0.0',
    'openculturas_post_update_views_refactor_0003' => '2.0.0',
    'openculturas_post_update_views_refactor_0004' => '2.0.0',
    'openculturas_post_update_views_refactor_0005' => '2.0.0',
    'openculturas_post_update_views_refactor_0006' => '2.0.0',
    'openculturas_post_update_views_refactor_0007' => '2.0.0',
    'openculturas_post_update_views_refactor_0008' => '2.0.0',
    'openculturas_post_update_views_refactor_0009' => '2.0.0',
    'openculturas_post_update_views_refactor_0010' => '2.0.0',
    'openculturas_post_update_views_refactor_0011' => '2.0.0',
  ];
}

/**
 * Configuration update.
 */
function openculturas_post_update_0045(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_0045');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

function _openculturas_post_update_interaction_button_section_remove_bookmark(string $component, EntityViewDisplayInterface $entityViewDisplay): void {
  $entityViewDisplay->removeComponent($component);
  $group_interact = $entityViewDisplay->getThirdPartySetting('field_group', 'group_interact');
  if ($group_interact) {
    foreach ($group_interact['children'] as $key => $child) {
      if ($child === $component) {
        unset($group_interact['children'][$key]);
        $group_interact['children'] = array_values($group_interact['children']);
      }
    }

    $entityViewDisplay->setThirdPartySetting('field_group', 'group_interact', $group_interact);
  }
}

/**
 * Disable bookmark as field and enable it as block, re-order of interact field_group fields.
 */
function openculturas_post_update_interaction_button_section(): string {

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');

  $bundle_ids = ['article', 'date', 'event', 'location', 'page', 'profile', 'faq'];
  foreach ($bundle_ids as $bundle_id) {
    $display = $entity_display->getViewDisplay('node', $bundle_id, 'full');
    if (!$display->isNew()) {
      _openculturas_post_update_interaction_button_section_remove_bookmark('flag_bookmark_node', $display);
      $display->save();
    }
  }

  $bundle_ids = ['article_type', 'category', 'column', 'event_type', 'location_type', 'page_type', 'faq_category'];
  foreach ($bundle_ids as $bundle_id) {
    $display = $entity_display->getViewDisplay('taxonomy_term', $bundle_id, 'full');
    if (!$display->isNew()) {
      _openculturas_post_update_interaction_button_section_remove_bookmark('flag_bookmark_term', $display);
      $display->save();
    }
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_interaction_button_section');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Setup password policies and enable strength indicator.
 */
function openculturas_post_update_password_policy(): string {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->install([
    'password_policy',
    'password_policy_length',
    'password_policy_character_types',
    'password_policy_blacklist',
  ]);

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');

  $displays_ids = ['default', 'register'];
  foreach ($displays_ids as $displays_id) {
    $display = $entity_display->getFormDisplay('user', 'user', $displays_id);
    if (!$display->isNew()) {
      $display->removeComponent('field_last_password_reset');
      $display->removeComponent('field_password_expiration');
      $display->removeComponent('field_pending_expire_sent');
      $display->save();
    }
  }

  $displays_ids = ['default', 'compact', 'full'];
  foreach ($displays_ids as $display_id) {
    $display = $entity_display->getViewDisplay('user', 'user', $display_id);
    if (!$display->isNew()) {
      $display->removeComponent('field_last_password_reset');
      $display->removeComponent('field_password_expiration');
      $display->removeComponent('field_pending_expire_sent');
      $display->save();
    }
  }

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_password_policy');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Removes invalid block categories and adds Content block category.
 */
function openculturas_post_update_field_block_ref_cleanup(): void {
  /** @var \Drupal\Core\Field\FieldConfigInterface|null $field */
  $field = FieldConfig::loadByName('paragraph', 'block', 'field_block_ref');
  if ($field instanceof FieldConfigInterface) {
    $selection = $field->getSetting('selection');
    if ($selection !== 'categories') {
      return;
    }

    $selection_settings = $field->getSetting('selection_settings');
    $categories_configured = $selection_settings['categories'] ?? [];
    if ($categories_configured !== []) {
      /** @var \Drupal\block_field\BlockFieldManagerInterface $block_field_manager */
      $block_field_manager = \Drupal::service('block_field.manager');
      $all_categories = array_values($block_field_manager->getBlockCategories());
      $all_categories = array_map(static fn(string|TranslatableMarkup $category): string => (string) $category, $all_categories);

      foreach ($categories_configured as $category_configured => $status) {
        if (!in_array($category_configured, $all_categories, TRUE)) {
          unset($categories_configured[$category_configured]);
        }
      }

      // Content block is enabled.
      if (in_array('Content block', $all_categories, TRUE)) {
        $categories_configured['Content block'] = 'Content block';
      }

      $selection_settings['categories'] = $categories_configured;
      $field->setSetting('selection_settings', $selection_settings);
      $field->save();
    }
  }
}

/**
 * Updates all field storage config from type viewfield due missing handler setting.
 */
function openculturas_post_update_viewfield_missing_handler(?array &$sandbox = NULL): void {
  $configEntityUpdater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $callback = static fn(FieldStorageConfigInterface $fieldStorageConfig): bool => $fieldStorageConfig->getType() === 'viewfield';
  $configEntityUpdater->update($sandbox, 'field_storage_config', $callback);
}

/**
 * Grant - access tour - permission to authenticated user role.
 */
function openculturas_post_update_tour_access(): void {
  if (\Drupal::moduleHandler()->moduleExists('tour')) {
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    if ($role instanceof RoleInterface) {
      $role->grantPermission('access tour');
      $role->save();
    }
  }
}

/**
 * Install admin_toolbar_links_access_filter.
 */
function openculturas_post_update_install_admin_toolbar_links_access_filter(): void {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->install(['admin_toolbar_links_access_filter']);
}

/**
 * Replace formtips selector for people_reference field.
 */
function openculturas_post_update_formtips_replace_people_reference_selector(): void {
  $configFactory = \Drupal::configFactory();
  $config = $configFactory->getEditable('formtips.settings');
  if (!$config->isNew()) {
    $formtips_selectors = $config->get('formtips_selectors');
    $config->set('formtips_selectors', str_replace("[id^='edit-field-people-reference-0-subform-field-member-0-target-id']", "[id^='edit-field-people-reference-']", (string) $formtips_selectors));
    $config->save();
  }
}

/**
 * Add filter_autop filter to minimal_html filter_format.
 */
function openculturas_post_update_add_filter_autop_to_minimal_html(): void {
  $configEntityStorage = \Drupal::entityTypeManager()->getStorage('filter_format');
  /** @var \Drupal\filter\FilterFormatInterface|null $filterFormat */
  $filterFormat = $configEntityStorage->load('minimal_html');
  if ($filterFormat instanceof FilterFormatInterface) {
    /** @var \Drupal\filter\Plugin\FilterInterface $filter */
    $filter = $filterFormat->filters('filter_autop');
    $configuration = $filter->getConfiguration();
    $configuration['status'] = TRUE;
    $filterFormat->setFilterConfig($filter->getPluginId(), $configuration);
    $filterFormat->save();
  }
}

/**
 * Grant - smart_date_recur - permissions to authenticated user role.
 */
function openculturas_post_update_smart_date_recur_access(): void {
  if (\Drupal::moduleHandler()->moduleExists('smart_date_recur')) {
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    if ($role instanceof RoleInterface) {
      $role->grantPermission('cancel smart date recur instances');
      $role->grantPermission('make smart dates recur');
      $role->grantPermission('reschedule smart date recur instances');
      $role->save();
    }
  }
}

/**
 * Migrate to ckEditor5.
 */
function openculturas_post_update_ckeditor5_migration(): string {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->install(['ckeditor5']);

  $role = Role::load(RoleInterface::ANONYMOUS_ID);
  if ($role instanceof RoleInterface) {
    $role->revokePermission('use text format restricted_html');
    $role->save();
  }

  $role = Role::load(RoleInterface::AUTHENTICATED_ID);
  if ($role instanceof RoleInterface) {
    $role->revokePermission('use text format restricted_html');
    $role->save();
  }

  $full_config_names = [
    'editor.editor.advanced_html',
    'editor.editor.basic_html',
    'editor.editor.full_html',
    'filter.format.advanced_html',
    'filter.format.basic_html',
    'filter.format.full_html',
    'filter.format.restricted_html',
  ];
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  foreach ($full_config_names as $full_config_name) {
    $config_name = ConfigName::createByFullName($full_config_name);

    if ($configUpdater->revert($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  return $logger->output();
}

/**
 * More compact address map.
 */
function openculturas_post_update_compact_address_map(): string {
  $full_config_name = 'core.entity_view_display.paragraph.address_data.default';
  $configFactory = \Drupal::configFactory();
  $config = $configFactory->get($full_config_name);
  if ($config->isNew()) {
    return 'Skipped';
  }

  $icon_url = $config->get('content.field_address_location.settings.icon.iconUrl');

  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  $config_name = ConfigName::createByFullName($full_config_name);

  if ($configUpdater->revert($config_name->getType(), $config_name->getName())) {
    $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
  }
  else {
    $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
  }

  $configFactory
    ->getEditable($full_config_name)
    ->set('content.field_address_location.settings.icon.iconUrl', $icon_url)
    ->save();

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');

  $display = $entity_display->getViewDisplay('node', 'date', 'full');
  if (!$display->isNew()) {
    $field_location = $display->getComponent('field_location');
    if (is_array($field_location)) {
      $field_location['type'] = 'entity_reference_entity_view';
      $field_location['label'] = 'hidden';
      $field_location['settings'] = ['view_mode' => 'compact', 'link' => FALSE];
      $display->setComponent('field_location', $field_location);
      $extra_field_location_address_data = $display->getComponent('extra_field_location_address_data');
      if (is_array($extra_field_location_address_data)) {
        $extra_field_location_address_data['weight'] = (int) $field_location['weight'] + 1;
        $display->setComponent('extra_field_location_address_data', $extra_field_location_address_data);
        $field_safety_info = $display->getComponent('field_safety_info');
        if (is_array($field_safety_info)) {
          $field_safety_info['weight'] = (int) $field_location['weight'] + 2;
          $display->setComponent('field_safety_info', $field_safety_info);
        }
      }

      $display->save();
    }
  }

  return $logger->output();
}

/**
 * Allows to use field_supporters for users.
 */
function openculturas_post_update_enable_field_supporters_for_all(array &$sandbox): void {
  /** @var \Drupal\user\RoleStorageInterface $roleStorage */
  $roleStorage = \Drupal::entityTypeManager()->getStorage('user_role');
  /** @var \Drupal\user\RoleInterface[] $roles */
  $roles = $roleStorage->loadMultiple();
  foreach ($roles as $role) {
    $role->revokePermission('create field_supporters');
    $role->revokePermission('edit field_supporters');
    $role->revokePermission('edit own field_supporters');
    $role->revokePermission('view field_supporters');
    $role->revokePermission('view own field_supporters');
    if ($role->id() === RoleInterface::AUTHENTICATED_ID) {
      $role->grantPermission('create sponsor media');
      $role->grantPermission('edit own sponsor media');
    }

    $role->save();
  }

  /** @var \Drupal\Core\Config\Entity\ConfigEntityUpdater $configEntityUpdater */
  $configEntityUpdater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $callback = static function (FieldStorageConfigInterface $fieldStorageConfig): bool {
    if ($fieldStorageConfig->getName() === 'field_supporters') {
      $fieldStorageConfig->setThirdPartySetting('field_permissions', 'permission_type', 'public');
      return TRUE;
    }

    return FALSE;
  };
  $configEntityUpdater->update($sandbox, 'field_storage_config', $callback);
}

/**
 * Add new field groups to content type page.
 */
function openculturas_post_update_add_field_groups_to_page(): void {
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');

  $entityFormDisplay = $entity_display->getFormDisplay('node', 'page');
  if (!$entityFormDisplay->isNew()) {
    $settings = [
      'children' => ['group_basics'],
      'label' => 'Tabs',
      'region' => 'content',
      'parent_name' => '',
      'weight' => 0,
      'format_type' => 'tabs',
      'format_settings' => [
        'classes' => '',
        'show_empty_fields' => FALSE,
        'id' => '',
        'direction' => 'horizontal',
        'width_breakpoint' => 640,
      ],
    ];

    $entityFormDisplay->setThirdPartySetting('field_group', 'group_tabs', $settings);
    $settings = [
      'children' => [
        'title',
        'field_subtitle',
        'field_mood_image',
        'body',
        'field_content_paragraphs',
        'field_supporters',
        'field_layout_switcher',
        'status',
      ],
      'label' => 'Basics',
      'region' => 'content',
      'parent_name' => 'group_tabs',
      'weight' => 0,
      'format_type' => 'tab',
      'format_settings' => [
        'classes' => '',
        'show_empty_fields' => FALSE,
        'id' => 'group-basics',
        'formatter' => 'open',
        'description' => '',
        'required_fields' => TRUE,
      ],
    ];

    $entityFormDisplay->setThirdPartySetting('field_group', 'group_basics', $settings);
    $entityFormDisplay->save();
  }
}

/**
 * Move the content moderation widget from sidebar to content area.
 */
function openculturas_post_update_moderation_widget_to_content_area(): void {

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');

  $bundle_ids = [
    'article' => 'group_magazine_tabs_container',
    'date' => 'group_date_tabs_container',
    'event' => 'group_event_tabs_container',
    'location' => 'group_location_tabs_container',
    'profile' => 'group_profile_tabs_container',
    'page' => 'group_tabs',
  ];
  foreach ($bundle_ids as $bundle_id => $group_id) {
    $display = $entity_display->getFormDisplay('node', $bundle_id);
    if (!$display->isNew() && $moderation_state_component = $display->getComponent('moderation_state')) {
      $group = $display->getThirdPartySetting('field_group', $group_id);
      if ($group && is_array($group['children'])) {
        $children = $group['children'];
        array_unshift($children, 'moderation_state');
        $group['children'] = array_values(array_unique($children));
        $display->setThirdPartySetting('field_group', $group_id, $group);
        $weight = 0;
        $moderation_state_component['weight'] = $weight;
        $display->setComponent('moderation_state', $moderation_state_component);
        foreach ($group['children'] as $child) {
          if ($child === 'moderation_state') {
            continue;
          }

          ++$weight;
          if ($display->getComponent($child)) {
            $component = $display->getComponent($child);
            $component['weight'] = $weight;
            $display->setComponent($child, $component);
          }
          elseif ($display->getThirdPartySetting('field_group', $child)) {
            $settings = $display->getThirdPartySetting('field_group', $child);
            $settings['weight'] = $weight;
            $display->setThirdPartySetting('field_group', $child, $settings);
          }
        }

        $display->save();
      }
    }
  }
}

/**
 * Replace field_category_target_id with term_node_tid_depth.
 */
function openculturas_post_update_related_content_via_term_node_tid_depth(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas', 'openculturas_post_update_related_content_via_term_node_tid_depth');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}
