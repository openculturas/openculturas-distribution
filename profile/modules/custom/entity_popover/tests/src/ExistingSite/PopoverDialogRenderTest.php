<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_popover\ExistingSite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use PHPUnit\Framework\Attributes\DataProvider;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Renders a popover-enabled node and checks the dialog content.
 */
final class PopoverDialogRenderTest extends ExistingSiteBase {

  private const string FIELD_NAME = 'field_entity_popover_test';

  private const string BUNDLE = 'faq';

  private const string HOST_VIEW_MODE = 'teaser';

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();

    $fieldStorage = FieldStorageConfig::create([
      'field_name' => self::FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'entity_popover',
    ]);
    $fieldStorage->save();
    // Deleting the storage also deletes the field and its display components.
    $this->markEntityForCleanup($fieldStorage);

    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => self::BUNDLE,
      'label' => 'Popover test',
    ])->save();
  }

  /**
   * Dialog view modes: the host's own one, and a different one.
   *
   * The synopsis view mode is used because the theme's field preprocess
   * calls toUrl() on the host entity there.
   *
   * @return array<string, array{string}>
   *   The dialog view mode for each test case.
   */
  public static function dialogViewModeProvider(): array {
    return [
      'same view mode as host' => [self::HOST_VIEW_MODE],
      'different view mode' => ['synopsis'],
    ];
  }

  /**
   * The dialog renders the entity without losing its ID.
   */
  #[DataProvider('dialogViewModeProvider')]
  public function testDialogRendersEntity(string $dialogViewMode): void {
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', self::BUNDLE, self::HOST_VIEW_MODE)
      ->setComponent(self::FIELD_NAME, [
        'type' => 'entity_popover',
        'settings' => ['view_mode' => $dialogViewMode],
      ])
      ->save();

    $paragraph = Paragraph::create([
      'type' => 'text',
      'field_body' => $this->randomMachineName(),
    ]);
    $paragraph->save();
    $this->markEntityForCleanup($paragraph);

    $title = $this->randomMachineName();
    $node = $this->createNode([
      'type' => self::BUNDLE,
      'title' => $title,
      'field_content_paragraphs' => [$paragraph],
      self::FIELD_NAME => TRUE,
    ]);

    $html = $this->renderEntity($node);
    $this->assertStringContainsString('entity-popover__dialog', $html);
    $dialogContent = substr($html, (int) strpos($html, 'entity-popover__content'));
    $this->assertStringContainsString($title, $dialogContent, 'The dialog contains the rendered entity.');

  }

  /**
   * Renders an entity in the host view mode.
   */
  private function renderEntity(EntityInterface $entity): string {
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, self::HOST_VIEW_MODE);

    return (string) \Drupal::service('renderer')->renderInIsolation($build);
  }

}
