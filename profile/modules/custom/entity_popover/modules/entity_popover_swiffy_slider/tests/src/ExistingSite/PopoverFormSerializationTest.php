<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_popover_swiffy_slider\ExistingSite;

use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_popover_swiffy_slider\FieldPopoverBuilder;
use Drupal\entity_popover_swiffy_slider\Hook\EntityPopoverSwiffySliderHooks;
use Drupal\entity_popover_swiffy_slider\PopoverFormatterSettings;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Checks that the popover settings form can be written to the form cache.
 *
 * Both contexts this form is embedded in — Field UI's per-field settings
 * subform and Layout Builder's block form — submit through the form API, and
 * every such request serializes the form into the form cache. A callback that
 * closes over a service makes that write fail with "The database connection
 * is not serializable", because the plugin manager behind it reaches the
 * database connection: a 500 on the very first click, with no error anywhere
 * in the test suite.
 *
 * So this is checked on the element the hook actually hands out, and on a
 * real form builder run that gets as far as the cache write — the second one
 * catches what the plain serialize() below would not, because the failure
 * happens on the rebuild path and only for the exact nesting a context uses.
 */
#[Group('openculturas')]
final class PopoverFormSerializationTest extends ExistingSiteBase {

  /**
   * An entity reference field on a node bundle without a native popover field.
   */
  private const string FIELD_NAME = 'field_portrait';

  /**
   * The bundle of that field.
   */
  private const string BUNDLE = 'profile';

  /**
   * The formatter the popover is configured with.
   */
  private const string SLIDER_FORMATTER = 'swiffy_slider_entity_reference';

  /**
   * The id of the probe form, and with it of its build.
   */
  public const string FORM_ID = 'entity_popover_swiffy_slider_serialization_probe';

  /**
   * The form builder's build id to start the probe with.
   */
  private const string FORM_BUILD_ID = 'form-entity-popover-serialization-probe';

  /**
   * The settings element under test.
   */
  private EntityPopoverSwiffySliderHooks $hooks;

  /**
   * The build id the probe form ended up in the cache under.
   *
   * @var string|null
   */
  private ?string $cachedBuildId = NULL;

  /**
   * Whether this test pushed a request of its own onto the request stack.
   */
  private bool $requestPushed = FALSE;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();

    $this->hooks = new EntityPopoverSwiffySliderHooks(
      $this->container->get('plugin.manager.field.formatter'),
      $this->container->get(PopoverFormatterSettings::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function tearDown(): void {
    // The probe form really ends up in the form cache; leave nothing behind.
    if ($this->requestPushed) {
      $this->container->get('request_stack')->pop();
      $this->requestPushed = FALSE;
    }

    if ($this->cachedBuildId !== NULL) {
      foreach (['form', 'form_state'] as $collection) {
        $this->container->get('keyvalue.expirable')->get($collection)->delete($this->cachedBuildId);
      }

      $this->cachedBuildId = NULL;
    }

    parent::tearDown();
  }

  /**
   * The element survives being put through serialize().
   */
  public function testHookElementIsSerializable(): void {
    // Nothing but arrays is expected in here, so no class is allowed back in.
    $restored = unserialize(serialize($this->fieldUiHookElement()), ['allowed_classes' => FALSE]);

    $this->assertIsArray($restored);
    $this->assertArrayHasKey('details', $restored);
  }

  /**
   * A real form builder run gets as far as writing the form cache.
   */
  public function testFormCacheWriteSucceeds(): void {
    $form = [
      '#tree' => TRUE,
      'settings' => ['third_party_settings' => $this->fieldUiHookElement()],
    ];

    // The form builder refuses to cache a form state on a GET request, and a
    // submitted form of either context is a POST — so the probe has to be
    // one, down to the request stack the form builder reads it from.
    $request = Request::create('/entity-popover-serialization-probe', 'POST');
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->container->get('request_stack')->push($request);
    $this->requestPushed = TRUE;

    $formState = new FormState();
    $formState->setUserInput([
      'form_id' => self::FORM_ID,
      'form_build_id' => self::FORM_BUILD_ID,
    ]);

    PopoverSerializationProbeForm::$formArray = $form;
    $built = $this->container->get('form_builder')->buildForm(PopoverSerializationProbeForm::class, $formState);

    $this->cachedBuildId = $built['#build_id'] ?? NULL;
    $this->assertNotNull($this->cachedBuildId, 'The probe form was built.');
    $this->assertNotNull(
      $this->container->get('keyvalue.expirable')->get('form')->get($this->cachedBuildId),
      'The form was written to the form cache.',
    );
  }

  /**
   * The element the hook builds for Field UI's per-field settings subform.
   *
   * @return array
   *   The third-party settings form of a popover that is already saved.
   */
  private function fieldUiHookElement(): array {
    $fieldDefinitions = $this->container->get('entity_field.manager')->getFieldDefinitions('node', self::BUNDLE);
    $this->assertArrayHasKey(self::FIELD_NAME, $fieldDefinitions);
    $fieldDefinition = $fieldDefinitions[self::FIELD_NAME];

    $formatterPluginManager = $this->container->get('plugin.manager.field.formatter');
    $plugin = $formatterPluginManager->createInstance(self::SLIDER_FORMATTER, [
      'field_definition' => $fieldDefinition,
      'view_mode' => 'teaser',
      'settings' => ['view_mode' => 'teaser', 'swiffy_slider_permalink' => NULL],
      'label' => 'hidden',
      'third_party_settings' => [
        FieldPopoverBuilder::SETTINGS_KEY => [
          'enable' => TRUE,
          'details' => [
            'formatter' => self::SLIDER_FORMATTER,
            'formatter_settings' => ['view_mode' => 'gallery_big', 'swiffy_slider_permalink' => NULL],
          ],
        ],
      ],
    ]);
    $this->assertInstanceOf(FormatterInterface::class, $plugin);

    $element = $this->hooks->thirdPartySettingsForm($plugin, $fieldDefinition, 'teaser', [], new FormState());
    $this->assertArrayHasKey('details', $element);

    return $element;
  }

}
