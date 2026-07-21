<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\openculturas_custom\Entity\ExtendedParagraph;
use Drupal\user\UserInterface;

/**
 * Entity lifecycle hook implementations for openculturas_custom.
 */
class OpenculturasCustomNodeLifecycleHooks {

  /**
   * Constructs a new OpenculturasCustomNodeLifecycleHooks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node): void {
    $owner_id = $node->getOwner()->id();
    $tags = [
      'entity_view_user_' . $owner_id . '_full',
    ];
    if ($node->hasField('field_event_series') && $node->hasField('field_parent_date')) {
      $field = $node->get('field_event_series');
      /** @var \Drupal\Core\Field\FieldItemInterface|null $field_item */
      $field_item = $field->first();
      if ($field->isEmpty() || $field_item instanceof FieldItemInterface && $field_item->getString() === 'no') {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
        $field = $node->get('field_parent_date');
        if (!$field->isEmpty()) {
          $field->setValue([]);
        }
      }
    }

    $node_reference_fields = [
      'field_location',
      'field_references',
    ];
    foreach ($node_reference_fields as $field_name) {
      if ($node->hasField($field_name)) {
        $refs = $node->get($field_name)->getValue();
        if (is_array($refs)) {
          foreach ($refs as $ref) {
            if (!isset($ref['target_id'])) {
              continue;
            }

            $tags[] = 'node:' . $ref['target_id'];
          }
        }
      }

      // If the field changes, also invalidate the original value.
      if ($node->getOriginal() instanceof NodeInterface && $node->getOriginal()->hasField($field_name)) {
        $refs = $node->get($field_name)->getValue();
        if (is_array($refs)) {
          foreach ($refs as $ref) {
            if (!isset($ref['target_id'])) {
              continue;
            }

            $tags[] = 'node:' . $ref['target_id'];
          }
        }
      }
    }

    Cache::invalidateTags(array_unique($tags));
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter().
   *
   * @see self::nodePresave()
   * @see \Drupal\openculturas_custom\Hook\OpenculturasCustomFlagHooks::flaggingInsert()
   */
  #[Hook('user_view_alter')]
  public function userViewAlter(array &$build, UserInterface $user): void {
    if ($build['#view_mode'] === 'full') {
      // Used to clear cache of user page.
      $build['#cache']['tags'][] = 'entity_view_user_' . $user->id() . '_full';
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    if (isset($entity_types['paragraph']) && $entity_types['paragraph'] instanceof EntityTypeInterface) {
      $entity_types['paragraph']->setClass(ExtendedParagraph::class);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('eca_delete')]
  public function ecaDelete(): void {
    $entities = $this->entityTypeManager->getStorage('eca')->loadMultiple();
    if ($entities === []) {
      $storage = $this->entityTypeManager->getStorage('eca_notification_recipient');
      $entities = $storage->loadMultiple();
      $storage->delete($entities);
    }
  }

}
