<?php

/**
 * @file
 * Checks that README.md lists every inline package library of composer.json.
 *
 * Compares the names of the "type": "package" repositories in composer.json
 * with the first column of the table in the "Third-party libraries" section
 * of README.md. Exits with code 1 if a library is missing or superfluous.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

$composerJson = json_decode(
  (string) file_get_contents($projectRoot . '/composer.json'),
  TRUE,
  512,
  JSON_THROW_ON_ERROR,
);

$composerLibraries = [];
foreach ($composerJson['repositories'] ?? [] as $repository) {
  if (($repository['type'] ?? NULL) === 'package' && isset($repository['package']['name'])) {
    $composerLibraries[] = $repository['package']['name'];
  }
}

$readme = (string) file_get_contents($projectRoot . '/README.md');
if (preg_match('/^## Third-party libraries\n(.*?)(?=^## )/ms', $readme, $sectionMatch) !== 1) {
  fwrite(STDERR, "README.md has no \"## Third-party libraries\" section.\n");
  exit(1);
}

$readmeLibraries = [];
foreach (explode("\n", $sectionMatch[1]) as $line) {
  if (preg_match('/^\|\s*([^|\s]+)\s*\|/', $line, $cellMatch) !== 1) {
    continue;
  }
  $cell = $cellMatch[1];
  // Skip the header row and the separator row.
  if ($cell === 'Library' || preg_match('/^-+$/', $cell) === 1) {
    continue;
  }
  $readmeLibraries[] = $cell;
}

$missingInReadme = array_diff($composerLibraries, $readmeLibraries);
$superfluousInReadme = array_diff($readmeLibraries, $composerLibraries);

if ($missingInReadme === [] && $superfluousInReadme === []) {
  echo "README.md lists all " . count($composerLibraries) . " inline package libraries of composer.json.\n";
  exit(0);
}

foreach ($missingInReadme as $library) {
  fwrite(STDERR, "Missing in README.md: $library\n");
}
foreach ($superfluousInReadme as $library) {
  fwrite(STDERR, "Not an inline package in composer.json: $library\n");
}
fwrite(STDERR, "Update the \"Third-party libraries\" table in README.md.\n");
exit(1);
