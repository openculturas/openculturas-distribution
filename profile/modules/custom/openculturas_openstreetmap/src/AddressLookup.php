<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\geocoder\GeocoderThrottleInterface;
use Geocoder\Location;
use Geocoder\Model\AddressBuilder;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function array_diff_key;
use function array_flip;
use function current;
use function explode;
use function http_build_query;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function reset;
use function strtoupper;

/**
 * @property ClientInterface&\GuzzleHttp\ClientTrait $httpClient
 */
final class AddressLookup implements ContainerInjectionInterface {
  use AutowireTrait;

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly LanguageManagerInterface $languageManager,
    #[Autowire(service: 'geocoder.throttle')]
    private readonly GeocoderThrottleInterface $geocoderThrottle,
    #[Autowire(service: 'cache.default')]
    private readonly CacheBackendInterface $cache,
    private readonly TimeInterface $time,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Builds the response.
   *
   * @php-return array{0:?Location, 1: ?string, 2: ?array}
   *
   * @see https://nominatim.org/release-docs/develop/api/Lookup/
   */
  public function lookup(string $id, bool $extra_tags = FALSE): array {
    $params = [
      'format' => 'jsonv2',
      'addressdetails' => 1,
      'extratags' => (int) $extra_tags,
      'osm_ids' => $id,
      'accept-language' => $this->languageManager->getCurrentLanguage()->getId(),
    ];
    if ($email = $this->configFactory->get('openculturas_openstreetmap.settings')->get('nominatim_email')) {
      $params['email'] = $email;
    }

    $url = 'https://nominatim.openstreetmap.org/lookup?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    $cache_keys = ['osm', 'lookup', $url];
    $cache = $this->cache->get(implode(':', $cache_keys));
    $place = NULL;
    if (!$cache) {
      $this->geocoderThrottle->waitForAvailability('Nominatim', ['limit' => 1, 'period' => 2]);
      /** @var \Psr\Http\Message\ResponseInterface $response */
      $response = $this->httpClient->get($url);

      if ($response->getStatusCode() === 200 && $response->getBody()->isReadable()) {
        $json = (string) $response->getBody();
        if ($json === '') {
          return [];
        }

        try {
          $jsonDecoded = json_decode($json, FALSE, 512, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException) {
          return [];
        }

        if (!is_array($jsonDecoded)) {
          return [];
        }

        /** @var \stdClass|null $place */
        $place = reset($jsonDecoded);
        if ($place === NULL) {
          return [];
        }

        $this->cache->set(implode(':', $cache_keys), $place, $this->time->getRequestTime() + (60 * 60 * 24), ['config:openculturas_openstreetmap.settings']);
      }
    }
    else {
      /** @var \stdClass $place */
      $place = $cache->data;
    }

    if (is_object($place)) {
      return [$this->jsonResultToLocation($place, TRUE), $place->name ?? NULL, $place->extratags ?? NULL];
    }

    return [];
  }

  /**
   * Copy of \Geocoder\Provider\Nominatim\Nominatim::jsonResultToLocation.
   *
   * @see \Geocoder\Provider\Nominatim\Nominatim::jsonResultToLocation
   */
  private function jsonResultToLocation(\stdClass $place, bool $reverse): Location {
    $builder = new AddressBuilder('nominatim');

    foreach (['state', 'county'] as $i => $tagName) {
      if (isset($place->address->{$tagName})) {
        $builder->addAdminLevel($i + 1, $place->address->{$tagName}, '');
      }
    }

    // Get the first postal-code when there are many.
    if (isset($place->address->postcode)) {
      $postalCode = $place->address->postcode;
      if (is_string($postalCode)) {
        $postalCode = current(explode(';', $postalCode));
      }

      $builder->setPostalCode($postalCode);
    }

    $localityFields = ['city', 'town', 'village', 'hamlet'];
    foreach ($localityFields as $localityField) {
      if (isset($place->address->{$localityField})) {
        $localityFieldContent = $place->address->{$localityField};

        if (!empty($localityFieldContent)) {
          $builder->setLocality($localityFieldContent);

          break;
        }
      }
    }

    $builder->setStreetName($place->address->road ?? $place->address->pedestrian ?? NULL);
    $builder->setStreetNumber($place->address->house_number ?? NULL);
    $builder->setSubLocality($place->address->suburb ?? NULL);
    $builder->setCountry($place->address->country ?? NULL);
    $builder->setCountryCode(isset($place->address->country_code) ? strtoupper($place->address->country_code) : NULL);

    $builder->setCoordinates((float) $place->lat, (float) $place->lon);

    $builder->setBounds($place->boundingbox[0], $place->boundingbox[2], $place->boundingbox[1], $place->boundingbox[3]);

    /** @var \Geocoder\Provider\Nominatim\Model\NominatimAddress $location */
    $location = $builder->build(NominatimAddress::class);
    $location = $location->withAttribution($place->licence);
    $location = $location->withDisplayName($place->display_name);

    $includedAddressKeys = ['city', 'town', 'village', 'state', 'county', 'hamlet', 'postcode', 'road', 'pedestrian', 'house_number', 'suburb', 'country', 'country_code', 'quarter'];

    $location = $location->withDetails(array_diff_key((array) $place->address, array_flip($includedAddressKeys)));

    if (isset($place->extratags)) {
      $location = $location->withTags((array) $place->extratags);
    }

    if (isset($place->address->quarter)) {
      $location = $location->withQuarter($place->address->quarter);
    }

    if (isset($place->address->neighbourhood)) {
      $location = $location->withNeighbourhood($place->address->neighbourhood);
    }

    if (isset($place->osm_id)) {
      $location = $location->withOSMId((int) $place->osm_id);
    }

    if (isset($place->osm_type)) {
      $location = $location->withOSMType($place->osm_type);
    }

    if (FALSE === $reverse) {
      $location = $location->withCategory($place->category);
      $location = $location->withType($place->type);
    }

    return $location;
  }

}
