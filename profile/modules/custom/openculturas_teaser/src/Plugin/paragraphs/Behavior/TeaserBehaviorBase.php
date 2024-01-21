<?php

declare(strict_types = 1);

namespace Drupal\openculturas_teaser\Plugin\paragraphs\Behavior;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class TeaserBehaviorBase extends ParagraphsBehaviorBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var array
   */
  protected array $cacheTags = [];

  /**
   * @var string
   *   Child classes should define the description field.
   */
  protected string $descriptionField = 'body';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $formState): array {
    $settings = $paragraph->getAllBehaviorSettings()[$this->getPluginId()];

    $form['#type'] = 'details';
    $form += [
      '#title' => $this->t('Override teaser content'),
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $settings['title'] ?? '',
      ],
      'subtitle' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subtitle'),
        '#default_value' => $settings['subtitle'] ?? '',
      ],
      'body' => [
        '#type' => 'textarea',
        '#title' => $this->t('Text'),
        '#default_value' => $settings['body'] ?? '',
      ],
      'media' => [
        '#type' => 'media_library',
        '#allowed_bundles' => ['image'],
        '#title' => $this->t('Media'),
        '#default_value' => $settings['media'] ?? NULL,
        '#description' => $this->t('Upload or select a teaser image.'),
      ],
    ];
    return [];
  }

  /**
   * Find the first media reference field in entity's selected viewmode.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $contentEntity
   *   The entity.
   * @param string $viewMode
   *   Selected viewmode.
   *
   * @return int|null
   *   The media id, if found
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTeaserMediaId(ContentEntityInterface $contentEntity, string $viewMode): ?int {
    $entityType = $contentEntity->getEntityTypeId();
    $bundle = $contentEntity->bundle();

    $layoutBuilderEntityViewDisplayStorage = $this->entityTypeManager->getStorage('entity_view_display');
    $display = $layoutBuilderEntityViewDisplayStorage->load(sprintf('%s.%s.%s', $entityType, $bundle, $viewMode));
    if ($display instanceof EntityViewDisplayInterface) {
      foreach ($display->getComponents() as $key => $item) {
        if ($item['type'] == 'entity_reference_entity_view') {
          /** @var \Drupal\Core\Field\FieldConfigInterface|null $fieldConfig */
          $fieldConfig = $contentEntity->getFieldDefinition($key);
          if (!$fieldConfig instanceof FieldConfigInterface) {
            return NULL;
          }

          $handler = $fieldConfig->getSetting('handler');
          if ($handler == 'default:media') {
            $mediaReferenceItem = $contentEntity->get($key)->first();
            if ($mediaReferenceItem instanceof EntityReferenceItem) {
              return (int) ($mediaReferenceItem->target_id);
            }

            return NULL;
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Creates the render array for a teaser.
   *
   * @param array $build
   *   Original render array.
   * @param array $settings
   *   Paragraph's settings.
   * @param string $entityKey
   *   The array key, that contains the entity to tease.
   *
   * @return array
   *   The complete render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getBaseBuildArray(array $build, array $settings, string $entityKey): array {
    if (!array_key_exists($entityKey, $build)) {
      $this->messenger()
        ->addError($this->t('Invalid arguments provided, @entity_key does not exist.', ['@entity_key' => $entityKey]));
      return $build;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $originalEntity */
    $originalEntity = $build[$entityKey];
    $build['#theme'] = 'teaser';
    $entity = clone $originalEntity;
    $viewMode = $build['#view_mode'];
    $this->cacheTags = Cache::mergeTags($this->cacheTags, $originalEntity->getCacheTags());
    if (!empty($settings['title'])) {
      $def = $this->entityTypeManager->getDefinition($entity->getEntityTypeId());
      if ($def instanceof EntityTypeInterface) {
        $labelField = $def->getKey('label');
        if (is_string($labelField)) {
          $entity->set($labelField, $settings['title']);
        }
      }
    }

    $build['#title'] = $entity->label();
    $build['#url'] = $entity->toUrl();
    if ($entity->hasField('field_subtitle')) {
      if (!empty($settings['subtitle'])) {
        $entity->set('field_subtitle', $settings['subtitle']);
      }

      $subtitle = $entity->get('field_subtitle')->getValue();
      if (!empty($subtitle)) {
        $build['#subtitle'] = $entity->get('field_subtitle')
          ->view($viewMode);
      }
    }

    if ($entity->hasField($this->descriptionField)) {
      if (!empty($settings['body'])) {
        $entity->set($this->descriptionField, $settings['body']);
      }

      $description = $entity->get($this->descriptionField);
      if (!$description->isEmpty()) {
        $build['#description'] = $description->view($viewMode);
      }
    }

    $mid = $this->getTeaserMediaId($entity, $viewMode);
    if (!empty($settings['media'])) {
      $mid = $settings['media'];
    }

    if ($mid) {
      $media = $this->entityTypeManager->getStorage('media')->load($mid);
      if ($media instanceof MediaInterface) {
        $this->cacheTags = Cache::mergeTags($this->cacheTags, $media->getCacheTags());
        $build['#media'] = $this->entityTypeManager->getViewBuilder('media')
          ->view($media, 'teaser_image');
      }
    }

    $build['#cache']['tags'] = $this->cacheTags;
    $build[$entityKey] = $entity;
    return $build;
  }

}
