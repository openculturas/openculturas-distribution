<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use function trim;

/**
 * Get the preferred langcode of notification recipient.
 *
 * @Action(
 *   id = "openculturas_preferred_langcode_recipients",
 *   label = @Translation("OpenCulturas: Get the preferred langcode of notification recipient"),
 *   description = @Translation("Get the preferred langcode of notification recipient and store it as a token.")
 * )
 */
final class GetPreferredLangcodeForNotificationRecipient extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'token_name' => 'preferred_langcode',
      'recipient' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['recipient'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email of recipient'),
      '#default_value' => $this->configuration['recipient'],
      '#description' => $this->t('Example: <em>mail@example.org</em>. This field supports tokens.'),
    ];
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#description' => $this->t('The preferred langcode will be stored in this token'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    $this->configuration['recipient'] = $form_state->getValue('recipient');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $recipient = trim((string) $this->tokenServices->replaceClear($this->configuration['recipient']));
    if ($recipient === '') {
      throw new \InvalidArgumentException("No recipient specified.");
    }

    /** @var \Drupal\openculturas_custom\EcaNotificationRecipientInterface[] $entities */
    $entities = $this->entityTypeManager->getStorage('eca_notification_recipient')->loadMultiple();
    foreach ($entities as $entity) {
      if ($entity->label() === $recipient) {
        $token_name = trim($this->configuration['token_name'] ?? '');
        if ($token_name === '') {
          $token_name = 'preferred_langcode';
        }

        $this->tokenServices->addTokenData($token_name, $entity->getPreferredLangcode());
        break;
      }
    }
  }

}
