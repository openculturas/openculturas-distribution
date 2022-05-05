<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\default_content\Normalizer\ContentEntityNormalizer;
use Drupal\default_content\Normalizer\ContentEntityNormalizerInterface;

/**
 * Disables pathauto on exported/imported path aliases.
 */
class Normalizer implements ContentEntityNormalizerInterface {

  /**
   * @var \Drupal\default_content\Normalizer\ContentEntityNormalizer
   */
  private $inner;

  public function __construct(ContentEntityNormalizer $entityNormalizer) {
    $this->inner = $entityNormalizer;
  }

  public function normalize(ContentEntityInterface $entity) {
    $data = $this->inner->normalize($entity);
    $path = $entity->path;
    if(!$entity->isNew() && $path) {
      foreach($path as $item) {
        if(!$item->pathauto && $item->pid) {
          $value = $item->getValue();
          unset($value['pid']);
          $data['default']['path'][] = $value + ['pathauto' => 0];
        }
      }
    }

    return $data;
  }

  public function denormalize(array $data) {
    return $this->inner->denormalize($data);
  }

  public function __call($method, $args) {
    return call_user_func_array([$this->inner, $method], $args);
  }
}
