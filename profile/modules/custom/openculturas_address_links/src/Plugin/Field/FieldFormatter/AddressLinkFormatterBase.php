<?php

declare(strict_types=1);

namespace Drupal\openculturas_address_links\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\address\AddressInterface;
use Drupal\openculturas_address_links\AddressService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function is_string;

/**
 * Abstract parent class for all address link formatters.
 */
abstract class AddressLinkFormatterBase extends FormatterBase {

  /**
   * The link purpose.
   */
  protected static string $purpose;

  /**
   * Service for converting address objects into plaintext, for use in URLs.
   */
  protected AddressService $addressService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->addressService = $container->get('openculturas_address_links.address_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'title' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);
    $elements['title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('title'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    if ($title = $this->getSetting('title')) {
      $summary[] = $this->t('Title: @title', ['@title' => $title]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewElement($item, $langcode);
    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The item.
   * @param string $langcode
   *   The language code.
   *
   * @return array|null
   *   The renderable array, or NULL if not applicable.
   */
  abstract protected function viewElement(FieldItemInterface $item, string $langcode): ?array;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    if (!is_string(static::$purpose)) {
      return FALSE;
    }

    /** @var \Drupal\openculturas_address_links\AddressService $addressService */
    $addressService = \Drupal::service('openculturas_address_links.address_service');

    return $addressService->isApplicable(static::$purpose);
  }

  /**
   * Builds a link render array from the given field and URL.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   * @param \Drupal\Core\Url $url
   *   The URL.
   *
   * @return array
   *   The link render array.
   */
  protected function buildLinkItem(FieldItemInterface $item, Url $url): array {
    $title = $this->getSetting('title');
    if (!$title) {
      $title = $item->getFieldDefinition()->getLabel();
    }

    return [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
    ];
  }

  protected function viewAddress(AddressInterface $address): ?array {
    if (($url = $this->addressService->buildUrlFromAddress($address, static::$purpose)) instanceof Url) {
      return $this->buildLinkItem($address, $url);
    }

    return NULL;
  }

}
