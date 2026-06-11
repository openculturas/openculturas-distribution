<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\Markup;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Extends the Paragraph entity to support additional field types in summaries.
 */
class ExtendedParagraph extends Paragraph {

  /**
   * {@inheritdoc}
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   Truncated plain text or markup from the field value.
   */
  public function getTextSummary($field_name, FieldDefinitionInterface $field_definition) {
    if ($field_definition->getType() === 'heading') {
      $raw = $this->get($field_name)->text;
      $text = is_string($raw) ? $raw : '';
      $summary = Unicode::truncate(trim(html_entity_decode(strip_tags($text))), 150);
      if (empty($summary)) {
        return Markup::create(Unicode::truncate(htmlspecialchars(trim($text)), 150));
      }

      return $summary;
    }

    return parent::getTextSummary($field_name, $field_definition);
  }

}
