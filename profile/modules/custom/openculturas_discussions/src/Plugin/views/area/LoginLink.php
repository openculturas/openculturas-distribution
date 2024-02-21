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
 * @ViewsArea("openculturas_discussions_login_link")
 */
final class LoginLink extends AreaPluginBase {

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
    $loginLink = parent::create($container, $configuration, $plugin_id, $plugin_definition,);
    $loginLink->accountProxy = $container->get('current_user');
    $loginLink->routeMatch = $container->get('current_route_match');
    $loginLink->redirectDestination = $container->get('redirect.destination');
    return $loginLink;
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface
      && $this->accountProxy->isAnonymous()
      && $node->hasField('field_comments_mode')
      && !$node->get('field_comments_mode')->isEmpty()
      && $node->get('field_comments_mode')->value === 'active') {
      $build['login_link'] = [
        '#type' => 'link',
        '#title' => $this->t('Log in to write a comment'),
        '#url' => Url::fromRoute('user.login', [], ['query' => $this->redirectDestination->getAsArray(), 'fragment' => 'comments']),
        '#attributes' => ['class' => 'button'],
      ];
      CacheableMetadata::createFromRenderArray($build)->addCacheableDependency($node)->applyTo($build);
      return $build;
    }

    return [];
  }

}
