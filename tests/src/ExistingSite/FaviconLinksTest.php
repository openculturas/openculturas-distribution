<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\Tests\openculturas\Traits\UserTrait;
use PHPUnit\Framework\Attributes\Group;

use function dirname;
use function is_array;
use function is_string;
use function json_decode;

#[Group('openculturas')]
class FaviconLinksTest extends ExistingSiteBase {

  use UserTrait;

  /**
   * The original favicon settings of the default theme, restored on teardown.
   *
   * @var array<string, mixed>|null
   */
  protected ?array $originalFaviconSettings = NULL;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->originalFaviconSettings !== NULL) {
      $this->container->get('config.factory')
        ->getEditable($this->getDefaultThemeName() . '.settings')
        ->set('favicon', $this->originalFaviconSettings)
        ->save();
    }

    parent::tearDown();
  }

  public function testShortcutIconHasSizes(): void {
    $this->drupalGet('<front>');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    // Without sizes, Chrome downloads the ICO in addition to the SVG.
    $session->elementExists('css', 'link[rel="icon"][href$="/favicon.ico"][sizes="32x32"]');
    $session->elementExists('css', 'link[rel="icon"][href$="/favicon.svg"][type="image/svg+xml"]');
  }

  public function testFaviconLinksResolve(): void {
    $this->drupalGet('<front>');
    $hrefs = $this->getLinkHrefs('link[rel="icon"], link[rel="apple-touch-icon"], link[rel="manifest"]');
    $this->assertCount(5, $hrefs, 'The front page links four icons and one manifest.');

    foreach ($hrefs as $href) {
      $this->getSession()->visit($this->getAbsoluteUrl($href));
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  public function testManifestIconsResolve(): void {
    $this->drupalGet('<front>');
    $hrefs = $this->getLinkHrefs('link[rel="manifest"]');
    $this->assertCount(1, $hrefs);
    $manifestUrl = $this->getAbsoluteUrl($hrefs[0]);

    $this->getSession()->visit($manifestUrl);
    $this->assertSession()->statusCodeEquals(200);
    $manifest = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertIsArray($manifest);
    $this->assertIsArray($manifest['icons'] ?? NULL);
    $this->assertNotEmpty($manifest['icons']);

    // Icon sources are relative to the manifest file's own location.
    foreach ($manifest['icons'] as $icon) {
      $this->assertIsArray($icon);
      $this->assertIsString($icon['src'] ?? NULL);
      $this->getSession()->visit(dirname($manifestUrl) . '/' . $icon['src']);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  public function testAdminThemeFavicon(): void {
    $this->loginWithRole('administrator');
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $hrefs = $this->getLinkHrefs('link[rel="icon"][href$="/favicons/drupal-oc-icon.png"]');
    $this->assertCount(1, $hrefs);

    $this->getSession()->visit($this->getAbsoluteUrl($hrefs[0]));
    $this->assertSession()->statusCodeEquals(200);
  }

  public function testCustomShortcutIconWins(): void {
    $configuration = $this->container->get('config.factory')->getEditable($this->getDefaultThemeName() . '.settings');
    $favicon = $configuration->get('favicon');
    $this->originalFaviconSettings = is_array($favicon) ? $favicon : [];

    $customPath = 'profiles/contrib/openculturas-profile/themes/opcult/favicons/drupal-oc-icon.png';
    $configuration
      ->set('favicon.use_default', FALSE)
      ->set('favicon.path', $customPath)
      ->save();

    $this->drupalGet('<front>');
    $session = $this->assertSession();
    // The uploaded favicon replaces the theme's ICO; sizes only applies to
    // .ico files.
    $session->elementExists('css', 'link[rel="icon"][href$="/favicons/drupal-oc-icon.png"]:not([sizes])');
    $session->elementNotExists('css', 'link[rel="icon"][href$="/favicon.ico"]');
  }

  /**
   * Returns the href attributes of all elements matching a CSS selector.
   *
   * @return list<string>
   *   The href values.
   */
  private function getLinkHrefs(string $selector): array {
    $hrefs = [];
    foreach ($this->getSession()->getPage()->findAll('css', $selector) as $element) {
      $href = $element->getAttribute('href');
      if (is_string($href)) {
        $hrefs[] = $href;
      }
    }

    return $hrefs;
  }

  private function getDefaultThemeName(): string {
    $defaultTheme = $this->container->get('config.factory')->get('system.theme')->get('default');
    $this->assertIsString($defaultTheme);
    return $defaultTheme;
  }

}
