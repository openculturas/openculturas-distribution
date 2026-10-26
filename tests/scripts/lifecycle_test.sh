#!/usr/bin/env bash

# Drives the lifecycle test suite for openculturas_responsive_images and
# openculturas_entity_popover.
#
# The scenarios are destructive: they install, migrate, switch, revert and
# uninstall the modules through the user interface. Every scenario therefore
# starts from the same DDEV snapshot, and the run leaves the database in a
# known state rather than in whatever the last scenario made of it.
#
# The database snapshots this script creates and restores live in
# .ddev/db_snapshots. The recorded state trees and logs live in
# tests/.lifecycle_test_state, inside the project tree, so they survive the
# container being recreated by a snapshot restore.

set -o nounset
set -o pipefail

if [[ -n ${DEBUG:-} ]]; then
  set -o xtrace
fi

readonly SCRIPT_NAME="$(basename "${BASH_SOURCE[0]}")"
readonly SCRIPT_DIRECTORY="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_DIRECTORY="$(cd "${SCRIPT_DIRECTORY}/../.." && pwd)"

readonly DEPENDENCIES=(ddev diff)

# The snapshot every scenario is built on: 3.1.2 with the branch's
# configuration imported and its update hooks still pending.
readonly BASE_SNAPSHOT='3.1.2'

# The snapshot the orchestrator creates once the update hooks have run. Every
# scenario restores this one, so a scenario never depends on what an earlier
# scenario left behind.
readonly WORKING_SNAPSHOT='lifecycle-after-updb'

# The update hooks that must be pending on BASE_SNAPSHOT.
readonly -a REQUIRED_PENDING_UPDATE_HOOKS=(
  'openculturas_update_10131'
  'openculturas_update_10132'
  'openculturas_update_10133'
  'openculturas_update_10134'
  'openculturas_update_10135'
  'openculturas_update_10136'
)

# The modules the scenarios install and uninstall. None of them may be active
# on the restored snapshot.
readonly -a MODULES_UNDER_TEST=(
  'openculturas_responsive_images'
  'openculturas_entity_popover'
  'entity_popover'
  'entity_popover_swiffy_slider'
)

# The state root of the lifecycle tests, as seen from the host and from inside
# the container. Every other state path below is derived from these two.
# The container path must match LifecycleStateRecorder::STATE_ROOT, which
# points at its phases directory.
readonly STATE_ROOT_HOST="${PROJECT_DIRECTORY}/tests/.lifecycle_test_state"
readonly STATE_ROOT="/var/www/html/tests/.lifecycle_test_state"

# Where the test classes record the state of a phase.
readonly STATE_DIRECTORY_HOST="${STATE_ROOT_HOST}/phases"
readonly STATE_DIRECTORY="${STATE_ROOT}/phases"

# Where the S1 baseline is kept between scenarios, so that clearing a
# scenario's own recorded phases cannot take the baseline with them.
readonly BASELINE_DIRECTORY_HOST="${STATE_ROOT_HOST}/baseline"
readonly BASELINE_DIRECTORY="${STATE_ROOT}/baseline"

# Where the end state of the UI path from scenario C is kept, so that scenario E
# can compare its deploy path against it.
readonly UI_PATH_DIRECTORY_HOST="${STATE_ROOT_HOST}/ui-path"
readonly UI_PATH_DIRECTORY="${STATE_ROOT}/ui-path"

# Where the per-phase PHPUnit logs are written on the host. Inside the
# repository so a run's output can be read next to the code it belongs to.
readonly LOG_DIRECTORY="${STATE_ROOT_HOST}/logs"

# Where the normalised copies of the phases are written before they are compared.
# The normaliser runs inside the container, diff runs on the host, so both paths
# are kept.
readonly NORMALISED_DIRECTORY_HOST="${STATE_ROOT_HOST}/normalised"
readonly NORMALISED_DIRECTORY="${STATE_ROOT}/normalised"

# The normaliser, as seen from inside the container. Its logic is
# ConfigurationNormaliser's; this script only calls it.
readonly NORMALISER_SCRIPT='/var/www/html/tests/scripts/normalise_configuration.php'

# The data dumps a phase comparison covers, as paths below a phase directory.
# Mirrors LifecycleBase::recordedDataFiles().
readonly -a COMPARED_DATA_FILES=(
  'data/crop_field_data.txt'
  'data/paragraph_field_entity_popover.txt'
  'data/file_usage.txt'
  'data/state.txt'
)

# Why core.extension differs from S1 after an uninstall.
readonly CORE_EXTENSION_REASON="Drupal core keeps the dependencies of an uninstalled module enabled, so responsive_image and svg_image_responsive stay listed."

# The differences a comparison expects, keyed by "<later>:<earlier>|<path>", where
# <path> is relative to the phase directory. Each reason is the one the PHPUnit
# test gives for the same difference in its assertPhasesMatch() call. A
# difference under any other key is a finding.
declare -rA EXPECTED_DIFFERENCES=(
  ['A4:S2|data/crop_field_data.txt']="the duplicates the migration created stay: they are content, and hook_uninstall() removes them later as the module's own crops"
  ['A4:S2|data/state.txt']="the migration history is remembered until the module is uninstalled; hook_install() documents no reverts of it either"
  ['A5:S1|config/core.extension.yml']="${CORE_EXTENSION_REASON}"
  ['A7:S1|config/core.extension.yml']="${CORE_EXTENSION_REASON}"
)

# Watchdog error severities that count as a finding. Drupal's severity levels:
# 8 is Error, the most severe. Warnings and below are logged legitimately by
# these modules, so only 8 is a failure.
readonly WATCHDOG_ERROR_SEVERITY=8

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

if [[ -n ${NO_COLOR:-} ]] || [[ ${TERM:-} == 'dumb' ]]; then
  RED=""
  GREEN=""
  YELLOW=""
  BLUE=""
  NC=""
fi

# Collected findings, one line each. Empty means the run was green.
declare -a FINDINGS=()

usage() {
  cat <<EOM

Runs the destructive lifecycle tests of openculturas_responsive_images and
openculturas_entity_popover against a DDEV site.

Restores the ${BASE_SNAPSHOT} snapshot, verifies that the database is the one the
scenarios assume, runs the pending update hooks, and then runs each scenario
from its own restore of the ${WORKING_SNAPSHOT} snapshot. Each scenario installs,
migrates, switches, reverts and uninstalls the modules through the user
interface and compares the recorded state against the baseline.

The database is left on ${WORKING_SNAPSHOT} unless --keep-database is given, in
which case ${WORKING_SNAPSHOT} is restored and the recorded state trees are
kept for inspection.

usage: ${SCRIPT_NAME} [options]

options:
    -s | --scenario <name>   Run only the named scenario. May be repeated.
                            One of: $(printf '%s ' "${SCENARIO_NAMES[@]}")
                            (default: all)
        --keep-database      Leave ${WORKING_SNAPSHOT} in place at the end instead
                            of restoring ${BASE_SNAPSHOT}.
        --continue-on-error  Keep going after a failing phase, so every finding
                            is collected in one run. Without it the run stops
                            at the first failure.
    -v | --verbose           Echo the output of every drush and phpunit call.
    -h | --help              Show this help message.

dependencies: ${DEPENDENCIES[*]}
              a running DDEV project, and the selenium compose profile for the
              browser-driven phases:
                ddev start --profiles=selenium

examples:
    ${SCRIPT_NAME}
    ${SCRIPT_NAME} --scenario A --scenario B --continue-on-error
    ${SCRIPT_NAME} --keep-database --verbose

EOM
  exit 1
}

# The scenarios in the order they are run.
SCENARIO_NAMES=(A B C D E)

main() {
  local keep_database="false"
  local continue_on_error="false"
  local verbose="false"
  local -a only_scenarios=()

  while [[ $# -gt 0 ]]; do
    case "$1" in
    -s | --scenario)
      shift
      if [[ $# -eq 0 ]]; then
        printf 'Error: %s needs a scenario name.\n' "${SCRIPT_NAME}" >&2
        usage
      fi
      only_scenarios+=("$1")
      ;;
    --keep-database)
      keep_database="true"
      ;;
    --continue-on-error)
      continue_on_error="true"
      ;;
    -v | --verbose)
      verbose="true"
      ;;
    -h | --help)
      usage
      ;;
    *)
      printf "Error: Unknown option '%s'.\n" "$1" >&2
      usage
      ;;
    esac
    shift
  done

  exit_on_missing_tools "${DEPENDENCIES[@]}"
  validate_scenario_names "${only_scenarios[@]}"

  cd "${PROJECT_DIRECTORY}" || {
    printf 'Error: Cannot enter %s.\n' "${PROJECT_DIRECTORY}" >&2
    exit 1
  }

  export LIFECYCLE_KEEP_GOING="${continue_on_error}"
  run_scenarios "${only_scenarios[@]}" || true

  print_summary
  if [[ ${keep_database} == 'true' ]]; then
    print_info "Leaving the database on the ${WORKING_SNAPSHOT} snapshot."
  else
    restore_base_snapshot || true
  fi

  if [[ ${#FINDINGS[@]} -gt 0 ]]; then
    return 1
  fi
  return 0
}

function exit_on_missing_tools() {
  local tool
  for tool in "$@"; do
    if command -v "${tool}" >/dev/null 2>&1; then
      continue
    fi
    printf "Error: Required tool '%s' is not installed or not in PATH.\n" "${tool}" >&2
    exit 1
  done
}

function validate_scenario_names() {
  local name
  for name in "$@"; do
    if [[ -z ${name} ]]; then
      continue
    fi
    local known=false
    local candidate
    for candidate in "${SCENARIO_NAMES[@]}"; do
      if [[ ${candidate} == "${name}" ]]; then
        known="true"
      fi
    done
    if [[ ${known} != 'true' ]]; then
      printf "Error: Unknown scenario '%s'. Known scenarios: %s.\n" \
        "${name}" "${SCENARIO_NAMES[*]}" >&2
      exit 1
    fi
  done
}

function print_header() {
  printf '%b\n' "${BLUE}============================================================${NC}"
  printf '%b\n' "${BLUE}$1${NC}"
  printf '%b\n' "${BLUE}============================================================${NC}"
}

function print_info() {
  printf '%b\n' "${BLUE}==>${NC} $1"
}

function print_success() {
  printf '%b\n' "${GREEN}  ok${NC} $1"
}

function print_warning() {
  printf '%b\n' "${YELLOW}  !!${NC} $1"
}

function print_failure() {
  printf '%b\n' "${RED} FAIL${NC} $1"
}

function record_finding() {
  local scenario="$1"
  local message="$2"
  FINDINGS+=("${scenario}: ${message}")
  print_failure "${message}"
}

function restore_base_snapshot() {
  print_info "Restoring the ${BASE_SNAPSHOT} snapshot."
  if ! ddev snapshot restore "${BASE_SNAPSHOT}"; then
    printf 'Error: Could not restore %s.\n' "${BASE_SNAPSHOT}" >&2
    return 1
  fi
  print_success "Database restored to ${BASE_SNAPSHOT}."
}

function restore_working_snapshot() {
  ddev snapshot restore "${WORKING_SNAPSHOT}" || {
    printf 'Error: Could not restore %s.\n' "${WORKING_SNAPSHOT}" >&2
    return 1
  }
}

function run_scenarios() {
  local -a requested=("$@")
  local name

  print_header "Preparing the database"
  prepare_database || return 1

  for name in "${SCENARIO_NAMES[@]}"; do
    if [[ ${#requested[@]} -gt 0 ]]; then
      local wanted="false"
      local candidate
      for candidate in "${requested[@]}"; do
        if [[ ${candidate} == "${name}" ]]; then
          wanted="true"
        fi
      done
      if [[ ${wanted} != 'true' ]]; then
        continue
      fi
    fi

    print_header "Scenario ${name}"
    if ! run_scenario "${name}"; then
      if [[ ${LIFECYCLE_KEEP_GOING} == 'true' ]]; then
        record_finding "${name}" "the scenario failed, continuing because --continue-on-error was given"
      else
        return 1
      fi
    fi
  done

  return 0
}

# Whether a file of the working snapshot exists in .ddev/db_snapshots.
function working_snapshot_exists() {
  compgen -G "${PROJECT_DIRECTORY}/.ddev/db_snapshots/${WORKING_SNAPSHOT}-*" >/dev/null
}

# Restores the base snapshot, checks it is the one the scenarios assume, runs
# the pending update hooks and snapshots the result.
function prepare_database() {
  restore_base_snapshot || return 1

  print_info "Checking that the database is the one the scenarios assume."
  if ! check_baseline; then
    printf '%s\n' "${RED}Refusing to run: the database is not a prepared ${BASE_SNAPSHOT} restore.${NC}" >&2
    return 1
  fi
  print_success "The database is a clean ${BASE_SNAPSHOT} restore."

  print_info "Running the pending update hooks."
  if ! run_drush updatedb --yes; then
    record_finding 'setup' 'drush updatedb failed'
    return 1
  fi
  if ! run_drush cache:rebuild; then
    record_finding 'setup' 'drush cache:rebuild failed after the update hooks'
    return 1
  fi

  if [[ -n ${DEBUG:-} ]]; then
    print_warning "No ${WORKING_SNAPSHOT} snapshot is created in debug mode; run it once to enable the scenarios."
    return 0
  fi

  print_info "Recording the S1 baseline."
  run_phpunit 'LifecycleBaselineTest' || {
    record_finding 'setup' 'recording the S1 baseline failed'
    return 1
  }

  stash_baseline_state || {
    record_finding 'setup' 'stashing the recorded baseline failed'
    return 1
  }

  # ddev refuses to overwrite an existing snapshot but still exits with 0, so a
  # snapshot from an earlier run is removed first, and the file is checked
  # afterwards instead of trusting the exit code.
  if working_snapshot_exists; then
    print_info "Removing the ${WORKING_SNAPSHOT} snapshot of an earlier run."
    ddev snapshot --cleanup --name="${WORKING_SNAPSHOT}" --yes
    if working_snapshot_exists; then
      record_finding 'setup' "the ${WORKING_SNAPSHOT} snapshot of an earlier run could not be removed"
      return 1
    fi
  fi

  print_info "Creating the ${WORKING_SNAPSHOT} snapshot."
  ddev snapshot --name="${WORKING_SNAPSHOT}" --yes
  if ! working_snapshot_exists; then
    record_finding 'setup' "creating the ${WORKING_SNAPSHOT} snapshot failed"
    return 1
  fi

  print_success "The ${WORKING_SNAPSHOT} snapshot is ready."
  return 0
}

# Verifies that the restored database has the pending update hooks and none of
# the modules under test. Anything else means it is not a database the
# scenarios may run their destructive steps on.
function check_baseline() {
  local hook
  local status
  status="$(ddev exec drush updatedb:status --format=json 2>/dev/null || true)"

  for hook in "${REQUIRED_PENDING_UPDATE_HOOKS[@]}"; do
    if [[ ${status} != *"${hook}"* ]]; then
      printf 'Error: %s is not pending on this database.\n' "${hook}" >&2
      return 1
    fi
  done

  local module
  for module in "${MODULES_UNDER_TEST[@]}"; do
    if ddev drush php:eval "print(\Drupal::moduleHandler()->moduleExists('${module}') ? 'yes' : 'no');" \
      2>/dev/null | tail -1 | grep -q 'yes'; then
      printf 'Error: %s is already installed on this database.\n' "${module}" >&2
      return 1
    fi
  done

  return 0
}

# Removes the recorded state trees of an earlier scenario.
#
# S1 is the baseline every scenario compares against. It is recorded once,
# during the preparation, and lives outside the per-scenario tree so that
# clearing a scenario's own phases cannot take it with them.
function clear_recorded_state() {
  rm -rf "${STATE_DIRECTORY_HOST}" || {
    printf 'Error: Could not clear the recorded state in %s.\n' "${STATE_DIRECTORY_HOST}" >&2
    return 1
  }
  mkdir -p "${STATE_DIRECTORY_HOST}" || return 1
  # Restore the baseline S1 next to the cleared scenario phases.
  restore_baseline_state || return 1
  return 0
}

# Puts the baseline back next to a scenario's phases.
function restore_baseline_state() {
  cp --archive "${BASELINE_DIRECTORY_HOST}/S1" "${STATE_DIRECTORY_HOST}/S1" || {
    printf 'Error: Could not restore the recorded baseline.\n' >&2
    return 1
  }
}

# Copies a scenario's in-use state aside, so a later scenario can compare against
# it: the recorded tree is cleared before every scenario, and scenario E has to
# compare its deploy path with what the UI path of scenario C looks like after
# migration, switch and backfill (before any uninstall).
function stash_ui_path_end_state() {
  local source="${STATE_DIRECTORY_HOST}/C-responsive-first-in-use"

  if [[ ! -d ${source} ]]; then
    record_finding 'C' "${source} was not recorded, so there is no UI path end state to stash for scenario E"
    return 1
  fi

  rm --recursive --force "${UI_PATH_DIRECTORY_HOST}" || return 1
  mkdir --parents "${UI_PATH_DIRECTORY_HOST}" || return 1
  cp --archive "${source}" "${UI_PATH_DIRECTORY_HOST}/C" || return 1
}

# Puts the stashed UI path in-use state back under its phase name.
function restore_ui_path_end_state() {
  local source="${UI_PATH_DIRECTORY_HOST}/C"

  if [[ ! -d ${source} ]]; then
    record_finding 'E' "there is no stashed UI path end state in ${source}: scenario C has to run first, with testResponsiveImagesFirst, so that it is recorded and stashed"
    return 1
  fi

  # Every run records a new baseline, so a stash older than it comes from an
  # earlier run, possibly with other module code. cp --archive keeps the
  # recording times, so the comparison holds for the stashed copies too.
  if [[ ${source} -ot ${BASELINE_DIRECTORY_HOST}/S1 ]]; then
    record_finding 'E' "the stashed UI path end state in ${source} is older than this run's baseline: run scenario C together with E"
    return 1
  fi

  rm --recursive --force "${STATE_DIRECTORY_HOST}/C-responsive-first-in-use" || return 1
  cp --archive "${source}" "${STATE_DIRECTORY_HOST}/C-responsive-first-in-use" || return 1
}

# Copies the recorded baseline aside before the first scenario clears the tree.
function stash_baseline_state() {
  if [[ ! -d ${STATE_DIRECTORY_HOST}/S1 ]]; then
    printf 'Error: The baseline S1 was not recorded in %s.\n' "${STATE_DIRECTORY_HOST}" >&2
    return 1
  fi

  rm --recursive --force "${BASELINE_DIRECTORY_HOST}" || return 1
  mkdir --parents "${BASELINE_DIRECTORY_HOST}" || return 1
  cp --archive "${STATE_DIRECTORY_HOST}/S1" "${BASELINE_DIRECTORY_HOST}/S1" || return 1
  return 0
}

function run_scenario() {
  local name="$1"
  local -a filters=()

  case "${name}" in
  A)
    filters=('ScenarioAResponsiveImagesTest')
    ;;
  B)
    filters=('ScenarioBEntityPopoverTest')
    ;;
  C)
    # One restore per test method: the two orders must not build on each
    # other, and the orchestrator restores the snapshot before each filter.
    filters=(
      'ScenarioCCombinedModulesTest::testResponsiveImagesFirst'
      'ScenarioCCombinedModulesTest::testPopoverFirst'
    )
    ;;
  D)
    filters=('ScenarioDDivergentStartTest')
    ;;
  E)
    filters=('ScenarioEConfigDeploymentTest')
    ;;
  *)
    record_finding "${name}" "no test class is mapped to this scenario"
    return 1
    ;;
  esac

local filter
  local phase_comparison
  local phpunit_passed
  local -a phase_comparisons=()

  for filter in "${filters[@]}"; do
    # Compared right after this filter, while its phases are the only ones
    # recorded: the next filter clears the recorded state before it starts.
    scenario_phase_comparisons "${name}" "${filter}" phase_comparisons
    # The recorded trees of the previous filter would otherwise still be there,
    # and a phase this run never records would be compared against a stale one
    # from an earlier filter. The baseline is put back, because it is what the
    # comparisons are made against.
    clear_recorded_state || return 1

    # Before the test runs, not after: scenario E compares its end state with
    # the UI path's end state from scenario C, and that phase has to be there
    # while the test reads it.
    if [[ ${name} == 'E' ]]; then
      restore_ui_path_end_state || return 1
    fi

    restore_working_snapshot || return 1

    print_info "Running ${filter} from a fresh ${WORKING_SNAPSHOT} restore."
    phpunit_passed='true'
    if run_phpunit "${filter}"; then
      print_success "${filter} passed."
    else
      record_finding "${name}" "${filter} failed; run it on its own to read the findings"
      phpunit_passed='false'
    fi

    # Compared even when PHPUnit failed: the recorded phases are what shows how a
    # red run went wrong. A phase the run never reached is reported as missing.
    for phase_comparison in "${phase_comparisons[@]}"; do
      compare_phases "${name}" "${phase_comparison%%:*}" "${phase_comparison##*:}"
    done

    if [[ ${phpunit_passed} == 'false' ]]; then
      return 1
    fi

    if [[ ${name} == 'C' && ${filter} == *testResponsiveImagesFirst ]]; then
      stash_ui_path_end_state || return 1
    fi
  done

  return 0
}

# Runs one test class or method.
#
# @param $1 A PHPUnit filter: a class name, or 'Class::method'.
function run_phpunit() {
  local filter="$1"
  local -a command=(vendor/bin/phpunit --testsuite lifecycle --filter "${filter}")

  if [[ -n ${DEBUG:-} ]]; then
    ddev exec "${command[@]}" 2>&1
  else
    # The log is written on the host: the PHPUnit output is the primary record
    # of what a run found, and it has to survive the container being recreated
    # by the next snapshot restore.
    mkdir -p "${LOG_DIRECTORY}"
    ddev exec "${command[@]}" 2>&1 | tee "${LOG_DIRECTORY}/lifecycle-${filter//::/-}.log"
  fi
  return "${PIPESTATUS[0]}"
}

function run_drush() {
  if [[ -n ${DEBUG:-} ]]; then
    ddev drush "$@"
  else
    ddev drush "$@" >/dev/null
  fi
}

# Fills the phase comparisons a scenario's last filter has to satisfy.
#
# The PHPUnit tests own the assertions; this is the second, independent check
# that the whole recorded state came back, not just the few keys a test looked
# at.
#
# @param $1 The scenario name.
# @param $2 The PHPUnit filter the comparisons apply to.
# @param $3 The name of the variable to fill, without the variable name.
function scenario_phase_comparisons() {
  local scenario="$1"
  local filter="$2"
  local -n target="$3"

  case "${scenario}" in
  A)
    # S3 is the switched state and differs from S2 by design, so it is not
    # compared with S2. The uninstalls are compared with the baseline.
    target=('A4:S2' 'A5:S1' 'A7:S1')
    ;;
  B)
    # B1 is the state right after the install, so it differs from S1 by design
    # and is not compared here; the PHPUnit test asserts on it directly.
    target=('B5:S1')
    ;;
  C)
    # Each order's own end state, not the other order's: both run in separate
    # test methods with a separate restore in between, so the comparison has to
    # follow the filter rather than the scenario.
    if [[ ${filter} == *testResponsiveImagesFirst ]]; then
      target=('C-responsive-first-final:S1')
    else
      target=('C-popover-first-final:S1')
    fi
    ;;
  D)
    # Nothing is expected to match: scenario D records what happens to a site
    # whose displays were changed by hand, whatever that turns out to be.
    target=()
    ;;
  E)
    # The deploy path ends with both modules installed and switched on, so it
    # is compared with the UI path's end state, not with S1. No pair is listed
    # here: a pair would report the whole config/sync difference between the
    # two starting points. ScenarioEConfigDeploymentTest asserts the comparison
    # itself, limited to the configuration the UI path changed.
    target=()
    ;;
  *)
    target=()
    ;;
  esac
}

# Writes the normalised configuration of a phase, and unchanged copies of its data
# dumps, to ${NORMALISED_DIRECTORY_HOST}/<phase>.
#
# @param $1 The scenario name.
# @param $2 The phase name.
function prepare_normalised_phase() {
  local scenario="$1"
  local phase="$2"
  local target_host="${NORMALISED_DIRECTORY_HOST}/${phase}"
  local data_file

  rm --recursive --force "${target_host}" || {
    record_finding "${scenario}" "${target_host} could not be cleared for the comparison"
    return 1
  }
  mkdir --parents "${target_host}/data" || {
    record_finding "${scenario}" "${target_host}/data could not be created for the comparison"
    return 1
  }

  if ! ddev exec php "${NORMALISER_SCRIPT}" "${STATE_DIRECTORY}/${phase}/config" "${NORMALISED_DIRECTORY}/${phase}/config"; then
    record_finding "${scenario}" "${phase} could not be normalised; the normaliser reported the error above"
    return 1
  fi

  for data_file in "${COMPARED_DATA_FILES[@]}"; do
    cp "${STATE_DIRECTORY_HOST}/${phase}/${data_file}" "${target_host}/${data_file}" || {
      record_finding "${scenario}" "${phase}/${data_file} could not be copied for the comparison"
      return 1
    }
  done
  return 0
}

# Lists the files of a normalised phase, relative to its directory.
#
# @param $1 The phase name.
function list_normalised_files() {
  local phase="$1"

  (cd "${NORMALISED_DIRECTORY_HOST}/${phase}" && find . -type f -printf '%P\n')
}

# Compares two recorded phases file by file, after normalising their configuration.
#
# A file that differs is a finding, unless the comparison expects that difference
# (see EXPECTED_DIFFERENCES): then its diff is printed as information. A file that
# exists on one side only is compared with /dev/null, so diff shows it as added or
# removed.
#
# The comparison runs whether or not the PHPUnit run before it passed, as long as
# both phases are recorded.
#
# @param $1 The scenario name.
# @param $2 The later phase, the state under test.
# @param $3 The earlier phase, the baseline it has to match.
function compare_phases() {
  local scenario="$1"
  local later="$2"
  local earlier="$3"
  local phase
  local relative_path
  local expected_reason
  local earlier_source
  local later_source
  local file_difference
  local -i diff_status=0
  local -i findings_before=${#FINDINGS[@]}
  local -i differing_count=0
  local -a relative_paths=()

  print_info "Comparing ${later} with ${earlier}."
  for phase in "${later}" "${earlier}"; do
    ddev exec test -d "${STATE_DIRECTORY}/${phase}" || {
      record_finding "${scenario}" "${phase} was never recorded"
      return 1
    }
  done

  prepare_normalised_phase "${scenario}" "${later}" || return 1
  prepare_normalised_phase "${scenario}" "${earlier}" || return 1

  mapfile -t relative_paths < <(
    {
      list_normalised_files "${later}"
      list_normalised_files "${earlier}"
    } | sort --unique
  )

  for relative_path in "${relative_paths[@]}"; do
    earlier_source="${NORMALISED_DIRECTORY_HOST}/${earlier}/${relative_path}"
    later_source="${NORMALISED_DIRECTORY_HOST}/${later}/${relative_path}"
    [[ -f ${earlier_source} ]] || earlier_source='/dev/null'
    [[ -f ${later_source} ]] || later_source='/dev/null'

    file_difference="$(diff --unified --label "${earlier}/${relative_path}" --label "${later}/${relative_path}" "${earlier_source}" "${later_source}" 2>&1)"
    diff_status=$?

    case "${diff_status}" in
    0)
      continue
      ;;
    1)
      differing_count+=1
      ;;
    *)
      # Exit status 2 is diff failing, not a difference between the files.
      record_finding "${scenario}" "diff could not compare ${relative_path} of ${later} and ${earlier}: ${file_difference}"
      continue
      ;;
    esac

    expected_reason="${EXPECTED_DIFFERENCES["${later}:${earlier}|${relative_path}"]:-}"
    if [[ -n ${expected_reason} ]]; then
      print_info "${relative_path} differs as expected: ${expected_reason}"
      printf '%s\n' "${file_difference}"
      continue
    fi

    record_finding "${scenario}" "${later} differs from ${earlier} in ${relative_path}:
${file_difference}"
  done

  if ((${#FINDINGS[@]} > findings_before)); then
    return 1
  fi

  if ((differing_count == 0)); then
    print_success "${later} matches ${earlier} (configuration normalised, data identical)."
  else
    print_success "${later} matches ${earlier} apart from the expected differences above."
  fi
  return 0
}

function print_summary() {
  print_header "Summary"
  if [[ ${#FINDINGS[@]} -eq 0 ]]; then
    print_success "No findings."
    return 0
  fi

  printf '%s\n' "${RED}${#FINDINGS[@]} finding(s):${NC}"
  local finding
  for finding in "${FINDINGS[@]}"; do
    printf '%s\n' "${RED}  - ${finding}${NC}" >&2
  done
  return 1
}

if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
  main "$@"
  exit $?
fi