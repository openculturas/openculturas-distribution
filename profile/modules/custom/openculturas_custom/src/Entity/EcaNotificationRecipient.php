<?php

declare(strict_types = 1);

namespace Drupal\openculturas_custom\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\eca\Entity\Eca;
use Drupal\openculturas_custom\EcaNotificationRecipientInterface;
use function array_filter;

/**
 * Defines the eca notification recipient entity type.
 *
 * @ConfigEntityType(
 *   id = "eca_notification_recipient",
 *   label = @Translation("Notification recipient"),
 *   label_collection = @Translation("Notification recipients"),
 *   label_singular = @Translation("notification recipient"),
 *   label_plural = @Translation("notification recipients"),
 *   label_count = @PluralTranslation(
 *     singular = "@count notification recipient",
 *     plural = "@count notification recipients",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\openculturas_custom\EcaNotificationRecipientListBuilder",
 *     "form" = {
 *       "add" = "Drupal\openculturas_custom\Form\EcaNotificationRecipientForm",
 *       "edit" = "Drupal\openculturas_custom\Form\EcaNotificationRecipientForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "eca_notification_recipient",
 *   admin_permission = "administer eca_notification_recipient",
 *   static_cache = TRUE,
 *   links = {
 *     "collection" = "/admin/config/workflow/eca-notification-recipient",
 *     "add-form" = "/admin/config/workflow/eca-notification-recipient/add",
 *     "edit-form" = "/admin/config/workflow/eca-notification-recipient/{eca_notification_recipient}",
 *     "delete-form" = "/admin/config/workflow/eca-notification-recipient/{eca_notification_recipient}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "eca_model" = "eca_model",
 *     "preferred_langcode" = "preferred_langcode"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "eca_model",
 *     "preferred_langcode"
 *   }
 * )
 */
class EcaNotificationRecipient extends ConfigEntityBase implements EcaNotificationRecipientInterface {

  /**
   * The eca notification recipient ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The eca notification recipient label.
   *
   * @var string
   */
  protected string $label;

  /**
   * The eca notification recipient status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The eca_notification_recipient eca_model.
   *
   * @var array
   */
  protected array $eca_model;

  /**
   * The eca_notification_recipient preferred_langcode.
   *
   * @var string
   */
  protected string $preferred_langcode;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $entityStorage): void {
    $this->eca_model = array_filter($this->eca_model);
    parent::preSave($entityStorage);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): self {
    parent::calculateDependencies();
    foreach ($this->eca_model as $model_name => $status) {
      if ($status) {
        $model = $this->entityTypeManager()->getStorage('eca')->load($model_name);
        if (!$model instanceof Eca) {
          continue;
        }

        $this->addDependency('config', $model->getConfigDependencyName());
      }
    }

    $this->addDependency('module', 'eca');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = parent::onDependencyRemoval($dependencies);
    foreach (array_keys($this->eca_model) as $model_name) {
      $model = $this->entityTypeManager()->getStorage('eca')->load($model_name);
      if (!$model instanceof Eca) {
        continue;
      }

      $name = $model->getConfigDependencyName();
      if (isset($dependencies['config'][$name], $this->eca_model[$model_name])) {
        unset($this->eca_model[$model_name]);
        $changed = TRUE;
      }
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function isEcaModelEnabledForRecipient(string $model): bool {
    return (array_filter($this->get('eca_model')) === [] || $model === '') || ($this->get('eca_model')[$model] ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredLangcode(): string {
    $language_list = $this->languageManager()->getLanguages();
    $preferred_langcode = $this->get('preferred_langcode');
    if (empty($preferred_langcode)) {
      return $this->languageManager()->getDefaultLanguage()->getId();
    }

    if (!isset($language_list[$preferred_langcode])) {
      return $this->languageManager()->getDefaultLanguage()->getId();
    }

    return $language_list[$preferred_langcode]->getId();
  }

}
