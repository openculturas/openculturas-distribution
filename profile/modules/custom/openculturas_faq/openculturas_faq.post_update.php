<?php

declare(strict_types=1);

/**
 * Re-order of interact field_group fields.
 */
function openculturas_faq_post_update_interaction_button_section(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas_faq', 'openculturas_faq_post_update_interaction_button_section');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Enable missing media edit button.
 */
function openculturas_faq_post_update_enable_media_edit(): void {
  if (\Drupal::moduleHandler()->moduleExists('media_library_edit') === FALSE) {
    return;
  }

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');
  $entityFormDisplay = $entity_display->getFormDisplay('node', 'faq');
  if (!$entityFormDisplay->isNew() && $entityFormDisplay->getComponent('field_mood_image')) {
    $component = $entityFormDisplay->getComponent('field_mood_image');
    $component['third_party_settings']['media_library_edit']['show_edit'] = '1';
    $entityFormDisplay->setComponent('field_mood_image', $component);
    $entityFormDisplay->save();
  }

}

/**
 * Enable missing media edit button.
 */
function openculturas_faq_post_update_enable_media_edit_2(): void {
  if (\Drupal::moduleHandler()->moduleExists('media_library_edit') === FALSE) {
    return;
  }

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');
  $entityFormDisplay = $entity_display->getFormDisplay('taxonomy_term', 'faq_category');
  if (!$entityFormDisplay->isNew() && $entityFormDisplay->getComponent('field_mood_image')) {
    $component = $entityFormDisplay->getComponent('field_mood_image');
    $component['third_party_settings']['media_library_edit']['show_edit'] = '1';
    $entityFormDisplay->setComponent('field_mood_image', $component);
    $entityFormDisplay->save();
  }

}
