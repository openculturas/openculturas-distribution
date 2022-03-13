<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ExtraFieldBase extends ExtraFieldDisplayFormattedBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface|null
   */
  protected $eventEntity;

  /**
   * @var array
   */
  protected $referenceViewFormatterSettings;

  /**
   * @var string
   */
  private $fieldname;

  /**
   * @var string
   */
  private $referenceField;

  /**
   * @param string $fieldname
   */
  public function setFieldname(string $fieldname): void {
    $this->fieldname = $fieldname;
  }

  /**
   * @param string $referenceField
   */
  public function setReferenceField(string $referenceField): void {
    $this->referenceField = $referenceField;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $build = [];
    if ($this->fieldname === NULL || $this->referenceField === NULL) {
      return $build;
    }
    if ($entity->hasField($this->referenceField) && !$entity->get($this->referenceField)->isEmpty()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $this->eventEntity = $entity->get($this->referenceField)->first()->entity;
      if ($this->fieldname !== NULL && (!$this->eventEntity->hasField($this->fieldname) || $this->eventEntity->get($this->fieldname)->isEmpty())) {
        return $build;
      }
      $this->referenceViewFormatterSettings = $this->entityDisplayRepository->getViewDisplay(
        $this->eventEntity->getEntityTypeId(),
        $this->eventEntity->bundle(),
        $this->viewMode
      )->getComponent($this->fieldname);
      $this->renderer->addCacheableDependency($build, $this->eventEntity);
    }

    return $build;
  }

  public function view(ContentEntityInterface $entity) {
    return $this->viewElements($entity);
  }

}
