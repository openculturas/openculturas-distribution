<?php

/**
 * @file
 * Post update functions.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\image\ImageEffectInterface;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\openculturas_discussions\InstallerHelper;
use Drupal\update_helper\ConfigName;
use Drupal\views\Views;

/**
 * Import or revert config.
 *
 * @param list<string> $full_config_names
 *   Names of the config to import or revert.
 * @param bool $revert
 *   When TRUE, revert the config.
 *
 * @return string
 *   Logger output.
 */
function _openculturas_post_update_import_or_revert_config(array $full_config_names, bool $revert = FALSE): string {
  /** @var \Drupal\config_update\ConfigReverter $configUpdater */
  $configUpdater = \Drupal::service('config_update.config_update');
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  $configFactory = \Drupal::configFactory();
  foreach ($full_config_names as $full_config_name) {
    if (!$revert && !$configFactory->get($full_config_name)->isNew()) {
      $logger->warning(sprintf('Unable to import %s config, because configuration file exits already.', $full_config_name));
      // Do not try to import config that exits.
      continue;
    }

    $config_name = ConfigName::createByFullName($full_config_name);
    if (!$revert && $configUpdater->import($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully imported.', $full_config_name));
    }
    elseif ($revert && $configUpdater->revert($config_name->getType(), $config_name->getName())) {
      $logger->info(sprintf('Configuration %s has been successfully reverted.', $full_config_name));
    }
    else {
      $logger->warning(sprintf('Unable to import %s config, because configuration file is not found.', $full_config_name));
    }
  }

  return $logger->output();
}

/**
 * Pins the "uuid" of existing config objects to a fixed value.
 *
 * Config_devel strips "uuid" from files exported to a module's or profile's
 * config/install directory, so importing such a file via
 * _openculturas_post_update_import_or_revert_config() always assigns a
 * fresh random uuid. For field storage/field configs that matters: a later
 * "drush config:import" comparing against config/sync's fixed uuid then
 * sees a uuid mismatch for the same config name and deletes and recreates
 * the entity instead of updating it, which for field storage means losing
 * all data written to its dedicated tables in between.
 *
 * @param array<string, string> $uuids_by_config_name
 *   Full config name => the uuid it must have.
 *
 * @return string
 *   Logger output.
 */
function _openculturas_post_update_pin_config_uuid(array $uuids_by_config_name): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');
  $configFactory = \Drupal::configFactory();

  foreach ($uuids_by_config_name as $full_config_name => $uuid) {
    $config = $configFactory->getEditable($full_config_name);
    if ($config->isNew()) {
      $logger->warning(sprintf('Unable to pin uuid for %s config, because configuration does not exist.', $full_config_name));
      continue;
    }

    if ($config->get('uuid') === $uuid) {
      $logger->notice(sprintf('SKIPPED. %s already has uuid %s.', $full_config_name, $uuid));
      continue;
    }

    $config->set('uuid', $uuid)->save();
    $logger->info(sprintf('Pinned uuid %s on %s config.', $uuid, $full_config_name));
  }

  return $logger->output();
}

/**
 * Implements hook_removed_post_updates().
 */
function openculturas_removed_post_updates(): array {
  return [
    'openculturas_post_update_0045' => '2.2.0',
    'openculturas_post_update_interaction_button_section' => '2.2.0',
    'openculturas_post_update_password_policy' => '2.2.0',
    'openculturas_post_update_field_block_ref_cleanup' => '2.2.0',
    'openculturas_post_update_viewfield_missing_handler' => '2.2.0',
    'openculturas_post_update_tour_access' => '2.2.0',
    'openculturas_post_update_install_admin_toolbar_links_access_filter' => '2.2.0',
    'openculturas_post_update_formtips_replace_people_reference_selector' => '2.2.0',
    'openculturas_post_update_add_filter_autop_to_minimal_html' => '2.2.0',
    'openculturas_post_update_smart_date_recur_access' => '2.2.0',
    'openculturas_post_update_ckeditor5_migration' => '2.2.0',
    'openculturas_post_update_compact_address_map' => '2.2.0',
    'openculturas_post_update_enable_field_supporters_for_all' => '2.2.0',
    'openculturas_post_update_add_field_groups_to_page' => '2.2.0',
    'openculturas_post_update_moderation_widget_to_content_area' => '2.2.0',
    'openculturas_post_update_related_content_via_term_node_tid_depth' => '2.2.0',
    'openculturas_post_update_enable_media_edit' => '2.2.0',
    'openculturas_post_update_missing_permission_media_entity_download' => '2.2.0',
    'openculturas_post_update_add_field_badges' => '2.2.0',
    'openculturas_post_update_related_date_alternative_pager_offset' => '2.2.0',
    'openculturas_post_update_add_ief_for_location_ref_in_date' => '2.2.0',
    'openculturas_post_update_add_info_block_about_moderation_states_for_date' => '2.2.0',
    'openculturas_post_update_replace_focal_point_with_image_crop' => '2.2.0',
    'openculturas_post_update_enable_default_filename_sanitization_configuration' => '2.2.0',
    'openculturas_post_update_add_missing_default_translation_filter' => '2.2.0',
    'openculturas_post_update_change_field_group_type_type_terms' => '2.2.0',
    'openculturas_post_update_enable_media_edit_2' => '2.2.0',
    'openculturas_post_update_move_field_layout_switcher' => '2.2.0',
    'openculturas_post_update_source_string_spell_corrections' => '2.2.0',
    'openculturas_post_update_issue_3446002' => '2.5.0',
    'openculturas_post_update_issue_3446002_1' => '2.5.0',
    'openculturas_post_update_issue_3446003' => '2.5.0',
    'openculturas_post_update_issue_3446003_1' => '2.5.0',
    'openculturas_post_update_related_article_term_pager_views_infinite_scroll' => '2.5.0',
    'openculturas_post_update_oc_frontpage_disable_feed' => '2.5.0',
    'openculturas_post_update_upcoming_dates_map_local_safe' => '2.5.0',
    'openculturas_post_update_media_bundles_language_switcher' => '2.5.0',
    'openculturas_post_update_paragraph_member_non_translatable_fields' => '2.5.0',
    'openculturas_post_update_setup_search_api_exclude_entity' => '2.5.0',
    'openculturas_post_update_related_sponsor_more_displays' => '2.5.0',
    'openculturas_post_update_setup_simple_image_rotate' => '2.5.0',
    'openculturas_post_update_swiffyslider_autohide' => '2.5.0',
    'openculturas_post_update_buttons_in_user_dashboard_permanently' => '2.5.0',
    'openculturas_post_update_setup_past_dates_archive' => '2.5.0',
    'openculturas_post_update_remove_term_validation_in_views_displays' => '2.5.0',
    'openculturas_post_update_setup_office_hours' => '2.5.0',
    'openculturas_post_update_pager_id_my_content_block' => '2.5.0',
    'openculturas_post_update_search_input_label' => '2.5.0',
    'openculturas_post_update_user_admin_people_add_realname' => '2.5.0',
    'openculturas_post_update_content_moderation_revision_uid_relationship' => '2.5.0',
    'openculturas_post_update_add_field_alternative_title' => '2.5.0',
    'openculturas_post_update_setup_paragraphs_type_a11y_wheelchair' => '2.5.0',
    'openculturas_post_update_eca_upgrade_2' => '2.5.0',
    'openculturas_post_update_revert_gin_theme_overrides_1' => '2.5.0',
    'openculturas_post_update_import_block_utility_menu_account' => '2.5.0',
    'openculturas_post_update_import_block_language_toggle' => '2.5.0',
    'openculturas_post_update_import_text_slider' => '2.6.0',
    'openculturas_post_update_text_slider_setup_swiffy_slider' => '2.6.0',
    'openculturas_post_update_revert_gin_theme_overrides_2' => '2.6.0',
    'openculturas_post_update_teaser_unified_teaser_image_big' => '2.6.0',
    'openculturas_post_update_node_page_view_mode_compact' => '2.6.0',
    'openculturas_post_update_related_date_location_output' => '2.6.0',
    'openculturas_post_update_paragraph_missing_icons' => '2.6.0',
    'openculturas_post_update_content_type_page_field_mood_image_view_mode' => '2.6.0',
    'openculturas_post_update_article_teaser_author_1' => '2.6.0',
    'openculturas_post_update_article_teaser_author_2' => '2.6.0',
    'openculturas_post_update_wrapper_section_1' => '2.6.0',
    'openculturas_post_update_wrapper_section_2' => '2.6.0',
    'openculturas_post_update_wrapper_section_3' => '2.6.0',
    'openculturas_post_update_wrapper_section_4' => '2.6.0',
    'openculturas_post_update_wrapper_section_5' => '2.6.0',
    'openculturas_post_update_wrapper_section_6' => '2.6.0',
    'openculturas_post_update_event_catalogue_default_css_class' => '2.6.0',
    'openculturas_post_update_view_related_article_display_related_article_latest_pager_id' => '2.6.0',
    'openculturas_post_update_ckeditor_styles' => '2.6.0',
    'openculturas_post_update_revert_gin_theme_overrides_3' => '2.6.0',
    'openculturas_post_update_revert_gin_theme_overrides_4' => '2.6.0',
    'openculturas_post_update_ckeditor_attributes' => '2.6.0',
    'openculturas_post_update_views_filter_by_default_language' => '2.6.0',
  ];
}

/**
 * Increase the item per page (6) for the default display in view related_dates_archive.
 */
function openculturas_post_update_related_dates_archive_items_per_page(): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $view = Views::getView('related_dates_archive');
  if ($view) {
    $display = $view->getDisplay();
    /** @var \Drupal\views\Plugin\views\ViewsPluginInterface|null $plugin */
    $plugin = $display->getPlugin('pager');
    if ($plugin && $plugin->getPluginId() === 'infinite_scroll') {
      /** @var array{options:array{items_per_page: int}} $plugin_options */
      $plugin_options = $display->getOption('pager');
      $plugin_options['options']['items_per_page'] = 6;
      $display->setOption('pager', $plugin_options);
      $view->save();
    }
    else {
      $logger->notice('SKIPPED. Plugin options not found.');
    }
  }
  else {
    $logger->notice('SKIPPED. View or display not found.');
  }

  return $logger->output();
}

/**
 * Increase the width/height of image.style.teaser_big when configured wrong w/h.
 */
function openculturas_post_update_image_style_teaser_big_increase_size(): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $imageStyleStorage = \Drupal::entityTypeManager()->getStorage('image_style');
  /** @var \Drupal\image\ImageStyleInterface|null $imageStyle */
  $imageStyle = $imageStyleStorage->loadOverrideFree('teaser_big');
  if ($imageStyle) {
    try {
      $effect = $imageStyle->getEffect('418ab1de-49cf-4bc4-a560-fe9245ae3070');
    }
    catch (\Throwable) {
      $effect = NULL;
    }

    if ($effect instanceof ImageEffectInterface) {
      /** @var array{data:array<?string,string|int>}  $configuration */
      $configuration = $effect->getConfiguration();
      $width = (int) ($configuration['data']['width'] ?? 0);
      $height = (int) ($configuration['data']['height'] ?? 0);
      if ($width === 680 && $height === 382) {
        $configuration['data']['width'] = 960;
        $configuration['data']['height'] = 540;
        $effect->setConfiguration($configuration);
        $imageStyle->save();
        $logger->info('Image style teaser_big updated from 680x382 to 960x540.');
      }
      else {
        $logger->info('SKIPPED. Width and height was customized.');
      }
    }
    else {
      $logger->notice('SKIPPED. Effect in image style teaser_big not found.');
    }
  }
  else {
    $logger->notice('SKIPPED. Image style teaser_big not found.');
  }

  return $logger->output();
}

/**
 * Bunch of new/updated configs.
 */
function openculturas_post_update_3_0(): string {
  $full_config_names = [
    'language.content_settings.paragraph.a11y_wheelchair',
    // Does only look good in Opcult.
    'core.entity_view_mode.paragraph.a11y',
    'core.entity_view_mode.paragraph.trigger_warnings',
    'core.entity_view_display.paragraph.gallery.grid',
    'core.entity_view_display.paragraph.a11y_wheelchair.a11y',
    'core.entity_view_display.paragraph.accessibility.a11y',
  ];

  $output = _openculturas_post_update_import_or_revert_config($full_config_names);

  $full_config_names = [
    // New display er_teaser_node and remove date from type filter in er_node_references.
    'views.view.entity_reference_node',
    // Set some CSS classes.
    'views.view.oc_frontpage',
    // New option layout-text-heavy.
    'field.storage.node.field_layout_switcher',
    // Change the description for layout-text-heavy.
    'field.field.node.page.field_layout_switcher',
    // Change the used display to er_teaser_node. Which comes via views.view.entity_reference_node revert.
    'field.field.paragraph.teaser_node.field_article',
    // New displays block_locations_by_term and attachment_map_by_term.
    'views.view.locations',
    // Enable translation.
    'field.field.media.document.field_inlanguage',

    // Was not really used. But with the new theme opcult.
    'core.entity_view_display.node.date.teaser',
    'core.entity_view_display.node.date.compact',
    'core.entity_view_display.node.date.teaser_big',
    'core.entity_view_display.node.date.teaser_unified',

    // Streamline all media form modes, show all context relevant fields.
    'core.entity_form_display.media.audio.default',
    'core.entity_form_display.media.audio.media_library',
    'core.entity_form_display.media.document.default',
    'core.entity_form_display.media.document.media_library',
    'core.entity_form_display.media.image.default',
    'core.entity_form_display.media.image.media_library',
    'core.entity_form_display.media.logo_image.default',
    'core.entity_form_display.media.logo_image.media_library',
    'core.entity_form_display.media.remote_video.default',
    'core.entity_form_display.media.remote_video.media_library',
    'core.entity_form_display.media.sponsor.default',
    'core.entity_form_display.media.sponsor.media_library',
    'core.entity_form_display.media.user_profile_picture.default',
    'core.entity_form_display.media.user_profile_picture.media_library',

    // Limit a11y paragraph field instances.
    'field.field.node.date.field_accessibility',
    'field.field.node.event.field_accessibility',

    // Fix Drupal modal content padding, User-friendly complex tables.
    'asset_injector.css.oc_gin_theme_overrides',

    // Hide labels.
    'core.entity_view_display.paragraph.accessibility.default',
    'core.entity_view_display.paragraph.contact_data.default',
    // Changed swiffy_slider_permalink.
    'core.entity_view_display.paragraph.text_slider.slider_multiple',

    // group_editorial field group closed by default.
    'core.entity_form_display.paragraph.wrapper_section.default',
    'core.entity_form_display.paragraph.teaser_wrapper.default',
  ];

  _openculturas_post_update_import_or_revert_config($full_config_names, TRUE);
  // We revert the entity_reference_node view here, so we need to make the
  // necessary adjustments for openculturas_discussions again
  // in the same update hook.
  $openculturas_discussions_installed = \Drupal::moduleHandler()->moduleExists('openculturas_discussions');
  if ($openculturas_discussions_installed) {
    InstallerHelper::setCommentFilter();
  }

  return $output;
}

/**
 * Make field_location required when attendance mode is OfflineEventAttendanceMode.
 */
function openculturas_post_update_location_required_for_offline_event(): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository */
  $entityDisplayRepository = \Drupal::service('entity_display.repository');
  $formDisplay = $entityDisplayRepository->getFormDisplay('node', 'date');

  $component = $formDisplay->getComponent('field_location');
  if ($component === NULL) {
    $logger->warning('SKIPPED. Component field_location not found on node.date.default form display.');
    return $logger->output();
  }

  if ($formDisplay->getComponent('field_attendance_mode') === NULL) {
    $logger->warning('SKIPPED. Component field_attendance_mode not found on node.date.default form display.');
    return $logger->output();
  }

  $conditionalFieldUuid = 'e496761e-2b04-40d2-9dad-e392c395f365';
  $component['settings']['bundle'] = '';
  $component['third_party_settings']['conditional_fields'][$conditionalFieldUuid] = [
    'entity_type' => 'node',
    'bundle' => 'date',
    'dependee' => 'field_attendance_mode',
    'settings' => [
      'state' => 'required',
      'reset' => FALSE,
      'condition' => 'value',
      'grouping' => 'AND',
      'values_set' => 1,
      'value' => '',
      'values' => [],
      'value_form' => [
        ['value' => 'OfflineEventAttendanceMode'],
      ],
      'effect' => 'show',
      'effect_options' => [],
      'selector' => '',
    ],
  ];
  $formDisplay->setComponent('field_location', $component);

  $formDisplay->save();

  $logger->info('Added required conditional field rule for field_location on node.date.default.');
  return $logger->output();
}

/**
 * Updates entity view display configurations for accessibility trigger warnings.
 */
function openculturas_post_update_entity_view_display_paragraph_accessibility_trigger_warnings(): string {
  $full_config_names = [
    'core.entity_view_display.paragraph.accessibility.trigger_warnings',
  ];
  return _openculturas_post_update_import_or_revert_config($full_config_names);
}

/**
 * No-op. Revert asset_injector.css.oc_gin_theme_overrides.
 */
function openculturas_post_update_revert_gin_theme_overrides_5(): void {
}

/**
 * Revert asset_injector.css.oc_gin_theme_overrides.
 */
function openculturas_post_update_revert_gin_theme_overrides_6(): string {
  $full_config_names = [
    'asset_injector.css.oc_gin_theme_overrides',
  ];
  return _openculturas_post_update_import_or_revert_config($full_config_names, TRUE);
}

/**
 * Adds the block-field-mood-image class to the mood image block, unless customized.
 */
function openculturas_post_update_taxonomy_term_page_type_full_mood_image_class(): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  // The block-field-mood-image class only styles opcult's layout builder
  // template (see theme-settings.php), so there is nothing to do on sites
  // that never installed the theme.
  if (!\Drupal::service('theme_handler')->themeExists('opcult')) {
    $logger->notice('SKIPPED. Theme opcult is not installed.');
    return $logger->output();
  }

  $displayStorage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  // The 'full_lb' view mode is the opcult theme's layout builder template,
  // copied onto 'full' when a site switches the page_type bundle to layout
  // builder output, so it needs the same fix.
  foreach (['full', 'full_lb'] as $viewMode) {
    $display = $displayStorage->load('taxonomy_term.page_type.' . $viewMode);
    if (!$display instanceof LayoutEntityDisplayInterface) {
      $logger->notice('SKIPPED. Display taxonomy_term.page_type.' . $viewMode . ' not found.');
      continue;
    }

    $moodImageComponent = NULL;
    foreach ($display->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        if ($component->getUuid() === '3cf68c61-638f-420d-b8d9-636e238efbb6' && $component->getPluginId() === 'field_block:taxonomy_term:page_type:field_mood_image') {
          $moodImageComponent = $component;
          break 2;
        }
      }
    }

    if (!$moodImageComponent) {
      $logger->notice('SKIPPED. Mood image block component not found in taxonomy_term.page_type.' . $viewMode . ' display.');
      continue;
    }

    if (!empty($moodImageComponent->get('additional'))) {
      $logger->notice('SKIPPED. Mood image block additional attributes have been customized in taxonomy_term.page_type.' . $viewMode . ' display.');
      continue;
    }

    $moodImageComponent->set('additional', [
      'component_attributes' => [
        'block_attributes' => [
          'id' => '',
          'class' => 'block-field-mood-image',
          'style' => '',
          'data' => '',
        ],
        'block_title_attributes' => [
          'id' => '',
          'class' => '',
          'style' => '',
          'data' => '',
        ],
        'block_content_attributes' => [
          'id' => '',
          'class' => '',
          'style' => '',
          'data' => '',
        ],
      ],
    ]);
    $display->save();

    $logger->info('Added block-field-mood-image class to the mood image block in taxonomy_term.page_type.' . $viewMode . ' display.');
  }

  return $logger->output();
}

/**
 * Imports configuration core.date_format.site_month_and_year.
 */
function openculturas_post_update_core_date_format_site_month_and_year(): string {
  $full_config_names = [
    'core.date_format.site_month_and_year',
  ];
  return _openculturas_post_update_import_or_revert_config($full_config_names);
}

/**
 * Imports the new "search_index" view mode and displays for media, paragraph and taxonomy_term entities.
 */
function openculturas_post_update_search_index_displays(): string {
  $full_config_names = [
    'core.entity_view_mode.media.search_index',
    'core.entity_view_mode.paragraph.search_index',
    'core.entity_view_mode.taxonomy_term.search_index',
    'core.entity_view_display.media.audio.search_index',
    'core.entity_view_display.media.document.search_index',
    'core.entity_view_display.media.image.search_index',
    'core.entity_view_display.media.logo_image.search_index',
    'core.entity_view_display.media.remote_video.search_index',
    'core.entity_view_display.media.sponsor.search_index',
    'core.entity_view_display.paragraph.a11y_wheelchair.search_index',
    'core.entity_view_display.paragraph.accessibility.search_index',
    'core.entity_view_display.paragraph.address_data.search_index',
    'core.entity_view_display.paragraph.block.search_index',
    'core.entity_view_display.paragraph.bookable_event.search_index',
    'core.entity_view_display.paragraph.contact_data.search_index',
    'core.entity_view_display.paragraph.download.search_index',
    'core.entity_view_display.paragraph.gallery.search_index',
    'core.entity_view_display.paragraph.media.search_index',
    'core.entity_view_display.paragraph.media_mention.search_index',
    'core.entity_view_display.paragraph.member.search_index',
    'core.entity_view_display.paragraph.teaser_external.search_index',
    'core.entity_view_display.paragraph.teaser_node.search_index',
    'core.entity_view_display.paragraph.teaser_term.search_index',
    'core.entity_view_display.paragraph.teaser_wrapper.search_index',
    'core.entity_view_display.paragraph.text.search_index',
    'core.entity_view_display.paragraph.text_slider.search_index',
    'core.entity_view_display.paragraph.view.search_index',
    'core.entity_view_display.paragraph.wrapper_section.search_index',
    'core.entity_view_display.taxonomy_term.article_type.search_index',
    'core.entity_view_display.taxonomy_term.category.search_index',
    'core.entity_view_display.taxonomy_term.column.search_index',
    'core.entity_view_display.taxonomy_term.event_type.search_index',
    'core.entity_view_display.taxonomy_term.location_type.search_index',
    'core.entity_view_display.taxonomy_term.page_type.search_index',
  ];

  $moduleHandler = \Drupal::moduleHandler();
  if ($moduleHandler->moduleExists('openculturas_faq')) {
    $full_config_names[] = 'core.entity_view_display.taxonomy_term.faq_category.search_index';
  }

  if ($moduleHandler->moduleExists('openculturas_section')) {
    $full_config_names[] = 'core.entity_view_display.taxonomy_term.oc_section.search_index';
  }

  return _openculturas_post_update_import_or_revert_config($full_config_names);
}

/**
 * Points paragraph/address reference fields on the node "search_index" displays at the search_index view mode.
 */
function openculturas_post_update_search_index_displays_set_reference_view_mode(): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $changes = [
    'node.article.search_index' => ['field_content_paragraphs' => 'default'],
    'node.event.search_index' => [
      'field_accessibility' => 'default',
      'field_press_quotes' => 'default',
    ],
    'node.location.search_index' => [
      'field_accessibility' => 'default',
      'field_address_data' => 'address_block',
      'field_press_quotes' => 'default',
    ],
    'node.page.search_index' => ['field_content_paragraphs' => 'default'],
    'node.profile.search_index' => [
      'field_address_data' => 'address_block',
      'field_press_quotes' => 'default',
    ],
  ];
  if (\Drupal::moduleHandler()->moduleExists('openculturas_faq')) {
    $changes['node.faq.search_index'] = ['field_content_paragraphs' => 'default'];
  }

  $displayStorage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  foreach ($changes as $displayId => $fields) {
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null $display */
    $display = $displayStorage->load($displayId);
    if (!$display instanceof EntityViewDisplayInterface) {
      $logger->notice('SKIPPED. Display ' . $displayId . ' not found.');
      continue;
    }

    $changed = FALSE;
    foreach ($fields as $fieldName => $previousViewMode) {
      $component = $display->getComponent($fieldName);
      if ($component === NULL) {
        $logger->notice('SKIPPED. Component ' . $fieldName . ' not found in ' . $displayId . ' display.');
        continue;
      }

      if (($component['settings']['view_mode'] ?? NULL) !== $previousViewMode) {
        $logger->notice('SKIPPED. ' . $fieldName . ' in ' . $displayId . ' display has been customized.');
        continue;
      }

      $component['settings']['view_mode'] = 'search_index';
      $display->setComponent($fieldName, $component);
      $changed = TRUE;
    }

    if ($changed) {
      $display->save();
      $logger->info('Updated ' . $displayId . ' display.');
    }
  }

  return $logger->output();
}

/**
 * Shows the bookable-info and people-reference fields on the node "search_index" displays.
 */
function openculturas_post_update_search_index_displays_show_reference_fields(): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $peopleReferenceComponent = [
    'type' => 'entity_reference_revisions_label',
    'label' => 'hidden',
    'settings' => ['link' => FALSE],
  ];
  $changes = [
    'node.event.search_index' => [
      'field_bookable_info' => [
        'type' => 'entity_reference_revisions_entity_view',
        'label' => 'hidden',
        'settings' => ['view_mode' => 'search_index', 'link' => ''],
      ],
      'field_people_reference' => $peopleReferenceComponent,
    ],
    'node.location.search_index' => ['field_people_reference' => $peopleReferenceComponent],
    'node.profile.search_index' => ['field_people_reference' => $peopleReferenceComponent],
  ];

  $displayStorage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  foreach ($changes as $displayId => $fields) {
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null $display */
    $display = $displayStorage->load($displayId);
    if (!$display instanceof EntityViewDisplayInterface) {
      $logger->notice('SKIPPED. Display ' . $displayId . ' not found.');
      continue;
    }

    $changed = FALSE;
    foreach ($fields as $fieldName => $component) {
      if ($display->getComponent($fieldName) !== NULL) {
        $logger->notice('SKIPPED. ' . $fieldName . ' in ' . $displayId . ' display has already been customized.');
        continue;
      }

      $display->setComponent($fieldName, $component);
      $changed = TRUE;
    }

    if ($changed) {
      $display->save();
      $logger->info('Updated ' . $displayId . ' display.');
    }
  }

  return $logger->output();
}

/**
 * Adds "field_location_precision" and shows it on the address_data form.
 *
 * The view displays need no update: a field that is absent from a display's
 * "content" is rendered as hidden regardless of whether it is listed under
 * "hidden" (see EntityDisplayBase::getComponent()), so existing sites hide
 * the new field automatically without any config change.
 */
function openculturas_post_update_address_data_location_precision(): string {
  $output = _openculturas_post_update_import_or_revert_config([
    'field.storage.paragraph.field_location_precision',
    'field.field.paragraph.address_data.field_location_precision',
  ]);

  $output .= _openculturas_post_update_pin_config_uuid([
    'field.storage.paragraph.field_location_precision' => '2ad9a0c5-f49a-4121-9d59-e9711ba4537e',
    'field.field.paragraph.address_data.field_location_precision' => '895bfdfd-ec1c-4c4d-b5ab-3dd2e3893bfb',
  ]);

  return $output . _openculturas_post_update_import_or_revert_config([
    'core.entity_form_display.paragraph.address_data.default',
    'field.field.paragraph.address_data.field_address_location',
  ], TRUE);
}

/**
 * Enables lazy loading for field_address_location in the "map" view mode.
 */
function openculturas_post_update_address_data_map_lazy_load(): string {
  /** @var \Drupal\update_helper\UpdateLogger $logger */
  $logger = \Drupal::service('update_helper.logger');

  $displayStorage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null $display */
  $display = $displayStorage->load('paragraph.address_data.map');
  if (!$display instanceof EntityViewDisplayInterface) {
    $logger->notice('SKIPPED. Display paragraph.address_data.map not found.');
    return $logger->output();
  }

  $component = $display->getComponent('field_address_location');
  if ($component === NULL) {
    $logger->notice('SKIPPED. Component field_address_location not found in paragraph.address_data.map display.');
    return $logger->output();
  }

  if (($component['settings']['map_lazy_load']['lazy_load'] ?? NULL) !== FALSE) {
    $logger->notice('SKIPPED. field_address_location lazy loading in paragraph.address_data.map display has been customized.');
    return $logger->output();
  }

  $component['settings']['map_lazy_load']['lazy_load'] = TRUE;
  $display->setComponent('field_address_location', $component);
  $display->save();

  $logger->info('Enabled lazy loading for field_address_location in paragraph.address_data.map display.');
  return $logger->output();
}

/**
 * Sets "field_location_precision" to "auto" on existing address data.
 *
 * Writes the field's dedicated tables directly instead of loading and saving
 * every address_data paragraph (and every one of its translations) through
 * the Entity API, since that would resave every revision of every such
 * paragraph across the whole site just to set one column.
 */
function openculturas_post_update_address_data_location_precision_default_value(): void {
  $fieldStorage = FieldStorageConfig::loadByName('paragraph', 'field_location_precision');
  if (!$fieldStorage instanceof FieldStorageConfigInterface) {
    return;
  }

  /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage */
  $storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  $tableMapping = $storage->getTableMapping();
  $dataTable = $storage->getDataTable();
  $revisionDataTable = $storage->getRevisionDataTable();
  if (!$tableMapping instanceof DefaultTableMapping || !is_string($dataTable) || !is_string($revisionDataTable)) {
    return;
  }

  $fieldTable = $tableMapping->getDedicatedDataTableName($fieldStorage);
  $fieldRevisionTable = $tableMapping->getDedicatedRevisionTableName($fieldStorage);
  $valueColumn = $tableMapping->getFieldColumnName($fieldStorage, 'value');

  $database = \Drupal::database();

  $select = $database->select($dataTable, 'base');
  $select->leftJoin($fieldTable, 'existing', 'existing.entity_id = base.id AND existing.langcode = base.langcode');
  $select->condition('base.type', 'address_data');
  $select->isNull('existing.entity_id');
  $select->addField('base', 'type', 'bundle');
  $select->addField('base', 'id', 'entity_id');
  $select->addField('base', 'revision_id', 'revision_id');
  $select->addField('base', 'langcode', 'langcode');
  $select->addExpression('0', 'deleted');
  $select->addExpression('0', 'delta');
  $select->addExpression("'auto'", $valueColumn);
  $database->insert($fieldTable)->from($select)->execute();

  // "paragraphs_item_field_data" has one row per translation, so joining it
  // directly onto every revision would multiply rows for entities with more
  // than one translation. Bundle is immutable for a given paragraph id, so
  // deduplicate to one row per id first.
  $bundles = $database->select($dataTable, 'bundles')
    ->fields('bundles', ['id', 'type'])
    ->distinct();

  $select = $database->select($revisionDataTable, 'revision');
  $select->innerJoin($bundles, 'base', 'base.id = revision.id');
  $select->leftJoin($fieldRevisionTable, 'existing', 'existing.entity_id = revision.id AND existing.revision_id = revision.revision_id AND existing.langcode = revision.langcode');
  $select->condition('base.type', 'address_data');
  $select->isNull('existing.entity_id');
  $select->addField('base', 'type', 'bundle');
  $select->addField('revision', 'id', 'entity_id');
  $select->addField('revision', 'revision_id', 'revision_id');
  $select->addField('revision', 'langcode', 'langcode');
  $select->addExpression('0', 'deleted');
  $select->addExpression('0', 'delta');
  $select->addExpression("'auto'", $valueColumn);
  $database->insert($fieldRevisionTable)->from($select)->execute();
}
