<?php

declare(strict_types=1);

namespace Drupal\openculturas_responsive_images;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\State\StateInterface;
use Drupal\crop\CropInterface;
use Drupal\crop\CropTypeInterface;
use Drupal\crop\Entity\Crop;

/**
 * Duplicates existing image crops onto this module's oc_* crop types.
 *
 * The duplicates keep the crop coordinates, so editors keep their existing
 * framing. If the target has a different aspect ratio, the crop is trimmed
 * around its center to the largest rectangle with the target ratio that fits
 * inside the original, so the image is never distorted. Crops that already
 * have a duplicate are skipped, so a migration can run again safely.
 */
final readonly class CropMigrator {

  /**
   * The crop types this module ships.
   *
   * @var list<string>
   */
  public const array OWN_CROP_TYPES = [
    'oc_16_9',
    'oc_1_1',
    'oc_2_1',
    'oc_3_4',
    'oc_4_3',
    'oc_9_16',
  ];

  /**
   * The state key holding the source to target mappings that were migrated.
   */
  public const string MAPPINGS_STATE_KEY = 'openculturas_responsive_images.crop_mappings';

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private EntityFieldManagerInterface $entityFieldManager,
    private Connection $database,
    private StateInterface $state,
  ) {}

  /**
   * The oc_* crop types crops can be migrated onto.
   *
   * @return array<string, \Drupal\crop\CropTypeInterface>
   *   The oc_* crop types, keyed by machine name.
   */
  public function targetTypes(): array {
    /** @var array<string, \Drupal\crop\CropTypeInterface> $types */
    $types = $this->entityTypeManager->getStorage('crop_type')->loadMultiple(self::OWN_CROP_TYPES);
    return $types;
  }

  /**
   * Crop types with unmigrated crops.
   *
   * Crop types (other than this module's own) that still have crops with no
   * oc_* equivalent yet. "Equivalent" is scoped to oc_* types sharing the
   * source's aspect ratio, so an unrelated migration (e.g. 16:9) can't make
   * a same-file crop of a different type (e.g. Free format) look migrated
   * just because it shares a file URI. Targets with a different ratio count
   * only if a migration onto them was run for this very source (see
   * migrate()), so the scoping above still holds.
   *
   * Crop types without an aspect ratio (focal point, free format) are never
   * listed. A focal point is no framing, and free-format coordinates don't
   * fit a fixed oc_* ratio, so there is nothing to migrate for them and they
   * would otherwise stay listed for good.
   *
   * @return array<string, \Drupal\crop\CropTypeInterface>
   *   The migratable crop types, keyed by machine name.
   */
  public function migratableSourceTypes(): array {
    /** @var array<string, \Drupal\crop\CropTypeInterface> $allTypes */
    $allTypes = $this->entityTypeManager->getStorage('crop_type')->loadMultiple();

    $sources = [];
    foreach ($allTypes as $id => $cropType) {
      if (in_array($id, self::OWN_CROP_TYPES, TRUE)) {
        continue;
      }

      if ($cropType->getAspectRatio() === '') {
        continue;
      }

      $targetIds = array_values(array_unique([
        ...$this->matchingTargetIds($cropType),
        ...$this->migratedTargetIds($id),
      ]));
      if ($this->hasUnmigratedCrop($id, $targetIds)) {
        $sources[$id] = $cropType;
      }
    }

    return $sources;
  }

  /**
   * Suggests a target crop type by matching aspect ratio.
   *
   * @return string
   *   The single aspect-ratio-matching target's machine name, or an empty
   *   string if there is no unambiguous match.
   */
  public function suggestedTarget(CropTypeInterface $source): string {
    $matches = $this->matchingTargetIds($source);
    return count($matches) === 1 ? reset($matches) : '';
  }

  /**
   * The IDs of a crop type's crops that have no crop of the target type yet.
   *
   * This is the same condition migrate() checks per crop, so passing the IDs
   * to migrate() only touches crops that are still to be duplicated.
   *
   * @param string $cropType
   *   The source crop type machine name.
   * @param string $targetType
   *   The oc_* crop type machine name crops are migrated onto.
   *
   * @return list<int|string>
   *   The crop IDs without a crop of the target type.
   */
  public function cropIds(string $cropType, string $targetType): array {
    $query = $this->unmigratedCropQuery($cropType, [$targetType]);
    if ($query instanceof SelectInterface) {
      return array_values($query->execute()?->fetchCol() ?? []);
    }

    return array_values($this->entityTypeManager->getStorage('crop')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $cropType)
      ->execute());
  }

  /**
   * Duplicates the given crops of a source crop type onto a target type.
   *
   * The source to target pair is remembered even when no crop is duplicated,
   * so that a source whose files all already have a crop of the target type
   * is no longer listed as unmigrated. Callers therefore have to reach this
   * method even with an empty list of crop IDs.
   *
   * @param string $sourceType
   *   The crop type machine name to migrate from.
   * @param list<int|string> $cropIds
   *   The IDs of the source's crops to duplicate. Crops that already have a
   *   crop of the target type for their file are skipped.
   * @param string $targetType
   *   The oc_* crop type machine name to migrate onto.
   *
   * @return int
   *   The number of crops duplicated.
   */
  public function migrate(string $sourceType, array $cropIds, string $targetType): int {
    $storage = $this->entityTypeManager->getStorage('crop');
    $targetCropType = $this->entityTypeManager->getStorage('crop_type')->load($targetType);
    $targetRatio = $targetCropType instanceof CropTypeInterface ? $targetCropType->getAspectRatio() : '';
    $sourceCropType = $this->entityTypeManager->getStorage('crop_type')->load($sourceType);
    $sourceRatio = $sourceCropType instanceof CropTypeInterface ? $sourceCropType->getAspectRatio() : '';
    $duplicated = 0;
    foreach ($storage->loadMultiple($cropIds) as $legacyCrop) {
      if (!$legacyCrop instanceof CropInterface) {
        continue;
      }

      $uri = (string) $legacyCrop->get('uri')->value;
      if (Crop::findCrop($uri, $targetType) !== NULL) {
        continue;
      }

      $size = [
        'width' => (int) $legacyCrop->get('width')->value,
        'height' => (int) $legacyCrop->get('height')->value,
      ];
      if ($sourceRatio !== $targetRatio) {
        $size = $this->fitToRatio($size['width'], $size['height'], (string) $targetRatio);
      }

      $storage->create([
        'type' => $targetType,
        'entity_id' => $legacyCrop->get('entity_id')->value,
        'entity_type' => $legacyCrop->get('entity_type')->value,
        'uri' => $uri,
        'x' => $legacyCrop->get('x')->value,
        'y' => $legacyCrop->get('y')->value,
        'width' => $size['width'],
        'height' => $size['height'],
      ])->save();
      $duplicated++;
    }

    $this->rememberMapping($sourceType, $targetType);

    return $duplicated;
  }

  /**
   * Parses an aspect ratio of the form 'W:H' into its width and height.
   *
   * @return array{int, int}|null
   *   The width and height, or NULL if the ratio is empty or malformed.
   */
  private function parseRatio(string $aspectRatio): ?array {
    if (preg_match('/^(\d+):(\d+)$/', $aspectRatio, $matches) !== 1) {
      return NULL;
    }

    $width = (int) $matches[1];
    $height = (int) $matches[2];
    return $width > 0 && $height > 0 ? [$width, $height] : NULL;
  }

  /**
   * Shrinks a rectangle to the largest one with the target aspect ratio.
   *
   * The rectangle keeps its center, so only the size changes. Unparsable
   * ratios leave the size unchanged.
   *
   * @return array{width: int, height: int}
   *   The fitted size.
   */
  private function fitToRatio(int $width, int $height, string $targetAspectRatio): array {
    $ratio = $this->parseRatio($targetAspectRatio);
    if ($ratio === NULL || $height === 0) {
      return ['width' => $width, 'height' => $height];
    }

    $targetRatio = $ratio[0] / $ratio[1];
    if ($width / $height > $targetRatio) {
      $width = (int) round($height * $targetRatio);
    }
    else {
      $height = (int) round($width / $targetRatio);
    }

    return ['width' => $width, 'height' => $height];
  }

  /**
   * The oc_* crop types a source was migrated onto, per stored history.
   *
   * @return list<string>
   *   The target crop type machine names.
   */
  private function migratedTargetIds(string $sourceType): array {
    /** @var array<string, list<string>> $mappings */
    $mappings = $this->state->get(self::MAPPINGS_STATE_KEY, []);
    return $mappings[$sourceType] ?? [];
  }

  /**
   * Stores that a source crop type was migrated onto a target crop type.
   *
   * This is per-environment migration history, so it lives in state instead of
   * config.
   */
  private function rememberMapping(string $sourceType, string $targetType): void {
    /** @var array<string, list<string>> $mappings */
    $mappings = $this->state->get(self::MAPPINGS_STATE_KEY, []);
    $targets = $mappings[$sourceType] ?? [];
    if (in_array($targetType, $targets, TRUE)) {
      return;
    }

    $targets[] = $targetType;
    $mappings[$sourceType] = $targets;
    $this->state->set(self::MAPPINGS_STATE_KEY, $mappings);
  }

  /**
   * The oc_* crop types sharing a crop type's aspect ratio.
   *
   * @return list<string>
   *   The matching oc_* crop type machine names.
   */
  private function matchingTargetIds(CropTypeInterface $source): array {
    return array_keys(array_filter(
      $this->targetTypes(),
      static fn (CropTypeInterface $target): bool => $target->getAspectRatio() === $source->getAspectRatio()
    ));
  }

  /**
   * Whether a crop type has a crop whose file has no crop in the targets.
   *
   * @param string $sourceType
   *   The source crop type machine name.
   * @param list<string> $targetTypes
   *   The matching oc_* crop type machine names.
   */
  private function hasUnmigratedCrop(string $sourceType, array $targetTypes): bool {
    $query = $this->unmigratedCropQuery($sourceType, $targetTypes);
    if (!$query instanceof SelectInterface) {
      return FALSE;
    }

    return $query->range(0, 1)->execute()?->fetchField() !== FALSE;
  }

  /**
   * Selects the crops of a source type whose file has no crop of the targets.
   *
   * @param string $sourceType
   *   The source crop type machine name.
   * @param list<string> $targetTypes
   *   The oc_* crop type machine names a crop of the same file would count as
   *   migrated. An empty list matches every crop of the source type.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface|null
   *   The query selecting the crop IDs, or NULL if the crop storage is not
   *   backed by a single table with a known mapping.
   */
  private function unmigratedCropQuery(string $sourceType, array $targetTypes): ?SelectInterface {
    $storage = $this->entityTypeManager->getStorage('crop');
    if (!$storage instanceof SqlContentEntityStorage) {
      return NULL;
    }

    $tableMapping = $storage->getTableMapping();
    $table = $storage->getDataTable();
    if (!$tableMapping instanceof DefaultTableMapping || !is_string($table)) {
      return NULL;
    }

    $typeColumn = $storage->getEntityType()->getKey('bundle');
    $idColumn = $storage->getEntityType()->getKey('id');
    if (!is_string($typeColumn) || !is_string($idColumn)) {
      return NULL;
    }

    $uriColumn = $tableMapping->getFieldColumnName(
      $this->entityFieldManager->getFieldStorageDefinitions('crop')['uri'],
      'value',
    );

    $query = $this->database->select($table, 'source');
    $query->addField('source', $idColumn);
    $query->condition('source.' . $typeColumn, $sourceType);
    if ($targetTypes !== []) {
      $migrated = $this->database->select($table, 'migrated');
      $migrated->addExpression('1');
      $migrated->where('migrated.' . $uriColumn . ' = source.' . $uriColumn);
      $migrated->condition('migrated.' . $typeColumn, $targetTypes, 'IN');
      $query->notExists($migrated);
    }

    return $query;
  }

}
