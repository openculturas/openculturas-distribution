<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\OpenStreetMap;

use Drupal\node\NodeInterface;
use function end;
use function explode;
use function is_array;
use function is_numeric;
use function str_replace;
use function trim;

final class DrupalToOpenStreetMapTransformer {

  public static function transform(NodeInterface $node, string $osm_tag): string {
    return self::transformMultiple($node, [$osm_tag])[$osm_tag] ?? '';
  }

  public static function transformMultiple(NodeInterface $node, array $osm_tags): array {
    /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $field */
    $field = $node->get('field_contact_data');
    $contact_data = NULL;
    if (!$field->isEmpty()) {
      /** @var \Drupal\paragraphs\ParagraphInterface|null $contact_data */
      $contact_data = $field->entity ?? NULL;
    }

    /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $field */
    $field = $node->get('field_address_data');
    $address_data = NULL;
    if (!$field->isEmpty()) {
      /** @var \Drupal\paragraphs\ParagraphInterface|null $address_data */
      $address_data = $field->entity ?? NULL;
    }

    $address = NULL;
    if ($address_data && !$address_data->get('field_address')->isEmpty()) {
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem|null $address */
      $address = $address_data->get('field_address')->first();
    }

    $transformed_data = [];
    foreach ($osm_tags as $osm_tag) {
      if ($contact_data) {
        if ($osm_tag === 'email' || $osm_tag === 'contact:email') {
          if (!$contact_data->get('field_email')->isEmpty()) {
            $field = $contact_data->get('field_email')->first();
            $transformed_data[$osm_tag] = $field ? $field->getString() : '';
          }
        }
        elseif ($osm_tag === 'phone' || $osm_tag === 'contact:phone') {
          if (!$contact_data->get('field_phone')->isEmpty()) {
            $field = $contact_data->get('field_phone')->first();
            $transformed_data[$osm_tag] = $field ? $field->getString() : '';
          }
        }
        elseif ($osm_tag === 'website' || $osm_tag === 'contact:website') {
          if (!$contact_data->get('field_url')->isEmpty()) {
            /** @var \Drupal\link\LinkItemInterface|null $field */
            $field = $contact_data->get('field_url')->first();
            $transformed_data[$osm_tag] = $field ? $field->getUrl()->toString() : '';
          }
        }
      }

      if ($osm_tag === 'opening_hours') {
        /** @var \Drupal\office_hours\Plugin\Field\FieldType\OfficeHoursItemListInterface $field */
        $field = $node->get('field_office_hours');
        $values = is_array($field->getValue()) ? $field->getValue() : [];
        $transformed_data[$osm_tag] = OfficeHoursToOsmFormatter::format($values);
      }

      if ($address) {
        if ($osm_tag === 'name') {
          $transformed_data[$osm_tag] = $address->getOrganization();
        }
        elseif ($osm_tag === 'addr:city') {
          $transformed_data[$osm_tag] = $address->getLocality();
        }
        elseif ($osm_tag === 'addr:postcode') {
          $transformed_data[$osm_tag] = $address->getPostalCode();
        }
        elseif ($osm_tag === 'addr:street') {
          [$street] = self::splitStreetHousenumber($address->getAddressLine1());
          $transformed_data[$osm_tag] = $street;
        }
        elseif ($osm_tag === 'addr:housenumber') {
          [$street, $house_number] = self::splitStreetHousenumber($address->getAddressLine1());
          $transformed_data[$osm_tag] = $house_number;
        }
      }

      /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList|null $field */
      $field = $node->hasField('field_accessibility') ? $node->get('field_accessibility') : NULL;
      if ($field) {
        $a11y_data = NULL;
        if (!$field->isEmpty()) {
          /** @var \Drupal\paragraphs\ParagraphInterface $referenced_entity */
          foreach ($field->referencedEntities() as $referenced_entity) {
            if ($referenced_entity->getType() === 'a11y_wheelchair') {
              $a11y_data = $referenced_entity;
              break;
            }
          }
        }

        if ($a11y_data) {
          if ($osm_tag === 'toilets:wheelchair') {
            if ($a11y_data->hasField('field_a11y_toilets_wheelchair') && !$a11y_data->get('field_a11y_toilets_wheelchair')->isEmpty()) {
              $field = $a11y_data->get('field_a11y_toilets_wheelchair')->first();
              $transformed_data[$osm_tag] = $field ? $field->getString() : '';
            }
          }
          elseif ($osm_tag === 'wheelchair') {
            if ($a11y_data->hasField('field_a11y_wheelchair') && !$a11y_data->get('field_a11y_wheelchair')->isEmpty()) {
              $field = $a11y_data->get('field_a11y_wheelchair')->first();
              $transformed_data[$osm_tag] = $field ? $field->getString() : '';
            }
          }
        }
      }
    }

    return $transformed_data;
  }

  /**
   * Returns the street number and house number.
   */
  public static function splitStreetHousenumber(string $address_line): array {
    $parts = explode(' ', $address_line);
    $house_number = is_numeric(end($parts)) ? end($parts) : '';
    $street = trim(str_replace($house_number, '', $address_line));
    return [$street, $house_number];
  }

  public static function fieldNameToTags(string $field_name): array {
    if ($field_name === 'field_url') {
      return ['website'];
    }

    if ($field_name === 'field_phone') {
      return ['phone'];
    }

    if ($field_name === 'field_email') {
      return ['email'];
    }

    if ($field_name === 'field_office_hours') {
      return ['opening_hours'];
    }

    if ($field_name === 'field_address_data') {
      return ['name', 'addr:postcode', 'addr:city', 'addr:street', 'addr:housenumber'];
    }

    if ($field_name === 'field_a11y_wheelchair') {
      return ['wheelchair'];
    }

    if ($field_name === 'field_a11y_toilets_wheelchair') {
      return ['toilets:wheelchair'];
    }

    return [];
  }

}
