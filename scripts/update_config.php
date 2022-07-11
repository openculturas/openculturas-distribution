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
    ->notName(['core.extension.yml', 'update.settings.yml', 'file.setting.yml', 'system.site.yml']);


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
        $target_file_contents = \Drupal\Core\Serialization\Yaml::encode($source_data);

        echo 'config/sync/' . $fileNameWithExtension;
        echo '=> ';
        echo 'profile/config/install/' . $fileNameWithExtension;
        echo PHP_EOL;
        file_put_contents('profile/config/install/' . $fileNameWithExtension, $target_file_contents);
      }
    }
    $source_data = \Drupal\Core\Serialization\Yaml::decode(file_get_contents('config/sync/core.extension.yml'));
    $target_data = \Drupal\Core\Serialization\Yaml::decode(file_get_contents('profile/openculturas.info.yml'));
    unset($source_data['module']['openculturas']);
    $module_list = array_keys($source_data['module']);
    sort($module_list);
    $target_data['install'] = $module_list;
    unset($target_data['install']['openculturas']);
    file_put_contents('profile/openculturas.info.yml', \Drupal\Core\Serialization\Yaml::encode($target_data));
    echo sprintf('Processed %d files' . PHP_EOL, $counter);
  }
}
main();



