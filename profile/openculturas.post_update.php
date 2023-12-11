<?php

/**
 * @file
 * Install, update and uninstall module functions.
 */

declare(strict_types = 1);

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldStorageConfigInterface;

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
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $callback = fn(FieldStorageConfigInterface $fieldStorageConfig): bool => $fieldStorageConfig->getType() === 'viewfield';
  $config_entity_updater->update($sandbox, 'field_storage_config', $callback);
}
