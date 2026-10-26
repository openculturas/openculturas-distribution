<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_popover_swiffy_slider\InheritedFieldRenderer;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_key_exists;
use function reset;

abstract class ExtraFieldBase extends ExtraFieldDisplayFormattedBase implements ContainerFactoryPluginInterface, InheritFieldInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected ?EntityInterface $eventEntity = NULL;

  /**
   * @var array|null
   */
  protected ?array $referenceViewFormatterSettings = NULL;

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * NULL if entity_popover_swiffy_slider is not installed.
   */
  protected ?InheritedFieldRenderer $inheritedFieldRenderer = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    // @phpstan-ignore new.static
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->inheritedFieldRenderer = $container->has(InheritedFieldRenderer::class) ? $container->get(InheritedFieldRenderer::class) : NULL;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity): array {
    $build = [];
    $reference_field = $this->getInheritEntityReferenceFieldName();
    $fieldname_in_reference = $this->getFieldNameInEntityReference();
    if ($entity->hasField($reference_field) && !$entity->get($reference_field)->isEmpty()) {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $list */
      $list = $entity->get($reference_field);
      if ($list->isEmpty()) {
        return $build;
      }

      $events = $list->referencedEntities();
      if ($events === []) {
        return $build;
      }

      $this->eventEntity = reset($events);
      if (!$this->eventEntity instanceof NodeInterface) {
        return $build;
      }

      if (!$this->eventEntity->hasField($fieldname_in_reference)) {
        return $build;
      }

      if ($this->eventEntity->get($fieldname_in_reference)->isEmpty()) {
        return $build;
      }

      $this->eventEntity = $this->entityRepository->getTranslationFromContext($this->eventEntity);
      $eventViewDisplay = $this->entityDisplayRepository->getViewDisplay(
        $this->eventEntity->getEntityTypeId(),
        $this->eventEntity->bundle(),
        $this->viewMode
      );
      if ($eventViewDisplay->getThirdPartySetting('layout_builder', 'enabled')) {
        $bundle = $this->eventEntity->bundle();
        $entityType = $this->eventEntity->getEntityTypeId();
        /** @var array $sections */
        $sections = $eventViewDisplay->getThirdPartySetting('layout_builder', 'sections', []);
        /** @var \Drupal\layout_builder\Section $section */
        foreach ($sections as $section) {
          foreach ($section->getComponents() as $component) {
            $configuration = (array) $component->get('configuration');
            if (!array_key_exists('id', $configuration)) {
              continue;
            }

            if ($configuration['id'] === sprintf('field_block:%s:%s:%s', $entityType, $bundle, $fieldname_in_reference) && array_key_exists('formatter', $configuration)) {
              $this->referenceViewFormatterSettings = $configuration['formatter'];
              // Found the component. Stop here.
              break 2;
            }
          }
        }
      }
      else {
        $this->referenceViewFormatterSettings = $eventViewDisplay->getComponent($fieldname_in_reference);
      }

      $this->renderer->addCacheableDependency($build, $this->eventEntity);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function view(ContentEntityInterface $entity): array {
    return $this->viewElements($entity);
  }

  /**
   * Renders a field inherited from $this->eventEntity to a markup string.
   *
   * Delegates to entity_popover_swiffy_slider's InheritedFieldRenderer
   * service (if that module is installed) so a popover enabled on the
   * referenced event's own field_gallery/field_mood_image still works on
   * this inherited copy — otherwise, since this bypasses both classic
   * entity rendering and Layout Builder's own field block rendering
   * entirely (calling FieldItemList::view() directly), neither of
   * entity_popover_swiffy_slider's own hooks would ever see it. Falls back
   * to a plain render with no popover support if that module isn't
   * installed: this class's own job (inheriting a field from a referenced
   * entity at all) has nothing to do with entity_popover, so it must keep
   * working without it.
   *
   * @param string $fieldName
   *   The field name on $this->eventEntity to render.
   * @param array $displaySettings
   *   The formatter configuration to render it with.
   */
  protected function renderInheritedField(string $fieldName, array $displaySettings): string|MarkupInterface {
    if (!$this->eventEntity instanceof NodeInterface) {
      return '';
    }

    if ($this->inheritedFieldRenderer instanceof InheritedFieldRenderer) {
      return $this->inheritedFieldRenderer->render($this->eventEntity, $fieldName, $displaySettings);
    }

    $renderArray = $this->eventEntity->get($fieldName)->view($displaySettings);
    return $this->renderer->render($renderArray);
  }

}
