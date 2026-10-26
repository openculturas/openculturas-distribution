<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

use Drupal\entity_popover\ParagraphBackfiller;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\openculturas_entity_popover\DisplayWirer;
use Drupal\openculturas_responsive_images\CropMigrator;
use Drupal\openculturas_responsive_images\SharedConfigSwitcher;

/**
 * Scenario C: both modules installed, used and removed together.
 *
 * Both modules touch the gallery displays - SharedConfigSwitcher switches
 * their field_gallery view mode, DisplayWirer puts the popover toggle on the
 * paragraph gallery and media displays - so this scenario checks that neither
 * module overwrites the other's changes on the same display, and that
 * uninstalling one does not take the other's work with it.
 *
 * The order is the variable, so there is one test method per order. They have
 * to be separate methods because each needs its own restore of the
 * lifecycle-after-updb snapshot, and the orchestrator restores once per test
 * method. Running them in one method would leave the second run building on
 * whatever the first one left.
 */
final class ScenarioCCombinedModulesTest extends LifecycleBase {

  private const string RESPONSIVE_IMAGES = 'openculturas_responsive_images';
  private const string ENTITY_POPOVER = 'openculturas_entity_popover';

  /**
   * The settings page of the responsive images module.
   */
  private const string RESPONSIVE_SETTINGS = '/admin/config/media/openculturas_responsive_images';

  /**
   * The gallery displays both modules have an interest in.
   *
   * SharedConfigSwitcher switches the field_gallery view mode of the two
   * single-item displays, DisplayWirer adds the popover toggle to the grid,
   * slider_duo and slider_multiple displays. None of them overlap, which is
   * the property this scenario exists to confirm on a real site.
   */
  private const array SWITCHED_GALLERY_DISPLAYS = ['paragraph.gallery.default', 'paragraph.gallery.slider'];

  private const array POPOVER_GALLERY_DISPLAYS = [
    'paragraph.gallery.grid',
    'paragraph.gallery.slider_duo',
    'paragraph.gallery.slider_multiple',
  ];

  /**
   * Runs the whole lifecycle with the responsive images module installed first.
   */
  public function testResponsiveImagesFirst(): void {
    $this->loginWithRole('administrator');
    $this->runOrder([self::RESPONSIVE_IMAGES, self::ENTITY_POPOVER], 'C-responsive-first');
  }

  /**
   * Runs the whole lifecycle with the entity popover module installed first.
   */
  public function testPopoverFirst(): void {
    $this->loginWithRole('administrator');
    $this->runOrder([self::ENTITY_POPOVER, self::RESPONSIVE_IMAGES], 'C-popover-first');
  }

  /**
   * Installs, uses and removes both modules in the given order.
   *
   * @param list<string> $order
   *   The modules, in the order they are installed.
   * @param string $phasePrefix
   *   The phase name prefix for this order.
   */
  private function runOrder(array $order, string $phasePrefix): void {
    $this->installModulesThroughUi($order);
    $this->reloadSiteState();

    foreach ($order as $module) {
      $this->assertTrue($this->moduleIsInstalled($module), sprintf('%s is installed.', $module));
    }

    // Migrate the crops through the form, the way a site builder does.
    $this->drupalGet(self::RESPONSIVE_SETTINGS);
    $this->migrateCropsThroughTheForm();

    // Switch the displays to responsive.
    $this->drupalGet(self::RESPONSIVE_SETTINGS);
    $this->submitForm([], 'Switch to responsive images');
    $this->reloadSiteState();
    $this->assertTrue(
      $this->container->get(SharedConfigSwitcher::class)->isResponsive(),
      'The image displays are switched to responsive.',
    );

    // Backfill the popover toggles.
    $this->drupalGet('/admin/config/content/entity_popover');
    $this->updateAllBundlesThroughTheForm();

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase($phasePrefix . '-in-use');

    // Both modules' work has to be present at the same time.
    $this->assertGalleryDisplaysHoldBothModulesWork();

    $this->recordPhase($phasePrefix);

    // Remove both, again in the given order, without reverting the switch
    // first, so the uninstall hooks have to undo their own work.
    $this->uninstallInOrder($order);
    $this->reloadSiteState();

    foreach (self::MODULES_UNDER_TEST as $module) {
      $this->assertFalse($this->moduleIsInstalled($module), sprintf('%s is uninstalled.', $module));
    }

    $this->assertNoNewWatchdogErrors();
    $this->recordPhase($phasePrefix . '-final');

    // The site has to be back where it started, whichever order it went in.
    // uninstallInOrder() also uninstalls the dependencies, so nothing differs.
    $this->assertPhasesMatch($phasePrefix . '-final', 'S1');
  }

  /**
   * Checks that both modules' changes are present on the gallery displays.
   *
   * Neither module may have overwritten the other's work on a display both of
   * them touch.
   */
  private function assertGalleryDisplaysHoldBothModulesWork(): void {
    foreach (self::SWITCHED_GALLERY_DISPLAYS as $displayId) {
      $component = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load($displayId)
        ?->getComponent(DisplayWirer::FIELD_GALLERY_FIELD_NAME);

      $this->assertIsArray($component, sprintf('%s has a %s component.', $displayId, DisplayWirer::FIELD_GALLERY_FIELD_NAME));
      $this->assertSame(
        'gallery_big',
        $component['settings']['view_mode'] ?? NULL,
        sprintf(
          '%s kept the responsive images module\'s view mode switch. A popover installation must not revert it.',
          $displayId,
        ),
      );
    }

    foreach (self::POPOVER_GALLERY_DISPLAYS as $displayId) {
      $component = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load($displayId)
        ?->getComponent(DisplayWirer::FIELD_NAME);

      $this->assertIsArray(
        $component,
        sprintf(
          '%s kept the popover toggle component. An image display switch must not remove it.',
          $displayId,
        ),
      );
      $this->assertSame(
        'entity_popover',
        $component['type'] ?? NULL,
        sprintf('%s still renders the popover toggle.', $displayId),
      );
    }

    // And the popover wiring on the node displays survived the switch.
    foreach (DisplayWirer::FIELD_GALLERY_DISPLAYS as $displayId) {
      $this->assertTrue(
        $this->galleryPopoverIsPresent($displayId),
        sprintf('The field_gallery popover on %s survived the responsive images switch.', $displayId),
      );
    }
  }

  /**
   * Whether a node display carries the popover setting on field_gallery.
   *
   * @param string $displayId
   *   The display id.
   */
  private function galleryPopoverIsPresent(string $displayId): bool {
    $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load($displayId);
    if (!$display instanceof LayoutBuilderEntityViewDisplay) {
      return FALSE;
    }

    $component = $display->getComponent(DisplayWirer::FIELD_GALLERY_FIELD_NAME);
    if (isset($component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER])) {
      return TRUE;
    }

    $wantedBlock = DisplayWirer::fieldGalleryBlockId($displayId);
    if (!$display->isLayoutBuilderEnabled()) {
      return FALSE;
    }

    foreach ($display->getSections() as $section) {
      foreach ($section->getComponents() as $sectionComponent) {
        $configuration = $sectionComponent->get('configuration');
        if (($configuration['id'] ?? '') === $wantedBlock
          && isset($configuration['formatter']['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER])) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Submits the crop migration form with every suggested target.
   */
  private function migrateCropsThroughTheForm(): void {
    $migrator = $this->container->get(CropMigrator::class);
    $values = [];
    foreach ($migrator->migratableSourceTypes() as $sourceId => $cropType) {
      $targetId = $migrator->suggestedTarget($cropType);
      if ($targetId !== '') {
        $values['mapping[' . $sourceId . '][target]'] = $targetId;
      }
    }

    if ($values === []) {
      $this->fail('The scenario needs migratable crops, but the settings form offers none.');
    }

    $this->submitForm($values, 'Migrate selected crops');
    $this->followBatch();
  }

  /**
   * Submits the popover settings form with every bundle selected.
   */
  private function updateAllBundlesThroughTheForm(): void {
    $values = [];
    foreach ($this->container->get(ParagraphBackfiller::class)->discoverParagraphFields() as $rowKey => $row) {
      $values['bundles[' . $rowKey . '][update]'] = TRUE;
    }

    if ($values === []) {
      $this->fail('The scenario needs paragraph bundles with a popover field, but the settings form offers none.');
    }

    $this->submitForm($values, 'Update selected bundles');
  }

  /**
   * Uninstalls the modules in the given order, one request each.
   *
   * Core disables the checkbox of a module another module still requires, so
   * modules with a dependency between them cannot be selected in one
   * submission. Uninstalling them one at a time is the path the form itself
   * offers a site builder.
   *
   * @param list<string> $modules
   *   The modules, in the order they are uninstalled.
   */
  private function uninstallInOrder(array $modules): void {
    foreach ($modules as $module) {
      $this->uninstallModulesThroughUi([$module]);
      $this->reloadSiteState();
    }

    // The generic modules have no dependents among the modules under test
    // any more, so they go last, also one at a time. Each one before the
    // module it depends on: svg_image_responsive requires responsive_image.
    foreach (['entity_popover_swiffy_slider', 'entity_popover', 'svg_image_responsive', 'responsive_image'] as $module) {
      if ($this->moduleIsInstalled($module)) {
        $this->uninstallModulesThroughUi([$module]);
        $this->reloadSiteState();
      }
    }
  }

}
