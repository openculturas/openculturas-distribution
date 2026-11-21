#!/usr/bin/env bash
# Installs a new site with the OpenCulturas profile and its demo content into
# the MySQL service of a CI job, like `composer run si` does locally. Runs from
# the repository root, after `composer install`.
set -o errexit -o nounset -o pipefail

if [[ -n ${DEBUG:-} ]]; then
  set -o xtrace
fi

main() {
  mkdir --parents web/sites/default/files/private web/sites/default/files/translations
  cp .ddev/settings.php web/sites/default/settings.php
  cp .gitlab/ci.settings.local.php web/sites/default/settings.local.php
  # Without a profile argument, the installer selects the distribution
  # profile.
  vendor/bin/drush site:install --yes --ansi
}

main "$@"
