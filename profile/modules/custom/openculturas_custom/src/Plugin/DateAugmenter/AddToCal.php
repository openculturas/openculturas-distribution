<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\DateAugmenter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\addtocal_augment\Plugin\DateAugmenter\AddToCal as AddToCalOrigin;
use function array_map;
use function mb_strlen;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function str_replace;
use function substr;
use function trim;

/**
 * Overrides the upstream AddToCal DateAugmenter plugin.
 *
 * Registered via openculturas_custom_date_augmenter_plugin_info_alter()
 * instead of a plugin attribute, so no #[DateAugmenter] declaration is needed.
 *
 * Deviations from the upstream class:
 *
 * buildLinks():
 * - Google Calendar description is built with a separate parseFieldValue()
 *   call that preserves basic HTML tags (<br>, <p>, etc.), because Google Calendar
 *   renders HTML in event descriptions. The iCal description strips all markup.
 * - RFC 5545 section 3.1 line folding (max 70 chars per content line) is
 *   applied to the iCal output; the upstream does not implement this.
 * - All-day events offset DTEND by +1 day, as iCalendar treats DTEND as
 *   exclusive for DATE values (RFC 5545 section 3.6.1).
 * - Description line breaks are always preserved ($keep_line_breaks = TRUE);
 *   the upstream makes this configurable via retain_spacing.
 * - SUMMARY, DESCRIPTION, and LOCATION are written as raw iCal content-line
 *   values without rawurlencode(); the upstream URL-encodes them.
 * - Ellipsis on truncated descriptions is always appended; the upstream makes
 *   this configurable.
 *
 * parseFieldValue():
 * - New method, used instead of the upstream's parseField(), so its
 *   signature does not need to stay compatible with the parent method.
 * - Adds $allowed_tags to allow selective HTML tag stripping, used for the
 *   Google description.
 * - When $keep_line_breaks is TRUE, CRLF pairs are normalised to LF before
 *   further processing; the upstream leaves whitespace untouched in that case.
 */
class AddToCal extends AddToCalOrigin {

  /**
   * Builds a prepared array of data for output.
   *
   * @param array $output
   *   The existing render array, to be augmented.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The object which contains the start time.
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $end
   *   The optional object which contains the end time.
   * @param array $options
   *   An array of options to further guide output.
   *
   * @return array|null
   *   The prepared ical/outlook/google link data, or NULL if no links
   *   should be rendered.
   */
  #[\Override]
  public function buildLinks(array $output, DrupalDateTime $start, ?DrupalDateTime $end = NULL, array $options = []): ?array {
    $google_link = [];
    // Use provided settings if they exist, otherwise look for plugin config.
    $config = $options['settings'] ?? $this->getConfiguration();
    if (empty($config['event_title']) && !isset($options['entity'])) {
      // @todo log some kind of warning that we can't work without the entity
      // or a provided title?
      return NULL;
    }

    $def_format = 'Ymd\\THi00';
    $def_format_z = $def_format . '\\Z';
    $end_fallback = $end ?? $start;
    $now = $this->getCurrentDate();
    // For a recurring date, determine if the last instance is in the past.
    $upcoming_instance = FALSE;
    // @todo Validate that if set, $options['ends'] is DrupalDateTime.
    if (!empty($options['repeats']) && (empty($options['ends']) || $options['ends'] > $now)) {
      $upcoming_instance = TRUE;
    }

    if (!$upcoming_instance && $end_fallback < $now && !$config['past_events']) {
      return NULL;
    }

    $entity = $options['entity'] ?? NULL;
    if (!$end instanceof DrupalDateTime) {
      $end = $start;
    }

    $timezone = $start->getTimezone()->getName();
    if (isset($options['allday']) && $options['allday']) {
      $start_formatted = $start->format("Ymd", ['timezone' => $timezone]);
      // Offset the end by one day for calendar ingestion.
      $end->add(new \DateInterval('P1D'));
      $end_formatted = $end->format("Ymd", ['timezone' => $timezone]);
      $prefix = ':';
    }
    else {
      $date_format = $def_format;
      if ($timezone !== '' && $timezone !== '0') {
        $prefix = ';TZID=' . $timezone . ':';
      }
      else {
        $date_format = $def_format_z;
        $prefix = ':';
      }

      $start_formatted = $start->format($date_format, ['timezone' => $timezone]);
      $end_formatted = $end->format($date_format, ['timezone' => $timezone]);
    }

    if (!empty($config['event_title'])) {
      $label = $this->parseFieldValue($config['event_title'], $entity);
    }
    else {
      $label = $this->parseFieldValue($entity->label(), FALSE);
    }

    $description = NULL;
    if (!empty($config['description'])) {
      $description = $this->parseFieldValue($config['description'], $entity, TRUE, TRUE);
      $google_link['details'] = $this->parseFieldValue($config['description'], $entity, TRUE, TRUE, '<br><p><b><u><a><ul><ol>');
      $google_link['details'] = str_replace('</p>' . PHP_EOL . PHP_EOL . '<p>', '</p><p>', $google_link['details']);
      $max_length = $config['max_desc'] ?? 60;
      if ($max_length) {
        // @todo Use Smart Trim if available.
        // @todo Make the use of ellipsis configurable?
        $description = trim(substr($description, 0, $max_length)) . '...';
      }
    }

    $location = NULL;
    if (!empty($config['location'])) {
      $location = $this->parseFieldValue($config['location'], $entity, TRUE, FALSE, NULL, TRUE);
    }

    $uuid = $entity->uuid() ?? Html::getUniqueId($label);

    // Build output.
    $ical_link = ['data:text/calendar;charset=utf8,BEGIN:VCALENDAR'];
    $ical_link[] = 'PRODID:' . $this->configFactory->get('system.site')->get('name');
    if ($timezone !== '' && $timezone !== '0') {
      $offset_from = $start->format('O', ['timezone' => $timezone]);
      $offset_to = $end->format('O', ['timezone' => $timezone]);

      // Timezone must precede VEVENT in iCal format
      // per icalendar.org/iCalendar-RFC-5545/3-6-5-time-zone-component.html .
      $google_link['ctz'] = $timezone;
      $ical_link['tz'][] = 'BEGIN:VTIMEZONE';
      $ical_link['tz'][] = 'TZID:' . $timezone;
      $ical_link['tz'][] = 'BEGIN:STANDARD';
      $ical_link['tz'][] = 'TZOFFSETFROM:' . $offset_from;
      $ical_link['tz'][] = 'TZOFFSETTO:' . $offset_to;
      $ical_link['tz'][] = 'END:STANDARD';
      $ical_link['tz'][] = 'END:VTIMEZONE';
    }

    $ical_link[] = 'VERSION:2.0';
    $ical_link[] = 'BEGIN:VEVENT';
    $ical_link[] = 'UID:' . $uuid;

    // Title.
    $ical_link[] = 'SUMMARY:' . $label;
    $google_link['text'] = $label;

    // Dates.
    // As per RFC 2445 4.8.7.2 the DTSTAMP property must be in UTC.
    $utc = new \DateTimeZone('UTC');
    $now->setTimezone($utc);
    $ical_link[] = 'DTSTAMP:' . $now->format($def_format_z);
    $ical_link['start'] = 'DTSTART' . $prefix . $start_formatted;
    $ical_link['end'] = 'DTEND' . $prefix . $end_formatted;
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

    /* Append every 70 chars a url encoded CRLF sequence followed by a whitespace. see https://icalendar.org/iCalendar-RFC-5545/3-1-content-lines.html */
    $ical_link = array_map(static fn(array|string $content): null|string|array => is_string($content) && mb_strlen($content) >= 70 ? preg_replace(sprintf('/(%s)/', str_repeat('.', 70)), '${1}%0D%0A%20', $content) : $content, $ical_link);

    // Set start/end dates timezone to UTC for Outlook.
    $outlook_link = $ical_link;
    if (isset($outlook_link['tz'])) {
      unset($outlook_link['tz']);
    }

    $start->setTimezone($utc);
    $end->setTimezone($utc);
    $outlook_link['start'] = 'DTSTART:' . $start->format($def_format_z);
    $outlook_link['end'] = 'DTEND:' . $end->format($def_format_z);

    return [
      'ical' => $ical_link,
      'outlook' => $outlook_link,
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
   *   Allowed tags. e. g. google description.
   * @param bool $addslashes
   *   Whether to escape commas per iCal RFC 5545.
   *
   * @return string
   *   The manipulated value, prepared for use in a link href.
   */
  public function parseFieldValue($field_value, $entity, $strip_markup = FALSE, $keep_line_breaks = FALSE, $allowed_tags = NULL, $addslashes = FALSE): string {
    if (\Drupal::hasService('token') && $entity) {
      $token_service = \Drupal::service('token');
      $token_data = [
        $entity->getEntityTypeId() => $entity,
      ];
      $field_value = $token_service->replace($field_value, $token_data, ['clear' => TRUE]);
    }

    if ($strip_markup) {
      // Strip tags. Requires decoding entities, which will be re-encoded later.
      $field_value = strip_tags(html_entity_decode((string) $field_value), $allowed_tags);

      // Strip out line breaks.
      $field_value = $keep_line_breaks
        ? preg_replace('/\r\n/m', PHP_EOL, $field_value) ?? $field_value
        : preg_replace('/\n|\r|\r\n|\t/m', ' ', $field_value) ?? $field_value;

      // Strip out non-breaking spaces.
      $field_value = str_replace(['&nbsp;', "\xc2\xa0"], ' ', $field_value);

      // Strip out extra spaces.
      $field_value = $keep_line_breaks ? $field_value : trim(preg_replace('/\s\s+/', ' ', $field_value) ?? $field_value);
    }

    if ($addslashes) {
      $field_value = str_replace(',', '\\,', $field_value);
    }

    return trim((string) $field_value);
  }

}
