<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\EventSubscriber;

use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Keeps the browser URL when an infinite scroll pager loads more items.
 *
 * Since Drupal 11.3, ViewAjaxController adds a setBrowserUrl command to every
 * Views AJAX response. With the infinite_scroll pager, this rewrites the
 * address bar to "?page=N" on each "Load more", although the page shows the
 * items of all pages loaded so far.
 *
 * Like views_infinite_scroll's own AjaxResponseSubscriber, this only alters
 * responses beyond page 0, so an exposed filter submit still updates the URL.
 *
 * @todo Remove when views_infinite_scroll ships a fix for
 *   https://www.drupal.org/project/views_infinite_scroll/issues/3599947.
 */
final class OpenculturasCustomInfiniteScrollBrowserUrlSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onResponse'],
    ];
  }

  /**
   * Removes the setBrowserUrl command from infinite scroll pager responses.
   */
  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();
    if (!$response instanceof ViewAjaxResponse) {
      return;
    }

    $pager = $response->getView()->getPager();
    if ($pager->getPluginId() !== 'infinite_scroll' || $pager->getCurrentPage() === 0) {
      return;
    }

    $commands = &$response->getCommands();
    foreach ($commands as $delta => $command) {
      if (($command['command'] ?? NULL) === 'setBrowserUrl') {
        unset($commands[$delta]);
      }
    }
  }

}
