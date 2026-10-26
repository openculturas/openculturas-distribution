<?php

declare(strict_types=1);

namespace Drupal\entity_popover_swiffy_slider;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\entity_popover_swiffy_slider\EventSubscriber\WrapFieldBlockPopover;
use function is_array;

/**
 * Removes the Layout Builder event subscriber when that module is absent.
 */
class EntityPopoverSwiffySliderServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $modules = $container->getParameter('container.modules');
    if (is_array($modules) && !isset($modules['layout_builder'])) {
      $container->removeDefinition(WrapFieldBlockPopover::class);
    }
  }

}
