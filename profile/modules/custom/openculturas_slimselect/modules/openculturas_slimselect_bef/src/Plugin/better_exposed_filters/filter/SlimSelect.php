<?php

declare(strict_types=1);

namespace Drupal\openculturas_slimselect_bef\Plugin\better_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "slimselect",
 *   label = "Slim Select",
 * )
 */
final class SlimSelect extends FilterWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Dependency Injection.
   */
  private ContainerInterface $container;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SlimSelect {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->container = $container;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []): bool {
    return !empty($filter_options['type'])
      && $filter_options['type'] === "select";
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function exposedFormAlter(array &$form, FormStateInterface $formState): void {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $filter_id = $this->getExposedFilterFieldId();

    parent::exposedFormAlter($form, $formState);

    $form[$filter_id]['#type'] = "select";
    $form[$filter_id]['#attributes']['class'][] = 'slimselect';
    $form[$filter_id]['#attributes']['class'][] = 'bef-slimselect';

    $configurationAttributes = ['select_all', 'allow_deselect', 'close_on_select'];
    foreach ($configurationAttributes as $configurationAttribute) {
      $form[$filter_id]['#attributes'][sprintf('data-%s', str_replace('_', '-', $configurationAttribute))] = $this->configuration['advanced'][$configurationAttribute] ?? 0;
    }

    if (
      !empty($filter->options['expose']['multiple'])
      && $filter->options['expose']['multiple'] === TRUE
      && !empty($filter->options['hierarchy'])
      && $filter->options['hierarchy'] === TRUE
      && $filter instanceof TaxonomyIndexTid
      && !empty($filter->options['vid'])
      && !empty($this->configuration['advanced']['to_optgroup'])
      && $this->configuration['advanced']['to_optgroup'] === 1
    ) {
      // Rewrite to Optgroup.
      $form[$filter_id]['#options'] = [];
      foreach ($this->getTaxonomyTermsForVid($filter->options['vid']) as $term) {
        $parents = $this->getTaxonomyTermParents($term);
        $parent = count($parents) >= 1 ? array_values($parents)[0] : $term;
        if ($this->getTaxonomyTermChildren($parent) !== []) {
          if ($parent->name->value === $term->name->value) {
            continue;
          }

          $form[$filter_id]['#options'][$parent->name->value][$term->tid->value] = $term->name->value;
        }
        else {
          $form[$filter_id]['#options'][$term->tid->value] = $term->name->value;
        }
      }
    }

    $form['#attached']['library'][] = 'openculturas_slimselect/openculturas_slimselect';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'advanced' => [
        'to_optgroup' => TRUE,
        'select_all' => TRUE,
        'allow_deselect' => TRUE,
        'close_on_select' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState): array {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $form = parent::buildConfigurationForm($form, $formState);

    if (
      !empty($filter->options['expose']['multiple'])
      && $filter->options['expose']['multiple'] === TRUE
      && !empty($filter->options['hierarchy'])
      && $filter->options['hierarchy'] === TRUE
      && $filter instanceof TaxonomyIndexTid
      && !empty($filter->options['vid'])
    ) {
      $form['advanced']['to_optgroup'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Rewrite select to optgroups'),
        '#default_value' => !empty($this->configuration['advanced']['to_optgroup']),
        '#weight' => 10,
      ];

      $form['advanced']['select_all'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add a select all option to the top of the optgroups'),
        '#default_value' => !empty($this->configuration['advanced']['select_all']),
        '#weight' => 11,
      ];
    }

    $form['advanced']['allow_deselect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow to deselect all values'),
      '#default_value' => !empty($this->configuration['advanced']['allow_deselect']),
      '#weight' => 12,
    ];
    $form['advanced']['close_on_select'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close the content area upon selecting a value'),
      '#default_value' => !empty($this->configuration['advanced']['close_on_select']),
      '#weight' => 13,
    ];
    return $form;
  }

  /**
   * @throws \Exception
   */
  private function getTaxonomyTermsForVid(string $vid): array {
    // Load taxonomy Terms.
    $terms = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');

    // Load tree for specified $vid terms.
    $terms = $terms->loadTree($vid, 0, NULL, TRUE);
    return $this->translateTerms($terms);
  }

  /**
   * @throws \Exception
   */
  private function getTaxonomyTermParents(Term $term, ?string $langCode = NULL): array {
    // Load Parents of Term.
    $parentTerms = $this->container->get('entity_type.manager')->getStorage('taxonomy_term')->loadParents($term->get('tid')->value);
    return $this->translateTerms($parentTerms, $langCode);
  }

  /**
   * @throws \Exception
   */
  private function getTaxonomyTermChildren(Term $term, ?string $langCode = NULL): array {
    // Load Children of Term.
    $childTerms = $this->container->get('entity_type.manager')->getStorage('taxonomy_term')->loadChildren($term->get('tid')->value);
    return $this->translateTerms($childTerms, $langCode);
  }

  /**
   * @throws \Exception
   */
  private function translateTerms(array $terms, ?string $langCode = NULL): array {
    // Get current langauge if none specified.
    if ($langCode === NULL) {
      $langCode = $this->container->get('language_manager')->getCurrentLanguage()->getId();
    }

    // Get translation for terms (if available).
    foreach ($terms as &$term) {

      if (!$term instanceof Term) {
        throw new \Exception("translateTerms() expects an array with Term Instances.");
      }

      if ($term->hasTranslation($langCode)) {
        $term = $term->getTranslation($langCode);
      }
    }

    return $terms;
  }

}
