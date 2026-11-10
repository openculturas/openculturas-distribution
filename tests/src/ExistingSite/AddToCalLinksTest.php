<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\addtocal_augment\IcsLink;
use Drupal\node\NodeInterface;
use Drupal\openculturas_custom\Plugin\DateAugmenter\AddToCal;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the add to calendar links built by the overridden AddToCal plugin.
 */
#[Group('openculturas')]
class AddToCalLinksTest extends ExistingSiteBase {

  /**
   * Plugin settings as used by the date displays.
   */
  private const array SETTINGS = [
    'label' => 'Add to calendar',
    'event_title' => '[node:title]',
    'location' => 'Main street 1, Berlin',
    'description' => '[node:body]',
    'retain_spacing' => TRUE,
    'icons' => FALSE,
    'max_desc' => 60,
    'ellipsis' => FALSE,
    'past_events' => FALSE,
    'target' => 'modal',
    'ignore_timezone_if_UTC' => TRUE,
  ];

  /**
   * The links are signed ICS URLs with valid iCalendar content.
   */
  public function testTimedEventLinks(): void {
    $title = 'Timed event ' . $this->randomMachineName() . ' with a title that is longer than seventy characters';
    $node = $this->createEventNode($title, '<p>First line</p><p>Second line</p>');
    $output = $this->augment($node, $this->date('2099-10-29 15:00:00'), $this->date('2099-10-29 16:00:00'));

    $this->assertArrayHasKey('addtocal', $output);
    $this->assertNotSame('', $output['addtocal']['#id']);

    foreach (['#ical', '#outlook'] as $key) {
      $ical = $this->decodeIcsUrl($output['addtocal'][$key]);
      $this->assertValidIcal($ical);
      $this->assertStringContainsString('SUMMARY:Timed event ', $ical);
      $this->assertStringContainsString('LOCATION:Main street 1\, Berlin', $this->unfold($ical));
      $this->assertStringContainsString('SUMMARY:' . $title, $this->unfold($ical));
    }

    $ical = $this->decodeIcsUrl($output['addtocal']['#ical']);
    $this->assertStringContainsString("DTSTART;TZID=Europe/Berlin:20991029T150000\r\n", $ical);
    $this->assertStringContainsString("DTEND;TZID=Europe/Berlin:20991029T160000\r\n", $ical);

    $outlook = $this->decodeIcsUrl($output['addtocal']['#outlook']);
    $this->assertStringContainsString("DTSTART:20991029T140000Z\r\n", $outlook);
    $this->assertStringContainsString("DTEND:20991029T150000Z\r\n", $outlook);
    $this->assertStringNotContainsString('VTIMEZONE', $outlook);

    $google = $output['addtocal']['#google'];
    $this->assertInstanceOf(Url::class, $google);
    $googleUrl = $google->toString();
    $this->assertStringStartsWith('https://calendar.google.com/calendar/render?action=TEMPLATE&', $googleUrl);
    parse_str((string) parse_url($googleUrl, PHP_URL_QUERY), $query);
    $this->assertSame($title, $query['text']);
    $this->assertSame('20991029T150000/20991029T160000', $query['dates']);
    $this->assertSame('Europe/Berlin', $query['ctz']);
    $this->assertIsString($query['details']);
    $this->assertStringContainsString('<p>First line</p>', $query['details'], 'Google keeps basic HTML in the description.');
  }

  /**
   * All-day events use an exclusive end date and keep the given dates intact.
   */
  public function testAllDayEventLinks(): void {
    $node = $this->createEventNode('All-day event ' . $this->randomMachineName(), '');

    // Smart Date sets the end of an all-day event to 23:59 of the last day.
    $output = $this->augment($node, $this->date('2099-10-29 00:00:00'), $this->date('2099-10-30 23:59:00'), ['allday' => TRUE]);

    $ical = $this->decodeIcsUrl($output['addtocal']['#ical']);
    $this->assertStringContainsString("DTSTART:20991029\r\n", $ical);
    $this->assertStringContainsString("DTEND:20991031\r\n", $ical);

    $outlook = $this->decodeIcsUrl($output['addtocal']['#outlook']);
    $this->assertStringContainsString("DTSTART:20991028T230000Z\r\n", $outlook);
    $this->assertStringContainsString("DTEND:20991030T225900Z\r\n", $outlook);

    parse_str((string) parse_url($output['addtocal']['#google']->toString(), PHP_URL_QUERY), $query);
    $this->assertSame('20991029/20991031', $query['dates']);

    // Without an end date, the end defaults to the start date object.
    $start = $this->date('2099-10-29 00:00:00');
    $output = $this->augment($node, $start, NULL, ['allday' => TRUE]);
    $this->assertSame('2099-10-29', $start->format('Y-m-d', ['timezone' => 'Europe/Berlin']), 'The start date of the caller keeps its day.');
    $ical = $this->decodeIcsUrl($output['addtocal']['#ical']);
    $this->assertStringContainsString("DTSTART:20991029\r\n", $ical);
    $this->assertStringContainsString("DTEND:20991030\r\n", $ical);
  }

  /**
   * The description is trimmed, also when the display sets no maximum length.
   */
  public function testDescriptionIsTrimmed(): void {
    $node = $this->createEventNode('Long event ' . $this->randomMachineName(), '<p>' . str_repeat('Lorem ipsum ', 300) . '</p>');

    $output = $this->augment($node, $this->date('2099-10-29 15:00:00'), NULL);
    $description = $this->descriptionOf($this->decodeIcsUrl($output['addtocal']['#ical']));
    $this->assertSame('Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum...', $description);

    $output = $this->augment($node, $this->date('2099-10-29 15:00:00'), NULL, [], ['max_desc' => 0]);
    $description = $this->descriptionOf($this->decodeIcsUrl($output['addtocal']['#ical']));
    $this->assertLessThanOrEqual(AddToCal::MAX_DESCRIPTION_LENGTH + 3, mb_strlen($description));
  }

  /**
   * The ICS URL returns the calendar file.
   */
  public function testIcsUrlServesCalendar(): void {
    $node = $this->createEventNode('Download event ' . $this->randomMachineName(), '<p>Text</p>');
    $output = $this->augment($node, $this->date('2099-10-29 15:00:00'), NULL);
    $url = $output['addtocal']['#ical'];
    $this->assertInstanceOf(Url::class, $url);

    $this->drupalGet($url->setAbsolute()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderContains('Content-Type', 'text/calendar');
    $this->assertSame($this->decodeIcsUrl($url), $this->getSession()->getPage()->getContent());
  }

  /**
   * The theme templates render the links without a download attribute.
   */
  public function testRenderedLinks(): void {
    $node = $this->createEventNode('Rendered event ' . $this->randomMachineName(), '<p>Text</p>');
    $output = $this->augment($node, $this->date('2099-10-29 15:00:00'), NULL);

    foreach (['modal', ''] as $target) {
      $element = $output['addtocal'];
      $element['#theme'] = $target === 'modal' ? 'addtocal_links__modal' : 'addtocal_links';
      $html = (string) $this->container->get('renderer')->renderInIsolation($element);

      $this->assertMatchesRegularExpression('#<a href="https://calendar\.google\.com/calendar/render\?action=TEMPLATE&amp;[^"]+" target="_blank"#', $html);
      $this->assertMatchesRegularExpression('#<a href="[^"]*/addtocal-augment/ics/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+" rel="nofollow" class="addtocal__link addtocal__ical"#', $html);
      $this->assertMatchesRegularExpression('#<a href="[^"]*/addtocal-augment/ics/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+" rel="nofollow" class="addtocal__link addtocal__outlook"#', $html);
      $this->assertStringNotContainsString('download=', $html);
    }
  }

  /**
   * Creates a node that is deleted after the test.
   */
  private function createEventNode(string $title, string $body): NodeInterface {
    return $this->createNode([
      'type' => 'page',
      'title' => $title,
      'body' => ['value' => $body, 'format' => 'basic_html'],
    ]);
  }

  /**
   * Creates a date in the site timezone.
   */
  private function date(string $value): DrupalDateTime {
    return DrupalDateTime::createFromFormat('Y-m-d H:i:s', $value, new \DateTimeZone('Europe/Berlin'));
  }

  /**
   * Runs the addtocal augmenter that the date displays use.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose values replace the tokens.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start date.
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $end
   *   The end date.
   * @param array<string, mixed> $options
   *   Additional options for the augmenter.
   * @param array<string, mixed> $settings
   *   Settings that override the default settings.
   *
   * @return array<string, mixed>
   *   The augmented render array.
   */
  private function augment(NodeInterface $node, DrupalDateTime $start, ?DrupalDateTime $end, array $options = [], array $settings = []): array {
    $plugin = $this->container->get('plugin.manager.dateaugmenter')->createInstance('addtocal');
    $this->assertInstanceOf(AddToCal::class, $plugin);
    $output = [];
    $plugin->augmentOutput($output, $start, $end, $options + [
      'entity' => $node,
      'settings' => $settings + self::SETTINGS,
    ]);
    return $output;
  }

  /**
   * Returns the iCalendar content of a signed ICS URL.
   */
  private function decodeIcsUrl(mixed $url): string {
    $this->assertInstanceOf(Url::class, $url);
    $this->assertSame('addtocal_augment.render_ics', $url->getRouteName());
    $parameters = $url->getRouteParameters();
    $ical = $this->container->get('addtocal_augment.ics_link')->verify($parameters['ics'], $parameters['signature']);
    $this->assertIsString($ical, sprintf('The URL has a valid %s signature.', IcsLink::class));
    return $ical;
  }

  /**
   * Checks the iCalendar structure and the RFC 5545 line rules.
   */
  private function assertValidIcal(string $ical): void {
    $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ical);
    $this->assertStringEndsWith("\r\nEND:VCALENDAR", $ical);
    $this->assertStringNotContainsString('%0D%0A', $ical);
    $this->assertStringNotContainsString('data:', $ical);
    foreach (explode("\r\n", $ical) as $line) {
      $this->assertStringNotContainsString("\n", $line, 'Lines are separated with CRLF only.');
      $this->assertLessThanOrEqual(75, strlen($line), sprintf('Line "%s" is folded.', $line));
    }
  }

  /**
   * Joins folded content lines.
   */
  private function unfold(string $ical): string {
    return str_replace("\r\n ", '', $ical);
  }

  /**
   * Returns the unfolded DESCRIPTION value.
   */
  private function descriptionOf(string $ical): string {
    $this->assertSame(1, preg_match('/^DESCRIPTION:(.*)$/m', $this->unfold($ical), $matches));
    return rtrim($matches[1], "\r");
  }

}
