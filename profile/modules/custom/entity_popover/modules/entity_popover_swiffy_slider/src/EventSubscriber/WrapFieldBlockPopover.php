<?php

declare(strict_types=1);

namespace Drupal\entity_popover_swiffy_slider\EventSubscriber;

use Drupal\entity_popover_swiffy_slider\FieldPopoverBuilder;
use Drupal\entity_popover_swiffy_slider\PopoverFormatterSettings;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Plugin\Block\FieldBlock;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Wraps a Layout Builder field block's own content with the popover dialog.
 *
 * Layout Builder bypasses classic entity rendering entirely for its own
 * field blocks: each one is built directly by
 * \Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray, which
 * never invokes hook_block_view_alter(). Subscribing to the same event, at
 * a lower priority, is the only way to reach the finished build — this
 * runs after that subscriber (priority 100) has already set it.
 */
final readonly class WrapFieldBlockPopover implements EventSubscriberInterface {

  public function __construct(
    private PopoverFormatterSettings $popoverFormatterSettings,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY => ['onBuildRender', 0],
    ];
  }

  /**
   * Wraps the field block's content once it has been built.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event): void {
    if (!$event->getPlugin() instanceof FieldBlock) {
      return;
    }

    $build = $event->getBuild();
    if (!isset($build['content'][0]) || !is_array($build['content'][0])) {
      return;
    }

    FieldPopoverBuilder::wrapFieldWithPopover($build['content'], 0, $this->popoverFormatterSettings);
    $event->setBuild($build);
  }

}
