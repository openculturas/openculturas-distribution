<?php
declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\views\filter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Database\Query\Condition;
use Drupal\smart_date\Plugin\views\filter\Date as SmartDateDate;
use function str_replace;
use function strpos;
use function substr;

/**
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date")
 */
class Date extends SmartDateDate {

  /**
   * Override parent method, to a filter for start date < end date.
   */
  protected function opSimple($field) {
    $timezone = $this->getTimezone();
    $granularity = $this->options['value']['granularity'];

    // Convert value to DateTimePlus for additional processing.
    $date_value = $this->value['value'];
    $value = new DateTimePlus($date_value, new \DateTimeZone($timezone));

    // Granularity requires some conversion.
    if ($granularity !== 'second') {
      $value_array = [
        'year' => $value->format('Y'),
        'month' => $value->format('n'),
        'day' => $value->format('j'),
        'hour' => $value->format('G'),
        'minute' => $value->format('i'),
        'second' => $value->format('s'),
      ];
      $min = $max = $value_array;
      switch ($granularity) {
        case 'year':
          $min['month'] = '01';
          $max['month'] = '12';
          $max['day'] = '31';
        case 'month':
          $min['day'] = '01';
          if ($granularity !== 'year') {
            $max['day'] = $value->format('t');
          }
        case 'day':
          $min['hour'] = '00';
          $max['hour'] = '23';
        case 'hour':
          $min['minute'] = '00';
          $max['minute'] = '59';
        case 'minute':
          $min['second'] = '00';
          $max['second'] = '59';
      }

      // Additional, operator-specific logic.
      if (substr($this->operator, 0, 1) === '>') {
        $value = $value::createFromArray($min, $timezone);
      }
      elseif (substr($this->operator, 0, 1) === '<') {
        $value = $value::createFromArray($max, $timezone);
      }
      else {
        $min_value = $value::createFromArray($min, $timezone)->format('U');
        $max_value = $value::createFromArray($max, $timezone)->format('U');
        if ($this->operator === '=') {
          $operator = 'BETWEEN';
        }
        elseif ($this->operator === '!=') {
          $operator = 'NOT BETWEEN';
        }
        $this->query->addWhereExpression($this->options['group'], "$field $operator $min_value AND $max_value");
        return;
      }
    }
    $start_field = str_replace('_end_value', '_value', $field);
    $end_field = str_replace('_value', '_end_value', $start_field);
    $is_end_field = $end_field === $field;


    if ($is_end_field === FALSE && $this->operator === '>=') {
      $orCondition = (new Condition('OR'));
      $orCondition
        ->condition($start_field, $value->format('U'), $this->operator)
        ->condition($end_field, $value->format('U'), '>=');
      $this->query->addWhere($this->options['group'], $orCondition);
      return;
    }
    if ($is_end_field === TRUE && $this->operator === '<=') {
      $orCondition = (new Condition('OR'));
      $orCondition
        ->condition($start_field, $value->format('U'), '<=')
        ->condition($end_field, $value->format('U'), $this->operator);
      $this->query->addWhere($this->options['group'], $orCondition);
      return;
    }
    $this->query->addWhereExpression($this->options['group'], "$field $this->operator " . $value->format('U'));
  }

}
