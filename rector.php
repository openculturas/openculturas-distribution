<?php
// phpcs:ignoreFile

declare(strict_types=1);

use DrupalFinder\DrupalFinderComposerRuntime;
use DrupalRector\Set\DrupalSetProvider;
use Rector\Config\RectorConfig;

$drupalFinder = new DrupalFinderComposerRuntime();
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
  ->withComposerBased(twig: TRUE, phpunit: TRUE, symfony: TRUE, drupal: TRUE)
  ->withPhpSets()
  ->withSetProviders(DrupalSetProvider::class)
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
  ->withSkip(
    [
      // Drupal render arrays (e.g. '#submit', '#process') must stay
      // serializable for the form cache; first-class callables become
      // Closures, which serialize() rejects.
      \Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector::class,
      \Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class,
      \Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector::class,
      \Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector::class => [
        __DIR__ . '/profile/'
      ],
      __DIR__ . '/profile/modules/custom/openculturas_faq/src/ProxyClass/OpenCulturasFaqUninstallValidator.php',
      __DIR__ . '/profile/modules/custom/openculturas_discussions/src/ProxyClass/OpenCulturasDiscussionsUninstallValidator.php',
    ]
  )
  ->withParallel(maxNumberOfProcess: 2);
