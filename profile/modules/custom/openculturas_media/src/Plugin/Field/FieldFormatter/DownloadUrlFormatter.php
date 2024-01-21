<?php

declare(strict_types = 1);

namespace Drupal\openculturas_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\FileStorageInterface;
use Drupal\media_entity_download\Plugin\Field\FieldFormatter\DownloadLinkFieldFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Plugin implementation of the 'media_entity_download_download_link' formatter.
 *
 * @FieldFormatter(
 *   id = "media_entity_download_url",
 *   label = @Translation("Download Url"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
final class DownloadUrlFormatter extends DownloadLinkFieldFormatter {

  /**
   * @var \Drupal\file\FileStorageInterface
   *   The file storage.
   */
  protected FileStorageInterface $fileStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $downloadUrlFormatter = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $downloadUrlFormatter->fileStorage = $container->get('entity_type.manager')->getStorage('file');
    return $downloadUrlFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $fieldItemList, $langcode): array {
    $elements = [];
    $settings = $this->getSettings();
    $parentAdapter = $fieldItemList->getParent();
    if ($parentAdapter instanceof EntityAdapter) {
      $parent = $parentAdapter->getValue()->id();

      foreach ($fieldItemList as $delta => $item) {

        $route_parameters = ['media' => $parent];
        $url_options = [];
        if ($delta > 0) {
          $route_parameters['query']['delta'] = $delta;
        }

        /** @var \Drupal\file\FileInterface|null $file */
        $file = $this->fileStorage->load($item->target_id);

        if (!$file instanceof FileInterface) {
          continue;
        }

        // Add download variant.
        $url_options['query'][$settings['disposition']] = NULL;
        if ($settings['disposition'] === ResponseHeaderBag::DISPOSITION_INLINE && !empty($settings['target'])) {
          // Link target only relevant for inline downloads (attachment
          // downloads will never navigate client locations)
          $url_options['attributes']['target'] = $settings['target'];
        }

        if (!empty($settings['rel'])) {
          $url_options['attributes']['rel'] = $settings['rel'];
        }

        $url = Url::fromRoute('media_entity_download.download', $route_parameters, $url_options);
        $elements[$delta] = [
          '#markup' => $url->toString(),
        ];
      }
    }

    return $elements;
  }

}
