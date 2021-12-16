# OpenCulturas distribution

Do not use this project/code to start a new project. Go to https://github.com/openculturas/openculturas-project
and use this.

## Requirements
* TBD


## Development

### Installation

* Clone this repository
* Install dependencies
  * `composer install`
* Prepare a setting.php, you can use the [settings.php](.ddev/settings.php)
  * `if [[ ! -h web/sites/default/settings.php ]];then cd web/sites/default/ && ln -sf ../../../.ddev/settings.php;fi`
  * Override values (DB etc.) in `web/sites/default/settings.local.php`
  * Make sure 'config_sync_directory' points to '../config/sync'
    `$settings['config_sync_directory'] = '../config/sync';`
* Install OpenCulturas distribution
  * `drush si drush si --existing-config`

### ddev

Cheatsheet:

* Start project `ddev start`
* Run composer commands `ddev composer COMMAND` e.g. `ddev composer install`
* Run drush commands `ddev drush COMMAND` e. g. `ddev drush uli`


