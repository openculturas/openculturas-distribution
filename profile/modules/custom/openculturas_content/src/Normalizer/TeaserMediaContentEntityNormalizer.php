<?php

declare(strict_types=1);

namespace Drupal\openculturas_content\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\default_content\Normalizer\ContentEntityNormalizerInterface;
use Drupal\openculturas_teaser\ContentSync\TeaserBehaviorMediaReferenceResolver;

/**
 * Decorates the default_content normalizer to fix up teaser media references.
 *
 * @see \Drupal\openculturas_teaser\ContentSync\TeaserBehaviorMediaReferenceResolver
 */
final readonly class TeaserMediaContentEntityNormalizer implements ContentEntityNormalizerInterface {

  public function __construct(
    private ContentEntityNormalizerInterface $inner,
    private TeaserBehaviorMediaReferenceResolver $teaserBehaviorMediaReferenceResolver,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function normalize(ContentEntityInterface $entity) {
    $normalized = $this->inner->normalize($entity);
    $this->teaserBehaviorMediaReferenceResolver->convertMediaIdsToUuids($normalized);
    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize(array $data) {
    $this->teaserBehaviorMediaReferenceResolver->convertMediaUuidsToIds($data);
    return $this->inner->denormalize($data);
  }

}
