<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSitePerformance;

use Drupal\Core\Url;
use Drupal\Tests\openculturas\ExistingSitePerformanceBase;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Performance metrics of the demo content for anonymous visitors.
 *
 * The profile installs the demo content of openculturas_content on a new
 * installation only. The test runs only on such a site and is skipped on a
 * site with the database of this project, which has the site UUID of
 * config/sync/system.site.yml.
 *
 * The demo date is on 2030-02-16. Until then, lists of upcoming dates contain
 * it, so the metrics do not change with the current date.
 */
final class DemoContentPerformanceTest extends ExistingSitePerformanceBase {

  /**
   * The site UUID of config/sync/system.site.yml.
   */
  private const string PROJECT_SITE_UUID = '4e1801fb-85a2-40f3-8ad4-11f952d5e819';

  /**
   * The UUIDs of the demo nodes under test, keyed by page name.
   *
   * The front page has no UUID, the test requests the route <front>.
   */
  private const array NODE_UUIDS = [
    'front' => NULL,
    // OpenCulturas-Dojo.
    'event' => 'e98c91ad-32a2-4938-a08e-0e170cc592a4',
    // OpenCulturas-Dojo - ID 17.
    'date' => 'cb6c29c4-9930-4c40-884c-549b9a070770',
    // OpenCulturas-Verein.
    'profile' => 'b3821a94-f7d2-4b4d-a1e2-5ba9ab38c229',
    // Hallo Welt!
    'article' => 'bbb884b9-682f-460a-9c92-2bc892fdcf96',
  ];

  /**
   * Asset metrics per page, the same for all cache states.
   */
  private const array ASSETS = [
    'front' => [
      'StylesheetCount' => 4,
      'ScriptCount' => 8,
      'StylesheetBytes' => 352533,
      'ScriptBytes' => 394188,
    ],
    'event' => [
      'StylesheetCount' => 4,
      'ScriptCount' => 8,
      'StylesheetBytes' => 336062,
      'ScriptBytes' => 391201,
    ],
    'date' => [
      'StylesheetCount' => 4,
      'ScriptCount' => 8,
      'StylesheetBytes' => 334671,
      'ScriptBytes' => 339476,
    ],
    'profile' => [
      'StylesheetCount' => 4,
      'ScriptCount' => 8,
      'StylesheetBytes' => 336062,
      'ScriptBytes' => 391201,
    ],
    'article' => [
      'StylesheetCount' => 4,
      'ScriptCount' => 8,
      'StylesheetBytes' => 334379,
      'ScriptBytes' => 339133,
    ],
  ];

  /**
   * Expected metrics per page and cache state, without the asset metrics.
   */
  private const array EXPECTED = [
    'front' => [
      // The views oc_frontpage.block_front_profiles and
      // oc_frontpage.block_front_locations sort randomly, which sets the
      // max-age to 0. The dynamic page cache cannot store the page, so a
      // page cache miss renders the page again. Sometimes the state cache
      // collector writes after the response.
      'dynamic_page_cache_hit' => [
        'QueryCount' => [38, 44],
        'CacheGetCount' => [420, 448],
        'CacheSetCount' => [8, 10],
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => [26, 27],
        'CacheTagInvalidationCount' => 0,
      ],
      'render_cache_miss' => [
        'QueryCount' => 85,
        'CacheGetCount' => 600,
        'CacheSetCount' => 65,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 42,
        'CacheTagInvalidationCount' => 0,
      ],
      'cold' => [
        'QueryCount' => 436,
        'CacheGetCount' => 664,
        'CacheSetCount' => 469,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 49,
        'CacheTagInvalidationCount' => 0,
      ],
    ],
    'event' => [
      'dynamic_page_cache_hit' => [
        // Sometimes the state cache collector writes after the response.
        'QueryCount' => [7, 9],
        'CacheGetCount' => 61,
        'CacheSetCount' => 2,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 7,
        'CacheTagInvalidationCount' => 0,
      ],
      'render_cache_miss' => [
        'QueryCount' => 71,
        'CacheGetCount' => 363,
        'CacheSetCount' => 49,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 33,
        'CacheTagInvalidationCount' => 0,
      ],
      'cold' => [
        'QueryCount' => 360,
        'CacheGetCount' => 464,
        'CacheSetCount' => 426,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 36,
        'CacheTagInvalidationCount' => 0,
      ],
    ],
    'date' => [
      'dynamic_page_cache_hit' => [
        // Sometimes the state cache collector writes after the response.
        'QueryCount' => [7, 9],
        'CacheGetCount' => 59,
        'CacheSetCount' => 2,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 7,
        'CacheTagInvalidationCount' => 0,
      ],
      'render_cache_miss' => [
        'QueryCount' => 50,
        'CacheGetCount' => 298,
        'CacheSetCount' => 46,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 27,
        'CacheTagInvalidationCount' => 0,
      ],
      'cold' => [
        'QueryCount' => 283,
        'CacheGetCount' => 409,
        'CacheSetCount' => 388,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 30,
        'CacheTagInvalidationCount' => 0,
      ],
    ],
    'profile' => [
      'dynamic_page_cache_hit' => [
        // Sometimes the state cache collector writes after the response.
        'QueryCount' => [7, 9],
        'CacheGetCount' => 61,
        'CacheSetCount' => 2,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 7,
        'CacheTagInvalidationCount' => 0,
      ],
      'render_cache_miss' => [
        'QueryCount' => 66,
        'CacheGetCount' => 362,
        'CacheSetCount' => 57,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 35,
        'CacheTagInvalidationCount' => 0,
      ],
      'cold' => [
        'QueryCount' => 348,
        'CacheGetCount' => 463,
        'CacheSetCount' => 431,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 38,
        'CacheTagInvalidationCount' => 0,
      ],
    ],
    'article' => [
      'dynamic_page_cache_hit' => [
        // Sometimes the state cache collector writes after the response.
        'QueryCount' => [7, 9],
        'CacheGetCount' => 59,
        'CacheSetCount' => 2,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 7,
        'CacheTagInvalidationCount' => 0,
      ],
      'render_cache_miss' => [
        'QueryCount' => 57,
        'CacheGetCount' => 308,
        'CacheSetCount' => 43,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 29,
        'CacheTagInvalidationCount' => 0,
      ],
      'cold' => [
        'QueryCount' => 303,
        'CacheGetCount' => 416,
        'CacheSetCount' => 396,
        'CacheDeleteCount' => 0,
        'CacheTagLookupQueryCount' => 32,
        'CacheTagInvalidationCount' => 0,
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if (\Drupal::config('system.site')->get('uuid') === self::PROJECT_SITE_UUID) {
      $this->markTestSkipped('The demo content exists only on a new installation.');
    }
  }

  /**
   * Returns the page names.
   *
   * @return array<string, array{string}>
   *   The page names, keyed by page name.
   */
  public static function pages(): array {
    $pages = [];
    foreach (array_keys(self::NODE_UUIDS) as $page) {
      $pages[$page] = [$page];
    }
    return $pages;
  }

  /**
   * Tests a dynamic page cache hit after a page cache miss.
   */
  #[DataProvider('pages')]
  public function testDynamicPageCacheHit(string $page): void {
    $url = $this->warmUp($page);
    \Drupal::cache('page')->deleteAll();
    $this->assertPageMetrics($page, 'dynamic_page_cache_hit', $url);
  }

  /**
   * Tests a render cache miss with all other caches warm.
   */
  #[DataProvider('pages')]
  public function testRenderCacheMiss(string $page): void {
    $url = $this->warmUp($page);
    foreach (['page', 'dynamic_page_cache', 'render'] as $bin) {
      \Drupal::cache($bin)->deleteAll();
    }
    $this->assertPageMetrics($page, 'render_cache_miss', $url);
  }

  /**
   * Tests a request after all caches are cleared.
   */
  #[DataProvider('pages')]
  public function testColdCaches(string $page): void {
    $url = $this->warmUp($page);
    $this->clearCaches();
    $this->assertPageMetrics($page, 'cold', $url);
  }

  /**
   * Requests the page once to create the asset aggregates and warm all caches.
   *
   * @return \Drupal\Core\Url
   *   The URL of the page.
   */
  private function warmUp(string $page): Url {
    $uuid = self::NODE_UUIDS[$page];
    if ($uuid === NULL) {
      $url = Url::fromRoute('<front>');
    }
    else {
      $node = \Drupal::service('entity.repository')->loadEntityByUuid('node', $uuid);
      $this->assertInstanceOf(NodeInterface::class, $node, sprintf('The demo node %s exists.', $uuid));
      $url = $node->toUrl();
    }
    $this->drupalGet($url);
    // The Selenium driver has no status code, so check the content instead.
    $this->assertSession()->elementExists('css', 'h1');
    $this->waitForPostResponseTasks();
    return $url;
  }

  /**
   * Requests the page and compares the metrics with the expected values.
   */
  private function assertPageMetrics(string $page, string $state, Url $url): void {
    $performance_data = $this->collectPerformanceData(function () use ($url): void {
      $this->drupalGet($url);
    }, 'demoContent_' . $page . '_' . $state);
    $this->assertPerformanceMetrics(self::EXPECTED[$page][$state] + self::ASSETS[$page], $performance_data);
  }

}
