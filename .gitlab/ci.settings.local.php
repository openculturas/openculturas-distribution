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
// Overrides for the test job only. renovate:update-artifacts runs
// `composer run cde`, and config_devel exports configuration with overrides
// applied, so these values would end up in profile/config/install.
if (getenv('OPENCULTURAS_CI_TESTS')) {
  // CI containers have no mail transport. A failed mail() call logs an error,
  // which fails the ExistingSite tests.
  $config['system.mail']['interface']['default'] = 'test_mail_collector';
  // The tests need no search index. Indexing items saved by update hooks
  // renders them in the default theme while updatedb runs, which logs
  // "Theme hook username not found".
  $config['search_api.index.content']['read_only'] = TRUE;
}
