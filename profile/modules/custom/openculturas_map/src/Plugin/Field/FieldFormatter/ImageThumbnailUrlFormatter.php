<?php

declare(strict_types=1);

namespace Drupal\openculturas_map\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;

/**
 * Plugin implementation of the 'image_thumbnail_url_formatter' formatter.
 */
#[FieldFormatter(
  id: 'image_thumbnail_url_formatter',
  label: new TranslatableMarkup('Image Thumbnail URL'),
  field_types: ['entity_reference', 'entity_reference_revisions'],
)]
class ImageThumbnailUrlFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      /** @var \Drupal\media\MediaInterface|null $media */
      $media = Media::load($entity->id());
      if (!$media instanceof MediaInterface) {
        continue;
      }

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

      $style = ImageStyle::load('thumbnail');
      if ($style === NULL) {
        throw new \UnexpectedValueException("Invalid Image Style!");
      }

      $uri = $file->getFileUri();
      if (!is_string($uri)) {
        continue;
      }

      $elements[(int) $delta] = [
        '#markup' => $style->buildUrl($uri),
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
