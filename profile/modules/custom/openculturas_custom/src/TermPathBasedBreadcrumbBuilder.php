<?php

declare(strict_types = 1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Drupal\taxonomy\TermBreadcrumbBuilder;

/**
 * Provides a custom taxonomy breadcrumb builder that uses the term hierarchy.
 */
class TermPathBasedBreadcrumbBuilder extends PathBasedBreadcrumbBuilder {

  /**
   * @var \Drupal\taxonomy\TermBreadcrumbBuilder
   */
  protected TermBreadcrumbBuilder $breadcrumbBuilder;

  public function setTermBreadcrumbBuilder(TermBreadcrumbBuilder $termBreadcrumbBuilder): void {
    $this->breadcrumbBuilder = $termBreadcrumbBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $routeMatch) {
    return $this->breadcrumbBuilder->applies($routeMatch);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $routeMatch): Breadcrumb {
    $breadcrumb_by_path = parent::build($routeMatch);
    $breadcrumb_by_term = $this->breadcrumbBuilder->build($routeMatch);

    if (count($breadcrumb_by_path->getLinks()) > 1) {
      return $breadcrumb_by_path;
    }

    // Fallback to term based breadcrumb.
    // This can happen when the request has an unalias path.
    return $breadcrumb_by_term;
  }

}
