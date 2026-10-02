<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\config_devel\Event\ConfigDevelSaveEvent;
use Drupal\openculturas_custom\EventSubscriber\OpenculturasCustomConfigDevelSubscriber;
use PHPUnit\Framework\Attributes\Group;

use function is_array;

#[Group('openculturas')]
class FaviconUpdateAndExportTest extends ExistingSiteBase {

  /**
   * The original global metatag tags, restored on teardown.
   *
   * @var array<string, mixed>|null
   */
  protected ?array $originalTags = NULL;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->originalTags !== NULL) {
      $this->container->get('config.factory')
        ->getEditable('metatag.metatag_defaults.global')
        ->set('tags', $this->originalTags)
        ->save();
    }

    parent::tearDown();
  }

  public function testUpdateKeepsCustomizedTagsAndFillsMissingOnes(): void {
    $configuration = $this->container->get('config.factory')->getEditable('metatag.metatag_defaults.global');
    $tags = $configuration->get('tags');
    $this->originalTags = is_array($tags) ? $tags : [];

    $customizedTags = $this->originalTags;
    $customizedTags['svg_icon'] = '/sites/default/files/custom.svg';
    unset($customizedTags['apple_touch_icon']);
    $configuration->set('tags', $customizedTags)->save();

    $this->container->get('module_handler')->loadInclude('openculturas', 'install');
    openculturas_update_10132();

    $tags = $this->container->get('config.factory')->get('metatag.metatag_defaults.global')->get('tags');
    $this->assertIsArray($tags);
    $this->assertSame('/sites/default/files/custom.svg', $tags['svg_icon']);
    $this->assertSame('[site:favicon-path]/apple-touch-icon.png', $tags['apple_touch_icon']);
    $this->assertSame('[site:favicon-shortcut-icon]', $tags['shortcut_icon']);
    $this->assertSame('[site:favicon-path]/favicon-96x96.png', $tags['icon_96x96']);
  }

  public function testProfileExportUsesDownstreamGinFaviconPath(): void {
    $event = $this->dispatchConfigDevelSave('profiles/contrib/openculturas-profile/config/install/gin.settings.yml');
    $data = $event->getData();
    $this->assertIsArray($data['favicon'] ?? NULL);
    $this->assertSame('profiles/contrib/openculturas-distribution/profile/themes/opcult/favicons/drupal-oc-icon.png', $data['favicon']['path']);
  }

  public function testOtherExportsKeepGinFaviconPath(): void {
    $event = $this->dispatchConfigDevelSave('profiles/contrib/openculturas-profile/modules/custom/openculturas_custom/config/install/gin.settings.yml');
    $data = $event->getData();
    $this->assertIsArray($data['favicon'] ?? NULL);
    $this->assertSame('local/path/drupal-oc-icon.png', $data['favicon']['path']);
  }

  private function dispatchConfigDevelSave(string $fileName): ConfigDevelSaveEvent {
    $event = new ConfigDevelSaveEvent([$fileName], ['favicon' => ['path' => 'local/path/drupal-oc-icon.png']]);
    $subscriber = new OpenculturasCustomConfigDevelSubscriber($this->container->get('config.manager'), $this->container->get('module_handler'));
    $subscriber->onConfigDevelSave($event);
    return $event;
  }

}
