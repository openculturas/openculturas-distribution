<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Form\OSM;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\openculturas_openstreetmap\OpenStreetMap\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function sprintf;
use function substr;

final class PushData extends FormBase {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PushData {
    $instance = parent::create($container);
    $instance->state = $container->get('state');
    $instance->logger = $container->get('logger.channel.openculturas_openstreetmap');
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
      /** @var string $current_osm_id */
      $current_osm_id = $this->state->get('openculturas_openstreetmap.settings.osmid');
      $osm_id = substr($current_osm_id, 1);
      $osm_type_short = $current_osm_id[0];
      $osm_type = NULL;
      try {
        $osm_type = ApiClient::osmTypeShortToLong($osm_type_short);
      }
      catch (\Exception $e) {
        Error::logException($this->logger, $e);
      }

      if ($osm_type) {
        $url = Url::fromUri(sprintf('%s/%s/%s', ApiClient::DEV_ENDPOINT, $osm_type, $osm_id));
        $this->messenger()->addWarning($this->t('Development mode enabled. <br>All changes will be added to: %url', ['%url' => Link::fromTextAndUrl($url->toString(), $url)->toString()]));
      }

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
