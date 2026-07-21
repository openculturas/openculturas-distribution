<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\block\BlockInterface;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\openculturas_custom\CurrentEntityHelper;

/**
 * Block and field access hook implementations for openculturas_custom.
 */
class OpenculturasCustomAccessHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasCustomAccessHooks.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(protected RouteMatchInterface $routeMatch) {
  }

  /**
   * Implements hook_block_access().
   */
  #[Hook('block_access')]
  public function blockAccess(BlockInterface $block, string $operation, AccountInterface $account): AccessResultInterface {
    // Note: $operation is 'view' during block rendering on ALL pages, including
    // node edit and delete forms. It does NOT indicate we are on a canonical
    // view route. The route check below is required for that distinction.
    if ($operation !== 'view' || !$block->getPlugin() instanceof TitleBlockPluginInterface) {
      return AccessResult::neutral();
    }

    // plugin.manager.layout_builder.section_storage is a soft dependency:
    // layout_builder is not a required module (see openculturas_custom.info.yml),
    // so this cannot be constructor-injected without breaking sites where it
    // is not installed.
    if (!\Drupal::hasService('plugin.manager.layout_builder.section_storage')) {
      return AccessResult::neutral();
    }

    $entity = CurrentEntityHelper::get_current_page_entity();
    if ($entity instanceof ContentEntityInterface) {
      // Only suppress the title block on canonical entity routes. On edit/delete
      // forms the entity appears in route params too, but Layout Builder does not
      // render there, so the title block must remain visible.
      $route_name = $this->routeMatch->getRouteName() ?? '';
      $entity_type_id = $entity->getEntityTypeId();
      if ($route_name !== sprintf('entity.%s.canonical', $entity_type_id)) {
        return AccessResult::neutral();
      }

      $view_mode = 'full';
      // Make the same as \Drupal\layout_builder\LayoutEntityHelperTrait::getSectionStorageForEntity.
      // When we use view-mode full as LB view-mode, we can use an anonymous class + trait.
      $contexts['entity'] = EntityContext::fromEntity($entity);
      $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
      if ($display instanceof LayoutEntityDisplayInterface) {
        $contexts['display'] = EntityContext::fromEntity($display);
      }

      $contexts['view_mode'] = new Context(new ContextDefinition('string'), $view_mode);
      /** @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $sectionStorageManager */
      $sectionStorageManager = \Drupal::service('plugin.manager.layout_builder.section_storage');
      if ($sectionStorageManager->findByContext($contexts, new CacheableMetadata())) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    $field_name = $field_definition->getName();
    if ($operation === 'view' && $field_name === 'field_forfree') {
      $entity = $items?->getEntity();
      // Not for free then there should be a price and hide this field output.
      if ($entity instanceof ContentEntityInterface && $entity->hasField('field_forfree')) {
        $forFree = (bool) $entity->get('field_forfree')->value;
        return AccessResult::forbiddenIf($forFree === FALSE);
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Implements hook_local_tasks_alter().
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(array &$local_tasks): void {
    if (isset($local_tasks['entity.user.canonical'])) {
      $local_tasks['entity.user.canonical']['title'] = $this->t('My content');
      $local_tasks['entity.user.canonical']['weight'] = -3;
    }

    if (isset($local_tasks['entity.user.edit_form'])) {
      $local_tasks['entity.user.edit_form']['title'] = $this->t('Account settings');
      $local_tasks['entity.user.edit_form']['weight'] = -2;
    }
  }

}
