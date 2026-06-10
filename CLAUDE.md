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

# Preview pending config changes
ddev drush config:export --diff

# Inspect a specific config object
ddev drush config:get <config.name>

# Show config sync directory path
ddev drush status --field=config-sync
```

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
```bash
ddev composer run php:lint        # PHP parallel lint
ddev composer run php:cs          # PHPCS only
ddev composer run php:cs-fix      # Auto-fix PHPCS issues
ddev composer run php:phpstan     # Static analysis (level: max)
ddev composer run php:rector      # Rector dry-run
ddev composer run php:rector-fix  # Rector auto-fix
```

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

## Git Workflow

- **Main branch for PRs:** `3.0.x`
- **Current development:** `3.0.x`

