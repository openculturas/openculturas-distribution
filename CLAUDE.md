# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OpenCulturas is a Drupal 10 installation profile (distribution) for arts and culture portals. It's a pre-configured platform with custom modules, themes, and extensive configuration.

- **Stack:** PHP 8.3+, Drupal 10, Node 22+
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
composer run php:qa          # Full QA: lint + cs + phpstan + rector
composer run php:cs          # PHPCS only
composer run php:cs-fix      # Auto-fix PHPCS issues
composer run php:phpstan     # Static analysis (level: max)
composer run php:rector      # Rector dry-run
composer run php:rector-fix  # Rector auto-fix
```

### JS/CSS Linting
```bash
npm run lint:js              # ESLint
npm run lint:scss            # stylelint SCSS
npm run lint:scss:fix        # Auto-fix SCSS
npm run prettier:scss        # Format SCSS
```

### Configuration & Content Export

Uses `config_devel` module. Config is declared in module `.info.yml` files and exported with `ddev drush cde <module>`.

```bash
ddev drush cde <module>  # Updates the configuration for a specific module via config_devel (Current state in database to module for new installation or post updates)
ddev composer run cde  # Updates the configuration for all modules/theme/profile via config_devel (Current state in database to module for new installation or post updates)
composer run export-content  # Export default content
composer run info_file_normalizer  # Sort .info.yml files alphabetically
```

## Architecture

```
profile/                      # Main Drupal installation profile
├── modules/custom/          # 13 custom modules (openculturas_*)
├── themes/                  # openculturas_base, opcult, opcult_starterkit
└── config/install/          # Default Drupal configuration
web/                         # Drupal web root (composer scaffold)
config/                      # Project-level config exports
scripts/                     # Utility scripts (info_file_normalizer.php, db_dump.sh, etc.)
tests/                       # PHPUnit tests
```

Custom modules are prefixed `openculturas_*` and handle: calendar widgets, maps (OpenStreetMap/Leaflet), media, FAQ, discussions, teasers, sections, and address links.

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
