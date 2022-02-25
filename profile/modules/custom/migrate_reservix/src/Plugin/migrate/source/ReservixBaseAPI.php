<?php

namespace Drupal\migrate_reservix\Plugin\migrate\source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Row;
use Drupal\entity_import\Plugin\migrate\source\EntityImportSourceLimitIteratorBase;
use Drupal\migrate_reservix\ReservixApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Provides a flexible, generic import source for the Reservix API.
 */
class ReservixBaseAPI extends EntityImportSourceLimitIteratorBase implements RequirementsInterface {

  /**
   * Available endpoints.
   *
   * @var array
   */
  const API_ENDPOINTS = [
    'custom/artist',
    'sale/event',
    'sale/eventgroup',
    'sale/genre',
    'custom/location',
    'custom/organizer',
    'sale/venue',
  ];

  /**
   * Total entries.
   *
   * @var int
   */
  protected int $totalEntries = 0;

  /**
   * Current page number for API calls.
   *
   * @var int
   */
  protected int $currentPage = 0;

  /**
   * The Reservix API service.
   *
   * @var \Drupal\migrate_reservix\ReservixApiClient
   */
  protected ReservixApiClient $apiService;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')
    );
    $instance->setApiService($container->get('migrate_reservix.client'));
    return $instance;
  }

  /**
   * Setter for API service.
   */
  public function setApiService($api_service) {
    $this->apiService = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    // Always include this fragment at the beginning of every prepareRow()
    // implementation, so parent classes can ignore rows.
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid(): bool {
    $config = $this->getConfiguration();
    return !empty($config['api_key']);
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    $config = $this->getConfiguration();
    if (!isset($config['api_key'])) {
      throw new RequirementsException(
        'Missing required api key token.',
        ['api_key']
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildImportForm(array $form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    // Making an API request will set up total item counts.
    $this->apiRequest();
    $values = [
      $this->t('Ready to import %count items from the endpoint %endpoint.', [
        '%count' => $this->totalEntries,
        '%endpoint' => $config['endpoint'],
      ]),
    ];

    // The form just shows overview data about the items to import.
    $form['access_token'] = [
      '#prefix' => '<h2>' . $this->t('Reservix Importer') . '</h2><pre>',
      '#suffix' => '</pre>',
      '#markup' => implode('<br/>', $values),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config['api_key'],
    ];
    $options = array_combine(self::API_ENDPOINTS, [
      $this->t('Artists'),
      $this->t('Events'),
      $this->t('Eventgroups'),
      $this->t('Genres'),
      $this->t('Locations'),
      $this->t('Organizers'),
      $this->t('Venues'),
    ]);
    $form['endpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('API Endpoint'),
      '#options' => $options,
      '#default_value' => $config['endpoint'],
    ];

    $description = [
      $this->t('JSON encoded parameters to include in request (i.e. [{"is_active": "true"}]).'),
      $this->t('You can specify dynamic dates with [date:today], [date:first day of last month], etc.'),
    ];

    $form['params'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Query Parameters'),
      '#default_value' => $config['params'],
      '#description' => implode('<br/>', $description),
    ];
    $form['throttle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Throttle'),
      '#default_value' => $config['throttle'],
      '#description' => $this->t('Limit the number of API items processed per request.'),
    ];
    return $form;
  }

  /**
   * Initialize migrate source iterator with results from an API request.
   *
   * The request is paginated. We determine the current page based on the
   * limit count and offset. The limit count is stored in configuration.
   * The limit offset is set by the batch processor and increments for
   * each batch (see \Drupal\entity_import\Form\EntityImporterBatchProcess).
   */
  public function initializeIterator() {
    $this->currentPage = ($this->getLimitCount() + $this->getLimitOffset()) / $this->getLimitCount();
    $results = $this->apiRequest($this->currentPage);
    $this->createIterator($results);
    return $this->iterator;
  }

  /**
   * Return the limit count from configuration.
   *
   * @return int
   *   An integer representing the limit count.
   */
  public function getLimitCount(): int {
    $config = $this->getConfiguration();
    if (!empty($config['throttle'])) {
      return $config['throttle'];
    }
    return parent::getLimitCount();
  }

  /**
   * Make an API request.
   *
   * @param int $page
   *   The page number starting.
   *
   * @return array
   *   An associative array of data.
   */
  public function apiRequest(int $page = 0): array {
    $config = $this->getConfiguration();
    $this->apiService->setCredentials($config['api_key']);

    // Set up API request parameters.
    $params = json_decode($config['params'], TRUE);
    $params['page'] = !$page ? $page : $page - 1;
    if ($this->getLimitCount() > 0) {
      $params['limit'] = $this->getLimitCount();
    }

    $response = $this->apiService->get(reset($config['endpoint']), $params);
    $this->totalEntries = $response['totalItems'] ?? 0;
    $response['data'] = $response['data'] ?? [];
    return $response['data'];
  }

  /**
   * Advance to the next row in the iterator, with pagination.
   *
   * Typically, next() will fail when it reaches the end of an iterator.
   * When called outside the context of a batch process (for example, Drush with
   * Migrate Tools) we need to advance the page and attempt another API request.
   * Otherwise, the source would always be limited to a single page.
   */
  public function next() {
    // If not called from inside a batch process, get the next page of data.
    if (!$this->isBatch
        && !$this->getIterator()->valid()) {
      $this->currentPage++;
      $results = $this->apiRequest($this->currentPage);
      if (count($results)) {
        $this->createIterator($results);
      }
    }
    parent::next();
  }

  /**
   * Create the iterator and populate with source data.
   *
   * @param array $source_data
   *   The source data from an API request.
   */
  protected function createIterator(array $source_data) {
    $iterator = new \AppendIterator();
    $iterator->append(new \ArrayIterator($source_data));
    $this->iterator = $iterator;
    return $this->iterator;
  }

  /**
   * Return the total number of items from API.
   */
  protected function doCount(): int {
    $this->apiRequest();
    return $this->totalEntries;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return json_encode(iterator_to_array($this->iterator));
  }

  /**
   * {@inheritdoc}
   *
   * Unused in this plugin, but required by interface.
   */
  public function limitedIterator(): \Iterator {
    return $this->iterator;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultConfiguration(): array {
    return [
      'api_key' => '',
      'endpoint' => 'sale/event',
      'params' => '',
      'throttle' => 200,
    ] + parent::defaultConfiguration();
  }

}
