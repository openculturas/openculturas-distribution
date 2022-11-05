<?php

namespace Drupal\openculturas_custom\Plugin\DateAugmenter;

use Drupal\addtocal_augment\Plugin\DateAugmenter\AddToCal as AddToCalOrigin;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use function array_map;
use function mb_strlen;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function str_replace;


class AddToCal extends AddToCalOrigin {

  /**
   * {@inheritdoc}
   */
  public function buildLinks(array $output, DrupalDateTime $start, DrupalDateTime $end = NULL, array $options = []) {
    $google_link = [];
    // Use provided settings if they exist, otherwise look for plugin config.
    $config = $options['settings'] ?? $this->getConfiguration();
    if (empty($config['event_title']) && !isset($options['entity'])) {
      // TODO: log some kind of warning that we can't work without the entity
      // or a provided title?
      return;
    }
    $end_fallback = $end ?? $start;

    $now = $this->getCurrentDate();
    // For a recurring date, determine if the last instance is in the past.
    $upcoming_instance = FALSE;
    // TODO: Validate that if set, $options['ends'] is DrupalDateTime.
    if (!empty($options['repeats']) && (empty($options['ends']) || $options['ends'] > $now)) {
      $upcoming_instance = TRUE;
    }
    if (!$upcoming_instance && $end_fallback < $now && !$config['past_events']) {
      return;
    }
    $entity = $options['entity'] ?? NULL;
    if ($end === null) {
      $end = $start;
    }
    if ($start instanceof DrupalDateTime && $tz = $start->getTimezone()) {
      $timezone = $tz->getName();
    }
    else {
      $tz = $this->configFactory->get('system.date')->get('timezone');
      $timezone = $tz['default'];
    }
    if (isset($options['allday']) && $options['allday']) {
      $start_formatted = $start->format("Ymd", $timezone);
      // Offset the end by one day for calendar ingestion.
      $end->add(new \DateInterval('P1D'));
      $end_formatted = $end->format("Ymd", $timezone);
      $prefix = ':';
    }
    else {
      $date_format = "Ymd\\THi00";
      if ($timezone !== '' && $timezone !== '0') {
        $prefix = ';TZID=' . $timezone . ':';
      }
      else {
        $date_format .= "\\Z";
        $prefix = ':';
      }
      $start_formatted = $start->format($date_format, $timezone);
      $end_formatted = $end->format($date_format, $timezone);
    }
    if (!empty($config['event_title'])) {
      $label = $this->parseField($config['event_title'], $entity);
    }
    else {
      $label = $this->parseField($entity->label(), FALSE);
    }
    $description = NULL;
    if (!empty($config['description'])) {
      $description = $this->parseField($config['description'], $entity, TRUE, TRUE);
      $google_link['details'] = $this->parseField($config['description'], $entity, TRUE, TRUE, '<br><p><b><u><a><ul><ol>');
      $google_link['details'] = str_replace('</p>' . PHP_EOL . PHP_EOL . '<p>', '</p><p>', $google_link['details']);

      $max_length = $config['max_desc'] ?? 60;
      if ($max_length) {
        // TODO: Use Smart Trim if available.
        // TODO: Make the use of ellipsis configurable?
        $description = trim(substr($description, 0, $max_length)) . '...';
      }
    }
    $location = NULL;
    if (!empty($config['location'])) {
      $location = $this->parseField($config['location'], $entity, TRUE);
    }
    $uuid = $entity->uuid() ?? Html::getUniqueId($label);

    // Build output.
    $ical_link = ['data:text/calendar;charset=utf8,BEGIN:VCALENDAR'];
    $ical_link[] = 'PRODID:' . $this->configFactory->get('system.site')->get('name');
    if ($timezone !== '' && $timezone !== '0') {
      $offset_from = $start->format('O', $timezone);
      $offset_to = $end->format('O', $timezone);

      // Timezone must precede VEVENT in iCal format
      // per icalendar.org/iCalendar-RFC-5545/3-6-5-time-zone-component.html .
      $google_link['ctz'] = $timezone;
      $ical_link[] = 'BEGIN:VTIMEZONE';
      $ical_link[] = 'TZID:' . $timezone;
      $ical_link[] = 'BEGIN:STANDARD';
      $ical_link[] = 'TZOFFSETFROM:' . $offset_from;
      $ical_link[] = 'TZOFFSETTO:' . $offset_to;
      $ical_link[] = 'END:STANDARD';
      $ical_link[] = 'END:VTIMEZONE';
    }
    $ical_link[] = 'VERSION:2.0';
    $ical_link[] = 'BEGIN:VEVENT';
    $ical_link[] = 'UID:' . $uuid;

    // Title.
    $ical_link[] = 'SUMMARY:' . $label;
    $google_link['text'] = $label;

    // Dates.
    // As per RFC 2445 4.8.7.2 the DTSTAMP property must be in UTC.
    $now->setTimezone(new \DateTimeZone('UTC'));
    $ical_link[] = 'DTSTAMP:' . $now->format('Ymd\\THi00\\Z');
    $ical_link[] = 'DTSTART' . $prefix . $start_formatted;
    $ical_link[] = 'DTEND' . $prefix . $end_formatted;
    $google_link['dates'] = $start_formatted . '/' . $end_formatted;

    // Recurrence.
    if (!empty($options['repeats'])) {
      $ical_link[] = '' . $options['repeats'];
      $google_link['recur'] = $options['repeats'];
    }

    // Description.
    if ($description) {
      $ical_link[] = 'DESCRIPTION:' . str_replace(PHP_EOL, '\\n', $description);
    }

    // Location.
    if ($location) {
      $ical_link[] = 'LOCATION:' . $location;
      $google_link['location'] = $location;
    }
    $ical_link[] = 'END:VEVENT';
    $ical_link[] = 'END:VCALENDAR';

    /**
     * Append every 70 chars a url encoded CRLF sequence followed by a whitespace.
     *
     * @link https://icalendar.org/iCalendar-RFC-5545/3-1-content-lines.html
     */
    $ical_link = array_map(fn($content): ?string => mb_strlen($content) >= 70 ? preg_replace(sprintf('/(%s)/', str_repeat('.', 70)), '${1}%0D%0A%20', $content) : $content, $ical_link);
    return [
      'ical' => $ical_link,
      'google' => $google_link,
    ];
  }

  /**
   * Manipulate the provided value, checking for tokens and cleaning up.
   *
   * @param string $field_value
   *   The value to manipulate.
   * @param mixed $entity
   *   The entity whose values can be used to replace tokens.
   * @param bool $strip_markup
   *   Whether or not to clean up the output.
   * @param bool $keep_line_breaks
   *   Whether or not to keep line breaks. e. g. for descriptions.
   * @param string $allowed_tags
   *   Allowed tags. e. g. google description
   *
   * @return string
   *   The manipulated value, prepared for use in a link href.
   */
  public function parseField($field_value, $entity, $strip_markup = FALSE, $keep_line_breaks = FALSE, $allowed_tags = NULL): string {
    if (\Drupal::hasService('token') && $entity) {
      $token_service = \Drupal::service('token');
      $token_data = [
        $entity->getEntityTypeId() => $entity,
      ];
      $field_value = $token_service->replace($field_value, $token_data, ['clear' => TRUE]);
    }
    if ($strip_markup) {
      // Strip tags. Requires decoding entities, which will be re-encoded later.
      $field_value = strip_tags(html_entity_decode($field_value), $allowed_tags);

      // Strip out line breaks.
      $field_value = $keep_line_breaks ? preg_replace('/\r\n/m', PHP_EOL, $field_value) : preg_replace('/\n|\r|\r\n|\t/m', ' ', $field_value);

      // Strip out non-breaking spaces.
      $field_value = (string) str_replace(['&nbsp;', "\xc2\xa0"], ' ', $field_value);

      // Strip out extra spaces.
      $field_value = $keep_line_breaks ? $field_value : trim(preg_replace('/\s\s+/', ' ', $field_value));
    }
    return trim($field_value);
  }

}
