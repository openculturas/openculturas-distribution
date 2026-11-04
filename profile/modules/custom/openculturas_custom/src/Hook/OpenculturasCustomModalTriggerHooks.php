<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\openculturas_custom\ModalTriggerTitle;

/**
 * Modal-trigger popover hook implementations for openculturas_custom.
 */
class OpenculturasCustomModalTriggerHooks implements TrustedCallbackInterface {

  /**
   * Constructs a new OpenculturasCustomModalTriggerHooks.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    protected RendererInterface $renderer,
    protected RouteMatchInterface $routeMatch,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderBlock', 'preRenderModalWrapper'];
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_openculturas_custom_modal_wrapper_alter')]
  public function themeSuggestionsOpenculturasCustomModalWrapperAlter(array &$suggestions, array $variables): void {
    if (!empty($variables['bundle'])) {
      $suggestions[] = 'openculturas_custom_modal_wrapper__' . $variables['bundle'];
    }
  }

  /**
   * Implements hook_block_view_alter().
   *
   * The block content only exists after the block's own #pre_render
   * callback, so the title is moved by a #pre_render callback that runs
   * after it.
   */
  #[Hook('block_view_alter')]
  public function blockViewAlter(array &$build, BlockPluginInterface $block): void {
    if ($block->getBaseId() === 'block_content') {
      $build['#pre_render'][] = [$this, 'preRenderBlock'];
    }
  }

  /**
   * Pre-render callback: moves a placement's title into its popover.
   *
   * @see \Drupal\openculturas_custom\EventSubscriber\OpenculturasCustomModalTriggerLayoutBuilderSubscriber
   */
  public function preRenderBlock(array $build): array {
    return ModalTriggerTitle::moveIntoPopover($build);
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter().
   *
   * The wrapping happens in a #pre_render callback: for a bundle like
   * `system_block_modal`, block_field has not built the referenced block
   * yet, so it is too early to check whether the content is empty.
   */
  #[Hook('block_content_view_alter')]
  public function blockContentViewAlter(array &$build, BlockContentInterface $entity, EntityViewDisplayInterface $display): void {
    $triggerMode = $this->getActiveModalTriggerMode($entity);
    if ($triggerMode === NULL) {
      return;
    }

    // The Layout Builder UI and its AJAX routes show the block inline, so
    // site builders can see and select it. A closed popover would only
    // leave a 1px anchor in the layout.
    $build['#cache']['contexts'][] = 'route.name';
    if (str_starts_with((string) $this->routeMatch->getRouteName(), 'layout_builder.')) {
      return;
    }

    $delay = $entity->hasField('field_modal_trigger_delay') ? (int) $entity->get('field_modal_trigger_delay')->value : 0;
    $cooldownDays = $entity->hasField('field_modal_cooldown_days') ? (int) $entity->get('field_modal_cooldown_days')->value : 0;
    $permanentDismiss = $entity->hasField('field_modal_permanent_dismiss') && (bool) $entity->get('field_modal_permanent_dismiss')->value;

    // Html::getUniqueId() only prevents duplicates within one request, but
    // the render cache keeps these IDs across requests.
    $idSuffix = bin2hex(random_bytes(8));
    $build['#modal_trigger_settings'] = [
      'anchor_id' => 'modal-trigger-anchor-' . $idSuffix,
      'popover_id' => 'modal-trigger-popover-' . $idSuffix,
      'trigger_mode' => $triggerMode,
      'delay' => $delay,
      'dismiss_key' => $entity->uuid(),
      'cooldown_days' => $cooldownDays,
      'permanent_dismiss' => $permanentDismiss,
      'bundle' => $entity->bundle(),
      'title' => NULL,
    ];
    $build['#pre_render'][] = [$this, 'preRenderModalWrapper'];
  }

  /**
   * Returns the active modal-trigger mode of the entity.
   *
   * @return string|null
   *   'delay' or 'scroll', or NULL if no mode is active.
   */
  private function getActiveModalTriggerMode(BlockContentInterface $entity): ?string {
    if (!$entity->hasField('field_modal_trigger_mode')) {
      return NULL;
    }

    $mode = $entity->get('field_modal_trigger_mode')->value;
    return in_array($mode, ['delay', 'scroll'], TRUE) ? $mode : NULL;
  }

  /**
   * Pre-render callback: wraps the content in the modal popover.
   *
   * Content that renders to nothing, for example a deleted referenced block
   * or a menu without accessible links, keeps the unwrapped build, so no
   * empty popover opens.
   */
  public function preRenderModalWrapper(array $build): array {
    if (empty($build['#modal_trigger_settings'])) {
      return $build;
    }

    $settings = $build['#modal_trigger_settings'];
    unset($build['#modal_trigger_settings']);

    // Render only the child elements. The '#' properties include the
    // recursive render protection of EntityViewBuilder. Rendering them in
    // isolation would clear the protection key of the outer render, and a
    // second render of the same entity on the page would break.
    $contentBuild = array_filter($build, static fn (string $key): bool => !str_starts_with($key, '#'), ARRAY_FILTER_USE_KEY);
    $content = $this->renderer->renderInIsolation($contentBuild);

    if ($this->isRenderedContentEmpty((string) $content)) {
      return $this->renderer->mergeBubbleableMetadata($build, $contentBuild);
    }

    $wrapper = [
      '#theme' => 'openculturas_custom_modal_wrapper',
      '#anchor_id' => $settings['anchor_id'],
      '#popover_id' => $settings['popover_id'],
      '#trigger_mode' => $settings['trigger_mode'],
      '#delay' => $settings['delay'],
      '#dismiss_key' => $settings['dismiss_key'],
      '#cooldown_days' => $settings['cooldown_days'],
      '#permanent_dismiss' => $settings['permanent_dismiss'],
      '#bundle' => $settings['bundle'],
      '#title' => $settings['title'],
      '#content' => $content,
      '#attached' => [
        'library' => ['openculturas_custom/modal_trigger'],
      ],
    ];

    // Keep EntityViewBuilder::unsetRecursiveRenderProtection(), the
    // #post_render callback of the replaced build. Without it, every later
    // render of the same entity in this request is aborted as recursive.
    // The callback needs #block_content and #view_mode to find its key.
    foreach (['#post_render', '#block_content', '#view_mode'] as $key) {
      if (isset($build[$key])) {
        $wrapper[$key] = $build[$key];
      }
    }

    $wrapper = $this->renderer->mergeBubbleableMetadata($wrapper, $build);
    return $this->renderer->mergeBubbleableMetadata($wrapper, $contentBuild);
  }

  /**
   * Whether rendered HTML has no visible content.
   *
   * An empty reference still renders field wrapper markup, for example
   * `<div class="field__item"></div>`, so the HTML is never blank. Elements
   * such as images count as content even without text.
   */
  private function isRenderedContentEmpty(string $html): bool {
    if (trim($html) === '') {
      return TRUE;
    }

    if (trim(strip_tags($html)) !== '') {
      return FALSE;
    }

    return !preg_match('/<(img|svg|video|audio|iframe|input|canvas|object|embed)\b/i', $html);
  }

}
