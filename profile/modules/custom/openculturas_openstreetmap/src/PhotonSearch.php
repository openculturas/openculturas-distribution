<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Language\LanguageManagerInterface;
use Geocoder\Collection;
use Geocoder\Model\Bounds;
use Geocoder\Provider\Photon\Photon;
use Geocoder\StatefulGeocoder;
use Psr\Http\Client\ClientInterface;

final class PhotonSearch {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LanguageManagerInterface $languageManager,
  ) {
    $this->config = $this->configFactory->get('openculturas_openstreetmap.settings');
  }

  public function find(string $location_name): Collection {
    $provider = Photon::withKomootServer($this->httpClient);
    $geocoder = new StatefulGeocoder($provider, $this->languageManager->getCurrentLanguage()->getId());
    $geocoder->setBounds($this->getBounds());
    return $this->doSearch($geocoder, $location_name);
  }

  protected function getBounds(): Bounds {
    /** @var float[] $bounds */
    $bounds = $this->config->get('bounds');
    return new Bounds(
      south: $bounds['south'] ?? 47.2701,
      west: $bounds['west'] ?? 5.8663,
      north: $bounds['north'] ?? 55.992,
      east: $bounds['east'] ?? 15.0419
    );
  }

  protected function doSearch(StatefulGeocoder $geocoder, string $location_name): Collection {
    return $geocoder->geocode($location_name);
  }

}
