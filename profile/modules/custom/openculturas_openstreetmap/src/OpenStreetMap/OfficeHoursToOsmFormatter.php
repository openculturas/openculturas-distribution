<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\OpenStreetMap;

use function array_filter;
use function array_pop;
use function implode;
use function is_array;
use function substr;

class OfficeHoursToOsmFormatter {

  public static function format(array $raw_values): string {
    if ($raw_values === []) {
      return '24/7 closed';
    }

    $opening_hours = [];
    $is_24_27 = NULL;
    $has_7_days = count(array_filter($raw_values, static function (array $value) {
        /** @var array{day:int, all_day:boolean, starthours:int, endhours:int} $value */
      return $value['all_day'];
    })) >= 7;
    foreach ($raw_values as $value) {
      /** @var array{day:int, all_day:boolean, starthours:int, endhours:int} $value */
      if ($value['day'] < 0) {
        continue;
      }

      if ($value['day'] > 6) {
        continue;
      }

      if (isset($opening_hours[$value['day']]) && $opening_hours[$value['day']] === -1) {
        continue;
      }

      if ($value['all_day'] === TRUE) {
        $opening_hours[$value['day']] = -1;
        if ($has_7_days && $is_24_27 === NULL) {
          $is_24_27 = TRUE;
        }

        continue;
      }

      $is_24_27 = FALSE;
      /* @phpstan-ignore-next-line */
      $opening_hours[$value['day']][] = $value;
    }

    if ($is_24_27) {
      return '24/7';
    }

    $days = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

    $range = [];
    $time_formatter = static function (int $time): string {
      $hour = substr((string) $time, 0, -2);
      $min = substr((string) $time, -2);
      return $hour . ':' . $min;
    };
    $same_day_range_first = NULL;
    $added_days = 1;
    foreach ($opening_hours as $day => $range_values) {
      if (is_array($range_values)) {
        if ($same_day_range_first === NULL) {
          $same_day_range_first = $day;
        }

        $same_day_range_last = $day;
        if ($day !== $added_days || count($range_values) > 1) {
          $same_day_range_first = $day;
          $added_days = $day;
        }
        elseif (isset($same_day_time_formatted)) {
          foreach ($range_values as $value) {
            /** @var array{day:int, all_day:boolean, starthours:int,endhours:int} $value */
            $same_day_time_formatted_current = $time_formatter($value['starthours']) . '-' . $time_formatter($value['endhours']);
            if ($same_day_time_formatted_current !== $same_day_time_formatted) {
              $same_day_range_first = $day;
              $same_day_range_last = $day;
            }

            unset($value);
          }
        }

        $current_day = $days[$day];
        if ($same_day_range_first && $same_day_range_last && $same_day_range_first !== $same_day_range_last) {
          $current_day = $days[$same_day_range_first] . '-' . $days[$same_day_range_last];
          array_pop($range);
        }

        $time_range = [];
        foreach ($range_values as $value) {
          /** @var array{day:int, all_day:boolean, starthours:int,endhours:int} $value */
          $same_day_time_formatted = $time_formatter($value['starthours']) . '-' . $time_formatter($value['endhours']);
          $time_range[] = $same_day_time_formatted;
          unset($value);
        }

        $current_day .= ' ' . implode(',', $time_range);
        $range[] = $current_day;
      }
      else {
        $same_day_range_first = NULL;
        $current_day = $days[$day];
        $current_day .= ' 00:00-24:00';
        $range[] = $current_day;
      }

      $added_days++;
    }

    return implode('; ', $range);
  }

}
