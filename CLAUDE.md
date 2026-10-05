<!-- ai-best-practices:start -->
<!-- Do not edit by hand inside the ai-best-practices markers in AGENTS.md; this block is regenerated when you update drupal/ai_best_practices. -->

## Drupal AI best practices

This project uses [`drupal/ai_best_practices`](https://www.drupal.org/project/ai_best_practices)
to provide AI guidance tailored for Drupal development.

**Skill discovery:** Skills are installed into `.agents/skills/` when you run
`composer install` or `composer update`. AI clients that support the
[Agent Skills specification](https://agentskills.io/specification) load skills
automatically from that directory — no manual listing needed. For clients that
do not yet support automatic discovery, this file (`AGENTS.md`) acts as a
compatibility fallback; add explicit skill references here only if your tooling
requires it.

**What to commit:** Add `.agents/` and `AGENTS.md` to version control so all
team members and CI environments share the same AI context. Also commit
tool-specific files such as `CLAUDE.md` and `GEMINI.md` if your team uses those
clients.
<!-- ai-best-practices:end -->

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OpenCulturas is a Drupal 10 installation profile (distribution) for arts and culture portals. It's a pre-configured platform with custom modules, themes, and extensive configuration.

- **Stack:** PHP 8.4+, Drupal 11, Node 22+
- **Package Managers:** Composer (PHP), npm (JS/CSS tooling)
- **Local Development:** DDEV
- DDEV project name: openculturas → site at https://openculturas.ddev.site

## Common Commands

All Drupal/Drush commands run inside the DDEV container.

```bash
# Start/stop environment
ddev start
ddev stop

# Drush shortcuts
# e.g. ddev drush cr, ddev drush cex, ddev drush cim
ddev drush <command>

# Generate one-time login URL
ddev drush uli

# Composer (runs inside DDEV container)
ddev composer require <package>
ddev composer update

# (Re-)install the site (clears files dir and runs drush site:install)
ddev composer run si

# Launch admin pages
ddev launch /admin
```

### Configuration Management

```bash
# Export config to sync dir
ddev drush config:export -y

# Import config from sync dir
ddev drush config:import -y

# List config objects that differ between DB and sync dir (read-only)
ddev drush config:status

# Inspect a specific config object
ddev drush config:get <config.name>

# Show config sync directory path
ddev drush status --field=config-sync
```

`ddev drush config:export --diff` is not a preview: run non-interactively,
it prints the diff and then exports to `config/sync`. To see a full diff
without touching the sync dir, export to a scratch directory and compare:

```bash
ddev drush config:export --destination=/tmp/config-preview -y
ddev exec diff --recursive --unified config/sync /tmp/config-preview
```

Check `git status -- config/sync` after any drush config command.

### Development Commands

```bash
# List enabled modules
ddev drush pm:list --status=enabled

# Enable a module
ddev drush pm:install <module>

# View recent log entries
ddev drush watchdog:show --count=20

# Clear all logs
ddev drush watchdog:delete all

# Run cron
ddev drush cron

# Execute PHP in Drupal context
ddev drush php:eval "<php code>"

# View fields for an entity bundle
ddev drush field:info <entity> <bundle>

# Run pending database updates
ddev drush updatedb

# Inspect database tables
ddev drush sql:query "DESCRIBE <table_name>;"
```

### PHP Quality Checks

Checks (run all before committing PHP changes — these mirror CI):
```bash
ddev composer run php:lint        # PHP parallel lint
ddev composer run php:cs          # PHPCS only
ddev composer run php:phpstan     # Static analysis (level: max)
ddev composer run php:rector      # Rector dry-run
```

Auto-fixers:
```bash
ddev composer run php:cs-fix      # Auto-fix PHPCS issues
ddev composer run php:rector-fix  # Rector auto-fix
```

### PHPUnit Tests

Tests use Drupal Test Traits and run against the existing local site (no
separate install), so they depend on its current database and config.

```bash
# ExistingSite tests
ddev exec vendor/bin/phpunit --testsuite existing-site

# Single test class
ddev exec vendor/bin/phpunit --filter FaviconLinksTest

# JavaScript tests need the selenium-chrome service, which only starts with
# its compose profile
ddev start --profiles=selenium
ddev exec vendor/bin/phpunit --testsuite existing-site-javascript

# Stop the selenium-chrome service again; `ddev restart` leaves it running
docker rm --force ddev-openculturas-selenium-chrome
```

`DTT_BASE_URL` and `DTT_MINK_DRIVER_ARGS` come from
`.ddev/config.selenium-standalone-chrome.yaml`.

The suites assume every pending update hook has already run on the local
database. Run `ddev drush updatedb:status` first; if updates are pending, run
`ddev drush updatedb -y && ddev drush cr`. A failure caused by missing updates
is an environment problem, so don't make tests defensive against an
un-updated database.

### JS/CSS Linting

These commands run inside the DDEV container (`ddev exec`). The `lint:js` script hardcodes `.` as target — to lint specific files use `npx eslint` directly.

```bash
ddev exec npm run lint:js              # ESLint JavaScript (entire project)
ddev exec npm run lint:yaml            # ESLint YAML
ddev exec npm run lint:css             # stylelint CSS (profile/modules)
ddev exec npm run lint:css:fix         # Auto-fix CSS
ddev exec npm run lint:scss            # stylelint SCSS (openculturas_base theme)
ddev exec npm run lint:scss:fix        # Auto-fix SCSS
ddev exec npm run prettier             # Format JavaScript
ddev exec npm run prettier:css         # Format CSS
ddev exec npm run prettier:scss        # Format SCSS

# Lint specific JS files
ddev exec npx eslint --ext .js --no-ignore path/to/file.js
```

### Theme Development

```bash
# Generate a new sub-theme from opcult_starterkit, build it, and verify SASS compiles
ddev composer run test-starterkit
```

### Configuration & Content Export

Uses `config_devel` module. Config is declared in module `.info.yml` files and exported with `ddev drush cde <module>`.

```bash
ddev drush cde <module>  # Updates the configuration for a specific module via config_devel (Current state in database to module for new installation or post updates)
ddev composer run cde  # Updates the configuration for the main profile and selected modules (openculturas_faq, openculturas_discussions, openculturas_map, openculturas_section, openculturas_openstreetmap) via config_devel
composer run export-content  # Export default content
composer run info_file_normalizer  # Sort .info.yml files alphabetically
```

## Architecture

```
profile/                      # Main Drupal installation profile
├── modules/custom/          # 13 custom modules (openculturas_* + dark_mode_toggle)
├── themes/                  # openculturas_base, opcult, opcult_starterkit
└── config/install/          # Default Drupal configuration
web/                         # Drupal web root (composer scaffold)
config/                      # Project-level config exports
scripts/                     # Utility scripts (info_file_normalizer.php, db_dump.sh, etc.)
tests/                       # PHPUnit tests
```

Most custom modules are prefixed `openculturas_*` and handle: calendar widgets, maps (OpenStreetMap/Leaflet), media, FAQ, discussions, teasers, sections, address links, and slim-select. The `dark_mode_toggle` module is the exception to this naming convention.

### Install paths: dev site vs. downstream

Downstream sites require `openculturas/openculturas-distribution`. The root
`composer.json` is `type: drupal-profile`, so the profile ends up at
`web/profiles/contrib/openculturas-distribution/profile/`.

`openculturas/openculturas-profile` (`profile/composer.json`) is only a path
repository for this repo's DDEV site, symlinked as
`web/profiles/contrib/openculturas-profile`. Paths containing
`openculturas-profile` are correct in `config/sync` (dev site only) and wrong in
`profile/config/install`. Never hardcode profile or theme paths in shipped
config; resolve them at runtime via `extension.list.theme` /
`extension.list.profile` (see `openculturas_custom/src/GetThemeFaviconsPath.php`).

### Translation

- `profile/config/install` is imported in one bulk sync during
  `drush site:install --existing-config`, so none of it gets a
  `_core.default_config_hash`. Locale's automatic interface-translation sync for
  config (`LocaleConfigManager`) requires that hash, so it never applies to the
  profile's baseline displays and other install config. Strings built from that
  config need an explicit `t()` at render time.
- Layout Builder `field_block` / `extra_field_block` labels are translated in
  `OpenculturasCustomDateEventHooks::preprocessBlock()` with context
  `field_group_label`, shared with field_group labels. Their schema `label` is
  set to `translatable: FALSE` in
  `OpenculturasCustomIntegrationHooks::configSchemaInfoAlter()` so Config
  Translation can't create an override that `t()` would then translate a second
  time. Use the same pattern (keep `type: label`, add `translatable: false`)
  for any config value that is translated explicitly at render time. When a
  module defines the schema key itself, set `translatable: false` there; the
  alter hook is only for keys inherited from a parent type.
- Never call `$this->t()` in a plugin's `defaultConfiguration()`. It runs in the
  current request's language and bakes the translated text into stored config.
  Store the English source string and translate on output.
- localize.drupal.org's potx scan can't find field_group labels, so
  `profile/TranslatableFieldGroups.php` holds literal `t()` calls as scan bait.
  The file is generated: don't edit it by hand, regenerate it with
  `ddev drush scr scripts/generate_field_group_strings.php`.
- Editing a `.po` file has no effect on the local site until it is imported:
  `ddev drush locale:import de profiles/contrib/openculturas-profile/modules/custom/openculturas_custom/translations/de.po --override=all`
  (path relative to the web root).

### Patching

The project uses `cweagans/composer-patches` v2. Patches are tracked in `patches.lock.json`.

```bash
# Re-discover patches and write patches.lock.json
ddev composer patches-relock

# Re-install patched deps and re-apply patches
ddev composer patches-repatch

# Diagnose common patching issues
ddev composer patches-doctor
```

## Development Principles

From DEVELOPMENT.md:
- **Privacy by default:** No external CDNs, local OpenStreetMap tiles
- **Accessibility by default:** WCAG compliance
- **Site-builder friendly:** Configuration over code when possible
- **English first:** All strings must be translatable
- **Freedom of choice:** Avoid unnecessary dependencies

## Debugging

```bash
# Follow web container logs (stdout/stderr)
ddev logs -f web

# Enable/disable Xdebug (no restart needed)
ddev xdebug on
ddev xdebug off
```

## Code Style & Standards

- Never use abbreviations in names. Write the full word every time — `$definition` not `$def`, `$configuration` not `$config`, `$identifier` not `$id`, `$parameters` not `$params`, `$temporary` not `$tmp`. Exceptions for widely accepted conventions: `$io`, `src`, `href`, `url`, `id` (when it is literally an ID/primary key), `html`, `csv`, `api`, `sql`, `php`, language codes like `$langcode`.
- When a `create(ContainerInterface $container)` override calls
  `parent::create()`, narrow the type with a docblock and a `static` return
  type, no `assert()`:

  ```php
  public static function create(ContainerInterface $container): static {
    /** @var static $instance */
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }
  ```
- Most hooks (`*_alter`, entity hooks, anything run via `invokeAll()`) may be
  implemented several times per module. Hooks that core calls for a single
  module via `ModuleHandler::invoke()` may not: preprocess hooks and
  `hook_theme` throw `LogicException: Module ... should not implement ... more
  than once` on a second implementation, even in a different class. Extend the
  existing method instead (e.g. the `preprocess_block` implementation in
  `OpenculturasCustomDateEventHooks`).

## Update Hooks

- Changes to `asset_injector.css.oc_gin_theme_overrides` reach existing sites
  only through a revert in `profile/openculturas.post_update.php`. Never use
  `hook_update_N()` or another config import path for it. Only the newest
  `openculturas_post_update_revert_gin_theme_overrides_N()` reverts the
  config. Older ones, and entries in multi-config post-updates, become no-ops
  (empty body, `void` return, docblock prefixed with `No-op.`) so a site
  updating across several releases reverts once.
- If the newest revert post-update is not part of a tagged release yet
  (`git tag --contains <commit>` is empty), change only the config file and
  keep that post-update. Otherwise add
  `openculturas_post_update_revert_gin_theme_overrides_<N+1>()` and make the
  previous one a no-op.

## Dependency Updates

- Renovate bumps of a package pinned only via npm `overrides` (e.g. `postcss`
  in the theme `package.json` files) can leave `package-lock.json` without the
  `node_modules/<package>` entry, without any error. Check the lockfile diff,
  including the repo root lockfile. Regular MR pipelines only run `npm ci` at
  the repo root, so a broken theme lockfile produces no CI failure. Fix with
  `ddev exec 'cd profile/themes/<theme> && npm install'`.
- Minor bumps of `mglaman/phpstan-drupal` can enable new rules. Run
  `ddev composer run php:phpstan` and include the fixes in the same commit.

## Releases

Use `scripts/create_release.sh <version>` (see README.md). It does not push;
the release branch is protected, so leave the printed `git push` commands to a
maintainer.

## Git Workflow

- **Main branch for PRs:** `3.2.x`
- **Current development:** `3.2.x`

