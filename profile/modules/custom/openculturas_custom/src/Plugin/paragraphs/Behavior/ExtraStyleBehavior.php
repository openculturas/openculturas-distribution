<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   *
   * @var array
   */
  protected array $allowedClasses = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $extraStyleBehavior = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $extraStyleBehavior->allowedClasses = [static::NONE => t('None')];
    $allowedClasses = $container->get('config.factory')
      ->get('openculturas_custom.settings')
      ->get('allowed_classes');
    if (empty($allowedClasses)) {
      $container->get('messenger')->addWarning('No style for selection defined, contact your sidebuilder.');
    }
    else {
      foreach ($allowedClasses as $class => $label) {
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        $extraStyleBehavior->allowedClasses[$class] = t($label);
      }
    }

    return $extraStyleBehavior;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $entityViewDisplay, $view_mode): void {
    $settings = $paragraph->getAllBehaviorSettings()[$this->getPluginId()];
    $class = $settings['class'];
    if ($class != static::NONE) {
      $build['#attributes']['class'][] = $class;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $formState): array {
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
