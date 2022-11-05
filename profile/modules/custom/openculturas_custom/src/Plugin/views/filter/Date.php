<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\views\filter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Database\Query\Condition;
use Drupal\smart_date\Plugin\views\filter\Date as SmartDateDate;
use function is_array;
use function str_replace;

/**
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date")
 */
class Date extends SmartDateDate {

  /**
   * {@inheritdoc}
   */
  public function operators(): array {
    $operators = parent::operators();
    $operators['starts_on_after'] = [
      'title' => $this->t('Starts on or after'),
      'short' => $this->t('Starts on or after'),
      'method' => 'startsOrEndOnOrAfter',
      'values' => 1,
    ];
    $operators['ends_on_after'] = [
      'title' => $this->t('Ends on or after'),
      'short' => $this->t('Ends on or after'),
      'method' => 'startsOrEndOnOrAfter',
      'values' => 1,
    ];
    return $operators;
  }

  /**
   * Adding starts/ends on or after filter to the query.
   */
  protected function startsOrEndOnOrAfter(string $field): void {
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
    }
    $start_field = str_replace('_end_value', '_value', $field);
    $end_field = str_replace('_value', '_end_value', $start_field);
    $operator = $field === $end_field ? '<=' : '>=';
    if (isset($min) && is_array($min) && $operator === '>=') {
      $value = $value::createFromArray($min, $timezone);
    }
    elseif (isset($max) && is_array($max) && $operator === '<=') {
      $value = $value::createFromArray($max, $timezone);
    }

    $orCondition = (new Condition('OR'));
    $orCondition
      ->condition($start_field, $value->format('U'), $operator)
      ->condition($end_field, $value->format('U'), $operator);
    $this->query->addWhere($this->options['group'], $orCondition);
  }

}
