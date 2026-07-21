<?php

declare(strict_types=1);

namespace Drupal\openculturas_section\Hook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\openculturas_custom\CurrentEntityHelper;

/**
 * Hook implementations for openculturas_section.
 */
class OpenculturasSectionHooks {

  /**
   * Constructs a new OpenculturasSectionHooks.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(protected EntityRepositoryInterface $entityRepository) {
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];
    $node = CurrentEntityHelper::getEventReference($node);
    // Adds the colorcode to node template attributes as css class.
    if ($node instanceof ContentEntityInterface && $node->hasField('field_section') && !$node->get('field_section')->isEmpty()) {
      /** @var \Drupal\taxonomy\TermInterface[] $entities */
      $entities = $node->get('field_section')->referencedEntities();
      foreach ($entities as $delta => $entity) {
        $translated_entity = $this->entityRepository->getTranslationFromContext($entity);
        if ($translated_entity->hasField('field_colorcode') && !$translated_entity->get('field_colorcode')->isEmpty()) {
          $variables['attributes']['class'][] = $translated_entity->get('field_colorcode')->value;
          // In case the field itself is also printed, add a data attribute,
          // which can be used for styling per value.
          if (isset($variables['content']['field_section'][$delta])) {
            $variables['content']['field_section'][$delta]['#attributes']['data-field_colorcode'] = $translated_entity->get('field_colorcode')->value;
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_taxonomy_term')]
  public function preprocessTaxonomyTerm(array &$variables): void {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $variables['term'];
    if ($term->hasField('field_colorcode') && !$term->get('field_colorcode')->isEmpty()) {
      $variables['attributes']['class'][] = $term->get('field_colorcode')->value;
    }
  }

}
