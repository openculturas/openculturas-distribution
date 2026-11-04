<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\Tests\openculturas\Traits\ModalTriggerTrait;
use Drupal\layout_builder\SectionComponent;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Renders modal-trigger blocks in the test process.
 *
 * Html::resetSeenIds() between two renders marks where a new request starts.
 */
#[Group('openculturas')]
class ModalTriggerRenderTest extends ExistingSiteBase {

  use ModalTriggerTrait;

  /**
   * A placement title must not show in the popover of another reference.
   */
  public function testPlacementTitleStaysWithItsPlacement(): void {
    $blockContent = $this->createModalTriggerBlockContent();
    $title = 'Placement title ' . $this->randomMachineName();
    $block = $this->placeModalTriggerBlock($blockContent, $title, TRUE);
    $paragraph = $this->createBlockEditorialParagraph($blockContent, 'Paragraph label', FALSE);

    $placementHtml = $this->renderEntity($block);
    $this->assertStringContainsString($title, $placementHtml, 'The placement shows its title in the popover.');

    $paragraphHtml = $this->renderEntity($paragraph);
    $this->assertStringContainsString('modal-trigger__popover', $paragraphHtml);
    $this->assertStringNotContainsString($title, $paragraphHtml, 'A reference without "Display title" shows no title from another placement.');
  }

  /**
   * A title moves into the popover for placements and block_field references.
   */
  public function testDisplayedTitleMovesIntoPopover(): void {
    $blockContent = $this->createModalTriggerBlockContent();
    $title = 'Shown title ' . $this->randomMachineName();
    $block = $this->placeModalTriggerBlock($blockContent, $title, TRUE);
    $paragraph = $this->createBlockEditorialParagraph($blockContent, $title, TRUE);

    foreach (['placement' => $block, 'paragraph' => $paragraph] as $label => $entity) {
      $html = $this->renderEntity($entity);
      $this->assertSame(1, substr_count($html, $title), sprintf('The %s shows its title once.', $label));
      $this->assertMatchesRegularExpression('/<h2 class="dialog-title"[^>]*>' . preg_quote($title, '/') . '<\/h2>/', $html, sprintf('The %s shows its title in the popover.', $label));
    }
  }

  /**
   * A title moves into the popover for Layout Builder content blocks.
   *
   * Layout Builder does not invoke hook_block_view_alter().
   */
  public function testLayoutBuilderTitleMovesIntoPopover(): void {
    $title = 'Layout Builder title ' . $this->randomMachineName();
    foreach ($this->createLayoutBuilderComponents($title) as $label => $component) {
      $html = $this->renderComponentPreview($component);
      $this->assertSame(1, substr_count($html, $title), sprintf('The %s shows its title once.', $label));
      $this->assertMatchesRegularExpression('/<h2 class="dialog-title"[^>]*>' . preg_quote($title, '/') . '<\/h2>/', $html, sprintf('The %s shows its title in the popover.', $label));
    }
  }

  /**
   * The Layout Builder UI shows a content block inline, with its title.
   */
  public function testLayoutBuilderUiShowsBlockInline(): void {
    $title = 'Layout Builder UI title ' . $this->randomMachineName();
    $request = Request::create('/node/1/layout');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'layout_builder.overrides.node.view');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/{node}/layout'));
    $requestStack = $this->container->get('request_stack');
    $requestStack->push($request);
    try {
      foreach ($this->createLayoutBuilderComponents($title) as $label => $component) {
        $html = $this->renderComponentPreview($component);
        $this->assertStringNotContainsString('<dialog', $html, sprintf('The %s has no popover.', $label));
        $this->assertStringNotContainsString('modal-trigger__anchor', $html, sprintf('The %s has no trigger anchor.', $label));
        $this->assertSame(1, substr_count($html, $title), sprintf('The %s shows its title once.', $label));
      }
    }
    finally {
      $requestStack->pop();
    }
  }

  /**
   * A system block in a modal shows the referenced block in a labelled popover.
   */
  public function testSystemBlockModalShowsReferencedBlock(): void {
    $blockContent = $this->createSystemModalBlockContent();
    $title = 'System modal title ' . $this->randomMachineName();
    $paragraph = $this->createBlockEditorialParagraph($blockContent, $title, TRUE);

    // The call-to-action menu links to forms for new content. Anonymous
    // users cannot open them, so the menu and the popover would be empty.
    $currentUser = $this->container->get('current_user');
    $originalAccount = $currentUser->getAccount();
    $administrator = $this->createUser([], NULL, TRUE);
    $this->assertInstanceOf(AccountInterface::class, $administrator);
    $currentUser->setAccount($administrator);
    try {
      $html = $this->renderEntity($paragraph);
    }
    finally {
      $currentUser->setAccount($originalAccount);
    }

    $this->assertMatchesRegularExpression('/<dialog\b[^>]*\bid="([^"]+)"[^>]*\baria-labelledby="\1-title"/', $html, 'The popover is labelled by its title.');
    $this->assertMatchesRegularExpression('/<h2 class="dialog-title" id="[^"]+-title">' . preg_quote($title, '/') . '<\/h2>/', $html, 'The title element has the ID the popover refers to.');
    $popoverContent = substr($html, (int) strpos($html, 'modal-trigger__content'));
    $this->assertMatchesRegularExpression('/<a\b[^>]*href=/', $popoverContent, 'The popover contains the links of the call-to-action menu.');
  }

  /**
   * A popover without a displayed title still has an accessible name.
   */
  public function testPopoverWithoutTitleHasAccessibleName(): void {
    $blockContent = $this->createModalTriggerBlockContent();
    $html = $this->renderEntity($this->placeModalTriggerBlock($blockContent, 'Hidden placement title', FALSE));

    $this->assertMatchesRegularExpression('/<dialog\b[^>]*\baria-label="Notice"/', $html, 'The popover has a generic accessible name.');
    $this->assertStringNotContainsString('aria-labelledby', $html, 'The popover refers to no title element.');
  }

  /**
   * A reference rendered later must not suppress the title of a placement.
   */
  public function testCachedReferenceDoesNotRemovePlacementTitle(): void {
    $blockContent = $this->createModalTriggerBlockContent();
    $paragraph = $this->createBlockEditorialParagraph($blockContent, 'Paragraph label', FALSE);
    $this->renderEntity($paragraph);

    Html::resetSeenIds();
    $title = 'Placement title ' . $this->randomMachineName();
    $block = $this->placeModalTriggerBlock($blockContent, $title, TRUE);
    $this->assertStringContainsString($title, $this->renderEntity($block));
  }

  /**
   * Popover IDs stay unique when placements come from the render cache.
   */
  public function testPopoverIdsStayUniqueAcrossCachedRenders(): void {
    $blockContent = $this->createModalTriggerBlockContent();
    $firstBlock = $this->placeModalTriggerBlock($blockContent, 'First placement', FALSE);
    $secondBlock = $this->placeModalTriggerBlock($blockContent, 'Second placement', FALSE);
    $viewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('block');

    // Two separate requests cache one placement each.
    $this->renderEntity($firstBlock);
    Html::resetSeenIds();
    $this->renderEntity($secondBlock);

    // A third request gets both placements from the render cache.
    Html::resetSeenIds();
    $page = [
      'first' => $viewBuilder->view($firstBlock),
      'second' => $viewBuilder->view($secondBlock),
    ];
    $html = (string) $this->container->get('renderer')->renderInIsolation($page);

    preg_match_all('/<dialog\b[^>]*\bid="([^"]+)"/', $html, $matches);
    $this->assertCount(2, $matches[1], 'The page contains two popovers.');
    $this->assertCount(2, array_unique($matches[1]), 'The popover IDs are unique on the page.');
  }

  /**
   * Only a cache miss of a placement loads its block_content by UUID.
   *
   * Core runs hook_block_view_alter() for a placed block inside its lazy
   * builder, so a render cache hit skips the hook.
   */
  public function testCachedPlacementRunsNoEntityQuery(): void {
    $blockContent = $this->createModalTriggerBlockContent();
    $block = $this->placeModalTriggerBlock($blockContent, 'Placement', TRUE);
    $uuid = $blockContent->uuid();
    $this->assertIsString($uuid);

    $this->assertNotSame([], $this->getUuidQueries($uuid, fn () => $this->renderEntity($block)), 'A cache miss loads the block_content by UUID.');

    Html::resetSeenIds();
    $this->assertSame([], $this->getUuidQueries($uuid, fn () => $this->renderEntity($block)), 'A cache hit does not load the block_content by UUID.');
  }

  /**
   * Runs a callback and returns the logged queries that use a UUID.
   *
   * @return list<string>
   *   The SQL of the matching queries.
   */
  private function getUuidQueries(string $uuid, callable $callback): array {
    $logKey = 'modal_trigger_' . $this->randomMachineName();
    Database::startLog($logKey);
    $callback();
    $queries = Database::getLog($logKey);
    Database::getConnection()->getLogger()?->clear($logKey);

    $uuidQueries = array_filter($queries, static fn (array $query): bool => in_array($uuid, $query['args'], TRUE));
    return array_column($uuidQueries, 'query');
  }

  /**
   * Creates a reusable and an inline content block component.
   *
   * @return array<string, \Drupal\layout_builder\SectionComponent>
   *   The components, keyed by a label for assertion messages.
   */
  private function createLayoutBuilderComponents(string $title): array {
    $blockContent = $this->createModalTriggerBlockContent();
    $inlineBlockContent = $this->container->get('entity_type.manager')->getStorage('block_content')->create([
      'type' => 'basic',
      'info' => 'Inline modal trigger test',
      'reusable' => FALSE,
      'body' => ['value' => '<p>Inline content</p>', 'format' => 'basic_html'],
      'field_modal_trigger_mode' => 'delay',
      'field_modal_trigger_delay' => 0,
    ]);

    return [
      'reusable block' => new SectionComponent($this->container->get('uuid')->generate(), 'content', [
        'id' => 'block_content:' . $blockContent->uuid(),
        'label' => $title,
        'label_display' => 'visible',
        'provider' => 'block_content',
      ]),
      'inline block' => new SectionComponent($this->container->get('uuid')->generate(), 'content', [
        'id' => 'inline_block:basic',
        'label' => $title,
        'label_display' => 'visible',
        'provider' => 'layout_builder',
        'block_serialized' => serialize($inlineBlockContent),
      ]),
    ];
  }

  /**
   * Renders a Layout Builder component in preview mode as HTML.
   */
  private function renderComponentPreview(SectionComponent $component): string {
    // The preview skips the access check, which an inline block without a
    // parent entity would fail.
    $build = $component->toRenderArray([], TRUE);
    $html = (string) $this->container->get('renderer')->renderInIsolation($build);
    // The preview also puts the block label into a placeholder attribute.
    return (string) preg_replace('/ data-layout-content-preview-placeholder-label="[^"]*"/', '', $html);
  }

  /**
   * Renders the default view of an entity as HTML.
   */
  private function renderEntity(EntityInterface $entity): string {
    $build = $this->container->get('entity_type.manager')->getViewBuilder($entity->getEntityTypeId())->view($entity);
    return (string) $this->container->get('renderer')->renderInIsolation($build);
  }

}
