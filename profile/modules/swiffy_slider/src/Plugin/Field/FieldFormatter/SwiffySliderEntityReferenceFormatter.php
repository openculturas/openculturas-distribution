<?php

declare(strict_types=1);

namespace Drupal\swiffy_slider\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use function array_filter;
use function array_merge;
use function sprintf;
use function substr;

/**
 * Plugin implementation of the 'Swiffy Slider' formatter.
 *
 * @FieldFormatter(
 *   id = "swiffy_slider_entity_reference",
 *   label = @Translation("Swiffy Slider"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SwiffySliderEntityReferenceFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    unset($settings['link']);
    $settings['swiffy_slider_permalink'] = '';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);
    $elements['swiffy_slider_permalink'] = [
      '#type' => 'url',
      '#title' => $this->t('Perma link'),
      '#description' => $this->t('Copy and paste the link from @target configuration page.', ['@target' => Link::fromTextAndUrl('Swiffy slider', Url::fromUri('https://swiffyslider.com/configuration/'))->toString()]),
      '#maxlength' => 1000,
      '#default_value' => $this->getSetting('swiffy_slider_permalink'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   * @return string[]
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    $suffix = $this->t('Default');
    if ($this->getSetting('swiffy_slider_permalink') !== '') {
      $uri = Url::fromUri($this->getSetting('swiffy_slider_permalink'));
      $suffix = Link::fromTextAndUrl($this->t('Perma link'), $uri)->toString();
    }
    $summary[] = (string) Markup::create(sprintf('%s: %s', $this->t('Swiffy slider'), $suffix));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL): array {
    $elements = parent::view($items, $langcode);
    if ($items->isEmpty()) {
      return $elements;
    }
    $elements['#attached']['library'][] = 'swiffy_slider/swiffy_slider-lib';
    $attributes = new Attribute();
    if ($this->getSetting('swiffy_slider_permalink') !== '') {
      $parsed_url = UrlHelper::parse($this->getSetting('swiffy_slider_permalink'));
      if (isset($parsed_url['query'])) {
        $attributes['class'] = array_filter($parsed_url['query'], fn(string $key): bool => substr($key, 0, 4) !== 'data', ARRAY_FILTER_USE_KEY);
        $data_attributes = array_filter($parsed_url['query'], fn(string $key): bool => substr($key, 0, 4) === 'data', ARRAY_FILTER_USE_KEY);
        $attributes = array_merge($attributes->toArray(), $data_attributes);
        $attributes = new Attribute($attributes);
      }
    }

    $elements['#swiffy_slider_attributes'] = $attributes;
    return $elements;
  }
}


