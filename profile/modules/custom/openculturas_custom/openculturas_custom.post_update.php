<?php

declare(strict_types=1);

use Drupal\Core\Site\Settings;
use Drupal\openculturas_custom\PeopleParagraphUnsharer;

/**
 * Implements hook_removed_post_updates().
 */
function openculturas_custom_removed_post_updates(): array {
  return [
    'openculturas_custom_post_update_set_allowed_values_function_for_field_status' => '2.2.0',
    'openculturas_custom_post_update_set_allowed_values_function_for_field_premiere' => '2.2.0',
    'openculturas_custom_post_update_grant_administer_openculturas_custom_configuration' => '2.2.0',
  ];
}

/**
 * Gives every node its own copy of a people paragraph shared with another node.
 */
function openculturas_custom_post_update_unshare_people_paragraphs(?array &$sandbox = NULL): string {
  $sandbox ??= [];
  $unsharer = \Drupal::service(PeopleParagraphUnsharer::class);
  if (!isset($sandbox['pending'])) {
    $pending = [];
    foreach ($unsharer->sharedReferences() as $nodeId => $owners) {
      $pending[] = [$nodeId, array_keys($owners)];
    }

    $sandbox['pending'] = $pending;
    $sandbox['total'] = count($pending);
    $sandbox['duplicates'] = 0;
  }

  /** @var list<array{int, list<int>}> $pending */
  $pending = $sandbox['pending'];
  /** @var int $duplicates */
  $duplicates = $sandbox['duplicates'];
  /** @var int $total */
  $total = $sandbox['total'];
  $batchSize = Settings::get('entity_update_batch_size', 50);
  foreach (array_splice($pending, 0, is_int($batchSize) ? $batchSize : 50) as [$nodeId, $targetIds]) {
    $duplicates += $unsharer->unshareNode($nodeId, $targetIds);
  }

  $sandbox['pending'] = $pending;
  $sandbox['duplicates'] = $duplicates;
  $sandbox['#finished'] = $total === 0 ? 1 : ($total - count($pending)) / $total;
  return $pending === [] ? sprintf('Created %d people paragraph duplicates for %d nodes.', $duplicates, $total) : '';
}
