<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Plugin\Oauth2Client;

use Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginBase;
use Drupal\oauth2_client\Plugin\Oauth2Client\StateTokenStorage;

/**
 * Provides Auth OpenStreetMapDev.
 *
 * @Oauth2Client(
 *   id = "openstreetmap_dev",
 *   name = @Translation("Authorization Code for OpenStreetMap Dev-Server"),
 *   grant_type = "authorization_code",
 *   authorization_uri = "https://master.apis.dev.openstreetmap.org/oauth2/authorize",
 *   token_uri = "https://master.apis.dev.openstreetmap.org/oauth2/token",
 *   success_message = TRUE,
 *   scopes = {"write_api"}
 * )
 */
final class AuthOpenStreetMapDev extends Oauth2ClientPluginBase {

  use StateTokenStorage;

}
