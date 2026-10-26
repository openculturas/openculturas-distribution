<?php

declare(strict_types=1);

namespace Drupal\entity_popover\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_popover\ParagraphBackfiller;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lets a site builder bulk-set the popover toggle's default value.
 *
 * See ParagraphBackfiller for the scope.
 */
final class SettingsForm extends FormBase {

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The paragraph backfiller.
   */
  protected ParagraphBackfiller $paragraphBackfiller;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container): static {
    /** @var static $instance */
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->paragraphBackfiller = $container->get(ParagraphBackfiller::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'entity_popover_settings';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (!$this->moduleHandler->moduleExists('paragraphs')) {
      $form['empty'] = [
        '#markup' => '<p>' . $this->t('Bulk-updating the popover toggle is only offered for paragraph bundles here - node and media entities can already be bulk-edited through a view. The Paragraphs module is not installed, so there is nothing to configure.') . '</p>',
      ];
      return $form;
    }

    $bundleFields = $this->paragraphBackfiller->discoverParagraphFields();

    if ($bundleFields === []) {
      $form['empty'] = [
        '#markup' => '<p>' . $this->t('No paragraph bundle currently has a popover field.') . '</p>',
      ];
      return $form;
    }

    $form['description'] = [
      '#markup' => '<p>' . $this->t("Each paragraph bundle below has a popover toggle field. Select the ones you want to bulk-set to the field's own default value - for example to update entities created before the field existed on that bundle, or through a process that bypasses the widget's own default. Leave a bundle unselected to keep deciding its popover state per entity.") . '</p>',
    ];

    $form['bundles'] = [
      '#type' => 'table',
      '#header' => [$this->t('Paragraph bundle'), $this->t('Field'), $this->t('Empty entities'), $this->t('Update to default')],
      '#tree' => TRUE,
    ];

    $bundlesByField = [];
    foreach ($bundleFields as $row) {
      $bundlesByField[$row['field']][] = $row['bundle'];
    }

    $emptyCounts = [];
    foreach ($bundlesByField as $fieldName => $bundles) {
      $emptyCounts[$fieldName] = $this->paragraphBackfiller->countEmptyByBundle($bundles, $fieldName);
    }

    foreach ($bundleFields as $rowKey => $row) {
      $emptyCount = $emptyCounts[$row['field']][$row['bundle']] ?? 0;

      $form['bundles'][$rowKey] = [
        'bundle' => ['#plain_text' => $row['bundle']],
        'field' => ['#plain_text' => $row['field']],
        'count' => ['#plain_text' => $emptyCount],
        'update' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Update %bundle', ['%bundle' => $row['bundle']]),
          '#title_display' => 'invisible',
          '#disabled' => $emptyCount === 0,
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update selected bundles'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $bundleFields = $this->paragraphBackfiller->discoverParagraphFields();
    /** @var array<string, array{bundle: string, field: string, update: int}> $selection */
    $selection = $form_state->getValue('bundles', []);

    $updated = 0;
    foreach ($selection as $rowKey => $row) {
      if (empty($row['update'])) {
        continue;
      }

      if (!isset($bundleFields[$rowKey])) {
        continue;
      }

      $updated += $this->paragraphBackfiller->backfillBundle($bundleFields[$rowKey]['bundle'], $bundleFields[$rowKey]['field']);
    }

    $this->messenger()->addStatus($this->formatPlural(
      $updated,
      "Updated 1 entity to its popover field's default value.",
      "Updated @count entities to their popover fields' default values."
    ));
  }

}
