<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_popover_swiffy_slider\ExistingSite;

use Drupal\entity_popover_swiffy_slider\PopoverFormatterSettings;
use PHPUnit\Framework\Attributes\Group;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Checks that popover settings are reduced to what the formatter defines.
 *
 * The stored settings are typed by the stored formatter in the config
 * schema, so a key the formatter does not have is a schema violation in
 * exported config. PopoverFormatterSettings is the single place that decides
 * which keys survive, for the settings form, the installer's wiring, and
 * rendering alike.
 */
#[Group('openculturas')]
final class PopoverFormatterSettingsTest extends ExistingSiteBase {

  /**
   * A setting the swiffy slider formatter defines.
   */
  private const string SLIDER_SETTING = 'swiffy_slider_permalink';

  /**
   * A setting only an entity view formatter defines.
   */
  private const string VIEW_MODE_SETTING = 'view_mode';

  /**
   * The module's own popover service under test.
   */
  private PopoverFormatterSettings $popoverFormatterSettings;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();

    $this->popoverFormatterSettings = $this->container->get(PopoverFormatterSettings::class);
  }

  /**
   * A key the chosen formatter does not define does not survive.
   */
  public function testSettingsUnknownToTheFormatterAreDropped(): void {
    $settings = $this->popoverFormatterSettings->filter('entity_reference_entity_view', [
      self::VIEW_MODE_SETTING => 'gallery_big',
      self::SLIDER_SETTING => 'https://example.com/slider',
    ]);

    $this->assertArrayHasKey(self::VIEW_MODE_SETTING, $settings);
    $this->assertSame('gallery_big', $settings[self::VIEW_MODE_SETTING]);
    $this->assertArrayNotHasKey(
      self::SLIDER_SETTING,
      $settings,
      'The slider preset is not a setting of entity_reference_entity_view.',
    );
  }

  /**
   * A key the chosen formatter defines but nobody filled in gets its default.
   */
  public function testMissingSettingsAreFilledWithTheFormatterDefaults(): void {
    $settings = $this->popoverFormatterSettings->filter('swiffy_slider_entity_reference', [
      self::SLIDER_SETTING => 'https://example.com/slider',
    ]);

    $this->assertSame('https://example.com/slider', $settings[self::SLIDER_SETTING]);
    $this->assertArrayHasKey(
      self::VIEW_MODE_SETTING,
      $settings,
      'The omitted view mode came from the formatter defaults.',
    );
  }

  /**
   * A formatter nobody can resolve has no settings at all.
   */
  public function testUnknownFormatterTypeYieldsNoSettings(): void {
    $this->assertSame([], $this->popoverFormatterSettings->filter('no_such_formatter', [
      self::VIEW_MODE_SETTING => 'gallery_big',
      self::SLIDER_SETTING => 'https://example.com/slider',
    ]));
    $this->assertSame([], $this->popoverFormatterSettings->filter('', []));
  }

}
