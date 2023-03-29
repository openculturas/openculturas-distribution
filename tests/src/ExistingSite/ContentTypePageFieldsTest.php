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
    $session->fieldExists('Title');
    $session->fieldExists('Subtitle');
    $session->elementTextEquals('css', '#field_mood_image-media-library-wrapper > legend:nth-child(1) > span:nth-child(1)', 'Main image');
    $session->elementExists('css', '#edit-field-mood-image-open-button');
    // $session->fieldValueEquals('input#edit-field-mood-image-open-button', 'Add media');
    $session->fieldExists('Summary');
    $session->fieldExists('Body');
    $session->pageTextContainsOnce('Content elements');
    $session->buttonExists('Add Block');
    $session->buttonExists('Add Gallery');
    $session->buttonExists('Add Text');
    $session->buttonExists('Add View');
    $session->elementTextEquals('css', '#field_supporters-media-library-wrapper > legend > span', 'Supported by');
    $session->fieldExists('Layout switch');
    $session->fieldExists('Language');

    return;
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
