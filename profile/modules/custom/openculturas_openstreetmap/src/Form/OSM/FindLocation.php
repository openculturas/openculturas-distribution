<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Form\OSM;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Utility\Error;
use Drupal\openculturas_openstreetmap\AddressLookup;
use Drupal\openculturas_openstreetmap\PhotonSearch;
use Geocoder\Formatter\StringFormatter;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function implode;
use function in_array;
use function is_numeric;
use function is_string;
use function md5;
use function sprintf;

final class FindLocation extends FormBase {

  /**
   * @var \Psr\Http\Client\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * @var \Drupal\openculturas_openstreetmap\AddressLookup
   */
  protected AddressLookup $addressLookup;

  /**
   * @var \Drupal\openculturas_openstreetmap\PhotonSearch
   */
  protected PhotonSearch $search;

  public const BUTTON_NAME = 'osm-find-button';

  /**
   * @link https://wiki.openstreetmap.org/wiki/Elements
   */
  public const SUPPORTED_TYPES = ['N', 'W', 'R'];

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    /** @var static $instance */
    $instance = parent::create($container);
    $instance->httpClient = $container->get('http_client');
    $instance->cache = $container->get('cache.default');
    $instance->time = $container->get('datetime.time');
    $instance->addressLookup = $container->get('openculturas_openstreetmap.addressLookup');
    $instance->search = $container->get('openculturas_openstreetmap.search');
    $instance->logger = $container->get('logger.channel.openculturas_openstreetmap');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'find_location';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $userInput = $form_state->getUserInput();
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    $location_name = $userInput['location_name'] ?? ($cached_values['location_name'] ?? NULL);
    $form['location_name'] = [
      '#title' => $this->t('Search by name'),
      '#type' => 'textfield',
      '#placeholder' => $this->t('e. g. Town Hall, City'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['osmfinder-input']],
      '#weight' => 1,
      '#default_value' => $location_name ?? '',
    ];
    $form['find'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find'),
      '#attributes' => ['class' => ['osmfinder-find-button']],
      '#name' => self::BUTTON_NAME,
      '#weight' => 2,
    ];
    $matches = ['_empty' => $this->t('n. a.')];
    if (is_string($location_name) && $location_name !== '' && !is_numeric($location_name)) {
      // On multiple requests/searches, drupal merges the result items, which results to duplicate items.
      // Therefore we use the input as an ID on js side to filter out items which does not match to the input.
      $inputMd5 = md5($location_name);
      $cache_keys = ['osm', 'komoot', $inputMd5];
      $cache = $this->cache->get(implode(':', $cache_keys));
      $resultList = [];

      if (!$cache) {
        $formatter = new StringFormatter();
        try {
          $result = $this->search->find($location_name);
        }
        catch (\Exception $e) {
          $this->messenger()->addError($this->t('A error occurred while searching for locations. Try again later.'));
          Error::logException($this->logger, $e);
        }

        if (isset($result)) {
          /** @var \Geocoder\Provider\Photon\Model\PhotonAddress $location */
          foreach ($result->all() as $location) {
            if (!$location->getName()) {
              continue;
            }

            if (!in_array($location->getOSMType(), self::SUPPORTED_TYPES, TRUE)) {
              continue;
            }

            if (!$location->getOSMId()) {
              continue;
            }

            if (!$location->getCoordinates()) {
              continue;
            }

            $osm_id = $location->getOSMType() . $location->getOSMId();
            $matches[$osm_id] = sprintf('<strong>%s</strong> <span lang="en" class="osm-amenity">%s</span><br>%s', $location->getName(), $location->getOSMTag()->value ?? '', $formatter->format($location, '%S %n, %z %L'));
            $resultList[] = ['resultListId' => $inputMd5, 'geo' => [$location->getCoordinates()->getLatitude(), $location->getCoordinates()->getLongitude()], 'label' => $matches[$location->getOSMType() . $location->getOSMId()]];
          }

          $this->cache->set(implode(':', $cache_keys), ['matches' => $matches, 'result' => $resultList], $this->time->getRequestTime() + (60 * 60 * 24), ['config:openculturas_openstreetmap.settings']);
        }
      }
      else {
        /** @var array $cacheData */
        $cacheData = $cache->data;
        $matches = $cacheData['matches'] ?? [];
        $resultList = $cacheData['result'] ?? [];
      }

      $form['#attached']['drupalSettings']['osmfinder']['result'] = [];
      if ($location_name && $resultList !== []) {
        $form['#attached']['drupalSettings']['osmfinder']['resultListId'] = $inputMd5;
        $form['#attached']['drupalSettings']['osmfinder']['result'] = $resultList;
      }

      if ($location_name && count($matches) > 1) {
        $form['map'] = [
          '#markup' => Markup::create('<div id="osmfinder-map" style="height: 400px; width: 750px;"></div>'),
          '#weight' => 3,
        ];
        $locations = $userInput['locations'] ?? ($cached_values['locations'] ?? '_empty');
        $form['locations'] = [
          '#type' => 'radios',
          '#prefix' => '<div id="osmfinder-result-list">',
          '#suffix' => '</div>',
          '#title' => $this->t('Location', options: ['context' => 'entity_bundle_name']),
          '#options' => $matches,
          '#weight' => 4,
          '#required' => TRUE,
          '#default_value' => $locations,
        ];
      }
      else {
        $form['#attached']['drupalSettings']['osmfinder']['resultListId'] = NULL;
        $form['#attached']['drupalSettings']['osmfinder']['result'] = [];
      }
    }

    $form['#attached']['library'][] = 'openculturas_openstreetmap/finder';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $keys = [
      'location_name',
      'locations',
    ];
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    foreach ($keys as $key) {
      if (!is_string($form_state->getValue($key))) {
        unset($cached_values[$key]);
        $form_state->unsetValue($key);
      }
    }

    if ($form_state->getValue('locations') === '_empty') {
      $form_state->setError($form['locations'], $this->t('@name field is required.', ['@name' => $this->t('Location', options: ['context' => 'entity_bundle_name'])]));
    }

    $form_state->setTemporaryValue('wizard', $cached_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $keys = [
      'location_name',
      'locations',
    ];
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    foreach ($keys as $key) {
      if (is_string($form_state->getValue($key))) {
        $cached_values[$key] = $form_state->getValue($key);
      }
    }

    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
