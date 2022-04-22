<?php

namespace Drupal\migrate_reservix\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class MigrateReservixCommands extends DrushCommands {

  /**
   * Get artists.
   *
   * @usage migrate_reservix:artists
   *
   * @command migrate_reservix:artists
   */
  public function getArtists() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getArtists(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

  /**
   * Get organizers.
   *
   * @usage migrate_reservix:organizers
   *
   * @command migrate_reservix:organizers
   */
  public function getOrganizers() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getOrganizers(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

  /**
   * Get locations.
   *
   * @usage migrate_reservix:locations
   *
   * @command migrate_reservix:locations
   */
  public function getLocations() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getLocations(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

  /**
   * Get genres.
   *
   * @usage migrate_reservix:genres
   *
   * @command migrate_reservix:genres
   */
  public function getGenres() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getGenres(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

  /**
   * Get dates.
   *
   * @usage migrate_reservix:dates
   *
   * @command migrate_reservix:dates
   */
  public function getDates() {
    $this->getEvents();
  }

  /**
   * Get events.
   *
   * @usage migrate_reservix:events
   *
   * @command migrate_reservix:events
   */
  public function getEvents() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getEvents(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

  /**
   * Get venues.
   *
   * @usage migrate_reservix:venues
   *
   * @command migrate_reservix:venues
   */
  public function getVenues() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getVenues(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

  /**
   * Get slideshow images.
   *
   * @usage migrate_reservix:images-slideshow
   *
   * @command migrate_reservix:images-slideshow
   */
  public function getImagesSlideshow() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getSlideshowImages(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

  /**
   * Get detail images.
   *
   * @usage migrate_reservix:images-detail
   *
   * @command migrate_reservix:images-detail
   */
  public function getImagesDetail() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getDetailImages(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

  /**
   * Get eventgroups.
   *
   * @usage migrate_reservix:eventgroups
   *
   * @command migrate_reservix:eventgroups
   */
  public function getEventgroups() {
    $config = \Drupal::configFactory()->get('migrate_reservix.settings');

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials($config->get('api_key'));

    $result = $service->getEventgroups(['page' => 0]);

    print json_encode($result, JSON_PRETTY_PRINT);
  }

}
