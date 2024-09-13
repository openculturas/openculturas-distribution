<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function is_array;
use function is_string;
use function t;

/**
 * @ParagraphsBehavior(
 *   id = "extra_style",
 *   label = @Translation("Extra style."),
 *   description = @Translation("Select an additional style."),
 *   weight = 2
 * )
 */
final class ExtraStyleBehavior extends ParagraphsBehaviorBase {

  public const NONE = 'none';

  /**
   * List of allowed classes from module settings.
   */
  protected array $allowedClasses = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->allowedClasses = [self::NONE => t('None')];
    $configuredAllowedClasses = $container->get('config.factory')
      ->get('openculturas_custom.settings')
      ->get('allowed_classes');
    if (is_array($configuredAllowedClasses)) {
      foreach ($configuredAllowedClasses as $class => $label) {
        if (!is_string($label)) {
          continue;
        }

        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        $instance->allowedClasses[$class] = t($label);
      }
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode): void {
    $settings = $paragraph->getAllBehaviorSettings()[$this->getPluginId()];
    $class = $settings['class'];
    if ($class !== self::NONE) {
      $build['#attributes']['class'][] = $class;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $settings = $paragraph->getAllBehaviorSettings()[$this->getPluginId()];
    $form = [
      '#type' => 'container',
      'class' => [
        '#type' => 'select',
        '#options' => $this->allowedClasses,
        '#title' => $this->t('Style'),
        '#default_value' => $settings['class'],
      ],
    ];
    return [];
  }

}
