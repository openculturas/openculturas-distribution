<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\block\Entity\Block;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity): array {
    $block = Block::create(['id' => 'shariff_block']);
    return $this->entityTypeManager->getViewBuilder('block')->view($block);
  }

}
