<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas_responsive_images\ExistingSite;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\openculturas_responsive_images\SharedConfigSwitcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests which switched components SharedConfigSwitcher treats as deviating.
 *
 * Nothing is saved: entities are built from the profile's config/install or
 * loaded from the site, and only changed in memory.
 */
#[Group('openculturas_responsive_images')]
final class SharedConfigSwitcherDeviationTest extends ExistingSiteBase {

  /**
   * Config entities with a switched component, keyed by config name.
   *
   * Metatag defaults and crop types are left out: their switches only touch
   * this module's own token or suffix, so they never deviate.
   *
   * @var array<string, array{0: string, 1: string}>
   *   Entity type ID and entity ID.
   */
  private const array SWITCHED_CONFIG = [
    'core.entity_view_display.media.image.header_image' => ['entity_view_display', 'media.image.header_image'],
    'core.entity_view_display.media.image.teaser_image' => ['entity_view_display', 'media.image.teaser_image'],
    'core.entity_view_display.media.image.teaser_image_big' => ['entity_view_display', 'media.image.teaser_image_big'],
    'core.entity_view_display.media.user_profile_picture.default' => ['entity_view_display', 'media.user_profile_picture.default'],
    'core.entity_view_display.media.user_profile_picture.profile_image' => ['entity_view_display', 'media.user_profile_picture.profile_image'],
    'core.entity_view_display.media.image.gallery' => ['entity_view_display', 'media.image.gallery'],
    'core.entity_view_display.media.image.content_asset' => ['entity_view_display', 'media.image.content_asset'],
    'core.entity_view_display.media.image.gallery_big' => ['entity_view_display', 'media.image.gallery_big'],
    'core.entity_view_display.media.image.token' => ['entity_view_display', 'media.image.token'],
    'core.entity_view_display.paragraph.gallery.default' => ['entity_view_display', 'paragraph.gallery.default'],
    'core.entity_view_display.paragraph.gallery.slider' => ['entity_view_display', 'paragraph.gallery.slider'],
    'core.entity_form_display.media.image.default' => ['entity_form_display', 'media.image.default'],
    'core.entity_form_display.media.image.media_library' => ['entity_form_display', 'media.image.media_library'],
    'core.entity_form_display.media.user_profile_picture.default' => ['entity_form_display', 'media.user_profile_picture.default'],
    'core.entity_form_display.media.user_profile_picture.media_library' => ['entity_form_display', 'media.user_profile_picture.media_library'],
    'views.view.related_dates_export' => ['view', 'related_dates_export'],
  ];

  /**
   * Switched config entities without a component state, as for the above.
   *
   * @var array<string, array{0: string, 1: string}>
   *   Entity type ID and entity ID.
   */
  private const array STATELESS_CONFIG = [
    'metatag.metatag_defaults.node' => ['metatag_defaults', 'node'],
    'metatag.metatag_defaults.node__date' => ['metatag_defaults', 'node__date'],
    'metatag.metatag_defaults.taxonomy_term' => ['metatag_defaults', 'taxonomy_term'],
    'crop.type.16_9' => ['crop_type', '16_9'],
    'crop.type.1_1' => ['crop_type', '1_1'],
    'crop.type.image_crop' => ['crop_type', 'image_crop'],
  ];

  /**
   * The switcher under test.
   */
  private SharedConfigSwitcher $switcher;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();
    $this->switcher = $this->container->get(SharedConfigSwitcher::class);
  }

  /**
   * The shipped config is the standard state.
   *
   * The profile's config/install holds the state of a site without the
   * module, so none of it deviates, and revert leaves it alone.
   */
  public function testShippedConfigIsStandard(): void {
    foreach (array_keys(self::SWITCHED_CONFIG) as $configName) {
      $entity = $this->createFromProfileConfig($configName);
      $this->assertNull($this->switcher->deviation($entity), $configName);
      $this->assertSame(SharedConfigSwitcher::STANDARD, $this->switcher->componentState($entity), $configName);

      $before = $entity->toArray();
      $this->assertFalse($this->switcher->switchEntity($entity, FALSE), $configName . ': a standard component is not reverted.');
      $this->assertSame($before, $entity->toArray(), $configName);
    }
  }

  /**
   * Every switched component of the development site is in the switched state.
   */
  public function testComponentStatesOfDevelopmentSite(): void {
    $expectedKeys = array_map(static fn (array $entity): string => $entity[0] . ':' . $entity[1], array_values(self::SWITCHED_CONFIG));
    $states = $this->switcher->componentStates();

    $this->assertEqualsCanonicalizing($expectedKeys, array_keys($states), 'Every switched entity except metatag defaults and crop types has a state.');
    foreach ($states as $key => $state) {
      $this->assertSame(SharedConfigSwitcher::SWITCHED, $state, $key);
    }
  }

  /**
   * The switched config of the development site does not deviate.
   */
  public function testSwitchedConfigIsNoDeviation(): void {
    $this->assertTrue($this->switcher->isResponsive(), 'The site is switched to responsive images.');
    foreach (self::SWITCHED_CONFIG as $configName => [$entityTypeId, $entityId]) {
      $entity = $this->container->get('entity_type.manager')->getStorage($entityTypeId)->load($entityId);
      $this->assertInstanceOf(ConfigEntityInterface::class, $entity, $configName);
      $this->assertNull($this->switcher->deviation($entity), $configName);
    }

    $this->assertSame([], $this->switcher->deviations());
  }

  /**
   * A deviating component is only switched when overwriting is allowed.
   *
   * @param string $configName
   *   The config name of the entity.
   * @param string $property
   *   The entity property holding the switched component.
   * @param list<string> $valuePath
   *   The path to the switched value within the property.
   * @param mixed $deviatingValue
   *   A value the site configured itself.
   * @param string $expectedCurrent
   *   The expected description of the deviating value.
   * @param string $expectedStandard
   *   The expected description of the standard value.
   * @param list<string> $switchedPath
   *   The path to the value this module switches to.
   * @param mixed $switchedValue
   *   The value this module switches to.
   * @param mixed $legacyValue
   *   The standard value at $valuePath.
   */
  #[DataProvider('deviationProvider')]
  public function testDeviation(string $configName, string $property, array $valuePath, mixed $deviatingValue, string $expectedCurrent, string $expectedStandard, array $switchedPath, mixed $switchedValue, mixed $legacyValue): void {
    $entity = $this->createFromProfileConfig($configName);
    $this->setValue($entity, $property, $valuePath, $deviatingValue);

    $this->assertSame([
      'key' => $entity->getEntityTypeId() . ':' . $entity->id(),
      'current' => $expectedCurrent,
      'standard' => $expectedStandard,
    ], $this->switcher->deviation($entity));
    $this->assertSame(SharedConfigSwitcher::DEVIATING, $this->switcher->componentState($entity));

    $before = $entity->toArray();
    $this->assertFalse($this->switcher->switchEntity($entity, FALSE), 'A deviation is not reverted.');
    $this->assertSame($before, $entity->toArray());

    $this->assertFalse($this->switcher->switchEntity($entity, TRUE), 'A deviation is not switched without approval.');
    $this->assertSame($deviatingValue, $this->getValue($entity, $property, $valuePath));

    $this->assertTrue($this->switcher->switchEntity($entity, TRUE, TRUE), 'An approved deviation is switched.');
    $this->assertSame($switchedValue, $this->getValue($entity, $property, $switchedPath));
    $this->assertNull($this->switcher->deviation($entity));
    $this->assertSame(SharedConfigSwitcher::SWITCHED, $this->switcher->componentState($entity));

    $this->assertTrue($this->switcher->switchEntity($entity, FALSE), 'The switched component is reverted.');
    $this->assertSame($legacyValue, $this->getValue($entity, $property, $valuePath));
    $this->assertNull($this->switcher->deviation($entity));
    $this->assertSame(SharedConfigSwitcher::STANDARD, $this->switcher->componentState($entity));
  }

  /**
   * One deviation per kind of switched component.
   *
   * @return iterable<string, array{string, string, list<string>, mixed, string, string, list<string>, mixed, mixed}>
   *   The test cases.
   */
  public static function deviationProvider(): iterable {
    yield 'media image formatter' => [
      'core.entity_view_display.media.image.content_asset',
      'content',
      ['field_media_image', 'settings', 'image_style'],
      'large',
      'image: large',
      'image: content_image',
      ['field_media_image', 'settings', 'responsive_image_style'],
      'oc_bounds_800',
      'content_image',
    ];
    yield 'token display' => [
      'core.entity_view_display.media.image.token',
      'content',
      ['field_media_image', 'settings', 'image_style'],
      'large',
      'image style: large',
      'image style: header_image',
      ['field_media_image', 'settings', 'image_style'],
      'oc_2_1_1200',
      'header_image',
    ];
    yield 'gallery paragraph display' => [
      'core.entity_view_display.paragraph.gallery.default',
      'content',
      ['field_gallery', 'settings', 'view_mode'],
      'teaser_node',
      'view mode: teaser_node',
      'view mode: gallery',
      ['field_gallery', 'settings', 'view_mode'],
      'gallery_big',
      'gallery',
    ];
    $viewImageStyle = ['default', 'display_options', 'fields', 'field_media_image_1', 'settings', 'image_style'];
    yield 'related dates export view' => [
      'views.view.related_dates_export',
      'display',
      $viewImageStyle,
      'large',
      'image style: large',
      'image style: teaser_big',
      $viewImageStyle,
      'oc_16_9_1200',
      'teaser_big',
    ];
    yield 'crop list' => [
      'core.entity_form_display.media.image.default',
      'content',
      ['field_media_image', 'settings', 'crop_list'],
      ['16_9'],
      'crop types: 16_9',
      'crop types: 16_9, free_format, image_crop',
      ['field_media_image', 'settings', 'crop_list'],
      ['oc_16_9', 'oc_2_1', 'free_format'],
      ['16_9', 'free_format', 'image_crop'],
    ];
  }

  /**
   * A different image_loading attribute alone is no deviation.
   */
  public function testImageLoadingIsIgnored(): void {
    $entity = $this->createFromProfileConfig('core.entity_view_display.media.image.content_asset');
    $this->setValue($entity, 'content', ['field_media_image', 'settings', 'image_loading'], ['attribute' => 'eager']);

    $this->assertNull($this->switcher->deviation($entity));
    $this->assertTrue($this->switcher->switchEntity($entity, TRUE));
    $this->assertSame('responsive_image', $this->getValue($entity, 'content', ['field_media_image', 'type']));
  }

  /**
   * The order of a crop list does not matter.
   */
  public function testCropListOrderIsIgnored(): void {
    $entity = $this->createFromProfileConfig('core.entity_form_display.media.image.default');
    $cropListPath = ['field_media_image', 'settings', 'crop_list'];

    $this->setValue($entity, 'content', $cropListPath, ['image_crop', '16_9', 'free_format']);
    $this->assertNull($this->switcher->deviation($entity), 'The legacy crop types in another order are the standard state.');

    $this->setValue($entity, 'content', $cropListPath, ['free_format', 'oc_2_1', 'oc_16_9']);
    $this->assertNull($this->switcher->deviation($entity), 'The responsive crop types in another order are the switched state.');
  }

  /**
   * A responsive crop list in another order is reverted like the exact one.
   */
  public function testRevertOfReorderedResponsiveCropList(): void {
    $entity = $this->createFromProfileConfig('core.entity_form_display.media.image.default');
    $cropListPath = ['field_media_image', 'settings', 'crop_list'];
    $this->setValue($entity, 'content', $cropListPath, ['free_format', 'oc_2_1', 'oc_16_9']);
    $this->assertSame(SharedConfigSwitcher::SWITCHED, $this->switcher->componentState($entity));

    $this->assertTrue($this->switcher->switchEntity($entity, FALSE));
    $this->assertSame(['16_9', 'free_format', 'image_crop'], $this->getValue($entity, 'content', $cropListPath));
    $this->assertSame(SharedConfigSwitcher::STANDARD, $this->switcher->componentState($entity));
  }

  /**
   * The switched development site has values to revert.
   *
   * The check reverts copies, so entities loaded through the storage before
   * and after it are the same.
   */
  public function testHasRevertibleValuesLeavesLoadedEntitiesUnchanged(): void {
    $before = $this->loadSwitchedEntitiesAsArrays();
    $this->assertTrue($this->switcher->hasRevertibleValues());
    $this->assertSame($before, $this->loadSwitchedEntitiesAsArrays());
  }

  /**
   * Loads every switched entity through its storage.
   *
   * @return array<string, array<mixed>>
   *   The entities as arrays, keyed by config name.
   */
  private function loadSwitchedEntitiesAsArrays(): array {
    $entityTypeManager = $this->container->get('entity_type.manager');
    $entities = [];
    foreach ([...self::SWITCHED_CONFIG, ...self::STATELESS_CONFIG] as $configName => [$entityTypeId, $entityId]) {
      $entity = $entityTypeManager->getStorage($entityTypeId)->load($entityId);
      $this->assertInstanceOf(ConfigEntityInterface::class, $entity, $configName);
      $entities[$configName] = $entity->toArray();
    }

    return $entities;
  }

  /**
   * Builds an unsaved config entity from the profile's config/install.
   */
  private function createFromProfileConfig(string $configName): ConfigEntityInterface {
    $profilePath = $this->container->get('extension.list.profile')->getPath('openculturas');
    $fileContents = file_get_contents(DRUPAL_ROOT . '/' . $profilePath . '/config/install/' . $configName . '.yml');
    $this->assertIsString($fileContents, $configName);
    $data = Yaml::decode($fileContents);
    $this->assertIsArray($data, $configName);

    $storage = $this->container->get('entity_type.manager')->getStorage(self::SWITCHED_CONFIG[$configName][0]);
    return $storage->createFromStorageRecord($data);
  }

  /**
   * Reads a nested value of an entity property.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity.
   * @param string $property
   *   The property.
   * @param list<string> $path
   *   The path within the property.
   */
  private function getValue(ConfigEntityInterface $entity, string $property, array $path): mixed {
    $value = $entity->get($property);
    $this->assertIsArray($value);
    return NestedArray::getValue($value, $path);
  }

  /**
   * Sets a nested value of an entity property, in memory only.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity.
   * @param string $property
   *   The property.
   * @param list<string> $path
   *   The path within the property.
   * @param mixed $value
   *   The value.
   */
  private function setValue(ConfigEntityInterface $entity, string $property, array $path, mixed $value): void {
    $propertyValue = $entity->get($property);
    $this->assertIsArray($propertyValue);
    NestedArray::setValue($propertyValue, $path, $value);
    $entity->set($property, $propertyValue);
  }

}
