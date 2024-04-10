<?php

declare(strict_types=1);

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
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
