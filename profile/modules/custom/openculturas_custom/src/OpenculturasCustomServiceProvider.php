<?php

declare(strict_types = 1);

namespace Drupal\openculturas_custom;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function is_array;

/**
 * Service Provider for Openculturas.
 */
class OpenculturasCustomServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $containerBuilder): void {
    $filter_protocols = $containerBuilder->getParameter('filter_protocols');
    if (is_array($filter_protocols)) {
      $filter_protocols[] = 'geo';
      $containerBuilder->setParameter('filter_protocols', $filter_protocols);
    }

    $modules = $containerBuilder->getParameter('container.modules');
    if (is_array($modules) && isset($modules['default_content'])) {
      $definition = new Definition(Normalizer::class, [
        new Reference('default_content_fixes.normalizer.inner'),
      ]);
      $definition->setDecoratedService('default_content.content_entity_normalizer', NULL, 9);
      $containerBuilder->setDefinition('default_content_fixes.normalizer', $definition);
    }
  }

}
