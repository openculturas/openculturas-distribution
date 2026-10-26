<?php

declare(strict_types=1);

namespace Drupal\openculturas_responsive_images\Drush\Commands;

use Drupal\openculturas_responsive_images\CropMigrator;
use Drush\Commands\AutowireTrait;
use Drush\Style\DrushStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migrates image crops onto the oc_* crop types without the settings form.
 *
 * Each crop type is migrated onto the single oc_* crop type with the same
 * aspect ratio, the default the settings form suggests. Crop types without
 * such a match are skipped, since a different ratio trims crops and needs an
 * editor's decision in the settings form. Running it again only migrates new
 * crops.
 */
#[AsCommand(
  name: self::NAME,
  description: 'Migrates existing image crops onto the responsive image crop types with the same aspect ratio.',
  aliases: ['oc-ri-migrate-crops'],
)]
final class MigrateCropsCommand extends Command {

  use AutowireTrait;

  public const string NAME = 'openculturas-responsive-images:migrate-crops';

  public function __construct(
    private readonly CropMigrator $cropMigrator,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function configure(): void {
    $this->addUsage(self::NAME);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new DrushStyle($input, $output);
    $sources = $this->cropMigrator->migratableSourceTypes();
    if ($sources === []) {
      $io->success('There are no image crops to migrate.');
      return self::SUCCESS;
    }

    foreach ($sources as $sourceType => $cropType) {
      $targetType = $this->cropMigrator->suggestedTarget($cropType);
      if ($targetType === '') {
        $io->note(sprintf('Skipped %s: no responsive image crop type has the same aspect ratio.', $sourceType));
        continue;
      }

      $duplicated = 0;
      $chunks = array_chunk($this->cropMigrator->cropIds($sourceType, $targetType), 50) ?: [[]];
      foreach ($chunks as $cropIds) {
        $duplicated += $this->cropMigrator->migrate($sourceType, $cropIds, $targetType);
      }

      $io->success(sprintf('Migrated %d crops from %s to %s.', $duplicated, $sourceType, $targetType));
    }

    return self::SUCCESS;
  }

}
