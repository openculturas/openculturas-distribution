<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\openculturas_openstreetmap\Form\OSM\SyncOperation;
use Drupal\openculturas_openstreetmap\OpenStreetMap\ApiClient;
use Drupal\openculturas_openstreetmap\Plugin\Field\FieldType\OsmIdItem;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Ujamii\OsmOpeningHours\OsmStringToOpeningHoursConverter;
use function array_merge;
use function array_slice;
use function date;
use function explode;
use function implode;
use function in_array;
use function sprintf;
use function strtotime;
use function substr;

/**
 * Defines the 'osm_id' field widget.
 */
#[FieldWidget(
  id: 'osm_id_default',
  label: new TranslatableMarkup('OSM ID'),
  field_types: ['osm_id']
)]
final class OSMIDDefaultWidget extends WidgetBase {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * @var \Drupal\openculturas_openstreetmap\OpenStreetMap\ApiClient
   */
  protected ApiClient $apiClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->state = $container->get('state');
    $instance->apiClient = $container->get('openculturas_openstreetmap.api_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    if ($this->isDefaultValueWidget($form_state)) {
      return $element;
    }

    $element += [
      '#type' => 'fieldset',
      '#attached' => [
        'library' => ['openculturas_openstreetmap/finder'],
      ],
    ];
    if (SyncOperation::canPush()) {
      $element['#description'] = $this->t('Only <em>saved</em> changes can be submitted to OpenStreetMap.');
      $element['#description_display'] = 'before';
    }

    $element['status_messages'] = [
      '#type' => 'status_messages',
    ];
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    $limit_validation_errors = [array_merge($parents, [$field_name])];

    // Create an ID suffix from the parents to make sure each widget is unique.
    $id_suffix = $parents ? '-' . implode('-', $parents) : '';
    $rebuild_form_name = $field_name . '-rebuild-form' . $id_suffix;
    $element['rebuild_form'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update widget'),
      '#name' => $rebuild_form_name,
      '#submit' => [[$this, 'rebuildFormSubmit']],
      '#ajax' => [
        'callback' => [$this, 'rebuildFormAjaxCallback'],
        'progress' => [
          'type' => 'fullscreen',
        ],
      ],
      '#limit_validation_errors' => $limit_validation_errors,
      '#attributes' => [
        'data-osm-rebuild-form' => '',
        'class' => ['js-hide'],
      ],
    ];

    $element['rebuild_data_list'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'data-osm-rebuild-list' => '',
      ],
    ];
    $open_button_name = $field_name . '-open-button' . $id_suffix;
    /** @var \Drupal\node\NodeForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_object->getEntity();

    $route_parameters = ['js' => 'nojs', 'step' => 'findLocation'];
    if (!$node->isNew()) {
      $route_parameters['node_id'] = $node->id();
      $osm_id = (string) $node->get('field_osm_id')->first()?->getString();
      if ($osm_id !== '') {
        $route_parameters['step'] = 'selectData';
        $route_parameters['locations'] = $osm_id;
      }
    }

    if ($this->lockWidget()) {
      $element['locked_widget'] = [
        '#markup' => '<p class="messages messages--warning">' . $this->t('This function is disabled because the module is not yet properly configured.') . '</p>',
      ];
    }

    $element['open_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Match with OSM'),
      '#name' => $open_button_name,
      '#ajax' => [
        'url' => Url::fromRoute('openculturas_openstreetmap.osm-finder-dialog.step', $route_parameters),
        'dialogType' => 'modal',
        'dialog' => ['width' => 800],
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Opening finder...'),
        ],
      ],
      '#limit_validation_errors' => [],
      '#disabled' => $this->lockWidget(),
    ];

    $current_osm_id = $items[$delta]->value ?? NULL;
    if (is_string($current_osm_id) && $current_osm_id !== '') {
      $osm_id = substr($current_osm_id, 1, -1);
      $osm_type_short = $current_osm_id[0];
      $osm_type = NULL;
      if ($osm_type_short === 'N') {
        $osm_type = 'node';
      }
      elseif ($osm_type_short === 'W') {
        $osm_type = 'way';
      }
      elseif ($osm_type_short === 'R') {
        $osm_type = 'relation';
      }

      if ($osm_type) {
        $api_endpoint = ApiClient::ENDPOINT;
        if ($this->state->get('openculturas_openstreetmap.settings.devmode')) {
          $osm_type = 'node';
          $osm_id = $this->state->get('openculturas_openstreetmap.settings.osmid');
          $api_endpoint = ApiClient::DEV_ENDPOINT;
        }

        /** @var string $osm_id */
        $url = Url::fromUri(sprintf('%s/%s/%s', $api_endpoint, $osm_type, $osm_id));
        $element['current_osmid'] = [
          '#markup' => '<p>' . $this->t('<a href="@osm_url" target="_blank">Open connected location on OpenStreetMap<span class="new-tab"> (in new tab)</span></a>', ['@osm_url' => $url->toString()]) . '</p>',
        ];
      }
    }

    $element['value'] = [
      '#title' => $this->t('OSM ID'),
      '#type' => 'hidden',
      '#default_value' => $current_osm_id,
      '#maxlength' => OsmIdItem::MAX_LENGTH,
      '#attributes' => [
        'data-osm-id-value' => '',
      ],
    ];
    return $element;
  }

  public static function rebuildFormSubmit(array $form, FormStateInterface $form_state): void {
    /** @var array $button */
    $button = $form_state->getTriggeringElement();
    /** @var array $field_element */
    $field_element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $osm_id_value = $field_element['value']['#value'] ?? NULL;
    $osm_rebuild_list = $field_element['rebuild_data_list']['#value'] ?? NULL;
    if (empty($osm_id_value) || empty($osm_rebuild_list)) {
      return;
    }

    $user_input = &$form_state->getUserInput();
    /** @var array $field_element_user_input */
    $field_element_user_input = NestedArray::getValue($user_input, array_slice($button['#parents'], 0, -1));
    $field_element_user_input['rebuild_data_list'] = '';
    NestedArray::setValue($user_input, array_slice($button['#parents'], 0, -1), $field_element_user_input);
    unset($field_element, $field_element_user_input);

    /** @var \Drupal\openculturas_openstreetmap\AddressLookup $addressLookup */
    $addressLookup = \Drupal::service('openculturas_openstreetmap.addressLookup');
    [
      $location,
      $location_name,
      $extra_tags,
    ] = $addressLookup->lookup($osm_id_value, TRUE);

    $osm_rebuild_list_array = explode(',', $osm_rebuild_list);

    if (in_array('field_address_data', $osm_rebuild_list_array, TRUE)) {
      $field_parents = $form['field_address_data']['widget'][0]['subform']['#parents'] ?? NULL;
      /** @var array $field_user_input */
      $field_user_input = NestedArray::getValue($user_input, $field_parents);
      $field_value = [
        'organization' => $location_name,
        'address_line1' => $location->getStreetName() . ' ' . $location->getStreetNumber(),
        'postal_code' => $location->getPostalCode(),
        'locality' => $location->getLocality(),
        'country_code' => 'DE',
      ];
      $field_user_input['field_address'][0]['address'] = $field_value;
      NestedArray::setValue($user_input, $field_parents + ['field_address', 0, 'address'], $field_user_input);
      $point = sprintf('POINT(%f %f)', $location->getCoordinates()->getLongitude(), $location->getCoordinates()->getLatitude());
      $field_user_input['field_address_location'][0] = ['value' => $point];
      NestedArray::setValue($user_input, $field_parents + ['field_address_location', 0, 'value'], $field_user_input);
      unset($field_parents, $field_user_input, $field_value, $point);
    }

    if ($extra_tags instanceof \stdClass) {
      $values = [];
      if (isset($extra_tags->email) && in_array('field_email', $osm_rebuild_list_array)) {
        $values['field_email'] = ['value' => $extra_tags->email];
      }
      elseif (isset($extra_tags->{'contact:email'}) && in_array('field_email', $osm_rebuild_list_array)) {
        $values['field_email'] = ['value' => $extra_tags->{'contact:email'}];
      }

      if (isset($extra_tags->phone) && in_array('field_phone', $osm_rebuild_list_array)) {
        $values['field_phone'] = ['value' => $extra_tags->phone];
      }
      elseif (isset($extra_tags->{'contact:phone'}) && in_array('field_phone', $osm_rebuild_list_array)) {
        $values['field_phone'] = ['value' => $extra_tags->{'contact:phone'}];
      }

      if (isset($extra_tags->website) && in_array('field_url', $osm_rebuild_list_array)) {
        $values['field_url'] = ['uri' => $extra_tags->website, 'title' => 'Website'];
      }
      elseif (isset($extra_tags->{'contact:website'}) && in_array('field_url', $osm_rebuild_list_array)) {
        $values['field_url'] = ['uri' => $extra_tags->{'contact:website'}, 'title' => 'Website'];
      }

      $widget_state = self::getWidgetState($form['#parents'], 'field_contact_data', $form_state);
      if ($values !== []) {
        $entityTypeManager = \Drupal::entityTypeManager();
        if (!isset($widget_state['paragraphs'])) {
          $paragraph_module_display = $entityTypeManager->getStorage('entity_form_display')->load('paragraph.contact_data.default');
          /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
          $paragraph = $entityTypeManager->getStorage('paragraph')->create(['type' => 'contact_data'] + $values);
          $widget_state['paragraphs'] = [['entity' => $paragraph, 'mode' => 'edit', 'display' => $paragraph_module_display]];
          $widget_state['items_count'] = 1;
          $widget_state['real_item_count'] = 1;
          $widget_state['selected_bundle'] = 'contact_data';
          self::setWidgetState($form['#parents'], 'field_contact_data', $form_state, $widget_state);
        }
        else {
          $field_parents = $form['field_contact_data']['widget']['#parents'];
          /** @var array $field_user_input */
          $field_user_input = NestedArray::getValue($user_input, $field_parents);
          foreach ($values as $field_name => $value) {
            $field_user_input[0]['subform'][$field_name][0] = $value;
          }

          NestedArray::setValue($user_input, $field_parents, $field_user_input);
        }
      }

      unset($widget_state, $paragraph, $field_parents, $field_user_input, $values);

      if (isset($extra_tags->opening_hours) && in_array('field_office_hours', $osm_rebuild_list_array)) {
        $field_parents = $form['field_office_hours']['widget']['#parents'];
        /** @var array $field_user_input */
        $field_user_input = NestedArray::getValue($user_input, $field_parents);
        $field_value = [];
        try {
          $hours = OsmStringToOpeningHoursConverter::openingHoursFromOsmString($extra_tags->opening_hours);
          foreach ($hours->forWeek() as $day => $openingHoursForDay) {
            // Will return 0 for Sunday through to 6 for Saturday.
            $value = ['day' => date('w', (int) strtotime((string) $day))];
            /** @var \Spatie\OpeningHours\TimeRange $current */
            foreach ($openingHoursForDay->getIterator() as $current) {
              $value['starthours'] = ['time' => (string) $current->start()];
              $value['endhours'] = ['time' => (string) $current->end()];
            }

            $field_value[] = $value;
            // Each day has a hidden a slot value. 7 Days -> 14 items.
            $value['starthours']['time'] = '';
            $value['endhours']['time'] = '';
            $field_value[] = $value;
          }

          $field_user_input['office_hours'][0]['value'] = $field_value;
          NestedArray::setValue($user_input, $field_parents, $field_user_input);
        }
        catch (\Exception $exception) {
        }
      }

      unset($field_parents, $field_user_input, $field_value, $value, $day, $hours, $openingHoursForDay);

      $values = [];
      if (isset($extra_tags->wheelchair) && in_array('field_a11y_wheelchair', $osm_rebuild_list_array)) {
        $values['field_a11y_wheelchair'] = $extra_tags->wheelchair;
      }

      if (isset($extra_tags->{'toilets:wheelchair'}) && in_array('field_a11y_toilets_wheelchair', $osm_rebuild_list_array)) {
        $values['field_a11y_toilets_wheelchair'] = $extra_tags->{'toilets:wheelchair'};
      }

      $widget_state = self::getWidgetState($form['#parents'], 'field_accessibility', $form_state);
      if ($values !== []) {
        /** @var array|null $widget_paragraphs */
        $widget_paragraphs = $widget_state['paragraphs'] ?? NULL;
        if (!$widget_paragraphs) {
          $entityTypeManager = \Drupal::entityTypeManager();
          $paragraph_module_display = $entityTypeManager->getStorage('entity_form_display')
            ->load('paragraph.a11y_wheelchair.default');
          /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
          $paragraph = $entityTypeManager->getStorage('paragraph')
            ->create(['type' => 'a11y_wheelchair'] + $values);
          $widget_state['paragraphs'] = [
            [
              'entity' => $paragraph,
              'mode' => 'edit',
              'display' => $paragraph_module_display,
            ],
          ];
          $widget_state['items_count'] = 1;
          $widget_state['real_item_count'] = 1;
          $widget_state['selected_bundle'] = 'a11y_wheelchair';
        }
        else {
          $add_new = TRUE;
          foreach ($widget_paragraphs as $delta => $widget_paragraph) {
            if ($widget_paragraph['entity']->getType() === 'a11y_wheelchair') {
              $add_new = FALSE;
              $field_parents = $form['field_accessibility']['widget']['#parents'];
              /** @var array $field_user_input */
              $field_user_input = NestedArray::getValue($user_input, $field_parents);
              foreach ($values as $field_name => $value) {
                $field_user_input[$delta]['subform'][$field_name] = $value;
              }

              NestedArray::setValue($user_input, $field_parents, $field_user_input);
              break;
            }
          }

          if ($add_new) {
            $entityTypeManager = \Drupal::entityTypeManager();
            $paragraph_module_display = $entityTypeManager->getStorage('entity_form_display')
              ->load('paragraph.a11y_wheelchair.default');
            /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
            $paragraph = $entityTypeManager->getStorage('paragraph')
              ->create(['type' => 'a11y_wheelchair'] + $values);
            $new_widget_paragraph = [
              'entity' => $paragraph,
              'mode' => 'edit',
              'display' => $paragraph_module_display,
            ];
            $widget_state['paragraphs'][] = $new_widget_paragraph;
            $widget_state['items_count']++;
            $widget_state['real_item_count']++;
          }
        }

        self::setWidgetState($form['#parents'], 'field_accessibility', $form_state, $widget_state);
      }

      unset($widget_state, $values, $field_parents, $field_user_input, $paragraph, $paragraph_module_display);
    }

    $form_state->setRebuild();

  }

  public static function rebuildFormAjaxCallback(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    /** @var array $button */
    $button = $form_state->getTriggeringElement();
    /** @var array{rebuild_data_list:string} $button_values */
    $button_values = $form_state->getValue(array_slice($button['#parents'], 0, -1));
    if ($button_values['rebuild_data_list'] === '') {
      return $response;
    }

    $response->addCommand(new MessageCommand(t('You have unsaved changes.'), options: ['type' => 'warning']));
    $rebuild_data_list = explode(',', $button_values['rebuild_data_list']);
    if (in_array('field_address_data', $rebuild_data_list, TRUE)) {
      $widget = $form['field_address_data']['widget'];
      $selector = sprintf('[data-drupal-selector="%s"]', $widget['#attributes']['data-drupal-selector']);
      $response->addCommand(new ReplaceCommand($selector, $widget));
    }

    if (in_array('field_office_hours', $rebuild_data_list, TRUE)) {
      $widget = $form['field_office_hours']['widget']['office_hours'][0];
      $selector = sprintf('[data-drupal-selector="%s"]', $widget['#attributes']['data-drupal-selector']);
      $response->addCommand(new ReplaceCommand($selector, $widget));
    }

    $subfields = ['field_url', 'field_email', 'field_phone'];
    foreach ($subfields as $subfield) {
      if (in_array($subfield, $rebuild_data_list, TRUE)) {
        $widget = $form['field_contact_data']['widget'];
        $selector = sprintf('[data-drupal-selector="%s"]', $widget['#attributes']['data-drupal-selector']);
        $response->addCommand(new ReplaceCommand($selector, $widget));
        // Needs only one field to rebuild the widget.
        break;
      }
    }

    $subfields = ['field_a11y_wheelchair', 'field_a11y_toilets_wheelchair'];
    foreach ($subfields as $subfield) {
      if (in_array($subfield, $rebuild_data_list, TRUE)) {
        $widget = $form['field_accessibility']['widget'];
        $selector = sprintf('[data-drupal-selector="%s"]', $widget['#attributes']['data-drupal-selector']);
        $response->addCommand(new ReplaceCommand($selector, $widget));
        // Needs only one field to rebuild the widget.
        break;
      }
    }

    return $response;
  }

  protected function lockWidget(): bool {
    return !$this->apiClient->canPush();
  }

}
