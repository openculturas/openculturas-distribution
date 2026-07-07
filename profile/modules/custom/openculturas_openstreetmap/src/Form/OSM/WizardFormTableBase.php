<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Form\OSM;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Drupal\openculturas_openstreetmap\AddressLookup;
use Drupal\openculturas_openstreetmap\OpenStreetMap\DrupalToOpenStreetMapTransformer;
use Geocoder\Formatter\StringFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function is_numeric;
use function is_string;
use function sprintf;

abstract class WizardFormTableBase extends FormBase {

  /**
   * @var \Drupal\openculturas_openstreetmap\AddressLookup
   */
  protected AddressLookup $addressLookup;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container): static {
    /** @var static $instance */
    $instance = parent::create($container);
    $instance->addressLookup = $container->get('openculturas_openstreetmap.addressLookup');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->renderer = $container->get('renderer');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    $header = [
      '',
      ['data' => $this->t('This location profile')],
      $this->t('Operation'),
      'OpenStreetMap',
    ];
    if ($this->getFormId() !== 'select_data') {
      unset($header[2]);
    }

    $form['data'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No information available.'),
    ];

    if (!isset($cached_values['locations']) || !is_string($cached_values['locations']) || $cached_values['locations'] === '' || $cached_values['locations'] === '_empty') {
      return $form;
    }

    $is_new = TRUE;
    $node = NULL;
    if (isset($cached_values['node_id']) && is_numeric($cached_values['node_id'])) {
      $node_id = $cached_values['node_id'];
      $node = $this->entityTypeManager->getStorage('node')
        ->load((int) $node_id);
      if ($node instanceof NodeInterface && $node->bundle() === 'location') {
        $is_new = FALSE;
      }
    }

    [
      $location,
      $location_name,
      $extra_tags,
    ] = $this->addressLookup->lookup($cached_values['locations'], TRUE);

    $formatter = new StringFormatter();

    $formDisplay = $this->entityDisplayRepository->getFormDisplay('node', 'location');

    $field_name = 'field_address_data';
    if ($formDisplay->getComponent($field_name)) {

      $output_local = '';
      $output_osm = sprintf('%s<br>%s', $location_name, $formatter->format($location, '%S %n<br> %z %L'));

      if ($node) {
        $tags = DrupalToOpenStreetMapTransformer::fieldNameToTags($field_name);
        $transformed_data = DrupalToOpenStreetMapTransformer::transformMultiple($node, $tags);
        $output_local = sprintf('%s<br>%s', $transformed_data['name'], sprintf('%s %s<br> %s %s', $transformed_data['addr:street'], $transformed_data['addr:housenumber'], $transformed_data['addr:postcode'], $transformed_data['addr:city']));
      }

      $form['data'][$field_name] = $this->buildTableRow($field_name, [
        'local' => Markup::create($output_local),
        'osm' => Markup::create($output_osm),
      ], $is_new);
    }

    $field_name = 'field_office_hours';
    if ($formDisplay->getComponent($field_name)) {
      $output_local = '';
      $output_osm = $extra_tags->opening_hours ?? '';

      if ($node) {
        $output_local = DrupalToOpenStreetMapTransformer::transform($node, 'opening_hours');
      }

      if ($output_local || $output_osm) {
        $form['data'][$field_name] = $this->buildTableRow($field_name, [
          'local' => Markup::create($output_local),
          'osm' => Markup::create($output_osm),
        ], $is_new);
      }
    }

    if ($formDisplay->getComponent('field_contact_data')) {
      $output_local = '';
      $output_osm = $extra_tags->{'contact:email'} ?? $extra_tags->email ?? '';

      if ($node) {
        $output_local = DrupalToOpenStreetMapTransformer::transform($node, 'email');
      }

      if ($output_local || $output_osm) {
        $form['data']['field_email'] = $this->buildTableRow('field_email', [
          'local' => $output_local,
          'osm' => $output_osm,
        ], $is_new);
      }

      $output_local = '';
      $output_osm = $extra_tags->{'contact:phone'} ?? $extra_tags->phone ?? '';

      if ($node) {
        $output_local = DrupalToOpenStreetMapTransformer::transform($node, 'phone');
      }

      if ($output_local || $output_osm) {
        $form['data']['field_phone'] = $this->buildTableRow('field_phone', [
          'local' => $output_local,
          'osm' => $output_osm,
        ], $is_new);
      }

      $output_local = '';
      $output_osm = $extra_tags->{'contact:website'} ?? $extra_tags->website ?? '';

      if ($node) {
        $output_local = DrupalToOpenStreetMapTransformer::transform($node, 'website');
      }

      if ($output_local || $output_osm) {
        $form['data']['field_url'] = $this->buildTableRow('field_url', [
          'local' => $output_local,
          'osm' => $output_osm,
        ], $is_new);
      }
    }

    if ($formDisplay->getComponent('field_accessibility')) {
      $output_local = '';
      $output_osm = $extra_tags->wheelchair ?? '';

      if ($node) {
        $output_local = DrupalToOpenStreetMapTransformer::transform($node, 'wheelchair');
      }

      if ($output_local || $output_osm) {
        $form['data']['field_a11y_wheelchair'] = $this->buildTableRow('field_a11y_wheelchair', [
          'local' => $output_local,
          'osm' => $output_osm,
        ], $is_new);
      }

      $output_local = '';
      $output_osm = $extra_tags->{'toilets:wheelchair'} ?? '';

      if ($node) {
        $output_local = DrupalToOpenStreetMapTransformer::transform($node, 'toilets:wheelchair');
      }

      if ($output_local || $output_osm) {
        $form['data']['field_a11y_toilets_wheelchair'] = $this->buildTableRow('field_a11y_toilets_wheelchair', [
          'local' => $output_local,
          'osm' => $output_osm,
        ], $is_new);
      }
    }

    return $form;
  }

  /**
   * Helper function to build a table row for our wizard.
   *
   * @param string $field_name
   *   Name of the field.
   * @param array{osm:string|MarkupInterface,local:string|MarkupInterface} $data
   *   An array with key local and the value in drupal
   *    and with the osm with value of OpenstreetMap.
   * @param bool $is_new
   *   TRUE when we have data (A location node) in drupal or FALSE when not.
   *
   * @return array{local:array,operation?:array,osm:array}
   *   An array for the table row.
   */
  protected function buildTableRow(string $field_name, array $data, bool $is_new): array {

    $operation = SyncOperation::None;

    if ($is_new) {
      $operation = SyncOperation::Pull;
    }

    $build = [];
    $markup_or_plain = static fn(string|MarkupInterface $value): array => ($value instanceof MarkupInterface ? ['#markup' => (string) $value] : ['#plain_text' => $value]);
    $build['label'] = [
      '#plain_text' => match ($field_name) {
        'field_address_data' => $this->t('Address data'),
        'field_office_hours' => $this->t('Opening hours'),
        'field_url' => $this->t('Website'),
        'field_email' => $this->t('Email'),
        'field_phone' => $this->t('Phone'),
        'field_a11y_wheelchair' => $this->t('Location wheelchair-accessible'),
        'field_a11y_toilets_wheelchair' => $this->t('Toilets wheelchair-accessible'),
        default => ''
      },
      '#wrapper_attributes' => ['header' => TRUE],
    ];
    $build['local'] = $markup_or_plain($data['local']) + ['#weight' => 1];
    $build['operation'] = [
      '#type' => 'select',
      '#options' => SyncOperation::asOptionList($is_new),
      '#default_value' => $operation->value,
      '#weight' => 2,
      '#disabled' => $is_new ,
    ];

    $build['osm'] = $markup_or_plain($data['osm']) + ['#weight' => 3];
    return $build;
  }

}
