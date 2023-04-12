<?php

declare(strict_types = 1);

namespace Drupal\openculturas_faq;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeStorageInterface;

/**
 * Prevents uninstalling of openculturas_faq when faq content was created.
 */
class OpenCulturasFaqUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The node entity storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected NodeStorageInterface $nodeStorage;

  /**
   * Constructs a new OpenCulturasFrequentlyAskedQuestionsUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];
    if ($module !== 'openculturas_faq') {
      return $reasons;
    }
    if ($this->hasFaqNodes()) {
      $reasons[] = (string) $this->t('To uninstall OpenCulturas - Frequently Asked Questions, delete all content that has the Faq content type');
    }
    return $reasons;
  }

  /**
   * Determines if there is any faq nodes or not.
   *
   * @return bool
   *   TRUE if there are faq nodes, FALSE otherwise.
   */
  protected function hasFaqNodes(): bool {
    $nodes = $this->nodeStorage->getQuery()
      ->condition('type', 'faq')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return $nodes !== [];
  }

}
