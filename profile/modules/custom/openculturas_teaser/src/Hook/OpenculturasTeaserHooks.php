<?php

declare(strict_types=1);

namespace Drupal\openculturas_teaser\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openculturas_teaser\Plugin\paragraphs\Behavior\TeaserBehaviorBase;

/**
 * Hook implementations for openculturas_teaser.
 */
class OpenculturasTeaserHooks {
  use StringTranslationTrait;

  /**
   * Paragraph bundles offering the "Teaser image view mode" Manage Display control.
   */
  private const array TEASER_BUNDLES = ['teaser_node', 'teaser_term', 'teaser_external'];

  /**
   * Constructs a new OpenculturasTeaserHooks.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   */
  public function __construct(
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'teaser' => [
        'variables' => [
          'title' => NULL,
          'subtitle' => NULL,
          'description' => NULL,
          'media' => NULL,
          'url' => NULL,
          'attributes' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    if (array_key_exists('#paragraph_type', $element)) {
      switch ($element['#paragraph_type']) {
        case 'teaser_node':
        case 'teaser_term':
        case 'teaser_external':
          if (isset($element['subform']) && is_array($element['subform'])) {
            $element['subform']['#weight'] = 1;
          }

          if (isset($element['behavior_plugins']) && is_array($element['behavior_plugins'])) {
            $element['behavior_plugins']['#weight'] = 2;
          }

          break;
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for entity_view_display_edit_form.
   *
   * Adds a "Teaser image view mode" control to Manage Display for the teaser
   * paragraph bundles, letting a site builder pick which media view mode
   * renders the teaser image at this paragraph view mode. Stored as a
   * third-party setting on the display itself (not tied to a field), since
   * the teaser image is built entirely in PHP by the teaser Behavior
   * plugins (see TeaserBehaviorBase::getConfiguredMediaViewMode()), not
   * through a normal field formatter.
   */
  #[Hook('form_entity_view_display_edit_form_alter')]
  public function formEntityViewDisplayEditFormAlter(array &$form, FormStateInterface $form_state): void {
    $formObject = $form_state->getFormObject();
    if (!$formObject instanceof EntityFormInterface) {
      return;
    }

    $display = $formObject->getEntity();
    if (!$display instanceof EntityViewDisplayInterface
      || $display->getTargetEntityTypeId() !== 'paragraph'
      || !in_array($display->getTargetBundle(), self::TEASER_BUNDLES, TRUE)
    ) {
      return;
    }

    $value = $display->getThirdPartySetting('openculturas_teaser', 'media_view_mode');
    $form['openculturas_teaser_media_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Teaser image view mode'),
      '#description' => $this->t('The media view mode used for this teaser image at this view mode. Overrides any other image style configuration for the teaser image.'),
      '#options' => $this->entityDisplayRepository->getViewModeOptionsByBundle('media', 'image'),
      '#default_value' => is_string($value) && $value !== '' ? $value : TeaserBehaviorBase::DEFAULT_MEDIA_VIEW_MODE,
      '#weight' => -10,
    ];
    $form['#entity_builders'][] = [self::class, 'buildMediaViewModeThirdPartySetting'];
  }

  /**
   * Entity builder callback for entity_view_display_edit_form.
   *
   * Stores the "Teaser image view mode" value as a third-party setting on
   * the entity view display being saved, unless it matches the default
   * (TeaserBehaviorBase::DEFAULT_MEDIA_VIEW_MODE, "teaser_image_big"), in
   * which case the setting is removed instead -
   * TeaserBehaviorBase::getConfiguredMediaViewMode() already falls back to
   * that default when the setting is absent, so storing it explicitly would
   * only add noise to every teaser display's exported configuration.
   */
  public static function buildMediaViewModeThirdPartySetting(string $entity_type_id, EntityInterface $entity, array &$form, FormStateInterface $form_state): void {
    if (!$entity instanceof EntityViewDisplayInterface) {
      return;
    }

    $value = $form_state->getValue('openculturas_teaser_media_view_mode');
    if (is_string($value) && $value !== '' && $value !== TeaserBehaviorBase::DEFAULT_MEDIA_VIEW_MODE) {
      $entity->setThirdPartySetting('openculturas_teaser', 'media_view_mode', $value);
    }
    else {
      $entity->unsetThirdPartySetting('openculturas_teaser', 'media_view_mode');
    }
  }

}
