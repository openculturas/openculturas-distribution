<?php

declare(strict_types = 1);

namespace Drupal\openculturas_custom\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
final class UserPage extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'enabled' => FALSE,
      'only_for_owner' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState): array {
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
    return parent::buildConfigurationForm($form, $formState);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState): void {
    $this->configuration['enabled'] = (bool) $formState->getValue('enabled');
    $this->configuration['only_for_owner'] = (bool) $formState->getValue('only_for_owner');
    parent::submitConfigurationForm($form, $formState);
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
  public function summary(): string {
    if (empty($this->configuration['enabled'])) {
      return (string) $this->t('Not enabled');
    }

    if (!$this->isNegated()) {
      return (string) $this->t('Do not return true on user page');
    }

    return (string) $this->t('Return true on user page');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $contexts = parent::getCacheContexts();
    if (empty($this->configuration['enabled'])) {
      return $contexts;
    }

    $contexts[] = 'route';
    $contexts[] = 'user';
    return $contexts;
  }

}
