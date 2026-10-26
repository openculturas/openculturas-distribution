<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

use Drupal\entity_popover\ParagraphBackfiller;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\openculturas_entity_popover\DisplayWirer;

/**
 * Scenario B: the full lifecycle of openculturas_entity_popover.
 *
 * One test method, because the steps are a sequence: each one starts from the
 * state the previous one left. Splitting the scenario would lose the state the
 * later steps assert on.
 *
 * The expectations come from the docblocks of hook_install() and
 * hook_uninstall() in openculturas_entity_popover.install and from the class
 * docblocks of DisplayWirer, NodeDisplayUnwirer and FieldUnwirer. Where the
 * two disagree, the test fails and the difference is a finding.
 */
final class ScenarioBEntityPopoverTest extends LifecycleBase {

  private const string MODULE = 'openculturas_entity_popover';

  /**
   * The settings page of the entity_popover module.
   */
  private const string SETTINGS_PATH = '/admin/config/content/entity_popover';

  /**
   * The button that bulk-updates the selected bundles.
   */
  private const string UPDATE_BUTTON = 'Update selected bundles';

  /**
   * A node display the module never wires, used for the site builder's popover.
   */
  private const string BUILDER_DISPLAY = 'node.profile.teaser';

  /**
   * A field of that display that DisplayWirer never touches.
   */
  private const string BUILDER_FIELD = 'field_portrait';

  /**
   * Runs every step of scenario B in order.
   */
  public function testEntityPopoverLifecycle(): void {
    $this->loginWithRole('administrator');

    $this->step1Install();
    $this->step2UpdateTogglesThroughSettings();
    $this->stepAddSiteBuilderPopover();
    $this->step4UninstallKeepsTheSiteBuilderPopover();
    $this->step5RemovePopoverAndUninstallEverything();
  }

  /**
   * Installs the module with its dependencies and checks what it wired.
   */
  private function step1Install(): void {
    $this->installModulesThroughUi([self::MODULE]);
    $this->reloadSiteState();

    $this->assertTrue($this->moduleIsInstalled(self::MODULE), 'The module is installed.');

    // The dependencies have to come with it through the UI.
    foreach (['entity_popover', 'entity_popover_swiffy_slider'] as $dependency) {
      $this->assertTrue(
        $this->moduleIsInstalled($dependency),
        sprintf('The hard dependency %s is installed together with the module.', $dependency),
      );
    }

    // hook_install() only adds the message when there are gallery or media
    // paragraphs to bulk-update, and this site has both.
    $session = $this->assertSession();
    $session->pageTextContains('The popover toggle is now available on Gallery and Media paragraphs');
    $session->pageTextContains('Existing entities keep the toggle empty until you bulk-update them');

    // DisplayWirer::FIELD_BUNDLES is the list the field configs are created
    // on, so those two field configs have to exist now.
    foreach (DisplayWirer::FIELD_BUNDLES as $bundle) {
      $this->assertNotNull(
        $this->container->get('entity_type.manager')->getStorage('field_config')->load('paragraph.' . $bundle . '.' . DisplayWirer::FIELD_NAME),
        sprintf('hook_install() adds %s to the paragraph bundle %s.', DisplayWirer::FIELD_NAME, $bundle),
      );
    }

    // And the node displays DisplayWirer::FIELD_GALLERY_DISPLAYS lists carry
    // the popover setting it added.
    foreach (DisplayWirer::FIELD_GALLERY_DISPLAYS as $displayId) {
      $this->assertTrue(
        $this->hasGalleryPopoverOn($displayId),
        sprintf('The module wired field_gallery on %s.', $displayId),
      );
    }

    // hook_install() states that existing paragraphs keep the toggle empty, so
    // the data of the paragraphs that already existed must not have been
    // touched. There is no table for the field yet, which is the same fact
    // seen from the other side.
    $this->assertSame(
      ['(table does not exist)'],
      $this->recorder->readDump('S1', 'data/paragraph_field_entity_popover.txt'),
      'No popover toggle data existed before the install.',
    );

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase('B1');
  }

  /**
   * Bulk-updates every bundle through the settings form.
   */
  private function step2UpdateTogglesThroughSettings(): void {
    $this->drupalGet(self::SETTINGS_PATH);
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->buttonExists(self::UPDATE_BUTTON);

    // Selecting every bundle is what a site builder does on the first run:
    // the module tells them existing entities keep an empty toggle and that
    // they have to update them here.
    $emptyBefore = $this->emptyParagraphCount();
    $this->assertGreaterThan(
      0,
      $emptyBefore,
      'The site has gallery and media paragraphs whose toggle is still empty.',
    );

    $bundles = $this->bundleRowKeys();
    $this->assertNotEmpty($bundles, 'The form offers the bundles with a popover field.');

    $values = [];
    foreach ($bundles as $rowKey) {
      $values['bundles[' . $rowKey . '][update]'] = TRUE;
    }
    $this->submitForm($values, self::UPDATE_BUTTON);

    $session = $this->assertSession();
    $session->pageTextContains(sprintf("Updated %d entities to their popover fields' default values.", $emptyBefore));

    // Every paragraph that was empty now has a value, and none of them is
    // empty any more.
    $this->assertSame(
      0,
      $this->emptyParagraphCount(),
      'After the bulk update no paragraph is left with an empty popover toggle.',
    );

    // A second run has nothing left to do, so it updates zero. The form
    // disables the checkbox of a bundle with no empty entities, which is what
    // stops the second run by itself.
    $this->drupalGet(self::SETTINGS_PATH);
    $this->submitForm($values, self::UPDATE_BUTTON);
    $this->assertSession()->pageTextContains('Updated 0 entities');

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase('B2');
  }

  /**
   * Adds a popover on a display the module never wires.
   */
  private function stepAddSiteBuilderPopover(): void {
    $this->setBuilderPopover();

    $this->assertTrue(
      $this->hasComponentPopover(self::BUILDER_DISPLAY, self::BUILDER_FIELD),
      'The site builder popover is saved on the field of the display the module never wires.',
    );

    // Saved through the display entity, so the display has to depend on the
    // module the setting comes from. hook_uninstall() promises this dependency
    // survives.
    $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load(self::BUILDER_DISPLAY);
    $this->assertContains(
      DisplayWirer::THIRD_PARTY_PROVIDER,
      $display?->getDependencies()['module'] ?? [],
      'The display depends on the module the popover setting comes from.',
    );

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase('B3');
  }

  /**
   * Uninstalls the module and checks what it takes back and what it keeps.
   */
  private function step4UninstallKeepsTheSiteBuilderPopover(): void {
    $this->uninstallModulesThroughUi([self::MODULE]);
    $this->reloadSiteState();

    $this->assertFalse($this->moduleIsInstalled(self::MODULE), 'The module is uninstalled.');

    // The wiring the module added to field_gallery is gone.
    foreach (DisplayWirer::FIELD_GALLERY_DISPLAYS as $displayId) {
      $this->assertFalse(
        $this->hasGalleryPopoverOn($displayId),
        sprintf('The field_gallery popover is gone from %s.', $displayId),
      );
    }

    // The popover the site builder set up stays, with its dependency, because
    // hook_uninstall() says it keeps both: the module providing the setting
    // stays installed.
    $this->assertTrue(
      $this->hasComponentPopover(self::BUILDER_DISPLAY, self::BUILDER_FIELD),
      'The site builder popover survives the uninstall of the module that wires displays.',
    );
    $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load(self::BUILDER_DISPLAY);
    $this->assertContains(
      DisplayWirer::THIRD_PARTY_PROVIDER,
      $display?->getDependencies()['module'] ?? [],
      'The display keeps its dependency on the popover setting module.',
    );

    // The field configs and their data go away immediately rather than being
    // left for cron, because "Fields pending deletion" would block
    // uninstalling entity_popover itself right after.
    foreach (DisplayWirer::FIELD_BUNDLES as $bundle) {
      $this->assertNull(
        $this->container->get('entity_type.manager')->getStorage('field_config')->load('paragraph.' . $bundle . '.' . DisplayWirer::FIELD_NAME),
        sprintf('The field config on the %s bundle is gone right after the uninstall.', $bundle),
      );
    }

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase('B4');

    // The field type provider can be uninstalled straight afterwards, without
    // waiting for cron and without the "Fields pending deletion" blocking it.
    // hook_uninstall() promises the field configs and their data go away
    // immediately, "otherwise \"Fields pending deletion\" blocks uninstalling
    // entity_popover itself (its field type) right after".
    //
    // What that promise is about is the field purge, so the check is that
    // nothing is left in the deleted fields repository. Core still blocks
    // uninstalling entity_popover while entity_popover_swiffy_slider is
    // installed, because that module requires it - a dependency order rule
    // that has nothing to do with field purging, and the reason the checkbox
    // is disabled until step 5.
    $this->assertSame(
      [],
      $this->pendingFieldDeletions(),
      'The uninstall leaves nothing in the deleted fields repository, so no "Fields pending deletion" is pending for the popover field.',
    );

    // The site builder popover is still there after openculturas_entity_popover
    // is gone: entity_popover_swiffy_slider stays installed, so the setting is
    // still a valid formatter setting of it.
    $this->assertTrue(
      $this->hasComponentPopover(self::BUILDER_DISPLAY, self::BUILDER_FIELD),
      'The site builder popover survives the uninstall of the module that wires displays.',
    );

    $this->assertNoNewWatchdogErrors();
  }

  /**
   * Removes the site builder popover and uninstalls the remaining modules.
   */
  private function step5RemovePopoverAndUninstallEverything(): void {
    // With the popover gone, nothing references entity_popover_swiffy_slider
    // any more, so both modules can be uninstalled together.
    $this->removeBuilderPopover();
    $this->reloadSiteState();

    // One module at a time: core disables the checkbox of a module another
    // module still requires, so entity_popover cannot be selected in the same
    // request as entity_popover_swiffy_slider. Uninstalling the dependent
    // module first is the path the form itself offers.
    $this->uninstallModulesThroughUi(['entity_popover_swiffy_slider']);
    $this->reloadSiteState();
    $this->uninstallModulesThroughUi(['entity_popover']);
    $this->reloadSiteState();

    $this->assertFalse(
      $this->moduleIsInstalled('entity_popover'),
      'With its dependent module gone, entity_popover uninstalls without a field purge pending.',
    );

    foreach (self::MODULES_UNDER_TEST as $module) {
      $this->assertFalse($this->moduleIsInstalled($module), sprintf('%s is uninstalled.', $module));
    }

    // Everything the scenario did has to be undone.
    $this->recordPhase('B5');
    $this->assertPhasesMatch('B5', 'S1');
    $this->assertNoNewWatchdogErrors();
  }

  /**
   * Whether a node display still carries the popover setting on field_gallery.
   *
   * @param string $displayId
   *   The display id.
   */
  private function hasGalleryPopoverOn(string $displayId): bool {
    $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load($displayId);
    if ($display === NULL) {
      $this->fail(sprintf('The display %s does not exist on this site.', $displayId));
    }

    // On a Layout Builder display the setting lives in the formatter of the
    // field_gallery block, everywhere else in the display component. Both are
    // covered, so the check does not depend on which one the display uses.
    foreach ($display->getComponents() as $fieldName => $component) {
      if ($fieldName === DisplayWirer::FIELD_GALLERY_FIELD_NAME
        && isset($component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER])) {
        return TRUE;
      }
    }

    foreach ($this->layoutBuilderFormatters($display) as $blockId => $formatter) {
      if ($blockId === DisplayWirer::fieldGalleryBlockId($displayId)
        && isset($formatter['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * The formatters of a Layout Builder display's field blocks.
   *
   * @param \Drupal\Core\Entity\Entity\EntityViewDisplay $display
   *   The display to read.
   *
   * @return array<string, array<string, mixed>>
   *   The formatter of each field block, keyed by block id.
   */
  private function layoutBuilderFormatters(?object $display): array {
    if (!$display instanceof LayoutBuilderEntityViewDisplay || !$display->isLayoutBuilderEnabled()) {
      return [];
    }

    $formatters = [];
    foreach ($display->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        $configuration = $component->get('configuration');
        $formatters[(string) ($configuration['id'] ?? '')] = $configuration['formatter'] ?? [];
      }
    }

    return $formatters;
  }

  /**
   * Whether a display component carries the popover setting.
   *
   * @param string $displayId
   *   The display id.
   * @param string $fieldName
   *   The field whose component to look at.
   */
  private function hasComponentPopover(string $displayId, string $fieldName): bool {
    $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load($displayId);
    $component = $display?->getComponent($fieldName);

    $this->assertIsArray($component, sprintf('%s has a %s component to put a popover on.', $displayId, $fieldName));
    return isset($component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER]);
  }

  /**
   * Sets a popover on a field of a display the module never wires.
   *
   * Saved through the display entity, the way the field UI form saves it.
   */
  private function setBuilderPopover(): void {
    $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load(self::BUILDER_DISPLAY);
    $component = $display?->getComponent(self::BUILDER_FIELD);
    $this->assertNotNull($display, 'The display the popover goes on exists.');
    $this->assertIsArray($component, 'The display has the field the site builder popover goes on.');

    $component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER] = [
      'enable' => TRUE,
      'details' => ['formatter' => $component['type']],
    ];
    $display->setComponent(self::BUILDER_FIELD, $component)->save();
  }

  /**
   * Removes the site builder popover again.
   */
  private function removeBuilderPopover(): void {
    $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load(self::BUILDER_DISPLAY);
    $this->assertNotNull($display, 'The display the popover was on still exists.');
    $component = $display->getComponent(self::BUILDER_FIELD);
    $this->assertIsArray($component, 'The display still has the field the popover was on.');

    unset($component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER]);
    $display->setComponent(self::BUILDER_FIELD, $component)->save();
  }

  /**
   * The field storages and field configs waiting to be purged.
   *
   * @return list<string>
   *   The unique identifiers of the deleted fields.
   */
  private function pendingFieldDeletions(): array {
    $repository = $this->container->get('entity_field.deleted_fields_repository');
    /** @var array<string, mixed> $storages */
    $storages = $repository->getFieldStorageDefinitions();
    // Every field config is looked up under the storage it belongs to, so the
    // popover storage's own key is what identifies them.
    $storageKey = array_key_first(array_filter(
      $storages,
      static fn (mixed $definition): bool => $definition instanceof FieldStorageConfigInterface
        && $definition->getName() === DisplayWirer::FIELD_NAME,
    ));
    /** @var array<string, mixed> $fields */
    $fields = $storageKey === NULL ? [] : $repository->getFieldDefinitions($storageKey);

    return array_values(array_unique([
      ...array_keys($storages),
      ...array_keys($fields),
    ]));
  }

  /**
   * The row keys of the settings form's bundle table.
   *
   * @return list<string>
   *   The row keys, one per "<bundle>:<field>" pair the form offers.
   */
  private function bundleRowKeys(): array {
    $keys = [];
    foreach ($this->paragraphPopoverFields() as $bundle => $fieldName) {
      $keys[] = $bundle . ':' . $fieldName;
    }

    return $keys;
  }

  /**
   * The paragraph bundles that carry a popover field, keyed by bundle.
   *
   * @return array<string, string>
   *   The field name per bundle.
   */
  private function paragraphPopoverFields(): array {
    $map = $this->container->get('entity_field.manager')->getFieldMapByFieldType('entity_popover');
    $bundles = [];
    foreach ($map['paragraph'] ?? [] as $fieldName => $information) {
      foreach (array_keys($information['bundles'] ?? []) as $bundle) {
        $bundles[(string) $bundle] = (string) $fieldName;
      }
    }

    return $bundles;
  }

  /**
   * How many paragraphs still have an empty popover toggle.
   *
   * Counted the way the settings form counts them, through the backfiller, so
   * the number the form shows and the number the test asserts on come from the
   * same definition of "empty".
   */
  private function emptyParagraphCount(): int {
    $fields = $this->paragraphPopoverFields();
    if ($fields === []) {
      return 0;
    }

    $byField = [];
    foreach ($fields as $bundle => $fieldName) {
      $byField[$fieldName][] = $bundle;
    }

    $counts = $this->container->get(ParagraphBackfiller::class);
    $total = 0;
    foreach ($byField as $fieldName => $bundles) {
      $total += array_sum($counts->countEmptyByBundle($bundles, $fieldName));
    }

    return $total;
  }

}
