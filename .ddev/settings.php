<?php

require $app_root . '/' . $site_path . '/default.settings.php';

$settings['config_sync_directory'] = '../config/sync';
$settings['file_private_path'] = 'sites/default/files/private';
// Automatically generated include for settings managed by ddev.
if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php') && getenv('IS_DDEV_PROJECT') == 'true') {
  include $app_root . '/' . $site_path . '/settings.ddev.php';
}
$databases['default']['default']['init_commands'] = [
  'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
];

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
$settings['config_exclude_modules'] = [
  'devel',
  'stage_file_proxy',
  'config_inspector',
  'upgrade_status',
  'bpmn_io',
  'eca_modeller_bpmn',
  'eca_ui',
  'config_devel',
  'config_update_ui',
  'smtp'
];

$settings['simple_environment_anonymous'] = TRUE;
$settings['simple_environment_indicator'] = '#000000/#1d70b7 Dev';
