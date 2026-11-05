<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Drush\Commands;

use Drupal\openculturas_custom\PeopleParagraphUnsharer;
use Drush\Commands\AutowireTrait;
use Drush\Style\DrushStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Gives every node its own copy of a shared "people" paragraph.
 *
 * @see \Drupal\openculturas_custom\PeopleParagraphUnsharer
 */
#[AsCommand(
  name: self::NAME,
  description: 'Gives every node its own copy of a people paragraph that it shares with another node.',
)]
final class RepairSharedPeopleParagraphsCommand extends Command {

  use AutowireTrait;

  public const string NAME = 'openculturas:repair-shared-people-paragraphs';

  public function __construct(
    private readonly PeopleParagraphUnsharer $peopleParagraphUnsharer,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function configure(): void {
    $this->addOption('dry-run', NULL, InputOption::VALUE_NONE, 'Only list the affected nodes.');
    $this->addUsage(self::NAME . ' --dry-run');
    $this->addUsage(self::NAME);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new DrushStyle($input, $output);
    $references = $this->peopleParagraphUnsharer->sharedReferences();
    if ($references === []) {
      $io->success('No node shares a people paragraph with another node.');
      return self::SUCCESS;
    }

    if ($input->getOption('dry-run') === TRUE) {
      $rows = [];
      foreach ($references as $nodeId => $owners) {
        $paragraphs = [];
        foreach ($owners as $targetId => $owner) {
          $paragraphs[] = sprintf('%d (node %d)', $targetId, $owner);
        }

        $rows[] = [$nodeId, implode(', ', $paragraphs)];
      }

      $io->table(['Node', 'Paragraphs to duplicate (owner)'], $rows);
      $io->note(sprintf('%d nodes share people paragraphs. Nothing was changed.', count($references)));
      return self::SUCCESS;
    }

    $duplicates = 0;
    $io->progressStart(count($references));
    foreach ($references as $nodeId => $owners) {
      $duplicates += $this->peopleParagraphUnsharer->unshareNode($nodeId, array_keys($owners));
      $io->progressAdvance();
    }

    $io->progressFinish();
    $io->success(sprintf('Created %d paragraph duplicates for %d nodes.', $duplicates, count($references)));
    return self::SUCCESS;
  }

}
