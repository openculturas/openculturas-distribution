<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas;

use weitzman\DrupalTestTraits\ExistingSiteSelenium2DriverTestBase as OriginExistingSiteSelenium2DriverTestBase;
use weitzman\DrupalTestTraits\LoggerTrait;

abstract class ExistingSiteJSTestBase extends OriginExistingSiteSelenium2DriverTestBase {
  use LoggerTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();
  }

}
