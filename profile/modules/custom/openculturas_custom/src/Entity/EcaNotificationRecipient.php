<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Entity\Eca;
use Drupal\openculturas_custom\EcaNotificationRecipientInterface;
use Drupal\openculturas_custom\EcaNotificationRecipientListBuilder;
use Drupal\openculturas_custom\Form\EcaNotificationRecipientForm;
use function array_filter;
use function is_array;

/**
 * Defines the eca notification recipient entity type.
 */
#[ConfigEntityType(
  id: 'eca_notification_recipient',
  label: new TranslatableMarkup('Notification recipient'),
  label_collection: new TranslatableMarkup('Notification recipients'),
  label_singular: new TranslatableMarkup('notification recipient'),
  label_plural: new TranslatableMarkup('notification recipients'),
  config_prefix: 'eca_notification_recipient',
  static_cache: TRUE,
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'eca_model' => 'eca_model',
    'preferred_langcode' => 'preferred_langcode',
  ],
  handlers: [
    'list_builder' => EcaNotificationRecipientListBuilder::class,
    'form' => [
      'add' => EcaNotificationRecipientForm::class,
      'edit' => EcaNotificationRecipientForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'collection' => '/admin/config/workflow/eca-notification-recipient',
    'add-form' => '/admin/config/workflow/eca-notification-recipient/add',
    'edit-form' => '/admin/config/workflow/eca-notification-recipient/{eca_notification_recipient}',
    'delete-form' => '/admin/config/workflow/eca-notification-recipient/{eca_notification_recipient}/delete',
  ],
  admin_permission: 'administer eca_notification_recipient',
  label_count: [
    'singular' => '@count notification recipient',
    'plural' => '@count notification recipients',
  ],
  config_export: [
    'id',
    'label',
    'eca_model',
    'preferred_langcode',
  ],
)]
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
  #[\Override]
  public function preSave(EntityStorageInterface $storage): void {
    $this->eca_model = array_filter($this->eca_model);
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
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
  #[\Override]
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
    $ecaModel = is_array($this->get('eca_model')) ? $this->get('eca_model') : [];
    return (array_filter($ecaModel) === [] || $model === '') || ($ecaModel[$model] ?? FALSE);
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
