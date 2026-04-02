<?php

/**
 * @file
 * Syncs display settings from 'full' to 'full_lb' view mode.
 *
 * Usage: drush scr scripts/sync_full_to_full_lb.php
 */

declare(strict_types=1);

$entity_type_manager = \Drupal::entityTypeManager();
$display_storage = $entity_type_manager->getStorage('entity_view_display');

/** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $all_displays */
$all_displays = $display_storage->loadMultiple();

$full_displays = [];
$full_lb_displays = [];

foreach ($all_displays as $display) {
  $view_mode = $display->getMode();
  $target_entity_type = $display->getTargetEntityTypeId();
  $bundle = $display->getTargetBundle();
  $key = "$target_entity_type.$bundle";

  if ($view_mode === 'full') {
    $full_displays[$key] = $display;
  }
  elseif ($view_mode === 'full_lb') {
    $full_lb_displays[$key] = $display;
  }
}

$count = 0;
foreach ($full_lb_displays as $key => $full_lb_display) {
  if (isset($full_displays[$key])) {
    $full_display = $full_displays[$key];

    echo "Syncing $key from full to full_lb...\n";

    // Get components and third party settings from 'full'.
    $components = $full_display->getComponents();
    $hidden_components = $full_display->get('hidden');
    $third_party_settings = $full_display->get('third_party_settings');

    // Remove all current components from full_lb.
    foreach ($full_lb_display->getComponents() as $name => $options) {
      $full_lb_display->removeComponent($name);
    }

    // Set components from full.
    foreach ($components as $name => $options) {
      $full_lb_display->setComponent($name, $options);
    }

    // Set hidden components.
    $full_lb_display->set('hidden', $hidden_components);

    // Set third party settings.
    $full_lb_display->set('third_party_settings', $third_party_settings);

    // Save the full_lb display.
    $full_lb_display->save();
    $count++;
  }
}

echo "Successfully synced $count displays.\n";
