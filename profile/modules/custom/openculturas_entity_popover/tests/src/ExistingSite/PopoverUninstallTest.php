<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas_entity_popover\ExistingSite;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\openculturas_entity_popover\DisplayWirer;
use Drupal\openculturas_entity_popover\NodeDisplayUnwirer;
use PHPUnit\Framework\Attributes\Group;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Checks that unwiring only removes the popover wiring the module created.
 *
 * NodeDisplayUnwirer is the counterpart of DisplayWirer: it takes back what
 * DisplayWirer put on the node displays and nothing else. entity_popover and
 * entity_popover_swiffy_slider stay installed when openculturas_entity_popover
 * is uninstalled, so a popover a site builder set up stays a valid setting
 * afterwards and has to survive.
 *
 * The unwirer is called directly rather than by uninstalling the module, so
 * the test never deletes the popover values of the site's own paragraphs.
 */
#[Group('openculturas')]
final class PopoverUninstallTest extends ExistingSiteBase {

  /**
   * The module under test.
   */
  private const string MODULE = 'openculturas_entity_popover';

  /**
   * The prefix of the entity view display config names.
   */
  private const string VIEW_DISPLAY_PREFIX = 'core.entity_view_display.';

  /**
   * A Layout Builder display from DisplayWirer::FIELD_GALLERY_DISPLAYS.
   */
  private const string LAYOUT_BUILDER_DISPLAY = 'node.profile.full';

  /**
   * A field block on that display, next to the field_gallery one.
   */
  private const string LAYOUT_BUILDER_BLOCK = 'field_block:node:profile:field_logo';

  /**
   * A node display the module never wires.
   */
  private const string PLAIN_DISPLAY = 'node.profile.teaser';

  /**
   * A field component on that display.
   */
  private const string PLAIN_FIELD = 'field_portrait';

  /**
   * A display from DisplayWirer::FIELD_GALLERY_DISPLAYS.
   *
   * The test switches it from Layout Builder to a plain display component.
   */
  private const string COMPONENT_DISPLAY = 'node.event.full';

  /**
   * The field the module wires on COMPONENT_DISPLAY.
   */
  private const string WIRED_FIELD = 'field_gallery';

  /**
   * A second field on COMPONENT_DISPLAY, for the site builder's popover.
   */
  private const string BUILDER_FIELD = 'field_people_reference';

  /**
   * The formatter the display components of the tests are written with.
   */
  private const array COMPONENT_FORMATTERS = [
    self::WIRED_FIELD => [
      'type' => 'swiffy_slider_entity_reference',
      'settings' => ['view_mode' => 'gallery', 'swiffy_slider_permalink' => NULL],
    ],
    self::BUILDER_FIELD => [
      'type' => 'entity_reference_revisions_entity_view',
      'settings' => ['view_mode' => 'default'],
    ],
  ];

  /**
   * The raw config data of every display the module may touch.
   *
   * @var array<string, array<string, mixed>>
   */
  private array $originalConfiguration = [];

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();

    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists(self::MODULE),
      'The module ' . self::MODULE . ' is not installed. The local site has to match config/sync.',
    );

    $configurationFactory = \Drupal::configFactory();
    foreach ($this->watchedConfigurationNames() as $configurationName) {
      if ($configurationFactory->get($configurationName)->isNew()) {
        continue;
      }

      $this->originalConfiguration[$configurationName] = $configurationFactory->get($configurationName)->getRawData();
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function tearDown(): void {
    // The config snapshot is taken before the test changes anything, so
    // restoring it undoes every wiring, unwiring and popover of the test.
    $configurationFactory = \Drupal::configFactory();
    foreach ($this->originalConfiguration as $configurationName => $data) {
      $configuration = $configurationFactory->getEditable($configurationName);
      if ($configuration->getRawData() !== $data) {
        $configuration->setData($data)->save();
      }
    }

    parent::tearDown();
  }

  /**
   * The Layout Builder field_gallery popovers go, everything else stays.
   *
   * All six wired displays run on Layout Builder, so the wiring lives in the
   * formatter of the field_gallery block there.
   */
  public function testUnwiringRemovesTheFieldGalleryBlockPopover(): void {
    $wired = $this->popoverSettingsByBlock();
    foreach (DisplayWirer::FIELD_GALLERY_DISPLAYS as $displayId) {
      $this->assertContains(
        DisplayWirer::fieldGalleryBlockId($displayId),
        $wired[$displayId] ?? [],
        'The module wired field_gallery on ' . $displayId . '.',
      );
    }

    // Two popovers a site builder set up: one on a block of a display the
    // module wires, one on a display the module never touches.
    $this->setBlockPopover(self::LAYOUT_BUILDER_DISPLAY, self::LAYOUT_BUILDER_BLOCK);
    $this->setComponentPopover(self::PLAIN_DISPLAY, self::PLAIN_FIELD);

    $this->unwire();

    $remaining = $this->popoverSettingsByBlock();
    foreach (DisplayWirer::FIELD_GALLERY_DISPLAYS as $displayId) {
      $this->assertNotContains(
        DisplayWirer::fieldGalleryBlockId($displayId),
        $remaining[$displayId] ?? [],
        'The field_gallery popover is gone from ' . $displayId . '.',
      );
    }

    $this->assertContains(
      self::LAYOUT_BUILDER_BLOCK,
      $remaining[self::LAYOUT_BUILDER_DISPLAY] ?? [],
      'The popover on the block of another field stayed.',
    );

    $this->assertTrue(
      $this->hasComponentPopover(self::PLAIN_DISPLAY, self::PLAIN_FIELD),
      'The popover on the component of another display stayed.',
    );
    $this->assertTrue(
      $this->dependsOnPopoverModule(self::PLAIN_DISPLAY),
      'The display still depends on the module the remaining popover setting comes from.',
    );
  }

  /**
   * The field_gallery popover goes from a plain display component too.
   *
   * Sites updated from releases before Layout Builder, and displays where a
   * site builder turned Layout Builder off again, keep the regular display
   * component instead of the field block.
   */
  public function testUnwiringRemovesTheFieldGalleryComponentPopover(): void {
    $this->switchToDisplayComponents(self::COMPONENT_DISPLAY);
    $this->container->get(DisplayWirer::class)->wireAll();

    $this->assertTrue(
      $this->hasComponentPopover(self::COMPONENT_DISPLAY, self::WIRED_FIELD),
      'The module wired field_gallery as a display component on the plain display.',
    );

    // The site builder's own popover, on another field of the same display.
    $this->setComponentPopover(self::COMPONENT_DISPLAY, self::BUILDER_FIELD);

    $this->unwire();

    $this->assertFalse(
      $this->hasComponentPopover(self::COMPONENT_DISPLAY, self::WIRED_FIELD),
      'The field_gallery popover is gone from the plain display component.',
    );
    $this->assertTrue(
      $this->hasComponentPopover(self::COMPONENT_DISPLAY, self::BUILDER_FIELD),
      'The popover on another field of the same display stayed.',
    );
    $this->assertTrue(
      $this->dependsOnPopoverModule(self::COMPONENT_DISPLAY),
      'The display still depends on the module the remaining popover setting comes from.',
    );
  }

  /**
   * Unwires the node displays, the way the uninstall hook does.
   */
  private function unwire(): void {
    $this->container->get(NodeDisplayUnwirer::class)->unwireAll();
  }

  /**
   * Turns a wired display into a plain one showing field_gallery as component.
   *
   * Layout Builder off, and the two fields the test needs in the content
   * region. The field_gallery formatter mirrors the one the field block of
   * the Layout Builder display uses, so both paths are the same formatter
   * seen through different storage.
   *
   * @param string $displayId
   *   The config name of the node display to change.
   */
  private function switchToDisplayComponents(string $displayId): void {
    $display = EntityViewDisplay::load($displayId);
    $this->assertInstanceOf(LayoutBuilderEntityViewDisplay::class, $display);
    $display->disableLayoutBuilder()->save();

    // Reloaded, so that the display is the plain one the module reads.
    $display = EntityViewDisplay::load($displayId);
    $this->assertInstanceOf(EntityViewDisplay::class, $display);

    foreach (self::COMPONENT_FORMATTERS as $fieldName => $formatter) {
      $display->setComponent($fieldName, $formatter + [
        'label' => 'hidden',
        'third_party_settings' => [],
        'region' => 'content',
      ])->save();
    }
  }

  /**
   * Whether a display component carries a popover setting.
   *
   * @param string $displayId
   *   The config name of the node display to look at.
   * @param string $fieldName
   *   The field whose component to look at.
   *
   * @return bool
   *   TRUE if the component has the entity_popover_swiffy_slider setting.
   */
  private function hasComponentPopover(string $displayId, string $fieldName): bool {
    $display = EntityViewDisplay::load($displayId);
    $this->assertNotNull($display);

    $component = $display->getComponent($fieldName);
    return isset($component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER]);
  }

  /**
   * Whether a display still depends on the popover settings module.
   *
   * @param string $displayId
   *   The config name of the node display to look at.
   *
   * @return bool
   *   TRUE if the display lists entity_popover_swiffy_slider as a module
   *   dependency.
   */
  private function dependsOnPopoverModule(string $displayId): bool {
    $display = EntityViewDisplay::load($displayId);
    $this->assertNotNull($display);

    return in_array(DisplayWirer::THIRD_PARTY_PROVIDER, $display->getDependencies()['module'] ?? [], TRUE);
  }

  /**
   * Sets a popover on a field block, the way a site builder would.
   *
   * @param string $displayId
   *   The config name of the Layout Builder node display.
   * @param string $blockId
   *   The id of the block to put the popover on.
   */
  private function setBlockPopover(string $displayId, string $blockId): void {
    $display = EntityViewDisplay::load($displayId);
    $this->assertInstanceOf(LayoutBuilderEntityViewDisplay::class, $display);
    $this->assertTrue($display->isLayoutBuilderEnabled());

    $found = FALSE;
    foreach ($this->popoverComponents($display) as $component) {
      $configuration = $component->get('configuration');
      if (($configuration['id'] ?? '') !== $blockId) {
        continue;
      }

      $configuration['formatter']['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER] = $this->popoverConfiguration();
      $component->set('configuration', $configuration);
      $found = TRUE;
    }

    $this->assertTrue($found, 'The display has the block the test needs.');
    $display->save();
  }

  /**
   * Sets a popover on a display component, the way a site builder would.
   *
   * @param string $displayId
   *   The config name of the node display.
   * @param string $fieldName
   *   The field to put the popover on.
   */
  private function setComponentPopover(string $displayId, string $fieldName): void {
    $display = EntityViewDisplay::load($displayId);
    $this->assertNotNull($display);

    $component = $display->getComponent($fieldName);
    $this->assertIsArray($component, 'The display has the field the test needs.');

    $component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER] = $this->popoverConfiguration();
    $display->setComponent($fieldName, $component)->save();
  }

  /**
   * A popover setting, as a site builder saves it.
   *
   * @return array<string, mixed>
   *   The entity_popover_swiffy_slider third-party settings.
   */
  private function popoverConfiguration(): array {
    return [
      'enable' => TRUE,
      'details' => ['formatter' => 'entity_reference_entity_view'],
    ];
  }

  /**
   * The block ids carrying a popover, per display.
   *
   * @return array<string, list<string>>
   *   Block ids that have an entity_popover_swiffy_slider setting, keyed by the
   *   display id they live on.
   */
  private function popoverSettingsByBlock(): array {
    $found = [];

    foreach ($this->viewDisplayNames() as $configurationName) {
      // Config entity storage prepends the prefix itself, so the entity is
      // loaded by the display id the module works with.
      $displayId = substr($configurationName, strlen(self::VIEW_DISPLAY_PREFIX));
      $display = EntityViewDisplay::load($displayId);
      if ($display === NULL) {
        continue;
      }

      foreach ($this->popoverComponents($display) as $component) {
        $configuration = $component->get('configuration');
        if (isset($configuration['formatter']['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER])) {
          $found[$displayId][] = $configuration['id'];
        }
      }
    }

    return $found;
  }

  /**
   * The Layout Builder components of a display.
   *
   * @param \Drupal\Core\Entity\Entity\EntityViewDisplay $display
   *   The display to read. Displays that do not render their fields as Layout
   *   Builder blocks have no components.
   *
   * @return list<\Drupal\layout_builder\SectionComponent>
   *   The components of the display's sections.
   */
  private function popoverComponents(EntityViewDisplay $display): array {
    if (!$display instanceof LayoutBuilderEntityViewDisplay || !$display->isLayoutBuilderEnabled()) {
      return [];
    }

    $components = [];
    foreach ($display->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        $components[] = $component;
      }
    }

    return $components;
  }

  /**
   * The config names of the entity view displays the module may wire.
   *
   * @return list<string>
   *   The entity view display config names of node and paragraph.
   */
  private function viewDisplayNames(): array {
    return array_values(array_merge(
      \Drupal::configFactory()->listAll(self::VIEW_DISPLAY_PREFIX . 'node.'),
      \Drupal::configFactory()->listAll(self::VIEW_DISPLAY_PREFIX . 'paragraph.'),
    ));
  }

  /**
   * The config names of the displays the test may change.
   *
   * @return list<string>
   *   The entity view and form display config names of node and paragraph.
   */
  private function watchedConfigurationNames(): array {
    return array_values(array_merge(
      $this->viewDisplayNames(),
      \Drupal::configFactory()->listAll('core.entity_form_display.node.'),
      \Drupal::configFactory()->listAll('core.entity_form_display.paragraph.'),
    ));
  }

}
