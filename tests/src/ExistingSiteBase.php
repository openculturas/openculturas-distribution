<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas;

use weitzman\DrupalTestTraits\ExistingSiteBase as OriginExistingSiteBase;
use weitzman\DrupalTestTraits\LoggerTrait;

abstract class ExistingSiteBase extends OriginExistingSiteBase {

  use LoggerTrait;

  /**
   * Can be used for link generating or drupalGet.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language_en;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();

    $this->language_en = $this->container->get('language_manager')->getLanguage('en');
  }

}
