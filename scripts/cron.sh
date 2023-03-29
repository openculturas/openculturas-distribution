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
readonly DOCROOT="$__DIR__/../web"

_drush() {
  PATH="$__DIR__/../vendor/bin:$PATH"
  export PATH
  drush --yes --ansi --uri="$CLOUDRON_APP_DOMAIN" "$@"
}

main() {
  cd "$DOCROOT" && _drush core:cron
}

main "$@"
