<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for the OpenCulturas custom configuration.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openculturas_custom_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['openculturas_custom.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $classMap = $this->config('openculturas_custom.settings')
      ->get('allowed_classes');
    $classes = [];
    foreach ($classMap as $class => $label) {
      $classes[] = sprintf("%s|%s", $class, $label);
    }

    $form['allowed_classes'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => $this->t('Allowed classes'),
      '#description' => $this->t('One class|label pair per line, use only letters and underscores (no dashes or spaces) in label.'),
      '#default_value' => implode("\r\n", $classes),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    try {
      $this->explodeClasses($form_state->getValue('allowed_classes'));
    }
    catch (InvalidFormatException $invalidFormatException) {
      $form_state->setErrorByName('allowed_classes', $invalidFormatException->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $classMap = $this->explodeClasses($form_state->getValue('allowed_classes'));
    $this->config('openculturas_custom.settings')
      ->set('allowed_classes', $classMap)
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Explodes the classes string to array.
   *
   * @param string $classesText
   *   Classes as entered in the form field.
   *
   * @return array
   *   key - value array with classes.
   *
   * @throws \Drupal\openculturas_custom\Form\InvalidFormatException
   */
  protected function explodeClasses(string $classesText): array {
    $classes = explode("\r\n", $classesText);
    $classMap = [];
    foreach ($classes as $class) {
      $matches = [];
      $class = trim($class);
      // Ignore empty lines.
      if ($class !== '') {
        $matched = preg_match("/(\w+)\s*\|\s*(\w+)/", $class, $matches);
        if (!$matched) {
          throw new InvalidFormatException(sprintf('The entered line "%s" has a invalid format', $class));
        }

        $classMap[$matches[1]] = $matches[2];
      }
    }

    return $classMap;
  }

}
