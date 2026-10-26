<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\Tests\openculturas\Traits\UserTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Base class for the destructive lifecycle tests of the two new modules.
 *
 * These tests install, migrate, switch, revert and uninstall
 * openculturas_responsive_images and openculturas_entity_popover through the
 * user interface, exactly like a site builder on an existing site. They are
 * destructive and must only be run through tests/scripts/lifecycle_test.sh, which
 * restores a DDEV snapshot before every scenario.
 *
 * Every test class calls assertDatabaseIsRestorable() in setUp() before it
 * changes anything. If the database is not the one the scenarios assume, the
 * test skips itself rather than installing or uninstalling anything on a
 * database that cannot be thrown away.
 */
#[Group('lifecycle')]
abstract class LifecycleBase extends ExistingSiteBase {

  use UserTrait;

  /**
   * The modules the two scenarios install and uninstall.
   *
   * @var list<string>
   */
  protected const array MODULES_UNDER_TEST = [
    'openculturas_responsive_images',
    'openculturas_entity_popover',
    'entity_popover',
    'entity_popover_swiffy_slider',
  ];

  /**
   * The profile holding the update hooks the scenarios expect to have run.
   */
  protected const string PROFILE = 'openculturas';

  /**
   * The update hooks that must have run on the database.
   *
   * @var list<string>
   */
  protected const array EXPECTED_UPDATE_HOOKS = [
    'openculturas_update_10131',
    'openculturas_update_10132',
    'openculturas_update_10133',
    'openculturas_update_10134',
    'openculturas_update_10135',
    'openculturas_update_10136',
  ];

  /**
   * The update hooks a restored 3.1.2 database still has pending.
   *
   * A scenario starts from a database the update hooks have already run on,
   * because the modules are installed on top of it through the UI. A database
   * that still has them pending is one the orchestrator did not prepare.
   *
   * @var list<string>
   */
  protected const array PENDING_ON_RESTORED_SNAPSHOT = self::EXPECTED_UPDATE_HOOKS;

  /**
   * The watchdog rows before the test started, to report new errors.
   *
   * @var list<string>
   */
  protected array $watchdogErrorsBeforeTest = [];

  /**
   * The state recorder.
   */
  protected LifecycleStateRecorder $recorder;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();

    // Built by hand rather than from the container: the tests directory is not
    // a service provider, and the recorder has to exist before the baseline
    // guard runs.
    $this->recorder = new LifecycleStateRecorder(
      $this->container->get('database'),
      $this->container->get('config.factory'),
      $this->container->get('state'),
    );

    // The baseline test is what produces the reference phase, so it may not
    // require one.
    if ($this->referencePhase() !== '') {
      $this->assertDatabaseIsRestorable();
    }
  }

  /**
   * Skips the test unless the database is the one the scenarios assume.
   *
   * Called before the test changes anything, so an unprepared database is
   * left untouched rather than uninstalled on. tests/scripts/lifecycle_test.sh
   * restores the 3.1.2 snapshot, runs the update hooks and snapshots the
   * result; a scenario starts from that second snapshot.
   */
  protected function assertDatabaseIsRestorable(): void {
    $installed = $this->installedModulesUnderTest();
    if ($installed !== []) {
      $this->markTestSkipped(sprintf(
        'The database already has %s installed. Lifecycle scenarios must start from a database where the modules under test are not installed. Run them through tests/scripts/lifecycle_test.sh.',
        implode(', ', $installed),
      ));
    }

    $pending = $this->pendingUpdateHooks();
    $unexpected = array_intersect($pending, self::PENDING_ON_RESTORED_SNAPSHOT);
    if ($unexpected !== []) {
      $this->markTestSkipped(sprintf(
        'The database still has %s pending. The scenarios start from a database the update hooks have already run on.',
        implode(', ', $unexpected),
      ));
    }

    $referencePhase = $this->referencePhase();
    if ($referencePhase !== '' && !$this->recorder->hasPhase($referencePhase)) {
      $this->markTestSkipped(sprintf(
        'The baseline phase %s is not recorded, so this test has nothing to compare against. Run it through tests/scripts/lifecycle_test.sh, which records the baseline before running the scenarios.',
        $referencePhase,
      ));
    }

    $this->watchdogErrorsBeforeTest = $this->recorder->readDump($referencePhase, 'watchdog_errors.txt');
  }

  /**
   * The module names of the modules under test that are installed.
   *
   * @return list<string>
   *   The installed module names, sorted.
   */
  protected function installedModulesUnderTest(): array {
    $moduleHandler = \Drupal::moduleHandler();
    $installed = array_values(array_filter(
      self::MODULES_UNDER_TEST,
      static fn (string $module): bool => $moduleHandler->moduleExists($module),
    ));
    sort($installed);
    return $installed;
  }

  /**
   * The pending update hooks of the profile on this database.
   *
   * Compared against the schema version the module is recorded at, which is
   * what decides whether `drush updatedb` still has work to do.
   *
   * @return list<string>
   *   The function names of the pending hooks.
   */
  protected function pendingUpdateHooks(): array {
    /** @var \Drupal\Core\Update\UpdateHookRegistry $registry */
    $registry = $this->container->get('update.update_hook_registry');

    $pending = [];
    foreach ($registry->getAvailableUpdates(self::PROFILE) as $number) {
      if ($number > $registry->getInstalledVersion(self::PROFILE)) {
        $pending[] = self::PROFILE . '_update_' . $number;
      }
    }

    sort($pending);
    return $pending;
  }

  /**
   * The phase whose recorded state the current test compares against.
   *
   * Overridden by the test classes that need a baseline other than S1.
   */
  protected function referencePhase(): string {
    return 'S1';
  }

  /**
   * Records the current state under the given phase name.
   *
   * @param string $phase
   *   The phase name.
   *
   * @return string
   *   The recorded directory.
   */
  protected function recordPhase(string $phase): string {
    // The configuration is read through the configuration factory and the
    // crops and the state through the database, so a save the scenario made in
    // this process is already visible here. The scenario's own state resets
    // happen in reloadSiteState(), which rebuilds the container, so nothing
    // stale is read.
    return $this->recorder->record($phase);
  }

  /**
   * Asserts that no watchdog error was logged since the test started.
   *
   * Every phase of a lifecycle scenario has to stay free of logged errors: a
   * step that completes but logs an error is a finding on its own.
   */
  protected function assertNoNewWatchdogErrors(): void {
    // A phase name of its own, removed again below: the check has to happen
    // after the step it checks, but it must not overwrite a phase a scenario
    // is about to compare against.
    $phase = 'watchdog-check-' . uniqid('', FALSE);
    $this->recordPhase($phase);
    $current = $this->recorder->readDump($phase, 'watchdog_errors.txt');
    $new = array_slice($current, count($this->watchdogErrorsBeforeTest));
    // readDump() reports an absent file as an empty list, while a present but
    // empty file still holds the trailing newline of the empty write.
    $new = array_values(array_filter($new, static fn (string $line): bool => trim($line) !== ''));

    $this->assertSame([], $new, sprintf(
      "New watchdog errors since the start of this test:\n%s\nErrors that were already on the database before the test:\n%s",
      $new === [] ? '(none)' : implode("\n", $new),
      $this->watchdogErrorsBeforeTest === [] ? '(none)' : implode("\n", $this->watchdogErrorsBeforeTest),
    ));

    $this->removePhase($phase);
  }

  /**
   * Removes a recorded phase.
   *
   * @param string $phase
   *   The phase name.
   */
  protected function removePhase(string $phase): void {
    $directory = $this->recorder->phaseDirectory($phase);
    if (!is_dir($directory)) {
      return;
    }

    $entries = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($entries as $entry) {
      /** @var \SplFileInfo $entry */
      $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($directory);
  }

  /**
   * Installs modules through the user interface.
   *
   * Uses /admin/modules and submits the form, the way a site builder does,
   * rather than the module installer service.
   *
   * Core splits this into two requests when a module has dependencies that
   * are not installed yet: the first shows the confirm-dependencies page,
   * the second performs the install. Both are followed, so a module with
   * dependencies is installed through the same two clicks a site builder
   * makes. $this->submitForm() only follows the first one.
   *
   * @param list<string> $modules
   *   The machine names of the modules to install.
   */
  protected function installModulesThroughUi(array $modules): void {
    $this->drupalGet('/admin/modules');
    $values = [];
    foreach ($modules as $module) {
      // Checkboxes of the extendable modules form are tree parents, so they
      // are addressed by their element id rather than by their name.
      $values['modules[' . $module . '][enable]'] = TRUE;
    }

    $this->submitForm($values, 'Install');

    $confirmations = 0;
    while ($this->isOnConfirmDependenciesPage() && $confirmations++ < 5) {
      // The confirm page's checkbox names the modules to install with, in a
      // form-specific key, so the whole form is submitted as-is.
      $this->submitForm([], 'Continue');
    }

    $this->assertFalse(
      $this->isOnConfirmDependenciesPage(),
      'The install did not end on the dependency confirmation page.',
    );
    // Submitting a checkbox does not prove the module got installed, so the
    // result is read back from the site.
    $this->reloadSiteState();
    foreach ($modules as $module) {
      $this->assertTrue($this->moduleIsInstalled($module), sprintf('%s is installed after the form submission.', $module));
    }
  }

  /**
   * Whether the browser sits on the confirm-dependencies page.
   */
  private function isOnConfirmDependenciesPage(): bool {
    return str_contains($this->getSession()->getCurrentUrl(), 'admin/modules/list/confirm');
  }

  /**
   * Uninstalls modules through the user interface.
   *
   * Uses /admin/modules/uninstall and submits the confirmation form. That
   * form checks a flat `modules[name]` checkbox per module.
   *
   * @param list<string> $modules
   *   The machine names of the modules to uninstall.
   */
  protected function uninstallModulesThroughUi(array $modules): void {
    $this->drupalGet('/admin/modules/uninstall');
    // The uninstall confirm form checks one flat "uninstall[name]" checkbox
    // per module, mirrored by an element id.
    $values = [];
    foreach ($modules as $module) {
      $values[$this->uninstallCheckboxId($module)] = TRUE;
    }

    $this->submitForm($values, 'Uninstall');

    // Core asks for a second confirmation listing what the uninstall will
    // delete, so a module is uninstalled through the same sequence of clicks
    // a site builder makes.
    $confirmations = 0;
    while ($this->isOnUninstallConfirmPage() && $confirmations++ < 5) {
      $this->submitForm([], 'Uninstall');
    }

    $this->assertFalse(
      $this->isOnUninstallConfirmPage(),
      'The uninstall did not end on the confirmation page.',
    );
    // A module that still has dependents shows a disabled checkbox, and
    // submitting it uninstalls nothing without any error, so the result is
    // read back from the site.
    $this->reloadSiteState();
    foreach ($modules as $module) {
      $this->assertFalse($this->moduleIsInstalled($module), sprintf('%s is uninstalled after the form submission.', $module));
    }
  }

  /**
   * Whether the browser sits on the uninstall confirmation page.
   */
  private function isOnUninstallConfirmPage(): bool {
    return str_contains($this->getSession()->getCurrentUrl(), 'admin/modules/uninstall/confirm');
  }

  /**
   * Asserts that two recorded phases hold the same state.
   *
   * The comparison covers the configuration export and the data dumps.
   * Watchdog errors are left to assertNoNewWatchdogErrors(). $notes documents
   * what a reader
   * should look at if this fails, and nothing is skipped on its account - the
   * differences are reported either way.
   *
   * @param string $later
   *   The phase name of the state under test.
   * @param string $earlier
   *   The phase name of the state it has to match.
   * @param list<string> $notes
   *   Hints for whoever reads a failure, printed with it.
   * @param array<string, string> $expectedDifferences
   *   Paths below a phase directory that are expected to differ, mapped to
   *   why. A difference in one of them is the expected outcome rather than a
   *   finding; a difference anywhere else fails.
   */
  protected function assertPhasesMatch(string $later, string $earlier, array $notes = [], array $expectedDifferences = []): void {
    foreach ([$later, $earlier] as $phase) {
      $this->assertTrue(
        $this->recorder->hasPhase($phase),
        sprintf('The phase %s is recorded, so there is something to compare.', $phase),
      );
    }

    // Watchdog errors are not part of the comparison: a phase that logged an
    // error is a finding of its own, raised by assertNoNewWatchdogErrors(),
    // and asserting on the baseline's errors here would only report them a
    // second time with a message that points at the wrong phase.
    $differences = [];

    foreach ($this->recordedDataFiles() as $relativePath) {
      $before = $this->recorder->readDump($earlier, $relativePath);
      $after = $this->recorder->readDump($later, $relativePath);
      if ($before !== $after) {
        $differences[$relativePath] = [
          'only_in_' . $earlier => array_values(array_diff($before, $after)),
          'only_in_' . $later => array_values(array_diff($after, $before)),
        ];
      }
    }

    $differences += $this->configDifferences($later, $earlier);

    foreach ($expectedDifferences as $relativePath => $reason) {
      if (isset($differences[$relativePath])) {
        // Named in $expectedDifferences, so its presence is the expected
        // outcome rather than a finding. What it contains is still reported
        // below, so an unexpected amount of difference in it is visible.
        $notes[] = sprintf('Expected difference in %s: %s', $relativePath, $reason);
        unset($differences[$relativePath]);
      }
    }

    $this->assertSame([], $differences, sprintf(
      "%s has the same state as %s.%s",
      $later,
      $earlier,
      $notes === [] ? '' : "\n" . implode("\n", $notes),
    ));
  }

  /**
   * The data dumps a phase comparison covers.
   *
   * @return list<string>
   *   The paths below a phase directory.
   */
  protected function recordedDataFiles(): array {
    return [
      'data/crop_field_data.txt',
      'data/paragraph_field_entity_popover.txt',
      'data/file_usage.txt',
      'data/state.txt',
    ];
  }

  /**
   * The configuration objects that differ between two phases.
   *
   * Only the keys that actually differ are returned, so a failure message is
   * readable. The objects are compared in their normalised form (see
   * ConfigurationNormaliser), so the uuid and the _core stamp do not count as
   * a difference, and neither does the key order of a mapping.
   *
   * @param string $later
   *   The phase name of the state under test.
   * @param string $earlier
   *   The phase name of the baseline.
   *
   * @return array<string, array{only_in_baseline: list<string>, only_in_state: list<string>}>
   *   The differences, keyed by configuration name.
   */
  protected function configDifferences(string $later, string $earlier): array {
    $differences = [];

    $names = array_unique([...$this->configNamesIn($earlier), ...$this->configNamesIn($later)]);
    sort($names);

    foreach ($names as $name) {
      $before = $this->configRawData($earlier, $name);
      $after = $this->configRawData($later, $name);

      if ($before === NULL || $after === NULL) {
        $differences[$name] = [
          'only_in_baseline' => $before === NULL ? [] : ['<present>'],
          'only_in_state' => $after === NULL ? [] : ['<present>'],
        ];
        continue;
      }

      $before = ConfigurationNormaliser::normalise($before);
      $after = ConfigurationNormaliser::normalise($after);

      if ($before === $after) {
        continue;
      }

      $differences[$name] = [
        'only_in_baseline' => $this->describeValueDifference($before, $after),
        'only_in_state' => $this->describeValueDifference($after, $before),
      ];
    }

    return $differences;
  }

  /**
   * Compares two phases, limited to the configuration a third phase changed.
   *
   * The objects that differ between $changedPhase and $changeBaseline are the
   * ones the path that produced $changedPhase touched: created, changed or
   * deleted. Only those are compared between $later and $earlier and counted as
   * a finding. Every other object that differs between the two phases is
   * returned by name, because it reflects the different starting points of the
   * two paths rather than a loss by the module.
   *
   * @param string $later
   *   The phase name of the state under test.
   * @param string $earlier
   *   The phase name it has to match for the touched objects.
   * @param string $changedPhase
   *   The phase whose changes define the touched objects.
   * @param string $changeBaseline
   *   The phase $changedPhase is measured against.
   *
   * @return array{touched: array<string, array{only_in_baseline: list<string>, only_in_state: list<string>}>, untouched: list<string>}
   *   The differences of the touched objects keyed by name, and the names of
   *   the untouched objects that differ.
   */
  protected function configDifferencesOfChangedObjects(string $later, string $earlier, string $changedPhase, string $changeBaseline): array {
    $touchedNames = array_keys($this->configDifferences($changedPhase, $changeBaseline));
    $differences = $this->configDifferences($later, $earlier);

    return [
      'touched' => array_intersect_key($differences, array_flip($touchedNames)),
      'untouched' => array_values(array_diff(array_keys($differences), $touchedNames)),
    ];
  }

  /**
   * The values of $left that are not in $right, as readable lines.
   *
   * @param array<string, mixed> $left
   *   The values of one side.
   * @param array<string, mixed> $right
   *   The values of the other side.
   *
   * @return list<string>
   *   One line per differing key.
   */
  protected function describeValueDifference(array $left, array $right): array {
    $lines = [];
    foreach ($left as $key => $value) {
      if (array_key_exists($key, $right) && $right[$key] === $value) {
        continue;
      }

      $lines[] = sprintf('%s: %s', $key, $this->scalarToString($value));
    }

    foreach ($right as $key => $value) {
      if (!array_key_exists($key, $left)) {
        $lines[] = sprintf('%s: (absent)', $key);
      }
    }

    sort($lines);
    return $lines;
  }

  /**
   * Renders a configuration value for a failure message.
   *
   * @param mixed $value
   *   The value.
   */
  protected function scalarToString(mixed $value): string {
    if (is_scalar($value) || $value === NULL) {
      return var_export($value, TRUE);
    }

    return json_encode($value) ?: get_debug_type($value);
  }

  /**
   * The configuration names of a recorded phase.
   *
   * @param string $phase
   *   The phase name.
   *
   * @return list<string>
   *   The configuration names, without the .yml suffix.
   */
  protected function configNamesIn(string $phase): array {
    $directory = $this->recorder->phaseDirectory($phase) . '/config';
    if (!is_dir($directory)) {
      return [];
    }

    $names = [];
    $entries = scandir($directory) ?: [];
    foreach ($entries as $entry) {
      if (str_ends_with($entry, '.yml')) {
        $names[] = substr($entry, 0, -strlen('.yml'));
      }
    }

    sort($names);
    return $names;
  }

  /**
   * The recorded configuration data of one object.
   *
   * @param string $phase
   *   The phase name.
   * @param string $name
   *   The configuration name.
   *
   * @return array<string, mixed>|null
   *   The configuration data, or NULL if the object is not in that phase.
   */
  protected function configRawData(string $phase, string $name): ?array {
    $path = $this->recorder->phaseDirectory($phase) . '/config/' . $name . '.yml';
    if (!is_file($path)) {
      return NULL;
    }

    $contents = file_get_contents($path);
    $data = $contents === FALSE ? NULL : Yaml::decode($contents);
    return is_array($data) ? $data : NULL;
  }

  /**
   * Whether a recorded phase has a configuration object.
   *
   * @param string $phase
   *   The phase name.
   * @param string $name
   *   The configuration name.
   */
  protected function configExistsIn(string $phase, string $name): bool {
    return is_file($this->recorder->phaseDirectory($phase) . '/config/' . $name . '.yml');
  }

  /**
   * Follows the batch of the crop migration to completion.
   *
   * Drupal's batch API renders each round as a page with a meta refresh back
   * to the batch processor. A test that stops at the first response sees one
   * round and a migration that silently did far less than it promised, so the
   * meta refresh is followed until the batch is done.
   *
   * The loop is bounded: a batch that never reports itself as finished would
   * otherwise hang the run.
   */
  protected function followBatch(): void {
    $rounds = 0;
    $maximumRounds = 200;

    while ($rounds++ < $maximumRounds) {
      $page = $this->getSession()->getPage();
      $batchUrl = $page->find('css', 'meta[http-equiv="refresh"]');

      if ($batchUrl === NULL) {
        // The batch is done: the last round redirected back to a page that is
        // not a batch page any more.
        $this->assertNoBatchError();
        return;
      }

      $content = $batchUrl->getAttribute('content') ?? '';
      if (!preg_match('/url=([^;]+)/', $content, $matches)) {
        $this->assertNoBatchError();
        return;
      }

      $this->drupalGet(trim($matches[1], " '\""));
    }

    $this->fail(sprintf('The crop migration batch did not finish after %d rounds.', $maximumRounds));
  }

  /**
   * Asserts that the batch did not report an error.
   */
  protected function assertNoBatchError(): void {
    $this->assertSession()->pageTextNotContains('An error occurred while migrating image crops.');
  }

  /**
   * The path of a file a scenario writes its observations to.
   *
   * Written into the project's own directory rather than into the recorded state
   * tree, because the orchestrator clears that tree before every scenario: a
   * report the point of which is to be read after the run would be gone before
   * anyone looks at it.
   *
   * @param string $name
   *   The file name.
   */
  protected function observationPath(string $name): string {
    // The logs sit next to the recorded phases, so the phases directory's
    // parent is the state root.
    $directory = dirname(LifecycleStateRecorder::STATE_ROOT) . '/logs';
    if (!is_dir($directory) && !mkdir($directory, 0o777, TRUE) && !is_dir($directory)) {
      $this->fail('Could not create the observation directory.');
    }

    return $directory . '/' . $name;
  }

  /**
   * The module names a recorded phase had enabled.
   *
   * Read back from the recorded core.extension rather than from the running
   * container, so it describes the state of that phase and not the state the
   * database happens to be in now.
   *
   * @param string $phase
   *   The phase name.
   *
   * @return list<string>
   *   The enabled module names, sorted.
   */
  protected function enabledModulesIn(string $phase): array {
    $extension = $this->configRawData($phase, 'core.extension');
    $modules = array_map(strval(...), array_keys($extension['module'] ?? []));
    sort($modules);
    return $modules;
  }

  /**
   * The element id of a module's checkbox on the uninstall confirm form.
   *
   * Core builds it from the machine name by replacing every underscore with a
   * dash, see SystemController::buildModulesUninstallForm().
   *
   * @param string $module
   *   The machine name of the module.
   */
  private function uninstallCheckboxId(string $module): string {
    return 'edit-uninstall-' . str_replace('_', '-', $module);
  }

  /**
   * Whether a module is installed.
   *
   * @param string $module
   *   The machine name of the module.
   */
  protected function moduleIsInstalled(string $module): bool {
    return \Drupal::moduleHandler()->moduleExists($module);
  }

  /**
   * Rebuilds the in-process container and reloads the Drupal root.
   *
   * The test process builds its own container, separate from the web request
   * the browser just made. After a request has installed or uninstalled a
   * module, the test process's container still holds the module list, the
   * services and the hooks of the state before that request, so every check
   * against the extension list, the container or a module's service class
   * would read the old state.
   *
   * Calling this is not a way around the user interface: the browser has
   * already performed the change by the time this runs. It only makes the
   * test process see what the site now is.
   */
  protected function reloadSiteState(): void {
    // Rebuilds the container inside the running kernel, so the new module's
    // services and hooks are in place for the checks that follow.
    $this->container->get('kernel')->rebuildContainer();
    $this->container = \Drupal::getContainer();

    $this->recorder = new LifecycleStateRecorder(
      $this->container->get('database'),
      $this->container->get('config.factory'),
      $this->container->get('state'),
    );
  }

}
