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
  if [[ -n ${CLOUDRON:-} ]]; then
    chown -R www-data:www-data /app/data
  fi
}

main "$@"
