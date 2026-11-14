<?php

declare(strict_types=1);

namespace Drupal\openculturas_calendar_widget\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\views\Element\View;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use function array_key_exists;
use function is_array;
use function is_string;
use function parse_url;
use function sprintf;
use function strip_tags;

/**
 * Returns responses for OpenCulturas calendar widget routes.
 */
final class OpenculturasCalendarWidgetController extends ControllerBase implements TrustedCallbackInterface {

  /**
   * @var \Drupal\Core\Render\BareHtmlPageRendererInterface
   */
  protected BareHtmlPageRendererInterface $bareHtmlPageRenderer;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $request = NULL;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->bareHtmlPageRenderer = $container->get('bare_html_page_renderer');
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $container->get('request_stack');
    $instance->request = $request_stack->getCurrentRequest();
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderViewElement'];
  }

  /**
   * Builds the response.
   */
  public function build(): HtmlResponse {
    $build = [];
    $config = $this->config('openculturas_calendar_widget.settings');
    $limit_access = $config->get('limit_access');
    $build['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => [
          'oc-calendar-widget',
        ],
      ],
    ];
    $build['container']['header'] = [
      '#type' => 'processed_text',
      '#text' => is_array($config->get('header')) ? ($config->get('header')['value'] ?? '') : '',
      '#format' => is_array($config->get('header')) ? ($config->get('header')['format'] ?? NULL) : NULL,
    ];
    $build['container']['view'] = [
      '#type' => 'view',
      '#name' => 'related_date',
      '#display_id' => 'upcoming_dates',
      '#pre_render' => [[View::class, 'preRenderViewElement'], self::preRenderViewElement(...)],
    ];
    $source_url = $this->getSourceUrl();
    if ($source_url instanceof Url) {
      $build['container']['link'] = [
        '#type' => 'more_link',
        '#title' => $this->t('More dates'),
        '#url' => $source_url,
        '#attributes' => ['class' => 'button'],
      ];
    }

    $build['container']['footer'] = [
      '#type' => 'processed_text',
      '#text' => is_array($config->get('footer')) ? ($config->get('footer')['value'] ?? '') : '',
      '#format' => is_array($config->get('footer')) ? ($config->get('footer')['format'] ?? NULL) : NULL,
    ];
    $build['#attached']['library'][] = 'openculturas_calendar_widget/widget';
    $head = [
      '#type' => 'html_tag',
      '#tag' => 'base',
      '#attributes' => ['target' => '_blank'],
    ];
    $build['#attached']['html_head'][] = [$head, 'oc_iframe_base'];
    $hostname = NULL;
    $wildcard = NULL;
    $token = $this->getQueryString('access_token');
    if ($limit_access && $token !== NULL) {
      $host_list = $config->get('host_list');
      if (is_array($host_list) && array_key_exists($token, $host_list)) {
        /** @var array{hostname?: string, wildcard?: bool, css?: string} $hostEntry */
        $hostEntry = $host_list[$token];
        $hostname = $hostEntry['hostname'] ?? NULL;
        $wildcard = $hostEntry['wildcard'] ?? NULL;
        $css = $hostEntry['css'] ?? NULL;
        if (is_string($css) && $css !== '') {
          $build['container']['css'] = [
            '#type' => 'html_tag',
            '#tag' => 'style',
            '#value' => Html::decodeEntities(strip_tags($css)),
          ];
        }
      }
    }

    $this->renderer->addCacheableDependency($build, $config);
    $response = $this->bareHtmlPageRenderer->renderBarePage($build, (string) $this->t('Upcoming dates'), 'page');
    if ($limit_access) {
      $response->headers->set('Content-Security-Policy', ["frame-ancestors 'none'"]);
      if (is_string($hostname)) {
        $uri_parts = parse_url($hostname);
        if ($uri_parts === FALSE) {
          return $response;
        }

        $scheme = $uri_parts['scheme'] ?? NULL;
        $host = $uri_parts['host'] ?? NULL;
        if (!$scheme || !$host) {
          return $response;
        }

        // Input could be https://example.org/path, which is not valid as frame ancestor.
        $hostname = sprintf('%s://%s', $scheme, $host);
        if ($wildcard) {
          // Append a variant with wildcard-domain.
          $hostname .= sprintf(' %s://*.%s', $scheme, $host);
        }

        $response->headers->set('Content-Security-Policy', [sprintf('frame-ancestors %s', $hostname)]);
      }
    }

    return $response;
  }

  /**
   * Returns the link target for the "More dates" button.
   *
   * The embed code sets source_uri to the absolute URL of the calendar page
   * on this site. Any other value, e.g. a URL on a different host, is ignored,
   * so a crafted embed URL cannot put a foreign link on this site.
   */
  protected function getSourceUrl(): ?Url {
    $source_uri = $this->getQueryString('source_uri');
    if ($source_uri === NULL || !$this->request instanceof Request) {
      return NULL;
    }

    // Only absolute URLs with a host, which externalIsLocal() requires.
    if (!UrlHelper::isValid($source_uri, TRUE)) {
      return NULL;
    }

    $base_url = $this->request->getSchemeAndHttpHost() . $this->request->getBasePath() . '/';
    if (!UrlHelper::externalIsLocal($source_uri, $base_url)) {
      return NULL;
    }

    return Url::fromUri($source_uri);
  }

  /**
   * Returns a query parameter, or NULL if it is missing or not a string.
   */
  protected function getQueryString(string $name): ?string {
    if (!$this->request instanceof Request) {
      return NULL;
    }

    $value = $this->request->query->all()[$name] ?? NULL;
    return is_string($value) && $value !== '' ? $value : NULL;
  }

  public static function preRenderViewElement(array $element): array {
    $view = &$element['view_build']['#view'];
    if ($view instanceof ViewExecutable) {
      $view->exposed_widgets = [];
      $view->attachment_before = [];
      $view->attachment_after = [];
    }

    return $element;
  }

}
