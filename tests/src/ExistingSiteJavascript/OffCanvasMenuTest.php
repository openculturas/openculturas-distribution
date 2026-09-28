<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSiteJavascript;

use Drupal\Tests\openculturas\ExistingSiteJSTestBase;
use PHPUnit\Framework\Attributes\Group;

#[Group('openculturas')]
class OffCanvasMenuTest extends ExistingSiteJSTestBase {

  public function testOpenAndClose(): void {
    $this->drupalGet('<front>');
    $session = $this->assertSession();
    // The opcult theme toggles the menu dialog via the native Popover API,
    // which keeps the open state in :popover-open instead of aria attributes.
    $session->elementAttributeContains('css', '#button-offcanvas-open', 'popovertarget', 'offcanvas_menu_dialog');
    $session->elementAttributeContains('css', '#button-offcanvas-close', 'popovertarget', 'offcanvas_menu_dialog');
    $this->assertFalse($this->isMenuOpen());

    $this->click('#button-offcanvas-open');
    $this->assertTrue($this->isMenuOpen());
    $closeButton = $this->getSession()->getPage()->find('css', '#offcanvas_menu_dialog #button-offcanvas-close');
    $this->assertNotNull($closeButton);
    $this->assertTrue($closeButton->isVisible());

    $this->click('#button-offcanvas-close');
    $this->assertFalse($this->isMenuOpen());
  }

  private function isMenuOpen(): bool {
    return (bool) $this->getSession()->evaluateScript("document.querySelector('#offcanvas_menu_dialog').matches(':popover-open')");
  }

}
