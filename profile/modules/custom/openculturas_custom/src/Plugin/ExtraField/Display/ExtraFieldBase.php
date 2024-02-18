<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $static = new static($configuration, $plugin_id, $plugin_definition);
    $static->renderer = $container->get('renderer');
    $static->entityTypeManager = $container->get('entity_type.manager');
    $static->entityDisplayRepository = $container->get('entity_display.repository');
    $static->entityRepository = $container->get('entity.repository');
    return $static;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $contentEntity): array {
    $build = [];
    $reference_field = $this->getInheritEntityReferenceFieldName();
    $fieldname_in_reference = $this->getFieldNameInEntityReference();
    if ($contentEntity->hasField($reference_field) && !$contentEntity->get($reference_field)->isEmpty()) {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $list */
      $list = $contentEntity->get($reference_field);
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
      if (!$this->eventEntity instanceof NodeInterface) {
        return $build;
      }

      $this->referenceViewFormatterSettings = $this->entityDisplayRepository->getViewDisplay(
        $this->eventEntity->getEntityTypeId(),
        $this->eventEntity->bundle(),
        $this->viewMode
      )->getComponent($fieldname_in_reference);
      $this->renderer->addCacheableDependency($build, $this->eventEntity);
    }

    return $build;
  }

  public function view(ContentEntityInterface $contentEntity) {
    return $this->viewElements($contentEntity);
  }

}
