<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

use Drupal\openculturas_responsive_images\SharedConfigSwitcher;

/**
 * Scenario D: what happens to a site whose displays were changed by hand.
 *
 * Both displays the scenario changes are switched by the responsive images
 * module: a gallery display with a different formatter, view mode, region and
 * weight, and a media display with a different image style and formatter
 * settings. SharedConfigSwitcher treats both as deviations. Switching leaves
 * them alone unless the site builder checks their "Overwrite" box, and a
 * revert after an approved overwrite restores the OpenCulturas standard, not
 * the site's own value.
 *
 * Every step is asserted, and what happened to each component is also written
 * to scenario-D-observations.txt.
 */
final class ScenarioDDivergentStartTest extends LifecycleBase {

  private const string RESPONSIVE_IMAGES = 'openculturas_responsive_images';

  private const string ENTITY_POPOVER = 'openculturas_entity_popover';

  private const string SETTINGS_PATH = '/admin/config/media/openculturas_responsive_images';

  /**
   * A gallery display the responsive images module switches.
   */
  private const string GALLERY_DISPLAY = 'paragraph.gallery.default';

  /**
   * A media image display the responsive images module switches.
   */
  private const string MEDIA_DISPLAY = 'media.image.content_asset';

  /**
   * The field both displays render.
   */
  private const string FIELD_NAME = 'field_gallery';

  /**
   * The field the media display renders.
   */
  private const string MEDIA_FIELD_NAME = 'field_media_image';

  /**
   * The component key of the gallery display.
   */
  private const string GALLERY_KEY = 'entity_view_display:' . self::GALLERY_DISPLAY;

  /**
   * The component key of the media display.
   */
  private const string MEDIA_KEY = 'entity_view_display:' . self::MEDIA_DISPLAY;

  /**
   * The name of the media display's overwrite checkbox.
   */
  private const string MEDIA_OVERWRITE_FIELD = 'deviations[' . self::MEDIA_KEY . '][overwrite]';

  /**
   * The OpenCulturas standard formatter settings of the media display.
   *
   * The legacy settings of media.image.content_asset in SharedConfigSwitcher.
   */
  private const array MEDIA_STANDARD_SETTINGS = [
    'image_link' => '',
    'image_style' => 'content_image',
    'svg_attributes' => ['width' => 680, 'height' => NULL],
    'svg_render_as_image' => TRUE,
    'image_loading' => ['attribute' => 'lazy'],
  ];

  /**
   * The component properties a switch must leave alone on a deviation.
   */
  private const array COMPARED_PROPERTIES = ['type', 'settings', 'region', 'weight'];

  /**
   * What happened to each adjustment, one line per step.
   *
   * @var list<string>
   */
  private array $observations = [];

  /**
   * The gallery component as the site configured it, after the install.
   *
   * @var array<string, mixed>
   */
  private array $divergentGallery = [];

  /**
   * The media component as the site configured it, after the install.
   *
   * @var array<string, mixed>
   */
  private array $divergentMedia = [];

  /**
   * Walks hand-made display changes through switch, revert and uninstall.
   */
  public function testDivergentDisplayConfiguration(): void {
    $this->loginWithRole('administrator');

    // The observations are written even when a step fails, so the file shows
    // how far the scenario got.
    try {
      $this->diverge();
      $this->recordPhase('D1-divergent');

      $this->install();
      $this->recordPhase('D2-installed');

      $this->switchWithoutApproval();
      $this->recordPhase('D3-switched');

      $this->switchWithMediaApproved();
      $this->recordPhase('D4-switched-approved');

      $this->revertToFixedStyles();
      $this->recordPhase('D5-reverted');

      $this->uninstall();
      $this->recordPhase('D6-uninstalled');
    }
    finally {
      $this->writeObservations();
    }

    $this->assertNoNewWatchdogErrors();
  }

  /**
   * Installs both modules and checks that both deviations are listed.
   */
  private function install(): void {
    $this->installModulesThroughUi([self::RESPONSIVE_IMAGES, self::ENTITY_POPOVER]);
    $this->reloadSiteState();

    $this->divergentGallery = $this->component(self::GALLERY_DISPLAY, self::FIELD_NAME);
    $this->divergentMedia = $this->component(self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);

    $this->assertEqualsCanonicalizing([self::GALLERY_KEY, self::MEDIA_KEY], array_keys($this->switcher()->deviations()), 'Exactly the two changed displays are deviations.');

    $this->drupalGet(self::SETTINGS_PATH);
    foreach ([self::GALLERY_KEY, self::MEDIA_KEY] as $key) {
      $this->assertSession()->pageTextContains($key);
      $this->assertSession()->fieldExists('deviations[' . $key . '][overwrite]');
    }

    $this->observeComponent('after install', self::GALLERY_DISPLAY, self::FIELD_NAME);
    $this->observeComponent('after install', self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);
  }

  /**
   * Switches without approving a deviation: both stay as they are.
   */
  private function switchWithoutApproval(): void {
    $this->drupalGet(self::SETTINGS_PATH);
    $this->submitForm([], 'Switch to responsive images');
    $this->assertSession()->pageTextContains('2 components with own settings were left unchanged. They are listed below.');
    $this->reloadSiteState();

    $this->assertComponentUnchanged($this->divergentGallery, self::GALLERY_DISPLAY, self::FIELD_NAME);
    $this->assertComponentUnchanged($this->divergentMedia, self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);

    $otherStates = array_diff_key($this->switcher()->componentStates(), [self::GALLERY_KEY => TRUE, self::MEDIA_KEY => TRUE]);
    $this->assertNotSame([], $otherStates, 'The other switched components have a state.');
    foreach ($otherStates as $key => $state) {
      $this->assertSame(SharedConfigSwitcher::SWITCHED, $state, sprintf('%s is switched.', $key));
    }

    $this->assertTrue(
      $this->switcher()->isResponsive(),
      'The button switched the displays: SharedConfigSwitcher::isResponsive() reports TRUE.',
    );

    $this->observeComponent('after switch', self::GALLERY_DISPLAY, self::FIELD_NAME);
    $this->observeComponent('after switch', self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);
  }

  /**
   * Switches again with only the media display approved.
   */
  private function switchWithMediaApproved(): void {
    $this->drupalGet(self::SETTINGS_PATH);
    $this->submitForm([self::MEDIA_OVERWRITE_FIELD => TRUE], 'Switch to responsive images');
    $this->reloadSiteState();

    $media = $this->component(self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);
    $this->assertSame('responsive_image', $media['type'] ?? NULL, 'The approved media display uses the responsive image formatter.');
    $this->assertSame('oc_bounds_800', $media['settings']['responsive_image_style'] ?? NULL, 'The approved media display uses oc_bounds_800.');
    $this->assertSame($this->divergentMedia['region'] ?? NULL, $media['region'] ?? NULL, 'The approved media display keeps its region.');
    $this->assertSame($this->divergentMedia['weight'] ?? NULL, $media['weight'] ?? NULL, 'The approved media display keeps its weight.');

    $this->assertComponentUnchanged($this->divergentGallery, self::GALLERY_DISPLAY, self::FIELD_NAME);

    $this->observeComponent('after approved switch', self::GALLERY_DISPLAY, self::FIELD_NAME);
    $this->observeComponent('after approved switch', self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);
  }

  /**
   * Reverts: the approved media display gets the OpenCulturas standard.
   */
  private function revertToFixedStyles(): void {
    $this->drupalGet(self::SETTINGS_PATH);
    $this->submitForm([], 'Revert to fixed image styles');
    $this->reloadSiteState();

    // The site's own image style "large" does not come back: the revert sets
    // the OpenCulturas standard, as the warning on the form says.
    $this->assertMediaHasStandard('after revert');
    $this->assertComponentUnchanged($this->divergentGallery, self::GALLERY_DISPLAY, self::FIELD_NAME);

    $this->observeComponent('after revert', self::GALLERY_DISPLAY, self::FIELD_NAME);
    $this->observeComponent('after revert', self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);
  }

  /**
   * Uninstalls the responsive images module: nothing changes any more.
   */
  private function uninstall(): void {
    $this->uninstallModulesThroughUi([self::RESPONSIVE_IMAGES]);
    $this->reloadSiteState();

    $this->assertComponentUnchanged($this->divergentGallery, self::GALLERY_DISPLAY, self::FIELD_NAME);
    $this->assertMediaHasStandard('after uninstall');

    $this->observeComponent('after uninstall', self::GALLERY_DISPLAY, self::FIELD_NAME);
    $this->observeComponent('after uninstall', self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);
  }

  /**
   * Asserts that the media display holds the OpenCulturas standard formatter.
   *
   * @param string $step
   *   The step of the scenario, for the assertion messages.
   */
  private function assertMediaHasStandard(string $step): void {
    $media = $this->component(self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME);
    $this->assertSame('image', $media['type'] ?? NULL, sprintf('%s: the media display uses the image formatter.', $step));
    $this->assertEquals(self::MEDIA_STANDARD_SETTINGS, $media['settings'] ?? NULL, sprintf('%s: the media display has the OpenCulturas standard settings.', $step));
    $this->assertSame($this->divergentMedia['region'] ?? NULL, $media['region'] ?? NULL, sprintf('%s: the media display keeps its region.', $step));
    $this->assertSame($this->divergentMedia['weight'] ?? NULL, $media['weight'] ?? NULL, sprintf('%s: the media display keeps its weight.', $step));
  }

  /**
   * Asserts that formatter, settings, region and weight are as before.
   *
   * @param array<string, mixed> $expected
   *   The component as the site configured it.
   * @param string $displayId
   *   The display id.
   * @param string $fieldName
   *   The field whose component to check.
   */
  private function assertComponentUnchanged(array $expected, string $displayId, string $fieldName): void {
    $component = $this->component($displayId, $fieldName);
    foreach (self::COMPARED_PROPERTIES as $property) {
      $this->assertEquals($expected[$property] ?? NULL, $component[$property] ?? NULL, sprintf('%s.%s keeps its %s.', $displayId, $fieldName, $property));
    }
  }

  /**
   * Loads a display component.
   *
   * @param string $displayId
   *   The display id.
   * @param string $fieldName
   *   The field whose component to load.
   *
   * @return array<string, mixed>
   *   The component.
   */
  private function component(string $displayId, string $fieldName): array {
    $component = $this->display($displayId)?->getComponent($fieldName);
    $this->assertIsArray($component, sprintf('%s renders %s.', $displayId, $fieldName));
    return $component;
  }

  /**
   * The shared config switcher of the current container.
   */
  private function switcher(): SharedConfigSwitcher {
    return $this->container->get(SharedConfigSwitcher::class);
  }

  /**
   * Changes a gallery and a media display away from the profile's baseline.
   *
   * Each change is a different kind of edit: a different formatter, different
   * formatter settings, a different region and a different weight.
   */
  private function diverge(): void {
    $gallery = $this->display(self::GALLERY_DISPLAY);
    $this->assertNotNull($gallery, 'The gallery display exists on this site.');

    $component = $gallery->getComponent(self::FIELD_NAME);
    $this->assertIsArray($component, 'The gallery display renders its field.');

    // A formatter the module knows nothing about, at a region and weight the
    // profile does not use. Switching to responsive images claims to keep
    // everything but the formatter type and settings - this display has all
    // three kinds of difference at once.
    $this->divergeDisplay(self::GALLERY_DISPLAY, self::FIELD_NAME, $component, [
      'type' => 'entity_reference_revisions_entity_view',
      'settings' => ['view_mode' => 'teaser_node'],
      'region' => 'sidebar',
      'weight' => 42,
    ]);

    $media = $this->display(self::MEDIA_DISPLAY);
    $this->assertNotNull($media, 'The media display exists on this site.');

    $mediaComponent = $media->getComponent(self::MEDIA_FIELD_NAME);
    $this->assertIsArray($mediaComponent, 'The media display renders its image field.');

    // The same kinds of change, on the other kind of display.
    $this->divergeDisplay(self::MEDIA_DISPLAY, self::MEDIA_FIELD_NAME, $mediaComponent, [
      'type' => 'image',
      'settings' => [
        'image_link' => 'file',
        'image_style' => 'large',
        'image_loading' => ['attribute' => 'eager'],
        'svg_attributes' => ['width' => 320, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
    ]);
  }

  /**
   * Writes one display component with the given formatter, region and weight.
   *
   * @param string $displayId
   *   The display the component belongs to.
   * @param string $fieldName
   *   The field the component belongs to.
   * @param array<string, mixed> $component
   *   The component as it is now.
   * @param array<string, mixed> $replacement
   *   The formatter type, settings and, where given, region and weight.
   */
  private function divergeDisplay(string $displayId, string $fieldName, array $component, array $replacement): void {
    $component['type'] = $replacement['type'];
    $component['settings'] = $replacement['settings'];
    if (isset($replacement['region'])) {
      $component['region'] = $replacement['region'];
    }

    if (isset($replacement['weight'])) {
      $component['weight'] = $replacement['weight'];
    }

    $this->display($displayId)?->setComponent($fieldName, $component)->save();
  }

  /**
   * Records the formatter, region and weight of one component.
   *
   * A missing component is recorded as "(none)" rather than failing the test,
   * because whether it survives is what this scenario observes.
   *
   * @param string $step
   *   The step of the scenario the observation belongs to.
   * @param string $displayId
   *   The display id.
   * @param string $fieldName
   *   The field whose component to observe.
   */
  private function observeComponent(string $step, string $displayId, string $fieldName): void {
    $component = $this->display($displayId)?->getComponent($fieldName) ?? [];

    $this->observations[] = sprintf(
      '%s | %s.%s | type=%s region=%s weight=%s settings=%s',
      $step,
      $displayId,
      $fieldName,
      (string) ($component['type'] ?? '(none)'),
      (string) ($component['region'] ?? '(none)'),
      (string) ($component['weight'] ?? '(none)'),
      (string) json_encode($component['settings'] ?? []),
    );
  }

  /**
   * Writes the observations next to the recorded phases.
   */
  private function writeObservations(): void {
    $path = $this->observationPath('scenario-D-observations.txt');
    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0o777, TRUE) && !is_dir(dirname($path))) {
      $this->fail('Could not create the observation directory.');
    }

    file_put_contents(
      $path,
      "Scenario D: what happened to hand-made display changes.\n\n"
      . implode("\n", $this->observations) . "\n",
    );
  }

  /**
   * Loads a display.
   *
   * @param string $displayId
   *   The display id.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay|null
   *   The display.
   */
  private function display(string $displayId): ?object {
    return $this->container->get('entity_type.manager')->getStorage('entity_view_display')->load($displayId);
  }

}
