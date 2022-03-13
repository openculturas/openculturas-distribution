<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

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
  protected $breadcrumbBuilder;

  public function setTermBreadcrumbBuilder(TermBreadcrumbBuilder $breadcrumbBuilder ) {
    $this->breadcrumbBuilder = $breadcrumbBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $this->breadcrumbBuilder->applies($route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb_by_path = parent::build($route_match);
    $breadcrumb_by_term = $this->breadcrumbBuilder->build($route_match);

    if (count($breadcrumb_by_path->getLinks()) > 1) {
      return $breadcrumb_by_path;
    }
    // Fallback to term based breadcrumb.
    // This can happen when the request has an unalias path.
    return $breadcrumb_by_term;
  }


}
