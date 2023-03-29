<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\default_content\Normalizer\ContentEntityNormalizer;
use Drupal\default_content\Normalizer\ContentEntityNormalizerInterface;
use function is_callable;

/**
 * Disables pathauto on exported/imported path aliases.
 */
class Normalizer implements ContentEntityNormalizerInterface {

  /**
   * @var \Drupal\default_content\Normalizer\ContentEntityNormalizer
   */
  private ContentEntityNormalizer $inner;

  public function __construct(ContentEntityNormalizer $entityNormalizer) {
    $this->inner = $entityNormalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize(ContentEntityInterface $entity) {
    $data = $this->inner->normalize($entity);
    $path = $entity->path ?? NULL;
    if (!$entity->isNew() && $path) {
      foreach ($path as $item) {
        if (!$item->pathauto && $item->pid) {
          $value = $item->getValue();
          unset($value['pid']);
          $data['default']['path'][] = $value + ['pathauto' => 0];
        }
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize(array $data) {
    return $this->inner->denormalize($data);
  }

  /**
   * @param string $method
   *   The name of the called method.
   * @param mixed $args
   *   The arguments of the method.
   *
   * @return mixed
   *   The return value of method call.
   */
  public function __call($method, $args) {
    if (is_callable([$this->inner, $method])) {
      return ($this->inner->{$method})(...$args);
    }
    return NULL;
  }

}
