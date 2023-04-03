<?php

require $app_root . '/' . $site_path . '/default.settings.php';

$settings['config_sync_directory'] = '../config/sync';

// Automatically generated include for settings managed by ddev.
if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php') && getenv('IS_DDEV_PROJECT') == 'true') {
  include $app_root . '/' . $site_path . '/settings.ddev.php';
}
$databases['default']['default']['init_commands'] = [
  'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
];

require_once $app_root . '/sites/example.settings.local.php';

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
   include $app_root . '/' . $site_path . '/settings.local.php';
}
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
  'config_devel'
];

// Override drupal/swiftmailer default config to use Mailhog
$config['smtp.settings']['smtp_on'] = TRUE;
$config['smtp.settings']['smtp_host'] = '127.0.0.1';
$config['smtp.settings']['smtp_port'] = '1025';
$config['system.mail']['interface']['default'] = 'SMTPMailSystem';
