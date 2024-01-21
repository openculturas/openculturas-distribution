<?php

declare(strict_types = 1);

namespace Drupal\openculturas_custom\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\openculturas_custom\Entity\EcaNotificationRecipient;
use function in_array;

/**
 * ECA notification recipient form.
 *
 * @property \Drupal\openculturas_custom\EcaNotificationRecipientInterface $entity
 */
class EcaNotificationRecipientForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $formState): array {

    $form = parent::form($form, $formState);

    $form['label'] = [
      '#type' => 'email',
      '#title' => $this->t('Recipient email'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Email of the recipient.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => [
        'exists' => [EcaNotificationRecipient::class, 'load'],
      ],
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];
    $options = [];
    /** @var \Drupal\eca\Entity\Eca[] $entities */
    $entities = $this->entityTypeManager->getStorage('eca')->loadMultiple();
    foreach ($entities as $entity) {
      if (!in_array('notification', $entity->getModel()->getTags(), TRUE)) {
        continue;
      }

      $options[$entity->id()] = $entity->label();
    }

    $models = $this->entity->get('eca_model') ?? [];
    $form['eca_model'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Notifications'),
      '#options' => $options,
      '#default_value' => array_keys(array_filter($models)),
      '#description' => $this->t('Leave blank to get all notifications.'),
    ];
    $form['preferred_langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_CONFIGURABLE,
      '#default_value' => $this->entity->get('preferred_langcode'),
      '#description' => $this->t("This recipient's preferred language for emails."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {
    $result = parent::save($form, $formState);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new notification recipient %label.', $message_args)
      : $this->t('Updated notification recipient %label.', $message_args);
    $this->messenger()->addStatus($message);
    $formState->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
