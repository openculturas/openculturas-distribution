<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas_entity_popover\ExistingSite;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\openculturas_entity_popover\DisplayWirer;
use PHPUnit\Framework\Attributes\Group;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Checks that wiring only stores popover settings the live formatter has.
 *
 * DisplayWirer offers the dialog the bigger view mode and a single-item
 * slider, which only mean something to a slider formatter. On a site whose
 * field_gallery runs on another formatter, storing them anyway would be a
 * config schema violation: the schema types the popover settings by the
 * formatter saved next to them.
 *
 * The wirer is called directly on the installed module and only changes
 * display config, so the test never touches the popover values of the site's
 * own paragraphs. Every config object it changes is restored in tearDown().
 */
#[Group('openculturas')]
final class DisplayWirerFormatterSettingsTest extends ExistingSiteBase {

  /**
   * The module under test.
   */
  private const string MODULE = 'openculturas_entity_popover';

  /**
   * The prefix of the entity view display config names.
   */
  private const string VIEW_DISPLAY_PREFIX = 'core.entity_view_display.';

  /**
   * A display from DisplayWirer::FIELD_GALLERY_DISPLAYS the test rewrites.
   */
  private const string DISPLAY = 'node.event.full';

  /**
   * The field the module wires on that display.
   */
  private const string FIELD_NAME = 'field_gallery';

  /**
   * The slider preset the wirer wants the popover to render with.
   */
  private const string SLIDER_PERMALINK = 'https://swiffyslider.com/configuration/?slider-item-ratio-value=slider-item-ratio-1x1&slider-nav-dark=slider-nav-dark&slider-nav-arrows=slider-nav-outside&slider-nav-visible=slider-nav-visible&slider-nav-autohide=slider-nav-autohide&slider-indicators-dark=slider-indicators-dark&slider-indicators-outside=slider-indicators-outside&slider-indicators-sm=slider-indicators-sm&slider-nav-animation-style=slider-nav-animation-scale';

  /**
   * The view mode the wirer wants the popover to render in.
   */
  private const string POPOVER_VIEW_MODE = 'gallery_big';

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
    foreach ($this->viewDisplayNames() as $configurationName) {
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
   * A field_gallery on a non-slider formatter gets no slider settings.
   */
  public function testWiringSkipsSettingsTheLiveFormatterDoesNotHave(): void {
    $this->useFormatter('entity_reference_entity_view');

    $settings = $this->wire();

    $this->assertSame('entity_reference_entity_view', $settings['details']['formatter']);
    $this->assertSame(self::POPOVER_VIEW_MODE, $settings['details']['formatter_settings']['view_mode']);
    $this->assertArrayNotHasKey(
      'swiffy_slider_permalink',
      $settings['details']['formatter_settings'],
      'The slider preset is not a setting of entity_reference_entity_view.',
    );
  }

  /**
   * A field_gallery on the slider formatter is wired exactly as before.
   */
  public function testSliderFormatterStillGetsBothSettings(): void {
    $this->useFormatter('swiffy_slider_entity_reference');

    $settings = $this->wire();

    $this->assertSame('swiffy_slider_entity_reference', $settings['details']['formatter']);
    $this->assertSame(self::POPOVER_VIEW_MODE, $settings['details']['formatter_settings']['view_mode']);
    $this->assertSame(self::SLIDER_PERMALINK, $settings['details']['formatter_settings']['swiffy_slider_permalink']);
  }

  /**
   * Wires the display and returns the popover settings that landed on it.
   *
   * @return array<string, mixed>
   *   The entity_popover_swiffy_slider third-party settings.
   */
  private function wire(): array {
    $this->container->get(DisplayWirer::class)->wireAll();

    $display = EntityViewDisplay::load(self::DISPLAY);
    $this->assertInstanceOf(EntityViewDisplay::class, $display);

    $component = $display->getComponent(self::FIELD_NAME);
    $this->assertIsArray($component);

    return $component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER];
  }

  /**
   * Puts a formatter of the test's choosing on field_gallery of the display.
   *
   * Layout Builder off and the field as a display component, so the wirer
   * takes the path a site without Layout Builder takes.
   *
   * @param string $formatterType
   *   The formatter plugin id to put on field_gallery.
   */
  private function useFormatter(string $formatterType): void {
    $display = EntityViewDisplay::load(self::DISPLAY);
    $this->assertInstanceOf(LayoutBuilderEntityViewDisplay::class, $display);
    $display->disableLayoutBuilder()->save();

    // Reloaded, so that the display is the plain one the wirer reads.
    $display = EntityViewDisplay::load(self::DISPLAY);
    $this->assertInstanceOf(EntityViewDisplay::class, $display);

    $display->setComponent(self::FIELD_NAME, [
      'type' => $formatterType,
      'label' => 'hidden',
      'settings' => ['view_mode' => 'gallery', 'swiffy_slider_permalink' => NULL],
      'third_party_settings' => [],
      'region' => 'content',
    ])->save();
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

}
