<?php

require $app_root . '/' . $site_path . '/default.settings.php';

$settings['config_sync_directory'] = '../config/sync';

// Automatically generated include for settings managed by ddev.
if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php') && getenv('IS_DDEV_PROJECT') == 'true') {
  include $app_root . '/' . $site_path . '/settings.ddev.php';
}

require_once $app_root . '/sites/example.settings.local.php';

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
   include $app_root . '/' . $site_path . '/settings.local.php';
}
$settings['config_exclude_modules'] = ['devel', 'stage_file_proxy', 'config_inspector', 'cookies_matomo', 'matomo', 'matomo_tagmanager', 'upgrade_status'];
