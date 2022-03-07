<?php

namespace Drupal\migrate_reservix;

use Drupal\Core\Config\ConfigFactory;
use Drupal\migrate_reservix\Plugin\migrate\source\ReservixBaseAPI;
use GuzzleHttp\Client;

/**
 * A RESTful client for Reservix API.
 *
 * @see https://developer.reservix.de/
 */
class ReservixApiClient {

  const IMAGE_DETAIL = 1;

  const IMAGE_SLIDESHOW = 2;

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

      default:
        if (in_array($endpoint, ReservixBaseAPI::API_ENDPOINTS)) {
          return $this->request('GET', $endpoint, $params);
        }
    }
    return [];
  }

  /**
   * Get genres API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getGenres(array $params = []): array {
    return $this->get('sale/genre', $params);
  }

  /**
   * Get venues API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getVenues(array $params = []): array {
    return $this->get('sale/venue', $params);
  }

  /**
   * Get events API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getEvents(array $params = []): array {
    return $this->get('sale/event', $params);
  }

  /**
   * Get organizers API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getOrganizers(array $params = []): array {
    $response = $this->get('sale/event', $params);
    $rows = $response;
    $rows['data'] = [];
    foreach ($response['data'] as $event) {
      if (isset($event['references'])
        && isset($event['references']['organizer'])
        && is_array($event['references']['organizer'])) {
        $rows['data'][] = reset($event['references']['organizer']);
      }
    }
    return $rows;
  }

  /**
   * Get locations API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getLocations(array $params = []): array {
    $response = $this->get('sale/event', $params);
    $rows = $response;
    $rows['data'] = [];
    foreach ($response['data'] as $event) {
      if (isset($event['references'])
        && isset($event['references']['location'])
        && is_array($event['references']['location'])) {
        $rows['data'][] = reset($event['references']['location']);
      }
    }
    return $rows;
  }

  /**
   * Get artists API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getArtists(array $params = []): array {
    $response = $this->get('sale/event', $params);
    $rows = $response;
    $rows['data'] = [];
    foreach ($response['data'] as $event) {
      if (isset($event['artist'])) {
        $rows['data'][] = ['name' => $event['artist']];
      }
      else {
        $rows['data'][] = ['name' => 'n/a'];
      }
    }
    return $rows;
  }

  /**
   * Get images API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   * @param int $type
   *   The image type id:
   *   - 1: A detail image.
   *   - 2: A slideshow image.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getImages(array $params = [], int $type = self::IMAGE_DETAIL): array {
    $response = $this->get('sale/event', $params);
    $rows = $response;
    $rows['data'] = [];
    foreach ($response['data'] as $event) {
      if (isset($event['references'])
         && is_array($event['references']['image'])) {

        foreach ($event['references']['image'] as $image) {
          if ($image['type'] === 1) {
            $rows['data'][] = $image;
          }
        }
      }
    }
    return $rows;
  }

  /**
   * Get detail images API wrapper.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getDetailImages(array $params = []): array {
    return $this->getImages($params, self::IMAGE_DETAIL);
  }

  /**
   * Get slideshow images API wrapper.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getSlideshowImages(array $params = []): array {
    return $this->getImages($params, self::IMAGE_SLIDESHOW);
  }

}
