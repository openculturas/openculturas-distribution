<?php

declare(strict_types=1);

namespace Drupal\openculturas_base\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\address\AddressInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;
use Drupal\image\ImageStyleInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for openculturas_base.
 */
class OpenculturasBaseHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasBaseHooks.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Extension\ThemeSettingsProvider $themeSettingsProvider
   *   The theme settings provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected ThemeManagerInterface $themeManager,
    protected ConfigFactoryInterface $configFactory,
    protected ThemeSettingsProvider $themeSettingsProvider,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
  ) {
  }

  /**
   * Implements hook_preprocess_HOOK() for html.html.twig.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    // Set entity type for adding a class to the body.
    foreach ($this->routeMatch->getParameters()->all() as $parameter) {
      if ($parameter instanceof FieldableEntityInterface) {
        $variables['entity_type_id'] = $parameter->getEntityTypeId();
        break;
      }
    }

    if ($this->themeManager->getActiveTheme()->getName() === 'openculturas_base') {
      $config = $this->configFactory->get('openculturas_base.settings');
      $variables['custom_favicons'] = !$config->get('favicon.use_default');
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for page.html.twig.
   *
   * Provides header image to serve as layout background.
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    $bg_image = $this->themeSettingsProvider->getSetting('background_image.mode') ?? 'mood_image';
    $mood_image_uri = NULL;
    if ($bg_image === 'mood_image') {
      // Check if the current page is a node or a taxonomy term page.
      /** @var \Drupal\node\NodeInterface|null $entity */
      $entity = $this->routeMatch->getParameter('node');
      if (!$entity instanceof NodeInterface) {
        /** @var \Drupal\taxonomy\TermInterface|null $entity */
        $entity = $this->routeMatch->getParameter('taxonomy_term');
      }

      if ($entity instanceof ContentEntityInterface && $entity->hasField('field_mood_image')) {
        $mood_image = $entity->get('field_mood_image')->entity;
      }
      else {
        return;
      }

      // Get the URL of the image style "header_image" and set it as a variable for page.html.twig.
      if ($mood_image instanceof MediaInterface) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
        $field = $mood_image->get('field_media_image');
        $references = $field->referencedEntities();
        if ($references === []) {
          return;
        }

        /** @var \Drupal\file\FileInterface $file */
        $file = reset($references);
        $mood_image_uri = $file->getFileUri();
      }
    }
    elseif ($bg_image === 'global_image') {
      $mood_image_uri = $this->themeSettingsProvider->getSetting('background_image.path');
    }

    if (is_string($mood_image_uri)) {
      /** @var \Drupal\image\ImageStyleStorageInterface $imageStorage */
      $imageStorage = $this->entityTypeManager->getStorage('image_style');
      $mood_image_style = $imageStorage->load('header_image');
      if (!$mood_image_style instanceof ImageStyleInterface) {
        return;
      }

      $mood_image_bg = $mood_image_style->buildUrl($mood_image_uri);
      $variables['mood_image_bg'] = $mood_image_bg;
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for node.html.twig.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];
    /** @var array<string, mixed> $attributes */
    $attributes =& $variables['attributes'];
    /** @var list<string> $classes */
    $classes =& $attributes['class'];
    /** @var array $content */
    $content =& $variables['content'];
    // Hide all fields used by seperated blocks in different areas of the theme on full viewmode.
    // We don't hide them on other viewmodes because they could be needed there.
    if ($variables["view_mode"] === 'full') {
      $hidden_fields = [
        'field_subtitle',
        'field_mood_image',
        'field_portrait',
        'field_premiere',
      ];
      foreach ($hidden_fields as $name) {
        if (isset($content[$name]) && is_array($content[$name])) {
          $content[$name]['#access'] = FALSE;
        }
      }

      if ($node->hasField('field_layout_switcher') && !$node->get('field_layout_switcher')->isEmpty()) {
        /** @var string $layout_switcher_value */
        $layout_switcher_value = $node->get('field_layout_switcher')->value;
        $classes[] = $layout_switcher_value;
      }

      if ($node->hasField('field_event_status') && !$node->get('field_event_status')->isEmpty()) {
        /** @var string $event_status_value */
        $event_status_value = $node->get('field_event_status')->value;
        $classes[] = $event_status_value;
      }
    }

    // Override details title with user-set value and hide original field rendering.
    if ($node->hasField('field_reference_title') && !$node->get('field_reference_title')->isEmpty()) {
      /** @var array $elements */
      $elements = $variables['elements'];
      /** @var array<string, \stdClass> $fieldgroups */
      $fieldgroups = $elements['#fieldgroups'] ?? [];
      if (isset($fieldgroups['group_featuring'])) {
        $fieldgroup = $fieldgroups['group_featuring'];
        $fieldgroup->label = $node->get('field_reference_title')->value;
        if (is_array($content['field_reference_title'])) {
          $content['field_reference_title']['#access'] = FALSE;
        }
      }
    }

    if ($node->bundle() === 'date' && $node->hasField('field_event_description') && !$node->get('field_event_description')->isEmpty()) {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $list */
      $list = $node->get('field_event_description');
      $events = $list->referencedEntities();
      if ($events !== []) {
        $event = reset($events);
        if ($event instanceof NodeInterface) {
          $variables['label'] = $event->label();
          if (isset($content['extra_field_event_mood_image']) && is_array($content['extra_field_event_mood_image'])) {
            $content['field_mood_image'] = $content['extra_field_event_mood_image'];
            $content['extra_field_event_mood_image']['#access'] = FALSE;
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for field.html.twig.
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    /** @var array $element */
    $element = $variables['element'];
    // Adds a class depending on referenced entity to links of entity_reference fields.
    if ($variables['field_type'] === 'entity_reference' && is_array($variables['items'])) {
      foreach ($variables["items"] as $item) {
        /** @var array $item */
        $content = $item['content'] ?? NULL;
        $options = is_array($content) ? $content['#options'] ?? NULL : NULL;
        if ($item['attributes'] instanceof Attribute && is_array($options) && isset($options['entity_type'], $options['entity'])) {
          $entityType = $options['entity_type'];
          if (is_string($entityType)) {
            $item['attributes']->addClass('reference--' . $entityType);
            $entity = $options['entity'];
            if ($entity instanceof ContentEntityInterface) {
              $item['attributes']->addClass('reference--' . $entityType . '--' . $entity->bundle());
            }
          }
        }
      }
    }

    if ($variables['field_name'] === 'field_badges') {
      $fieldItems = $element['#items'];
      if ($fieldItems instanceof FieldItemListInterface && is_array($variables['items'])) {
        foreach ($variables['items'] as $delta => $item) {
          /** @var array $item */
          if (!$item['attributes'] instanceof Attribute) {
            continue;
          }

          $fieldItem = $fieldItems->get($delta);
          if ($fieldItem !== NULL) {
            $item['attributes']->addClass('badge--' . $fieldItem->getString());
          }
        }
      }
    }

    if ($variables['field_name'] === 'field_content_paragraphs' && ($element['#view_mode'] ?? NULL) === 'synopsis') {
      // We want to provide a url as a fallback in case the body text for smart trim
      // is too short, but the entity has content in this field.
      $variables['url'] = NULL;
      $fieldableEntity = $element['#object'];
      if ($fieldableEntity instanceof FieldableEntityInterface && $fieldableEntity->hasLinkTemplate('canonical')) {
        $translatedEntity = $this->entityRepository->getTranslationFromContext($fieldableEntity);
        $variables['url'] = $translatedEntity->toUrl()->toString();
      }
    }
  }

  /**
   * Implements hook_theme_suggestions_taxonomy_term_alter().
   */
  #[Hook('theme_suggestions_taxonomy_term_alter')]
  public function themeSuggestionsTaxonomyTermAlter(array &$suggestions, array $variables): void {
    /** @var array $elements */
    $elements = $variables['elements'];
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $elements['#taxonomy_term'];
    /** @var string $view_mode */
    $view_mode = $elements['#view_mode'];
    $sanitized_view_mode = strtr($view_mode, '.', '_');
    // Add view mode theme suggestions.
    $suggestions[] = 'taxonomy_term__' . $sanitized_view_mode;
    $suggestions[] = 'taxonomy_term__' . $term->bundle() . '__' . $sanitized_view_mode;
    $suggestions[] = 'taxonomy_term__' . $term->id() . '__' . $sanitized_view_mode;
  }

  /**
   * Implements hook_preprocess_HOOK() for paragraph.html.twig.
   */
  #[Hook('preprocess_paragraph')]
  public function preprocessParagraph(array &$variables): void {
    $variables['address_links'] = NULL;
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];
    // openculturas_address_links.address_service is a soft dependency: the
    // module is not a required dependency of this theme (see
    // openculturas_base.info.yml), so this cannot be constructor-injected
    // without breaking sites where it is not installed.
    if ($paragraph->bundle() === 'address_data' && \Drupal::hasService('openculturas_address_links.address_service')) {
      $links = [];
      if ($paragraph->hasField('field_address_location') && !$paragraph->get('field_address_location')->isEmpty()) {
        /** @var \Drupal\geofield\Plugin\Field\FieldType\GeofieldItem|null $item */
        $item = $paragraph->get('field_address_location')->first();
        if ($item instanceof GeofieldItem) {
          /** @var \Drupal\openculturas_address_links\AddressService $addressService */
          $addressService = \Drupal::service('openculturas_address_links.address_service');
          if ($url = $addressService->buildUrlFromGeofield($item, 'directions')) {
            $links['directions'] = [
              '#type' => 'link',
              '#title' => $this->t('Navigation'),
              '#url' => $url,
              '#theme_wrappers' => [
                'container' => [
                  '#attributes' => [
                    'class' => [
                      'address-navigation',
                    ],
                  ],
                ],
              ],
            ];
          }
        }
      }

      if ($paragraph->hasField('field_address') && !$paragraph->get('field_address')->isEmpty()) {
        $item = $paragraph->get('field_address')->first();
        if ($item instanceof AddressInterface) {
          /** @var \Drupal\openculturas_address_links\AddressService $addressService */
          $addressService = \Drupal::service('openculturas_address_links.address_service');
          if ($url = $addressService->buildUrlFromAddress($item, 'public_transport')) {
            $links['public_transport'] = [
              '#type' => 'link',
              '#title' => $this->t('Public transport'),
              '#url' => $url,
              '#theme_wrappers' => [
                'container' => [
                  '#attributes' => [
                    'class' => [
                      'public-transport',
                    ],
                  ],
                ],
              ],
            ];
          }
        }
      }

      if ($links !== []) {
        $variables['address_links'] = $links;
      }
    }

    if ($paragraph->bundle() === 'member' && $paragraph->hasField('field_member') && !$paragraph->get('field_member')->isEmpty()) {
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem|null $entityReferenceItem */
      $entityReferenceItem = $paragraph->get('field_member')->first();
      if (!$entityReferenceItem instanceof EntityReferenceItem) {
        return;
      }

      $variables['member_is_published'] = FALSE;
      $variables['user_has_edit_access'] = FALSE;
      /** @var \Drupal\node\NodeInterface|null $node */
      $node = $entityReferenceItem->entity ?? NULL;
      if (!$node instanceof NodeInterface) {
        return;
      }

      $variables['member_is_published'] = $node->isPublished();
      $variables['user_has_edit_access'] = $node->access('edit');
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter() for field.html.twig.
   *
   * Adds the view_mode of the field to the twig template suggestions.
   */
  #[Hook('theme_suggestions_field_alter')]
  public function themeSuggestionsFieldAlter(array &$suggestions, array $variables): void {
    // Add view_mode of the element (if available) to each field template suggestion.
    /** @var array $element */
    $element = $variables['element'];
    $viewMode = $element['#view_mode'] ?? NULL;
    if (!is_string($viewMode) || $viewMode === '') {
      return;
    }

    $result = [];
    foreach ($suggestions as $suggestion) {
      if (!is_string($suggestion)) {
        continue;
      }

      $result[] = $suggestion;
      $result[] = $suggestion . '__view_mode_' . $viewMode;
    }

    $suggestions = $result;
  }

}
