<?php

declare(strict_types=1);

namespace Drupal\entity_popover;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Tracks which entities are currently rendering inside a popover dialog.
 *
 * Guards against infinite recursion when a popover's target view mode
 * happens to display the same popover-enabled field on the same entity.
 */
final class RenderContextGuard implements TrustedCallbackInterface {

  /**
   * @var array<string, int>
   */
  private array $active = [];

  public function isActive(string $entityTypeId, string $entityId): bool {
    return isset($this->active[$entityTypeId . ':' . $entityId]);
  }

  public function enter(string $entityTypeId, string $entityId): void {
    $key = $entityTypeId . ':' . $entityId;
    $this->active[$key] = ($this->active[$key] ?? 0) + 1;
  }

  public function leave(string $entityTypeId, string $entityId): void {
    $key = $entityTypeId . ':' . $entityId;
    if (!empty($this->active[$key]) && --$this->active[$key] <= 0) {
      unset($this->active[$key]);
    }
  }

  /**
   * Pre-render callback: builds the dialog content while the guard is active.
   *
   * Bracketing the guard around #pre_render/#post_render (rather than just
   * around the initial viewMultiple() call) is required because building a
   * render array only assembles it — nested preprocess hooks for the dialog
   * content fire later, when Twig actually renders it. The guard must stay
   * active for that whole nested render, not just the array-assembly step.
   *
   * The dialog content is a clone of the host entity, not the live
   * instance, with this same field cleared on the clone so the dialog can
   * never recurse back into opening another popover for the same field. The
   * clone keeps the entity ID, because templates and preprocess functions
   * routinely call toUrl() on the rendered entity, which throws for an entity
   * without ID.
   *
   * Keeping the ID has two consequences for the returned render array:
   * - Drupal's entity view builder refuses to render the same entity
   *   ID/revision + view mode a second time while the first render is still
   *   in progress. That legitimately happens whenever a popover reuses the
   *   entity's currently active view mode, a case this module is required to
   *   support. The core recursion protection callbacks are therefore removed;
   *   this class plus the cleared field already prevent real recursion.
   * - The clone would share the render cache keys of the live entity in the
   *   same view mode. They are extended with a popover-specific suffix so the
   *   altered clone is cached separately and never written to, or served
   *   from, the regular entity render cache entry.
   */
  public static function preRenderDialog(array $element): array {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $element['#target_entity'];
    $entityTypeId = $entity->getEntityTypeId();

    \Drupal::service('entity_popover.render_context')
      ->enter($entityTypeId, PopoverId::entityIdentifier($entity));

    $clone = clone $entity;
    $clone->set($element['#field_name'], FALSE);

    // The contrib paragraph_view_mode module lets a paragraph override which
    // view mode it renders as, keyed off its own field value. The clone
    // carries that value over, so without clearing it here that
    // module's hook_entity_view_mode_alter() would silently rewrite this
    // popover's explicitly configured target view mode back to whatever the
    // source paragraph normally displays as, defeating the point of choosing
    // a different view mode for the dialog.
    if ($clone->hasField('paragraph_view_mode')) {
      $clone->set('paragraph_view_mode', NULL);
    }

    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entityTypeId);
    $dialogContent = $viewBuilder->view($clone, $element['#view_mode']);
    if (isset($dialogContent['#cache']['keys'])) {
      $dialogContent['#cache']['keys'][] = 'entity_popover_dialog';
      $dialogContent['#cache']['keys'][] = $element['#field_name'];
    }

    $isRecursionProtection = static fn (mixed $callback): bool => is_array($callback)
      && in_array($callback[1] ?? NULL, ['setRecursiveRenderProtection', 'unsetRecursiveRenderProtection'], TRUE);
    foreach (['#pre_render', '#post_render'] as $callbackKey) {
      $dialogContent[$callbackKey] = array_values(array_filter(
        $dialogContent[$callbackKey] ?? [],
        static fn (mixed $callback): bool => !$isRecursionProtection($callback),
      ));
    }

    $element['#dialog_content'] = $dialogContent;

    return $element;
  }

  /**
   * Post-render callback: releases the guard once the dialog has rendered.
   */
  public static function postRenderDialog(string $content, array $element): string {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $element['#target_entity'];
    \Drupal::service('entity_popover.render_context')
      ->leave($entity->getEntityTypeId(), PopoverId::entityIdentifier($entity));

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderDialog', 'postRenderDialog'];
  }

}
