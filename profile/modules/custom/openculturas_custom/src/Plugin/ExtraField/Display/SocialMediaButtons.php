<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Social media buttons
 *
 * @ExtraFieldDisplay(
 *   id = "social_media_buttons",
 *   label = @Translation("Social media buttons"),
 *   visible = FALSE,
 *   bundles = {
 *     "node.*",
 *     "taxonomy_term.*",
 *   }
 * )
 */
class SocialMediaButtons extends ExtraFieldDisplayFormattedBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->pluginManager = $container->get('plugin.manager.block');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity): array {
    /** @var \Drupal\shariff\Plugin\Block\ShariffBlock $block */
    $block = $this->pluginManager->createInstance('shariff_block');
    return $block->build();
  }

}
