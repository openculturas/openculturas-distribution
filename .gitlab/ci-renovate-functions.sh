#!/usr/bin/env bash
# Shared functions for CI jobs on Renovate branches. Source this file, do not
# execute it. Needs GITLAB_TOKEN and the predefined CI variables.

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

# Prints the merge base of HEAD and the target branch of the current Renovate
# branch.
determine_renovate_merge_base() {
  local target_branch target_commit
  target_branch="$(determine_target_branch)"
  git fetch --quiet origin "${target_branch}" >&2
  target_commit="$(git rev-parse FETCH_HEAD)"
  echo "Baseline: ${target_branch}" >&2
  determine_merge_base "${target_branch}" "${target_commit}"
}
