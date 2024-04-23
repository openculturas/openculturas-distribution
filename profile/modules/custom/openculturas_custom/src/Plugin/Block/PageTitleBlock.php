<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\openculturas_custom\CurrentEntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function rtrim;
use function strip_tags;

/**
 * Provides a page title with subtitle block.
 *
 * @Block(
 *   id = "openculturas_custom_page_title",
 *   admin_label = @Translation("Page title with subtitle")
 * )
 */
final class PageTitleBlock extends BlockBase implements TitleBlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PageTitleBlock {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title): PageTitleBlock {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @php-return array{label_display: false}
   */
  public function defaultConfiguration(): array {
    return [
      'label_display' => FALSE,
      'subheadline_display' => TRUE,
      'subtype_display' => TRUE,
      'profilepicture_display' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['page_title_block'] = [
      '#type' => 'details',
      '#title' => $this->t('Components'),
      '#open' => TRUE,
      '#description' => $this->t('The default page title block composes several fields. Deselect here what you prefer to place elsewhere in the layout.'),
    ];
    $form['page_title_block']['page_title_display'] = [
      '#title' => $this->t('Page title'),
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#default_value' => TRUE,
    ];
    $form['page_title_block']['subheadline_display'] = [
      '#title' => $this->t('Subheadline (tagline)'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['subheadline_display'],
    ];
    $form['page_title_block']['subtype_display'] = [
      '#title' => $this->t('Sub type'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['subtype_display'],
    ];
    $form['page_title_block']['profilepicture_display'] = [
      '#title' => $this->t('Profile picture'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['profilepicture_display'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['subheadline_display'] = $form_state->getValue(['page_title_block', 'subheadline_display']);
    $this->configuration['subtype_display'] = $form_state->getValue(['page_title_block', 'subtype_display']);
    $this->configuration['profilepicture_display'] = $form_state->getValue(['page_title_block', 'profilepicture_display']);
    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $page_entity = CurrentEntityHelper::get_current_page_entity();
    $current_entity = CurrentEntityHelper::getEventReference($page_entity);
    $subtitle = NULL;
    $sub_type = NULL;
    $profile_image = NULL;

    if ($current_entity instanceof ContentEntityInterface) {
      $title_markup = [];
      if ($page_entity instanceof ContentEntityInterface && $page_entity->hasField('field_premiere')
        && !$page_entity->get('field_premiere')->isEmpty()) {
        $field_premiere_render_array = $page_entity->get('field_premiere')->view(['label' => 'hidden']);
        $title_markup[] = ['#plain_text' => rtrim(strip_tags((string) $this->renderer->renderPlain($field_premiere_render_array))) . ': '];
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface $current_entity */
      $current_entity = $this->entityRepository->getTranslationFromContext($current_entity);
      $title_markup[] = ['#plain_text' => $current_entity->label()];
      $this->title = $title_markup;
      if ($this->configuration['subheadline_display'] && $current_entity->hasField('field_subtitle')
        && !$current_entity->get('field_subtitle')->isEmpty()) {
        $subtitle = $current_entity->get('field_subtitle')->view(['label' => 'hidden']);
      }

      if ($this->configuration['subtype_display'] && $current_entity->hasField('field_sub_type')
        && !$current_entity->get('field_sub_type')->isEmpty()) {
        $sub_type = $current_entity->get('field_sub_type')->view(['label' => 'hidden']);
      }

      if ($this->configuration['profilepicture_display'] && $current_entity->hasField('field_portrait')
        && !$current_entity->get('field_portrait')->isEmpty()) {
        $display_options = [
          'type' => 'entity_reference_entity_view',
          'label' => 'hidden',
          'settings' => ['view_mode' => 'profile_image'],
        ];
        $profile_image = $current_entity->get('field_portrait')->view($display_options);
      }
    }

    $build = [
      '#theme' => 'page_title_custom',
      '#title' => $this->title,
      '#subtitle' => $subtitle,
      '#sub_type' => $sub_type,
      '#profile_image' => $profile_image,
    ];
    /*
     * Needs the cache dependency also for no-content, so that a update of the entity invalidates the cache.
     * No-cache is also not a option.
     */
    $this->renderer->addCacheableDependency($build, $current_entity);
    $this->renderer->addCacheableDependency($build, $page_entity);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'route',
    ]);
  }

}
