<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Form\OSM;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\openculturas_openstreetmap\OpenStreetMap\ApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class PushData extends FormBase {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PushData {
    $instance = parent::create($container);
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'push_data';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    if ($this->state->get('openculturas_openstreetmap.settings.devmode')) {
      $url = Url::fromUri(ApiClient::DEV_ENDPOINT . '/node/' . $this->state->get('openculturas_openstreetmap.settings.osmid'));
      $this->messenger()->addWarning($this->t('Development mode enabled. <br>All changes will be added to: %url', ['%url' => Link::fromTextAndUrl($url->toString(), $url)->toString()]));
      $form['status_messages'] = [
        '#theme' => 'status_messages',
        '#message_list' => $this->messenger()->deleteAll(),
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];
    }

    $form['changeset_comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#default_value' => $cached_values['changeset_comment'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $keys = [
      'changeset_comment',
    ];
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    foreach ($keys as $key) {
      $cached_values[$key] = $form_state->getValue($key);
    }

    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
