#!/usr/bin/env bash

DEPENDENCIES=(git ddev sed)
SCRIPT_NAME=$(basename "$0")
SCRIPT_VERSION="1.0.0"
NORMALIZER_FILE="scripts/info_file_normalizer.php"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

if [[ -n "${NO_COLOR:-}" ]] || [[ "${TERM:-}" == "dumb" ]]; then
    RED=""
    GREEN=""
    YELLOW=""
    BLUE=""
    NC=""
fi

function usage() {
    cat <<EOM

Prepare an OpenCulturas release: bump the version in all .info.yml files,
commit, tag, then reset the working tree to the next -dev version.

usage: ${SCRIPT_NAME} [version] [options]

arguments:
    version                       Release version in semver format (e.g. 3.1.0, 3.1.0-beta3, 3.1.0-rc1)
                                  Prompted for interactively if omitted.

options:
    -h|--help                    Show this help message
    --version                    Show script version information

dependencies: ${DEPENDENCIES[*]}

examples:
    ${SCRIPT_NAME} 3.1.0
    ${SCRIPT_NAME} 3.1.0-rc1
    ${SCRIPT_NAME}

EOM
    exit 1
}

function main() {
    local release_version=""

    while [ "$1" != "" ]; do
        case $1 in
        --version)
            echo "${SCRIPT_NAME} version ${SCRIPT_VERSION}"
            exit 0
            ;;
        -h | --help)
            usage
            ;;
        -*)
            print_error "Unknown option '$1'"
            usage
            ;;
        *)
            release_version="$1"
            ;;
        esac
        shift
    done

    exit_on_missing_tools "${DEPENDENCIES[@]}"

    cd "$(git rev-parse --show-toplevel)" || {
        print_error "Not inside a git repository"
        exit 1
    }

    if [ -z "$release_version" ]; then
        read -r -p "Release version (semver, e.g. 3.1.0 or 3.1.0-rc1): " release_version
    fi

    if ! is_valid_semver "$release_version"; then
        print_error "'${release_version}' is not a valid semver version"
        echo "Expected format: MAJOR.MINOR.PATCH or MAJOR.MINOR.PATCH-(alpha|beta|rc)N, e.g. 3.1.0 or 3.1.0-beta3" >&2
        exit 1
    fi

    ensure_clean_working_tree

    local current_branch
    current_branch=$(git symbolic-ref -q --short HEAD)
    if [ -z "$current_branch" ]; then
        print_error "Not on a branch (detached HEAD)"
        exit 1
    fi

    local dev_version="${current_branch}-dev"

    print_header "Release ${release_version} on branch ${current_branch}"
    echo "Release commit will set VERSION to: ${release_version}"
    echo "Branch will be reset back to:       ${dev_version}"
    echo
    read -r -p "Continue? (y/n): " -n 1 REPLY
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Aborted."
        exit 0
    fi

    set_normalizer_version "$release_version"
    run_normalizer
    commit_changes "chore(release): Prepare ${release_version}"
    tag_release "$release_version"

    set_normalizer_version "$dev_version"
    run_normalizer
    commit_changes "chore: Back to ${dev_version}"

    print_success "Release ${release_version} prepared locally on ${current_branch}."
    print_step_hint "$current_branch" "$release_version"
}

function is_valid_semver() {
    local version="$1"
    [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-(alpha|beta|rc)[0-9]+)?$ ]]
}

function ensure_clean_working_tree() {
    if [ -n "$(git status --porcelain)" ]; then
        print_error "Working tree is not clean. Commit or stash your changes first."
        exit 1
    fi
}

function set_normalizer_version() {
    local version="$1"
    sed -i "s/^const VERSION = '.*';\$/const VERSION = '${version}';/" "$NORMALIZER_FILE" || {
        print_error "Failed to update VERSION in ${NORMALIZER_FILE}"
        exit 1
    }
}

function run_normalizer() {
    if ! ddev composer run info_file_normalizer; then
        print_error "info_file_normalizer failed"
        exit 1
    fi
}

function commit_changes() {
    local message="$1"
    git add -A -- profile "$NORMALIZER_FILE" || {
        print_error "Failed to stage changes"
        exit 1
    }
    if ! git commit --message="$message"; then
        print_error "Failed to commit: ${message}"
        exit 1
    fi
    print_success "Committed: ${message}"
}

function tag_release() {
    local version="$1"
    if ! git tag --annotate "$version" --message="$version"; then
        print_error "Failed to create tag ${version}"
        exit 1
    fi
    print_success "Tagged: ${version}"
}

function print_step_hint() {
    local branch="$1"
    local version="$2"
    cat <<EOM

The release branch is typically protected. Unlock it, then push manually:

    git push origin ${branch}
    git push origin ${version}

After the tag is pushed and synced to git.drupalcode.org, create the
release on drupal.org manually.
EOM
}

function print_header() {
    echo -e "${BLUE}=======================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}=======================${NC}"
}

function print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

function print_error() {
    echo -e "${RED}❌ Error: $1${NC}" >&2
}

function exit_on_missing_tools() {
    for cmd in "$@"; do
        if command -v "$cmd" &>/dev/null; then
            continue
        fi
        print_error "Required tool '${cmd}' is not installed or not in PATH"
        exit 1
    done
}

if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
    exit 0
fi
