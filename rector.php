<?php
// phpcs:ignoreFile

declare(strict_types=1);

use DrupalFinder\DrupalFinder;
use DrupalRector\Set\Drupal10SetList;
use Rector\Config\RectorConfig;

$drupalFinder = new DrupalFinder();
$drupalFinder->locateRoot(__DIR__);
$drupalRoot = $drupalFinder->getDrupalRoot();


return RectorConfig::configure()
  ->withPreparedSets(
    deadCode: true,
    codeQuality: true,
    codingStyle: true,
    typeDeclarations: true,
    instanceOf: true,
    earlyReturn: true
  )
  ->withSets(
    [Drupal10SetList::DRUPAL_10]
  )
  ->withAutoloadPaths(
    [
      $drupalRoot . '/core',
      $drupalRoot . '/modules',
      $drupalRoot . '/themes'
    ]
  )
  ->withCache(
    getenv('CI') ? __DIR__ . '/.rectorcache' : NULL,
    getenv('CI') ? \Rector\Caching\ValueObject\Storage\FileCacheStorage::class : NULL,
  )
  ->withFileExtensions(
    ['php', 'module', 'theme', 'install', 'profile']
  )
  ->withImportNames(importDocBlockNames: false, importShortClasses: false)
  ->withPaths([
    __DIR__ . '/profile/'
  ])
  ->withPHPStanConfigs([__DIR__ . '/phpstan-for-rector.neon'])
  ->withPhpVersion(\Rector\ValueObject\PhpVersion::PHP_81)
  ->withSkip(
    [
      \Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class,
      \Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector::class,
      \Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector::class,
      \Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector::class => [
        __DIR__ . '/profile/'
      ],
      __DIR__ . '/profile/modules/custom/openculturas_custom/src/Plugin/DateAugmenter/AddToCal.php',
      __DIR__ . '/profile/modules/custom/geofield_proximity_filter_extra/src/Controller/AutocompleteFiltersController.php',
      __DIR__ . '/profile/modules/custom/openculturas_faq/src/ProxyClass/OpenCulturasFaqUninstallValidator.php',
      __DIR__ . '/profile/modules/custom/openculturas_discussions/src/ProxyClass/OpenCulturasDiscussionsUninstallValidator.php',
    ]
  )
  ->withParallel(maxNumberOfProcess: 2);
