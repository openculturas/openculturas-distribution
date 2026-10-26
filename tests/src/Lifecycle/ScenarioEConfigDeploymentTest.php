<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

use Drupal\openculturas_responsive_images\CropMigrator;

/**
 * Scenario E: the config deployment path, the way an instance installs itself.
 *
 * A site that deploys this branch does not go through /admin/modules. It runs
 * `drush deploy`, which imports the configuration and installs the modules with
 * $is_syncing = TRUE, and then runs the two Drush commands
 * scripts/post_tasks.sh runs after every deploy. The import uses `deploy`
 * rather than `config:import` because that is what post_tasks.sh runs.
 *
 * That path is different from the one scenarios A and B walk, because the
 * install hooks bail out early while a config import is running:
 * openculturas_responsive_images_install() returns on $is_syncing, so nothing
 * tells the site builder to migrate anything and nothing switches. The Drush
 * commands are what cover the gap.
 *
 * post_tasks.sh also runs `locale:import` for the German translation. That is
 * left out here because it only changes translations, not what the modules do.
 *
 * This scenario is the only one that runs a deploy, which is why the task
 * allows it here and nowhere else.
 */
final class ScenarioEConfigDeploymentTest extends LifecycleBase {

  /**
   * The UI path state after migration, switch and backfill, before uninstall.
   */
  private const string UI_PATH_END_STATE = 'C-responsive-first-in-use';

  /**
   * Deploys the branch, runs the post_tasks commands, checks the site.
   */
  public function testConfigDeploymentPath(): void {
    $this->loginWithRole('administrator');

    $this->deployBranch();
    $this->reloadSiteState();

    foreach (self::MODULES_UNDER_TEST as $module) {
      $this->assertTrue(
        $this->moduleIsInstalled($module),
        sprintf('The deploy installed %s.', $module),
      );
    }

    // A deploy leaves nothing behind for a second run to do.
    $this->assertSame(
      '',
      $this->configStatus(),
      'A second `drush config:status` after the deploy is empty: the deploy brought the whole configuration in one go.',
    );

    $this->assertNoNewWatchdogErrors();

    // The two commands scripts/post_tasks.sh runs, in the order it runs them.
    $this->runDrushCommand('openculturas-responsive-images:migrate-crops');
    $this->runDrushCommand('entity-popover:backfill-paragraphs');
    $this->reloadSiteState();

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase('E2');

    $this->assertCropsAreMigrated();
    $this->assertTogglesAreFilled();

    $this->assertMatchesUiPath();
  }

  /**
   * Deploys the branch the way scripts/post_tasks.sh does.
   *
   * See scripts/post_tasks.sh: `drush deploy` imports the configuration and
   * runs the update hooks and deploy hooks of the branch.
   */
  private function deployBranch(): void {
    $this->runDrushCommand('deploy --yes');
  }

  /**
   * Runs one Drush command and fails the test when it does not succeed.
   *
   * @param string $command
   *   The command and its arguments, without the leading "drush".
   */
  private function runDrushCommand(string $command): void {
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open(
      'cd /var/www/html && vendor/bin/drush ' . $command . ' 2>&1',
      $descriptors,
      $pipes,
    );

    $this->assertIsResource($process, 'Could not run the Drush command.');
    $collected = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $result = proc_close($process);

    $this->assertSame(
      0,
      $result,
      sprintf("The Drush command `drush %s` failed:\n%s", $command, $collected),
    );
  }

  /**
   * The output of `drush config:status`.
   */
  private function configStatus(): string {
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open(
      'cd /var/www/html && vendor/bin/drush config:status 2>&1',
      $descriptors,
      $pipes,
    );

    $this->assertIsResource($process, 'Could not read the configuration status.');
    $collected = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    // Drush prints its own banner and the "no changes" line around the actual
    // listing; only the configuration names themselves matter.
    $names = [];
    foreach (explode("\n", $collected) as $line) {
      if (str_contains($line, '[') && str_contains($line, ']')) {
        $names[] = trim($line);
      }
    }

    return implode("\n", $names);
  }

  /**
   * Asserts that every legacy crop got a duplicate.
   */
  private function assertCropsAreMigrated(): void {
    $migrator = $this->container->get(CropMigrator::class);
    $this->assertSame(
      [],
      $migrator->migratableSourceTypes(),
      'After the deploy command no crop type is left unmigrated.',
    );

    // The deploy command migrates "each crop type onto the single oc_* crop
    // type with the same aspect ratio", so only the three target types this
    // site's crop types map onto are expected to have crops. The remaining
    // three ship for editors to pick by hand in the settings form and
    // legitimately stay empty here.
    foreach (['oc_16_9', 'oc_1_1', 'oc_2_1'] as $targetId) {
      $count = $this->cropCount($targetId);

      $this->assertGreaterThan(
        0,
        $count,
        sprintf('The deploy command created crops of the %s type.', $targetId),
      );
    }

    foreach (['oc_3_4', 'oc_4_3', 'oc_9_16'] as $targetId) {
      $this->assertSame(
        0,
        $this->cropCount($targetId),
        sprintf(
          'The deploy command creates no %s crops: it only migrates onto a target with the same aspect ratio, and nothing on this site maps there.',
          $targetId,
        ),
      );
    }

    $mappings = $this->container->get('state')->get(CropMigrator::MAPPINGS_STATE_KEY);
    $this->assertNotEmpty($mappings, 'The deploy command recorded the migration history.');
  }

  /**
   * How many crops of a type exist.
   *
   * @param string $type
   *   The crop type machine name.
   */
  private function cropCount(string $type): int {
    return (int) $this->container->get('entity_type.manager')->getStorage('crop')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->count()
      ->execute();
  }

  /**
   * Asserts that no paragraph is left with an empty popover toggle.
   */
  private function assertTogglesAreFilled(): void {
    $rows = $this->recorder->readDump('E2', 'data/paragraph_field_entity_popover.txt');
    $empty = array_values(array_filter(
      $rows,
      static fn (string $line): bool => str_ends_with($line, "\t0") || str_ends_with($line, "\t"),
    ));

    $this->assertSame(
      [],
      $empty,
      'After the deploy command no paragraph is left with an empty popover toggle.',
    );
  }

  /**
   * Asserts that the deploy path ends in the same state as the UI path.
   *
   * The two paths start from different databases and configuration. Data
   * dumps must match, except where a difference has a cause in the code. The
   * configuration is compared only for the objects the UI path changed
   * compared with S1, since those are the objects the modules and their forms
   * worked on. Other differences are written to the observation file.
   */
  private function assertMatchesUiPath(): void {
    if (!$this->recorder->hasPhase(self::UI_PATH_END_STATE)) {
      $this->fail(sprintf(
        'The UI path end state (%s) is not recorded. Scenario E must run after scenario C (responsive images first) which records this phase. Run the full lifecycle test via tests/scripts/lifecycle_test.sh.',
        self::UI_PATH_END_STATE
      ));
    }

    // Everything is collected and written before the first assertion, so a
    // failing check does not keep the rest of the comparison out of the report.
    $observations = ['Scenario E compared with the end state of the UI path (scenario C).', ''];
    $dataMismatches = [];

    foreach ($this->recordedDataFiles() as $relativePath) {
      $ui = $this->recorder->readDump(self::UI_PATH_END_STATE, $relativePath);
      $deploy = $this->recorder->readDump('E2', $relativePath);

      $observations[] = sprintf('%s: %s', $relativePath, $ui === $deploy ? 'identical' : 'DIFFERENT');
      if ($ui !== $deploy) {
        $observations[] = $this->listDifference($ui, $deploy);
        $dataMismatches[$relativePath] = [$ui, $deploy];
      }
    }

    $touchedByUiPath = array_keys($this->configDifferences(self::UI_PATH_END_STATE, 'S1'));
    $configuration = $this->configDifferencesOfChangedObjects('E2', self::UI_PATH_END_STATE, self::UI_PATH_END_STATE, 'S1');

    $observations[] = '';
    $observations[] = sprintf('Configuration objects the UI path changed compared with S1: %d', count($touchedByUiPath));
    $observations[] = implode("\n", $touchedByUiPath);
    $observations[] = '';
    $observations[] = sprintf('Configuration objects that differ between the deploy path and the UI path, and that the UI path did not change: %d', count($configuration['untouched']));
    $observations[] = implode("\n", $configuration['untouched']);

    file_put_contents($this->observationPath('scenario-E-comparison.txt'), implode("\n", $observations) . "\n");

    foreach ($dataMismatches as $relativePath => [$ui, $deploy]) {
      $this->assertSame(
        $ui,
        $deploy,
        sprintf('%s is the same on the deploy path as on the UI path.', $relativePath),
      );
    }

    $this->assertSame(
      [],
      $configuration['touched'],
      'Every configuration object the UI path changed compared with S1 holds the same normalised state on the deploy path as on the UI path (see scenario-E-comparison.txt).',
    );
  }

  /**
   * Describes what one list has and the other does not, for the observation file.
   *
   * @param list<string> $expected
   *   The lines of the UI path.
   * @param list<string> $actual
   *   The lines of the deploy path.
   */
  private function listDifference(array $expected, array $actual): string {
    return sprintf(
      "  only on the UI path (%d):\n    %s\n  only on the deploy path (%d):\n    %s",
      count(array_diff($expected, $actual)),
      implode("\n    ", array_slice(array_diff($expected, $actual), 0, 20)),
      count(array_diff($actual, $expected)),
      implode("\n    ", array_slice(array_diff($actual, $expected), 0, 20)),
    );
  }

}
