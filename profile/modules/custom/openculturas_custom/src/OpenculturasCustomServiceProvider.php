<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use function is_array;

/**
 * Service Provider for Openculturas.
 */
class OpenculturasCustomServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $filter_protocols = $container->getParameter('filter_protocols');
    if (is_array($filter_protocols)) {
      $filter_protocols[] = 'geo';
      $container->setParameter('filter_protocols', $filter_protocols);
    }
  }

}
