<?php

namespace Drupal\openculturas_openstreetmap\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\openculturas_openstreetmap\OpenStreetMap\ApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function file_exists;
use function json_encode;
use function sprintf;
use function substr;

final class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * @var \Drupal\openculturas_openstreetmap\OpenStreetMap\ApiClient
   */
  protected ApiClient $apiClient;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    /** @var static $instance */
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->state = $container->get('state');
    $instance->apiClient = $container->get('openculturas_openstreetmap.api_client');
    $instance->fileSystem = $container->get('file_system');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['openculturas_openstreetmap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openculturas_openstreetmap_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('openculturas_openstreetmap.settings');
    $form['bounds'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Boundary'),
      '#tree' => TRUE,
    ];
    $form['bounds']['predefined_list'] = [
      '#title' => $this->t('Area'),
      '#type' => 'select',
      '#options' => [
        'de' => $this->t('Germany'),
        'at' => $this->t('Austria'),
        'ch' => $this->t('Switzerland'),
        'eu' => $this->t('Europe'),
        'ww' => $this->t('Worldwide'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $config->get('predefined_list') ?? 'de',
      '#required' => TRUE,
    ];

    $states = [
      'visible' => [
        ':input[name="bounds[predefined_list]"]' => ['value' => 'custom'],
      ],
      'required' => [
        ':input[name="bounds[predefined_list]"]' => ['value' => 'custom'],
      ],
    ];

    $form['bounds']['west'] = [
      '#type' => 'textfield',
      '#title' => $this->t('West'),
      '#default_value' => $config->get('bounds.west'),
      '#size' => 10,
      '#states' => $states,
    ];
    $form['bounds']['south'] = [
      '#type' => 'textfield',
      '#title' => $this->t('South'),
      '#default_value' => $config->get('bounds.south'),
      '#size' => 10,
      '#states' => $states,
    ];
    $form['bounds']['east'] = [
      '#type' => 'textfield',
      '#title' => $this->t('East'),
      '#default_value' => $config->get('bounds.east'),
      '#size' => 10,
      '#states' => $states,
    ];
    $form['bounds']['north'] = [
      '#type' => 'textfield',
      '#title' => $this->t('North'),
      '#default_value' => $config->get('bounds.north'),
      '#size' => 10,
      '#states' => $states,
    ];
    $form['nominatim_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => $config->get('nominatim_email'),
      '#description' => $this->t("If you are making large numbers of request please include an appropriate email address to identify your requests. See Nominatim's <a href='@url'>Usage Policy</a> for more details.",
        ['@url' => 'https://operations.osmfoundation.org/policies/nominatim/']
      ),
    ];
    $form['changeset_footer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Changeset comment footer'),
      '#description' => $this->t('Appended to the changeset comment. It is good practise to name the platform, therefore use at least the token [site:base-url]'),
      '#default_value' => $config->get('changeset_footer'),
    ];
    $form['token_tree'] = [
      '#token_tree_table' => TRUE,
      '#theme' => 'token_tree_link',
      '#token_types' => ['node'],
    ];
    $oauth2_client_storage = $this->entityTypeManager->getStorage('oauth2_client');
    /** @var \Drupal\oauth2_client\Entity\Oauth2ClientInterface|null $client_config */
    $client_config = $oauth2_client_storage->load('openstreetmap');
    /** @var \Drupal\oauth2_client\Entity\Oauth2ClientInterface|null $client_config_dev */
    $client_config_dev = $oauth2_client_storage->load('openstreetmap_dev');
    $form['oauth_credentials'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('OAuth credentials'),
      '#description' => $this->t('OAuth credentials exist: @current_value', ['@current_value' => file_exists('private://osm/secret.json') ? $this->t('Yes') : $this->t('No')]),
    ];
    $form['oauth_credentials']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#disabled' => !$client_config,
    ];
    $form['oauth_credentials']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#disabled' => !$client_config,
    ];
    $form['push_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow pushing to OpenStreetMap'),
      '#description' => $this->t('Allows users to not only pull information from OpenStreetMap (OSM) but also to push updates to OSM. This toggle is disabled unless OAuth2 client is enabled and configured accordingly.'),
      '#default_value' => $this->state->get('openculturas_openstreetmap.settings.push_enabled'),
      '#disabled' => $this->state->get('openculturas_openstreetmap.settings.devmode') ? (!$client_config_dev || !$client_config_dev->status()) : (!$client_config || !$client_config->status()),
    ];

    if ($form['push_enabled']['#disabled']) {
      $form['push_enabled']['#default_value'] = FALSE;
    }

    $form['devmode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Development mode'),
      '#description' => $this->t("When active you can use OSMʼs development server to test this module. Please read the instructions on the <a href='@help-url'>help page</a>.", [
        '@help-url' => Url::fromRoute('help.page', ['name' => 'openculturas_openstreetmap'])->toString(),
      ]),
      '#default_value' => $this->state->get('openculturas_openstreetmap.settings.devmode'),
      '#disabled' => !$client_config_dev || !$client_config_dev->status(),
    ];
    if ($form['devmode']['#disabled']) {
      $form['devmode']['#default_value'] = FALSE;
    }

    $form['osmid'] = [
      '#type' => 'textfield',
      '#title' => 'OSM ID',
      '#description' => $this->t('The location ID on the development server to apply your changes to for testing purposes. Prefixed with N (node) or W (way) or R (relation).'),
      '#default_value' => $this->state->get('openculturas_openstreetmap.settings.osmid'),
      '#states' => [
        'visible' => [
          ':input[name="devmode"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="devmode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if ($this->state->get('openculturas_openstreetmap.settings.devmode', FALSE) && $this->state->get('openculturas_openstreetmap.settings.push_enabled', FALSE)) {
      /** @var string $current_osm_id */
      $current_osm_id = $this->state->get('openculturas_openstreetmap.settings.osmid');
      $osm_id = substr($current_osm_id, 1);
      $osm_type_short = $current_osm_id[0];
      $osm_type = NULL;
      try {
        $osm_type = ApiClient::osmTypeShortToLong($osm_type_short);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
      }

      if ($osm_type) {
        $url = Url::fromUri(sprintf('%s/%s/%s', ApiClient::DEV_ENDPOINT, $osm_type, $osm_id));
        $this->messenger()->addWarning($this->t('Development mode enabled. <br>All changes will be added to: %url', ['%url' => Link::fromTextAndUrl($url->toString(), $url)->toString()]));
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $created_file = FALSE;
    if ($form_state->getValue('client_id') && $form_state->getValue('client_secret')) {
      $data = json_encode([
        'client_id' => $form_state->getValue('client_id'),
        'client_secret' => $form_state->getValue('client_secret'),
      ], JSON_THROW_ON_ERROR);

      try {
        $directory = 'private://osm';
        if ($this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
          $created_file = $this->fileSystem->saveData($data, 'private://osm/secret.json', FileExists::Replace);
        }
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
      }

      if ($created_file) {
        $this->messenger()->addStatus($this->t('The secret file has been created.'));
      }
    }

    $dev_mode = (bool) $form_state->getValue('devmode');
    /** @var string $current_osm_id */
    $current_osm_id = $form_state->getValue('osmid');
    if ($dev_mode && $current_osm_id !== '') {
      $osm_type_short = $current_osm_id[0];
      try {
        ApiClient::osmTypeShortToLong($osm_type_short);
      }
      catch (\Exception $e) {
        $form_state->setErrorByName('osmid', $e->getMessage());
      }
    }

    if ($form_state->getValue('push_enabled') && !$this->apiClient->hasToken($dev_mode)) {
      $form_state->setError($form['push_enabled'], $this->t('Now <a href="@edit_url">request a token</a>.', ['@edit_url' => Url::fromRoute('entity.oauth2_client.edit_form', ['oauth2_client' => $dev_mode ? 'openstreetmap_dev' : 'openstreetmap'])->toString()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('openculturas_openstreetmap.settings');

    if ($form_state->getValue('changeset_footer')) {
      $config->set('changeset_footer', $form_state->getValue('changeset_footer'));
    }
    else {
      $config->clear('changeset_footer');
    }

    if ($form_state->getValue('nominatim_email', '') !== '') {
      $config->set('nominatim_email', $form_state->getValue('nominatim_email'));
    }
    else {
      $config->clear('nominatim_email');
    }

    if ($form_state->getValue('push_enabled') !== NULL) {
      $this->state->set('openculturas_openstreetmap.settings.push_enabled', (bool) $form_state->getValue('push_enabled'));
    }
    else {
      $this->state->deleteMultiple(['openculturas_openstreetmap.settings.push_enabled']);
    }

    /** @var array{predefined_list: string, west: ?string, south: ?string, east: ?string, north: ?string} $bounds */
    $bounds = $form_state->getValue('bounds');
    $list = $bounds['predefined_list'];
    $config->set('predefined_list', $list);
    unset($bounds['predefined_list']);
    if ($list === 'custom') {
      $config->set('bounds', $bounds);
    }
    elseif ($list === 'de') {
      $config->set('bounds', [
        'west' => 5.8663,
        'south' => 47.2701,
        'east' => 15.0419,
        'north' => 55.992,
      ]);
    }
    elseif ($list === 'at') {
      $config->set('bounds', [
        'west' => 9.5307,
        'south' => 46.3723,
        'east' => 17.1608,
        'north' => 49.0205,
      ]);
    }
    elseif ($list === 'ch') {
      $config->set('bounds', [
        'west' => 5.9559,
        'south' => 45.818,
        'east' => 10.4921,
        'north' => 47.8084,
      ]);
    }
    elseif ($list === 'eu') {
      $config->set('bounds', [
        'west' => -50.1,
        'south' => 34.9,
        'east' => 68.9,
        'north' => 81.9,
      ]);
    }
    elseif ($list === 'ww') {
      $config->set('bounds', [
        'west' => -180.0,
        'south' => -85.0,
        'east' => 180.0,
        'north' => 85.0,
      ]);
    }

    if ($form_state->getValue('devmode')) {
      $this->state->set('openculturas_openstreetmap.settings.devmode', TRUE);
      $this->state->set('openculturas_openstreetmap.settings.osmid', $form_state->getValue('osmid'));
    }
    else {
      $keys = ['openculturas_openstreetmap.settings.devmode', 'openculturas_openstreetmap.settings.osmid'];
      $this->state->deleteMultiple($keys);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
