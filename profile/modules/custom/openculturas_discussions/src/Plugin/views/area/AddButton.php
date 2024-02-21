<?php

declare(strict_types=1);

namespace Drupal\openculturas_discussions\Plugin\views\area;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views area handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("openculturas_discussions_add_button")
 */
final class AddButton extends AreaPluginBase {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $accountProxy;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  private RedirectDestinationInterface $redirectDestination;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $addButton = parent::create($container, $configuration, $plugin_id, $plugin_definition,);
    $addButton->accountProxy = $container->get('current_user');
    $addButton->routeMatch = $container->get('current_route_match');
    $addButton->redirectDestination = $container->get('redirect.destination');
    return $addButton;
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $this->routeMatch->getParameter('node');
    if ($this->accountProxy->isAnonymous()) {
      return [];
    }

    if (!$this->accountProxy->hasPermission('create comment content')) {
      return [];
    }

    if ($node instanceof NodeInterface
      && $node->hasField('field_comments_mode')
      && !$node->get('field_comments_mode')->isEmpty()
      && $node->get('field_comments_mode')->value === 'active') {
      $build['add_button'] = [
        '#type' => 'link',
        '#title' => $this->t('Add a comment'),
        '#url' => Url::fromRoute('node.add', ['node_type' => 'comment'],
          [
            'query' => $this->redirectDestination->getAsArray() + ['content_id' => $node->id()],
          ]
        ),
        '#attributes' => ['class' => 'button'],
      ];
      CacheableMetadata::createFromRenderArray($build)->addCacheableDependency($node)->applyTo($build);
      return $build;
    }

    return [];
  }

}
