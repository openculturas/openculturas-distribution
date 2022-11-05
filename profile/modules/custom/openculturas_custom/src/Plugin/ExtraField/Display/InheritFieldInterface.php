<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

interface InheritFieldInterface {

  /**
   * Name of the field where the entity is stored from which we take the field.
   */
  public function getInheritEntityReferenceFieldName() : string;

  /**
   * Name of the field which we inherit.
   */
  public function getFieldNameInEntityReference() : string;

}
