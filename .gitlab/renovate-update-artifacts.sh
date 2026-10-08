#!/usr/bin/env bash
# Regenerates committed artifacts on a Renovate branch: Drupal config exports
# (only when composer files changed) and theme build output (only for themes
# whose npm files changed). Runs from the repository root. CI copies this file
# to /tmp first, because it must survive checking out the target branch.
set -o errexit -o nounset -o pipefail

readonly THEME_DIRECTORIES=(profile/themes/opcult profile/themes/openculturas_base)
readonly DEEPEN_STEP=50
readonly DEEPEN_ATTEMPTS=4

determine_target_branch() {
  local target_branch
  target_branch="$(curl --silent --fail --header "PRIVATE-TOKEN: ${GITLAB_TOKEN}" \
    "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/merge_requests?state=opened&source_branch=${CI_COMMIT_BRANCH}" \
    | php -r '$requests = json_decode(stream_get_contents(STDIN), TRUE); echo $requests[0]["target_branch"] ?? "";')"
  if [[ -z ${target_branch} && ${CI_COMMIT_BRANCH} =~ ^renovate_([0-9]+\.[0-9]+\.x)- ]]; then
    target_branch="${BASH_REMATCH[1]}"
  fi
  if [[ -z ${target_branch} ]]; then
    echo "Cannot determine merge request target branch for ${CI_COMMIT_BRANCH}" >&2
    exit 1
  fi
  echo "${target_branch}"
}

# Renovate rebases its branches only on conflicts, so a branch often lags the
# target branch. Comparing against the merge base ignores changes that happened
# only on the target branch. The shallow clone may not reach the merge base:
# deepen in steps, then fetch the full history as a last resort.
determine_merge_base() {
  local target_branch="${1}"
  local target_commit="${2}"
  local merge_base attempt
  local start_seconds="${SECONDS}"
  merge_base="$(git merge-base "${target_commit}" HEAD || true)"
  for ((attempt = 1; attempt <= DEEPEN_ATTEMPTS && ${#merge_base} == 0; attempt++)); do
    echo "Merge base not found, deepening history by ${DEEPEN_STEP} commits (attempt ${attempt}/${DEEPEN_ATTEMPTS})" >&2
    git fetch --quiet --deepen="${DEEPEN_STEP}" origin "${target_branch}" >&2
    merge_base="$(git merge-base "${target_commit}" HEAD || true)"
  done
  if [[ -z ${merge_base} && $(git rev-parse --is-shallow-repository) == true ]]; then
    echo "Merge base not found, fetching full history" >&2
    git fetch --quiet --unshallow origin "${target_branch}" >&2
    merge_base="$(git merge-base "${target_commit}" HEAD || true)"
  fi
  if [[ -z ${merge_base} ]]; then
    echo "Cannot determine merge base of ${target_branch} and ${CI_COMMIT_BRANCH}" >&2
    exit 1
  fi
  echo "Merge base determined in $((SECONDS - start_seconds)) seconds" >&2
  echo "${merge_base}"
}

update_database_artifacts() {
  local merge_base="${1}"
  git clone --depth 1 "${CI_SERVER_URL}/lvsn/openculturas-distribution-database.git" /tmp/database
  mv /tmp/database/db.sql.gz /tmp/db.sql.gz
  cp .gitlab/renovate.settings.local.php /tmp/renovate.settings.local.php
  git checkout --detach "${merge_base}"
  composer install --no-interaction --no-progress --ansi
  mkdir --parents web/sites/default/files/private web/sites/default/files/translations
  cp .ddev/settings.php web/sites/default/settings.php
  cp /tmp/renovate.settings.local.php web/sites/default/settings.local.php
  bash scripts/db_import.sh
  # Modules installed by the deploy import make Locale rewrite language.de
  # config afterwards. A second import restores the merge base state.
  vendor/bin/drush config:import --yes --ansi
  git checkout "${CI_COMMIT_BRANCH}"
  composer install --no-interaction --no-progress --ansi
  vendor/bin/drush updatedb --yes --ansi
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
  local target_branch
  target_branch="$(determine_target_branch)"
  git fetch origin "${target_branch}"
  local target_commit
  target_commit="$(git rev-parse FETCH_HEAD)"
  local merge_base
  merge_base="$(determine_merge_base "${target_branch}" "${target_commit}")"
  echo "Baseline: ${target_branch} at ${merge_base}"

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
    echo "Composer and theme dependency files match the merge base with ${target_branch}, nothing to update."
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
