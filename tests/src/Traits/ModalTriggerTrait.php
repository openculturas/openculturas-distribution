<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Traits;

use Drupal\block\BlockInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Creates block_content entities with modal-trigger settings and references.
 *
 * Every entity is marked for cleanup, so the local site keeps its data.
 */
trait ModalTriggerTrait {

  /**
   * Creates a basic block_content entity that opens in a popover.
   *
   * @param string $mode
   *   The modal-trigger mode: 'delay' or 'scroll'.
   * @param int $delay
   *   Seconds before the popover opens in 'delay' mode.
   * @param int $cooldownDays
   *   Days before the popover opens again after a plain close.
   * @param bool $permanentDismiss
   *   Whether the popover shows a "Don't show again" control.
   */
  protected function createModalTriggerBlockContent(string $mode = 'delay', int $delay = 0, int $cooldownDays = 0, bool $permanentDismiss = FALSE): BlockContentInterface {
    $blockContent = $this->container->get('entity_type.manager')->getStorage('block_content')->create([
      'type' => 'basic',
      'info' => 'Modal trigger test ' . $this->randomMachineName(),
      'body' => [
        'value' => '<p>Modal trigger content ' . $this->randomMachineName() . '</p>',
        'format' => 'basic_html',
      ],
      'field_modal_trigger_mode' => $mode,
      'field_modal_trigger_delay' => $delay,
      'field_modal_cooldown_days' => $cooldownDays,
      'field_modal_permanent_dismiss' => $permanentDismiss,
    ]);
    $blockContent->save();
    $this->markEntityForCleanup($blockContent);

    return $blockContent;
  }

  /**
   * Creates a system_block_modal block_content entity with the CtA menu.
   */
  protected function createSystemModalBlockContent(): BlockContentInterface {
    $blockContent = $this->container->get('entity_type.manager')->getStorage('block_content')->create([
      'type' => 'system_block_modal',
      'info' => 'System modal test ' . $this->randomMachineName(),
      'field_block_ref' => [
        'plugin_id' => 'system_menu_block:call-to-action',
        'settings' => [
          'id' => 'system_menu_block:call-to-action',
          'label' => 'Call to action',
          'label_display' => '0',
          'provider' => 'system',
          'level' => 1,
          'depth' => 0,
          'expand_all_items' => FALSE,
        ],
      ],
      'field_modal_trigger_mode' => 'delay',
      'field_modal_trigger_delay' => 0,
      'field_modal_cooldown_days' => 0,
      'field_modal_permanent_dismiss' => FALSE,
    ]);
    $blockContent->save();
    $this->markEntityForCleanup($blockContent);

    return $blockContent;
  }

  /**
   * Places a block_content entity in the content region of the default theme.
   */
  protected function placeModalTriggerBlock(BlockContentInterface $blockContent, string $label, bool $displayLabel): BlockInterface {
    $block = $this->container->get('entity_type.manager')->getStorage('block')->create([
      'id' => 'modal_trigger_test_' . strtolower($this->randomMachineName()),
      'theme' => $this->container->get('config.factory')->get('system.theme')->get('default'),
      'region' => 'content',
      'weight' => -100,
      'plugin' => 'block_content:' . $blockContent->uuid(),
      'settings' => [
        'label' => $label,
        'label_display' => $displayLabel ? 'visible' : '0',
        'provider' => 'block_content',
      ],
    ]);
    $block->save();
    $this->markEntityForCleanup($block);

    return $block;
  }

  /**
   * Builds the field value of a block_editorial paragraph's field_block_ref.
   *
   * @return array<string, mixed>
   *   The block_field item value.
   */
  protected function buildBlockReferenceValue(BlockContentInterface $blockContent, string $label, bool $displayLabel): array {
    return [
      'plugin_id' => 'block_content:' . $blockContent->uuid(),
      'settings' => [
        'id' => 'block_content:' . $blockContent->uuid(),
        'label' => $label,
        'label_display' => $displayLabel ? 'visible' : '0',
        'provider' => 'block_content',
      ],
    ];
  }

  /**
   * Creates a block_editorial paragraph without a parent entity.
   */
  protected function createBlockEditorialParagraph(BlockContentInterface $blockContent, string $label, bool $displayLabel): ParagraphInterface {
    $paragraph = $this->container->get('entity_type.manager')->getStorage('paragraph')->create([
      'type' => 'block_editorial',
      'field_block_ref' => $this->buildBlockReferenceValue($blockContent, $label, $displayLabel),
    ]);
    $paragraph->save();
    $this->markEntityForCleanup($paragraph);
    $this->assertInstanceOf(ParagraphInterface::class, $paragraph);

    return $paragraph;
  }

}
