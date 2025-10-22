<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Plugin\Oauth2Client;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\oauth2_client\Attribute\Oauth2Client;
use Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginBase;
use Drupal\oauth2_client\Plugin\Oauth2Client\StateTokenStorage;

/**
 * Provides Auth OpenStreetMap.
 */
#[Oauth2Client(
  id: 'openstreetmap',
  name: new TranslatableMarkup('Authorization Code for OpenStreetMap'),
  grant_type: 'authorization_code',
  authorization_uri: 'https://www.openstreetmap.org/oauth2/authorize',
  token_uri: 'https://www.openstreetmap.org/oauth2/token',
  scopes: ['write_api'],
  success_message: TRUE
)]
final class AuthOpenStreetMap extends Oauth2ClientPluginBase {

  use StateTokenStorage;

}
