<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas_responsive_images\ExistingSite;

use Drupal\crop\Entity\Crop;
use Drupal\crop\Entity\CropType;
use Drupal\openculturas_responsive_images\CropMigrator;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests which crop types the crop migrator lists as migratable.
 */
final class CropMigratorTest extends ExistingSiteBase {

  /**
   * The crop migrator under test.
   */
  private CropMigrator $cropMigrator;

  /**
   * A file URI unique to this test run.
   */
  private string $uri;

  /**
   * The machine names of the crop types this test created.
   *
   * @var list<string>
   */
  private array $createdCropTypeIds = [];

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();
    $this->cropMigrator = $this->container->get(CropMigrator::class);
    $this->uri = 'public://crop-migrator-test-' . bin2hex(random_bytes(6)) . '.jpg';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function tearDown(): void {
    $state = $this->container->get('state');
    /** @var array<string, list<string>> $mappings */
    $mappings = $state->get(CropMigrator::MAPPINGS_STATE_KEY, []);
    $state->set(CropMigrator::MAPPINGS_STATE_KEY, array_diff_key($mappings, array_flip($this->createdCropTypeIds)));
    parent::tearDown();
  }

  /**
   * Crop types without an aspect ratio are never listed.
   */
  public function testCropTypeWithoutAspectRatioIsNotListed(): void {
    $cropType = $this->createCropType('');
    $sourceId = (string) $cropType->id();
    $this->createCrop($sourceId);

    $this->assertArrayNotHasKey($sourceId, $this->cropMigrator->migratableSourceTypes());
  }

  /**
   * A source crop without an oc_* duplicate is listed until it is migrated.
   */
  public function testCropWithoutDuplicateIsListedUntilMigrated(): void {
    $cropType = $this->createCropType('16:9');
    $sourceId = (string) $cropType->id();
    $crop = $this->createCrop($sourceId);

    $this->assertArrayHasKey($sourceId, $this->cropMigrator->migratableSourceTypes());

    $this->assertSame(1, $this->cropMigrator->migrate($sourceId, [(string) $crop->id()], 'oc_16_9'));
    $duplicate = Crop::findCrop($this->uri, 'oc_16_9');
    $this->assertNotNull($duplicate);
    $this->markEntityForCleanup($duplicate);

    $this->assertArrayNotHasKey($sourceId, $this->cropMigrator->migratableSourceTypes());
  }

  /**
   * A mismatched migration trims the crop around its center.
   */
  public function testMismatchedMigrationFitsCropToTargetRatio(): void {
    $cropType = $this->createCropType('16:9');
    $sourceId = (string) $cropType->id();
    $crop = $this->createCrop($sourceId, 160, 90);

    $this->assertSame(1, $this->cropMigrator->migrate($sourceId, [(string) $crop->id()], 'oc_1_1'));
    $duplicate = Crop::findCrop($this->uri, 'oc_1_1');
    $this->assertNotNull($duplicate);
    $this->markEntityForCleanup($duplicate);

    $this->assertSame('10', (string) $duplicate->get('x')->value);
    $this->assertSame('10', (string) $duplicate->get('y')->value);
    $this->assertSame('90', (string) $duplicate->get('width')->value);
    $this->assertSame('90', (string) $duplicate->get('height')->value);
  }

  /**
   * A same-ratio migration copies the coordinates unchanged.
   */
  public function testSameRatioMigrationKeepsCoordinates(): void {
    $cropType = $this->createCropType('16:9');
    $sourceId = (string) $cropType->id();
    $crop = $this->createCrop($sourceId, 160, 90);

    $this->cropMigrator->migrate($sourceId, [(string) $crop->id()], 'oc_16_9');
    $duplicate = Crop::findCrop($this->uri, 'oc_16_9');
    $this->assertNotNull($duplicate);
    $this->markEntityForCleanup($duplicate);

    $this->assertSame('160', (string) $duplicate->get('width')->value);
    $this->assertSame('90', (string) $duplicate->get('height')->value);
  }

  /**
   * A source migrated onto a different ratio is no longer listed.
   */
  public function testSourceMigratedOntoDifferentRatioIsNotListed(): void {
    $cropType = $this->createCropType('16:9');
    $sourceId = (string) $cropType->id();
    $crop = $this->createCrop($sourceId, 160, 90);

    $this->assertArrayHasKey($sourceId, $this->cropMigrator->migratableSourceTypes());

    $this->cropMigrator->migrate($sourceId, [(string) $crop->id()], 'oc_1_1');
    $duplicate = Crop::findCrop($this->uri, 'oc_1_1');
    $this->assertNotNull($duplicate);
    $this->markEntityForCleanup($duplicate);

    $this->assertArrayNotHasKey($sourceId, $this->cropMigrator->migratableSourceTypes());
  }

  /**
   * Only crops without a crop of the target type are listed and migrated.
   */
  public function testCropIdsSkipsAlreadyMigratedCrops(): void {
    $cropType = $this->createCropType('16:9');
    $sourceId = (string) $cropType->id();
    $this->createCrop($sourceId);
    $openCrop = $this->createCrop($sourceId, 16, 9, $this->uri . '-open');

    // Migrate a crop of a third file, so the type holds one migrated crop and
    // two crops that are still open.
    $migratedUri = $this->uri . '-migrated';
    $migratedCrop = $this->createCrop($sourceId, 16, 9, $migratedUri);
    $this->assertSame(1, $this->cropMigrator->migrate($sourceId, [(string) $migratedCrop->id()], 'oc_16_9'));
    $duplicate = Crop::findCrop($migratedUri, 'oc_16_9');
    $this->assertNotNull($duplicate);
    $this->markEntityForCleanup($duplicate);

    $openCropIds = array_map(strval(...), $this->cropMigrator->cropIds($sourceId, 'oc_16_9'));
    $this->assertCount(2, $openCropIds);
    $this->assertNotContains((string) $migratedCrop->id(), $openCropIds);
    $this->assertContains((string) $openCrop->id(), $openCropIds);

    $this->assertSame(2, $this->cropMigrator->migrate($sourceId, $openCropIds, 'oc_16_9'));
    $this->assertSame([], $this->cropMigrator->cropIds($sourceId, 'oc_16_9'));

    foreach ([$this->uri, $this->uri . '-open'] as $uri) {
      $newDuplicate = Crop::findCrop($uri, 'oc_16_9');
      $this->assertNotNull($newDuplicate);
      $this->markEntityForCleanup($newDuplicate);
    }
  }

  /**
   * A source whose files all have a crop of the target is remembered.
   *
   * The IDs to migrate are empty in this case, so only the mapping itself can
   * stop the source from staying listed.
   */
  public function testSourceWithoutOpenCropsIsStillRemembered(): void {
    $cropType = $this->createCropType('2:1');
    $sourceId = (string) $cropType->id();
    $this->createCrop($sourceId, 160, 90);

    // The target crop comes from another source, so the pair is not stored yet.
    $otherSourceId = (string) $this->createCropType('16:9')->id();
    $this->createCrop($otherSourceId);
    $this->assertSame(1, $this->cropMigrator->migrate($otherSourceId, $this->cropMigrator->cropIds($otherSourceId, 'oc_16_9'), 'oc_16_9'));
    $duplicate = Crop::findCrop($this->uri, 'oc_16_9');
    $this->assertNotNull($duplicate);
    $this->markEntityForCleanup($duplicate);

    $this->assertSame([], $this->cropMigrator->cropIds($sourceId, 'oc_16_9'));
    $this->assertArrayHasKey($sourceId, $this->cropMigrator->migratableSourceTypes());

    $this->assertSame(0, $this->cropMigrator->migrate($sourceId, $this->cropMigrator->cropIds($sourceId, 'oc_16_9'), 'oc_16_9'));

    $this->assertArrayNotHasKey($sourceId, $this->cropMigrator->migratableSourceTypes());
  }

  /**
   * Creates a throwaway crop type with the given aspect ratio.
   */
  private function createCropType(string $aspectRatio): CropType {
    $cropType = CropType::create([
      'id' => 'crop_migrator_test_' . bin2hex(random_bytes(4)),
      'label' => 'Crop migrator test',
      'aspect_ratio' => $aspectRatio,
    ]);
    $cropType->save();
    $this->createdCropTypeIds[] = (string) $cropType->id();
    $this->markEntityForCleanup($cropType);
    return $cropType;
  }

  /**
   * Creates a crop of the given type for this test's unique file URI.
   *
   * @param string $type
   *   The crop type machine name.
   * @param int $width
   *   The crop width.
   * @param int $height
   *   The crop height.
   * @param string $uri
   *   The file URI of the crop. Several crops of one crop type need distinct
   *   URIs, since a migration works per file URI.
   */
  private function createCrop(string $type, int $width = 16, int $height = 9, string $uri = ''): Crop {
    $crop = Crop::create([
      'type' => $type,
      'entity_id' => 1,
      'entity_type' => 'file',
      'uri' => $uri === '' ? $this->uri : $uri,
      'x' => 10,
      'y' => 10,
      'width' => $width,
      'height' => $height,
    ]);
    $crop->save();
    $this->markEntityForCleanup($crop);
    return $crop;
  }

}
