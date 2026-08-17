<?php

declare(strict_types=1);

namespace Drupal\cookies_popover\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a button that reopens the cookiesjsr settings layer.
 */
#[Block(
  id: 'cookies_settings_button',
  admin_label: new TranslatableMarkup('Cookie settings (reopen button)'),
  category: new TranslatableMarkup('Cookies Popover'),
)]
class CookiesSettingsButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function defaultConfiguration(): array {
    return [
      'button_text' => 'Privacy settings',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $this->configuration['button_text'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['button_text'] = $form_state->getValue('button_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      'content' => [
        '#theme' => 'cookies_popover_settings_button',
        '#text' => $this->configuration['button_text'],
      ],
    ];
  }

}
