<?php

declare(strict_types=1);

namespace Drupal\openculturas_calendar_widget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\views\Element\View;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use function array_key_exists;
use function is_array;
use function sprintf;

/**
 * Returns responses for OpenCulturas calendar widget routes.
 */
class OpenculturasCalendarWidgetController extends ControllerBase implements TrustedCallbackInterface {

  protected BareHtmlPageRendererInterface $bareHtmlPageRenderer;

  protected ?Request $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->bareHtmlPageRenderer = $container->get('bare_html_page_renderer');
    $instance->request = $container->get('request_stack')->getCurrentRequest();

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
      '#display_id' => 'embed_calendar',
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
    $head = [
      '#type' => 'html_tag',
      '#tag' => 'base',
      '#attributes' => ['target' => '_blank'],
    ];
    $build['#attached']['html_head'][] = [$head, 'oc_iframe_base'];
    $response = $this->bareHtmlPageRenderer->renderBarePage($build, $this->t('Upcoming dates'), 'markup');
    if ($limit_access) {
      $response->headers->set('Content-Security-Policy', ["frame-ancestors 'none'"]);

      $token = $this->request->get('access_token');
      $host_list = $config->get('host_list');
      if (!empty($token) && is_array($host_list) && array_key_exists($token, $host_list)) {
        $hostname = $config->get('host_list')[$token]['hostname'] ?? NULL;
        if ($hostname !== FALSE) {
          $response->headers->set('Content-Security-Policy', [sprintf("frame-ancestors %s", $hostname)]);
        }
      }
    }
    return $response;
  }

  public static function preRenderViewElement($element) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = &$element['view_build']['#view'];
    $view->exposed_widgets = [];
    return $element;
  }

}
