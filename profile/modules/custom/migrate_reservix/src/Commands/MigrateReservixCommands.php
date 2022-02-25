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
   * Command description here.
   *
   * @usage migrate_reservix-foo foo
   *
   * @command migrate_reservix:foo
   * @aliases foo
   */
  public function commandName() {
    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $service->setCredentials('rSvxmbcnf5ajNPwALSBchE97MmMiuIBtP1uqQ5tJ8xyYC01O');

    $result = $service->getArtists();

    $this->logger()->success(print_r($result, TRUE));
  }

}
