<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use function implode;
use function is_array;
use function is_string;

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
    if (is_array($classMap)) {
      /**
       * @var string $class
       * @var string $label
       */
      foreach ($classMap as $class => $label) {
        $classes[] = sprintf("%s|%s", $class, $label);
      }
    }

    $form['allowed_classes'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => $this->t('Allowed classes'),
      '#description' => $this->t('One class|label pair per line, use only letters and underscores (no dashes or spaces) in class.'),
      '#default_value' => implode("\r\n", $classes),
    ];
    $form['mailsignature'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email signature'),
      '#description' => $this->t('This signature will be appended to all outgoing emails.'),
      '#default_value' => $this->config('openculturas_custom.settings')
        ->get('mailsignature'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $allowed_classes_value = $form_state->getValue('allowed_classes');
    if (is_string($allowed_classes_value)) {
      try {
        $this->explodeClasses($allowed_classes_value);
      }
      catch (InvalidFormatException $invalidFormatException) {
        $form_state->setErrorByName('allowed_classes', $invalidFormatException->getMessage());
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $allowed_classes_value = $form_state->getValue('allowed_classes');
    $classMap = [];
    try {
      $classMap = is_string($allowed_classes_value) ? $this->explodeClasses($allowed_classes_value) : [];
    }
    catch (InvalidFormatException) {
    }

    $mailsignature = $form_state->getValue('mailsignature');
    $this->config('openculturas_custom.settings')
      ->set('allowed_classes', $classMap)
      ->set('mailsignature', is_string($mailsignature) ? trim($mailsignature) : '')
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Explodes the classes string to array.
   *
   * @param string $classesText
   *   Classes as entered in the form field.
   *
   * @return string[]
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
