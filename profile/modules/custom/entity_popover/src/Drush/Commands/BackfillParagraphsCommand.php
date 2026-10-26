<?php

declare(strict_types=1);

namespace Drupal\entity_popover\Drush\Commands;

use Drupal\entity_popover\ParagraphBackfiller;
use Drush\Commands\AutowireTrait;
use Drush\Style\DrushStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sets empty popover toggles on paragraphs without the settings form.
 *
 * Covers every paragraph bundle with a popover field, like selecting all of
 * them in the settings form. Toggles that already have a value are kept, so
 * running it again only updates paragraphs that are still empty.
 */
#[AsCommand(
  name: self::NAME,
  description: "Sets empty popover toggles on paragraphs to their field's default value.",
  aliases: ['ep-backfill'],
)]
final class BackfillParagraphsCommand extends Command {

  use AutowireTrait;

  public const string NAME = 'entity-popover:backfill-paragraphs';

  public function __construct(
    private readonly ParagraphBackfiller $paragraphBackfiller,
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
    $io->success(sprintf("Updated %d paragraphs to their popover field's default value.", $this->paragraphBackfiller->backfillAll()));
    return self::SUCCESS;
  }

}
