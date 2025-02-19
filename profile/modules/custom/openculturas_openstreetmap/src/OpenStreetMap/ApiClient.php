<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\OpenStreetMap;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\Error;
use Drupal\oauth2_client\Service\Oauth2ClientServiceInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function assert;
use function is_object;
use function is_string;
use function json_decode;
use function sprintf;
use function str_replace;

/**
 * @property ClientInterface&\GuzzleHttp\ClientTrait $httpClient
 */
final class ApiClient {
  use AutowireTrait;

  /**
   * AccessToken from the API.
   *
   * @var string|null
   */
  protected ?string $token = NULL;

  /**
   * Depending on how configured the suffix of the final api url.
   *
   * @var string|null
   */
  protected ?string $baseUri = NULL;

  /**
   * When development mode is configured the hardcoded OSM ID.
   *
   * @var string|null
   */
  protected ?string $osmId = NULL;

  public const DEV_ENDPOINT = 'https://master.apis.dev.openstreetmap.org';

  public const ENDPOINT = 'https://openstreetmap.org';

  public function __construct(
    #[Autowire(service: 'oauth2_client.service')]
    private readonly Oauth2ClientServiceInterface $oauth2Client,
    private readonly ClientInterface $httpClient,
    private readonly StateInterface $state,
    #[Autowire(service: 'logger.channel.openculturas_openstreetmap')]
    private readonly LoggerInterface $logger,
    private readonly ModuleExtensionList $moduleExtensionList,
  ) {
  }

  public function canPush(): bool {
    /** @var bool|null $development_mode */
    $development_mode = $this->state->get('openculturas_openstreetmap.settings.devmode');
    return $this->hasToken((bool) $development_mode);
  }

  public function setToken(bool $development_mode = FALSE): void {
    try {
      if ($development_mode) {
        $this->token = $this->oauth2Client->retrieveAccessToken('openstreetmap_dev')?->getToken();
        $this->baseUri = self::DEV_ENDPOINT . '/api/0.6';
        /** @var string|null $value */
        $value = $this->state->get('openculturas_openstreetmap.settings.osmid');
        $this->osmId = $value;
      }
      else {
        $this->token = $this->oauth2Client->retrieveAccessToken('openstreetmap')?->getToken();
        $this->baseUri = self::ENDPOINT . '/api/0.6';
      }
    }
    catch (\Exception $exception) {
      Error::logException($this->logger, $exception);
    }
  }

  public function hasToken(bool $development_mode = FALSE): bool {
    $this->setToken($development_mode);
    return $this->token !== NULL;
  }

  /**
   * @link https://wiki.openstreetmap.org/wiki/API_v0.6#Create:_PUT_/api/0.6/changeset/create
   *   ApiDoc Create changeset
   */
  public function createChangeSet(bool $needs_review = FALSE, ?string $comment = NULL): ?string {
    if ($this->canPush() === FALSE) {
      return NULL;
    }

    $xw = new \XMLWriter();
    $xw->openMemory();
    $xw->startDocument(encoding: 'utf-8');
    $xw->setIndent(TRUE);
    $xw->startElement('osm');
    $xw->startElement('changeset');

    $version = $this->moduleExtensionList->getExtensionInfo('openculturas')['version'] ?? 'Unknown version';
    $this->createTagElement($xw, 'created_by', 'OpenCulturas - ' . $version);
    if ($comment) {
      $this->createTagElement($xw, 'comment', $comment);
    }

    if ($needs_review) {
      $this->createTagElement($xw, 'review_requested', 'yes');
    }

    $xw->endElement();
    $xw->endElement();
    $xw->endDocument();

    $doc = $xw->outputMemory();
    assert(is_string($this->baseUri));
    $url = $this->baseUri . '/changeset/create';
    try {
      /** @var \Psr\Http\Message\ResponseInterface $response */
      $response = $this->httpClient->put($url, ['headers' => $this->headers(), 'body' => $doc]);
    }
    catch (\Exception $exception) {
      Error::logException($this->logger, $exception);
      return NULL;
    }

    if ($response->getStatusCode() === 200) {
      return (string) $response->getBody();
    }

    $this->logger->error('Could not create changeset. Response: ' . $response->getBody()->getContents());
    return NULL;
  }

  /**
   * @link https://wiki.openstreetmap.org/wiki/API_v0.6#Close:_PUT_/api/0.6/changeset/#id/close
   *   Apidoc Close Changeset
   */
  public function closeChangeSet(string $changeSetId): bool {
    assert(is_string($this->baseUri));
    $url = $this->baseUri . '/changeset/#id/close';
    try {
      /** @var \Psr\Http\Message\ResponseInterface $response */
      $response = $this->httpClient->put(str_replace('#id', $changeSetId, $url), ['headers' => $this->headers()]);
    }
    catch (\Exception $exception) {
      Error::logException($this->logger, $exception);
      return FALSE;
    }

    if ($response->getStatusCode() === 200) {
      return TRUE;
    }

    $this->logger->error('Could not close changeset. Response: ' . $response->getBody()->getContents());
    return FALSE;
  }

  /**
   * @return \stdClass{type: string, id: int, lat: float, lon: float,
   *   timestamp: string, version: int, changeset: int, user: string, uid: int,
   *   tags: \stdClass}
   *   | null
   *
   * @link https://wiki.openstreetmap.org/wiki/API_v0.6#Read:_GET_/api/0.6/[node|way|relation]/#id
   *   ApiDoc Read Element
   */
  public function getElement(string $elementType, string $id): ?\stdClass {
    if ($this->canPush() === FALSE) {
      return NULL;
    }

    $id = $this->getOsmId($id);
    assert(is_string($this->baseUri));
    $url = sprintf($this->baseUri . '/%s/%s.json', $elementType, $id);
    try {
      /** @var \Psr\Http\Message\ResponseInterface $response */
      $response = $this->httpClient->get($url, ['headers' => $this->headers()]);
    }
    catch (\Exception $exception) {
      Error::logException($this->logger, $exception);
      return NULL;
    }

    if ($response->getStatusCode() === 200) {
      $json = $response->getBody()->getContents();

      try {
        $jsonDecoded = json_decode($json, FALSE, 512, JSON_THROW_ON_ERROR);
      }
      catch (\JsonException $jsonException) {
        Error::logException($this->logger, $jsonException);
        return NULL;
      }

      if (!is_object($jsonDecoded)) {
        return NULL;
      }

      $element = $jsonDecoded->{'elements'}[0] ?? NULL;
      if (!$element instanceof \stdClass) {
        return NULL;
      }

      return $element;
    }

    $this->logger->error('Could not read element. Response: ' . $response->getBody()->getContents());
    return NULL;
  }

  /**
   * @link https://wiki.openstreetmap.org/wiki/API_v0.6#Update:_PUT_/api/0.6/[node|way|relation]/#id
   *   ApiDoc to update a element
   */
  public function updateElement(string $elementType, string $id, string $changeSetID, string $version, array $tags, string $lat, string $lon): bool {
    $id = $this->getOsmId($id);
    $xw = new \XMLWriter();
    $xw->openMemory();
    $xw->startDocument(encoding: 'utf-8');
    $xw->setIndent(FALSE);
    $xw->startElement('osm');
    $xw->startElement($elementType);
    $xw->startAttribute('changeset');
    $xw->text($changeSetID);
    $xw->endAttribute();
    $xw->startAttribute('id');
    $xw->text($id);
    $xw->endAttribute();
    $xw->startAttribute('version');
    $xw->text($version);
    $xw->endAttribute();
    $xw->startAttribute('lat');
    $xw->text($lat);
    $xw->endAttribute();
    $xw->startAttribute('lon');
    $xw->text($lon);
    $xw->endAttribute();
    foreach ($tags as $tag_key => $tag_value) {
      $this->createTagElement($xw, $tag_key, $tag_value);
    }

    $xw->endElement();
    $xw->endElement();
    $xw->endDocument();

    $doc = $xw->outputMemory();
    assert(is_string($this->baseUri));
    $url = sprintf($this->baseUri . '/%s/%s', $elementType, $id);
    try {
      /** @var \Psr\Http\Message\ResponseInterface $response */
      $response = $this->httpClient->put($url, ['headers' => $this->headers(), 'body' => $doc]);
    }
    catch (\Exception $exception) {
      Error::logException($this->logger, $exception);
      return FALSE;
    }

    if ($response->getStatusCode() === 200) {
      return TRUE;
    }

    $this->logger->error('Could not update element. Response: ' . $response->getBody()->getContents());
    return FALSE;
  }

  protected function getOsmId(string $id): string {
    return $this->osmId ?? $id;
  }

  protected function headers(): array {
    return ['Authorization' => 'Bearer ' . $this->token];
  }

  protected function createTagElement(\XMLWriter $doc, string $key, string $value): void {
    $doc->startElement('tag');
    $doc->startAttribute('k');
    $doc->text($key);
    $doc->endAttribute();
    $doc->startAttribute('v');
    $doc->text($value);
    $doc->endAttribute();
    $doc->endElement();
  }

}
