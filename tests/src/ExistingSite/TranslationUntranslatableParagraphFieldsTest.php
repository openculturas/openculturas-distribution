<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\Tests\openculturas\Traits\UserTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use PHPUnit\Framework\Attributes\Group;

/**
 * Saves a translation of a node with untranslatable paragraph fields.
 *
 * The paragraphs widget hides untranslatable fields on the translation form.
 * A hidden widget must not write its value back, otherwise the untranslatable
 * fields constraint rejects the translation.
 */
#[Group('openculturas')]
final class TranslationUntranslatableParagraphFieldsTest extends ExistingSiteBase {

  use UserTrait;

  private const string LOCATION_VALUE = 'POINT(10.004077 51.705295)';

  /**
   * The original value of the geocoder presave setting.
   */
  private bool $geocoderPresaveDisabled = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Saving the address paragraph geocodes its address with an external
    // service. The test does not need the geocoding result.
    $configuration = \Drupal::configFactory()->getEditable('geocoder.settings');
    $this->geocoderPresaveDisabled = (bool) $configuration->get('geocoder_presave_disabled');
    $configuration->set('geocoder_presave_disabled', TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::configFactory()->getEditable('geocoder.settings')
      ->set('geocoder_presave_disabled', $this->geocoderPresaveDisabled)
      ->save();
    parent::tearDown();
  }

  /**
   * Adds a translation without a change to the untranslatable fields.
   */
  public function testAddTranslation(): void {
    $node_id = $this->createLocation()->id();
    $this->assertNotNull($node_id);
    $this->loginWithRole('administrator');

    $this->drupalGet(
      '/node/' . $node_id . '/translations/add/de/en',
      ['language' => \Drupal::languageManager()->getLanguage('en')],
    );
    $this->submitForm([], 'edit-submit');

    $this->assertSession()->pageTextNotContains('Non-translatable fields can only be changed when updating the original language.');
    $this->assertSession()->pageTextContains('has been updated.');

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $storage->resetCache([$node_id]);

    $revision_id = $storage->getLatestRevisionId($node_id);
    $this->assertNotNull($revision_id);
    $revision = $storage->loadRevision($revision_id);
    $this->assertInstanceOf(NodeInterface::class, $revision);
    $this->assertTrue($revision->hasTranslation('en'));
    $address = $revision->get('field_address_data')->entity;
    $this->assertInstanceOf(Paragraph::class, $address);
    $this->assertSame(self::LOCATION_VALUE, $address->get('field_address_location')->value);
  }

  /**
   * Creates a German location with address and contact paragraphs.
   */
  private function createLocation(): NodeInterface {
    $address = Paragraph::create([
      'type' => 'address_data',
      'langcode' => 'de',
      'field_address' => [
        'country_code' => 'DE',
        'locality' => 'Northeim',
        'postal_code' => '37154',
        'address_line1' => 'Obere Str. 1',
      ],
      'field_address_location' => self::LOCATION_VALUE,
      'field_location_precision' => 'auto',
    ]);
    $contact = Paragraph::create([
      'type' => 'contact_data',
      'langcode' => 'de',
      'field_social_media_links' => [
        'platform_values' => [
          'facebook' => ['value' => 'example'],
        ],
      ],
    ]);
    $node = Node::create([
      'type' => 'location',
      'langcode' => 'de',
      'title' => 'Translation test location ' . $this->randomMachineName(),
      'moderation_state' => 'published',
      'field_address_data' => $address,
      'field_contact_data' => $contact,
    ]);
    $node->save();

    // Delete the node before its paragraphs.
    $this->markEntityForCleanup($node);
    $this->markEntityForCleanup($address);
    $this->markEntityForCleanup($contact);
    return $node;
  }

}
