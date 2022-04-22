<?php

namespace Drupal\migrate_reservix;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;

/**
 * A RESTful client for Reservix API.
 *
 * @see https://developer.reservix.de/
 */
class MockReservixApiClient implements ReservixApiClientInterface {

  /**
   * The HTTP Client for making API requests.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Base URI for requests.
   *
   * @var string
   */
  protected string $baseUri = 'https://api.reservix.de/1/';

  /**
   * API key.
   *
   * @var string
   */
  protected string $apiKey;

  /**
   * The constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The http client object.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory object.
   */
  public function __construct(
    Client $http_client,
    ConfigFactory $config_factory
  ) {
    echo __CLASS__ . '::' . __METHOD__, PHP_EOL;
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('migrate_reservix.settings');
  }

  /**
   * Set API key for requests.
   *
   * @param string $api_key
   *   The API key.
   */
  public function setCredentials(string $api_key) {
    $this->apiKey = $api_key;
  }

  /**
   * Generic request method.
   *
   * @param string $method
   *   The request method.
   * @param string $endpoint
   *   The API endpoint.
   * @param array $query_params
   *   The request parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function request(string $method, string $endpoint, array $query_params = []): array {
    $params = [
      'headers' => [
        'x-api-key' => $this->apiKey,
        'x-api-output-format' => 'application/json',
      ],
      'query' => $query_params,
      'timeout' => 0,
    ];
    $result = $this->httpClient->{$method}(
      $this->baseUri . $endpoint,
      $params
    );

    // Remove invalid control characters from json string.
    $json = preg_replace('/[\x00-\x1F\x7F]/', '', $result->getBody());
    // Log occurrences of invalid control characters.
    if ($result->getBody() !== $json) {
      \Drupal::logger('migrate_reservix')
        ->debug('Invalid control characters have been stripped of the json response at @url with <pre>@params</pre>', [
          '@url' => $this->baseUri . $endpoint,
          '@params' => print_r($params, TRUE),
        ]);
    }

    return (array) json_decode((string) $json, TRUE);
  }

  /**
   * Get request.
   *
   * @param string $endpoint
   *   The API endpoint.
   * @param array $params
   *   The request parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function get(string $endpoint, array $params = []): array {
    $params = [
      'lat' => $this->config->get('latitude'),
      'lng' => $this->config->get('longitude'),
      'radius' => $this->config->get('radius'),
    ] + $params;

    switch ($endpoint) {
      case 'custom/artist':
        return $this->getArtists($params);

      case 'custom/location':
        return $this->getLocations($params);

      case 'custom/organizer':
        return $this->getOrganizers($params);

      case 'custom/imagesdetail':
        return $this->getDetailImages($params);

      case 'custom/imagesslideshow':
        return $this->getSlideshowImages($params);

      case 'sale/event':
        return $this->getEvents($params);

      case 'sale/genre':
        return $this->getGenres($params);

      case 'sale/venue':
        return $this->getVenues($params);

      case 'sale/eventgroup':
        return $this->getEventgroups($params);

      default:
        break;
    }
    return [];
  }

  /**
   */
  public function getGenres(array $params = []): array {
    $filename = 'genres.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

  /**
   */
  public function getVenues(array $params = []): array {
    $filename = 'venues.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

  /**
   */
  public function getEvents(array $params = []): array {
    echo __METHOD__, PHP_EOL;
    $filename = 'events.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

  /**
   */
  public function getOrganizers(array $params = []): array {
    $filename = 'organizers.json';
    return $response = json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);;
  }

  /**
   */
  public function getLocations(array $params = []): array {
    $filename = 'locations.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

  /**
   */
  public function getArtists(array $params = []): array {
    $filename = 'artists.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

  /**
   */
  public function getImages(array $params = [], int $type = self::IMAGE_DETAIL): array {
    $filename = 'images.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

  /**
   */
  public function getDetailImages(array $params = []): array {
    $filename = 'image-details.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

  /**
   */
  public function getSlideshowImages(array $params = []): array {
    $filename = 'images-slideshow.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

  /**
   */
  public function getEventgroups(array $params = []): array {
    return [];
    $filename = 'images-slideshow.json';
    return json_decode(file_get_contents(__DIR__ . '/../tests/assets/' . $filename), TRUE);
  }

}
