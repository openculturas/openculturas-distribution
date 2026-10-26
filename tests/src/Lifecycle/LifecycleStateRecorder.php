<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\State\StateInterface;

/**
 * Records the observable state of a site into a comparable directory tree.
 *
 * The recorded tree is what tests/scripts/lifecycle_test.sh diffs between phases:
 * a configuration export, a set of sorted data dumps and the watchdog errors
 * logged so far. Everything is a plain text file so the comparison is a plain
 * recursive diff and a finding is readable without this class.
 *
 * The data dumps deliberately only cover the tables the two modules under
 * test touch. Core behaviour on uninstall - removing config, purging field
 * data - is not re-implemented here, it is covered indirectly by the fact that
 * the recorded config and data come back empty.
 */
final class LifecycleStateRecorder {

  /**
   * The directory the recorded trees are written below.
   *
   * Resolves to /var/www/html/tests/.lifecycle_test_state/phases inside the
   * container, which is the project's tests/.lifecycle_test_state/phases
   * directory. The logs and the other state directories sit next to it.
   */
  public const string STATE_ROOT = '/var/www/html/tests/.lifecycle_test_state/phases';

  /**
   * The prefix of the state keys the two modules under test write.
   *
   * @var list<string>
   */
  public const array WATCHED_STATE_PREFIXES = [
    'openculturas_responsive_images',
    'entity_popover',
    'openculturas_entity_popover',
  ];

  /**
   * The crop fields of the crop entity, with the columns worth comparing.
   *
   * The crop id is left out on purpose: a duplicate created by a migration
   * gets a fresh id, and comparing ids would report every migration as a
   * change to the existing crops. Type, entity, coordinates and size are what
   * "the same crop" means.
   *
   * @var list<string>
   */
  private const array CROP_COLUMNS = [
    'type',
    'entity_type',
    'entity_id',
    'uri',
    'x',
    'y',
    'width',
    'height',
  ];

  /**
   * Constructs a LifecycleStateRecorder.
   */
  public function __construct(
    private Connection $database,
    private ConfigFactoryInterface $configurationFactory,
    private StateInterface $state,
  ) {}

  /**
   * Records the current state into the directory of the given phase.
   *
   * @param string $phase
   *   The phase name, used as the directory name.
   *
   * @return string
   *   The absolute path of the recorded tree.
   */
  public function record(string $phase): string {
    $directory = self::STATE_ROOT . '/' . $phase;
    $this->removeDirectory($directory);
    $this->createDirectory($directory);

    $this->recordConfiguration($directory . '/config');
    $this->writeLines($directory . '/data/crop_field_data.txt', $this->cropLines());
    $this->writeLines($directory . '/data/paragraph_field_entity_popover.txt', $this->paragraphPopoverLines());
    $this->writeLines($directory . '/data/file_usage.txt', $this->fileUsageLines());
    $this->writeLines($directory . '/data/state.txt', $this->stateLines());
    $this->writeLines($directory . '/watchdog_errors.txt', $this->watchdogErrorLines());

    return $directory;
  }

  /**
   * The phase names that already hold a recorded tree.
   *
   * @return list<string>
   *   The recorded phase names, sorted.
   */
  public function recordedPhases(): array {
    if (!is_dir(self::STATE_ROOT)) {
      return [];
    }

    $phases = array_values(array_diff(scandir(self::STATE_ROOT) ?: [], ['.', '..']));
    sort($phases);
    return $phases;
  }

  /**
   * Whether a phase has already been recorded.
   *
   * @param string $phase
   *   The phase name.
   */
  public function hasPhase(string $phase): bool {
    return in_array($phase, $this->recordedPhases(), TRUE);
  }

  /**
   * The absolute path of a recorded phase.
   *
   * @param string $phase
   *   The phase name.
   */
  public function phaseDirectory(string $phase): string {
    return self::STATE_ROOT . '/' . $phase;
  }

  /**
   * Reads a recorded data dump back.
   *
   * @param string $phase
   *   The phase name.
   * @param string $relativePath
   *   The path below the phase directory, for example
   *   'data/crop_field_data.txt'.
   *
   * @return list<string>
   *   The lines of the dump, empty if the file does not exist.
   */
  public function readDump(string $phase, string $relativePath): array {
    $path = $this->phaseDirectory($phase) . '/' . $relativePath;
    if (!is_file($path)) {
      return [];
    }

    $contents = file($path, FILE_IGNORE_NEW_LINES);
    return $contents === FALSE ? [] : $contents;
  }

  /**
   * Exports every configuration object into the given directory.
   *
   * Written one file per configuration name, sorted by name, the same layout
   * `drush config:export` produces, so the recorded tree can be diffed against
   * a real export as well.
   *
   * @param string $directory
   *   The directory to write the configuration into.
   */
  private function recordConfiguration(string $directory): void {
    $this->createDirectory($directory);

    $names = $this->configurationFactory->listAll();
    sort($names);

    // Same encoder core's own FileStorage uses, so a recorded tree is
    // byte-identical to a `drush config:export` of the same state.
    $serializer = new Yaml();
    foreach ($names as $name) {
      $configuration = $this->configurationFactory->get($name);
      if ($configuration->isNew()) {
        continue;
      }

      file_put_contents(
        $directory . '/' . $name . '.yml',
        $serializer->encode($configuration->getRawData()),
      );
    }
  }

  /**
   * The crop rows as sorted text.
   *
   * @return list<string>
   *   One line per crop.
   */
  private function cropLines(): array {
    if (!$this->tableExists('crop_field_data')) {
      return ['(table does not exist)'];
    }

    $query = $this->database->select('crop_field_data', 'crop');
    $query->fields('crop', self::CROP_COLUMNS);
    return $this->sortedLines($query);
  }

  /**
   * The paragraph popover toggle rows as sorted text.
   *
   * The table only exists while the field does, so a missing table is recorded
   * as such rather than as an empty result: "no rows" and "no field" are very
   * different states when checking that an uninstall removed the data.
   *
   * @return list<string>
   *   One line per paragraph row.
   */
  private function paragraphPopoverLines(): array {
    if (!$this->tableExists('paragraph__field_entity_popover')) {
      return ['(table does not exist)'];
    }

    $query = $this->database->select('paragraph__field_entity_popover', 'toggle');
    $query->fields('toggle', ['entity_id', 'langcode', 'field_entity_popover_value']);
    return $this->sortedLines($query);
  }

  /**
   * The file usage rows as sorted text.
   *
   * @return list<string>
   *   One line per file usage entry.
   */
  private function fileUsageLines(): array {
    if (!$this->tableExists('file_usage')) {
      return ['(table does not exist)'];
    }

    $query = $this->database->select('file_usage', 'usage');
    $query->fields('usage', ['fid', 'module', 'type', 'id', 'count']);
    return $this->sortedLines($query);
  }

  /**
   * The state entries of the two modules under test, as sorted text.
   *
   * @return list<string>
   *   One line per state entry.
   */
  private function stateLines(): array {
    if (!$this->tableExists('key_value')) {
      return ['(table does not exist)'];
    }

    // StateInterface cannot enumerate its keys, so the store is read directly.
    $query = $this->database->select('key_value', 'state');
    $query->fields('state', ['collection', 'name']);
    $query->condition('collection', 'state');
    $query->orderBy('name');

    $lines = [];
    foreach ($query->execute() ?: [] as $row) {
      if (!$this->isWatchedStateKey((string) $row->name)) {
        continue;
      }

      $lines[] = $row->name . ' = ' . json_encode($this->state->get((string) $row->name));
    }

    sort($lines);
    return $lines === [] ? ['(no watched state entries)'] : $lines;
  }

  /**
   * The watchdog rows of error severity or worse, as sorted text.
   *
   * Only severity 8 (Drupal's "Error"), since a step that completes while
   * logging an error is a finding on its own. Warnings and below are left
   * out: the modules legitimately log notices and info entries, so treating
   * those as failures would only produce noise.
   *
   * @return list<string>
   *   One line per logged error.
   */
  private function watchdogErrorLines(): array {
    $query = $this->database->select('watchdog', 'watchdog');
    $query->fields('watchdog', ['wid', 'type', 'severity', 'message']);
    // Drupal's watchdog levels: 8 is Error, the most severe. 4 is a
    // warning and below it sit notice and info, which the modules legitimately
    // log (a module install is an info entry). Only 8 and above count.
    $query->condition('severity', 8, '>=');
    // The variables are serialized in and not worth comparing; the message
    // and the type already identify the source.
    $query->orderBy('wid');
    $result = $query->execute();

    $lines = [];
    foreach ($result ?: [] as $row) {
      $lines[] = sprintf(
        'wid=%s severity=%s type=%s message=%s',
        $row->wid,
        $row->severity,
        $row->type,
        preg_replace('/\s+/', ' ', strip_tags((string) $row->message)),
      );
    }

    return $lines;
  }

  /**
   * Whether a state key belongs to one of the modules under test.
   *
   * @param string $key
   *   The state key.
   */
  private function isWatchedStateKey(string $key): bool {
    foreach (self::WATCHED_STATE_PREFIXES as $prefix) {
      if (str_starts_with($key, $prefix)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Runs a select and returns its rows as sorted, tab separated lines.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to run.
   *
   * @return list<string>
   *   One line per row, sorted so the dump does not depend on the storage
   *   engine's row order.
   */
  private function sortedLines(SelectInterface $query): array {
    $lines = [];
    foreach ($query->execute() ?: [] as $row) {
      $values = [];
      foreach ($row as $value) {
        $values[] = str_replace(["\t", "\n"], ' ', (string) $value);
      }

      $lines[] = implode("\t", $values);
    }

    sort($lines);
    return $lines;
  }

  /**
   * Whether a table exists in the active database.
   *
   * @param string $table
   *   The table name.
   */
  private function tableExists(string $table): bool {
    return $this->database->schema()->tableExists($table);
  }

  /**
   * Writes lines to a file, creating the directory.
   *
   * @param string $path
   *   The file to write.
   * @param list<string> $lines
   *   The lines to write.
   */
  private function writeLines(string $path, array $lines): void {
    $this->createDirectory(dirname($path));
    file_put_contents($path, implode("\n", $lines) . "\n");
  }

  /**
   * Creates a directory, including its parents.
   *
   * @param string $directory
   *   The directory to create.
   */
  private function createDirectory(string $directory): void {
    if (!is_dir($directory) && !mkdir($directory, 0o777, TRUE) && !is_dir($directory)) {
      throw new \RuntimeException('Could not create ' . $directory . '.');
    }
  }

  /**
   * Removes a directory and everything below it.
   *
   * @param string $directory
   *   The directory to remove.
   */
  private function removeDirectory(string $directory): void {
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

}
