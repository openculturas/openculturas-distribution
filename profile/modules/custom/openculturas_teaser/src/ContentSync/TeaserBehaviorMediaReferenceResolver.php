<?php

declare(strict_types=1);

namespace Drupal\openculturas_teaser\ContentSync;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\MediaInterface;
use function is_array;
use function is_numeric;
use function is_string;

/**
 * Resolves the media reference inside teaser paragraph behavior settings.
 *
 * The teaser paragraph behaviors (link_teaser, node_teaser, term_teaser,
 * see \Drupal\openculturas_teaser\Plugin\paragraphs\Behavior\TeaserBehaviorBase)
 * store their "media" setting as a plain entity id inside the paragraph's
 * behavior_settings field, which is a serialized string_long value rather
 * than a real entity_reference field. default_content's normalizer only
 * resolves entity ids to uuids for real entity_reference typed data
 * properties, so the raw id would otherwise round-trip unchanged through
 * export/import, pointing at the wrong (or a nonexistent) media entity on
 * a fresh install. This resolver rewrites that id to a uuid on export and
 * back to an id on import, without changing the runtime storage format.
 */
final readonly class TeaserBehaviorMediaReferenceResolver {

  /**
   * Paragraph behavior plugin ids that store a media reference.
   */
  private const array BEHAVIOR_PLUGIN_IDS = ['link_teaser', 'node_teaser', 'term_teaser'];

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private EntityRepositoryInterface $entityRepository,
  ) {
  }

  /**
   * Rewrites teaser behavior media ids to uuids in normalized export data.
   *
   * Also registers the media entity as a dependency of the exported root
   * entity, so the importer's topological sort saves it first.
   *
   * @param array $normalized
   *   The normalized entity data, as returned by
   *   \Drupal\default_content\Normalizer\ContentEntityNormalizerInterface::normalize().
   */
  public function convertMediaIdsToUuids(array &$normalized): void {
    $this->walkBehaviorSettings($normalized, function (array &$settings) use (&$normalized): void {
      foreach (self::BEHAVIOR_PLUGIN_IDS as $behaviorPluginId) {
        $mediaId = $settings[$behaviorPluginId]['media'] ?? NULL;
        if (!is_numeric($mediaId)) {
          continue;
        }

        $media = $this->entityTypeManager->getStorage('media')->load((int) $mediaId);
        if (!$media instanceof MediaInterface) {
          continue;
        }

        $settings[$behaviorPluginId]['media'] = $media->uuid();
        $normalized['_meta']['depends'][$media->uuid()] = 'media';
      }
    });
  }

  /**
   * Rewrites teaser behavior media uuids back to ids for import.
   *
   * @param array $data
   *   The raw decoded default_content data, before being passed to
   *   \Drupal\default_content\Normalizer\ContentEntityNormalizerInterface::denormalize().
   */
  public function convertMediaUuidsToIds(array &$data): void {
    $this->walkBehaviorSettings($data, function (array &$settings): void {
      foreach (self::BEHAVIOR_PLUGIN_IDS as $behaviorPluginId) {
        $mediaUuid = $settings[$behaviorPluginId]['media'] ?? NULL;
        if (!is_string($mediaUuid) || is_numeric($mediaUuid)) {
          continue;
        }

        $media = $this->entityRepository->loadEntityByUuid('media', $mediaUuid);
        if ($media instanceof MediaInterface) {
          $settings[$behaviorPluginId]['media'] = (string) $media->id();
        }
      }
    });
  }

  /**
   * Recursively finds every behavior_settings value array and invokes it.
   *
   * @param array $data
   *   The (possibly nested) default_content data to walk.
   * @param callable(array): void $callback
   *   Invoked once per behavior_settings value array, i.e. the array keyed
   *   by paragraph behavior plugin id.
   */
  private function walkBehaviorSettings(array &$data, callable $callback): void {
    foreach ($data as $key => &$value) {
      if (!is_array($value)) {
        continue;
      }

      if ($key === 'behavior_settings') {
        foreach ($value as &$item) {
          if (isset($item['value']) && is_array($item['value'])) {
            $callback($item['value']);
          }
        }

        unset($item);
        continue;
      }

      $this->walkBehaviorSettings($value, $callback);
    }

    unset($value);
  }

}
