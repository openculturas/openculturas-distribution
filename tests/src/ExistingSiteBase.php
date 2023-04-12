<?php

declare(strict_types = 1);

namespace Drupal\Tests\openculturas;

use weitzman\DrupalTestTraits\ExistingSiteBase as OriginExistingSiteBase;

abstract class ExistingSiteBase extends OriginExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();
  }

}
