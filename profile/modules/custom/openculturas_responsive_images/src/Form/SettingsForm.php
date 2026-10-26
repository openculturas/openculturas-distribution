<?php

declare(strict_types=1);

namespace Drupal\openculturas_responsive_images\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\crop\CropTypeInterface;
use Drupal\openculturas_responsive_images\CropMigrator;
use Drupal\openculturas_responsive_images\SharedConfigSwitcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Switches the image displays and migrates crops onto the oc_ crop types.
 */
final class SettingsForm extends FormBase {

  /**
   * The crop migrator.
   */
  protected CropMigrator $cropMigrator;

  /**
   * The shared config switcher.
   */
  protected SharedConfigSwitcher $sharedConfigSwitcher;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container): static {
    /** @var static $instance */
    $instance = parent::create($container);
    $instance->cropMigrator = $container->get(CropMigrator::class);
    $instance->sharedConfigSwitcher = $container->get(SharedConfigSwitcher::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openculturas_responsive_images_settings';
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['displays'] = $this->buildDisplaysElement();
    $form['crops'] = $this->buildCropMigrationElement();
    return $form;
  }

  /**
   * Builds the element that switches the image displays.
   *
   * @return array<string, mixed>
   *   The render array.
   */
  private function buildDisplaysElement(): array {
    $states = $this->sharedConfigSwitcher->componentStates();
    $deviations = $this->sharedConfigSwitcher->deviations();
    $element = [
      '#type' => 'details',
      '#title' => $this->t('Image displays'),
      '#open' => TRUE,
      'description' => [
        '#markup' => '<p>' . $this->describeDisplays($states, $deviations !== []) . '</p>',
      ],
    ];

    if ($deviations !== []) {
      $element['deviations'] = $this->buildDeviationsTable($deviations);
      $element['deviations_warning'] = [
        '#markup' => '<div class="messages messages--warning">' . $this->t('Overwritten settings are lost. A later revert sets the OpenCulturas standard values, not the previous own settings.') . '</div>',
      ];
    }

    $element['actions'] = ['#type' => 'actions'];
    if (array_intersect([SharedConfigSwitcher::STANDARD, SharedConfigSwitcher::DEVIATING], $states) !== []) {
      $element['actions']['switch'] = [
        '#type' => 'submit',
        '#value' => $this->t('Switch to responsive images'),
        '#submit' => ['::submitSwitchToResponsive'],
        '#limit_validation_errors' => [['deviations']],
      ];
    }

    if ($this->sharedConfigSwitcher->hasRevertibleValues()) {
      $element['actions']['revert'] = [
        '#type' => 'submit',
        '#value' => $this->t('Revert to fixed image styles'),
        '#submit' => ['::submitRevertToLegacy'],
        '#limit_validation_errors' => [],
      ];
    }

    return $element;
  }

  /**
   * Describes how far the image displays are switched.
   *
   * @param array<string, string> $states
   *   The component states, keyed by component key.
   * @param bool $hasDeviations
   *   Whether deviating components are listed below.
   */
  private function describeDisplays(array $states, bool $hasDeviations): TranslatableMarkup {
    $switchedCount = count(array_keys($states, SharedConfigSwitcher::SWITCHED, TRUE));
    if ($switchedCount > 0 && $switchedCount === count($states)) {
      return $this->t('Header images, teasers, profile pictures, galleries and content images use responsive images.');
    }

    if ($switchedCount === 0) {
      return $this->t('Header images, teasers, profile pictures, galleries and content images still use the fixed image styles. Migrate the existing image crops first, then switch.');
    }

    if ($hasDeviations) {
      return $this->t('Only some of the image displays use responsive images so far; components with own settings are listed below.');
    }

    return $this->t('Only some of the image displays use responsive images so far.');
  }

  /**
   * Builds the table of deviating components, each with an overwrite checkbox.
   *
   * The rows are keyed by component key. Form API keeps ":" and "." inside
   * the brackets of the element names, so the checked keys arrive unchanged
   * in the submitted values.
   *
   * @param array<string, array{key: string, current: string, standard: string}> $deviations
   *   The deviations, keyed by component key.
   *
   * @return array<string, mixed>
   *   The render array.
   */
  private function buildDeviationsTable(array $deviations): array {
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Component'),
        $this->t('Current setting'),
        $this->t('OpenCulturas standard'),
        $this->t('Overwrite'),
      ],
      '#tree' => TRUE,
    ];

    foreach ($deviations as $key => $deviation) {
      // The descriptions are config values such as "image: large", which have
      // no translation.
      $table[$key] = [
        'component' => ['#plain_text' => $key],
        'current' => ['#plain_text' => $deviation['current']],
        'standard' => ['#plain_text' => $deviation['standard']],
        'overwrite' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Overwrite %component', ['%component' => $key]),
          '#title_display' => 'invisible',
        ],
      ];
    }

    return $table;
  }

  /**
   * Builds the element that migrates crops onto the oc_ crop types.
   *
   * @return array<string, mixed>
   *   The render array.
   */
  private function buildCropMigrationElement(): array {
    $element = [
      '#type' => 'details',
      '#title' => $this->t('Image crops'),
      '#open' => TRUE,
    ];

    $sources = $this->cropMigrator->migratableSourceTypes();
    if ($sources === []) {
      $element['empty'] = [
        '#markup' => '<p>' . $this->t('There are no image crops to migrate.') . '</p>',
      ];
      return $element;
    }

    $element['description'] = [
      '#markup' => '<p>' . $this->t('The following image crop types were found on this site. For each, optionally choose a new responsive-image crop type to migrate its existing crops onto. For the same aspect ratio the crop coordinates are kept. For a different aspect ratio the crop is trimmed around its center to fit, so the image is never distorted. Existing crops of a target type are not overwritten, so running a migration again does not change files that were already migrated.') . '</p>',
    ];

    $element['mapping'] = [
      '#type' => 'table',
      '#header' => [$this->t('Existing crop type'), $this->t('Migrate to')],
      '#tree' => TRUE,
    ];

    foreach ($sources as $id => $cropType) {
      $element['mapping'][$id] = [
        'label' => [
          '#plain_text' => $cropType->label(),
        ],
        'target' => [
          '#type' => 'select',
          '#title' => $this->t('Migrate %label to', ['%label' => (string) $cropType->label()]),
          '#title_display' => 'invisible',
          '#options' => $this->buildTargetOptions($cropType),
          '#default_value' => $this->cropMigrator->suggestedTarget($cropType),
        ],
      ];
    }

    $element['actions'] = ['#type' => 'actions'];
    $element['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate selected crops'),
    ];

    return $element;
  }

  /**
   * Builds the target options of one row, grouped by aspect ratio match.
   *
   * @return array<string, mixed>
   *   The select options: the empty choice, then two optgroups.
   */
  private function buildTargetOptions(CropTypeInterface $source): array {
    $sameRatio = [];
    $differentRatio = [];
    foreach ($this->cropMigrator->targetTypes() as $id => $target) {
      if ($target->getAspectRatio() === $source->getAspectRatio()) {
        $sameRatio[$id] = $target->label();
      }
      else {
        $differentRatio[$id] = $target->label();
      }
    }

    $options = ['' => $this->t('- Do not migrate -')];
    if ($sameRatio !== []) {
      $options[(string) $this->t('Same aspect ratio')] = $sameRatio;
    }

    if ($differentRatio !== []) {
      $options[(string) $this->t('Different aspect ratio (crop is trimmed to fit)')] = $differentRatio;
    }

    return $options;
  }

  /**
   * Submit handler: switches the image displays to responsive images.
   *
   * Deviating components are only overwritten when their checkbox is checked.
   *
   * @param array<string, mixed> $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitSwitchToResponsive(array &$form, FormStateInterface $form_state): void {
    $this->sharedConfigSwitcher->switchToResponsive($this->approvedKeys($form_state));
    $states = $this->sharedConfigSwitcher->componentStates();
    $allSwitched = array_diff($states, [SharedConfigSwitcher::SWITCHED]) === [];
    $this->messenger()->addStatus($allSwitched
      ? $this->t('The image displays use responsive images now.')
      : $this->t('The image displays without own settings use responsive images now.'));

    $remaining = count($this->sharedConfigSwitcher->deviations());
    if ($remaining > 0) {
      $this->messenger()->addWarning($this->formatPlural(
        $remaining,
        '1 component with own settings was left unchanged. It is listed below.',
        '@count components with own settings were left unchanged. They are listed below.'
      ));
    }
  }

  /**
   * Submit handler: reverts the image displays to the fixed image styles.
   *
   * @param array<string, mixed> $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitRevertToLegacy(array &$form, FormStateInterface $form_state): void {
    $this->sharedConfigSwitcher->revertToLegacy();
    $this->messenger()->addStatus($this->t('The image displays use the fixed image styles again.'));
  }

  /**
   * The keys of the deviating components whose overwrite box is checked.
   *
   * @return list<string>
   *   The component keys.
   */
  private function approvedKeys(FormStateInterface $form_state): array {
    $rows = $form_state->getValue('deviations');
    if (!is_array($rows)) {
      return [];
    }

    $approvedKeys = [];
    foreach ($rows as $key => $row) {
      if (is_array($row) && (bool) ($row['overwrite'] ?? FALSE)) {
        $approvedKeys[] = (string) $key;
      }
    }

    return $approvedKeys;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var array<string, array{target: string}> $mapping */
    $mapping = $form_state->getValue('mapping', []);
    $sources = $this->cropMigrator->migratableSourceTypes();
    $targets = $this->cropMigrator->targetTypes();
    $sameRatioOperations = [];
    $crossRatioOperations = [];
    foreach ($mapping as $sourceType => $row) {
      $targetType = $row['target'];
      if ($targetType === '') {
        continue;
      }

      $operation = [
        [self::class, 'batchMigrateCropType'],
        [$sourceType, $targetType],
      ];
      if (isset($sources[$sourceType], $targets[$targetType]) && $sources[$sourceType]->getAspectRatio() === $targets[$targetType]->getAspectRatio()) {
        $sameRatioOperations[] = $operation;
      }
      else {
        $crossRatioOperations[] = $operation;
      }
    }

    // Same-ratio pairs run first: migrate() skips files that already have a
    // crop of the target type, so a trimmed crop created by a cross-ratio pair
    // would block the exact crop of the same file.
    $operations = [...$sameRatioOperations, ...$crossRatioOperations];

    if ($operations === []) {
      $this->messenger()->addStatus($this->t('No crop types were selected for migration.'));
      return;
    }

    batch_set([
      'title' => $this->t('Migrating image crops'),
      'operations' => $operations,
      'finished' => [self::class, 'batchFinished'],
    ]);
  }

  /**
   * Batch operation callback: duplicates one crop type's crops onto a new type.
   *
   * The total is the number of crops that still lack a crop of the target
   * type, so crops migrated by an earlier run are not counted or loaded.
   *
   * @param string $sourceType
   *   The legacy crop type machine name to migrate from.
   * @param string $targetType
   *   The oc_ crop type machine name to migrate onto.
   * @param array<string, mixed> $context
   *   The batch context.
   */
  public static function batchMigrateCropType(string $sourceType, string $targetType, array &$context): void {
    $cropMigrator = \Drupal::service(CropMigrator::class);

    if (!isset($context['sandbox']['cropIds'])) {
      $context['sandbox']['cropIds'] = $cropMigrator->cropIds($sourceType, $targetType);
      $context['sandbox']['total'] = count($context['sandbox']['cropIds']);
      $context['sandbox']['duplicated'] = 0;
      $context['results']['duplicated'] ??= 0;

      if ($context['sandbox']['total'] === 0) {
        // migrate() records the mapping, so a source whose files all already
        // have a crop of the target type stops being listed as unmigrated.
        $cropMigrator->migrate($sourceType, [], $targetType);
        $context['finished'] = 1;
        return;
      }
    }

    /** @var list<int|string> $cropIds */
    $cropIds = array_splice($context['sandbox']['cropIds'], 0, 50);
    $duplicated = $cropMigrator->migrate($sourceType, $cropIds, $targetType);
    $context['sandbox']['duplicated'] += $duplicated;
    $context['results']['duplicated'] += $duplicated;

    $context['finished'] = 1 - (count($context['sandbox']['cropIds']) / $context['sandbox']['total']);
    $context['message'] = t('Migrating @source: @done of @total crops.', [
      '@source' => $sourceType,
      '@done' => $context['sandbox']['duplicated'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Whether the batch completed without a fatal error.
   * @param array<string, mixed> $results
   *   The accumulated batch results.
   * @param array<int, mixed> $operations
   *   The operations that were not completed.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural(
        $results['duplicated'] ?? 0,
        'Migrated 1 image crop.',
        'Migrated @count image crops.'
      ));
    }
    else {
      \Drupal::messenger()->addError(t('An error occurred while migrating image crops.'));
    }
  }

}
