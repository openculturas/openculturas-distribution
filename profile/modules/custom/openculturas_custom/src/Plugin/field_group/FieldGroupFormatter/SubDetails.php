<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\field_group\FieldGroupFormatter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\field_group\Plugin\field_group\FieldGroupFormatter\Details;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'sub_details' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "sub_details",
 *   label = @Translation("Details (title from token)"),
 *   description = @Translation("Add a details element"),
 *   supported_contexts = {
 *     "view"
 *   }
 * )
 */
final class SubDetails extends Details implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new self($plugin_id, $plugin_definition, $configuration['group'], $configuration['settings'], $configuration['label']);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->token = $container->get('token');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(): array {
    $form = parent::settingsForm();
    if ($this->moduleHandler->moduleExists('token')) {
      $form['name_pattern_help'] = [
        '#type' => 'container',
        'token_link' => [
          '#theme' => 'token_tree_link',
          '#token_types' => ['node'],
          '#dialog' => TRUE,
        ],
        // Only the label will be replaced, so place directly after the label.
        '#weight' => $form['label']['#weight'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object): void {
    parent::process($element, $processed_object);
    $title = NULL;
    /** @var array $processed_object */
    if (array_key_exists('#node', $processed_object)) {
      $title = $this->token->replace($element['#title'], ['node' => $processed_object['#node']]);
    }

    if (!empty($title)) {
      $element['#title'] = $title;
    }

    $element['#plugin_id'] = $this->getBaseId();
  }

}
