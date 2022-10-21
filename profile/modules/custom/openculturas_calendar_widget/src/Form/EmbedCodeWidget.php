<?php

declare(strict_types=1);

namespace Drupal\openculturas_calendar_widget\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use function sprintf;

final class EmbedCodeWidget extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openculturas_calendar_embed_code_widget';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Url $url = NULL) {
    $config = $this->configFactory()->getEditable('openculturas_calendar_widget.settings');
    $iframe_src = $url ? $url->toString() : '';
    $form['container'] = [
      '#type' => 'details',
      '#title' => t('Embed code'),
    ];

    if ($config->get('limit_access')) {
      $form['container']['#description'] = $this->t('Configure the filter and click on <em>Add host</em>');
      $form['container']['addhost'] = [
        '#type' => 'link',
        '#title' => $this->t('Add host'),
        '#url' => Url::fromRoute('openculturas_calendar_widget.settings', ['iframe_src' => $iframe_src]),
        '#attributes' => ['class' => 'button']
      ];
    }
    else {
      $form['container']['status'] = [
        '#markup' => '<div id="openculturas-calendar-widget-status"></div>',
      ];
      static::embedCodeWidgetElement($form['container'], $iframe_src);
      $form['#attached']['library'][] = 'openculturas_calendar_widget/widget';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

  public static function embedCodeWidgetElement(array &$element, ?string $iframe_src) {
    $code_html_id = Html::getUniqueId("oc-embed-code");
    $element['code'] = [
      '#title' => t('Embed code'),
      '#title_display' => 'invisible',
      '#type' => 'textarea',
      '#resizable' => FALSE,
      '#rows' => 2,
      '#cols' => 2,
      '#id' => $code_html_id,
      '#suffix' => Markup::create(sprintf('<button data-source-id="%s" class="button openculturas-calendar-widget-copy-button">%s</button>', $code_html_id, t('Copy text'))),
      '#value' => ''
    ];
    if (!empty($iframe_src)) {
      $element['code']['#value'] = <<<EOF
<iframe id="calender-upcoming-dates" style="border: none" title="Upcoming dates" width="560" height="800" src="$iframe_src" />
EOF;
    }
  }

}
