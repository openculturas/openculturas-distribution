<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\openculturas_custom\PeopleParagraphUnsharer;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Every node gets its own copy of a shared people paragraph.
 *
 * The test unshares only its own nodes, so it does not change other content
 * of the site.
 *
 * @see \Drupal\openculturas_custom\PeopleParagraphUnsharer
 */
#[Group('openculturas')]
final class UnsharePeopleParagraphsTest extends ExistingSiteBase {

  public function testSharedParagraphIsDuplicated(): void {
    $member = Paragraph::create([
      'type' => 'member',
      'field_member_function' => 'Test function ' . $this->randomMachineName(),
    ]);
    $event = Node::create([
      'type' => 'event',
      'title' => 'Unshare people test ' . $this->randomMachineName(),
      'field_people_reference' => $member,
    ]);
    $event->save();
    // Delete the nodes before the paragraphs.
    $this->markEntityForCleanup($event);
    $member_id = (int) $member->id();
    $member_revision_id = (int) $member->getRevisionId();

    // Reference the event's paragraph, as a clone of it did.
    $date = Node::create([
      'type' => 'date',
      'title' => 'Unshare people test ' . $this->randomMachineName(),
      'field_people_reference' => [
        'target_id' => $member_id,
        'target_revision_id' => $member_revision_id,
      ],
    ]);
    $date->save();
    $this->markEntityForCleanup($date);
    $this->markEntityForCleanup($member);
    $date_id = (int) $date->id();
    $event_id = (int) $event->id();

    // The date save moved the paragraph to the date. Make the event its parent
    // again, as before the date was created.
    $storage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $storage->resetCache([$member_id]);
    $shared = $storage->load($member_id);
    $this->assertInstanceOf(ParagraphInterface::class, $shared);
    $shared->setParentEntity($event, 'field_people_reference');
    $shared->setNewRevision(FALSE);
    $shared->save();

    $unsharer = \Drupal::service(PeopleParagraphUnsharer::class);
    $node_ids = $unsharer->sharedReferences();
    $this->assertSame([$member_id => $event_id], $node_ids[$date_id] ?? NULL, 'The date references the shared paragraph of the event, which keeps it.');
    $this->assertArrayNotHasKey($event_id, $node_ids, 'The event is the parent of the paragraph and keeps it.');

    $this->assertSame(1, $unsharer->unshareNode($date_id, [$member_id]));

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache([$date_id, $event_id]);
    $date = $node_storage->load($date_id);
    $event = $node_storage->load($event_id);
    $this->assertInstanceOf(NodeInterface::class, $date);
    $this->assertInstanceOf(NodeInterface::class, $event);

    $copy_id = (int) $date->get('field_people_reference')->target_id;
    $this->assertNotSame($member_id, $copy_id);
    $this->assertSame($member_id, (int) $event->get('field_people_reference')->target_id);

    $storage->resetCache([$member_id, $copy_id]);
    $copy = $storage->load($copy_id);
    $this->assertInstanceOf(ParagraphInterface::class, $copy);
    $this->markEntityForCleanup($copy);
    $this->assertSame((string) $date_id, (string) $copy->get('parent_id')->value);
    $this->assertSame($shared->get('field_member_function')->value, $copy->get('field_member_function')->value);

    $original = $storage->load($member_id);
    $this->assertInstanceOf(ParagraphInterface::class, $original);
    $this->assertSame((string) $event_id, (string) $original->get('parent_id')->value);

    $this->assertArrayNotHasKey($date_id, $unsharer->sharedReferences());
  }

}
