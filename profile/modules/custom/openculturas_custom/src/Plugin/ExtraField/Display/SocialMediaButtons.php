<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Social media buttons.
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
final class SocialMediaButtons extends ExtraFieldDisplayFormattedBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SocialMediaButtons {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->pluginManager = $container->get('plugin.manager.block');
    $instance->setStringTranslation($container->get('string_translation'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelDisplay(): string {
    return 'above';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Share');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function viewElements(ContentEntityInterface $contentEntity): array {
    /** @var \Drupal\shariff\Plugin\Block\ShariffBlock $block */
    $block = $this->pluginManager->createInstance('shariff_block');
    return $block->build();
  }

}
