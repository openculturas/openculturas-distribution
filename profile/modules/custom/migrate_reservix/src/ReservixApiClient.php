<?php

namespace Drupal\migrate_reservix;

use Drupal\migrate_reservix\Plugin\migrate\source\ReservixBaseAPI;
use GuzzleHttp\Client;

/**
 * A RESTful client for Reservix API.
 *
 * @see https://developer.reservix.de/
 */
class ReservixApiClient {

  /**
   * The HTTP Client for making API requests.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

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
   *   The http client service.
   */
  public function __construct(
    Client $http_client
  ) {
    $this->httpClient = $http_client;
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
    switch ($endpoint) {
      case 'custom/artist':
        return $this->getArtists($params);

      case 'custom/location':
        return $this->getLocations($params);

      case 'custom/organizer':
        return $this->getOrganizers($params);

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

}
