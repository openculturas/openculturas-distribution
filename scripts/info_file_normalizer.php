<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use Drupal\Core\Serialization\Yaml;
use Symfony\Component\Finder\Finder;

// <3 https://github.com/consolidation/comments/pull/2/files
$comments = new class
{
  protected bool $hasStored;
  protected array|false $headComments;
  protected array $accumulated;
  protected array $stored;
  protected array $endComments;

  public function __construct()
  {
    $this->hasStored = false;
    $this->headComments = false;
    $this->accumulated = [];
    $this->lineIds = [];
    $this->stored = [];
    $this->endComments = [];
  }

  /**
   * Collect all of the comments from a text file
   * (usually a yaml file).
   *
   * @param array $contentLines
   */
  public function collect(array $contentLines): void
  {
    $contentLines = $this->removeTrailingBlankLines($contentLines);

    // Put look through the rest of the lines and store the comments as needed
    foreach ($contentLines as $line) {
      if ($this->isBlank($line)) {
        $this->accumulateEmptyLine();
      } elseif ($this->isComment($line)) {
        $this->accumulate($line);
      } else {
        $this->storeAccumulated($line);
      }
    }
    $this->endCollect();
  }

  /**
   * @param array $contentLines
   * @return array of lines with comments re-intersperced
   */
  public function inject(array $contentLines): array
  {
    $contentLines = $this->removeTrailingBlankLines($contentLines);

    // If there were any comments at the beginning of the
    // file, then put them back at the beginning.
    $result = $this->headComments === false ? [] : $this->headComments;
    foreach ($contentLines as $line) {
      $fetched = $this->find($line);
      $result = array_merge($result, $fetched);

      $result[] = $line;
    }
    // Any comments found at the end of the file will stay at
    // the end of the file.
    $result = array_merge($result, $this->endComments);
    return $result;
  }

  /**
   * @param string $line
   * @return true if provided line is a comment
   */
  protected function isComment(string $line): int|false
  {
    return preg_match('%^ *#%', $line);
  }

  /**
   * Stop collecting. Any accumulated comments will be
   * remembered so that they may be re-injected at the
   * end of the new file.
   */
  protected function endCollect(): void
  {
    $this->endComments = $this->accumulated;
    $this->accumulated = [];
  }

  protected function accumulateEmptyLine(): void
  {
    if ($this->hasStored) {
        $this->accumulate('');
        return;
    }

    if ($this->headComments === false) {
      $this->headComments = [];
    }

    $this->headComments = array_merge($this->headComments, $this->accumulated);
    $this->accumulated = [''];
  }

  /**
   * Accumulate comments and blank lines in our cache.
   * @param string $line
   */
  protected function accumulate(string $line): void
  {
    $this->accumulated[] = $line;
  }

  /**
   * When a non-comment line is found, remember all of
   * the comment lines that came before it in our cache.
   *
   * @param string $line
   */
  protected function storeAccumulated(string $line): void
  {
    // Remember that we called storeAccumulated at least once
    $this->hasStored = true;

    // The very first time storeAccumulated is called, the
    // accumulated comments will be placed in $this->headComments
    // instead of stored, so they may be restored to the
    // beginning of the new file.
    if ($this->headComments === false) {
      $this->headComments = $this->accumulated;
      $this->accumulated = [];
    }
    if (!empty($this->accumulated)) {
      $lineId = $this->getLineId($line, true);
      $this->stored[$lineId][] = $this->accumulated;
      $this->accumulated = [];
    }
  }

  /**
   * Generates unique line id based on the key it contains. It allows
   * to reattach comments to the edited yaml sections.
   *
   * For example, lets take a look at the following yaml:
   *
   * # Top comments
   * top:
   * # Top one
   *   one:
   *     # Top two
   *     two: two
   * # Bottom comments
   * bottom:
   *   # Bottom one
   *   one:
   *     # Bottom two
   *     two: 2
   *
   * This method generates ids based on keys (discarding values).
   * Additionally, duplicating keys are taken into account as well.
   * The following ids will be generated:
   *
   * 1|top
   * 1|  one
   * 1|    two
   * 1|bottom
   * 2|  one
   * 2|    two
   *
   * @param string $line
   * @param bool $isCollecting
   */
  protected function getLineId(string $line, bool $isCollecting = true)
  {
    [$id] = explode(':', $line, 2);

    if ($isCollecting) {
      if (isset($this->lineIds[$id])) {
        $this->lineIds[$id][] = end($this->lineIds[$id]) + 1;
      } else {
        $this->lineIds[$id] = [1];
      }

      return end($this->lineIds[$id]) . '|' . $id;
    }

    if (isset($this->lineIds[$id])) {
      return array_shift($this->lineIds[$id]) . '|' . $id;
    } else {
      return  '1|' . $id;
    }
  }


  /**
   * Check to see if the provided line has any associated comments.
   *
   * @param string $line
   */
  protected function find(string $line)
  {
    $lineId = $this->getLineId($line, false);
    if (!isset($this->stored[$lineId]) || empty($this->stored[$lineId])) {
      return [];
    }
    // The stored result is a stack of accumulated comments. Pop
    // one off; if more remain, they will be attached to the next
    // line with the same value.
    return array_shift($this->stored[$lineId]);
  }

  /**
   * Remove all of the blank lines from the end of an array of lines.
   */
  protected function removeTrailingBlankLines(array $lines): array {
    // Remove all of the trailing blank lines.
    while (!empty($lines) && $this->isBlank(end($lines))) {
      array_pop($lines);
    }
    return $lines;
  }

  /**
   * Return 'true' if the provided line is empty (save for whitespace)
   */
  protected function isBlank(string $line): false|int {
    return preg_match('#^\s*$#', $line);
  }
};


$finder = (new Finder())->files()->in('profile/')->exclude('config/')->name('*.info.yml');

const VERSION = '2.2.0';
foreach ($finder->getIterator() as $file) {
  $yamlContent = $file->getContents();
  $comments->collect(explode("\n", $yamlContent));
  $info = Yaml::decode($yamlContent);
  if (isset($info['dependencies'])) {
    natsort($info['dependencies']);
    $info['dependencies'] = array_values(array_unique($info['dependencies']));
  }
  if (isset($info['config_devel']['install'])) {
    natsort($info['config_devel']['install']);
    $info['config_devel']['install'] = array_values(array_unique($info['config_devel']['install']));
  }
  if (isset($info['config_devel']['optional'])) {
    natsort($info['config_devel']['optional']);
    $info['config_devel']['optional'] = array_values(array_unique($info['config_devel']['optional']));
  }
  if (isset($info['install'])) {
    natsort($info['install']);
    $info['install'] = array_values(array_unique($info['install']));
  }
  $new_info = [
    'name' => $info['name'],
    'type' => $info['type'],
    'core_version_requirement' => '^10',
    'description' => $info['description'],
    'package' => 'OpenCulturas',
    'version' => VERSION,
    'project' => 'openculturas'
  ] + $info;

  $altered_contents = Yaml::encode($new_info);
  $altered_with_comments = $comments->inject(explode("\n", $altered_contents));
  file_put_contents($file->getPathname(), trim(implode("\n", $altered_with_comments)));
}


