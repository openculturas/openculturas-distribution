<?php

declare(strict_types=1);

namespace Drupal\openculturas_discussions;

use Drupal\views\ViewExecutable;
use Drupal\views\Views;

class InstallerHelper {

  public static function install(): void {
    self::setCommentFilter();
  }

  public static function setCommentFilter(): void {
    $view = Views::getView('entity_reference_node');
    if ($view instanceof ViewExecutable) {
      $type_filter_configuration = $view->getHandler('er_node_references', 'filter', 'type');
      if (is_array($type_filter_configuration)) {
        $type_filter_configuration['value']['comment'] = 'comment';
        $view->setHandler('er_node_references', 'filter', 'type', $type_filter_configuration);
      }
      else {
        $type_filter_configuration = [
          'value' => ['comment' => 'comment'],
          'operator' => 'not in',
        ];
        $view->addHandler('er_node_references', 'filter', 'node_field_data', 'type', $type_filter_configuration);
      }

      $view->save();
    }
  }

}
