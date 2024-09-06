<?php

declare(strict_types=1);

namespace Drupal\openculturas_address_links;

use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepositoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\address\AddressInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;
use function is_string;

/**
 * Service for converting address objects into plaintext, for use in URLs.
 */
class AddressService {

  /**
   * The address format repository.
   */
  protected AddressFormatRepositoryInterface $addressFormatRepository;

  /**
   * The country repository.
   */
  protected CountryRepositoryInterface $countryRepository;

  /**
   * The subdivision repository.
   */
  protected SubdivisionRepositoryInterface $subdivisionRepository;

  /**
   * Language Manager.
   */
  protected readonly LanguageManagerInterface $languageManager;

  /**
   * Config factory service.
   */
  protected readonly ConfigFactoryInterface $configFactory;

  /**
   * Drupal placeholder/token replacement system.
   */
  protected readonly Token $token;

  /**
   * Constructs a new instance.
   */
  public function __construct(
    AddressFormatRepositoryInterface $address_format_repository,
    CountryRepositoryInterface $country_repository,
    SubdivisionRepositoryInterface $subdivision_repository,
    LanguageManagerInterface $languageManager,
    ConfigFactoryInterface $configFactory,
    Token $token,
  ) {
    $this->addressFormatRepository = $address_format_repository;
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
    $this->token = $token;
  }

  /**
   * Creates a plain address string from a given address object.
   */
  public function getPlainAddress(AddressInterface $address): string {
    $formatter = new DefaultFormatter(
      $this->addressFormatRepository,
      $this->countryRepository,
      $this->subdivisionRepository
    );
    $str = $formatter->format($address, [
      'html' => FALSE,
      'locale' => $this->languageManager->getCurrentLanguage()->getId(),
    ]);
    return str_replace("\n", ', ', $str);
  }

  /**
   * Gets the configuration array.
   *
   * @return array
   *   The config.
   */
  protected function getConfig(): array {
    return $this->configFactory->get('openculturas_address_links.settings')->get();
  }

  /**
   * Determines whether the service is able to build links for a given purpose.
   */
  public function isApplicable(string $purpose): bool {
    return (bool) $this->getUri($purpose);
  }

  /**
   * Gets the link URI for the given purpose.
   *
   * @param string $purpose
   *   The purpose.
   *
   * @return string|null
   *   The URI, if available.
   */
  protected function getUri(string $purpose): ?string {
    $config = $this->getConfig();
    if (isset($config[$purpose]) && is_string($config[$purpose]) && $config[$purpose] !== '') {
      return $config[$purpose];
    }

    return NULL;
  }

  /**
   * Creates a URL based on the given token replacements for the given purpose.
   *
   * @param array $replacements
   *   The replacements.
   * @param string $purpose
   *   The purpose.
   *
   * @return \Drupal\Core\Url|null
   *   The generated URL, if applicable.
   */
  public function buildUrl(array $replacements, string $purpose): ?Url {
    if (!$uri = $this->getUri($purpose)) {
      return NULL;
    }

    $base_url = $this->token->replace($uri, $replacements);
    return Url::fromUri($base_url);
  }

  /**
   * Creates a URL based on the given address for the given purpose.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param string $purpose
   *   The purpose.
   *
   * @return \Drupal\Core\Url|null
   *   The generated URL, if applicable.
   */
  public function buildUrlFromAddress(AddressInterface $address, string $purpose): ?Url {
    return $this->buildUrl(['address' => $address], $purpose);
  }

  /**
   * Creates a URL based on the given geofield for the given purpose.
   *
   * @param \Drupal\geofield\Plugin\Field\FieldType\GeofieldItem $item
   *   The geofield.
   * @param string $purpose
   *   The purpose.
   *
   * @return \Drupal\Core\Url|null
   *   The generated URL, if applicable.
   */
  public function buildUrlFromGeofield(GeofieldItem $item, string $purpose): ?Url {
    return $this->buildUrl(['geofield' => $item], $purpose);
  }

}
