<?php

/**
 * @file
 * Settings for performance tests.
 *
 * Included by settings.php only while a performance test runs.
 *
 * @see \Drupal\Tests\openculturas\ExistingSitePerformanceBase
 */

// Makes the core test module performance_test available.
$settings['extension_discovery_scan_tests'] = TRUE;

// With a max-age above 0, Chrome takes a page that it requested before from
// its own cache, so the request does not reach Drupal and is not measured.
// The internal page cache keeps cached pages independent of this value.
$config['system.performance']['cache']['page']['max_age'] = 0;
