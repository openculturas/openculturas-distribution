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

/**
 * Source string spell corrections.
 */
function openculturas_faq_post_update_source_string_spell_corrections(): void {
  // Typos + Replace technical correct wording with user-friendly wording.
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('views.view.faq');
  if (!$config->isNew()) {
    if ($config->get('display.page_faq_overview.display_options.fields.status.settings.format_custom_false')) {
      $config->set('display.page_faq_overview.display_options.fields.status.settings.format_custom_false', 'Unpublished');
    }

    if ($config->get('display.related_faq_term.display_options.title')) {
      $config->set('display.related_faq_term.display_options.title', 'Questions in this category');
    }

    $config->save();
  }

  $config = $config_factory->getEditable('core.entity_view_display.taxonomy_term.faq_category.full');
  if (!$config->isNew()) {
    if ($config->get('third_party_settings.field_group.group_related_questions.label')) {
      $config->set('third_party_settings.field_group.group_related_questions.label', 'Questions in this category');
    }

    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.faq_all_languages');
  if (!$config->isNew()) {
    $config->set('label', 'FAQ (all languages)');
    $config->save();
  }

  $config = $config_factory->getEditable('pathauto.pattern.faq_term_all_languages');
  if (!$config->isNew()) {
    $config->set('label', 'FAQ Term (all languages)');
    $config->save();
  }

}
