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

$settings['trusted_host_patterns'] = ['.*'];
$settings['config_sync_directory'] = '../config/sync';
$settings['file_temp_path'] = '/tmp';
$settings['file_private_path'] = '/app/data/private';
$config['locale.settings']['translation']['path'] = '/app/data/files/translations';
$settings['skip_permissions_hardening'] = FALSE;
$config['smtp.settings']['smtp_on'] = FALSE;
$config['openculturas_map.settings']['development_mode'] = TRUE;
$settings['config_exclude_modules'] = [
  'devel',
  'stage_file_proxy',
  'config_inspector',
  'upgrade_status',
  'bpmn_io',
  'eca_modeller_bpmn',
  'eca_ui'
];

if (getenv('STAGE_FILE_PROXY_ORIGIN')) {
  $config['stage_file_proxy.settings']['origin'] = getenv('STAGE_FILE_PROXY_ORIGIN');
  $config['stage_file_proxy.settings']['origin_dir'] = 'sites/default/files';
  $settings['simple_environment_anonymous'] = TRUE;
  $settings['simple_environment_indicator'] = '#000000/#ffdd00 Stage';
}

if (file_exists('/app/data/settings.local.php')) {
  include '/app/data/settings.local.php';
}

$settings['container_yamls'][] = '/app/data/local.services.yml';
