<?php

declare(strict_types=1);

namespace Drupal\entity_popover\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_popover\PopoverId;
use Drupal\entity_popover\RenderContextGuard;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a boolean toggle that opens the host entity in a popover dialog.
 */
#[FieldFormatter(
  id: 'entity_popover',
  label: new TranslatableMarkup('Entity popover'),
  field_types: ['entity_popover', 'boolean'],
)]
final class EntityPopoverFormatter extends FormatterBase {

  public function __construct(
    string $pluginId,
    mixed $pluginDefinition,
    FieldDefinitionInterface $fieldDefinition,
    array $settings,
    string $label,
    string $viewMode,
    array $thirdPartySettings,
    protected readonly EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $label, $viewMode, $thirdPartySettings);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition): static {
    return new self(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_display.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function defaultSettings(): array {
    return [
      'view_mode' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function settingsForm(array $form, FormStateInterface $formState): array {
    $viewModes = $this->getAvailableViewModes();

    $element = parent::settingsForm($form, $formState);
    $element['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#description' => $this->t('The view mode used to render this entity inside the popover dialog. The view mode currently used to display this entity is also available, for example to enlarge it without changing its layout.'),
      '#options' => $viewModes,
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function settingsSummary(): array {
    $viewModes = $this->getAvailableViewModes();
    $viewMode = $this->getSetting('view_mode');

    return [
      $this->t('View mode: @view_mode', [
        '@view_mode' => $viewModes[$viewMode] ?? $viewMode,
      ]),
    ];
  }

  /**
   * Returns the view modes available on this field's bundle, keyed by ID.
   *
   * @return array<string, string>
   *   The bundle's view mode labels, keyed by view mode ID.
   */
  protected function getAvailableViewModes(): array {
    $bundle = $this->fieldDefinition->getTargetBundle();
    if ($bundle === NULL) {
      return [];
    }

    return $this->entityDisplayRepository->getViewModeOptionsByBundle(
      $this->fieldDefinition->getTargetEntityTypeId(),
      $bundle,
    );
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $entity = $items->getEntity();
    $entityTypeId = $entity->getEntityTypeId();
    $entityId = PopoverId::entityIdentifier($entity);
    $viewMode = $this->getSetting('view_mode');

    if (\Drupal::service('entity_popover.render_context')->isActive($entityTypeId, $entityId)) {
      // The target view mode displays this same popover-enabled field on
      // the same entity again — skip to avoid infinite recursion.
      return $elements;
    }

    foreach ($items as $delta => $item) {
      if (empty($item->value)) {
        continue;
      }

      $elements[$delta] = [
        '#theme' => 'entity_popover_dialog',
        '#unique_id' => PopoverId::build(
          $entityTypeId,
          $entityId,
          $this->fieldDefinition->getName(),
          $this->viewMode,
          $langcode ?: $items->getLangcode(),
        ),
        '#toggle_label' => $this->t('Zoom'),
        '#close_label' => $this->t('Close'),
        '#target_entity' => $entity,
        '#view_mode' => $viewMode,
        '#field_name' => $this->fieldDefinition->getName(),
        '#pre_render' => [[RenderContextGuard::class, 'preRenderDialog']],
        '#post_render' => [[RenderContextGuard::class, 'postRenderDialog']],
        '#attached' => [
          'library' => ['entity_popover/dialog'],
        ],
      ];
    }

    return $elements;
  }

}
