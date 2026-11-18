<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSitePerformance;

use Drupal\Core\Url;
use Drupal\Tests\PerformanceData;
use Drupal\Tests\openculturas\ExistingSitePerformanceBase;

/**
 * Performance metrics of a basic page for anonymous visitors.
 *
 * The test creates its own node, so the metrics do not depend on the content
 * of the local database.
 */
final class BasicPagePerformanceTest extends ExistingSitePerformanceBase {

  /**
   * Asset metrics, the same for all cache states.
   */
  private const array ASSETS = [
    'StylesheetCount' => 4,
    'ScriptCount' => 8,
    'StylesheetBytes' => 335276,
    'ScriptBytes' => 388677,
  ];

  /**
   * The URL of the node under test.
   */
  private Url $url;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $node = $this->createNode([
      'type' => 'page',
      'title' => 'Performance test page',
      'body' => ['value' => '<p>Body text.</p>', 'format' => 'basic_html'],
      'moderation_state' => 'published',
    ]);
    $this->url = $node->toUrl();

    // Create the asset aggregates on disk and warm all caches.
    $this->drupalGet($this->url);
    // The Selenium driver has no status code, so check the content instead.
    $this->assertSession()->elementTextContains('css', 'h1', 'Performance test page');
    $this->waitForPostResponseTasks();
  }

  /**
   * Tests a page cache hit.
   */
  public function testPageCacheHit(): void {
    $this->assertPerformanceMetrics([
      // Sometimes the state cache collector writes after the response.
      'QueryCount' => [0, 2],
      'CacheGetCount' => [2, 3],
      'CacheSetCount' => [0, 1],
      'CacheDeleteCount' => 0,
      'CacheTagLookupQueryCount' => 1,
      'CacheTagInvalidationCount' => 0,
    ] + self::ASSETS, $this->collectPageData('basicPagePageCacheHit'));
  }

  /**
   * Tests a dynamic page cache hit after a page cache miss.
   */
  public function testDynamicPageCacheHit(): void {
    \Drupal::cache('page')->deleteAll();
    $this->assertPerformanceMetrics([
      // Sometimes the state cache collector writes after the response.
      'QueryCount' => [7, 9],
      'CacheGetCount' => 60,
      'CacheSetCount' => 2,
      'CacheDeleteCount' => 0,
      'CacheTagLookupQueryCount' => 7,
      'CacheTagInvalidationCount' => 0,
    ] + self::ASSETS, $this->collectPageData('basicPageDynamicPageCacheHit'));
  }

  /**
   * Tests a render cache miss with all other caches warm.
   */
  public function testRenderCacheMiss(): void {
    foreach (['page', 'dynamic_page_cache', 'render'] as $bin) {
      \Drupal::cache($bin)->deleteAll();
    }
    $this->assertPerformanceMetrics([
      'QueryCount' => 41,
      'CacheGetCount' => 208,
      'CacheSetCount' => 45,
      'CacheDeleteCount' => 0,
      'CacheTagLookupQueryCount' => 23,
      'CacheTagInvalidationCount' => 0,
    ] + self::ASSETS, $this->collectPageData('basicPageRenderCacheMiss'));
  }

  /**
   * Tests a request after all caches are cleared.
   */
  public function testColdCaches(): void {
    $this->clearCaches();
    $this->assertPerformanceMetrics([
      'QueryCount' => 256,
      'CacheGetCount' => 353,
      'CacheSetCount' => 360,
      'CacheDeleteCount' => 0,
      'CacheTagLookupQueryCount' => 26,
      'CacheTagInvalidationCount' => 0,
    ] + self::ASSETS, $this->collectPageData('basicPageCold'));
  }

  /**
   * Requests the node and collects the performance data.
   */
  private function collectPageData(string $service_name): PerformanceData {
    return $this->collectPerformanceData(function (): void {
      $this->drupalGet($this->url);
    }, $service_name);
  }

}
