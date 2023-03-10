<?php

// @codingStandardsIgnoreFile
require_once $app_root . '/' . $site_path  . '/default.settings.php';

$settings['hash_salt'] = hash('sha256', getenv('CLOUDRON_APP_DOMAIN'));

$databases['default']['default'] = [
  'database' => getenv('CLOUDRON_MYSQL_DATABASE'),
  'username' => getenv('CLOUDRON_MYSQL_USERNAME'),
  'password' => getenv('CLOUDRON_MYSQL_PASSWORD'),
  'prefix' => '',
  'host' => getenv('CLOUDRON_MYSQL_HOST')?: 'db',
  'port' => getenv('CLOUDRON_MYSQL_PORT')?: '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
  'init_commands' => [
    'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
  ],
];
$settings['allow_authorize_operations'] = FALSE;

# Taken from https://github.com/amazeeio/drupal-example/blob/master/web/sites/default/settings.php#L117
$settings['trusted_host_patterns'] = [
  '^' . str_replace(['.', 'https://', 'http://', ','], ['\.', '', '', '|'], getenv('CLOUDRON_APP_DOMAIN')) . '$', // escape dots, remove schema, use commas as regex separator
];
$settings['config_sync_directory'] = '../config/sync';
$settings['file_temp_path'] = '/tmp';
$settings['file_private_path'] = '/app/data/private';
$config['locale.settings']['translation']['path'] = '/app/data/files/translations';
$settings['skip_permissions_hardening'] = FALSE;
$config['smtp.settings']['smtp_on'] = FALSE;
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = [getenv('CLOUDRON_PROXY_IP')];

$settings['config_exclude_modules'] = [
  'devel',
  'stage_file_proxy',
  'config_inspector',
  'cookies_matomo',
  'matomo',
  'matomo_tagmanager',
  'upgrade_status',
  'openculturas_calendar_widget',
  'bpmn_io',
  'eca_modeller_bpmn',
  'eca_ui',
  'smtp',
];

if (getenv('STAGE_FILE_PROXY_ORIGIN')) {
  $config['stage_file_proxy.settings']['origin'] = getenv('STAGE_FILE_PROXY_ORIGIN');
}
