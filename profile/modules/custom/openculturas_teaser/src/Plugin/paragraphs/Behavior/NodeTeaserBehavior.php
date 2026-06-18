<?php

declare(strict_types=1);

namespace Drupal\openculturas_teaser\Plugin\paragraphs\Behavior;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Attribute\ParagraphsBehavior;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;

#[ParagraphsBehavior(
  id: 'node_teaser',
  label: new TranslatableMarkup('Node teaser.'),
  description: new TranslatableMarkup("Allow overriding node's teaser values."),
  weight: 2
)]
class NodeTeaserBehavior extends TeaserBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode): void {
    $settings = (array) ($paragraph->getAllBehaviorSettings()[$this->getPluginId()] ?? []);
    if (empty($build['field_article'])) {
      return;
    }

    /** @var array{field_article: array<0,array>} $build */
    $buildNode = &$build['field_article'][0];
    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $buildNode['#node'];
    if ($node instanceof NodeInterface) {
      $id = sprintf('%s-%s-%s', $paragraph->bundle(), $paragraph->id(), $node->id());
      $buildNode = $this->getBaseBuildArray($buildNode, $settings, '#node');
      $buildNode['#attributes'] = new Attribute([
        'class' => [
          'teaser-internal',
          'teaser-node',
        ],
        'id' => $id,
      ]);
    }

    $cacheableMetadata = CacheableMetadata::createFromRenderArray($buildNode);
    $cacheableMetadata->addCacheableDependency($paragraph);
    $cacheableMetadata->applyTo($buildNode);
    // We need an additional cache key, or the field renders all references
    // with default cache keys. (entity_view:ENTITY_TYPE_ID:ENTITY_ID:VIEW_MODE:LANGCODE).
    $buildNode['#cache']['keys'][] = 'ParagraphsBehavior-' . $paragraph->id();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsTypeInterface $paragraphs_type): bool {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager */
    $fieldManager = \Drupal::service('entity_field.manager');
    $fieldDefinitions = $fieldManager->getFieldDefinitions('paragraph', (string) $paragraphs_type->id());
    $baseFieldDefinitions = $fieldManager->getBaseFieldDefinitions('paragraph');
    $fieldKeys = array_diff(array_keys($fieldDefinitions), array_keys($baseFieldDefinitions));
    foreach ($fieldKeys as $item) {
      $fieldDefinition = $fieldDefinitions[$item];
      if ($fieldDefinition->getType() === 'entity_reference') {
        $handler = $fieldDefinition->getSetting('handler');
        if ($handler === 'default:node') {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
