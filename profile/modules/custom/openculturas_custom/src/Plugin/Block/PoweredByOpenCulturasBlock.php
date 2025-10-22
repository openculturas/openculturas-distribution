<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Powered by OpenCulturas' block.
 */
#[Block(
  id: 'powered_by_openculturas',
  admin_label:  new TranslatableMarkup('Powered by')
)]
final class PoweredByOpenCulturasBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return ['#markup' => '<span>' . $this->t('<a href=":poweredby_oc">OpenCulturas</a> based on <a href=":poweredby">Drupal</a>', [':poweredby_oc' => 'https://www.openculturas.org', ':poweredby' => 'https://www.drupal.org']) . '</span>'];
  }

}
