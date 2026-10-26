<?php

declare(strict_types=1);

namespace Drupal\openculturas_entity_popover;

use Drupal\Core\Field\DeletedFieldsRepositoryInterface;
use Drupal\Core\Field\FieldPurger;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Removes what DisplayWirer added to the paragraph bundles again.
 *
 * The counterpart of DisplayWirer: the field storage and its field configs
 * are created in code, so the module that created them has to remove them
 * again. Only what the module created itself is touched - a bundle a site
 * builder hung the field on keeps its field config, and with it the storage.
 */
final readonly class FieldUnwirer {

  /**
   * The batch size handed to FieldPurger::purgeBatch() per round.
   */
  private const int BATCH_SIZE = 50;

  /**
   * The upper bound on purge rounds.
   *
   * Reaching it takes more data than self::BATCH_SIZE records per round times
   * this, more than a site is expected to have deleted popover values on;
   * whatever is left then goes to the field purge cron.
   */
  private const int MAX_ROUNDS = 100;

  public function __construct(
    private FieldPurger $fieldPurger,
    private DeletedFieldsRepositoryInterface $deletedFieldsRepository,
    private LoggerChannelInterface $logger,
  ) {}

  /**
   * Removes the field configs on the module's own bundles, and their data.
   *
   * The field storage is only removed when no field config is left on it:
   * FieldConfig::postDelete() does that on its own once the last field is
   * gone, so this never deletes the storage a second time.
   */
  public function unwireAll(): void {
    $storage = FieldStorageConfig::loadByName('paragraph', DisplayWirer::FIELD_NAME);
    if (!$storage) {
      return;
    }

    foreach (DisplayWirer::FIELD_BUNDLES as $bundle) {
      FieldConfig::loadByName('paragraph', $bundle, DisplayWirer::FIELD_NAME)?->delete();
    }

    $this->purge($storage->getUniqueStorageIdentifier());
  }

  /**
   * Purges what is left of the popover field, immediately.
   *
   * Purges the deleted field configs and, if the field storage is among them
   * because no field config was left to keep it, the storage too. One round
   * only frees self::BATCH_SIZE data records, and FieldPurger keeps a field
   * config in the repository until a round comes back with less than that, so
   * the loop runs until nothing of this field is pending or, at the latest,
   * for self::MAX_ROUNDS rounds.
   *
   * The field storage alone is not a sign that there is anything left to do:
   * as long as another bundle a site builder hung the field on still uses it,
   * it stays behind on purpose, and only the deleted field configs and their
   * data are waiting in the repository.
   *
   * @param string $uniqueId
   *   The unique storage identifier of the paragraph.field_entity_popover
   *   storage.
   */
  private function purge(string $uniqueId): void {
    $round = 0;
    while ($this->hasPendingDeletions($uniqueId)) {
      if ($round++ === self::MAX_ROUNDS) {
        $this->logger->warning('Stopped purging @id after @rounds batches. The rest is left to the field purge cron.', [
          '@id' => $uniqueId,
          '@rounds' => self::MAX_ROUNDS,
        ]);
        return;
      }

      $this->fieldPurger->purgeBatch(self::BATCH_SIZE, $uniqueId);
    }
  }

  /**
   * Whether anything of a field storage is still waiting to be purged.
   *
   * @param string $uniqueId
   *   The unique storage identifier of the field storage to look at.
   *
   * @return bool
   *   TRUE if the field storage or one of its field configs is in the deleted
   *   fields repository.
   */
  private function hasPendingDeletions(string $uniqueId): bool {
    return isset($this->deletedFieldsRepository->getFieldStorageDefinitions()[$uniqueId])
      || $this->deletedFieldsRepository->getFieldDefinitions($uniqueId) !== [];
  }

}
