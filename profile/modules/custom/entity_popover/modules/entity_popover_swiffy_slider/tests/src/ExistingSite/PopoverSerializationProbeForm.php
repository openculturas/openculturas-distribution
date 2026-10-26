<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_popover_swiffy_slider\ExistingSite;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The form the serialization probe builds.
 *
 * A named class, not an anonymous one: the form state holding the form
 * object goes into the form cache itself, and an anonymous class cannot be
 * serialized.
 */
final class PopoverSerializationProbeForm extends FormBase {

  /**
   * The form array to build, handed over from the test.
   *
   * @var array<mixed>
   */
  public static array $formArray = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return PopoverFormSerializationTest::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState): array {
    return self::$formArray;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {}

}
