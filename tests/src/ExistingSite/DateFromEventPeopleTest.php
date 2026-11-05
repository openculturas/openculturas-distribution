<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Core\Form\FormState;
use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

/**
 * A date created from an event gets its own copies of the event's people.
 *
 * The date and the event must not share a paragraph. A shared paragraph gets
 * a new revision and parent on every save of either node.
 *
 * @see \Drupal\openculturas_custom\Hook\OpenculturasCustomDateEventHooks::nodePrepareForm()
 */
#[Group('openculturas')]
final class DateFromEventPeopleTest extends ExistingSiteBase {

  public function testPeopleAreDuplicated(): void {
    $member = Paragraph::create([
      'type' => 'member',
      'field_member_function' => 'Test function ' . $this->randomMachineName(),
    ]);
    $event = Node::create([
      'type' => 'event',
      'title' => 'Date from event test ' . $this->randomMachineName(),
      'field_people_reference' => $member,
    ]);
    $event->save();
    // Delete the node before its paragraph.
    $this->markEntityForCleanup($event);
    $this->markEntityForCleanup($member);

    $date = Node::create([
      'type' => 'date',
      'title' => 'Date from event test ' . $this->randomMachineName(),
    ]);
    $this->prepareDateForm($date, $event);

    $copy = $date->get('field_people_reference')->entity;
    $this->assertInstanceOf(ParagraphInterface::class, $copy);
    $this->assertTrue($copy->isNew());
    $this->assertNotSame($member->uuid(), $copy->uuid());
    $this->assertSame($member->get('field_member_function')->value, $copy->get('field_member_function')->value);

    $date->save();
    $this->markEntityForCleanup($date);

    // Load both paragraphs from storage, because saving the date can replace
    // the paragraph object in the field.
    $member_id = $member->id();
    $this->assertNotNull($member_id);
    $copy_id = $date->get('field_people_reference')->target_id;
    $this->assertNotEquals($member_id, $copy_id);
    $storage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $storage->resetCache([$member_id, $copy_id]);

    $stored_copy = $storage->load($copy_id);
    $this->assertInstanceOf(ParagraphInterface::class, $stored_copy);
    $this->markEntityForCleanup($stored_copy);
    $this->assertSame((string) $date->id(), (string) $stored_copy->get('parent_id')->value);

    $original = $storage->load($member_id);
    $this->assertInstanceOf(ParagraphInterface::class, $original);
    $this->assertSame((string) $event->id(), (string) $original->get('parent_id')->value);
  }

  /**
   * Runs hook_node_prepare_form() for a date created from the given event.
   */
  private function prepareDateForm(NodeInterface $date, NodeInterface $event): void {
    $request_stack = \Drupal::requestStack();
    $request_stack->push(Request::create('/node/add/date', 'GET', ['event_id' => $event->id()]));
    try {
      \Drupal::moduleHandler()->invokeAll('node_prepare_form', [$date, 'default', new FormState()]);
    }
    finally {
      $request_stack->pop();
    }
  }

}
