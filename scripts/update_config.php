<?php
declare(strict_types=1);

include_once 'vendor/autoload.php';

function removeDirectory(string $path): void {
  $files = glob($path . '/*');
  foreach ($files as $file) {
    is_dir($file) ? removeDirectory($file) : unlink($file);
  }
  rmdir($path);
}

function main() {
  $exclude_list = [
    'automated_cron.settings.yml',
    'claro.settings.yml',
    'coffee.configuration.yml',
    'comment.settings.yml',
    'composer_deploy.settings.yml',
    'contact.settings.yml',
    'cookies.cookies_service.base.yml',
    'cookies.cookies_service.video.yml',
    'cookies.cookies_service_group.default.yml',
    'cookies.cookies_service_group.performance.yml',
    'cookies.cookies_service_group.social.yml',
    'cookies.cookies_service_group.tracking.yml',
    'cookies.cookies_service_group.video.yml',
    'cookies.texts.yml',
    'core.extension.yml',
    'update.settings.yml',
    'file.setting.yml',
    'system.site.yml',
    'eca.settings.yml',
    'views.view.eca_log.yml'
  ];

  if (!is_dir('config/sync/')) {
    exit(sprintf('%s is not a directory', 'config/sync') . PHP_EOL);
  }
  if (is_dir('profile/config/install/')) {
    removeDirectory('profile/config/install/');
  }
  if (!mkdir('profile/config/install/') && !is_dir('profile/config/install/')) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created',
      'profile/config/install/'));
  }
  $finder = \Symfony\Component\Finder\Finder::create();
  // find all files in the current directory
  $finder->files()->in('config/sync/')
    ->name('*.yml')
    ->exclude('language')
    ->notName($exclude_list);

  $destination_override = [

  ];

  $counter = 0;
  if ($finder->hasResults()) {
    foreach ($finder as $file) {
      if ($counter === 5) {
       // break;
      }
      $counter++;

      $absoluteFilePath = $file->getRealPath();
      $fileNameWithExtension = $file->getRelativePathname();
      if (is_file('config/sync/' . $fileNameWithExtension)) {
        $source_data = \Drupal\Core\Serialization\Yaml::decode($file->getContents());
        // Keep uuid to allows default values for viewfields.
        // Example config/sync/field.field.node.profile.field_performer_at_view_ref.yml -> default_value[0].target_uuid.
        if (strpos($file->getFilename(), 'views.view.') === FALSE) {
          unset($source_data['uuid']);
        }
        unset($source_data['_core']);

        echo 'config/sync/' . $fileNameWithExtension;
        echo '=> ';
        $destination =  'profile/config/install/';
        if (isset($destination_override[$fileNameWithExtension])) {
          $destination = $destination_override[$fileNameWithExtension];
          if (isset($source_data['dependencies']) && !isset($source_data['dependencies']['enforced']['module'])) {
            $source_data['dependencies']['enforced']['module'] = [basename(dirname($destination_override[$fileNameWithExtension],
              2))];
          }
        }
        $destination = $destination . $fileNameWithExtension;
        echo $destination;
        echo PHP_EOL;
        $target_file_contents = \Drupal\Core\Serialization\Yaml::encode($source_data);
        file_put_contents($destination, $target_file_contents);
      }
    }
    $source_data = \Drupal\Core\Serialization\Yaml::decode(file_get_contents('config/sync/core.extension.yml'));
    $target_data = \Drupal\Core\Serialization\Yaml::decode(file_get_contents('profile/openculturas.info.yml'));
    unset($source_data['module']['openculturas']);
    unset($source_data['module']['openculturas_calendar_widget']);
    $module_list = array_keys($source_data['module']);
    sort($module_list);
    $target_data['install'] = $module_list;
    unset($target_data['install']['openculturas']);
    file_put_contents('profile/openculturas.info.yml', \Drupal\Core\Serialization\Yaml::encode($target_data));
    echo sprintf('Processed %d files' . PHP_EOL, $counter);
  }
}
main();



