<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User page' condition.
 *
 * @Condition(
 *   id = "user_page",
 *   label = @Translation("User page"),
 *   context_definitions = {
 *     "current_user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
final class UserPage extends \Drupal\Core\Condition\ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['enabled' => FALSE, 'only_for_owner' => TRUE] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enabled'] = [
      '#title' => $this->t('Enable condition'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['enabled'],
    ];
    $form['only_for_owner'] = [
      '#title' => $this->t('Only for owner'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['only_for_owner'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled'] = (bool) $form_state->getValue('enabled');
    $this->configuration['only_for_owner'] = (bool) $form_state->getValue('only_for_owner');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    if (empty($this->configuration['enabled']) && !$this->isNegated()) {
      return TRUE;
    }

    $route_name = $this->routeMatch->getRouteName();

    /** @var \Drupal\user\UserInterface $current_user */
    $current_user = $this->getContextValue('current_user');
    $is_owner = FALSE;
    $is_route = $route_name === 'entity.user.canonical';
    if ($is_route) {
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->routeMatch->getParameter('user');
      $is_owner = $current_user->id() === $account->id();
    }

    if (!empty($this->configuration['only_for_owner'])) {
      return $is_owner && $is_route;
    }
    return $is_route;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (empty($this->configuration['enabled'])) {
      return $this->t('Not enabled');
    }

    if (!$this->isNegated()) {
      return $this
        ->t('Do not return true on user page');
    }
    return $this
      ->t('Return true on user page');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    if (empty($this->configuration['enabled'])) {
      return $contexts;
    }
    $contexts[] = 'route';
    $contexts[] = 'user';
    return $contexts;
  }


}
