<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas_openstreetmap\ExistingSite;

use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\node\NodeInterface;
use Drupal\openculturas_openstreetmap\OpenStreetMap\DrupalToOpenStreetMapTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the OSM opening_hours tag built from field_office_hours.
 *
 * Each case saves a location with the given office hours and loads it again,
 * so the formatter receives the values in the form the field storage returns.
 */
#[Group('openculturas')]
final class OpeningHoursTransformTest extends ExistingSiteBase {

  /**
   * Tests the opening_hours tag for one set of office hours.
   *
   * @param list<array{day: int, all_day: bool, starthours: int, endhours: int}> $office_hours
   *   The field_office_hours values of the location.
   * @param string $expected
   *   The expected opening_hours tag value.
   */
  #[DataProvider('officeHoursProvider')]
  public function testOpeningHours(array $office_hours, string $expected): void {
    $node = $this->createNode([
      'type' => 'location',
      'title' => $this->randomMachineName(),
      'field_office_hours' => $office_hours,
    ]);
    $node_id = $node->id();
    $this->assertNotNull($node_id);
    $loaded_node = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadUnchanged($node_id);
    $this->assertInstanceOf(NodeInterface::class, $loaded_node);

    $this->assertSame($expected, DrupalToOpenStreetMapTransformer::transform($loaded_node, 'opening_hours'));
  }

  /**
   * Provides office hours and the opening_hours tag built from them.
   *
   * @return array<string, array{list<array{day: int, all_day: bool, starthours: int, endhours: int}>, string}>
   *   Office hours and the expected tag value, keyed by case name.
   */
  public static function officeHoursProvider(): array {
    return [
      'no office hours' => [[], '24/7 closed'],
      'every day all day' => [
        array_map(self::allDay(...), range(0, 6)),
        '24/7',
      ],
      'equal weekdays merge into a range' => [
        [
          ...array_map(static fn (int $day): array => self::slot($day, 1000, 1900), range(1, 5)),
          self::slot(6, 1100, 1400),
        ],
        'Mo-Fr 10:00-19:00; Sa 11:00-14:00',
      ],
      'two slots on one day' => [
        [
          self::slot(1, 1000, 1200),
          self::slot(1, 1400, 1800),
          self::slot(2, 1000, 1800),
        ],
        'Mo 10:00-12:00,14:00-18:00; Tu 10:00-18:00',
      ],
      'one day all day' => [
        [
          self::allDay(1),
          self::slot(2, 1000, 1800),
        ],
        'Mo 00:00-24:00; Tu 10:00-18:00',
      ],
      'all day on every day but one is not 24/7' => [
        [
          ...array_map(self::allDay(...), range(1, 6)),
          self::slot(0, 1000, 1800),
        ],
        'Mo 00:00-24:00; Tu 00:00-24:00; We 00:00-24:00; Th 00:00-24:00; Fr 00:00-24:00; Sa 00:00-24:00; Su 10:00-18:00',
      ],
      'sunday after saturday starts a new rule' => [
        array_map(static fn (int $day): array => self::slot($day, 1400, 1800), [5, 6, 0]),
        'Fr-Sa 14:00-18:00; Su 14:00-18:00',
      ],
      'exception dates are ignored' => [
        [
          self::slot(1, 1000, 1800),
          self::slot(strtotime('2030-12-24 00:00:00 UTC'), 1000, 1200),
        ],
        'Mo 10:00-18:00',
      ],
    ];
  }

  /**
   * Builds a time slot of field_office_hours.
   *
   * @return array{day: int, all_day: bool, starthours: int, endhours: int}
   *   The field item value.
   */
  private static function slot(int $day, int $start_hours, int $end_hours): array {
    return [
      'day' => $day,
      'all_day' => FALSE,
      'starthours' => $start_hours,
      'endhours' => $end_hours,
    ];
  }

  /**
   * Builds an all-day item of field_office_hours.
   *
   * @return array{day: int, all_day: bool, starthours: int, endhours: int}
   *   The field item value.
   */
  private static function allDay(int $day): array {
    return [
      'day' => $day,
      'all_day' => TRUE,
      'starthours' => 0,
      'endhours' => 0,
    ];
  }

}
