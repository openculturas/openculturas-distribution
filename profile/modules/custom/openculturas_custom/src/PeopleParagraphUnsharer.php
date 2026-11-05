<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Gives every node its own copy of a shared "people" paragraph.
 *
 * Creating a date from an event cloned the event's people paragraphs with
 * their ID, so the date and the event referenced the same paragraph. Every
 * save of either node then moved the paragraph to a new parent and revision.
 * The node named as parent of the paragraph keeps it. Every other node gets a
 * duplicate of the paragraph revision it references, in its default and in
 * its latest revision.
 */
final readonly class PeopleParagraphUnsharer {

  private const string FIELD_NAME = 'field_people_reference';

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private Connection $database,
  ) {}

  /**
   * Lists the nodes that reference a people paragraph of another node.
   *
   * @return array<int, array<int, int>>
   *   The ID of the node that keeps the paragraph, keyed by the ID of the
   *   shared paragraph to replace and by node ID.
   */
  public function sharedReferences(): array {
    $nodesByTarget = array_filter(
      $this->nodesByTarget(),
      static fn(array $nodes): bool => count($nodes) > 1,
    );
    if ($nodesByTarget === []) {
      return [];
    }

    $parents = $this->database->select('paragraphs_item_field_data', 'paragraph')
      ->fields('paragraph', ['id', 'parent_id'])
      ->condition('paragraph.id', array_keys($nodesByTarget), 'IN')
      ->condition('paragraph.parent_type', 'node')
      ->condition('paragraph.default_langcode', 1)
      ->execute()?->fetchAllKeyed() ?? [];

    $nodeIds = [];
    foreach ($nodesByTarget as $targetId => $nodes) {
      $owner = isset($parents[$targetId], $nodes[(int) $parents[$targetId]]) ? (int) $parents[$targetId] : min(array_keys($nodes));
      foreach (array_keys($nodes) as $nodeId) {
        if ($nodeId !== $owner) {
          $nodeIds[$nodeId][$targetId] = $owner;
        }
      }
    }

    ksort($nodeIds);
    return $nodeIds;
  }

  /**
   * Replaces shared people paragraphs of one node with duplicates.
   *
   * @param int $nodeId
   *   The node ID.
   * @param list<int> $targetIds
   *   The IDs of the shared paragraphs to replace.
   *
   * @return int
   *   The number of created duplicates.
   */
  public function unshareNode(int $nodeId, array $targetIds): int {
    /** @var \Drupal\node\NodeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('node');
    $node = $storage->load($nodeId);
    if (!$node instanceof NodeInterface) {
      return 0;
    }

    $revisionIds = array_unique([(int) $node->getRevisionId(), (int) $storage->getLatestRevisionId($nodeId)]);
    $duplicates = 0;
    foreach ($revisionIds as $revisionId) {
      $revision = $storage->loadRevision($revisionId);
      if ($revision instanceof NodeInterface) {
        $duplicates += $this->unshareRevision($revision, $targetIds);
      }
    }

    // Keep the memory use flat when many nodes are processed.
    $storage->resetCache([$nodeId]);
    $this->entityTypeManager->getStorage('paragraph')->resetCache($targetIds);
    return $duplicates;
  }

  /**
   * Maps the referenced paragraphs to the nodes that reference them.
   *
   * Only the default and the latest revision of each node count.
   *
   * @return array<int, array<int, true>>
   *   The node IDs as keys, keyed by paragraph ID.
   */
  private function nodesByTarget(): array {
    $table = 'node_revision__' . self::FIELD_NAME;
    if (!$this->database->schema()->tableExists($table)) {
      return [];
    }

    $latestRevisions = $this->database->select('node_revision', 'revision');
    $latestRevisions->addField('revision', 'nid');
    $latestRevisions->addExpression('MAX([revision].[vid])', 'vid');
    $latestRevisions->groupBy('revision.nid');

    $revisionIds = array_unique(array_merge(
      $this->database->select('node', 'node')->fields('node', ['vid'])->execute()?->fetchCol() ?? [],
      $latestRevisions->execute()?->fetchCol(1) ?? [],
    ));
    if ($revisionIds === []) {
      return [];
    }

    $nodesByTarget = [];
    foreach (array_chunk($revisionIds, 1000) as $chunk) {
      $references = $this->database->select($table, 'reference')
        ->fields('reference', ['entity_id', self::FIELD_NAME . '_target_id'])
        ->condition('reference.revision_id', $chunk, 'IN')
        ->distinct()
        ->execute()?->fetchAll() ?? [];
      foreach ($references as $reference) {
        $nodesByTarget[(int) $reference->{self::FIELD_NAME . '_target_id'}][(int) $reference->entity_id] = TRUE;
      }
    }

    return $nodesByTarget;
  }

  /**
   * Replaces shared people paragraphs in all translations of a node revision.
   *
   * @param \Drupal\node\NodeInterface $revision
   *   The node revision.
   * @param list<int> $targetIds
   *   The IDs of the shared paragraphs to replace.
   *
   * @return int
   *   The number of created duplicates.
   */
  private function unshareRevision(NodeInterface $revision, array $targetIds): int {
    if (!$revision->hasField(self::FIELD_NAME)) {
      return 0;
    }

    // One duplicate per referenced paragraph revision, shared by the
    // translations of this node revision.
    $duplicates = [];
    foreach (array_keys($revision->getTranslationLanguages()) as $langcode) {
      foreach ($revision->getTranslation($langcode)->get(self::FIELD_NAME) as $item) {
        $paragraph = $item->get('entity')->getValue();
        if (!$paragraph instanceof ParagraphInterface) {
          continue;
        }

        if (!in_array((int) $paragraph->id(), $targetIds, TRUE)) {
          continue;
        }

        $key = $paragraph->id() . ':' . $paragraph->getRevisionId();
        $duplicates[$key] ??= $paragraph->getUntranslated()->createDuplicate();
        $item->set('entity', $duplicates[$key]);
      }
    }

    if ($duplicates === []) {
      return 0;
    }

    // Changes neither the revision history nor the "changed" time.
    $revision->setNewRevision(FALSE);
    $revision->setSyncing(TRUE);
    $revision->save();
    return count($duplicates);
  }

}
