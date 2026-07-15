<?php

declare(strict_types=1);

namespace Drupal\openculturas_faq;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Prevents uninstalling of openculturas_faq when faq content was created.
 */
class OpenCulturasFaqUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new OpenCulturasFrequentlyAskedQuestionsUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, TranslationInterface $stringTranslation) {
    $this->setStringTranslation($stringTranslation);
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
    $nodes = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'faq')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return $nodes !== [];
  }

}
