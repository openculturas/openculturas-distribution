<?php

declare(strict_types=1);

namespace Drupal\openculturas_calendar_widget\Controller;

use Drupal\Component\Utility\Html;
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
  public static function create(ContainerInterface $container): OpenculturasCalendarWidgetController {
    $instance = parent::create($container);
    $instance->bareHtmlPageRenderer = $container->get('bare_html_page_renderer');
    /** @var \Drupal\Core\Http\RequestStack $request_stack */
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
      '#text' => $config->get('header')['value'] ?? '',
      '#format' => $config->get('header')['format'] ?? NULL,
    ];
    $build['container']['view'] = [
      '#type' => 'view',
      '#name' => 'related_date',
      '#display_id' => 'upcoming_dates',
      '#pre_render' => [[View::class, 'preRenderViewElement'], [self::class, 'preRenderViewElement']],
    ];
    $build['container']['link'] = [
      '#type' => 'more_link',
      '#title' => $this->t('More dates'),
      '#url' => $this->request !== NULL ? Url::fromUri($this->request->query->get('source_uri')) : Url::fromUri('<front>'),
      '#attributes' => ['class' => 'button'],
    ];
    $build['container']['footer'] = [
      '#type' => 'processed_text',
      '#text' => $config->get('footer')['value'] ?? '',
      '#format' => $config->get('footer')['format'] ?? NULL,
    ];
    $build['#attached']['library'][] = 'openculturas_calendar_widget/widget';
    $head = [
      '#type' => 'html_tag',
      '#tag' => 'base',
      '#attributes' => ['target' => '_blank'],
    ];
    $build['#attached']['html_head'][] = [$head, 'oc_iframe_base'];
    if ($limit_access && $this->request) {
      $token = $this->request->get('access_token');
      $host_list = $config->get('host_list');
      $hostname = NULL;
      if (!empty($token) && is_array($host_list) && array_key_exists($token, $host_list)) {
        $hostname = $config->get('host_list')[$token]['hostname'] ?? NULL;
        $css = $config->get('host_list')[$token]['css'] ?? NULL;
        if ($css) {
          $build['container']['css'] = [
            '#type' => 'html_tag',
            '#tag' => 'style',
            '#value' => Html::decodeEntities(strip_tags((string) $css)),
          ];
        }
      }
    }
    $this->renderer->addCacheableDependency($build, $config);
    $response = $this->bareHtmlPageRenderer->renderBarePage($build, (string) $this->t('Upcoming dates'), 'page');
    if ($limit_access) {
      $response->headers->set('Content-Security-Policy', ["frame-ancestors 'none'"]);
      if (isset($hostname) && is_string($hostname)) {
        $response->headers->set('Content-Security-Policy', [sprintf('frame-ancestors %s', $hostname)]);
      }
    }
    return $response;
  }

  public static function preRenderViewElement(array $element): array {
    /** @var \Drupal\views\ViewExecutable|mixed|null $view */
    $view = &$element['view_build']['#view'];
    if ($view instanceof ViewExecutable) {
      $view->exposed_widgets = [];
      $view->attachment_before = [];
      $view->attachment_after = [];
    }
    return $element;
  }

}
