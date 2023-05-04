<?php

declare(strict_types = 1);

namespace Drupal\openculturas_calendar_widget\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function sprintf;
use function t;

final class EmbedCodeWidget extends FormBase {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): EmbedCodeWidget {
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    $instance->setConfigFactory($container->get('config.factory'));
    $instance->setStringTranslation($container->get('string_translation'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openculturas_calendar_embed_code_widget';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Url $url = NULL): array {
    $config = $this->configFactory()->getEditable('openculturas_calendar_widget.settings');
    $iframe_src = $url instanceof Url ? $url->toString() : '';
    $form['container'] = [
      '#type' => 'details',
      '#title' => $this->t('Embed code'),
      '#description' => $this->t('To embed the calendar as filtered above, copy and paste this code into a website.'),
    ];
    $form['container']['status'] = [
      '#markup' => '<div id="openculturas-calendar-widget-messages"></div>',
    ];
    if (!$config->get('limit_access')) {
      self::embedCodeWidgetElement($form['container'], $iframe_src);
    }
    if ($this->currentUser()->hasPermission('administer openculturas_calendar_widget configuration')) {
      $list_items = [];
      if (!$config->get('limit_access')) {
        $list_items[] = $this->t('To limit the access to embed the calendar use the %link and add at least one website that is allowed to embed the calendar and add a corresponding url.', [
          '%link' => Link::createFromRoute($this->t('OpenCulturas calendar widget settings'), 'openculturas_calendar_widget.settings')->toString(),
        ]);
        $list_items[] = $this->t('To add more calendars configure the filter, copy the url and paste it as url in the %link.', [
          '%link' => Link::createFromRoute($this->t('OpenCulturas calendar widget settings'),
            'openculturas_calendar_widget.settings')->toString(),
        ]);
      }
      else {
        $list_items[] = $this->t('Configure the filter, copy the url and paste it as url in the %link.', [
          '%link' => Link::createFromRoute($this->t('OpenCulturas calendar widget settings'),
            'openculturas_calendar_widget.settings')->toString(),
        ]);
      }

      $form['container']['help'] = [
        '#theme' => 'item_list',
        '#items' => $list_items,
        '#title' => $this->t('OpenCulturas calender widget configuration'),
      ];
      $code_html_id = Html::getUniqueId('openculturas-calendar-widget-iframe-src');
      $form['container']['iframe_src'] = [
        '#type' => 'textfield',
        '#title' => $this->t('iFrame source url'),
        '#id' => $code_html_id,
        '#suffix' => Markup::create(sprintf('<button data-source-id="%s" class="button openculturas-calendar-widget-copy-button">%s</button>', $code_html_id, $this->t('Copy text'))),
        '#value' => $iframe_src,
      ];
      $this->renderer->addCacheableDependency($form, $config);
    }

    $form['#attached']['library'][] = 'openculturas_calendar_widget/widget';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

  public static function embedCodeWidgetElement(array &$element, ?string $iframe_src): void {
    $code_html_id = Html::getUniqueId('openculturas-calendar-widget-embed-code');
    $element['code'] = [
      '#title' => t('Embed code'),
      '#title_display' => 'invisible',
      '#type' => 'textarea',
      '#rows' => 2,
      '#cols' => 2,
      '#id' => $code_html_id,
      '#suffix' => Markup::create(sprintf('<button data-source-id="%s" class="button openculturas-calendar-widget-copy-button">%s</button>', $code_html_id, t('Copy text'))),
      '#value' => '',
    ];
    if (!empty($iframe_src)) {
      $element['code']['#value'] = <<<EOF
<iframe id="calender-upcoming-dates" style="border: none" title="Upcoming dates" width="560" height="800" src="$iframe_src" />
EOF;
    }
  }

}
