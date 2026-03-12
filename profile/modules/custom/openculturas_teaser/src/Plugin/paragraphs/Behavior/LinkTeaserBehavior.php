<?php

declare(strict_types=1);

namespace Drupal\openculturas_teaser\Plugin\paragraphs\Behavior;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\Attribute\ParagraphsBehavior;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;

#[ParagraphsBehavior(
  id: 'link_teaser',
  label: new TranslatableMarkup('Teaser for external links'),
  description: new TranslatableMarkup('Generate title, subtitle + description'),
  weight: 2
)]
class LinkTeaserBehavior extends TeaserBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode): void {
    $settings = $paragraph->getAllBehaviorSettings()[$this->getPluginId()] ?? [];
    $originalField = $build['field_url_single_value'][0];
    $cacheableMetadata = CacheableMetadata::createFromRenderArray($build['field_url_single_value'][0]);
    $url = $originalField['#url'];
    $teaser = [
      '#theme' => 'teaser',
    ];
    $teaser['#title'] = $originalField['#title'];
    if (!empty($settings['subtitle'])) {
      $teaser['#subtitle'] = $settings['subtitle'];
    }

    if (!empty($settings['body'])) {
      $teaser['#description'] = $settings['body'];
    }

    if (!empty($settings['media'])) {
      $mid = $settings['media'];
      $media = $this->entityTypeManager->getStorage('media')->load($mid);
      if ($media instanceof MediaInterface) {
        $cacheableMetadata->addCacheableDependency($media);
        $teaser['#media'] = $this->entityTypeManager->getViewBuilder('media')
          ->view($media, 'teaser_image_big');
      }
    }

    $teaser['#url'] = $url->toString();
    $teaser['#attributes'] = new Attribute(['class' => ['teaser-external']]);
    $build['field_url_single_value'][0] = $teaser;
    $cacheableMetadata->addCacheableDependency($paragraph);
    $cacheableMetadata->applyTo($build['field_url_single_value'][0]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    parent::buildBehaviorForm($paragraph, $form, $form_state);
    // Pay attention to the hash(#) !
    unset($form['title']);
    $form['#title'] = $this->t('Additional teaser content');
    // Hint: Do not return the form, or we get the ugly paragraphs-behavior tabs, which we do not want. See internal issue 863.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsTypeInterface $paragraphs_type): bool {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager */
    $fieldManager = \Drupal::service('entity_field.manager');
    $fd = $fieldManager->getFieldDefinitions('paragraph', (string) $paragraphs_type->id());
    $ef = $fieldManager->getBaseFieldDefinitions('paragraph');
    $fieldKeys = array_diff(array_keys($fd), array_keys($ef));
    foreach ($fieldKeys as $item) {
      $fieldDefinition = $fd[$item];
      if ($fieldDefinition->getType() === 'link') {
        return TRUE;
      }
    }

    return FALSE;
  }

}
