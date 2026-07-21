<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\FlagInterface;
use Drupal\node\NodeInterface;
use Drupal\openculturas_custom\Plugin\DateAugmenter\AddToCal;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\smart_date\Plugin\Field\FieldType\SmartDateItem;
use Drupal\taxonomy\TermInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event/date entity hook implementations for openculturas_custom.
 */
class OpenculturasCustomDateEventHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasCustomDateEventHooks.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected AccountProxyInterface $currentUser,
    protected RouteMatchInterface $routeMatch,
  ) {
  }

  /**
   * Implements hook_ENTITY_TYPE_prepare_form().
   */
  #[Hook('node_prepare_form')]
  public function nodePrepareForm(NodeInterface $node): void {
    $is_date_entity = $node->bundle() === 'date' && $node->isNew();
    $request = $this->requestStack->getCurrentRequest();
    if (!$request instanceof Request) {
      return;
    }

    if ($is_date_entity && $request->query->has('event_id')) {
      $event_id = $request->query->get('event_id');
      if (empty($event_id)) {
        return;
      }

      $event = $this->entityTypeManager->getStorage('node')->load($event_id);
      if (!$event instanceof NodeInterface) {
        return;
      }

      if ($event->bundle() !== 'event') {
        return;
      }

      $node->set('field_event_description', [
        'target_id' => $event->id(),
      ]);
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $source_data */
      $source_data = $event->get('field_people_reference');
      if ($source_data->isEmpty()) {
        return;
      }

      $cloned_paragraphs = [];
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      foreach ($source_data->referencedEntities() as $paragraph) {
        $cloned_paragraphs[] = clone $paragraph;
      }

      $node->set('field_people_reference', $cloned_paragraphs);
    }

    $is_event_entity = $node->bundle() === 'event' && $node->isNew();
    if ($is_event_entity && $request->query->has('member_id')) {
      $member_id = $request->query->get('member_id');
      if (empty($member_id)) {
        return;
      }

      $profile = $this->entityTypeManager->getStorage('node')->load($member_id);
      if (!$profile instanceof NodeInterface) {
        return;
      }

      if ($profile->bundle() !== 'profile') {
        return;
      }

      $paragraph = Paragraph::create([
        'type' => 'member',
      ]);
      $paragraph->set('field_member', [
        'target_id' => $member_id,
      ]);
      $node->set('field_people_reference', [
        $paragraph,
      ]);
    }
  }

  /**
   * Implements hook_entity_view_display_alter().
   */
  #[Hook('entity_view_display_alter')]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, array $context): void {
    if ($context['entity_type'] === 'node' && $context['view_mode'] === 'full') {
      foreach ($display->getComponents() as $name => $options) {
        if ($name === 'field_date') {
          // Force past_display will be hidden by self::nodeViewAlter()|self::preprocessBlock()
          // when next_display is also shown.
          $options['settings']['past_display'] = 1;
          $display->setComponent($name, $options);
        }
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter().
   */
  #[Hook('node_view_alter')]
  public function nodeViewAlter(array &$build, NodeInterface $entity): void {
    // Do nothing when LB is used.
    if (isset($build['_layout_builder'])) {
      return;
    }

    if ($build['#view_mode'] === 'full' && $entity->bundle() === 'date') {
      $build['field_location']['#title'] = $this->t("Visit the location's profile");
      $build['field_event_description']['#title'] = $this->t('See all about');
      if (isset($build['field_date'])) {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request && $request->query->has('date_delta') && is_numeric($delta = $request->query->get('date_delta'))) {
          $cloned_entity = clone $entity;
          /** @var \Drupal\smart_date\Plugin\Field\FieldType\SmartDateFieldItemList $field */
          $field = $cloned_entity->get('field_date');
          /** @var \Drupal\smart_date\Plugin\Field\FieldType\SmartDateItem|null $delta_item */
          $delta_item = $field->get($delta);
          if ($delta_item instanceof SmartDateItem) {
            $field->setValue([
              $delta_item->getValue(),
            ]);
            $display_settings = $this->entityDisplayRepository->getViewDisplay('node', 'date', 'full')->getComponent('field_date');
            if (is_array($display_settings)) {
              if (isset($display_settings['settings']['past_display'])) {
                $display_settings['settings']['past_display'] = 1;
              }

              $build['field_date'] = $field->view($display_settings);
            }

            $cacheMetadata = CacheableMetadata::createFromRenderArray($build['field_date']);
            $cacheMetadata->setCacheContexts([
              'url.query_args:date_delta',
            ]);
            $cacheMetadata->applyTo($build['field_date']);
          }
        }

        $children = Element::getVisibleChildren($build['field_date']);
        foreach ($children as $index) {
          if (isset($build['field_date'][$index]['#past_display'], $build['field_date'][$index]['#next_display']['#children'])) {
            $children_next = Element::getVisibleChildren($build['field_date'][$index]['#next_display']['#children']);
            if ($children_next) {
              $build['field_date'][$index]['#past_display']['#access'] = FALSE;
            }
          }
        }
      }
    }

    if ($entity->hasField('field_event_description') && !$entity->get('field_event_description')->isEmpty()) {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $event_list */
      $event_list = $entity->get('field_event_description');
      $events = $event_list->referencedEntities();
      $event = reset($events);
      if ($event instanceof NodeInterface) {
        $flag_bundles = [
          'bookmark_node',
          'recommendation_node',
        ];
        $flagStorage = $this->entityTypeManager->getStorage('flag');
        foreach ($flag_bundles as $flag_bundle) {
          $flag = $flagStorage->load($flag_bundle);
          if ($flag instanceof FlagInterface) {
            $build_key = 'flag_' . $flag->id();
            if (isset($build[$build_key])) {
              $build[$build_key] = array_merge($build[$build_key], [
                '#lazy_builder' => [
                  'flag.link_builder:build',
                  [
                    $event->getEntityTypeId(),
                    $event->id(),
                    $flag->id(),
                    'default',
                  ],
                ],
              ]);
            }
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for block.html.twig.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(array &$variables): void {
    $derivative_plugin_id = $variables['derivative_plugin_id'] ?? NULL;
    if (!$derivative_plugin_id) {
      return;
    }

    $isAnonymous = $this->currentUser->isAnonymous();
    $entity = $variables['content'][0]['#object'] ?? NULL;
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    if ($isAnonymous) {
      if (str_ends_with((string) $variables['derivative_plugin_id'], 'flag_bookmark_node')) {
        $variables['content'] = [];
        $variables['content']['flag_bookmark_node'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
          <div class="flag flag-bookmark-node action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to bookmark' | t}}">{{ 'Log in to bookmark' | t}}</a></div>
          EOF,
        ];
      }

      if (str_ends_with((string) $variables['derivative_plugin_id'], 'flag_bookmark_term')) {
        $variables['content'] = [];
        $variables['content']['flag_bookmark_term'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
          <div class="flag flag-bookmark-term action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to bookmark' | t}}">{{ 'Log in to bookmark' | t}}</a></div>
          EOF,
        ];
      }

      if (str_ends_with((string) $variables['derivative_plugin_id'], 'flag_recommendation_node')) {
        $variables['content'] = [];
        $variables['content']['flag_recommendation_node'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
          <div class="flag flag-recommendation-node action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to recommend'| t}}">{{'Recommend'|t}}</a></div>
          EOF,
        ];
      }

      if (($entity instanceof NodeInterface || $entity instanceof TermInterface) && (str_ends_with((string) $variables['derivative_plugin_id'], 'flag_claim_ownership') && $entity->hasField('field_allow_claiming') && !$entity->get('field_allow_claiming')->isEmpty())) {
        $allowed = (bool) $entity->get('field_allow_claiming')->value;
        if ($allowed) {
          $variables['content'] = [];
          $variables['content']['flag_claim_ownership'] = [
            '#type' => 'inline_template',
            '#template' => <<<EOF
            <div class="flag flag-claim-ownership action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to claim this' | t}}">{{'Claim ownership'|t}}</a></div>
            EOF,
          ];
        }
      }

      if (str_ends_with((string) $variables['derivative_plugin_id'], 'flag_report_abuse')) {
        $variables['content'] = [];
        $variables['content']['flag_report_abuse'] = [
          '#type' => 'inline_template',
          '#template' => <<<EOF
          <div class="flag flag-report-abuse action-flag"><a class="flag--link flag--button disabled" href="{{ path('user.login',{},{'query':{'destination': path('<current>') }}) }}" title="{{ 'Log in to report abuse' | t}}">{{'Report abuse'|t}}</a></div>
          EOF,
        ];
      }
    }

    if ($derivative_plugin_id === 'node:date:field_date' && isset($variables['content'][0])) {
      $request = $this->requestStack->getCurrentRequest();
      if ($request instanceof Request && $request->query->has('date_delta') && is_numeric($date_delta = $request->query->get('date_delta'))) {
        $delta = (int) $date_delta;
        if ($delta >= 0 && $entity->hasField('field_date') && !$entity->get('field_date')->isEmpty()) {
          $cloned_entity = clone $entity;
          /** @var \Drupal\smart_date\Plugin\Field\FieldType\SmartDateFieldItemList $field */
          $field = $cloned_entity->get('field_date');
          $count = $field->count();
          // Hardening: only allow deltas that actually exist (delta 0 is valid).
          if ($delta < $count) {
            /** @var \Drupal\smart_date\Plugin\Field\FieldType\SmartDateItem|null $delta_item */
            $delta_item = $field->get($delta);
            if ($delta_item instanceof SmartDateItem) {
              // Replace the field values with the selected delta only.
              $field->setValue([
                $delta_item->getValue(),
              ]);
              // Reuse the formatter/settings from the field block configuration (Layout Builder).
              $display_settings = $variables['configuration']['formatter'];
              // Build a fresh render array for the restricted field.
              $variables['content'] = [];
              $variables['content'][] = $field->view($display_settings);
              // Ensure correct caching per query argument.
              $cacheMetadata = CacheableMetadata::createFromRenderArray($variables['content'][0]);
              $cacheMetadata->addCacheContexts([
                'url.query_args:date_delta',
              ]);
              $cacheMetadata->addCacheableDependency($entity);
              $cacheMetadata->applyTo($variables['content'][0]);
            }
          }
        }
      }

      // Hide past date output, when next is displayed.
      $children = Element::getVisibleChildren($variables['content'][0]);
      foreach ($children as $index) {
        if (isset($variables['content'][0][$index]['#past_display'], $variables['content'][0][$index]['#next_display']['#children'])) {
          $children_next = Element::getVisibleChildren($variables['content'][0][$index]['#next_display']['#children']);
          if ($children_next) {
            $variables['content'][0][$index]['#past_display']['#access'] = FALSE;
          }
        }
      }
    }

    if (str_ends_with((string) $variables['derivative_plugin_id'], 'field_people_reference') && ($entity->hasField('field_reference_title') && !$entity->get('field_reference_title')->isEmpty())) {
      $variables['label'] = NULL;
    }
  }

  /**
   * Implements hook_views_post_execute().
   */
  #[Hook('views_post_execute')]
  public function viewsPostExecute(ViewExecutable $view): void {
    if ($view->current_display === 'related_date_alternative' && $view->id() === 'related_date') {
      $displayedNode = $this->routeMatch->getParameter('node');
      if (!$displayedNode instanceof NodeInterface) {
        return;
      }

      foreach ($view->result as $row) {
        /** @var \Drupal\node\NodeInterface $node */
        $node = $row->_entity;
        if ($node->id() === $displayedNode->id()) {
          $date_delta = $view->getRequest()->query->get('date_delta');
          if (is_scalar($date_delta) && property_exists($row, 'node__field_date_delta') && (int) $row->node__field_date_delta === (int) $date_delta) {
            unset($view->result[$row->index]);
            --$view->total_rows;
            break;
          }

          if ($date_delta === NULL) {
            unset($view->result[$row->index]);
            --$view->total_rows;
            break;
          }
        }
      }
    }
  }

  /**
   * Implements hook_date_augmenter_plugin_info_alter().
   */
  #[Hook('date_augmenter_plugin_info_alter')]
  public function dateAugmenterPluginInfoAlter(array &$plugins): void {
    $plugins['addtocal']['class'] = AddToCal::class;
    $plugins['addtocal']['provider'] = 'openculturas_custom';
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * @see openculturas_custom_event_submit_handler()
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\node\Form\NodeForm $node_form */
    $node_form = $form_state->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_form->getEntity();
    if ($node->bundle() === 'event') {
      $this->requestStack->getCurrentRequest()?->query->remove('destination');
      $form['#submit'][] = 'openculturas_custom_event_submit_handler';
      $form['actions']['openculturas_custom_event_submit'] = $form['actions']['submit'];
      $form['actions']['openculturas_custom_event_submit']['#value'] = $this->t('Save and add date');
      $form['actions']['openculturas_custom_event_submit']['#button_type'] = 'secondary';
      $form['actions']['openculturas_custom_event_submit']['#gin_action_item'] = TRUE;
      $form['actions']['openculturas_custom_event_submit']['#submit'][] = 'openculturas_custom_event_submit_handler';
    }
  }

}
