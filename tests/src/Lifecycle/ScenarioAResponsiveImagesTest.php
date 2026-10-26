<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

use Drupal\openculturas_responsive_images\CropMigrator;
use Drupal\openculturas_responsive_images\SharedConfigSwitcher;

/**
 * Scenario A: the full lifecycle of openculturas_responsive_images.
 *
 * One test method, because the steps are a sequence: each one starts from the
 * state the previous one left. PHPUnit resets the browser session per method,
 * and splitting the scenario would lose the state the later steps assert on.
 *
 * The expectations come from the docblocks of hook_install() and
 * hook_uninstall() in openculturas_responsive_images.install and from the
 * class docblocks of CropMigrator and SharedConfigSwitcher. Where the two
 * disagree, the test fails and the difference is a finding.
 */
final class ScenarioAResponsiveImagesTest extends LifecycleBase {

  private const string MODULE = 'openculturas_responsive_images';

  /**
   * The settings page of the module.
   */
  private const string SETTINGS_PATH = '/admin/config/media/openculturas_responsive_images';

  /**
   * The button that migrates the crops selected in the form.
   */
  private const string MIGRATE_BUTTON = 'Migrate selected crops';

  /**
   * The button that switches the image displays to responsive.
   */
  private const string SWITCH_BUTTON = 'Switch to responsive images';

  /**
   * The button that switches the image displays back to fixed styles.
   */
  private const string REVERT_BUTTON = 'Revert to fixed image styles';

  /**
   * Why core.extension differs after an uninstall, as the expectation names it.
   */
  private const string CORE_EXTENSION_REASON = 'Drupal core keeps the dependencies of an uninstalled module enabled, so responsive_image and svg_image_responsive stay listed.';

  /**
   * Runs every step of scenario A in order.
   */
  public function testResponsiveImagesLifecycle(): void {
    $this->loginWithRole('administrator');

    $baselineCrops = $this->cropsIn('S1');
    $this->assertNotEmpty($baselineCrops, 'The baseline has crops to migrate.');

    $this->step1Install($baselineCrops);
    $this->step2MigrateCrops();
    $this->step3MigrateCropsAgain();
    $this->step4SwitchToResponsive();
    $this->step5RevertToFixedStyles();
    $this->step6UninstallWithoutReverting($baselineCrops);
    $this->step7ReinstallAndUninstallAgain($baselineCrops);
  }

  /**
   * Installs the module through the UI and records the S2 baseline.
   *
   * @param list<array<string, string>> $baselineCrops
   *   The crop rows of S1, which the install has to leave alone.
   */
  private function step1Install(array $baselineCrops): void {
    $this->installModulesThroughUi([self::MODULE]);
    $this->reloadSiteState();

    $this->assertTrue($this->moduleIsInstalled(self::MODULE), 'The module is installed.');

    // hook_install() switches nothing: "Installing the module doesn't switch
    // any shared config, so on an existing site the image displays still use
    // the legacy image styles.".
    $this->assertFalse(
      $this->container->get(SharedConfigSwitcher::class)->isResponsive(),
      'Installing the module must not switch the shared image displays: hook_install() switches nothing.',
    );

    // Same check as the settings form, so both agree on what is left to
    // migrate. This site has migratable crops, so the message has to name the
    // crop migration.
    $session = $this->assertSession();
    $session->pageTextContains('Responsive images are installed but not used yet.');
    $session->pageTextContains('migrate your existing image crops');

    // The message links to the settings page, so the site builder has a way
    // to get there from the message itself.
    $settingsLink = $this->getSession()->getPage()->find('css', '.messages--status a[href$="/admin/config/media/openculturas_responsive_images"]');
    $this->assertNotNull(
      $settingsLink,
      'The install message links to the settings page, so the site builder can act on it.',
    );
    $this->assertSame(
      'OpenCulturas - Responsive images settings',
      $settingsLink->getText(),
      'The link in the install message carries the label the install hook builds it with.',
    );

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase('S2');
    $this->assertTrue($this->recorder->hasPhase('S2'), 'The state after the install is recorded as S2.');

    // The module ships only its own config, so S1 to S2 may add exactly the
    // crop types, image styles and responsive image styles it ships, plus the
    // core.extension listing. Anything else means the install rewired shared
    // config, which the module promises not to do.
    $this->assertOnlyModuleOwnConfigChanged('S2', 'S1');

    $this->assertSame(
      $baselineCrops,
      $this->cropsIn('S2'),
      'Installing the module creates no crop: the crop rows are byte-identical to S1.',
    );
  }

  /**
   * Migrates the crops the settings form suggests a target for.
   */
  private function step2MigrateCrops(): void {
    $cropsBefore = $this->cropsIn('S2');

    $this->drupalGet(self::SETTINGS_PATH);
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    // The submit buttons are input elements, so their label is a value
    // attribute rather than page text.
    $session->buttonExists(self::MIGRATE_BUTTON);
    $session->buttonExists(self::SWITCH_BUTTON);

    // Every source the form offers is submitted with its suggested target,
    // which is what a site builder who accepts the defaults does.
    $sources = $this->container->get(CropMigrator::class)->migratableSourceTypes();
    $this->assertNotEmpty($sources, 'The settings form offers the crop types that still need migrating.');

    $values = [];
    $migratedPairs = [];
    foreach ($sources as $sourceId => $cropType) {
      $targetId = $this->container->get(CropMigrator::class)->suggestedTarget($cropType);
      if ($targetId === '') {
        // A source with no unambiguous aspect-ratio match has no suggestion;
        // a site builder would leave it alone, so the test does too.
        continue;
      }

      $values['mapping[' . $sourceId . '][target]'] = $targetId;
      $migratedPairs[$sourceId] = $targetId;
    }
    $this->assertNotEmpty($migratedPairs, 'At least one crop type has a suggested target to migrate.');

    $this->submitForm($values, self::MIGRATE_BUTTON);
    $this->followBatch();

    $this->recordPhase('A2');
    $cropsAfter = $this->cropsIn('A2');

    // One target crop per legacy crop of a migrated source, and no more.
    foreach ($migratedPairs as $sourceId => $targetId) {
      $sources = array_values(array_filter(
        $cropsBefore,
        static fn (array $row): bool => $row['type'] === $sourceId,
      ));
      $targets = array_values(array_filter(
        $cropsAfter,
        static fn (array $row): bool => $row['type'] === $targetId,
      ));

      $this->assertCount(
        count($sources),
        $targets,
        sprintf('Every %s crop got exactly one %s duplicate.', $sourceId, $targetId),
      );

      // Coordinate-wise, since migrate() keeps them for a matching aspect
      // ratio and trims the size to fit otherwise.
      $expected = [];
      foreach ($sources as $source) {
        $expected[$source['uri']] = $source;
      }
      $actual = [];
      foreach ($targets as $target) {
        $actual[$target['uri']] = $target;
      }
      $this->assertSame(
        array_keys($expected),
        array_keys($actual),
        sprintf('The %s duplicates cover exactly the files the %s crops are on.', $targetId, $sourceId),
      );
      foreach ($expected as $uri => $source) {
        $this->assertSame(
          [
            'entity_type' => $source['entity_type'],
            'entity_id' => $source['entity_id'],
            'x' => $source['x'],
            'y' => $source['y'],
          ],
          [
            'entity_type' => $actual[$uri]['entity_type'],
            'entity_id' => $actual[$uri]['entity_id'],
            'x' => $actual[$uri]['x'],
            'y' => $actual[$uri]['y'],
          ],
          sprintf(
            'The %s duplicate of %s keeps the entity and the top-left corner: migrate() copies x and y unchanged.',
            $targetId,
            $uri,
          ),
        );
      }
    }

    // migrate() only creates crops, so the legacy rows have to be untouched.
    $this->assertSame(
      $cropsBefore,
      array_values(array_filter(
        $cropsAfter,
        static fn (array $row): bool => !in_array($row['type'], CropMigrator::OWN_CROP_TYPES, TRUE),
      )),
      'The migration leaves the legacy crops byte-identical: it only adds duplicates.',
    );

    // The state key has to name every migrated pair, since that is what stops
    // a source from being offered again.
    $mappings = $this->stateMappings();
    foreach ($migratedPairs as $sourceId => $targetId) {
      $this->assertContains(
        $targetId,
        $mappings[$sourceId] ?? [],
        sprintf('The migration history remembers that %s was migrated onto %s.', $sourceId, $targetId),
      );
    }

    $this->assertNoNewWatchdogErrors();
  }

  /**
   * Migrates a second time: nothing must change.
   */
  private function step3MigrateCropsAgain(): void {
    $before = $this->cropsIn('A2');

    $this->drupalGet(self::SETTINGS_PATH);
    $sources = $this->container->get(CropMigrator::class)->migratableSourceTypes();
    $values = [];
    foreach ($sources as $sourceId => $cropType) {
      $targetId = $this->container->get(CropMigrator::class)->suggestedTarget($cropType);
      if ($targetId !== '') {
        $values['mapping[' . $sourceId . '][target]'] = $targetId;
      }
    }

    if ($values === []) {
      // Nothing left to offer, which is the strongest form of "a second run
      // changes nothing". Recorded as such rather than skipped silently.
      $this->recordPhase('A3');
      $this->assertSame($before, $this->cropsIn('A3'), 'No source is left to migrate, so no crop changed.');
      return;
    }

    $this->submitForm($values, self::MIGRATE_BUTTON);
    $this->followBatch();

    $this->recordPhase('A3');
    $this->assertSame(
      $before,
      $this->cropsIn('A3'),
      'A second migration creates no duplicate and changes no crop: migrate() skips files that already have a crop of the target type.',
    );

    $this->assertNoNewWatchdogErrors();
  }

  /**
   * Switches the image displays to responsive and records S3.
   */
  private function step4SwitchToResponsive(): void {
    $this->drupalGet(self::SETTINGS_PATH);
    $this->submitForm([], self::SWITCH_BUTTON);
    $this->reloadSiteState();

    $this->assertTrue(
      $this->container->get(SharedConfigSwitcher::class)->isResponsive(),
      'The button switched the displays: SharedConfigSwitcher::isResponsive() reports TRUE.',
    );

    // Every display the switcher lists has to carry a responsive formatter
    // now. Reading them through the display entity is what the theme and the
    // render layer see.
    foreach (['media.image.header_image', 'media.image.teaser_image', 'media.image.teaser_image_big', 'media.user_profile_picture.default', 'media.image.gallery', 'media.image.content_asset'] as $displayId) {
      $component = $this->viewDisplayComponent($displayId, 'field_media_image');
      $this->assertSame(
        'responsive_image',
        $component['type'] ?? NULL,
        sprintf('%s renders field_media_image through the responsive image formatter.', $displayId),
      );
      $this->assertStringStartsWith(
        'oc_',
        (string) ($component['settings']['responsive_image_style'] ?? ''),
        sprintf('%s uses a responsive image style of this module.', $displayId),
      );
    }

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase('S3');
  }

  /**
   * Switches back and checks that only the module's own values change.
   */
  private function step5RevertToFixedStyles(): void {
    $this->drupalGet(self::SETTINGS_PATH);
    $this->submitForm([], self::REVERT_BUTTON);
    $this->reloadSiteState();

    $this->assertFalse(
      $this->container->get(SharedConfigSwitcher::class)->isResponsive(),
      'The button reverted the displays: SharedConfigSwitcher::isResponsive() reports FALSE again.',
    );

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase('A4');

    // Reverting must restore exactly the S2 config: the class docblock of
    // SharedConfigSwitcher promises that "revert only touches config that
    // still holds this module's values".
    $this->assertPhasesMatch('A4', 'S2', [], [
      'data/crop_field_data.txt' => 'the duplicates the migration created stay: they are content, and hook_uninstall() removes them later as the module\'s own crops',
      'data/state.txt' => 'the migration history is remembered until the module is uninstalled; hook_install() documents no reverts of it either',
    ]);
  }

  /**
   * Switches once more and uninstalls without reverting first.
   *
   * @param list<array<string, string>> $baselineCrops
   *   The crop rows of S1, which the uninstall has to restore.
   */
  private function step6UninstallWithoutReverting(array $baselineCrops): void {
    $this->drupalGet(self::SETTINGS_PATH);
    $this->submitForm([], self::SWITCH_BUTTON);
    $this->reloadSiteState();

    $this->assertTrue(
      $this->container->get(SharedConfigSwitcher::class)->isResponsive(),
      'The button switched the displays to responsive again before the uninstall.',
    );

    $this->uninstallModulesThroughUi([self::MODULE]);
    $this->reloadSiteState();

    $this->assertFalse($this->moduleIsInstalled(self::MODULE), 'The module is uninstalled.');

    $this->recordPhase('A5');

    // hook_uninstall() reverts the shared config, deletes the migration state
    // and deletes the crop content entities of its own crop types. Everything
    // else has to be exactly what it was in S1.
    //
    // core.extension is the one documented exception: Drupal core does not
    // uninstall a module's dependencies when that module is uninstalled, so
    // responsive_image and svg_image_responsive - installed alongside the
    // module as its dependencies - stay enabled. That is core's uninstall
    // behaviour, not something either module under test does, so it is named
    // here rather than hidden in a blanket allowlist. The assertion below
    // pins down exactly which modules remain, so a third one appearing would
    // still fail.
    $this->assertPhasesMatch('A5', 'S1', [], [
      'core.extension' => self::CORE_EXTENSION_REASON,
    ]);
    $this->assertOnlyDependenciesRemainEnabled('A5');

    // The crops of the module's own types are gone, the legacy ones are as
    // they were. hook_uninstall() states both.
    $remaining = $this->cropsIn('A5');
    foreach ($remaining as $row) {
      $this->assertNotContains(
        $row['type'],
        CropMigrator::OWN_CROP_TYPES,
        'hook_uninstall() deletes every crop of the module\'s own crop types.',
      );
    }
    $this->assertSame(
      $baselineCrops,
      $remaining,
      'The legacy crops are byte-identical to S1 after the uninstall.',
    );

    // The state key is deleted explicitly by hook_uninstall().
    $this->assertSame(
      [],
      $this->stateMappings(),
      'hook_uninstall() deletes the migration history from state.',
    );

    $this->assertNoNewWatchdogErrors();
  }

  /**
   * Installs, uninstalls again and checks both leave no residue.
   */
  private function step7ReinstallAndUninstallAgain(array $baselineCrops): void {
    $this->installModulesThroughUi([self::MODULE]);
    $this->reloadSiteState();
    $this->recordPhase('A6');

    // A second install has to land on the state the first install landed on,
    // so the second run cannot build on anything the first one left behind.
    $this->assertOnlyModuleOwnConfigChanged('A6', 'S1');
    $this->assertSame(
      $baselineCrops,
      $this->cropsIn('A6'),
      'The second install creates no crop, exactly like the first one.',
    );
    $this->assertSame([], $this->stateMappings(), 'The second install writes no migration history.');

    $this->uninstallModulesThroughUi([self::MODULE]);
    $this->reloadSiteState();

    $this->assertFalse($this->moduleIsInstalled(self::MODULE), 'The module is uninstalled a second time.');
    $this->recordPhase('A7');

    // Same dependencies as after the first uninstall, for the same reason.
    $this->assertPhasesMatch('A7', 'S1', [], [
      'core.extension' => self::CORE_EXTENSION_REASON,
    ]);
    $this->assertOnlyDependenciesRemainEnabled('A7');
    $this->assertNoNewWatchdogErrors();
  }

  /**
   * Asserts that an uninstall left enabled only the dependencies core keeps.
   *
   * Pinned to exactly the two dependencies core leaves behind, so a third
   * module surviving the uninstall still fails.
   *
   * @param string $phase
   *   The phase recorded after the uninstall.
   */
  private function assertOnlyDependenciesRemainEnabled(string $phase): void {
    $this->assertSame(
      ['responsive_image', 'svg_image_responsive'],
      array_values(array_diff($this->enabledModulesIn($phase), $this->enabledModulesIn('S1'))),
      'Uninstalling the module leaves enabled only the two dependencies Drupal core keeps installed, and nothing else.',
    );
  }

  /**
   * Asserts that two phases differ only in the module's own config.
   *
   * @param string $later
   *   The phase name of the later state.
   * @param string $earlier
   *   The phase name of the baseline.
   */
  private function assertOnlyModuleOwnConfigChanged(string $later, string $earlier): void {
    $unexpected = [];
    foreach ($this->configNamesIn($earlier) as $name) {
      if ($this->configurationIsUnchanged($earlier, $later, $name)) {
        continue;
      }

      if (!$this->isModuleOwnConfigName($name)) {
        $unexpected[] = $name;
      }
    }
    foreach ($this->configNamesIn($later) as $name) {
      if (!$this->configExistsIn($earlier, $name) && !$this->isModuleOwnConfigName($name)) {
        $unexpected[] = $name . ' (added)';
      }
    }

    $this->assertSame([], array_values(array_unique($unexpected)), sprintf(
      'Installing the module changes only the configuration the module owns plus core.extension. Unrelated config changed: %s',
      $unexpected === [] ? '(none)' : "\n  " . implode("\n  ", array_unique($unexpected)),
    ));
  }

  /**
   * Whether a configuration object holds the same data in two phases.
   *
   * The uuid and the config hash are excluded: core stamps every object it
   * saves with a fresh uuid, so an install that rewrites an object without
   * changing anything about it shows a different uuid. Neither value reaches
   * the rendered site.
   *
   * @param string $earlier
   *   The phase name of the baseline.
   * @param string $later
   *   The phase name of the state under test.
   * @param string $name
   *   The configuration name.
   */
  private function configurationIsUnchanged(string $earlier, string $later, string $name): bool {
    $before = $this->configRawData($earlier, $name) ?? [];
    $after = $this->configRawData($later, $name) ?? [];

    return ConfigurationNormaliser::normalise($before) === ConfigurationNormaliser::normalise($after);
  }

  /**
   * Whether a configuration object belongs to the module under test.
   *
   * @param string $name
   *   The configuration name.
   */
  private function isModuleOwnConfigName(string $name): bool {
    return $name === 'core.extension'
      || str_starts_with($name, 'crop.type.oc_')
      || str_starts_with($name, 'image.style.oc_')
      || str_starts_with($name, 'responsive_image.styles.oc_');
  }

  /**
   * The crop rows of a recorded phase, keyed by the columns the dump holds.
   *
   * @param string $phase
   *   The phase name.
   *
   * @return list<array<string, string>>
   *   One entry per crop, sorted the way the dump is.
   */
  private function cropsIn(string $phase): array {
    $lines = $this->recorder->readDump($phase, 'data/crop_field_data.txt');

    $columns = ['type', 'entity_type', 'entity_id', 'uri', 'x', 'y', 'width', 'height'];
    $rows = [];
    foreach ($lines as $line) {
      if (trim($line) === '' || str_starts_with($line, '(')) {
        continue;
      }

      $values = explode("\t", $line);
      if (count($values) !== count($columns)) {
        continue;
      }

      $rows[] = array_combine($columns, $values);
    }

    return $rows;
  }

  /**
   * The migration history in state.
   *
   * @return array<string, list<string>>
   *   The target crop types per source crop type.
   */
  private function stateMappings(): array {
    $line = $this->container->get('state')->get(CropMigrator::MAPPINGS_STATE_KEY);
    /** @var array<string, list<string>> $mappings */
    $mappings = is_array($line) ? $line : [];
    return $mappings;
  }

  /**
   * The field_media_image component of a media view display.
   *
   * @param string $displayId
   *   The display id, for example media.image.header_image.
   * @param string $fieldName
   *   The field whose component to read.
   *
   * @return array<string, mixed>
   *   The component.
   */
  private function viewDisplayComponent(string $displayId, string $fieldName): array {
    $component = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load($displayId)
      ?->getComponent($fieldName);

    $this->assertIsArray($component, sprintf('%s has a %s component.', $displayId, $fieldName));
    return $component;
  }

}
