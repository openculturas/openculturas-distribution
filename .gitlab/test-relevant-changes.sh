#!/usr/bin/env bash
# Usage: test-relevant-changes.sh <base-revision> [<revision>]
#
# Tells whether the Composer packages of <revision> (default: HEAD) differ from
# <base-revision> in a way that can change the test result. Packages that
# only analyse or format code (IGNORED_PACKAGES) do not count. Every other
# changed package and every change to patches.lock.json counts, so an unknown
# package runs the tests.
#
# Exit codes:
#   0  relevant changes
#   10 no relevant changes
#   other: error
set -o errexit -o nounset -o pipefail

if [[ -n ${DEBUG:-} ]]; then
  set -o xtrace
fi

readonly EXIT_UNCHANGED=10

# Static analysis, coding standards and Composer tooling. Regular expression,
# matched against the full package name.
readonly IGNORED_PACKAGES='^(phpstan/.*|rector/.*|mglaman/phpstan-drupal|palantirnet/drupal-rector|tomasvotruba/.*|drupal/coder|squizlabs/php_codesniffer|slevomat/coding-standard|sirbrillig/phpcs-variable-analysis|dealerdirect/phpcodesniffer-composer-installer|php-parallel-lint/.*|ergebnis/.*|drupal/ai_best_practices)$'

# Prints one line per package of composer.lock: name, version and reference.
package_fingerprint() {
  local revision="${1}"
  git show "${revision}:composer.lock" | php -r '
    $lock = json_decode(stream_get_contents(STDIN), TRUE, 512, JSON_THROW_ON_ERROR);
    foreach (array_merge($lock["packages"], $lock["packages-dev"] ?? []) as $package) {
      $reference = $package["dist"]["reference"] ?? $package["source"]["reference"] ?? "";
      echo $package["name"], " ", $package["version"], " ", $reference, PHP_EOL;
    }
  ' | sort
}

main() {
  local base_revision="${1:?Missing base revision}"
  local revision="${2:-HEAD}"

  if ! git diff --quiet "${base_revision}" "${revision}" -- patches.lock.json; then
    echo "Patches changed."
    return 0
  fi

  local changed_packages relevant_packages
  changed_packages="$(comm -3 <(package_fingerprint "${base_revision}") <(package_fingerprint "${revision}") \
    | awk '{ print $1 }' | sort --unique)"
  relevant_packages="$(grep --invert-match --extended-regexp "${IGNORED_PACKAGES}" <<<"${changed_packages}" || true)"

  if [[ -n ${relevant_packages} ]]; then
    echo "Relevant packages changed:"
    echo "${relevant_packages}"
    return 0
  fi
  if [[ -n ${changed_packages} ]]; then
    echo "Only ignored packages changed:"
    echo "${changed_packages}"
  else
    echo "No Composer packages changed."
  fi
  return "${EXIT_UNCHANGED}"
}

main "$@"
