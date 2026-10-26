<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\entity_popover\ParagraphBackfiller;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks that a backfilled paragraph is visible to Paragraph::load().
 */
#[Group('openculturas')]
final class ParagraphBackfillerTest extends ExistingSiteBase {

  private const string FIELD_NAME = 'field_entity_popover';

  private const string BUNDLE = 'backfill_test';

  private const string TRANSLATION_BUNDLE = 'backfill_translation_test';

  /**
   * The backfill writes the default value despite a warm entity cache.
   */
  public function testBackfillRefreshesCachedParagraph(): void {
    $paragraphsType = ParagraphsType::create([
      'id' => self::BUNDLE,
      'label' => 'Backfill test',
    ]);
    $paragraphsType->save();

    $fieldStorage = FieldStorageConfig::loadByName('paragraph', self::FIELD_NAME);
    $this->assertNotNull($fieldStorage);

    $fieldConfig = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => self::BUNDLE,
      'label' => 'Enable popover',
      'default_value' => [['value' => 1]],
    ]);
    $fieldConfig->save();

    $paragraph = Paragraph::create([
      'type' => self::BUNDLE,
      self::FIELD_NAME => [],
    ]);
    $paragraph->save();

    // Cleaned up in this order: the paragraph still needs its field, the
    // field still needs its bundle.
    $this->markEntityForCleanup($paragraph);
    $this->markEntityForCleanup($fieldConfig);
    $this->markEntityForCleanup($paragraphsType);

    $identifier = (int) $paragraph->id();

    // Load the paragraph once so both the memory cache and the persistent
    // entity cache hold the empty field value.
    $cached = Paragraph::load($identifier);
    $this->assertNotNull($cached);
    $this->assertSame([], $cached->get(self::FIELD_NAME)->getValue());
    $this->assertNotFalse(\Drupal::cache('entity')->get('values:paragraph:' . $identifier));

    $updated = $this->container->get(ParagraphBackfiller::class)
      ->backfillBundle(self::BUNDLE, self::FIELD_NAME);
    $this->assertSame(1, $updated);

    $this->assertFalse(
      \Drupal::cache('entity')->get('values:paragraph:' . $identifier),
      'The backfill cleared the stale persistent entity cache entry.',
    );

    $reloaded = Paragraph::load($identifier);
    $this->assertNotNull($reloaded);
    $this->assertSame(1, (int) $reloaded->get(self::FIELD_NAME)->value);
  }

  /**
   * A non-translatable popover field is counted and written only once.
   *
   * Core stores a non-translatable field under the entity's default langcode
   * alone, so the translation rows of the base tables must neither be counted
   * as empty nor receive field rows of their own.
   *
   * Content translation is not enabled for any bundle on this site, but the
   * entity API adds a translation all the same, which is all this test needs.
   */
  public function testBackfillIgnoresTranslationsOfNonTranslatableField(): void {
    $paragraphsType = ParagraphsType::create([
      'id' => self::TRANSLATION_BUNDLE,
      'label' => 'Backfill translation test',
    ]);
    $paragraphsType->save();

    $fieldStorage = FieldStorageConfig::loadByName('paragraph', self::FIELD_NAME);
    $this->assertNotNull($fieldStorage);
    $this->assertFalse($fieldStorage->isTranslatable());

    $fieldConfig = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => self::TRANSLATION_BUNDLE,
      'label' => 'Enable popover',
      'default_value' => [['value' => 1]],
    ]);
    $fieldConfig->save();

    $languageManager = \Drupal::languageManager();
    $defaultLangcode = $languageManager->getDefaultLanguage()->getId();
    $translationLangcode = NULL;
    foreach ($languageManager->getLanguages() as $language) {
      if ($language->getId() !== $defaultLangcode && !$language->isLocked()) {
        $translationLangcode = $language->getId();
        break;
      }
    }
    $this->assertNotNull($translationLangcode, 'A second, unlocked language exists to translate into.');

    // An explicit 0 in the default language is a stored value, not a missing
    // one, however many translations the paragraph has.
    $setParagraph = Paragraph::create([
      'type' => self::TRANSLATION_BUNDLE,
      self::FIELD_NAME => [['value' => 0]],
    ]);
    $setParagraph->save();
    $setParagraph->addTranslation($translationLangcode, []);
    $setParagraph->save();

    // Both results are taken before the empty paragraph below joins the
    // bundle, so they describe this paragraph alone.
    $backfiller = $this->container->get(ParagraphBackfiller::class);
    $countsWithSetParagraphOnly = $backfiller->countEmptyByBundle([self::TRANSLATION_BUNDLE], self::FIELD_NAME);
    $updatedWithSetParagraphOnly = $backfiller->backfillBundle(self::TRANSLATION_BUNDLE, self::FIELD_NAME);

    $emptyParagraph = Paragraph::create([
      'type' => self::TRANSLATION_BUNDLE,
      self::FIELD_NAME => [],
    ]);
    $emptyParagraph->save();
    $emptyParagraph->addTranslation($translationLangcode, []);
    $emptyParagraph->save();

    // Cleaned up in this order: both paragraphs still need their field, the
    // field still needs its bundle.
    $this->markEntityForCleanup($setParagraph);
    $this->markEntityForCleanup($emptyParagraph);
    $this->markEntityForCleanup($fieldConfig);
    $this->markEntityForCleanup($paragraphsType);

    $this->assertSame(
      [],
      $countsWithSetParagraphOnly,
      'A paragraph whose default-language value is set is not counted as empty, translation or not.',
    );
    $this->assertSame(
      0,
      $updatedWithSetParagraphOnly,
      'A paragraph whose default-language value is set is not backfilled, translation or not.',
    );

    $this->assertSame(
      1,
      $backfiller->backfillBundle(self::TRANSLATION_BUNDLE, self::FIELD_NAME),
      'The paragraph without a value is backfilled exactly once.',
    );

    $emptyParagraphId = (int) $emptyParagraph->id();
    $paragraphStorage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $this->assertInstanceOf(SqlContentEntityStorage::class, $paragraphStorage);
    $tableMapping = $paragraphStorage->getTableMapping();
    $this->assertInstanceOf(DefaultTableMapping::class, $tableMapping);
    $dataFieldTable = $tableMapping->getDedicatedDataTableName($fieldStorage);
    $revisionFieldTable = $tableMapping->getDedicatedRevisionTableName($fieldStorage);

    $fieldLangcodes = [];
    foreach ([$dataFieldTable, $revisionFieldTable] as $tableName) {
      $statement = \Drupal::database()->select($tableName, 'field_rows')
        ->fields('field_rows', ['langcode'])
        ->condition('entity_id', $emptyParagraphId)
        ->execute();
      $this->assertNotNull($statement);
      $fieldLangcodes[$tableName] = $statement->fetchCol();
    }

    $this->assertSame(
      [$defaultLangcode],
      $fieldLangcodes[$dataFieldTable],
      'The data table holds exactly one row for the paragraph, under the default langcode.',
    );
    $this->assertSame(
      [],
      array_diff($fieldLangcodes[$revisionFieldTable], [$defaultLangcode]),
      'The revision table holds no row under a translation langcode.',
    );

    $reloaded = Paragraph::load($emptyParagraphId);
    $this->assertNotNull($reloaded);
    $this->assertSame(1, (int) $reloaded->getUntranslated()->get(self::FIELD_NAME)->value);
    $this->assertSame(
      1,
      (int) $reloaded->getTranslation($translationLangcode)->get(self::FIELD_NAME)->value,
      'The translation reads the value stored for the default language.',
    );
  }

}
