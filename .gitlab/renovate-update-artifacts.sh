#!/usr/bin/env bash
# Regenerates committed artifacts on a Renovate branch: Drupal config exports
# (only when composer files changed) and theme build output (only for themes
# whose npm files changed). Runs from the repository root. CI copies this file
# and .gitlab/ci-renovate-functions.sh to /tmp first, because they must survive
# checking out the merge base.
set -o errexit -o nounset -o pipefail

readonly THEME_DIRECTORIES=(profile/themes/opcult profile/themes/openculturas_base)
# Copied to /tmp together with this script.
# shellcheck source=ci-renovate-functions.sh
source "$(dirname "${BASH_SOURCE[0]}")/ci-renovate-functions.sh"

update_database_artifacts() {
  local merge_base="${1}"
  # The merge base may not contain these files yet.
  cp .gitlab/ci-import-database.sh .gitlab/ci.settings.local.php /tmp/
  git checkout --detach "${merge_base}"
  composer install --no-interaction --no-progress --ansi
  bash /tmp/ci-import-database.sh /tmp/ci.settings.local.php
  git checkout "${CI_COMMIT_BRANCH}"
  composer install --no-interaction --no-progress --ansi
  vendor/bin/drush updatedb --yes --ansi
  # Resave all config entities so schema changes made by updated modules land
  # in this commit instead of in the diff of the next unrelated change.
  vendor/bin/drush php:script scripts/resave_config_entities.php --ansi
  vendor/bin/drush config:export --yes --ansi
  composer run cde
}

build_theme() {
  local theme_directory="${1}"
  echo "Building ${theme_directory}"
  (
    cd "${theme_directory}"
    npm ci --no-audit
    npm run build
  )
}

main() {
  local merge_base
  merge_base="$(determine_renovate_merge_base)"
  echo "Merge base: ${merge_base}"

  local composer_changed=false
  local -a changed_themes=()
  if [[ -n ${UPDATE_ARTIFACTS:-} ]]; then
    composer_changed=true
    changed_themes=("${THEME_DIRECTORIES[@]}")
  else
    local theme_directory
    if ! git diff --quiet "${merge_base}" HEAD -- composer.json composer.lock; then
      composer_changed=true
    fi
    for theme_directory in "${THEME_DIRECTORIES[@]}"; do
      if ! git diff --quiet "${merge_base}" HEAD -- "${theme_directory}/package.json" "${theme_directory}/package-lock.json"; then
        changed_themes+=("${theme_directory}")
      fi
    done
  fi

  if [[ ${composer_changed} == false && ${#changed_themes[@]} -eq 0 ]]; then
    echo "Composer and theme dependency files match the merge base, nothing to update."
    exit 0
  fi

  if [[ ${composer_changed} == true ]]; then
    update_database_artifacts "${merge_base}"
  elif [[ ${#changed_themes[@]} -gt 0 ]]; then
    # The theme styles import Composer packages from web/libraries.
    composer install --no-interaction --no-progress --ansi
  fi
  local changed_theme
  for changed_theme in "${changed_themes[@]}"; do
    build_theme "${changed_theme}"
  done

  git add --all config/sync profile
  if ! git diff --cached --quiet --exit-code; then
    git commit --message 'chore(renovate): update artifacts'
    git push "https://oauth2:${GITLAB_TOKEN}@${CI_SERVER_HOST}/${CI_PROJECT_PATH}.git" "HEAD:${CI_COMMIT_BRANCH}"
  fi
}

main "$@"
