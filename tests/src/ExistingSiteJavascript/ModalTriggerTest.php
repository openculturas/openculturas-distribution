<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSiteJavascript;

use Behat\Mink\Driver\Selenium2Driver;
use Drupal\Tests\openculturas\ExistingSiteJSTestBase;
use Drupal\Tests\openculturas\Traits\ModalTriggerTrait;
use Drupal\block_content\BlockContentInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the modal-trigger popover in the browser.
 */
#[Group('openculturas')]
class ModalTriggerTest extends ExistingSiteJSTestBase {

  use ModalTriggerTrait;

  /**
   * The scroll floor of the modal-trigger script: cookiesjsr scrollLimit + 1.
   */
  private const SCROLL_FLOOR = 251;

  /**
   * The browser window size before a test resized it.
   *
   * @var array{width: int, height: int}|null
   */
  private ?array $originalWindowSize = NULL;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // The browser session outlives this test, so later tests would otherwise
    // run with the changed window size.
    if ($this->originalWindowSize !== NULL) {
      $this->getSession()->resizeWindow($this->originalWindowSize['width'], $this->originalWindowSize['height']);
    }
    // Remove the consent cookie and the stored dismissals.
    $this->getSession()->executeScript('window.localStorage.clear();');
    $this->getSession()->setCookie('cookiesjsr', NULL);
    parent::tearDown();
  }

  /**
   * Resizes the browser window and restores the old size after the test.
   */
  private function resizeWindow(int $width, int $height): void {
    if ($this->originalWindowSize === NULL) {
      $this->originalWindowSize = [
        'width' => (int) $this->getSession()->evaluateScript('window.outerWidth'),
        'height' => (int) $this->getSession()->evaluateScript('window.outerHeight'),
      ];
    }
    $this->getSession()->resizeWindow($width, $height);
  }

  /**
   * A scroll-mode popover opens when its anchor is visible on a short page.
   */
  public function testScrollModeOpensOnShortPage(): void {
    $this->resizeWindow(1280, 4000);
    $popoverId = $this->visitPageWithModalTrigger($this->createModalTriggerBlockContent('scroll'));

    $scrollableHeight = (int) $this->getSession()->evaluateScript('document.documentElement.scrollHeight - window.innerHeight');
    $this->assertLessThan(self::SCROLL_FLOOR, $scrollableHeight, 'The page cannot scroll past the scroll floor.');
    $this->assertTrue((bool) $this->getSession()->evaluateScript(sprintf("document.querySelector('[data-modal-trigger-popover=\"%s\"]').getBoundingClientRect().top < window.innerHeight", $popoverId)), 'The anchor is in the viewport.');

    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens.');
  }

  /**
   * An open popover takes the focus and closes on Escape.
   */
  public function testOpenPopoverTakesFocusAndClosesOnEscape(): void {
    $popoverId = $this->visitPageWithModalTrigger($this->createModalTriggerBlockContent());
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens.');

    $this->assertTrue((bool) $this->getSession()->evaluateScript(sprintf("document.getElementById('%s').contains(document.activeElement)", $popoverId)), 'The focus is inside the popover.');

    $this->pressEscape();
    $this->assertTrue($this->waitForPopoverState($popoverId, FALSE), 'Escape closes the popover.');
  }

  /**
   * A popover hidden by the cookie settings layer opens again afterwards.
   *
   * Closing the cookie UI is no dismissal of the popover, so no cooldown is
   * written.
   */
  public function testPopoverOpensAgainAfterCookieLayerCloses(): void {
    $blockContent = $this->createModalTriggerBlockContent('delay', 0, 7);
    $popoverId = $this->visitPageWithModalTrigger($blockContent);
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens.');
    $this->assertSession()->elementExists('css', '#cookiesjsr');

    // Simulate the cookie settings layer: cookiesjsr mounts it into
    // #cookiesjsr and removes it again when the visitor closes it.
    $this->getSession()->executeScript("const layer = document.createElement('div'); layer.className = 'cookiesjsr-layer-wrapper'; document.getElementById('cookiesjsr').appendChild(layer);");
    $this->assertTrue($this->waitForPopoverState($popoverId, FALSE), 'The cookie settings layer hides the popover.');

    $this->getSession()->executeScript("document.querySelector('#cookiesjsr .cookiesjsr-layer-wrapper').remove();");
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens again after the cookie settings layer closes.');
    $this->assertNull($this->getDismissal($blockContent), 'The cookie settings layer does not write a dismissal.');
  }

  /**
   * A scroll-mode popover hidden by the cookie UI opens again afterwards.
   */
  public function testScrollModeOpensAgainAfterCookieLayerCloses(): void {
    $this->resizeWindow(1280, 4000);
    $popoverId = $this->visitPageWithModalTrigger($this->createModalTriggerBlockContent('scroll'));
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens.');

    $this->getSession()->executeScript("const layer = document.createElement('div'); layer.className = 'cookiesjsr-layer-wrapper'; document.getElementById('cookiesjsr').appendChild(layer);");
    $this->assertTrue($this->waitForPopoverState($popoverId, FALSE), 'The cookie settings layer hides the popover.');

    $this->getSession()->executeScript("document.querySelector('#cookiesjsr .cookiesjsr-layer-wrapper').remove();");
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens again after the cookie settings layer closes.');
  }

  /**
   * The cookie UI also hides the popover when its container is added late.
   *
   * A BigPipe placeholder can add #cookiesjsr after the behavior attached.
   */
  public function testLateCookieContainerHidesPopover(): void {
    $popoverId = $this->visitPageWithModalTrigger($this->createModalTriggerBlockContent());
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens.');

    // Attach the behavior again while #cookiesjsr does not exist.
    $this->getSession()->executeScript(sprintf("document.querySelectorAll('#cookiesjsr').forEach((container) => container.remove()); document.getElementById('%s').hidePopover(); once.remove('modalTrigger', '[data-modal-trigger-popover]'); Drupal.behaviors.modalTrigger.attach(document, drupalSettings);", $popoverId));
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens without a cookie container.');

    $this->getSession()->executeScript("const container = document.createElement('div'); container.id = 'cookiesjsr'; const layer = document.createElement('div'); layer.className = 'cookiesjsr-layer-wrapper'; container.appendChild(layer); document.body.appendChild(container);");
    $this->assertTrue($this->waitForPopoverState($popoverId, FALSE), 'The cookie settings layer in a late container hides the popover.');

    $this->getSession()->executeScript("document.querySelector('#cookiesjsr .cookiesjsr-layer-wrapper').remove();");
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens again after the cookie settings layer closes.');
  }

  /**
   * A delay-mode popover opens after its delay, also after the cookie UI.
   */
  public function testDelayModeWaitsForItsDelay(): void {
    $popoverId = $this->visitPageWithModalTrigger($this->createModalTriggerBlockContent('delay', 3));

    $this->getSession()->wait(1500);
    $this->assertFalse($this->isPopoverOpen($popoverId), 'The popover is closed before the delay ends.');
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens after the delay.');

    $this->getSession()->executeScript("const layer = document.createElement('div'); layer.className = 'cookiesjsr-layer-wrapper'; document.getElementById('cookiesjsr').appendChild(layer);");
    $this->assertTrue($this->waitForPopoverState($popoverId, FALSE), 'The cookie settings layer hides the popover.');
    $this->getSession()->executeScript("document.querySelector('#cookiesjsr .cookiesjsr-layer-wrapper').remove();");

    $this->getSession()->wait(1500);
    $this->assertFalse($this->isPopoverOpen($popoverId), 'The delay runs again after the cookie settings layer closes.');
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens after the delay ran again.');
  }

  /**
   * A scroll-mode popover opens when its anchor scrolls into view.
   */
  public function testScrollModeOpensWhenAnchorScrollsIntoView(): void {
    $this->resizeWindow(1280, 800);
    [$popoverId] = $this->visitPageWithModalTriggers([$this->createModalTriggerBlockContent('scroll')], TRUE, 3);
    $anchorSelector = sprintf("document.querySelector('[data-modal-trigger-popover=\"%s\"]')", $popoverId);

    $this->assertTrue((bool) $this->getSession()->evaluateScript($anchorSelector . '.getBoundingClientRect().top > window.innerHeight'), 'The anchor is below the viewport.');
    $this->getSession()->wait(1500);
    $this->assertFalse($this->isPopoverOpen($popoverId), 'The popover is closed before the anchor scrolls into view.');

    $this->getSession()->executeScript($anchorSelector . ".scrollIntoView({block: 'center'});");
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens when the anchor scrolls into view.');
  }

  /**
   * The real cookie banner blocks the popover until the visitor accepts.
   */
  public function testCookieBannerBlocksPopoverUntilAccepted(): void {
    // A page that cannot scroll shows the cookie banner at once.
    $this->resizeWindow(1280, 4000);
    $blockContent = $this->createModalTriggerBlockContent('delay', 2, 7);
    [$popoverId] = $this->visitPageWithModalTriggers([$blockContent], FALSE);

    $this->assertTrue($this->getSession()->wait(5000, "document.querySelector('.cookiesjsr-banner--active') !== null"), 'The cookie banner shows.');
    $this->getSession()->wait(3000);
    $this->assertFalse($this->isPopoverOpen($popoverId), 'The popover stays closed while the cookie banner shows.');

    $acceptButton = $this->assertSession()->elementExists('css', '.cookiesjsr-banner--active .cookiesjsr-btn.allowAll');
    $acceptButton->click();
    $this->assertTrue($this->getSession()->wait(5000, "document.querySelector('.cookiesjsr-banner--active') === null"), 'The cookie banner closes.');

    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens after the cookie banner closes.');
    $this->assertNull($this->getDismissal($blockContent), 'The cookie banner does not write a dismissal.');
  }

  /**
   * A plain close keeps the popover closed for the cooldown.
   */
  public function testCloseStartsCooldown(): void {
    $blockContent = $this->createModalTriggerBlockContent('delay', 0, 7);
    $popoverId = $this->visitPageWithModalTrigger($blockContent);
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens.');

    $this->click(sprintf('#%s .modal-trigger__close', $popoverId));
    $this->assertTrue($this->waitForPopoverState($popoverId, FALSE), 'The close button closes the popover.');
    $until = $this->waitForDismissal($blockContent);
    $this->assertNotNull($until, 'The close writes a dismissal.');
    $this->assertGreaterThan((time() + 6 * 86400) * 1000, $until, 'The dismissal lasts for the cooldown.');
    $this->assertLessThan((time() + 8 * 86400) * 1000, $until, 'The dismissal ends after the cooldown.');

    $this->assertPopoverStaysClosedAfterReload();
  }

  /**
   * The "Don't show again" button keeps the popover closed permanently.
   */
  public function testDontShowAgainDismissesPermanently(): void {
    $blockContent = $this->createModalTriggerBlockContent('delay', 0, 0, TRUE);
    $popoverId = $this->visitPageWithModalTrigger($blockContent);
    $this->assertTrue($this->waitForPopoverState($popoverId, TRUE), 'The popover opens.');

    $this->click(sprintf('#%s [data-modal-trigger-dont-show-again]', $popoverId));
    $this->assertTrue($this->waitForPopoverState($popoverId, FALSE), '"Don\'t show again" closes the popover.');
    $until = $this->waitForDismissal($blockContent);
    $this->assertNotNull($until, '"Don\'t show again" writes a dismissal.');
    $this->assertGreaterThan((time() + 50 * 365 * 86400) * 1000, $until, 'The dismissal is permanent.');

    $this->assertPopoverStaysClosedAfterReload();
  }

  /**
   * Several popovers on one page open on top of each other.
   */
  public function testSeveralPopoversCloseOneAfterAnother(): void {
    [$firstPopoverId, $secondPopoverId] = $this->visitPageWithModalTriggers([
      $this->createModalTriggerBlockContent(),
      $this->createModalTriggerBlockContent(),
    ]);
    $this->assertTrue($this->waitForPopoverState($firstPopoverId, TRUE), 'The first popover opens.');
    $this->assertTrue($this->waitForPopoverState($secondPopoverId, TRUE), 'The second popover opens.');

    // The popover that opened last is on top.
    $this->click(sprintf('#%s .modal-trigger__close', $secondPopoverId));
    $this->assertTrue($this->waitForPopoverState($secondPopoverId, FALSE), 'The second popover closes.');
    $this->assertTrue($this->isPopoverOpen($firstPopoverId), 'The first popover stays open.');

    $this->click(sprintf('#%s .modal-trigger__close', $firstPopoverId));
    $this->assertTrue($this->waitForPopoverState($firstPopoverId, FALSE), 'The first popover closes.');
  }

  /**
   * Visits a new page that references the block_content, without cookie banner.
   *
   * @return string
   *   The ID of the popover element.
   */
  private function visitPageWithModalTrigger(BlockContentInterface $blockContent): string {
    [$popoverId] = $this->visitPageWithModalTriggers([$blockContent]);
    return $popoverId;
  }

  /**
   * Visits a new page that references each block_content in one paragraph.
   *
   * @param list<\Drupal\block_content\BlockContentInterface> $blockContents
   *   The block_content entities, in page order.
   * @param bool $acceptCookies
   *   Whether a consent cookie keeps the cookie banner hidden.
   * @param int $textParagraphsBefore
   *   The number of long text paragraphs above the references.
   *
   * @return list<string>
   *   The IDs of the popover elements, in page order.
   */
  private function visitPageWithModalTriggers(array $blockContents, bool $acceptCookies = TRUE, int $textParagraphsBefore = 0): array {
    $paragraphStorage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $paragraphs = [];
    for ($index = 0; $index < $textParagraphsBefore; $index++) {
      $paragraphs[] = $paragraphStorage->create([
        'type' => 'text',
        'field_body' => [
          'value' => '<p>' . str_repeat('Text above the modal trigger block. ', 200) . '</p>',
          'format' => 'basic_html',
        ],
      ]);
    }
    foreach ($blockContents as $blockContent) {
      $paragraphs[] = $paragraphStorage->create([
        'type' => 'block_editorial',
        'field_block_ref' => $this->buildBlockReferenceValue($blockContent, 'Paragraph label', FALSE),
      ]);
    }
    $node = $this->createNode([
      'type' => 'page',
      'title' => 'Modal trigger test ' . $this->randomMachineName(),
      'status' => 1,
      'moderation_state' => 'published',
      'field_content_paragraphs' => $paragraphs,
    ]);
    foreach ($paragraphs as $paragraph) {
      $this->markEntityForCleanup($paragraph);
    }

    // A consent cookie that covers every service keeps the cookie banner
    // hidden, so it does not block the popover.
    $this->drupalGet('<front>');
    $this->getSession()->setCookie('cookiesjsr', $acceptCookies ? (string) json_encode(['functional' => TRUE, 'video' => FALSE]) : NULL);

    $this->drupalGet($node->toUrl());
    $popoverIds = [];
    foreach ($this->getSession()->getPage()->findAll('css', 'dialog.modal-trigger__popover') as $popover) {
      $popoverId = $popover->getAttribute('id');
      $this->assertIsString($popoverId);
      $popoverIds[] = $popoverId;
    }
    $this->assertCount(count($blockContents), $popoverIds, 'The page contains one popover per block_content.');

    return $popoverIds;
  }

  /**
   * Reloads the page and checks that the popover does not open.
   */
  private function assertPopoverStaysClosedAfterReload(): void {
    $this->getSession()->reload();
    $popover = $this->assertSession()->elementExists('css', 'dialog.modal-trigger__popover');
    $popoverId = $popover->getAttribute('id');
    $this->assertIsString($popoverId);
    $this->getSession()->wait(2000);
    $this->assertFalse($this->isPopoverOpen($popoverId), 'The popover stays closed after a reload.');
  }

  /**
   * Returns the end of the stored dismissal of a block_content, if any.
   *
   * @return int|null
   *   Epoch milliseconds until the popover may open again.
   */
  private function getDismissal(BlockContentInterface $blockContent): ?int {
    $until = $this->getSession()->evaluateScript(sprintf("JSON.parse(window.localStorage.getItem('oc-modal-trigger:%s'))?.until ?? null", $blockContent->uuid()));
    return is_numeric($until) ? (int) $until : NULL;
  }

  /**
   * Waits until a dismissal of a block_content is stored.
   *
   * The toggle event, which writes the dismissal, fires asynchronously after
   * the popover closes.
   *
   * @return int|null
   *   Epoch milliseconds until the popover may open again.
   */
  private function waitForDismissal(BlockContentInterface $blockContent): ?int {
    $this->getSession()->wait(5000, sprintf("window.localStorage.getItem('oc-modal-trigger:%s') !== null", $blockContent->uuid()));
    return $this->getDismissal($blockContent);
  }

  /**
   * Whether the popover is open.
   */
  private function isPopoverOpen(string $popoverId): bool {
    return (bool) $this->getSession()->evaluateScript(sprintf("document.getElementById('%s').matches(':popover-open')", $popoverId));
  }

  /**
   * Waits until the popover is open or closed.
   */
  private function waitForPopoverState(string $popoverId, bool $open, int $timeoutMilliseconds = 5000): bool {
    $condition = sprintf("document.getElementById('%s').matches(':popover-open') === %s", $popoverId, $open ? 'true' : 'false');
    return $this->getSession()->wait($timeoutMilliseconds, $condition);
  }

  /**
   * Presses Escape with a real key event.
   *
   * Mink's keyPress() dispatches a synthetic event, which the browser does
   * not use to close popovers or dialogs.
   */
  private function pressEscape(): void {
    $driver = $this->getSession()->getDriver();
    $this->assertInstanceOf(Selenium2Driver::class, $driver);
    $escape = "\u{E00C}";
    $driver->getWebDriverSession()->postActions([
      'actions' => [
        [
          'type' => 'key',
          'id' => 'keyboard',
          'actions' => [
            ['type' => 'keyDown', 'value' => $escape],
            ['type' => 'keyUp', 'value' => $escape],
          ],
        ],
      ],
    ]);
    $driver->getWebDriverSession()->deleteActions();
  }

}
