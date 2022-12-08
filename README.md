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
  * PHP 7.4
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
#### Update installation profile configuration
This script copies and prepares the files from `config/sync` to `profile/config/install` to use it for a new installation.
* `composer run update-config`

#### Updating initial content
* Fetch latest changes and install site: `git pull && composer install && composer run update-config && ddev composer run si`
* Change content via UI
* Export content `ddev composer run export-content`
