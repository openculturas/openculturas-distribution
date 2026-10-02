<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\openculturas_custom\GetThemeFaviconsPath;
use PHPUnit\Framework\Attributes\Group;

#[Group('openculturas')]
class FaviconPathResolutionTest extends ExistingSiteBase {

  /**
   * The base path before the test changed it.
   */
  protected ?string $originalBasePath = NULL;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->originalBasePath !== NULL) {
      $GLOBALS['base_path'] = $this->originalBasePath;
    }

    parent::tearDown();
  }

  public function testPathIsRelative(): void {
    $path = GetThemeFaviconsPath::getPath('opcult');
    $this->assertIsString($path);
    $this->assertStringEndsWith('/themes/opcult/favicons', $path);
    $this->assertStringStartsNotWith('/', $path);
  }

  public function testPathOfThemeWithoutFavicons(): void {
    $this->assertNull(GetThemeFaviconsPath::getPath('stable9'));
  }

  public function testPathOfUnknownTheme(): void {
    // "core" is the pseudo theme used when no installed theme is negotiated.
    $this->assertNull(GetThemeFaviconsPath::getPath('core'));
  }

  public function testActiveThemeWithOwnFavicons(): void {
    $activeTheme = $this->createActiveTheme('openculturas_base');
    $this->assertSame(GetThemeFaviconsPath::getPath('openculturas_base'), GetThemeFaviconsPath::getPathForActiveTheme($activeTheme));
  }

  public function testSubThemeFallsBackToNearestBaseTheme(): void {
    // stable9 stands in for a sub-theme without its own favicons directory.
    $activeTheme = $this->createActiveTheme('stable9', ['openculturas_base', 'opcult']);
    $this->assertSame(GetThemeFaviconsPath::getPath('openculturas_base'), GetThemeFaviconsPath::getPathForActiveTheme($activeTheme));
  }

  public function testThemeChainWithoutFaviconsFallsBackToOpcult(): void {
    $expected = GetThemeFaviconsPath::getPath('opcult');
    $this->assertSame($expected, GetThemeFaviconsPath::getPathForActiveTheme($this->createActiveTheme('stable9')));
    $this->assertSame($expected, GetThemeFaviconsPath::getPathForActiveTheme($this->createActiveTheme('core')));
  }

  public function testFaviconPathTokenIncludesBasePath(): void {
    $this->originalBasePath = $GLOBALS['base_path'];
    $GLOBALS['base_path'] = '/subdirectory/';

    $path = GetThemeFaviconsPath::getPathForActiveTheme($this->container->get('theme.manager')->getActiveTheme());
    $this->assertIsString($path);
    $replaced = $this->container->get('token')->replace('[site:favicon-path]');
    $this->assertSame('/subdirectory/' . $path, $replaced);
  }

  public function testShortcutIconTokenDefaultsToThemeIco(): void {
    $replaced = $this->container->get('token')->replace('[site:favicon-shortcut-icon]');
    $this->assertStringEndsWith('/favicons/favicon.ico', $replaced);
  }

  /**
   * Creates an active theme with the given base themes, nearest first.
   *
   * @param string $name
   *   The theme name.
   * @param list<string> $baseThemeNames
   *   The base theme names.
   */
  private function createActiveTheme(string $name, array $baseThemeNames = []): ActiveTheme {
    $themeList = $this->container->get('extension.list.theme');
    $baseThemeExtensions = [];
    foreach ($baseThemeNames as $baseThemeName) {
      $baseThemeExtensions[$baseThemeName] = $themeList->get($baseThemeName);
    }

    return new ActiveTheme([
      'name' => $name,
      'base_theme_extensions' => $baseThemeExtensions,
    ]);
  }

}
