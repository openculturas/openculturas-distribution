<?php

declare(strict_types=1);

namespace Drupal\openculturas_discussions\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook implementations for openculturas_discussions.
 */
class OpenculturasDiscussionsHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasDiscussionsHooks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RequestStack $requestStack,
    protected AccountProxyInterface $currentUser,
  ) {
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    $field_name = $field_definition->getName();
    if ($field_name !== 'field_comments') {
      return AccessResult::neutral();
    }

    if ($operation === 'view' && $field_definition->getName() === 'field_comments') {
      $entity = $items?->getEntity();
      if ($entity instanceof NodeInterface) {
        $mode = $entity->get('field_comments_mode')->value ?? NULL;
        return AccessResult::forbiddenIf(!in_array($mode, [
          'active',
          'freeze',
        ], TRUE));
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Implements hook_ENTITY_TYPE_prepare_form().
   */
  #[Hook('node_prepare_form')]
  public function nodePrepareForm(NodeInterface $node): void {
    $is_comment_entity = $node->bundle() === 'comment' && $node->isNew();
    $request = $this->requestStack->getCurrentRequest();
    if ($is_comment_entity && $request instanceof Request && $request->query->has('content_id')) {
      $content_id = $request->query->get('content_id');
      if (empty($content_id)) {
        return;
      }

      $content_entity = $this->entityTypeManager->getStorage('node')->load($content_id);
      if (!$content_entity instanceof NodeInterface) {
        return;
      }

      $node->set('field_ref_node', [
        'target_id' => $content_entity->id(),
      ]);
      if ($node->isNew() && $node->hasField('field_behalf') && $node->get('field_behalf')->isEmpty()) {
        /** @var \Drupal\user\UserInterface|null $user */
        $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
        $main_profile_non_empty = $user instanceof UserInterface && $user->hasField('field_main_profile') && !$user->get('field_main_profile')->isEmpty();
        if ($main_profile_non_empty) {
          /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
          $field = $user->get('field_main_profile');
          $main_profile = current($field->referencedEntities());
          if ($main_profile instanceof NodeInterface) {
            $node->set('field_behalf', [
              'target_id' => $main_profile->id(),
            ]);
          }
        }
      }
    }
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    $data['openculturas_discussions']['table']['group'] = 'OpenCulturas - Discussions';
    $data['openculturas_discussions']['table']['join'] = [
          // #global is a special flag which allows a table to appear all the time.
      '#global' => [],
    ];
    $data['openculturas_discussions']['openculturas_discussions_login_link'] = [
      'title' => $this->t('Login link'),
      'help' => $this->t('Provides a login link for the anonymous user.'),
      'area' => [
        'id' => 'openculturas_discussions_login_link',
      ],
    ];
    $data['openculturas_discussions']['openculturas_discussions_add_button'] = [
      'title' => $this->t('Add button'),
      'help' => $this->t('Provides a link to add a new comment.'),
      'area' => [
        'id' => 'openculturas_discussions_add_button',
      ],
    ];
  }

}
