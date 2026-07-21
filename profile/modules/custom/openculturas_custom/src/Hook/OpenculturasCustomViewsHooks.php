<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;
use Drupal\views\ViewExecutable;

/**
 * Views hook implementations for openculturas_custom.
 */
class OpenculturasCustomViewsHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasCustomViewsHooks.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(protected AccountProxyInterface $currentUser) {
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_views_exposed_form_alter')]
  public function formViewsExposedFormAlter(array &$form, FormStateInterface $form_state): void {
    if ($form['#id'] === 'views-exposed-form-search-search-input') {
      $form['fulltext']['#title_display'] = 'invisible';
    }
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    $data['views']['non_translatable_nothing'] = [
      'title' => $this->t('Custom text (non translatable)'),
      'help' => $this->t('Provide custom text or link.'),
      'field' => [
        'id' => 'non_translatable_custom',
        'click sortable' => FALSE,
      ],
    ];
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    // Only doing this for media library widget view until there is
    // a better solution.
    // https://www.drupal.org/project/drupal/issues/3283692#comment-14544647
    if ($view->id() === 'media_library') {
      $result = [];
      // Display only media authored by the current user or can edit.
      foreach ($view->result as $key => $row) {
        /** @var \Drupal\media\MediaInterface $entity */
        $entity = $row->_entity;
        // Do not remove type casting. current user id is a string-int!
        if ((int) $entity->getOwnerId() === $this->currentUser->id() || $entity->access('update') || $this->isMediaCreativeCommons($entity)) {
          $result[$key] = $row;
        }
      }

      $view->result = $result;
    }
  }

  /**
   * Checks the licenses for Creative-Commons.
   */
  protected function isMediaCreativeCommons(MediaInterface $media): bool {
    if ($media->hasField('field_licenses') && !$media->get('field_licenses')->isEmpty()) {
      $field = $media->get('field_licenses');
      $field_item = $field->first();
      if (!$field_item instanceof FieldItemInterface) {
        return FALSE;
      }

      $value = $field_item->getValue();
      if (is_array($value) && isset($value['license'])) {
        return $value['license'] !== 'all_rights_reserved';
      }
    }

    return FALSE;
  }

}
