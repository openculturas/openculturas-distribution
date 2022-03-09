<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\system\PathBasedBreadcrumbBuilder;

/**
 * Provides a custom taxonomy breadcrumb builder that uses the term hierarchy.
 */
class TermPathBasedBreadcrumbBuilder extends PathBasedBreadcrumbBuilder {

  /**
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbBuilder;

  public function setTermBreadcrumbBuilder(BreadcrumbBuilderInterface $breadcrumbBuilder ) {
    $this->breadcrumbBuilder = $breadcrumbBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $this->breadcrumbBuilder->applies($route_match);
  }

}
