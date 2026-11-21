#!/usr/bin/env bash
# Imports the shared database snapshot into the MySQL service of a CI job and
# brings it to the state of the checked-out code (scripts/db_import.sh runs
# `drush deploy`). Runs from the repository root, after `composer install`.
#
# Usage: ci-import-database.sh [settings.local.php]
#
# The optional argument is the settings.local.php file to install. It defaults
# to .gitlab/ci.settings.local.php. Callers that check out an older commit
# first copy this script and the settings file to /tmp and pass that path.
#
# The database repository is private. The job token can clone it, because the
# repository allows job tokens of this project. DATABASE_REPOSITORY_URL
# overrides the clone URL, e.g. for local runs where CI_SERVER_URL points to a
# mock server.
set -o errexit -o nounset -o pipefail

if [[ -n ${DEBUG:-} ]]; then
  set -o xtrace
fi

readonly DATABASE_REPOSITORY="${DATABASE_REPOSITORY_URL:-${CI_SERVER_URL/:\/\//://gitlab-ci-token:${CI_JOB_TOKEN}@}/lvsn/openculturas-distribution-database.git}"

main() {
  local settings_file="${1:-.gitlab/ci.settings.local.php}"
  local database_directory
  database_directory="$(mktemp --directory)"
  git clone --depth 1 "${DATABASE_REPOSITORY}" "${database_directory}"
  mv "${database_directory}/db.sql.gz" /tmp/db.sql.gz
  rm --recursive --force "${database_directory}"

  mkdir --parents web/sites/default/files/private web/sites/default/files/translations
  cp .ddev/settings.php web/sites/default/settings.php
  cp "${settings_file}" web/sites/default/settings.local.php
  bash scripts/db_import.sh
  # Modules installed by the deploy import make Locale rewrite language.de
  # config afterwards. A second import restores the state of the code.
  vendor/bin/drush config:import --yes --ansi
}

main "$@"
