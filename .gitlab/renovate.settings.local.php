<?php

// @codingStandardsIgnoreFile
$settings['hash_salt'] = hash('sha256', 'renovate');
$databases['default']['default'] = [
  'database' => 'app',
  'username' => 'app',
  'password' => 'app',
  'prefix' => '',
  'host' => 'mysql',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
  'init_commands' => [
    'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
  ],
];
$settings['trusted_host_patterns'] = ['.*'];
$settings['file_temp_path'] = '/tmp';
$config['smtp.settings']['smtp_on'] = FALSE;
