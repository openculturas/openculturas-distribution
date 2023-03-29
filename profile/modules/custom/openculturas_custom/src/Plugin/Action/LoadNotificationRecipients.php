<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use function trim;

/**
 * Load the notification recipients.
 *
 * @Action(
 *   id = "openculturas_load_notification_recipients",
 *   label = @Translation("OpenCulturas: Load the notification recipients"),
 *   description = @Translation("Load the notification recipients for current model and store it as a token.")
 * )
 */
final class LoadNotificationRecipients extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'token_name' => 'recipients',
      'model' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#description' => $this->t('The recipients will be stored in this token as list.'),
    ];
    $form['model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ECA Model'),
      '#default_value' => $this->configuration['model'],
      '#description' => $this->t('Leave empty to send the notification to all recipients.'),
    ];
    return $form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $model = $form_state->getValue('model');

    if ($model !== '') {
      $entity = $this->entityTypeManager->getStorage('eca')->load($model);
      if (!$entity instanceof Eca) {
        $form_state->setErrorByName('model', $this->t('This %name is not a valid eca model', ['%name' => $model]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    $this->configuration['model'] = $form_state->getValue('model');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    /** @var \Drupal\openculturas_custom\EcaNotificationRecipientInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage('eca_notification_recipient')->loadMultiple();
    $recipients = [];
    $eca_model = trim($this->configuration['model'] ?? '');
    foreach ($entities as $entity) {
      if ($entity->status() && $entity->isEcaModelEnabledForRecipient($eca_model)) {
        $recipients[] = (string) $entity->label();
      }
    }
    if ($recipients) {
      $token_name = trim($this->configuration['token_name'] ?? '');
      if ($token_name === '') {
        $token_name = 'recipients';
      }
      $this->tokenServices->addTokenData($token_name, $recipients);
    }
  }

}
