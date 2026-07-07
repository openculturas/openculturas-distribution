<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

#[FieldType(
  id: "osm_id",
  label: new TranslatableMarkup("OSM ID"),
  description: new TranslatableMarkup("Stores an ID corresponding with an OpenStreetMap item."),
  default_widget: "osm_id_default",
  default_formatter: "basic_string",
  cardinality: 1
)]
final class OsmIdItem extends FieldItemBase {
  /**
   * The maximum length for the osm-id value.
   */
  public const int MAX_LENGTH = 256;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => self::MAX_LENGTH,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('OSM ID'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  #[\Override]
  public function isEmpty(): bool {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getConstraints(): array {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => [
        'Length' => [
          'max' => self::MAX_LENGTH,
          'maxMessage' => $this->t('%name: the OSM ID may not exceed @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => self::MAX_LENGTH]),
        ],
      ],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Random\RandomException
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $values['value'] = random_int(10 ** 8, (10 ** 9) - 1);
    return $values;
  }

}
