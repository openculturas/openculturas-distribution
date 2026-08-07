<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\update_helper_checklist\Entity\Update;
use Drupal\user\RoleInterface;

/**
 * Third-party integration hook implementations for openculturas_custom.
 */
class OpenculturasCustomIntegrationHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasCustomIntegrationHooks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleExtensionList $moduleExtensionList,
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Implements hook_checklistapi_checklist_info_alter().
   */
  #[Hook('checklistapi_checklist_info_alter')]
  public function checklistapiChecklistInfoAlter(array &$definitions): void {
    if (isset($definitions['update_helper_checklist']['#title'])) {
      $definitions['update_helper_checklist']['#title'] = $this->t('OpenCulturas update instructions');
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($form_id === "checklistapi_checklist_form" && $form['#checklist']->id === 'update_helper_checklist') {
      $checklist = $form['#checklist'];
      $groups = $checklist->items;
      // Prevent the user from Clearing checklist progress.
      $form['actions']['clear']['#access'] = FALSE;
      foreach (Element::children($groups) as $group_key) {
        $group = &$groups[$group_key];
        foreach (Element::children($group) as $item_key) {
          $update_key = str_replace('.', '_', (string) $item_key);
          $entity = $this->entityTypeManager->getStorage('update_helper_checklist_update')->load($update_key);
          $entityStatus = $entity instanceof Update && $entity->wasSuccessfulByHook();
          if ($entityStatus) {
            $form[$group_key][$item_key]['#disabled'] = TRUE;
          }
        }
      }
    }
  }

  /**
   * Implements hook_config_schema_info_alter().
   */
  #[Hook('config_schema_info_alter')]
  public function configSchemaInfoAlter(array &$definitions): void {
    if (isset($definitions['field.field_settings.viewfield']['mapping']['allowed_views'])) {
      $definitions['field.field_settings.viewfield']['mapping']['allowed_views']['orderby'] = 'key';
    }

    if (isset($definitions['field.field_settings.viewfield']['mapping']['allowed_display_types'])) {
      $definitions['field.field_settings.viewfield']['mapping']['allowed_display_types']['orderby'] = 'key';
    }

    if (isset($definitions['block_field_selection.categories']['mapping']['categories'])) {
      $definitions['block_field_selection.categories']['mapping']['categories']['orderby'] = 'key';
    }

    if (isset($definitions['node.type.*'])) {
      $definitions['node.type.*']['mapping']['name']['translation context'] = 'entity_bundle_name';
    }

    if (isset($definitions['core.entity_form_display.*.*.*.third_party.field_group']['sequence']['mapping']['label'])) {
      $definitions['core.entity_form_display.*.*.*.third_party.field_group']['sequence']['mapping']['label']['translation context'] = 'field_group_label';
    }

    if (isset($definitions['core.entity_view_display.*.*.*.third_party.field_group']['sequence']['mapping']['label'])) {
      $definitions['core.entity_view_display.*.*.*.third_party.field_group']['sequence']['mapping']['label']['translation context'] = 'field_group_label';
    }

    // Field blocks and extra field blocks are the Layout Builder equivalent
    // of the field_group labels above: since 3.1.x moved node output from
    // field_group to Layout Builder, the same label text (e.g. "Gallery",
    // "Contact") now recurs as block labels across many view displays.
    // OpenculturasCustomDateEventHooks::preprocessBlock() translates these
    // labels explicitly at render time (theme-independent, so it applies
    // regardless of which theme a site activates), so community translations
    // are reused there instead of retranslating identical strings. "label"
    // must stay untranslatable so Drupal can never generate a per-language
    // config override for it — the config value must always be the raw
    // English source, or the preprocess step's t() call would translate an
    // already-translated value a second time.
    //
    // Both types inherit "label" from "block_settings" via their "type" key
    // rather than declaring it themselves, so it is not present yet at this
    // level; it still merges correctly once added here because the config
    // schema system deep-merges a type's own mapping over its parent's.
    if (isset($definitions['block.settings.field_block:*:*:*'])) {
      $definitions['block.settings.field_block:*:*:*']['mapping']['label']['translatable'] = FALSE;
    }

    if (isset($definitions['block.settings.extra_field_block:*:*:*'])) {
      $definitions['block.settings.extra_field_block:*:*:*']['mapping']['label']['translatable'] = FALSE;
    }

    if (isset($definitions['paragraphs.paragraphs_type.*']['mapping']['label'])) {
      $definitions['paragraphs.paragraphs_type.*']['mapping']['label']['translation context'] = 'entity_bundle_name';
    }

    // We do not need translated CSS.
    if (isset($definitions['asset_injector.css.*']['mapping']['code'])) {
      $definitions['asset_injector.css.*']['mapping']['code']['translatable'] = FALSE;
    }

    // The "delta" field in the related_date* and oc_map_dates views uses the
    // numeric field's "Rewrite results" text as a Twig token expression
    // (e.g. "{{ title }}") to build a link to the parent node, not as
    // human-readable prose. Marking it translatable let a community
    // translation mistranslate the literal token (see
    // https://localize.drupal.org/translate/languages/de/translate?sid=2446810),
    // silently breaking the token match and blanking the rendered link text.
    // The base "alter" mapping is defined on the parent "views_field" type and
    // only appears here once merged, so it must be added rather than altered.
    if (isset($definitions['views.field.numeric'])) {
      $definitions['views.field.numeric']['mapping']['alter']['mapping']['text']['translatable'] = FALSE;
      $definitions['views.field.numeric']['mapping']['alter']['mapping']['path']['translatable'] = FALSE;
    }
  }

  /**
   * Implements hook_locale_translation_projects_alter().
   */
  #[Hook('locale_translation_projects_alter')]
  public function localeTranslationProjectsAlter(array &$projects): void {
    if (isset($projects['openculturas'])) {
      $projects['openculturas']['info']['name'] = 'OpenCulturas';
      $includes = $projects['openculturas']['includes'] ?? [];
      if (!is_array($includes)) {
        return;
      }

      foreach (array_keys($includes) as $project) {
        if ($project === 'openculturas') {
          continue;
        }

        $path = $this->moduleExtensionList->getPath($project);
        if (is_dir($path . '/translations') === FALSE) {
          continue;
        }

        $info = $this->moduleExtensionList->getExtensionInfo($project);
        $projects[$project] = $projects['openculturas'];
        unset($projects[$project]['includes']);
        $projects[$project]['name'] = $project;
        $projects[$project]['info'] = $info;
        $projects[$project]['info']['interface translation server pattern'] = $path . '/translations/%language.po';
      }
    }
  }

  /**
   * Implements hook_mail_alter().
   */
  #[Hook('mail_alter')]
  public function mailAlter(array &$message): void {
    $mail_signature = $this->configFactory->get('openculturas_custom.settings')->get('mailsignature');
    if (!is_string($mail_signature)) {
      return;
    }

    if ($mail_signature === '') {
      return;
    }

    $message['body'][] = $mail_signature;
  }

  /**
   * Implements hook_themes_installed().
   */
  #[Hook('themes_installed')]
  public function themesInstalled(array $theme_list): void {
    foreach ($theme_list as $theme) {
      if ($theme === 'opcult') {
        $config = $this->configFactory->getEditable('layout_builder_component_attributes');
        if (!$config->isNew()) {
          $config->set('allowed_block_title_attributes.id', FALSE);
          $config->set('allowed_block_title_attributes.data', FALSE);
          $config->set('allowed_block_content_attributes.data', FALSE);
          $config->set('allowed_block_content_attributes.data', FALSE);
          $config->save();
        }

        $config = $this->configFactory->getEditable('formtips.settings');
        if (!$config->isNew()) {
          $config->set('formtips_themes.opcult', 'opcult');
          $config->save();
        }

        /** @var \Drupal\user\RoleInterface|null $role */
        $role = $this->entityTypeManager->getStorage('user_role')->load('oc_admin');
        if ($role instanceof RoleInterface) {
          $role->grantPermission('administer blockgroups');
          $role->grantPermission('manage layout builder component attributes');
          $role->save();
        }
      }
    }
  }

}
