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
readonly DOCROOT="$__DIR__/../../web"

main() {
  cd "$DOCROOT"

  if [ -d "themes/custom/myowntheme" ]; then
    echo "Clean up themes/custom/myowntheme"
    rm --recursive "themes/custom/myowntheme"
  fi

  ../vendor/bin/dr generate-theme --name MyOwnTheme --description "My own theme based on opcult for Openculturas" --path themes/custom --starterkit opcult_starterkit myowntheme
  cd themes/custom/myowntheme
  npm install
  npm run build
}

main "$@"
