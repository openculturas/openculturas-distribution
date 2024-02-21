<?php

declare(strict_types=1);

namespace Drupal\openculturas_discussions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeStorageInterface;

/**
 * Prevents uninstalling of openculturas_discussions when comment content was created.
 */
final class OpenCulturasDiscussionsUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The node entity storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected NodeStorageInterface $nodeStorage;

  /**
   * Constructs a new OpenCulturasDiscussionsUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TranslationInterface $translation) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];
    if ($module !== 'openculturas_discussions') {
      return $reasons;
    }

    if ($this->hasCommentNodes()) {
      $reasons[] = (string) $this->t('To uninstall OpenCulturas - Discussions, delete all content that has the comment content type');
    }

    return $reasons;
  }

  /**
   * Determines if there is any comment nodes or not.
   *
   * @return bool
   *   TRUE if there are comment nodes, FALSE otherwise.
   */
  protected function hasCommentNodes(): bool {
    $nodes = $this->nodeStorage->getQuery()
      ->condition('type', 'comment')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return $nodes !== [];
  }

}
