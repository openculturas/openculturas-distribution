<?php

declare(strict_types=1);

namespace Drupal\openculturas_calendar_widget\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\views\Element\View;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function array_key_exists;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Returns responses for OpenCulturas calendar widget routes.
 */
class OpenculturasCalendarWidgetController extends ControllerBase implements TrustedCallbackInterface {

  protected BareHtmlPageRendererInterface $bareHtmlPageRenderer;

  protected ?Request $request;

  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->bareHtmlPageRenderer = $container->get('bare_html_page_renderer');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderViewElement'];
  }

  /**
   * Builds the response.
   */
  public function build() {
    $config = $this->config('openculturas_calendar_widget.settings');
    $limit_access = $config->get('limit_access');
    $display = $this->request->get('display');
    $displays = [
      'related_date_organizer',
      'upcoming_dates',
      'related_date_location'
    ];
    if (!in_array($display, $displays)) {
      $response = new Response();
      $response->setContent('');
      $response->setStatusCode(404);
      $response->headers->set('Content-Security-Policy', ["frame-ancestors 'none'"]);
      return $response;
    }

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
      '#format' => $config->get('header')['format'] ?? NULL
    ];
    $build['container']['view'] = [
      '#type' => 'view',
      '#name' => 'related_date',
      '#display_id' => $this->request->get('display'),
      '#arguments' => $this->request->get('args'),
      '#pre_render' => [[View::class, 'preRenderViewElement'], [static::class, 'preRenderViewElement']]
    ];
    $build['container']['link'] = [
      '#type' => 'more_link',
      '#title' => $this->t('More dates'),
      '#url' => Url::fromUri($this->request->query->get('source_uri'))
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
    if ($limit_access) {
      $token = $this->request->get('access_token');
      $host_list = $config->get('host_list');
      $hostname = NULL;
      if (!empty($token) && is_array($host_list) && array_key_exists($token, $host_list)) {
        $hostname = $config->get('host_list')[$token]['hostname'] ?? NULL;
        $css = $config->get('host_list')[$token]['css'] ?? NULL;
        $build['container']['css'] = [
          '#type' => 'html_tag',
          '#tag' => 'style',
          '#value' => Html::decodeEntities(strip_tags((string) $css))
        ];
      }
    }
    $this->renderer->addCacheableDependency($build, $config);
    $response = $this->bareHtmlPageRenderer->renderBarePage($build, $this->t('Upcoming dates'), 'markup');
    if ($limit_access) {
      $response->headers->set('Content-Security-Policy', ["frame-ancestors 'none'"]);
      if ($hostname !== NULL) {
        $response->headers->set('Content-Security-Policy', [sprintf("frame-ancestors %s", $hostname)]);
      }
    }
    return $response;
  }

  public static function preRenderViewElement($element) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = &$element['view_build']['#view'];
    if ($view instanceof ViewExecutable) {
      $view->exposed_widgets = [];
      $view->attachment_before = [];
      $view->attachment_after = [];
    }
    return $element;
  }

}
