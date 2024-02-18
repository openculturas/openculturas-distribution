<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openculturas_custom\CurrentEntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a bookmark flag icon block.
 *
 * @Block(
 *   id = "openculturas_custom_bookmark_flag_icon",
 *   admin_label = @Translation("Bookmark flag icon")
 * )
 */
final class BookmarkFlagIconBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use UncacheableDependencyTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): BookmarkFlagIconBlock {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): ?array {
    $build = [];
    $page_entity = CurrentEntityHelper::get_current_page_entity();
    $current_entity = CurrentEntityHelper::getEventReference($page_entity);
    if ($current_entity instanceof FieldableEntityInterface
      && ($current_entity->getEntityTypeId() === 'node'
        || $current_entity->getEntityTypeId() === 'taxonomy_term')
      ) {
      if ($this->account->isAuthenticated()) {
        $build['flag_bookmark_' . $current_entity->getEntityTypeId()] = [
          '#lazy_builder' => [
            'flag.link_builder:build', [
              $current_entity->getEntityTypeId(),
              $current_entity->id(),
              ($current_entity->getEntityTypeId() === 'taxonomy_term') ? 'bookmark_term' : 'bookmark_node',
            ],
          ],
          '#create_placeholder' => TRUE,
        ];
      }
      else {
        $build['flag_bookmark_' . $current_entity->getEntityTypeId()] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
<div class="flag flag-bookmark-node action-flag"><a class="flag--link disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to bookmark' | t}}"></a></div>
EOF
        ];
      }

      return $build;
    }

    return NULL;
  }

}
