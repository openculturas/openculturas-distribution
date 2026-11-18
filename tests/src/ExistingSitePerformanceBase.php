<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas;

use Drupal\Tests\PerformanceData;
use weitzman\DrupalTestTraits\ExistingSitePerformanceBase as OriginExistingSitePerformanceBase;

/**
 * Base class for performance tests against the existing site.
 *
 * The core test module performance_test records every database query and
 * cache operation of every request into one key value entry. The entry grows
 * until a test reads it, so the module is installed only for the duration of
 * a test.
 *
 * While a test runs, a marker file makes settings.php include
 * .ddev/settings.performance-test.php for the test process and for the
 * requests of the browser.
 */
abstract class ExistingSitePerformanceBase extends OriginExistingSitePerformanceBase {

  /**
   * Whether this test installed performance_test and must uninstall it.
   */
  private bool $uninstallPerformanceTest = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Drupal reads settings.php during parent::setUp(), so the marker file
    // must exist before.
    touch(self::markerFilePath());
    parent::setUp();
    if (!\Drupal::moduleHandler()->moduleExists('performance_test')) {
      \Drupal::service('module_installer')->install(['performance_test']);
      $this->uninstallPerformanceTest = TRUE;
    }
    $this->failOnLoggedErrors();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->uninstallPerformanceTest) {
      \Drupal::service('module_installer')->uninstall(['performance_test']);
      \Drupal::keyValue('performance_test')->deleteAll();
    }
    parent::tearDown();
    // Remove the marker file only after the uninstall. Without the setting
    // extension_discovery_scan_tests, Drupal cannot find the installed module.
    unlink(self::markerFilePath());
  }

  /**
   * Waits until asset requests and post-response tasks are finished.
   *
   * The browser receives the page before Drupal has finished its
   * post-response tasks and before the asset requests are complete.
   */
  protected function waitForPostResponseTasks(): void {
    sleep(2);
  }

  /**
   * Asserts performance metrics.
   *
   * @param array<string, int|array{int, int}> $expected
   *   The expected metrics, keyed by the names from getMetrics(). A value is
   *   an exact number or a list with the minimum and the maximum.
   *   StylesheetBytes and ScriptBytes may differ by 2000 bytes.
   * @param \Drupal\Tests\PerformanceData $performance_data
   *   The collected performance data.
   */
  protected function assertPerformanceMetrics(array $expected, PerformanceData $performance_data): void {
    $metrics = $this->getMetrics($performance_data);
    // Replace each value inside its range with the expected value, so that
    // the diff of a failure shows only the metrics outside their range.
    $actual = [];
    foreach ($expected as $name => $value) {
      $range = $value;
      if (in_array($name, ['StylesheetBytes', 'ScriptBytes'], TRUE) && is_int($value)) {
        $range = [$value - 2000, $value + 2000];
      }
      [$minimum, $maximum] = is_array($range) ? $range : [$range, $range];
      $actual[$name] = $metrics[$name] >= $minimum && $metrics[$name] <= $maximum ? $value : $metrics[$name];
    }
    $this->assertSame($expected, $actual);
  }

  /**
   * Returns the path of the marker file that settings.php checks.
   */
  private static function markerFilePath(): string {
    return dirname(__DIR__) . '/.performance-test-running';
  }

}
