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
  drush --yes --ansi "$@"
}

main() {
  if [[ -f /tmp/db.sql ]]; then
    echo "Delete existing sql-file."
    rm -f /tmp/db.sql
  fi
  if [[ ! -f /tmp/db.sql.gz ]]; then
    echo "No dump found" >&2
    exit 1
  fi

  cd "$DOCROOT"

  _drush sql:create
  _drush sql:query --file=/tmp/db.sql.gz
  rm -f /tmp/db.sql
  if [[ -f "$__DIR__/post_tasks.sh" ]]; then
    exec bash ${DEBUG:+-o xtrace} "$__DIR__/post_tasks.sh"
  fi
}

main "$@"
