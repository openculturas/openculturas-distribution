<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_popover\ExistingSite;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Checks that popover dialog ids stay unique across render cache hits.
 *
 * Reproduces the collision the deterministic PopoverId format fixes: node A
 * renders cold and stores its dialog id in the render cache, a new request
 * starts with an empty Html::getUniqueId() counter, node B (cache miss)
 * builds first and takes the first id, and node A (cache hit) replays its
 * stored id — with Html::getUniqueId() both dialogs then share one id in
 * the same response.
 */
final class PopoverDialogIdCollisionTest extends ExistingSiteBase {

  private const string FIELD_NAME = 'field_popover_id_collision_test';

  private const string BUNDLE = 'faq';

  private const string HOST_VIEW_MODE = 'teaser';

  /**
   * The two dialog ids stay distinct when a render cache hit replays one.
   */
  public function testDialogIdsStayUniqueWhenRenderCacheReplaysOne(): void {
    $this->createPopoverField();

    $target = $this->createNode([
      'type' => self::BUNDLE,
      'title' => $this->randomMachineName(),
    ]);
    $nodeA = $this->createNode([
      'type' => self::BUNDLE,
      'title' => $this->randomMachineName(),
      self::FIELD_NAME => $target->id(),
    ]);
    $nodeB = $this->createNode([
      'type' => self::BUNDLE,
      'title' => $this->randomMachineName(),
      self::FIELD_NAME => $target->id(),
    ]);

    $firstRenderOfA = $this->renderEntity($nodeA);
    $storedIds = $this->extractDialogIds($firstRenderOfA);
    $this->assertCount(1, $storedIds, 'The cold render of node A produces one dialog id.');

    // A new request starts with an empty Html::getUniqueId() counter, while
    // node A's id lives on in the render cache.
    Html::resetSeenIds();

    // Node B is a cache miss and builds first, node A builds second and its
    // cache hit replays the stored id.
    $htmlOfB = $this->renderEntity($nodeB);
    $htmlOfAAgain = $this->renderEntity($nodeA);

    $this->assertSame(
      $storedIds,
      $this->extractDialogIds($htmlOfAAgain),
      'The cache hit replays the id stored with node A.',
    );

    $combinedIds = $this->extractDialogIds($htmlOfB . $htmlOfAAgain);
    $this->assertCount(2, $combinedIds, 'Each node renders one dialog.');
    $this->assertCount(
      2,
      array_unique($combinedIds),
      'The dialogs do not share an id when a cached id is replayed.',
    );

    foreach ([$htmlOfB, $htmlOfAAgain] as $html) {
      [$dialogId] = $this->extractDialogIds($html);
      $this->assertStringContainsString('popovertarget="' . $dialogId . '"', $html, 'The trigger points at the dialog.');
      $this->assertStringContainsString('<dialog id="' . $dialogId . '"', $html, 'The dialog carries the id.');
    }
  }

  /**
   * Unsaved entities get distinct dialog ids, saved ones keep their id.
   *
   * Unsaved entities have no id, so the dialog id falls back to the UUID.
   * Without it, two unsaved entities of one bundle, such as two paragraphs
   * added in a node preview, would share one dialog id.
   */
  public function testUnsavedEntitiesGetDistinctDialogIds(): void {
    $this->createPopoverField();

    $target = $this->createNode([
      'type' => self::BUNDLE,
      'title' => $this->randomMachineName(),
    ]);
    $savedNode = $this->createNode([
      'type' => self::BUNDLE,
      'title' => $this->randomMachineName(),
      self::FIELD_NAME => $target->id(),
    ]);

    // These nodes are never saved, so they need no cleanup.
    $unsavedNodes = [];
    foreach ([1, 2] as $number) {
      $unsavedNodes[$number] = Node::create([
        'type' => self::BUNDLE,
        'title' => $this->randomMachineName(),
        self::FIELD_NAME => $target->id(),
      ]);
    }

    $unsavedIds = [];
    foreach ($unsavedNodes as $number => $unsavedNode) {
      $this->assertTrue($unsavedNode->isNew(), 'The node is unsaved.');
      $html = $this->renderEntity($unsavedNode);
      $dialogIds = $this->extractDialogIds($html);
      $this->assertCount(1, $dialogIds, 'Unsaved node ' . $number . ' renders one dialog.');
      $this->assertStringContainsString(
        Html::getId((string) $unsavedNode->uuid()),
        $dialogIds[0],
        'The dialog id of unsaved node ' . $number . ' contains its UUID.',
      );
      $this->assertStringContainsString('popovertarget="' . $dialogIds[0] . '"', $html, 'The trigger points at the dialog.');
      $unsavedIds[] = $dialogIds[0];
    }

    $this->assertNotSame($unsavedIds[0], $unsavedIds[1], 'Two unsaved nodes do not share a dialog id.');

    $expectedSavedId = 'entity-popover-node-' . $savedNode->id() . '-'
      . Html::getId(self::FIELD_NAME) . '-' . self::HOST_VIEW_MODE . '-'
      . $savedNode->language()->getId();
    $this->assertSame(
      [$expectedSavedId],
      $this->extractDialogIds($this->renderEntity($savedNode)),
      'A saved node keeps the dialog id built from its numeric id.',
    );
  }

  /**
   * Adds the popover-enabled test field to the bundle and its host display.
   */
  private function createPopoverField(): void {
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => self::FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
    ]);
    $fieldStorage->save();
    // Deleting the storage also deletes the field and its display component.
    $this->markEntityForCleanup($fieldStorage);

    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => self::BUNDLE,
      'label' => 'Popover collision test',
    ])->save();

    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', self::BUNDLE, self::HOST_VIEW_MODE)
      ->setComponent(self::FIELD_NAME, [
        'type' => 'entity_reference_label',
        'label' => 'hidden',
        'settings' => ['link' => FALSE],
        'third_party_settings' => [
          'entity_popover_swiffy_slider' => [
            'enable' => TRUE,
            'details' => [
              'formatter' => 'entity_reference_label',
              'formatter_settings' => ['link' => FALSE],
            ],
          ],
        ],
      ])
      ->save();
  }

  /**
   * Renders an entity in the host view mode.
   */
  private function renderEntity(EntityInterface $entity): string {
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, self::HOST_VIEW_MODE);

    return (string) \Drupal::service('renderer')->renderInIsolation($build);
  }

  /**
   * Returns the dialog ids used in the given markup.
   *
   * @return string[]
   *   The value of every id attribute that starts with "entity-popover".
   */
  private function extractDialogIds(string $html): array {
    preg_match_all('/\bid="(entity-popover[^"]*)"/', $html, $matches);

    return $matches[1];
  }

}
