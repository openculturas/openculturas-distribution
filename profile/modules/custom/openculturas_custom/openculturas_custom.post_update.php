<?php

declare(strict_types=1);

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\language\Config\LanguageConfigOverride;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\locale\StringInterface;

/**
 * Set allowed_values_function for field_status in node.
 */
function openculturas_custom_post_update_set_allowed_values_function_for_field_status(): void {
  $field_storage = FieldStorageConfig::load('node.field_event_status');
  if ($field_storage instanceof FieldStorageConfigInterface) {
    $field_storage->setSetting('allowed_values_function', 'openculturas_custom_event_status_allowed_values_function');
    $field_storage->save();
  }

  $strings = [
    'Cancelled' => 'Abgesagt',
    'Moved online' => 'Findet online statt',
    'Postponed' => 'Verschoben',
    'Rescheduled' => 'Neuer Termin',
    'Scheduled' => 'Findet planmäßig statt',
  ];
  /** @var \Drupal\locale\StringStorageInterface $string_storage */
  $string_storage = \Drupal::service('locale.storage');

  foreach ($strings as $english => $german) {
    $values = ['source' => $english, 'context' => 'event_status'];
    $source_string = $string_storage->findString($values);
    if ($source_string === NULL) {
      $source_string = $string_storage->createString($values)->save();
    }

    if (!$source_string instanceof StringInterface) {
      continue;
    }

    $translation = $string_storage->findTranslation(['lid' => $source_string->getId(), 'language' => 'de']);
    if ($translation === NULL) {
      try {
        $string_storage->createTranslation(['lid' => $source_string->getId(), 'language' => 'de', 'translation' => $german])->save();
      }
      catch (\Exception) {
      }
    }
    else {
      try {
        $translation->setValues(['language' => 'de', 'translation' => $german])->save();
      }
      catch (\Exception) {
      }
    }
  }
}

/**
 * Set allowed_values_function for field_premiere in node.
 */
function openculturas_custom_post_update_set_allowed_values_function_for_field_premiere(): void {
  $field_storage = FieldStorageConfig::load('node.field_premiere');
  if ($field_storage instanceof FieldStorageConfigInterface) {
    $field_storage->setSetting('allowed_values_function', 'openculturas_custom_premiere_allowed_values_function');
    $field_storage->setCardinality(1);
    $field_storage->save();
  }

  $strings = [
    'Premiere' => 'Premiere',
    'Opening' => 'Eröffnung',
    'Closing' => 'Zum letzten Mal',
    'Special' => 'Sonderveranstaltung',
  ];
  /** @var \Drupal\locale\StringStorageInterface $string_storage */
  $string_storage = \Drupal::service('locale.storage');

  foreach ($strings as $english => $german) {
    $values = ['source' => $english, 'context' => 'premiere'];
    $source_string = $string_storage->findString($values);
    if ($source_string === NULL) {
      $source_string = $string_storage->createString($values)->save();
    }

    if (!$source_string instanceof StringInterface) {
      continue;
    }

    $translation = $string_storage->findTranslation(['lid' => $source_string->getId(), 'language' => 'de']);
    if ($translation === NULL) {
      try {
        $string_storage->createTranslation(['lid' => $source_string->getId(), 'language' => 'de', 'translation' => $german])->save();
      }
      catch (\Exception) {
      }
    }
    else {
      try {
        $translation->setValues(['language' => 'de', 'translation' => $german])->save();
      }
      catch (\Exception) {
      }
    }
  }

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display */
  $entity_display = \Drupal::service('entity_display.repository');
  $form_display = $entity_display->getFormDisplay('node', 'date', 'default');
  if (!$form_display->isNew()) {
    $component = $form_display->getComponent('field_premiere');
    if ($component) {
      $component['type'] = 'options_select';
      $form_display->setComponent('field_premiere', $component);
      $form_display->save();
    }
  }

  $config = \Drupal::configFactory()->getEditable('field.field.node.date.field_premiere');
  if (!$config->isNew()) {
    $config->set('label', 'Highlight / special occasion');
    $config->set('description', 'Highlight this date as a special occasion. The selected value will be added to the title of the date.');
    $config->save();

    $languageManager = \Drupal::languageManager();
    if ($languageManager instanceof ConfigurableLanguageManagerInterface) {
      $configTranslation = $languageManager->getLanguageConfigOverride('de', 'field.field.node.date.field_premiere');
      if ($configTranslation instanceof LanguageConfigOverride) {
        $configTranslation->set('label', 'Hervorhebung / besonderer Anlass');
        $configTranslation->set('description', 'Termin als besonderen Anlass hervorheben. Der gewählte Wert wird zum Titel des Termins hinzugefügt.');
        $configTranslation->save();
      }
    }
  }
}
