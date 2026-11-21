<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSiteJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\openculturas\ExistingSiteJSTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('openculturas')]
class ScrollToTopTest extends ExistingSiteJSTestBase {

  /**
   * The default theme to restore after the test, if the test changed it.
   */
  private ?string $originalDefaultTheme = NULL;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->originalDefaultTheme !== NULL) {
      $this->setDefaultTheme($this->originalDefaultTheme);
    }
    parent::tearDown();
  }

  /**
   * The "Back to top" link scrolls the page back to the top.
   *
   * Both themes rely on the native "#top" fragment and the CSS
   * scroll-behavior, without a JavaScript scroll animation.
   */
  #[DataProvider('themeProvider')]
  public function testScrollToTop(string $theme): void {
    $currentDefaultTheme = \Drupal::config('system.theme')->get('default');
    if ($currentDefaultTheme !== $theme) {
      $this->originalDefaultTheme = $currentDefaultTheme;
      $this->setDefaultTheme($theme);
    }

    // A small window makes sure that the front page can scroll.
    $this->getSession()->resizeWindow(1024, 600);
    $this->drupalGet('<front>');
    $this->assertSame($theme, $this->getSession()->evaluateScript('drupalSettings.ajaxPageState.theme'));
    $page = $this->getSession()->getPage();

    $this->getSession()->executeScript('window.scrollTo(0, document.documentElement.scrollHeight)');
    $this->assertTrue(
      $page->waitFor(5, fn (): bool => $this->getScrollPosition() > 0),
      'The front page scrolls down.',
    );

    $link = $page->find('css', 'a.scroll-to-top');
    $this->assertNotNull($link);
    $this->assertSame('#top', $link->getAttribute('href'));
    // The theme can show the link only after the page has scrolled.
    $this->assertTrue($page->waitFor(5, static fn (): bool => $link->isVisible()));
    $link->click();

    $this->assertTrue(
      $page->waitFor(5, fn (): bool => $this->getScrollPosition() === 0),
      'The page scrolls back to the top.',
    );
  }

  /**
   * Provides the themes with a "Back to top" link.
   *
   * @return array<string, array{string}>
   *   The theme machine names, keyed by data set name.
   */
  public static function themeProvider(): array {
    return [
      'opcult' => ['opcult'],
      'openculturas_base' => ['openculturas_base'],
    ];
  }

  private function getScrollPosition(): int {
    $position = $this->getSession()->evaluateScript('Math.round(window.scrollY)');
    $this->assertIsInt($position);
    return $position;
  }

  private function setDefaultTheme(string $theme): void {
    \Drupal::configFactory()->getEditable('system.theme')->set('default', $theme)->save();
    Cache::invalidateTags(['rendered']);
  }

}
