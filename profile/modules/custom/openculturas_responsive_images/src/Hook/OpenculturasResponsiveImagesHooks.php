<?php

declare(strict_types=1);

namespace Drupal\openculturas_responsive_images\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;

/**
 * Theming and preprocessing hook implementations for openculturas_responsive_images.
 */
class OpenculturasResponsiveImagesHooks {

  /**
   * Implements hook_preprocess_HOOK() for responsive-image.html.twig.
   *
   * The 'sizes' attribute may only contain 'auto' on images that are also
   * lazy-loaded; a responsive image style's 'sizes' mapping can start with
   * 'auto' so the browser sizes the image intrinsically once it is
   * lazy-loaded, but the same responsive image style can also end up on an
   * eager-loaded field (for example a hero image above the fold), where
   * 'auto' would make the markup invalid.
   *
   * @see https://html.spec.whatwg.org/multipage/images.html#sizes-attributes
   */
  #[Hook('preprocess_responsive_image')]
  public function preprocessResponsiveImage(array &$variables): void {
    if (($variables['img_element']['#attributes']['loading'] ?? NULL) === 'lazy') {
      return;
    }

    foreach ($variables['sources'] ?? [] as $sourceAttributes) {
      if (!$sourceAttributes instanceof Attribute) {
        continue;
      }

      if (!$sourceAttributes->hasAttribute('sizes')) {
        continue;
      }

      $this->setOrRemoveSizes($sourceAttributes, (string) $sourceAttributes['sizes']);
    }

    if (isset($variables['img_element']['#attributes']['sizes'])) {
      $sizes = $this->removeAutoFromSizes((string) $variables['img_element']['#attributes']['sizes']);
      if ($sizes === '') {
        unset($variables['img_element']['#attributes']['sizes']);
      }
      else {
        $variables['img_element']['#attributes']['sizes'] = $sizes;
      }
    }
  }

  /**
   * Updates or removes the 'sizes' attribute on a source Attribute object.
   */
  private function setOrRemoveSizes(Attribute $attributes, string $sizes): void {
    $sizes = $this->removeAutoFromSizes($sizes);
    if ($sizes === '') {
      $attributes->removeAttribute('sizes');
    }
    else {
      $attributes->setAttribute('sizes', $sizes);
    }
  }

  /**
   * Removes the 'auto' entry from a 'sizes' attribute value.
   */
  private function removeAutoFromSizes(string $sizes): string {
    $values = array_filter(
      array_map(trim(...), explode(',', $sizes)),
      static fn (string $value): bool => $value !== 'auto',
    );
    return implode(', ', $values);
  }

}
