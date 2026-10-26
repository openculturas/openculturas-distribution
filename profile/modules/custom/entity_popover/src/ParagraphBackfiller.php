<?php

declare(strict_types=1);

namespace Drupal\entity_popover;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use function array_map;

/**
 * Sets empty popover toggles on paragraphs to their field's default value.
 *
 * Scoped to paragraph bundles only - node/media entities can already be
 * bulk-edited through Views Bulk Operations, paragraphs have no such
 * workaround. Discovers bundles by field type (not a hardcoded field name),
 * so this also picks up any popover field a site builder adds themselves.
 */
final readonly class ParagraphBackfiller {

  private const string FIELD_TYPE = 'entity_popover';

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private EntityFieldManagerInterface $entityFieldManager,
    private Connection $database,
    private CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * Backfills every paragraph bundle with a popover field.
   *
   * @return int
   *   The number of paragraphs updated.
   */
  public function backfillAll(): int {
    $updated = 0;
    foreach ($this->discoverParagraphFields() as $row) {
      $updated += $this->backfillBundle($row['bundle'], $row['field']);
    }

    return $updated;
  }

  /**
   * Paragraph bundles with a field of the entity_popover type.
   *
   * @return array<string, array{bundle: string, field: string}>
   *   Keyed by "<bundle>:<field name>" to stay unique even if a bundle ever
   *   carries more than one popover field.
   */
  public function discoverParagraphFields(): array {
    $fieldMap = $this->entityFieldManager->getFieldMapByFieldType(self::FIELD_TYPE);
    $rows = [];
    foreach ($fieldMap['paragraph'] ?? [] as $fieldName => $fieldInfo) {
      $fieldName = (string) $fieldName;
      foreach (array_keys($fieldInfo['bundles']) as $bundle) {
        $bundle = (string) $bundle;
        $rows["$bundle:$fieldName"] = ['bundle' => $bundle, 'field' => $fieldName];
      }
    }

    return $rows;
  }

  /**
   * Resolves the dedicated SQL tables backing a paragraph field.
   *
   * @return array{fieldStorage: \Drupal\field\FieldStorageConfigInterface, dataTable: string, revisionDataTable: string, fieldTable: string, fieldRevisionTable: string, valueColumn: string}|null
   *   NULL if the field has no configurable, dedicated SQL storage.
   */
  private function resolveFieldTables(string $fieldName): ?array {
    $fieldStorage = FieldStorageConfig::loadByName('paragraph', $fieldName);
    if (!$fieldStorage instanceof FieldStorageConfigInterface) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('paragraph');
    if (!$storage instanceof SqlContentEntityStorage) {
      return NULL;
    }

    $tableMapping = $storage->getTableMapping();
    $dataTable = $storage->getDataTable();
    $revisionDataTable = $storage->getRevisionDataTable();
    if (!$tableMapping instanceof DefaultTableMapping || !is_string($dataTable) || !is_string($revisionDataTable)) {
      return NULL;
    }

    return [
      'fieldStorage' => $fieldStorage,
      'dataTable' => $dataTable,
      'revisionDataTable' => $revisionDataTable,
      'fieldTable' => $tableMapping->getDedicatedDataTableName($fieldStorage),
      'fieldRevisionTable' => $tableMapping->getDedicatedRevisionTableName($fieldStorage),
      'valueColumn' => $tableMapping->getFieldColumnName($fieldStorage, 'value'),
    ];
  }

  /**
   * A bundle-deduplicated subquery joinable onto the revision table.
   *
   * The data table has one row per translation, so joining it directly onto
   * every revision would multiply rows for entities with more than one
   * translation. Bundle is immutable for a given paragraph id, so
   * deduplicate to one row per id first.
   *
   * For a non-translatable field the lookup is restricted to the
   * default-language rows as well, mirroring the revision restriction
   * described in revisionsMissingFieldSelect(). That does not change which
   * bundles are found - every translation of an entity carries the same
   * bundle - it only keeps the two restrictions in step.
   *
   * @param array{fieldStorage: \Drupal\field\FieldStorageConfigInterface, dataTable: string, revisionDataTable: string, fieldTable: string, fieldRevisionTable: string, valueColumn: string} $tables
   *   The tables backing the field, see resolveFieldTables().
   */
  private function bundleLookup(array $tables): SelectInterface {
    $select = $this->database->select($tables['dataTable'], 'bundles')
      ->fields('bundles', ['id', 'type'])
      ->distinct();
    if (!$tables['fieldStorage']->isTranslatable()) {
      $select->condition('bundles.default_langcode', 1);
    }

    return $select;
  }

  /**
   * A revision-table select joined and filtered down to "missing the field".
   *
   * Checks every revision, not just the current one - a host entity (another
   * paragraph, or the node itself) can still be pinned to an older revision
   * that predates the field being populated on this paragraph, see
   * backfillBundle(). Only checking the current revision would under-report
   * and let the "Update to default" checkbox get disabled while stale,
   * still-referenced revisions remain unfixed.
   *
   * Filters the dedicated revision table on `deleted = 0`, mirroring every
   * equivalent join Drupal core builds against dedicated field-storage
   * tables - otherwise a leftover deleted=1 row (field removed and re-added
   * to the bundle) defeats the anti-join and the row is wrongly treated as
   * already populated.
   *
   * A non-translatable field is additionally restricted to the base and
   * revision rows with default_langcode = 1. The base tables hold one row per
   * translation, but SqlContentEntityStorage::saveToDedicatedTables() stores
   * a non-translatable field under the entity's default langcode only, so a
   * translation row has no counterpart in the dedicated table and would
   * always pass the anti-join as "missing", no matter whether the value is
   * set - including an explicit 0. The langcode equality in the join stays:
   * on a default-language row the row's own langcode is the default langcode,
   * so it still matches. A translatable field is stored per language and
   * keeps the per-langcode matching for every translation.
   *
   * Shared by paragraphIdsMissingValue(), countEmptyByBundle() and
   * backfillBundle() so the join/condition sequence exists once.
   *
   * @param string|array $bundle
   *   The bundle to filter on: a single bundle, compared with `=`, or several
   *   bundles sharing this field name, compared with `IN`.
   * @param array{fieldStorage: \Drupal\field\FieldStorageConfigInterface, dataTable: string, revisionDataTable: string, fieldTable: string, fieldRevisionTable: string, valueColumn: string} $tables
   *   The tables backing the field, see resolveFieldTables().
   */
  private function revisionsMissingFieldSelect(string|array $bundle, array $tables): SelectInterface {
    $select = $this->database->select($tables['revisionDataTable'], 'revision');
    $select->innerJoin($this->bundleLookup($tables), 'base', 'base.id = revision.id');
    $select->leftJoin($tables['fieldRevisionTable'], 'existing', 'existing.entity_id = revision.id AND existing.revision_id = revision.revision_id AND existing.langcode = revision.langcode AND existing.deleted = 0');
    $select->condition('base.type', $bundle, is_array($bundle) ? 'IN' : '=');
    $select->isNull('existing.entity_id');
    if (!$tables['fieldStorage']->isTranslatable()) {
      $select->condition('revision.default_langcode', 1);
    }

    return $select;
  }

  /**
   * Paragraph ids of the given bundle with a revision missing the field.
   *
   * @return int[]
   *   The distinct paragraph ids.
   */
  private function paragraphIdsMissingValue(string $bundle, string $fieldName): array {
    $tables = $this->resolveFieldTables($fieldName);
    if ($tables === NULL) {
      return [];
    }

    $select = $this->revisionsMissingFieldSelect($bundle, $tables);
    $select->addField('revision', 'id', 'entity_id');
    $select->distinct();

    $ids = $select->execute()?->fetchCol() ?? [];
    return array_map(intval(...), $ids);
  }

  /**
   * Counts empty paragraphs for every bundle sharing one field, in one query.
   *
   * Grouped by bundle and counted in SQL (COUNT DISTINCT) rather than
   * fetching the full id list just to run count() on it, and batched across
   * every bundle that shares this field name rather than issuing one query
   * per (bundle, field) row - discoverParagraphFields() already knows every
   * bundle for a given field up front. The revision select itself comes from
   * revisionsMissingFieldSelect(), so what counts as "missing" - including
   * the default-language-only rule for a non-translatable field - is defined
   * in exactly one place.
   *
   * @param string[] $bundles
   *   The bundles to count, all carrying a field of this name.
   * @param string $fieldName
   *   The field name shared by every bundle in $bundles.
   *
   * @return array<string, int>
   *   Empty-entity count keyed by bundle. Bundles with no empty entities are
   *   omitted.
   */
  public function countEmptyByBundle(array $bundles, string $fieldName): array {
    $tables = $this->resolveFieldTables($fieldName);
    if ($tables === NULL || $bundles === []) {
      return [];
    }

    $select = $this->revisionsMissingFieldSelect($bundles, $tables);
    $select->addField('base', 'type', 'bundle');
    $select->addExpression('COUNT(DISTINCT revision.id)', 'empty_count');
    $select->groupBy('base.type');

    $counts = [];
    foreach ($select->execute() ?? [] as $record) {
      $counts[(string) $record->bundle] = (int) $record->empty_count;
    }

    return $counts;
  }

  /**
   * Sets the field's own configured default value on every empty entity.
   *
   * Writes the field's dedicated tables directly instead of loading and
   * saving every paragraph through the Entity API. Saving a paragraph
   * creates a new default revision for that paragraph only - it does not
   * update the target_revision_id a host entity (another paragraph, or the
   * node itself) still has pinned to the paragraph's older revision, so the
   * backfilled value would never actually render wherever that reference
   * still points at the old revision. Writing every existing revision row
   * directly avoids that mismatch entirely.
   *
   * Since this bypasses the Entity API, it also bypasses what a normal save()
   * does to caches. Replicating both halves of that:
   * EntityBase::invalidateTagsOnSave() invalidates the updated entity's own
   * tag plus the entity type's (non-bundle-specific) list tag - nothing more,
   * e.g. no ancestor entities - which is what render caches need. What tag
   * invalidation alone cannot do is refresh the entity itself: the persistent
   * entity cache entry (cache_entity) is only tagged with entity_field_info,
   * so a stale, pre-backfill entity would keep serving an empty field value
   * from Paragraph::load(). save() avoids that by running resetCache() on the
   * storage from EntityStorageBase::doPostSave(), which clears the memory
   * cache and the persistent cache for exactly these ids.
   *
   * @return int
   *   The number of paragraphs actually updated.
   */
  public function backfillBundle(string $bundle, string $fieldName): int {
    $fieldConfig = FieldConfig::loadByName('paragraph', $bundle, $fieldName);
    $tables = $this->resolveFieldTables($fieldName);
    if ($tables === NULL || !$fieldConfig) {
      return 0;
    }

    $ids = $this->paragraphIdsMissingValue($bundle, $fieldName);
    if ($ids === []) {
      return 0;
    }

    $defaultValue = $fieldConfig->getDefaultValueLiteral()[0]['value'] ?? 0;

    $select = $this->database->select($tables['dataTable'], 'base');
    $select->leftJoin($tables['fieldTable'], 'existing', 'existing.entity_id = base.id AND existing.langcode = base.langcode AND existing.deleted = 0');
    $select->condition('base.type', $bundle);
    if (!$tables['fieldStorage']->isTranslatable()) {
      $select->condition('base.default_langcode', 1);
    }

    $select->isNull('existing.entity_id');
    $select->addField('base', 'type', 'bundle');
    $select->addField('base', 'id', 'entity_id');
    $select->addField('base', 'revision_id', 'revision_id');
    $select->addField('base', 'langcode', 'langcode');
    $select->addExpression('0', 'deleted');
    $select->addExpression('0', 'delta');
    $select->addExpression(':value', $tables['valueColumn'], [':value' => $defaultValue]);
    $this->database->insert($tables['fieldTable'])->from($select)->execute();

    $select = $this->revisionsMissingFieldSelect($bundle, $tables);
    $select->addField('base', 'type', 'bundle');
    $select->addField('revision', 'id', 'entity_id');
    $select->addField('revision', 'revision_id', 'revision_id');
    $select->addField('revision', 'langcode', 'langcode');
    $select->addExpression('0', 'deleted');
    $select->addExpression('0', 'delta');
    $select->addExpression(':value', $tables['valueColumn'], [':value' => $defaultValue]);
    $this->database->insert($tables['fieldRevisionTable'])->from($select)->execute();

    $this->entityTypeManager->getStorage('paragraph')->resetCache($ids);

    $tags = [];
    foreach ($ids as $id) {
      $tags[] = 'paragraph:' . $id;
      $tags[] = 'paragraph:' . $id . ':revisions';
    }

    $tags[] = 'paragraph_list';
    $this->cacheTagsInvalidator->invalidateTags($tags);

    return count($ids);
  }

}
