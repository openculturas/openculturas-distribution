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
use Drupal\block\BlockInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\filter\FilterFormatInterface;
use Drupal\language\Config\LanguageConfigOverride;
use Drupal\language\ConfigurableLanguageManagerInterface;
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

function _openculturas_post_update_interaction_button_section_remove_bookmark(string $component, EntityViewDisplayInterface $display): void {
  $display->removeComponent($component);
  $group_interact = $display->getThirdPartySetting('field_group', 'group_interact');
  if ($group_interact) {
    foreach ($group_interact['children'] as $key => $child) {
      if ($child === $component) {
        unset($group_interact['children'][$key]);
        $group_interact['children'] = array_values($group_interact['children']);
      }
    }

    $display->setThirdPartySetting('field_group', 'group_interact', $group_interact);
  }
}

/**
 * Disable bookmark as field and enable it as block.
 *
 * And re-order of interact field_group fields.
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
  foreach ($displays_ids as $displays_id) {
    $display = $entity_display->getViewDisplay('user', 'user', $displays_id);
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
      /** @var string[]|TranslatableMarkup[]  $all_categories */
      $all_categories = array_values($block_field_manager->getBlockCategories());
      $all_categories = array_map(static fn(string|TranslatableMarkup $category): string => is_string($category) ? $category : (string) $category, $all_categories);

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
 * Updates all field storage config from type viewfield.
 *
 * (due missing handler setting)
 */
function openculturas_post_update_viewfield_missing_handler(?array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $callback = static fn(FieldStorageConfigInterface $fieldStorageConfig): bool => $fieldStorageConfig->getType() === 'viewfield';
  $config_entity_updater->update($sandbox, 'field_storage_config', $callback);
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
 * No-op update.
 */
function openculturas_post_update_install_admin_toolbar_links_access_filter(): void {
  // Replaced by core. See admin_toolbar_update_8003.
}

/**
 * Replace formtips selector for people_reference field.
 */
function openculturas_post_update_formtips_replace_people_reference_selector(): void {
  $conficFactory = \Drupal::configFactory();
  $config = $conficFactory->getEditable('formtips.settings');
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
  $filterFormatStorage = \Drupal::entityTypeManager()->getStorage('filter_format');
  /** @var \Drupal\filter\FilterFormatInterface|null $filterFormat */
  $filterFormat = $filterFormatStorage->load('minimal_html');
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

/**
 * Enable missing media edit button.
 */
function openculturas_post_update_enable_media_edit(): void {
  if (\Drupal::moduleHandler()->moduleExists('media_library_edit') === FALSE) {
    return;
  }

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');

  $entityFormDisplay = $entity_display->getFormDisplay('node', 'location');
  if (!$entityFormDisplay->isNew() && $entityFormDisplay->getComponent('field_supporters')) {
    $component = $entityFormDisplay->getComponent('field_supporters');
    $component['third_party_settings']['media_library_edit']['show_edit'] = '1';
    $entityFormDisplay->setComponent('field_supporters', $component);
    $entityFormDisplay->save();
  }

  $entityFormDisplay = $entity_display->getFormDisplay('node', 'profile');
  if (!$entityFormDisplay->isNew() && $entityFormDisplay->getComponent('field_logo')) {
    $component = $entityFormDisplay->getComponent('field_logo');
    $component['third_party_settings']['media_library_edit']['show_edit'] = '1';
    $entityFormDisplay->setComponent('field_logo', $component);
    $entityFormDisplay->save();
  }

  $entityFormDisplay = $entity_display->getFormDisplay('node', 'event');

  foreach (['field_gallery', 'field_mood_image', 'field_supporters'] as $field_name) {
    if (!$entityFormDisplay->isNew() && $entityFormDisplay->getComponent($field_name)) {
      $component = $entityFormDisplay->getComponent($field_name);
      $component['third_party_settings']['media_library_edit']['show_edit'] = '1';
      $entityFormDisplay->setComponent($field_name, $component);
    }
  }

  $entityFormDisplay->save();

  $entityFormDisplay = $entity_display->getFormDisplay('paragraph', 'download');

  if (!$entityFormDisplay->isNew() && $entityFormDisplay->getComponent('field_media_multiple')) {
    $component = $entityFormDisplay->getComponent('field_media_multiple');
    $component['third_party_settings']['media_library_edit']['show_edit'] = '1';
    $entityFormDisplay->setComponent('field_media_multiple', $component);
    $entityFormDisplay->save();
  }

  $entityFormDisplay = $entity_display->getFormDisplay('paragraph', 'gallery');

  if (!$entityFormDisplay->isNew() && $entityFormDisplay->getComponent('field_gallery')) {
    $component = $entityFormDisplay->getComponent('field_gallery');
    $component['third_party_settings']['media_library_edit']['show_edit'] = '1';
    $entityFormDisplay->setComponent('field_gallery', $component);
    $entityFormDisplay->save();
  }

}

/**
 * Adds the missing permission 'download media'.
 */
function openculturas_post_update_missing_permission_media_entity_download(): void {
  if (\Drupal::moduleHandler()->moduleExists('media_entity_download') === FALSE) {
    return;
  }

  /** @var \Drupal\user\RoleStorageInterface $roleStorage */
  $roleStorage = \Drupal::entityTypeManager()->getStorage('user_role');
  /** @var \Drupal\user\RoleInterface|null $role */
  $role = $roleStorage->load(RoleInterface::ANONYMOUS_ID);
  if ($role instanceof RoleInterface) {
    $role->grantPermission('download media');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = $roleStorage->load(RoleInterface::AUTHENTICATED_ID);
  if ($role instanceof RoleInterface) {
    $role->grantPermission('download media');
    $role->save();
  }
}

/**
 * Adds a new field field_badges.
 */
function openculturas_post_update_add_field_badges(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  $successfully = $updater->executeUpdate('openculturas', 'openculturas_post_update_add_field_badges');
  if (!$successfully) {
    return $updater->logger()->output();
  }

  $languageManager = \Drupal::languageManager();
  if ($languageManager instanceof ConfigurableLanguageManagerInterface) {
    $configTranslation = $languageManager->getLanguageConfigOverride('de', 'field.storage.node.field_badges');
    if ($configTranslation instanceof LanguageConfigOverride) {
      $configTranslation->set('settings.allowed_values.0.label', 'Ausgezeichnet als Kulturguide');
      $configTranslation->save();
    }

    $configTranslation = $languageManager->getLanguageConfigOverride('de', 'views.view.related_profile');
    if ($configTranslation instanceof LanguageConfigOverride) {
      $configTranslation->set('display.related_profile_term_artist.display_title', 'Künstler:innen - nach Fokuskategorie');
      $configTranslation->set('display.related_profile_term_artist.display_options.title', 'Künstler:innen - nach Fokuskategorie');
      $configTranslation->set('display.related_profile_term_organizer.display_title', 'Veranstalter:innen - nach Fokuskategorie');
      $configTranslation->set('display.related_profile_term_organizer.display_options.title', 'Veranstalter:innen - nach Fokuskategorie');
      $configTranslation->save();
    }
  }

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');

  $field_name = 'field_badges';
  $entityFormDisplay = $entity_display->getFormDisplay('node', 'profile');
  if (!$entityFormDisplay->isNew()) {
    $group_id = 'group_administrative';
    $group = $entityFormDisplay->getThirdPartySetting('field_group', $group_id);
    if (isset($group['children']) && is_array($group['children'])) {
      $group['children'] = array_values(array_unique(array_merge(['created', 'uid', $field_name], $group['children'])));
      $entityFormDisplay->setThirdPartySetting('field_group', $group_id, $group);
      $weight = 0;
      if (!$entityFormDisplay->getComponent($field_name)) {
        $entityFormDisplay->setComponent($field_name, [
          'type' => 'options_buttons',
          'region' => 'content',
          'settings' => [],
          'third_party_settings' => [],
          'weight' => 0,
        ]);
      }

      foreach ($group['children'] as $child) {
        ++$weight;
        if ($component = $entityFormDisplay->getComponent($child)) {
          $component['weight'] = $weight;
          $entityFormDisplay->setComponent($child, $component);
        }
      }

      $entityFormDisplay->save();
    }
  }

  $entityViewDisplay = $entity_display->getViewDisplay('node', 'profile', 'full');

  $weight = $entityViewDisplay->getThirdPartySetting('field_group', 'group_workflow') ? $entityViewDisplay->getThirdPartySetting('field_group', 'group_workflow')['weight'] : $entityViewDisplay->getHighestWeight();
  if (!$entityViewDisplay->isNew()) {
    $component = [
      'label' => 'hidden',
      'weight' => $weight + 1,
      'type' => 'list_default',
      'region' => 'content',
      'settings' => [],
      'third_party_settings' => [
        'field_formatter_class' => [
          'class' => '',
        ],
      ],
    ];
    $entityViewDisplay->setComponent($field_name, $component);
    $entityViewDisplay->save();
  }

  /** @var \Drupal\user\RoleStorageInterface $roleStorage */
  $roleStorage = \Drupal::entityTypeManager()->getStorage('user_role');
  /** @var \Drupal\user\RoleInterface|null $role */
  $role = $roleStorage->load(RoleInterface::ANONYMOUS_ID);
  if ($role instanceof RoleInterface) {
    $role->grantPermission('view field_badges');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = $roleStorage->load(RoleInterface::AUTHENTICATED_ID);
  if ($role instanceof RoleInterface) {
    $role->grantPermission('view field_badges');
    $role->save();
  }

  /** @var \Drupal\user\RoleInterface|null $role */
  $role = $roleStorage->load('oc_admin');
  if ($role instanceof RoleInterface) {
    $role->grantPermission('edit field_badges');
    $role->save();
  }

  $view = Views::getView('related_profile');
  if ($view) {
    $display = $view->getDisplay();
    $sorts = $display->getOption('sorts');
    if (isset($sorts['sticky'])) {
      $sticky = $sorts['sticky'];
      unset($sorts['sticky']);
      $sorts = array_merge(['sticky' => $sticky], $sorts);
      $display->setOption('sorts', $sorts);
      $view->save();
    }
  }

  return $updater->logger()->output();
}

/**
 * Sets the offset to 0.
 */
function openculturas_post_update_related_date_alternative_pager_offset(): void {
  $view = Views::getView('related_date');
  if ($view) {
    $display = $view->setDisplay('related_date_alternative');
    if ($display) {
      $pager = $view->getDisplay()->getOption('pager');
      if (isset($pager['options']['offset'])) {
        $pager['options']['offset'] = 0;
        $view->getDisplay()->setOption('pager', $pager);
        $view->save();
      }
    }
  }
}

/**
 * Setups IEF for the location reference field in date entity.
 */
function openculturas_post_update_add_ief_for_location_ref_in_date(): string {
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $new_configs = [
    'core.entity_form_mode.node.compact',
    'core.entity_form_display.node.location.compact',
    'asset_injector.css.oc_gin_ief_overrides',
  ];
  foreach ($new_configs as $full_config_name) {
    $config_name = ConfigName::createByFullName($full_config_name);

    if ($configUpdater->revert($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully reverted.', $full_config_name));
    }
    elseif ($configUpdater->import($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  $configFactory = \Drupal::configFactory();
  $config = $configFactory->getEditable('field.field.node.date.field_location');
  if (!$config->isNew()) {
    $config->set('description', "Start typing the location's name and wait for autocompletion. Not found? Then create one. You can edit it later and add more information.");
    $config->save();
  }

  $languageManager = \Drupal::languageManager();
  if ($languageManager instanceof ConfigurableLanguageManagerInterface) {
    $configTranslation = $languageManager->getLanguageConfigOverride('de', 'core.entity_form_display.node.location.compact');
    if ($configTranslation instanceof LanguageConfigOverride) {
      $configTranslation->set('third_party_settings.field_group.group_location_tab_basics.label', 'Basis-Informationen');
      $configTranslation->save();
    }

    $configTranslation = $languageManager->getLanguageConfigOverride('de', 'field.field.node.date.field_location');
    if ($configTranslation instanceof LanguageConfigOverride) {
      $configTranslation->set('description', 'Autovervollständigung: Vorschläge kommen bei Eingabe. Nicht gefunden? Dann neuen Ort erstellen. Dieser kann später noch bearbeitet und um mehr Informationen ergänzt werden.');
      $configTranslation->save();
    }

    $configTranslation = $languageManager->getLanguageConfigOverride('de', 'field.field.node.date.field_people_reference');
    if ($configTranslation instanceof LanguageConfigOverride) {
      $configTranslation->set('description', 'Wer tritt auf? Einträge können durch Ziehen sortiert werden.');
      $configTranslation->save();
    }
  }

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');
  $form_display = $entity_display->getFormDisplay('node', 'date', 'default');
  if (!$form_display->isNew()) {
    // Move organizer to the first tab.
    $old_group_id = 'group_date_tab_people';
    $group = $form_display->getThirdPartySetting('field_group', $old_group_id);
    if (isset($group['children']) && is_array($group['children'])) {
      $key = array_search('field_organizer', $group['children'], TRUE);
      if ($key !== FALSE) {
        unset($group['children'][$key]);
      }

      $group['children'] = array_values(array_unique($group['children']));
      $form_display->setThirdPartySetting('field_group', $old_group_id, $group);
    }

    $new_group_id = 'group_date_tab_basics';
    $group = $form_display->getThirdPartySetting('field_group', $new_group_id);
    if (isset($group['children']) && is_array($group['children'])) {
      $group['children'][] = 'field_organizer';
      $group['children'] = array_values(array_unique($group['children']));
      $form_display->setThirdPartySetting('field_group', $new_group_id, $group);
    }

    // Change widget of field_location to inline_entity_form_complex_open.
    $field_location_component = $form_display->getComponent('field_location');
    if (is_array($field_location_component) && $field_location_component['type'] !== 'inline_entity_form_complex_open') {
      $field_location_component['type'] = 'inline_entity_form_complex_open';
      $field_location_component['settings'] = [
        'form_mode' => 'compact',
        'override_labels' => '1',
        'label_singular' => 'Location',
        'label_plural' => 'Locations',
        'allow_new' => '1',
        'allow_existing' => '1',
        'match_operator' => 'CONTAINS',
        'removed_reference' => 'keep',
        'revision' => 0,
        'collapsible' => 0,
        'collapsed' => 0,
        'allow_duplicate' => 0,
      ];
      $form_display->setComponent('field_location', $field_location_component);
    }

    $form_display->save();
  }

  return $logger->output();
}

/**
 * Place a new block for the openculturas theme.
 */
function openculturas_post_update_add_info_block_about_moderation_states_for_date(): string {
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $new_or_changed_configs = [
    'views.view.oc_er_mod_states',
    'block.block.a11y_features',
    'block.block.call_to_action',
    'block.block.oc_search_input',
    'block.block.openculturas_base_breadcrumbs',
    'block.block.openculturas_base_content',
    'block.block.openculturas_base_help',
    'block.block.openculturas_base_local_actions',
    'block.block.openculturas_base_local_tasks',
    'block.block.openculturas_base_messages',
    'block.block.openculturas_er_mod_states',
    'block.block.pagetitlewithsubtitle',
    'block.block.typetaxonomytermname',
    'block.block.userlogin',
  ];
  foreach ($new_or_changed_configs as $full_config_name) {
    $config_name = ConfigName::createByFullName($full_config_name);

    if ($configUpdater->revert($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully reverted.', $full_config_name));
    }
    elseif ($configUpdater->import($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  $languageManager = \Drupal::languageManager();
  if ($languageManager instanceof ConfigurableLanguageManagerInterface) {
    $configTranslation = $languageManager->getLanguageConfigOverride('de', 'views.view.oc_er_mod_states');
    if ($configTranslation instanceof LanguageConfigOverride) {
      $configTranslation->set('display.default.display_options.title', 'Moderationsstatus verbundener Inhalte');
      $configTranslation->set('display.default.display_options.fields.title.alter.text', 'Veranstaltungsbeschreibung: {{ title }} ({{ moderation_state }})');
      $configTranslation->set('display.default.display_options.fields.title_1.alter.text', 'Ort: {{ title_1 }} ({{ moderation_state_1 }})');
      $configTranslation->set('display.default.display_options.fields.title_2.alter.text', 'Veranstalter*in: {{ title_2 }} ({{ moderation_state_2 }})');
      $configTranslation->set('display.default.display_options.fields.title_3.alter.text', 'Veranstaltungsreihe: {{ title_3 }} ({{ moderation_state_3 }})');
      $configTranslation->set('display.default.display_options.fields.title_4.alter.text', 'Termine in dieser Reihe: {{ title_4 }} ({{ moderation_state_4 }})');
      $configTranslation->set('display.default.display_options.footer.area_text_custom.content', '<div class="description">Diese Information ist nur sichtbar, weil du berechtigt bist, diesen Inhalt zu bearbeiten.</div>');
      $configTranslation->save();
    }
  }

  return $logger->output();
}

/**
 * Replace focal point field widget with the image crop field widget.
 */
function openculturas_post_update_replace_focal_point_with_image_crop(): string {
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $new_or_changed_configs = [
    'crop.type.16_9',
    'crop.type.1_1',
    'crop.type.free_format',
    'crop.type.image_crop',
    'core.entity_form_display.media.image.default',
    'core.entity_form_display.media.image.media_library',
    'core.entity_form_display.media.user_profile_picture.default',
    'core.entity_form_display.media.user_profile_picture.media_library',
    'image.style.content_image',
    'image.style.gallery',
    'image.style.header_image',
    'image.style.profile',
    'image.style.teaser',
    'image.style.teaser_big',
    'image_widget_crop.settings',
  ];
  foreach ($new_or_changed_configs as $full_config_name) {
    $config_name = ConfigName::createByFullName($full_config_name);

    if ($configUpdater->revert($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully reverted.', $full_config_name));
    }
    elseif ($configUpdater->import($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  return $logger->output();
}

/**
 * Enable default filename sanitization configuration.
 */
function openculturas_post_update_enable_default_filename_sanitization_configuration(): void {
  $config = \Drupal::configFactory()->getEditable('file.settings');
  $config->set('filename_sanitization.transliterate', TRUE);
  $config->set('filename_sanitization.replace_whitespace', TRUE);
  $config->set('filename_sanitization.replace_non_alphanumeric', TRUE);
  $config->set('filename_sanitization.deduplicate_separators', TRUE);
  $config->set('filename_sanitization.lowercase', TRUE);
  $config->set('filename_sanitization.replacement_character', '-');
  $config->save();
}

/**
 * Add missing default translation filter to some views.
 */
function openculturas_post_update_add_missing_default_translation_filter(): string {
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $new_or_changed_configs = [
    'views.view.recommended_by',
    'views.view.related_event',
    'views.view.related_location',
  ];
  foreach ($new_or_changed_configs as $full_config_name) {
    $config_name = ConfigName::createByFullName($full_config_name);

    if ($configUpdater->revert($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully reverted.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  return $logger->output();
}

/**
 * Change details field_group to div html-element (term types).
 */
function openculturas_post_update_change_field_group_type_type_terms(): void {
  $term_bundles = [
    'article_type' => 'group_articles',
    'event_type' => 'group_events',
    'faq_category' => 'group_related_questions',
    'location_type' => 'group_locations',
    'page_type' => 'group_pages',
  ];
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');
  foreach ($term_bundles as $term_bundle => $group_id) {
    $view_display = $entity_display->getViewDisplay('taxonomy_term', $term_bundle, 'full');
    if (!$view_display->isNew()) {
      $group = $view_display->getThirdPartySetting('field_group', $group_id);
      if ($group['format_type'] !== 'details') {
        continue;
      }

      $group['format_type'] = 'html_element';
      $group['format_settings']['classes'] = 'related_content';
      $group['format_settings'] += [
        'element' => 'div',
        'show_label' => 0,
        'label_element' => 'h3',
        'label_element_classes' => '',
        'effect' => 'none',
        'speed' => 'fast',
        'attributes' => '',
        'show_empty_fields' => FALSE,
      ];
      unset($group['format_settings']['open'], $group['format_settings']['required_fields'], $group['format_settings']['description']);

      $view_display->setThirdPartySetting('field_group', $group_id, $group);
      $view_display->save();
    }
  }
}

/**
 * Enable missing media edit button.
 */
function openculturas_post_update_enable_media_edit_2(): void {
  if (\Drupal::moduleHandler()->moduleExists('media_library_edit') === FALSE) {
    return;
  }

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');

  $bundles = [
    'event_type',
    'article_type',
    'column',
    'category',
    'location_type',
    'page_type',
  ];
  foreach ($bundles as $bundle) {
    $entityFormDisplay = $entity_display->getFormDisplay('taxonomy_term', $bundle);
    if (!$entityFormDisplay->isNew() && $entityFormDisplay->getComponent('field_mood_image')) {
      $component = $entityFormDisplay->getComponent('field_mood_image');
      $component['third_party_settings']['media_library_edit']['show_edit'] = '1';
      $entityFormDisplay->setComponent('field_mood_image', $component);
      $entityFormDisplay->save();
    }
  }
}

/**
 * Moves the field layout_switcher to field group administrative.
 */
function openculturas_post_update_move_field_layout_switcher(): void {
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');
  $form_display = $entity_display->getFormDisplay('node', 'page', 'default');
  if (!$form_display->isNew() && $form_display->getComponent('field_layout_switcher')) {
    $group = $form_display->getThirdPartySetting('field_group', 'group_basics');
    if (isset($group['children'])) {
      $key = array_search('field_layout_switcher', $group['children'], TRUE);
      unset($group['children'][$key]);
      $group['children'] = array_values(array_unique($group['children']));
      $form_display->setThirdPartySetting('field_group', 'group_basics', $group);
    }

    $group = $form_display->getThirdPartySetting('field_group', 'group_administrative');
    if (isset($group['children'])) {
      $group['children'][] = 'field_layout_switcher';
      $group['children'] = array_values(array_unique($group['children']));
      $form_display->setThirdPartySetting('field_group', 'group_administrative', $group);
      $form_display->save();
    }

    /** @var \Drupal\user\RoleStorageInterface $roleStorage */
    $roleStorage = \Drupal::entityTypeManager()->getStorage('user_role');
    /** @var \Drupal\user\RoleInterface|null $role */
    $role = $roleStorage->load('oc_admin');
    if ($role instanceof RoleInterface) {
      $role->grantPermission('create field_layout_switcher');
      $role->grantPermission('edit own field_layout_switcher');
      $role->grantPermission('edit field_layout_switcher');
      $role->save();
    }
  }
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
 * Source string spell corrections.
 */
function openculturas_post_update_source_string_spell_corrections(): void {
  // Letter case, not title case.
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');
  $form_display = $entity_display->getFormDisplay('node', 'date', 'default');
  if (!$form_display->isNew()) {
    $group_id = 'group_date_tab_attendance';
    $group = $form_display->getThirdPartySetting('field_group', $group_id);
    if ($group) {
      $group['label'] = 'Attendance and tickets';
      $form_display->setThirdPartySetting('field_group', $group_id, $group);
    }

    $group_id = 'group_date_group_event_series';
    $group = $form_display->getThirdPartySetting('field_group', $group_id);
    if ($group) {
      $group['label'] = 'Event series';
      $form_display->setThirdPartySetting('field_group', $group_id, $group);
      $form_display->save();
    }
  }

  $view_display = $entity_display->getFormDisplay('user', 'user', 'full');
  if (!$view_display->isNew()) {
    $group_id = 'group_my_bookmarks';
    $group = $view_display->getThirdPartySetting('field_group', $group_id);
    if ($group) {
      $group['label'] = 'My bookmarks';
      $view_display->setThirdPartySetting('field_group', $group_id, $group);
      $view_display->save();
    }
  }

  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('block.block.a11y_features');
  if (!$config->isNew()) {
    $config->set('settings.label', 'Accessibility features');
    $config->save();
  }

  $config = $config_factory->getEditable('field.field.paragraph.accessibility.field_a11y_features');
  if (!$config->isNew()) {
    $config->set('label', 'Accessibility features');
    $config->save();
  }

  $config = $config_factory->getEditable('core.entity_view_mode.media.header_image');
  if (!$config->isNew()) {
    $config->set('label', 'Header image');
    $config->save();
  }

  $config = $config_factory->getEditable('taxonomy.vocabulary.article_type');
  if (!$config->isNew()) {
    $config->set('name', 'Article type');
    $config->save();
  }

  $config = $config_factory->getEditable('taxonomy.vocabulary.event_type');
  if (!$config->isNew()) {
    $config->set('name', 'Event type');
    $config->set('description', 'Event sub types');
    $config->save();
  }

  $config = $config_factory->getEditable('taxonomy.vocabulary.location_type');
  if (!$config->isNew()) {
    $config->set('name', 'Location type');
    $config->set('description', 'Location sub types');
    $config->save();
  }

  $config = $config_factory->getEditable('taxonomy.vocabulary.page_type');
  if (!$config->isNew()) {
    $config->set('name', 'Page type');
    $config->save();
  }

  $config = $config_factory->getEditable('shortcut.set.magazin-redaktion');
  if (!$config->isNew()) {
    $config->set('label', 'Magazine editors');
    $config->save();
  }

  $config = $config_factory->getEditable('field.field.media.image.field_caption');
  if (!$config->isNew()) {
    $config->set('description', 'Name and caption can be displayed in galleries');
    $config->save();
  }

  $config = $config_factory->getEditable('field.field.node.date.field_attendance_mode');
  if (!$config->isNew()) {
    $config->set('label', 'Attendance mode');
    $config->save();
  }

  $config = $config_factory->getEditable('system.menu.call-to-action');
  if (!$config->isNew()) {
    $config->set('description', 'Menu with highlighted styling for flexible placement');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.article_types');
  if (!$config->isNew()) {
    $config->set('label', 'Article type (all languages)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.categories');
  if (!$config->isNew()) {
    $config->set('label', 'Category (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.categories_de');
  if (!$config->isNew()) {
    $config->set('label', 'Category (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.column');
  if (!$config->isNew()) {
    $config->set('label', 'Column (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.column_de');
  if (!$config->isNew()) {
    $config->set('label', 'Column (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.comment');
  if (!$config->isNew()) {
    $config->set('label', 'Comment (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.comment_de');
  if (!$config->isNew()) {
    $config->set('label', 'Comment (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.dates');
  if (!$config->isNew()) {
    $config->set('label', 'Date (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.dates_de');
  if (!$config->isNew()) {
    $config->set('label', 'Date (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.event_types');
  if (!$config->isNew()) {
    $config->set('label', 'Event type (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.event_types_de');
  if (!$config->isNew()) {
    $config->set('label', 'Event type (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.events');
  if (!$config->isNew()) {
    $config->set('label', 'Event (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.events_de');
  if (!$config->isNew()) {
    $config->set('label', 'Event (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.location_type');
  if (!$config->isNew()) {
    $config->set('label', 'Location type (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.location_type_de');
  if (!$config->isNew()) {
    $config->set('label', 'Location type (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.locations');
  if (!$config->isNew()) {
    $config->set('label', 'Location (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.locations_de');
  if (!$config->isNew()) {
    $config->set('label', 'Location (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.magazine');
  if (!$config->isNew()) {
    $config->set('label', 'Magazine (EN)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.magazine_de');
  if (!$config->isNew()) {
    $config->set('label', 'Magazine (DE)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.page_type');
  if (!$config->isNew()) {
    $config->set('label', 'Page type (all languages)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.profiles');
  if (!$config->isNew()) {
    $config->set('label', 'Profile (all languages)');
    $config->save();
  }

  $config = $config_factory->getEditable('views.view.locations');
  if (!$config->isNew()) {
    $config->set('display.attachment_map.display_options.header.result.content', '<p class="result-counter">Displaying <strong>@start - @end</strong> of <strong>@total</strong></p>');
    $config->save();
  }

  $config = $config_factory->getEditable('views.view.my_content');
  if (!$config->isNew()) {
    $config->set('display.my_content_block.display_options.header.area_text_custom.content', '<p class="infobox">To add a new date, start from the event page\'s calendar section.</p>');
    $config->save();
  }

  $config = $config_factory->getEditable('views.view.oc_er_mod_states');
  if (!$config->isNew()) {
    $config->set('display.default.display_options.footer.area_text_custom.content', '<p class="description">You are seeing this info because you have permission to edit this content. Otherwise it is invisible.</p>');
    $config->save();
  }

  $config = $config_factory->getEditable('views.view.related_date');
  if (!$config->isNew()) {
    if ($config->get('display.default.display_options.fields.nothing.plugin_id')) {
      $config->set('display.default.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.default.display_options.fields.nothing_1.plugin_id')) {
      $config->set('display.default.display_options.fields.nothing_1.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.block_front_dates.display_options.fields.nothing.plugin_id')) {
      $config->set('display.block_front_dates.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.block_front_dates.display_options.fields.nothing_1.plugin_id')) {
      $config->set('display.block_front_dates.display_options.fields.nothing_1.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.block_online.display_options.fields.nothing.plugin_id')) {
      $config->set('display.block_online.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.block_online.display_options.fields.nothing_1.plugin_id')) {
      $config->set('display.block_online.display_options.fields.nothing_1.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.related_date_alternative.display_options.fields.nothing.plugin_id')) {
      $config->set('display.related_date_alternative.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.related_date_alternative.display_options.fields.nothing_1.plugin_id')) {
      $config->set('display.related_date_alternative.display_options.fields.nothing_1.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.related_date_event.display_options.fields.nothing.plugin_id')) {
      $config->set('display.related_date_event.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.related_date_event.display_options.fields.nothing_1.plugin_id')) {
      $config->set('display.related_date_event.display_options.fields.nothing_1.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.related_date_location.display_options.fields.nothing.plugin_id')) {
      $config->set('display.related_date_location.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.related_date_location.display_options.fields.nothing_1.plugin_id')) {
      $config->set('display.related_date_location.display_options.fields.nothing_1.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.upcoming_dates.display_options.fields.nothing.plugin_id')) {
      $config->set('display.upcoming_dates.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.upcoming_dates.display_options.fields.nothing_1.plugin_id')) {
      $config->set('display.upcoming_dates.display_options.fields.nothing_1.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.upcoming_dates_map.display_options.fields.nothing.plugin_id')) {
      $config->set('display.upcoming_dates_map.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.upcoming_dates_list.display_options.fields.nothing.plugin_id')) {
      $config->set('display.upcoming_dates_list.display_options.fields.nothing.plugin_id', 'non_translatable_custom');
    }

    if ($config->get('display.upcoming_dates_list.display_options.fields.nothing_1.plugin_id')) {
      $config->set('display.upcoming_dates_list.display_options.fields.nothing_1.plugin_id', 'non_translatable_custom');
    }

    $config->save();
  }

  $config = $config_factory->getEditable('node.type.event');
  if (!$config->isNew()) {
    $config->set('help', '<a href="?tour" class="button">Take a tour</a><p class="description">Learn why and how to create an event. The button will reload this page and entries may be lost. To prevent this, save your changes before starting the tour.</p>');
    $config->save();
  }

  $config = $config_factory->getEditable('node.type.location');
  if (!$config->isNew()) {
    $config->set('help', '<a href="?tour" class="button">Take a tour</a><p class="description">Learn why and how to create a location profile. The button will reload this page and entries may be lost. To prevent this, save your changes before starting the tour.</p>');
    $config->save();
  }

  $config = $config_factory->getEditable('node.type.profile');
  if (!$config->isNew()) {
    $config->set('help', '<a href="?tour" class="button">Take a tour</a><p class="description">Learn why and how to create a profile. The button will reload this page and entries may be lost. To prevent this, save your changes before starting the tour.</p>');
    $config->save();
  }
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
