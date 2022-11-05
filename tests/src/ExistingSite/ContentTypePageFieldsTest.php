<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\Tests\openculturas\Traits\UserTrait;

/**
 * @group openculturas
 */
class ContentTypePageFieldsTest extends ExistingSiteBase {

  use UserTrait;

  public function testFieldsExistsForEditor(): void {
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(403);
    $this->loginWithRole('magazine_editor');
    // English.
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $session = $this->assertSession();
    $session->fieldExists('Language');
    $session->fieldExists('Title');
    $session->fieldExists('Subtitle');
    $session->buttonExists('Add media');
    $session->fieldExists('Body');
    $session->buttonExists('Add Content element');

    // German.
    $this->drupalGet('/de/node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $session = $this->assertSession();
    $session->fieldExists('Sprache');
    $session->fieldExists('Title');
    $session->fieldExists('Untertitel');
    $session->buttonExists('Medien hinzufügen');
    $session->fieldExists('Textkörper');
    $session->buttonExists('Content element hinzufügen');
  }

}
