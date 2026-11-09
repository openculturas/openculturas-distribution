<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;

/**
 * Views selection handler that also accepts newly created entities.
 *
 * \Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection does not
 * implement SelectionWithAutocreateInterface, so
 * \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator
 * rejects every unsaved entity referenced through a field with
 * "handler: views", such as one built by the Inline Entity Form widget, or
 * typed as free text into a tags autocomplete widget with "Create referenced
 * entities if they don't already exist" enabled.
 *
 * This plugin is its own group (id equals group), not a sub-plugin of
 * "views", so it appears as its own "Reference method" option in the field
 * settings UI. A sub-plugin like "views:autocreate" would stay invisible
 * there: \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::fieldSettingsForm()
 * only lists one option per group, keyed by a plugin ID that equals either
 * the group name or "{group}:{target_type}". A site builder could then
 * unknowingly reset the field back to plain "views" by saving any unrelated
 * field setting, since the stored handler would match nothing in the select.
 *
 * The view selects the existing entities. "target_bundles" has the same
 * format and meaning for new entities as in DefaultSelection: it limits the
 * bundles of new entities. Widgets read it from the handler settings to
 * decide which bundles they offer for new entities:
 * - Inline Entity Form offers only the target bundles the user may create.
 *   Without target bundles, it offers every bundle of the target type.
 * - The autocomplete widget creates new entities in the only target bundle,
 *   or in "auto_create_bundle" if there are several.
 *
 * validateReferenceableNewEntities() also checks "create" access of the
 * current user, as https://www.drupal.org/project/drupal/issues/3372919
 * proposes for DefaultSelection. A new entity the user may not create gets
 * the "cannot be referenced" form error. This also covers widgets that do not
 * check access themselves.
 */
#[EntityReferenceSelection(
  id: 'views_autocreate',
  label: new TranslatableMarkup('Views: Filter by an entity reference view (allow new entities)'),
  group: 'views_autocreate',
  weight: 0,
)]
final class ViewsAutocreateSelection extends ViewsSelection implements SelectionWithAutocreateInterface {

  /**
   * Constructs a new ViewsAutocreateSelection object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user,
    RendererInterface $renderer,
    /**
     * The entity type bundle info service.
     */
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function defaultConfiguration(): array {
    return [
      // NULL allows new entities of every bundle, as in DefaultSelection.
      'target_bundles' => NULL,
      'auto_create' => FALSE,
      'auto_create_bundle' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();
    $entity_type = $this->entityTypeManager->getDefinition($configuration['target_type']);
    if ($entity_type->hasKey('bundle')) {
      $bundle_options = [];
      foreach ($this->entityTypeBundleInfo->getBundleInfo($configuration['target_type']) as $bundle_name => $bundle_info) {
        $bundle_options[$bundle_name] = $bundle_info['label'];
      }

      natsort($bundle_options);

      $form['target_bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('@bundle_label of new items', ['@bundle_label' => $entity_type->getBundleLabel()]),
        '#description' => $this->t('The view selects existing items. New items, for example from an inline entity form, are limited to these.'),
        '#options' => $bundle_options,
        '#default_value' => (array) $configuration['target_bundles'],
        '#required' => TRUE,
        '#element_validate' => [[self::class, 'validateTargetBundles']],
        '#weight' => -3,
      ];
    }

    $form['auto_create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Create referenced entities if they don't already exist"),
      '#default_value' => $configuration['auto_create'],
      '#weight' => -2,
    ];

    if (isset($bundle_options) && count($bundle_options) > 1) {
      $form['auto_create_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Store new items in'),
        '#description' => $this->t('Only used if more than one bundle is selected above.'),
        '#options' => $bundle_options,
        '#empty_value' => '',
        '#default_value' => $configuration['auto_create_bundle'],
        '#element_validate' => [[self::class, 'validateAutoCreateBundle']],
        '#states' => [
          'visible' => [
            ':input[name="settings[handler_settings][auto_create]"]' => ['checked' => TRUE],
          ],
        ],
        '#weight' => -1,
      ];
    }

    return $form;
  }

  /**
   * Stores only the checked target bundles.
   */
  public static function validateTargetBundles(array &$element, FormStateInterface $form_state): void {
    $form_state->setValueForElement($element, array_filter((array) $element['#value']));
  }

  /**
   * Requires an autocreate bundle among several target bundles.
   *
   * The autocomplete widget needs it to know where to store a new item.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget::getAutocreateBundle()
   */
  public static function validateAutoCreateBundle(array &$element, FormStateInterface $form_state): void {
    $parents = array_slice($element['#parents'], 0, -1);
    if (!$form_state->getValue([...$parents, 'auto_create'])) {
      $form_state->setValueForElement($element, NULL);
      return;
    }

    $target_bundles = array_filter((array) $form_state->getValue([...$parents, 'target_bundles']));
    if (count($target_bundles) < 2) {
      $form_state->setValueForElement($element, NULL);
      return;
    }

    if (!in_array($element['#value'], $target_bundles, TRUE)) {
      $form_state->setError($element, new TranslatableMarkup('Select one of the selected bundles to store new items in.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function createNewEntity($entity_type_id, $bundle, $label, $uid): EntityInterface {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $values = [$entity_type->getKey('label') => $label];
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $values[$bundle_key] = $bundle;
    }

    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function validateReferenceableNewEntities(array $entities): array {
    $target_bundles = $this->getConfiguration()['target_bundles'] ?? NULL;
    return array_filter($entities, function (EntityInterface $entity) use ($target_bundles): bool {
      if (isset($target_bundles) && !in_array($entity->bundle(), $target_bundles, TRUE)) {
        return FALSE;
      }

      return $this->entityTypeManager
        ->getAccessControlHandler($entity->getEntityTypeId())
        ->createAccess($entity->bundle(), $this->currentUser);
    });
  }

}
