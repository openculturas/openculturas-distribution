<?php

declare(strict_types=1);

namespace Drupal\openculturas_address_links\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openculturas_address_links\AddressService;

/**
 * Hook implementations for openculturas_address_links.
 */
class OpenculturasAddressLinksHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasAddressLinksHooks.
   *
   * @param \Drupal\openculturas_address_links\AddressService $addressService
   *   The address service.
   */
  public function __construct(protected AddressService $addressService) {
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    return [
      'types' => [
        'address' => [
          'name' => $this->t('Address'),
          'description' => $this->t('Address tokens.'),
        ],
        'geofield' => [
          'name' => $this->t('Geofield'),
          'description' => $this->t('Geofield tokens.'),
        ],
      ],
      'tokens' => [
        'address' => [
          'address' => [
            'name' => $this->t('Address'),
            'description' => $this->t('Plaintext address in a single line.'),
          ],
        ],
        'geofield' => [
          'lat' => [
            'name' => $this->t('Latitude'),
            'description' => $this->t('Latitude of the address geofield.'),
          ],
          'lon' => [
            'name' => $this->t('Longitude'),
            'description' => $this->t('Longitude of the address geofield.'),
          ],
          'latlon' => [
            'name' => $this->t('Latitude/longitude'),
            'description' => $this->t('Latitude and longitude of the address geofield, separated by comma.'),
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data): array {
    $replacements = [];
    /** @var array<string, string> $tokens */
    if ($type === 'address') {
      /** @var \Drupal\address\AddressInterface $address */
      $address = $data['address'];
      foreach ($tokens as $name => $original) {
        if ($name === 'address') {
          $replacements[$original] = $this->addressService->getPlainAddress($address);
        }
      }
    }
    elseif ($type === 'geofield') {
      /** @var object{lat: mixed, lon: mixed, latlon: mixed} $geofield */
      $geofield = $data['geofield'];
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'lat':
            $replacements[$original] = $geofield->lat;
            break;

          case 'lon':
            $replacements[$original] = $geofield->lon;
            break;

          case 'latlon':
            $replacements[$original] = $geofield->latlon;
            break;
        }
      }
    }

    return $replacements;
  }

}
