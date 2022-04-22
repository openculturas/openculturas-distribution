<?php

namespace Drupal\migrate_reservix;

/**
 * A RESTful client for Reservix API.
 *
 * @see https://developer.reservix.de/
 */
interface ReservixApiClientInterface {

  const IMAGE_DETAIL = 1;

  const IMAGE_SLIDESHOW = 2;

  /**
   * Set API key for requests.
   *
   * @param string $api_key
   *   The API key.
   */
  public function setCredentials(string $api_key);

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
  public function get(string $endpoint, array $params = []): array;

  /**
   * Get genres API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getGenres(array $params = []): array;

  /**
   * Get venues API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getVenues(array $params = []): array;

  /**
   * Get events API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getEvents(array $params = []): array;

  /**
   * Get organizers API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getOrganizers(array $params = []): array;

  /**
   * Get locations API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getLocations(array $params = []): array;

  /**
   * Get artists API wrapper.
   *
   * @param array $params
   *   An array of API parameters.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getArtists(array $params = []): array;

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
  public function getImages(array $params = [], int $type = self::IMAGE_DETAIL): array;

  /**
   * Get detail images API wrapper.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getDetailImages(array $params = []): array;

  /**
   * Get slideshow images API wrapper.
   *
   * @return array
   *   An associative array of data from the API.
   */
  public function getSlideshowImages(array $params = []): array;

}
