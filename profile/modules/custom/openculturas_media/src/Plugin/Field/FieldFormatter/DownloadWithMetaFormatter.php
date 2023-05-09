<?php

declare(strict_types = 1);

namespace Drupal\openculturas_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\media\MediaInterface;
use Drupal\media_entity_download\Plugin\Field\FieldFormatter\DownloadLinkFieldFormatter;
use Drupal\openculturas_media\Service\ReadableMime;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'media_entity_download_download_link' formatter.
 *
 * @FieldFormatter(
 *   id = "media_entity_download_meta",
 *   label = @Translation("Download link with meta data"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
final class DownloadWithMetaFormatter extends DownloadLinkFieldFormatter {

  /**
   * @var \Drupal\openculturas_media\Service\ReadableMime
   */
  protected ReadableMime $mimeFormatter;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->mimeFormatter = $container->get('openculturas_media.readable_mime');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {

    $build = [];
    $elements = parent::viewElements($items, $langcode);
    $parentAdapter = $items->getParent();
    if ($parentAdapter instanceof EntityAdapter) {
      /** @var \Drupal\media\MediaInterface|null $parent */
      $parent = $parentAdapter->getValue();
      if (!$parent instanceof MediaInterface) {
        return $elements;
      }
      $predefined = $this->languageManager->getStandardLanguageList();
      foreach ($items as $delta => $item) {
        $attribute = new Attribute();
        $build[$delta] = [
          '#theme' => 'media_download',
          '#link' => $elements[$delta],
        ];
        if ($parent->hasField('field_filesize') && !$parent->get('field_filesize')->isEmpty()) {
          $filesize = $parent->get('field_filesize')->getString();
          $build[$delta]['#filesize'] = [
            '#markup' => format_size($filesize),
          ];
        }
        if ($parent->hasField('field_mimetype') && !$parent->get('field_mimetype')->isEmpty()) {
          $mimetype = $parent->get('field_mimetype')->getString();
          $readable = $this->mimeFormatter
            ->getReadableMime($mimetype);

          $build[$delta]['#mimetype'] = [
            '#markup' => $readable,
          ];
          $attribute->addClass('file--' . file_icon_class($mimetype));
        }
        if ($parent->hasField('field_inlanguage') && !$parent->get('field_inlanguage')->isEmpty()) {
          $langcode = $parent->get('field_inlanguage')->getString();
          if (isset($predefined[$langcode])) {
            $build[$delta]['#inlanguage'] = $this->t($predefined[$langcode][0]); // phpcs:ignore
          }
        }
        $build[$delta]['#attributes'] = $attribute;
      }
    }
    return $build;
  }

}
