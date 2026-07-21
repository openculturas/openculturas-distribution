<?php

declare(strict_types=1);

namespace Drupal\openculturas_faq\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Hook implementations for openculturas_faq.
 */
class OpenculturasFaqHooks {

  /**
   * Constructs a new OpenculturasFaqHooks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * Implements hook_ENTITY_TYPE_prepare_form().
   */
  #[Hook('node_prepare_form')]
  public function nodePrepareForm(NodeInterface $node): void {
    $is_new_entity = $node->bundle() === 'faq' && $node->isNew();
    if ($is_new_entity === FALSE) {
      return;
    }

    $request = \Drupal::request();
    if ($request->query->has('field_faq_category')) {
      $faq_category_id = $request->query->get('field_faq_category');
      if (empty($faq_category_id)) {
        return;
      }

      $faq_category = $this->entityTypeManager->getStorage('taxonomy_term')->load($faq_category_id);
      if (!$faq_category instanceof TermInterface) {
        return;
      }

      if ($faq_category->bundle() !== 'faq_category') {
        return;
      }

      $node->set('field_faq_category', [
        'target_id' => $faq_category->id(),
      ]);
    }
  }

}
