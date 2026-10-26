<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

/**
 * Records the S1 baseline: the state of a site the modules were never on.
 *
 * Run by tests/scripts/lifecycle_test.sh right after the update hooks have run on
 * the restored 3.1.2 snapshot. Every scenario compares its end state against
 * this one, so "as if the modules had never been installed" is decided by a
 * recording of that exact state rather than by a hand-written expectation.
 *
 * This class asserts nothing on purpose. Its only job is to produce the
 * baseline; a failure here means the baseline could not be recorded, and the
 * orchestrator stops.
 */
final class LifecycleBaselineTest extends LifecycleBase {

  /**
   * The baseline test records the reference phase, so it does not compare to one.
   */
  #[\Override]
  protected function referencePhase(): string {
    return '';
  }

  /**
   * Records the state of the database the scenarios start from.
   */
  public function testRecordBaseline(): void {
    // No login and no browser: nothing here changes the site, it only reads.
    $directory = $this->recordPhase('S1');

    $this->assertFileExists(
      $directory . '/data/crop_field_data.txt',
      'The baseline records the crop data every migration compares against.',
    );
    $this->assertFileExists(
      $directory . '/data/state.txt',
      'The baseline records the state entries of the modules under test, of which there must be none yet.',
    );
  }

}
