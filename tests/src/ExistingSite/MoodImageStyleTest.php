<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\file\FileInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\image\ImageStyleStorageInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\opcult\Hook\OpcultHooks;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the image style the backdrop of opcult pages is built with.
 *
 * The backdrop follows the optional openculturas_responsive_images module:
 * once it is installed, its oc_16_9_* styles are used, independent of the
 * module's switch. Before the crops are migrated there are no oc_16_9 crops,
 * and the styles render a centered 16:9 crop. A site without the module uses
 * the header_image style.
 *
 * The tests replace the module handler with a double, so they cover both
 * states without installing or uninstalling the module.
 */
#[Group('openculturas')]
class MoodImageStyleTest extends ExistingSiteBase {

  /**
   * The background image path, the theme setting used here.
   */
  protected const string BACKGROUND_IMAGE_PATH = 'public://background.jpg';

  /**
   * The URL each image style builds for the background image.
   *
   * @var array<string, string>
   */
  protected const array STYLE_URLS = [
    'header_image' => 'public://styles/header_image/background.jpg',
    'oc_16_9_1200' => 'public://styles/oc_16_9_1200/background.jpg',
    'oc_16_9_2000' => 'public://styles/oc_16_9_2000/background.jpg',
  ];

  /**
   * The image style the backdrop was built with.
   */
  protected ?string $usedStyle = NULL;

  /**
   * Tests the backdrop image style for each module state and theme setting.
   *
   * @param bool $is_module_installed
   *   Whether openculturas_responsive_images is installed.
   * @param string $background_image_mode
   *   The theme setting choosing the backdrop source.
   * @param string|null $expected_style
   *   The image style the backdrop has to use, or NULL for no backdrop.
   */
  #[DataProvider('backdropStyleProvider')]
  public function testBackdropImageStyle(bool $is_module_installed, string $background_image_mode, ?string $expected_style): void {
    $variables = [];
    $this->createHooks($is_module_installed, $background_image_mode)->preprocessPage($variables);

    $this->assertSame($expected_style, $this->usedStyle);
    $this->assertSame(
      $expected_style === NULL ? NULL : self::STYLE_URLS[$expected_style],
      $variables['mood_image_bg'] ?? NULL,
    );
  }

  /**
   * Data provider for testBackdropImageStyle().
   *
   * @return array<string, array{bool, string, string|null}>
   *   The test cases.
   */
  public static function backdropStyleProvider(): array {
    return [
      'module missing' => [FALSE, 'mood_image', 'header_image'],
      'module missing, high resolution' => [FALSE, 'mood_image_high_resolution', 'header_image'],
      'module installed, medium resolution' => [TRUE, 'mood_image', 'oc_16_9_1200'],
      'module installed, high resolution' => [TRUE, 'mood_image_high_resolution', 'oc_16_9_2000'],
      'module installed, global image' => [TRUE, 'global_image', 'oc_16_9_1200'],
      'no backdrop configured' => [TRUE, 'disabled', NULL],
    ];
  }

  /**
   * Tests that a rendered backdrop depends on the image style list.
   *
   * Installing the module creates the oc_16_9_* styles, which changes the
   * style the backdrop uses without touching the page content, and module
   * installation flushes no render cache. The expected tags are read from the
   * image style entity type, so a wrong hardcoded tag name fails here.
   *
   * @see \Drupal\Core\Theme\ThemeManager::render()
   */
  #[DataProvider('moduleStateProvider')]
  public function testBackdropIsCacheTagged(bool $is_module_installed): void {
    $variables = [];
    $this->createHooks($is_module_installed, 'mood_image')->preprocessPage($variables);

    $this->assertEqualsCanonicalizing(
      \Drupal::entityTypeManager()->getDefinition('image_style')->getListCacheTags(),
      $variables['#cache']['tags'] ?? [],
    );
  }

  /**
   * Data provider for testBackdropIsCacheTagged().
   *
   * @return array<string, array{bool}>
   *   The test cases.
   */
  public static function moduleStateProvider(): array {
    return [
      'module missing' => [FALSE],
      'module installed' => [TRUE],
    ];
  }

  /**
   * Tests that the container can build the hook class.
   *
   * Theme hook classes are registered as autowired services, so every
   * constructor argument has to be resolvable by its type.
   */
  public function testHookServiceIsAutowired(): void {
    $this->assertSame(OpcultHooks::class, \Drupal::service(OpcultHooks::class)::class);
  }

  /**
   * Returns the hook class with a module handler double.
   *
   * @param bool $is_module_installed
   *   Whether the double reports openculturas_responsive_images as installed.
   * @param string $background_image_mode
   *   The theme setting choosing the backdrop source.
   */
  protected function createHooks(bool $is_module_installed, string $background_image_mode): OpcultHooks {
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('moduleExists')->willReturnCallback(
      static fn (string $module): bool => $module === 'openculturas_responsive_images' && $is_module_installed
    );

    return new OpcultHooks(
      $this->createRouteMatch(),
      $this->createThemeSettingsProvider($background_image_mode),
      $this->createEntityTypeManager(),
      $this->createStub(EntityRepositoryInterface::class),
      $moduleHandler,
    );
  }

  /**
   * Returns a route match holding a node with a mood image.
   *
   * @see \Drupal\opcult\Hook\OpcultHooks::getRouteEntityMoodImageUri()
   */
  protected function createRouteMatch(): RouteMatchInterface {
    $mediaImageField = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $file = $this->createMock(FileInterface::class);
    $file->method('getFileUri')->willReturn(self::BACKGROUND_IMAGE_PATH);
    $mediaImageField->method('referencedEntities')->willReturn([$file->id() => $file]);

    $media = $this->createMock(MediaInterface::class);
    $media->method('get')->with('field_media_image')->willReturn($mediaImageField);

    // The hook reads the media through the field list's magic $entity
    // property, which a field list delegates to its first item.
    $moodImageField = $this->createMock(FieldItemList::class);
    $moodImageField->method('__get')->willReturnCallback(
      static fn (string $property_name): mixed => $property_name === 'entity' ? $media : NULL
    );

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('field_mood_image')->willReturn(TRUE);
    $node->method('get')->with('field_mood_image')->willReturn($moodImageField);

    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getParameter')->with('node')->willReturn($node);
    return $routeMatch;
  }

  /**
   * Returns a theme settings provider with the given backdrop mode.
   *
   * @param string $background_image_mode
   *   The theme setting choosing the backdrop source.
   */
  protected function createThemeSettingsProvider(string $background_image_mode): ThemeSettingsProvider {
    $provider = $this->createMock(ThemeSettingsProvider::class);
    $provider->method('getSetting')->willReturnCallback(
      static fn (string $setting): mixed => match ($setting) {
        'background_image.mode' => $background_image_mode,
        'background_image.path' => self::BACKGROUND_IMAGE_PATH,
        default => NULL,
      }
    );
    return $provider;
  }

  /**
   * Returns an entity type manager serving the image styles.
   *
   * The storage records the style it loaded in $usedStyle, so the test can
   * assert on the selection itself and not only on the resulting URL.
   */
  protected function createEntityTypeManager(): EntityTypeManagerInterface {
    $imageStyleStorage = $this->createMock(ImageStyleStorageInterface::class);
    $imageStyleStorage->method('load')->willReturnCallback(function (string $style_name): ImageStyleInterface {
      $style = $this->createMock(ImageStyleInterface::class);
      $style->method('id')->willReturn($style_name);
      $style->method('buildUrl')->willReturnCallback(
        fn (string $uri): string => $this->replaceStyleUri($uri, $style_name)
      );
      $this->usedStyle = $style_name;
      return $style;
    });
    $imageStyleStorage->method('getEntityType')->willReturn(\Drupal::entityTypeManager()->getDefinition('image_style'));

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturnCallback(fn(string $entity_type_id): ?object => match ($entity_type_id) {
      'image_style' => $imageStyleStorage,
      default => NULL,
    });
    return $entityTypeManager;
  }

  /**
   * Returns the URL the given image style builds for the background image.
   *
   * @param string $uri
   *   The background image URI.
   * @param string $style_name
   *   The image style name.
   */
  protected function replaceStyleUri(string $uri, string $style_name): string {
    return str_replace(self::BACKGROUND_IMAGE_PATH, self::STYLE_URLS[$style_name], $uri);
  }

}
