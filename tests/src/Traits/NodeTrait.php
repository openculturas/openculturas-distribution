<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Traits;

use Drupal\node\NodeInterface;

/**
 * Trait for node related functions.
 */
trait NodeTrait {

  protected function visitNodeByTitle(string $title): void {
    $this->visitNodeByOpAndTitle('view', $title);
  }

  protected function openNodeEditFormByTitle(string $title): void {
    $this->visitNodeByOpAndTitle('edit', $title);
  }

  protected function openNodeDeleteFormByTitle(string $title): void {
    $this->visitNodeByOpAndTitle('delete', $title);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function visitNodeByOpAndTitle(string $op, string $title): void {
    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage('node');

    $entities = $storage->loadByProperties([
      'title' => $title,
    ]);

    $count = count($entities);
    if ($count < 1) {
      throw new \InvalidArgumentException(sprintf('A node with title %s does not exits.', $title));
    }

    if ($count > 1) {
      throw new \InvalidArgumentException(sprintf('There are more than %s nodes with title %s.', $count, $title));
    }

    $entity = \reset($entities);

    if (!$entity instanceof NodeInterface) {
      throw new \Exception('Could not retrieve node entity by provided title.');
    }

    if ($op === 'view') {
      $this->visit($entity->toUrl()->toString());
    }
    elseif ($op === 'edit') {
      $this->visit($entity->toUrl('edit-form')->toString());
    }
    elseif ($op === 'delete') {
      $this->visit($entity->toUrl('delete-form')->toString());
    }
    else {
      throw new \InvalidArgumentException(sprintf('The given %s op is not a valid value. (Valid: view, edit or delete)', $op));
    }
  }

}
