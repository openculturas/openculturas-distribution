<?php

declare(strict_types=1);

namespace Drupal\openculturas_entity_popover;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Removes what DisplayWirer added to the node displays again.
 *
 * The counterpart of DisplayWirer::wireFieldGallery() for the displays in
 * DisplayWirer::FIELD_GALLERY_DISPLAYS: the popover settings on field_gallery
 * are created in code, so the module that created them has to remove them
 * again when it is uninstalled.
 *
 * Nothing else on those displays is touched. A popover a site builder set up
 * on another field of a node display stays, and so does the display's
 * dependency on entity_popover_swiffy_slider - that module stays installed
 * when this one goes, and the setting is a valid formatter setting of it.
 *
 * The displays are loaded and saved as entities rather than edited as raw
 * config data, so the config dependencies are recalculated by Drupal on save
 * and this never has to prune dependencies.module by hand.
 *
 * Unlike DisplayUnwirer, which strips the popover wiring from any display
 * handed to it for the config_devel export of the profile's own config, this
 * class removes exactly what the module wired and nothing more.
 *
 * @see DisplayUnwirer
 * @see FieldUnwirer
 */
final class NodeDisplayUnwirer {

  /**
   * Removes the popover settings from the node displays the module wired.
   *
   * Displays that do not exist on this site, and displays where field_gallery
   * carries no popover settings, are skipped without an error. A display is
   * only saved when something actually changed.
   */
  public function unwireAll(): void {
    foreach (DisplayWirer::FIELD_GALLERY_DISPLAYS as $displayId) {
      $this->unwireFieldGallery($displayId);
    }
  }

  /**
   * Removes the popover settings from field_gallery of a single node display.
   *
   * Mirrors DisplayWirer::wireFieldGallery(): on a Layout Builder display the
   * settings live in the formatter of the field_gallery block, on every other
   * display in the field_gallery display component.
   *
   * @param string $displayId
   *   The config name of the node entity view display to unwire.
   */
  private function unwireFieldGallery(string $displayId): void {
    $display = EntityViewDisplay::load($displayId);
    if (!$display instanceof EntityViewDisplayInterface) {
      return;
    }

    if ($display instanceof LayoutBuilderEntityViewDisplay && $display->isLayoutBuilderEnabled()) {
      $this->unwireFieldGalleryBlock($display, DisplayWirer::fieldGalleryBlockId($displayId));
      return;
    }

    $component = $display->getComponent(DisplayWirer::FIELD_GALLERY_FIELD_NAME);
    if (!isset($component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER])) {
      return;
    }

    unset($component['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER]);
    $display->setComponent(DisplayWirer::FIELD_GALLERY_FIELD_NAME, $component)->save();
  }

  /**
   * Removes the popover settings from the field_gallery block of a display.
   *
   * @param \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $display
   *   The Layout Builder enabled display to unwire.
   * @param string $blockId
   *   The id of the field_gallery block within the display.
   */
  private function unwireFieldGalleryBlock(LayoutBuilderEntityViewDisplay $display, string $blockId): void {
    $changed = FALSE;

    foreach ($display->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        $configuration = $component->get('configuration');
        if (($configuration['id'] ?? '') !== $blockId) {
          continue;
        }

        if (!isset($configuration['formatter']['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER])) {
          continue;
        }

        unset($configuration['formatter']['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER]);
        $component->set('configuration', $configuration);
        $changed = TRUE;
      }
    }

    if ($changed) {
      $display->save();
    }
  }

}
