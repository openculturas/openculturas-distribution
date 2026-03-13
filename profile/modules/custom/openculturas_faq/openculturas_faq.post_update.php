<?php

declare(strict_types=1);

use Drupal\update_helper\ConfigName;

/**
 * Implements hook_removed_post_updates().
 */
function openculturas_faq_removed_post_updates(): array {
  return [
    'openculturas_faq_post_update_interaction_button_section' => '2.2.0',
    'openculturas_faq_post_update_enable_media_edit' => '2.2.0',
    'openculturas_faq_post_update_enable_media_edit_2' => '2.2.0',
    'openculturas_faq_post_update_source_string_spell_corrections' => '2.2.0',
  ];
}

/**
 * Display field_content_paragraphs in view mode synopsis.
 */
function openculturas_faq_post_update_view_display_synopsis(): string {
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  $full_config_name = 'core.entity_view_display.node.faq.synopsis';
  $config_name = ConfigName::createByFullName($full_config_name);
  if ($configUpdater->revert($config_name->getType(), $config_name->getName())) {
    $logger->info(sprintf('Configuration %s has been successfully reverted.', $full_config_name));
  }
  else {
    $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
  }

  return $logger->output();
}
