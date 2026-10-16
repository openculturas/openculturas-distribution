<?php

declare(strict_types=1);

/**
 * Saves every configuration entity once.
 *
 * Saving applies the current config schema (type casting, dependency
 * recalculation, third party settings normalisation), so changes that modules
 * would otherwise introduce on their own the next time somebody edits an
 * entity become visible in one dedicated diff.
 *
 * Entities are loaded without configuration overrides from settings.php.
 * Saving an entity loaded with overrides would write the override values
 * (for example local hostnames or an enabled config split) into the active
 * configuration.
 *
 * Usage: ddev drush scr scripts/resave_config_entities.php
 * Then review: git diff --stat -- config/sync
 */

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;

$entity_type_manager = \Drupal::entityTypeManager();
$saved = 0;
$failures = [];

foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $definition) {
  if (!$definition instanceof ConfigEntityTypeInterface) {
    continue;
  }
  $storage = $entity_type_manager->getStorage($entity_type_id);
  if (!$storage instanceof ConfigEntityStorageInterface) {
    continue;
  }
  $count = 0;
  foreach ($storage->loadMultipleOverrideFree() as $entity) {
    try {
      $entity->save();
      $count++;
    }
    catch (\Throwable $exception) {
      $failures[] = sprintf('%s:%s: %s', $entity_type_id, $entity->id(), $exception->getMessage());
    }
  }
  $saved += $count;
  print sprintf("%-40s %d\n", $entity_type_id, $count);
}

print sprintf("\nSaved %d configuration entities, %d failures.\n", $saved, count($failures));
foreach ($failures as $failure) {
  print "FAILED $failure\n";
}
