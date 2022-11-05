<?php

declare(strict_types=1);

namespace Drupal\openculturas_calendar_widget\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function trim;

/**
 * Settings form for the OpenCulturas configuration.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container): SettingsForm {
    $instance = parent::create($container);
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openculturas_calendar_widget_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['openculturas_calendar_widget.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = TRUE;
    $form['limit_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit access'),
      '#default_value' => $this->config('openculturas_calendar_widget.settings')->get('limit_access'),
    ];

    $items = [];
    if ($form_state->hasTemporaryValue('host_list')) {
      $items = $form_state->getTemporaryValue('host_list');
    }
    elseif (!$form_state->getValues()) {
      $items = $this->config('openculturas_calendar_widget.settings')->get('host_list') ?? [];
    }
    if ((is_countable($items) ? count($items) : 0) === 0 || !is_array($items)) {
      $items = is_array($items) ? $items : [];
      $items[Crypt::randomBytesBase64()] = ['hostname' => '', 'iframe_src' => ''];
    }
    $form['host_list'] = [
      '#type' => 'details',
      '#title' => $this->t('Websites'),
      '#open' => count($items) < 10,
      '#states' => [
        'visible' => [
          ':input[name="limit_access"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['host_list']['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Website'),
        $this->t('Public access token'),
        $this->t('Url'),
        $this->t('Embed code'),
        $this->t('Css'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are currently no items.'),
      '#prefix' => '<div id="host_list">',
      '#suffix' => '</div>',
    ];
    foreach ($items as $token => $values) {
      $form['host_list']['items'][$token]['hostname'] = [
        '#title' => $this->t('Website'),
        '#title_display' => 'invisible',
        '#type' => 'url',
        '#size' => 3,
        '#default_value' => $values['hostname'],
        '#placeholder' => 'https://example.org',
        '#states' => [
          'required' => [
            ':input[name="limit_access"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['host_list']['items'][$token]['token'] = [
        '#plain_text' => $token,
      ];
      $form['host_list']['items'][$token]['iframe_src'] = [
        '#title' => $this->t('Url'),
        '#title_display' => 'invisible',
        '#type' => 'url',
        '#size' => 5,
        '#maxlength' => 500,
        '#default_value' => $values['iframe_src'],
        '#placeholder' => Url::fromRoute('openculturas_calendar_widget.embed')->setAbsolute()->toString(),
        '#states' => [
          'required' => [
            ':input[name="limit_access"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $iframe_src = $values['iframe_src'] ?? NULL;
      if (is_string($iframe_src)) {
        $url = Url::fromUri($iframe_src);
        $query = $url->getOption('query');
        $query['access_token'] = $token;
        $url->setOption('query', $query);
        $iframe_src = (string) $url->toString();
      }
      EmbedCodeWidget::embedCodeWidgetElement($form['host_list']['items'][$token], $iframe_src);
      $form['host_list']['items'][$token]['css'] = [
        '#title' => $this->t('Css'),
        '#title_display' => 'invisible',
        '#type' => 'textarea',
        '#rows' => 2,
        '#cols' => 2,
        '#default_value' => $values['css'] ?? '',
      ];
      $form['host_list']['items'][$token]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [[$this, 'removeRowSubmit']],
        '#name' => 'host_remove' . $token,
        '#ajax' => [
          'callback' => [self::class, 'ajaxRefreshCallback'],
          'wrapper' => 'host_list',
          'id' => $token,
        ],
      ];

      $form['host_list']['actions'] = [
        '#type' => 'actions',
      ];

      $form['host_list']['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add one more'),
        '#submit' => [[$this, 'addRowSubmit']],
        '#name' => 'add_host',
        '#ajax' => [
          'callback' => [self::class, 'ajaxRefreshCallback'],
          'wrapper' => 'host_list',
        ],
      ];
    }
    $form['header'] = [
      '#type' => 'text_format',
      '#format' => $this->config('openculturas_calendar_widget.settings')->get('header')['format'] ?? NULL,
      '#title' => $this->t('Header'),
      '#description' => $this->t('Will be displayed above of the calendar'),
      '#default_value' => $this->config('openculturas_calendar_widget.settings')->get('header')['value'] ?? NULL,
    ];
    $form['footer'] = [
      '#type' => 'text_format',
      '#format' => $this->config('openculturas_calendar_widget.settings')->get('footer')['format'] ?? NULL,
      '#title' => $this->t('Footer'),
      '#description' => $this->t('Will be displayed underneath of the calendar'),
      '#default_value' => $this->config('openculturas_calendar_widget.settings')->get('footer')['value'] ?? NULL,
    ];
    $form['#attached']['library'][] = 'openculturas_calendar_widget/widget';
    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback reloading the items table.
   */
  public static function ajaxRefreshCallback(array $form): array {
    return $form['host_list']['items'];
  }

  /**
   * Adds an empty item to the table.
   */
  public function addRowSubmit(array &$form, FormStateInterface $form_state): void {
    if ($form_state->hasTemporaryValue('host_list')) {
      $items = $form_state->getTemporaryValue('host_list');
      $items[Crypt::randomBytesBase64()] = '';
      $form_state->setTemporaryValue('host_list', $items);
      $form_state->setRebuild();
      $this->messenger->addWarning($this->t('You have unsaved changes.'));
    }
  }

  /**
   * Removes an item from the table.
   */
  public function removeRowSubmit(array &$form, FormStateInterface $form_state): void {
    if (!is_array($form_state->getTriggeringElement())) {
      parent::validateForm($form, $form_state);
      return;
    }
    if (!isset($form_state->getTriggeringElement()['#ajax']['id'])) {
      parent::validateForm($form, $form_state);
      return;
    }
    $id = $form_state->getTriggeringElement()['#ajax']['id'];
    if ($form_state->hasTemporaryValue('host_list')) {
      $items = $form_state->getTemporaryValue('host_list');
      unset($items[$id]);
      $form_state->setTemporaryValue('host_list', $items);
      $form_state->setRebuild();
      $this->messenger->addWarning($this->t('You have unsaved changes.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!is_array($form_state->getTriggeringElement())) {
      parent::validateForm($form, $form_state);
      return;
    }
    if (!isset($form_state->getTriggeringElement()['#name'])) {
      parent::validateForm($form, $form_state);
      return;
    }
    if ($form_state->getValue('limit_access')) {
      $host_list = $form_state->getValue(['host_list', 'items']) ?? [];
      $hostnames = [];
      if (!is_array($host_list)) {
        return;
      }
      foreach ($host_list as $token => $values) {
        $hostname = trim($values['hostname']);
        if (empty($hostname) && isset($form['host_list']['items'][$token]['hostname'])) {
          unset($host_list[$token]);
        }
        if ($form_state->getTriggeringElement()['#name'] === 'add_host' && !empty($hostname) && in_array($hostname, $hostnames, TRUE)) {
          $form_state->setError($form['host_list']['items'][$token]['hostname'], (string) $this->t('Duplicated hostname'));
          return;
        }
        $hostnames[] = $hostname;
      }
      $form_state->setTemporaryValue('host_list', $host_list);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('openculturas_calendar_widget.settings');
    if (is_array($form_state->getTriggeringElement()) && $form_state->getTriggeringElement()['#name'] !== 'op') {
      return;
    }

    if ($form_state->getValue('limit_access')) {
      $host_list = $form_state->getValue(['host_list', 'items']) ?? [];
      $host_list = is_array($host_list) ? $host_list : [];
      $host_list_new = [];

      foreach ($host_list as $token => $input_values) {
        if (empty($input_values)) {
          continue;
        }
        $values = [];
        $values['hostname'] = trim($input_values['hostname']);
        $values['iframe_src'] = trim($input_values['iframe_src']);
        $values['css'] = trim($input_values['css']);
        if (empty($values['hostname'])) {
          continue;
        }
        if (empty($values['iframe_src'])) {
          continue;
        }
        $host_list_new[$token] = $values;
      }
      $config->set('host_list', $host_list_new);
    }
    else {
      $config->set('host_list', []);
    }

    $config->set('footer', $form_state->getValue('footer'));
    $config->set('header', $form_state->getValue('header'));
    $config->set('limit_access', $form_state->getValue('limit_access'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
