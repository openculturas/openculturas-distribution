<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

/**
 * Flag module hook implementations for openculturas_custom.
 */
class OpenculturasCustomFlagHooks {

  /**
   * Constructs a new OpenculturasCustomFlagHooks.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('flagging_insert')]
  public function flaggingInsert(FlaggingInterface $entity): void {
    if ($entity->getFlagId() === 'recommendation_node') {
      $flagged_entity = $entity->getFlaggable();
      $this->cacheTagsInvalidator->invalidateTags($flagged_entity->getCacheTagsToInvalidate());
    }
    elseif (in_array($entity->getFlagId(), [
      'bookmark_node',
      'bookmark_term',
    ], TRUE)) {
      $id = $this->currentUser->id();
      $tags = [
        'entity_view_user_' . $id . '_full',
      ];
      $this->cacheTagsInvalidator->invalidateTags($tags);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('flagging_update')]
  public function flaggingUpdate(FlaggingInterface $entity): void {
    if ($entity->getFlagId() === 'recommendation_node') {
      $flagged_entity = $entity->getFlaggable();
      $this->cacheTagsInvalidator->invalidateTags($flagged_entity->getCacheTagsToInvalidate());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_prepare_form().
   */
  #[Hook('flagging_prepare_form')]
  public function flaggingPrepareForm(FlaggingInterface $entity): void {
    if ($entity->isNew() && $entity->hasField('field_behalf') && $entity->get('field_behalf')->isEmpty()) {
      /** @var \Drupal\user\UserInterface|null $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $main_profile_non_empty = $user instanceof UserInterface && $user->hasField('field_main_profile') && !$user->get('field_main_profile')->isEmpty();
      if ($main_profile_non_empty) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
        $field = $user->get('field_main_profile');
        $main_profile = current($field->referencedEntities());
        if ($main_profile instanceof NodeInterface) {
          $entity->set('field_behalf', [
            'target_id' => $main_profile->id(),
          ]);
        }
      }
    }
  }

  /**
   * Implements hook_flag_action_access().
   */
  #[Hook('flag_action_access')]
  public function flagActionAccess(string $action, FlagInterface $flag, AccountInterface $account, ?EntityInterface $flaggable = NULL): AccessResultInterface {
    if ($account->isAnonymous()) {
      return AccessResult::neutral();
    }

    /** @var \Drupal\node\NodeInterface|null $entity */
    $entity = $flaggable;
    if ($entity && $flag->id() === 'claim_ownership' && $entity->hasField('field_allow_claiming') && !$entity->get('field_allow_claiming')->isEmpty()) {
      $allowed = (bool) $entity->get('field_allow_claiming')->value;
      if (!$allowed) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(array &$build, EntityInterface $entity): void {
    // When LB is used then somehow name is not set, but used by template_preprocess_taxonomy_term().
    if ($entity->getEntityTypeId() === 'taxonomy_term' && !isset($build['name'])) {
      $build['name'] = [
        '#plain_text' => $entity->label(),
      ];
    }

    if (isset($build['_layout_builder'])) {
      // Do nothing when LB is used.
      return;
    }

    $isAnonymous = $this->currentUser->isAnonymous();
    if ($isAnonymous && ($entity->getEntityTypeId() === 'node' || $entity->getEntityTypeId() === 'taxonomy_term')) {
      if (isset($build['flag_bookmark_node'])) {
        $build['flag_bookmark_node'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
          <div class="flag flag-bookmark-node action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to bookmark' | t}}">{{ 'Log in to bookmark' | t}}</a></div>
          EOF,
          '#weight' => $build['flag_bookmark_node']['#weight'],
        ];
      }

      if (isset($build['flag_bookmark_term'])) {
        $build['flag_bookmark_term'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
          <div class="flag flag-bookmark-term action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to bookmark' | t}}">{{ 'Log in to bookmark' | t}}</a></div>
          EOF,
          '#weight' => $build['flag_bookmark_term']['#weight'],
        ];
      }

      if (isset($build['flag_recommendation_node'])) {
        $build['flag_recommendation_node'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
          <div class="flag flag-recommendation-node action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to recommend'| t}}">{{ 'Recommend' | t}}</a></div>
          EOF,
          '#weight' => $build['flag_recommendation_node']['#weight'],
        ];
      }

      assert($entity instanceof NodeInterface || $entity instanceof TermInterface);
      if (isset($build['flag_claim_ownership']) && $entity->hasField('field_allow_claiming') && !$entity->get('field_allow_claiming')->isEmpty()) {
        $allowed = (bool) $entity->get('field_allow_claiming')->value;
        if ($allowed) {
          $build['flag_claim_ownership'] = [
            '#type' => 'inline_template',
            '#template' => <<<EOF
            <div class="flag flag-claim-ownership action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to claim this' | t}}">{{ 'Claim ownership' | t}}</a></div>
            EOF,
            '#weight' => $build['flag_claim_ownership']['#weight'],
          ];
        }
      }

      if (isset($build['flag_report_abuse'])) {
        $build['flag_report_abuse'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
          <div class="flag flag-report-abuse action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to report abuse' | t}}">{{ 'Report abuse' | t}}</a></div>
          EOF,
          '#weight' => $build['flag_report_abuse']['#weight'],
        ];
      }
    }
  }

}
