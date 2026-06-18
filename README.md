# OpenCulturas

## Table of contents

- Introduction
- Requirements
- Third-party libraries
- Installation
- FAQ
- Development
- Maintenance
- Security

## Introduction

OpenCulturas is built as a pre-configured platform for cultural-focused communities, empowering actors in the cultural field to own their data and make their information accessible to a wide audience.

## Requirements
* https://www.drupal.org/docs/system-requirements for Drupal
  * PHP 8.4
* A bunch of drupal modules and external libraries.

## Third-party libraries

These JavaScript/CSS libraries are not available on Packagist and are declared as inline package repositories in `composer.json`.

| Library | Repository | Used by |
|---------|------------|---------|
| ajaxorg/ace-builds | https://github.com/ajaxorg/ace-builds | drupal/asset_injector |
| brianvoe/slim-select | https://github.com/brianvoe/slim-select | openculturas_slimselect |
| choices-js/choices.js | https://github.com/choices-js/choices | drupal/choices |
| elmarquis/leaflet.gesture-handling | https://github.com/elmarquis/leaflet.gestureHandling | drupal/leaflet |
| fengyuanchen/cropperjs | https://github.com/fengyuanchen/cropperjs | drupal/image_widget_crop |
| fortawesome/font-awesome | https://github.com/FortAwesome/Font-Awesome | openculturas_base, drupal/social_media_links |
| heiseonline/shariff | https://github.com/heiseonline/shariff | drupal/shariff |
| leaflet/leaflet.fullscreen | https://github.com/brunob/leaflet.fullscreen | drupal/leaflet |
| leaflet/leaflet.markercluster | https://github.com/Leaflet/Leaflet.markercluster | drupal/leaflet |
| perliedman/leaflet-control-geocoder | https://github.com/perliedman/leaflet-control-geocoder | openculturas_map |

## Installation

### New installation

Use the composer project template:

```bash
composer create-project --remove-vcs drupal/openculturas_project example.org
```

For more information go to https://www.drupal.org/project/openculturas_project.

### Updating

#### Patch release

Optionally update library versions in the inline package repositories in `composer.json`, then:

```bash
composer update openculturas/openculturas-distribution "drupal/core-*" drupal/core --with-dependencies --minimal-changes
drush updatedb --yes
# Review changes, then export and commit configuration
drush config:export --yes
```

#### Minor release

Manually increase the version constraints for `drupal/core-*` and `openculturas/openculturas-distribution` to the next minor version in `composer.json`, then follow the same steps as a patch release.

## FAQ

Q: How can I ignore patches?

A: OpenCulturas defines patches for some contrib modules. If you want to update a module before we release a new version with the patch removed, you can configure composer-patches to ignore specific patches. For more information go to https://docs.cweagans.net/composer-patches/usage/configuration/#ignore-dependency-patches.

Q: How can I contribute to OpenCulturas?

A: Please read the [Contribution gudelines](CONTRIBUTING.md).

## Development

Please read the [Development guidelines](DEVELOPMENT.md) before you start.

We recommend using https://ddev.com for development.

### Installation (with ddev)

* Clone this repository
* Install dependencies
  * `ddev composer install`
* Install OpenCulturas distribution
  * `ddev drush site:install --yes --existing-config`

### DDEV

Cheatsheet:

* Start project `ddev start`
* Run composer commands `ddev composer COMMAND` e.g. `ddev composer install`
* Run drush commands `ddev drush COMMAND` e.g. `ddev drush uli`
* Import latest database snapshot `ddev dbimport`

More information about ddev cli command https://ddev.readthedocs.io/en/stable/users/basics/cli-usage/.

### Patching

Patches are managed with [cweagans/composer-patches](https://docs.cweagans.net/composer-patches/) v2 and tracked in `patches.lock.json`.

```bash
ddev composer patches-relock   # Re-discover patches in composer.json and rewrite patches.lock.json
ddev composer patches-repatch  # Delete, re-download and re-apply patches for all affected packages
ddev composer patches-doctor   # Diagnose common patching issues
```

Run `patches-relock` after adding or removing a patch in `composer.json`. Run `patches-repatch` when a patch fails to apply or a package needs a clean re-install.

### PHP Quality Checks

```bash
ddev composer run php:lint        # PHP parallel lint
ddev composer run php:cs          # PHPCS
ddev composer run php:cs-fix      # Auto-fix PHPCS issues
ddev composer run php:phpstan     # Static analysis
ddev composer run php:rector      # Rector dry-run
ddev composer run php:rector-fix  # Rector auto-fix
```

### JS/CSS Linting

All linting commands run inside the DDEV container:

```bash
ddev exec npm run lint:js         # ESLint JavaScript (entire project)
ddev exec npm run lint:yaml       # ESLint YAML
ddev exec npm run lint:css        # stylelint CSS (profile/modules)
ddev exec npm run lint:css:fix    # Auto-fix CSS
ddev exec npm run lint:scss       # stylelint SCSS (openculturas_base theme)
ddev exec npm run lint:scss:fix   # Auto-fix SCSS
ddev exec npm run prettier        # Prettier JavaScript
ddev exec npm run prettier:css    # Prettier CSS
ddev exec npm run prettier:scss   # Prettier SCSS
```

To lint specific JS files:

```bash
ddev exec npx eslint --ext .js --no-ignore path/to/file.js
```

### Configuration files

All configurations are managed via [config_devel](https://www.drupal.org/project/config_devel).
Each configuration is listed in the info file of the profile or module.
Therefore, any changes to the configuration must also be made in the info file.

After that, enable config_devel and run `ddev composer run cde` or `ddev drush cde module`.

This command updates all configuration listed in the info file and removes the key `_core` and `uuid` except for
views configuration. The uuid is needed because the uuid is used in other configuration as a default value, without this
the default value would be not set/broken.

## Maintenance

### Repository

The source code is privately hosted and mirrored to https://github.com/openculturas/openculturas-distribution.
To create a new release on drupal.org we push the used git branch/tag to https://git.drupalcode.org/project/openculturas.
Someday when the issue https://www.drupal.org/project/project_composer/issues/3252534 is fixed we can directly work on drupal.org git repository.

### Info files

To update the version and sort dependencies in all module/theme info files, run:
`ddev composer run info_file_normalizer`

#### Creating a release

1. Set the version in `scripts/info_file_normalizer.php` (the `VERSION` constant) to the release version, e.g. `3.1.0-beta3`
2. Run `ddev composer run info_file_normalizer`
3. Commit: `chore(release): Prepare 3.1.0-beta3`
4. Tag the commit: `git tag --annotate 3.1.0-beta3 --message="3.1.0-beta3"` (annotated tag required — a plain `git tag` will fail)
5. Set `VERSION` back to `3.1.x-dev`
6. Run `ddev composer run info_file_normalizer`
7. Commit: `chore: Back to 3.1.x-dev`
8. `git push`
9. `git push --tags`

After the tag is pushed and the repository is synced to https://git.drupalcode.org/project/openculturas, create the release on drupal.org manually.

### Updating initial content
* Fetch latest changes and install site: `git pull && ddev composer install && ddev composer run si`
* Change content via UI
* Export content `ddev composer run export-content`

### Drush scripts

At the moment l.d.o does not find the labels of field_group. Therefore, we generate the strings.

`drush scr scripts/generate_field_group_strings.php`

Download the composer.json from drupal/core-dev and update the adjusted core-dev composer.json based on the current Drupal version.

`drush scr scripts/update_drupal_core_dev.php`

Sync display settings from 'full' to 'full_lb' view mode.

`drush scr scripts/sync_full_to_full_lb.php`

## Security

Concerns about the software security? Or have you found a vulnerability? Please follow the principles of Responsible Disclosure. You'll find all information you need in [SECURITY.md](SECURITY.md).
