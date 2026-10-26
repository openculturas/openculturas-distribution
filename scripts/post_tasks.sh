#!/usr/bin/env bash

set -o nounset
set -o pipefail
set -o errexit

if [[ -n ${DEBUG:-} ]]; then
  set -o xtrace
fi

__DIR__="$(
  cd "$(dirname "${0}")"
  pwd
)"
readonly __DIR__

_drush() {
  PATH="$__DIR__/../vendor/bin:$__DIR__/../bin:$PATH"
  export PATH
  drush --ansi --yes "$@"
}

# Reads the module list completely before matching: piping drush into
# `grep --quiet` lets grep exit on the first hit, drush then dies of SIGPIPE
# and pipefail turns a match into a failure. errexit is suspended inside a
# function called as an `if` condition, so a drush failure has to exit
# explicitly, otherwise it would read as "module not enabled".
module_enabled() {
  local enabled_modules
  enabled_modules="$(_drush pm:list --status=enabled --type=module --field=name)" || exit "$?"
  grep --quiet --line-regexp --fixed-strings "$1" <<<"$enabled_modules"
}

main() {
  DOCROOT="${__DIR__}/../web"
  cd "$DOCROOT"
  if [[ -n ${CLOUDRON:-} ]]; then
    mkdir -p /app/data/private
    mkdir -p /app/data/files/translations
    chown -R www-data:www-data /app/data
  fi
  _drush locale:import de profiles/contrib/openculturas-profile/modules/custom/openculturas_custom/translations/de.po
  _drush deploy
  if module_enabled openculturas_responsive_images; then
    _drush openculturas-responsive-images:migrate-crops
  fi
  # Fills empty popover toggles on every paragraph bundle, deliberately
  # skipping the per-bundle choice of the settings form. This script is
  # export-ignored (.gitattributes), so it only runs on this repository's own
  # instances; sites installing the distribution still decide per bundle.
  if module_enabled entity_popover; then
    _drush entity-popover:backfill-paragraphs
  fi
  if [[ -n ${CLOUDRON:-} ]]; then
    chown -R www-data:www-data /app/data
  fi
}

main "$@"
