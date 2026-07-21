<?php

declare(strict_types=1);

namespace Drupal\openculturas_calendar_widget\Hook;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\openculturas_calendar_widget\Form\EmbedCodeWidget;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook implementations for openculturas_calendar_widget.
 */
class OpenculturasCalendarWidgetHooks {

  /**
   * Constructs a new OpenculturasCalendarWidgetHooks.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected AccountProxyInterface $currentUser,
    protected RequestStack $requestStack,
    protected FormBuilderInterface $formBuilder,
  ) {
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'page__openculturas_calendar_widget__embed' => [
        'base hook' => 'page',
      ],
    ];
  }

  /**
   * Implements hook_views_pre_view().
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view): void {
    if ($this->routeMatch->getRouteName() !== 'openculturas_calendar_widget.embed') {
      return;
    }

    if ($view->id() === 'related_date' && $view->current_display === 'upcoming_dates') {
      $view->exposed_widgets = [];
      /** @var array{type: string} $pager */
      $pager = $view->display_handler->getOption('pager');
      $pager['type'] = 'some';
      $view->display_handler->setOption('pager', $pager);
      $view->setItemsPerPage(12);
      $view->display_handler->setOption('header', []);
      /** @var string $css_class */
      $css_class = $view->display_handler->getOption('css_class');
      $view->display_handler->setOption('css_class', $css_class . ' calendar--embed');
    }
  }

  /**
   * Implements hook_views_post_build().
   */
  #[Hook('views_post_build')]
  public function viewsPostBuild(ViewExecutable $view): void {
    if ($this->routeMatch->getRouteName() === 'openculturas_calendar_widget.embed') {
      return;
    }

    if ($view->id() === 'related_date' && $view->current_display === 'upcoming_dates') {
      $access = $this->currentUser->hasPermission('access openculturas_calendar_widget embed') || $this->currentUser->hasPermission('administer openculturas_calendar_widget configuration');
      if ($access) {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
          return;
        }

        $target_uri = is_string($view->exposed_widgets['#action'] ?? NULL) ? $view->exposed_widgets['#action'] : $request->getRequestUri();
        $exposed_input = $view->getExposedInput();
        $exposed_input['source_uri'] = Url::fromUserInput($target_uri)->setOption('query', $exposed_input)->setAbsolute()->toString();
        $url = Url::fromRoute('openculturas_calendar_widget.embed')->setOption('query', $exposed_input)->setAbsolute();
        $view->attachment_before[] = $this->formBuilder->getForm(EmbedCodeWidget::class, $url);
      }
    }
  }

}
