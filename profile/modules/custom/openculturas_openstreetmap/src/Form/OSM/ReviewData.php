<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Form\OSM;

use Drupal\Core\Form\FormStateInterface;

final class ReviewData extends WizardFormTableBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'review_data';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    if (!$cached_values) {
      return $form;
    }

    $form = parent::buildForm($form, $form_state);
    $form['data']['#empty'] = $this->t('No data will be changed');
    $cached_data = $cached_values['data'] ?? [];
    if (!is_array($cached_data)) {
      return $form;
    }

    foreach ($cached_data as $key => $data_value) {
      if ($data_value['operation'] === SyncOperation::Pull->value) {
        $rendered = $this->renderer->renderRoot($form['data'][$key]['local']);
        $form['data'][$key]['local'] = [];
        $form['data'][$key]['local']['del'] = [
          '#type' => 'html_tag',
          '#tag' => 'del',
          '#value' => $rendered,
          '#access' => (string) $rendered !== '',
        ];
        $prefix = (string) $rendered !== '' ? '<br>' : '';
        $rendered = $this->renderer->renderRoot($form['data'][$key]['osm']);
        $form['data'][$key]['local']['ins'] = [
          '#prefix' => $prefix,
          '#type' => 'html_tag',
          '#tag' => 'ins',
          '#value' => $rendered,
          '#access' => (string) $rendered !== '',
        ];
      }
      elseif ($data_value['operation'] === SyncOperation::Push->value) {
        $rendered = $this->renderer->renderRoot($form['data'][$key]['osm']);
        $form['data'][$key]['osm'] = [];
        $form['data'][$key]['osm']['del'] = [
          '#type' => 'html_tag',
          '#tag' => 'del',
          '#value' => $rendered,
          '#access' => (string) $rendered !== '',
        ];
        $prefix = (string) $rendered !== '' ? '<br>' : '';
        $rendered = $this->renderer->renderRoot($form['data'][$key]['local']);
        $form['data'][$key]['osm']['ins'] = [
          '#prefix' => $prefix,
          '#type' => 'html_tag',
          '#tag' => 'ins',
          '#value' => $rendered,
          '#access' => (string) $rendered !== '',
        ];

      }

      unset($form['data'][$key]['operation']);
    }

    $form['#attached'] = [
      'library' => ['openculturas_openstreetmap/wizard'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

  }

}
