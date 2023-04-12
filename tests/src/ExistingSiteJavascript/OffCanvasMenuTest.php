<?php

declare(strict_types = 1);

namespace Drupal\Tests\openculturas\ExistingSiteJavascript;

use Drupal\Tests\openculturas\ExistingSiteJSTestBase;

/**
 * @group openculturas
 */
class OffCanvasMenuTest extends ExistingSiteJSTestBase {

  public function testOpenAndClose(): void {
    $this->drupalGet('<front>');
    $session = $this->assertSession();
    $session->elementAttributeContains('css', '#offcanvas_menu', 'aria-hidden', 'true');
    $session->elementAttributeContains('css', '#button-offcanvas-open', 'aria-expanded', 'false');
    $session->elementAttributeContains('css', '#button-offcanvas-close', 'aria-expanded', 'false');
    $this->click('#button-offcanvas-open');
    $session->elementAttributeContains('css', '#offcanvas_menu', 'aria-hidden', 'false');
    $session->elementAttributeContains('css', '#button-offcanvas-open', 'aria-expanded', 'true');
    $session->elementAttributeContains('css', '#button-offcanvas-close', 'aria-expanded', 'true');
    $this->click('#button-offcanvas-close');
    $session->elementAttributeContains('css', '#offcanvas_menu', 'aria-hidden', 'true');
    $session->elementAttributeContains('css', '#button-offcanvas-open', 'aria-expanded', 'false');
    $session->elementAttributeContains('css', '#button-offcanvas-close', 'aria-expanded', 'false');
  }

}
