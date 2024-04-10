# OpenCulturas

## Table of contents

- Introduction
- Requirements
- Installation
- FAQ
- Development

## Introduction

OpenCulturas is built as a pre-configured platform for cultural-focused communities, empowering actors in the cultural field to own their data and make their information accessible to a wide audience.

## Requirements
* https://www.drupal.org/docs/system-requirements for Drupal 9
  * PHP 8.1
* A bunch of drupal modules and external libraries.

## Installation

We provide a composer project to install OpenCulturas. For more information go to https://www.drupal.org/project/openculturas_project.

## FAQ

Q: How can I ignore patches?

A: The package which we use to patch dependencies allows to ignore patches. For more information go to https://github.com/cweagans/composer-patches#ignoring-patches.

## Development

We recommend to use https://ddev.com for development.

At the moment the source code is privately hosted and mirrored to https://github.com/openculturas/openculturas-distribution.
To create a new release on drupal.org we push the used git branch/tag to https://git.drupalcode.org/project/openculturas.
Someday when the issue https://www.drupal.org/project/project_composer/issues/3252534 is fixed we can directly work on drupal.org git repository.

### Installation (with ddev)

* Clone this repository
* Install dependencies
  * `ddev composer install`
* Install OpenCulturas distribution
  * `ddev drush site:install --yes --existing-config`

### Installation (without ddev)

* Clone this repository
* Install dependencies
  * `composer install`
* Prepare a setting.php, you can use the [settings.php](.ddev/settings.php)
  * `if [[ ! -h web/sites/default/settings.php ]];then cd web/sites/default/ && ln -sf ../../../.ddev/settings.php;fi`
  * Override values (DB etc.) in `web/sites/default/settings.local.php`
  * Make sure **config_sync_directory** points to **../config/sync**

    `$settings['config_sync_directory'] = '../config/sync';`
* Install OpenCulturas distribution
  * `drush site:install --yes --existing-config`

### ddev

Cheatsheet:

* Start project `ddev start`
* Run composer commands `ddev composer COMMAND` e.g. `ddev composer install`
* Run drush commands `ddev drush COMMAND` e. g. `ddev drush uli`

More information about ddev cli command https://ddev.readthedocs.io/en/stable/users/basics/cli-usage/.

### Composer scripts

### Info files:

To update the version, sort list like dependencies in openculturas modules, run:
`composer run info_file_normalizer`

#### Updating initial content
* Fetch latest changes and install site: `git pull && ddev composer install && ddev composer run si`
* Change content via UI
* Export content `ddev composer run export-content`

### drush scripts

At the moment l.d.o does not find the labels of field_group. Therefore we generate the strings.

`drush scr scripts/generate_field_group_strings.php`

### Configuration files

All configuration are managed via [config_devel](https://www.drupal.org/project/config_devel).
Each configuration is listed in the info file of the profile or submodule.
So any new configuration needs to be added to the info file.

After that, enable config_devel and run `ddev drush cde openculturas` or `ddev drush cde submodule`.

This command updates all configuration which are listed in the info file and removes the key `_core` and `uuid` except for
views configuration. The uuid is needed because the uuid is used in other configuration as default value, without this
the default value would be not set/broken.
