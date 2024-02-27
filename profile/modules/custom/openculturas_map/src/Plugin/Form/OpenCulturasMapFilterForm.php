<?php

declare(strict_types=1);

namespace Drupal\openculturas_map\Plugin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Form\ViewsExposedForm;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Filter form for the OpenCulturas Map.
 */
final class OpenCulturasMapFilterForm extends FormBase {

  /**
   * Machine name of the View used for the filter.
   *
   * @var string
   */
  protected string $viewId = 'oc_map_locations';

  /**
   * Machine name of the display in the view.
   *
   * @var string
   */
  protected string $viewDisplayId = 'rest_export';

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private ContainerInterface $container;

  /**
   * @var \Drupal\views\ViewExecutable
   */
  private ViewExecutable $viewExecutable;

  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openculturas_map_filter';
  }

  public function loadView(string|null $viewId = NULL): self {

    $view = Views::getView($viewId ?? $this->viewId);
    if (!$view instanceof ViewExecutable || $view->storage->getOriginalId() !== $this->viewId) {
      throw new \Exception("Could not load view!");
    }

    $this->viewExecutable = $view;
    return $this;
  }

  /**
   * Loads the View Display.
   *
   * @param string|null $viewDisplayId
   *   The view display id.
   *
   * @return $this
   *
   * @throws \Exception
   * phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
   */
  public function loadViewDisplay(string|null $viewDisplayId = NULL): self {
    $this->viewExecutable->setDisplay($viewDisplayId ?? $this->viewDisplayId);
    $displayPluginBase = $this->viewExecutable->getDisplay();

    if ($displayPluginBase->display['id'] !== $this->viewDisplayId) {
      throw new \Exception("Could not load display!");
    }

    return $this;
  }

  public function getExposedFilterForm(): array {
    $this->loadView();
    $this->viewExecutable->initHandlers();
    $formState = new FormState();
    $formState->setFormState([
      'view' => $this->viewExecutable,
      'display' => $this->viewExecutable->display_handler->display,
      'exposed_form_plugin' => $this->viewExecutable->display_handler->getPlugin('exposed_form'),
      'method' => 'get',
      'rerender' => TRUE,
      'no_redirect' => TRUE,
    // This is important for handle the form status.
      'always_process' => TRUE,
    ]);
    $form = $this->container->get('form_builder')->buildForm(ViewsExposedForm::class, $formState);
    if ($this->viewExecutable->pager !== NULL) {
      $form['#pager'] = $this->viewExecutable->pager;
    }

    $skipKeys = ["items_per_page", "offset"];
    foreach ($form as $key => $value) {
      if (
        stristr($key, "proximity")
        || in_array($key, $skipKeys)
      ) {
        unset($form[$key]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $this->loadView()->loadViewDisplay();
    return $this->getExposedFilterForm();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
