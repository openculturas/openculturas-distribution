<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\EventSubscriber;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\openculturas_custom\ModalTriggerTitle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function class_exists;
use function in_array;

/**
 * Moves the title of a Layout Builder content block into its popover.
 *
 * Layout Builder builds its blocks in
 * \Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray, which
 * never invokes hook_block_view_alter(). This subscriber runs after that
 * subscriber (priority 100), so the build already exists.
 */
final class OpenculturasCustomModalTriggerLayoutBuilderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Sites that update from an old release build the container before
    // layout_builder is installed.
    if (!class_exists(LayoutBuilderEvents::class)) {
      return [];
    }

    return [
      LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY => ['onBuildRender', 0],
    ];
  }

  /**
   * Moves the title of a reusable or inline content block.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event): void {
    $plugin = $event->getPlugin();
    if (!$plugin instanceof BlockPluginInterface || !in_array($plugin->getBaseId(), ['block_content', 'inline_block'], TRUE)) {
      return;
    }

    $build = $event->getBuild();
    if ($build !== []) {
      $event->setBuild(ModalTriggerTitle::moveIntoPopover($build));
    }
  }

}
