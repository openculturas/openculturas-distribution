<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas_calendar_widget\ExistingSite;

use Drupal\Tests\openculturas\ExistingSiteBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks how the calendar widget embed page handles its query parameters.
 *
 * The embed code sets source_uri to the calendar page of this site and
 * access_token to a token from the widget settings. Requests can contain any
 * other value. These must not produce a link to a different host or a server
 * error.
 */
#[Group('openculturas')]
class EmbedQueryParametersTest extends ExistingSiteBase {

  private const string SETTINGS = 'openculturas_calendar_widget.settings';

  private const string EMBED_PATH = '/openculturas-calendar-widget/embed';

  /**
   * The original widget settings, or NULL if the settings did not exist.
   *
   * @var array<string, mixed>|null
   */
  protected ?array $originalSettings = NULL;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $settings = $this->container->get('config.factory')->getEditable(self::SETTINGS);
    if ($this->originalSettings === NULL) {
      $settings->delete();
    }
    else {
      $settings->setData($this->originalSettings)->save();
    }

    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $settings = $this->container->get('config.factory')->getEditable(self::SETTINGS);
    $this->originalSettings = $settings->isNew() ? NULL : $settings->getRawData();
  }

  public function testSourceUriOnThisSiteRendersLink(): void {
    $source_uri = $this->getAbsoluteUrl('/calendar?category=1');
    $this->drupalGet(self::EMBED_PATH, ['query' => ['source_uri' => $source_uri]]);
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->elementAttributeContains('css', '.more-link a', 'href', $source_uri);
  }

  /**
   * Provides source_uri values that must not render a link.
   *
   * @return array<string, array{0: string|array<int, string>}>
   *   The source_uri values, keyed by case name.
   */
  public static function invalidSourceUriProvider(): array {
    return [
      'foreign host' => ['https://evil.example/calendar'],
      'protocol-relative foreign host' => ['//evil.example/calendar'],
      'foreign host with this host as subdomain' => ['http://web.evil.example/calendar'],
      'this host in the query of a foreign url' => ['https://evil.example/?http://web/'],
      'no scheme' => ['foo'],
      'javascript scheme' => ['javascript:alert(1)'],
      'array' => [['http://web/calendar']],
    ];
  }

  /**
   * @param string|array<int, string> $source_uri
   *   The source_uri query value.
   */
  #[DataProvider('invalidSourceUriProvider')]
  public function testInvalidSourceUriRendersNoLink(string|array $source_uri): void {
    $this->drupalGet(self::EMBED_PATH, ['query' => ['source_uri' => $source_uri]]);
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->elementExists('css', '#oc-calendar-widget');
    $session->elementNotExists('css', '.more-link');
  }

  public function testAccessTokenSetsFrameAncestors(): void {
    $token = $this->randomMachineName(32);
    $this->setLimitedAccess($token, 'https://example.org/path');

    $this->drupalGet(self::EMBED_PATH, ['query' => ['access_token' => $token]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-Security-Policy', 'frame-ancestors https://example.org');
  }

  /**
   * Provides access_token values that match no configured website.
   *
   * @return array<string, array{0: string|array<int, string>}>
   *   The access_token values, keyed by case name.
   */
  public static function invalidAccessTokenProvider(): array {
    return [
      'unknown token' => ['unknown-token'],
      'array' => [['unknown-token']],
    ];
  }

  /**
   * @param string|array<int, string> $access_token
   *   The access_token query value.
   */
  #[DataProvider('invalidAccessTokenProvider')]
  public function testInvalidAccessTokenDeniesFraming(string|array $access_token): void {
    $this->setLimitedAccess($this->randomMachineName(32), 'https://example.org');

    $this->drupalGet(self::EMBED_PATH, ['query' => ['access_token' => $access_token]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('Content-Security-Policy', "frame-ancestors 'none'");
  }

  /**
   * Allows one website to embed the widget with the given token.
   */
  protected function setLimitedAccess(string $token, string $hostname): void {
    $this->container->get('config.factory')->getEditable(self::SETTINGS)
      ->set('limit_access', TRUE)
      ->set('host_list', [
        $token => [
          'hostname' => $hostname,
          'wildcard' => FALSE,
          'iframe_src' => $this->getAbsoluteUrl(self::EMBED_PATH),
          'css' => '',
        ],
      ])
      ->save();
  }

}
