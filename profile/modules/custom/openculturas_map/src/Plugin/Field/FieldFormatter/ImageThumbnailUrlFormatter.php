<?php

declare(strict_types=1);

namespace Drupal\openculturas_map\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\media\Entity\Media;

/**
 * Plugin implementation of the 'image_thumbnail_url_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "image_thumbnail_url_formatter",
 *   label = @Translation("Image Thumbnail URL"),
 *   field_types = {
 *      "entity_reference",
 *      "entity_reference_revisions"
 *   }
 * )
 */
class ImageThumbnailUrlFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    assert($items instanceof EntityReferenceFieldItemListInterface);
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      /** @var \Drupal\media\MediaInterface $media */
      $media = Media::load($entity->id());
      if (!$media->hasField('field_media_image')) {
        continue;
      }

      if ($media->get('field_media_image')->isEmpty()) {
        continue;
      }

      $file = $media->get('field_media_image')->entity;

      if (!$file instanceof FileInterface) {
        continue;
      }

      /** @var \Drupal\image\ImageStyleInterface $style */
      $style = ImageStyle::load('thumbnail');
      if (!$style instanceof ImageStyleInterface) {
        throw new \UnexpectedValueException("Invalid Image Style!");
      }

      $uri = $file->getFileUri();
      $uri = is_string($uri) ? $style->buildUrl($uri) : NULL;
      $elements[$delta] = [
        '#markup' => $uri,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'media'
      && parent::isApplicable($field_definition);
  }

}
