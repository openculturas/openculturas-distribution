#!/usr/bin/env bash

set -o nounset
set -o pipefail
set -o errexit

if [[ -n ${DEBUG:-} ]]; then
  set -o xtrace
fi

readonly __DIR__="$(
  cd "$(dirname "${0}")"
  pwd
)"

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
    chown -R www-data.www-data /app/data
    _drush cache:rebuild
    _drush pm:install stage_file_proxy
  fi
  _drush deploy
  if [[ -n ${CLOUDRON:-} ]]; then
    chown -R www-data.www-data /app/data
  fi
}

main "$@"
