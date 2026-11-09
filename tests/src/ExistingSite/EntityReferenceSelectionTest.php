<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\ExistingSite;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Tests\openculturas\ExistingSiteBase;
use Drupal\Tests\openculturas\Traits\LoginTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * The edit form only accepts stored references to allowed targets.
 *
 * For every role and field, content owned by a user with that role references
 * a published target, an unpublished target of another user and an own
 * unpublished target. The edit form is submitted unchanged.
 *
 * A published target must be accepted. An unpublished target must be rejected
 * with "This entity cannot be referenced.", unless the field is meant to
 * reference unpublished targets or the user cannot view the target. For
 * example, an editor without "view any unpublished content" can save content
 * referencing an unpublished node of another user, because core skips the
 * validation of stored references to targets the user cannot view.
 *
 * @see \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator
 */
#[Group('openculturas')]
class EntityReferenceSelectionTest extends ExistingSiteBase {

  use LoginTrait;

  /**
   * The reference fields, as "entity type.bundle.field name".
   */
  private const array FIELDS = [
    'node.article.field_author',
    'node.article.field_references',
    'node.date.field_event_description',
    'node.date.field_location',
    'node.date.field_parent_date',
    'paragraph.bookable_event.field_contact_person',
    'paragraph.member.field_member',
    'paragraph.teaser_node.field_article',
    'paragraph.teaser_term.field_term',
  ];

  /**
   * The fields meant to reference unpublished targets the user can view.
   *
   * Their views filter with "Published status or admin user".
   *
   * @see \Drupal\node\Plugin\views\filter\Status
   */
  private const array FIELDS_WITH_UNPUBLISHED_TARGETS = [
    'node.article.field_author',
    'node.article.field_references',
    'node.date.field_event_description',
    'node.date.field_location',
    'node.date.field_parent_date',
    'paragraph.bookable_event.field_contact_person',
    'paragraph.member.field_member',
    'paragraph.teaser_node.field_article',
  ];

  /**
   * Path of a static file the edit forms redirect to after saving.
   *
   * Skips rendering the saved content. The event form drops it.
   *
   * @see \Drupal\openculturas_custom\Hook\OpenculturasCustomDateEventHooks::formNodeFormAlter()
   */
  private const string DESTINATION = '/robots.txt';

  /**
   * Submits the edit form of content referencing each target as each role.
   *
   * Loops over roles first, so every role logs in only once.
   */
  public function testEditFormsValidateStoredReferences(): void {
    $failures = [];
    $editing_role_ids = array_fill_keys(self::FIELDS, []);
    foreach ($this->getRoleIds() as $role_id) {
      $account = $this->createActiveUser($role_id);
      foreach (self::FIELDS as $field) {
        $field_failures = $this->getEditFormFailures($field, $role_id, $account);
        if ($field_failures === NULL) {
          continue;
        }

        $editing_role_ids[$field][$role_id] = $role_id;
        array_push($failures, ...$field_failures);
      }
    }

    foreach ($editing_role_ids as $field => $role_ids) {
      if ($role_ids === []) {
        $failures[] = sprintf('No role can edit the content holding %s.', $field);
      }
    }

    $this->assertSame([], $failures, implode("\n", $failures));
  }

  /**
   * Submits the edit form of content referencing each target of the field.
   *
   * @return list<string>|null
   *   The failures, or NULL if the role cannot edit the content at all.
   */
  private function getEditFormFailures(string $field, string $role_id, UserInterface $account): ?array {
    $failures = [];
    foreach ($this->createTargets($field, $account) as $target_label => $target) {
      $host = $this->createHost($field, $account, $target);
      if (!$host->access('update', $account)) {
        return NULL;
      }

      if (!$this->drupalUserIsLoggedIn($account)) {
        $this->drupalLogin($account);
      }

      $error_message = $this->submitUnchangedEditForm($host);
      $failure_prefix = sprintf('Role %s, %s %s via %s', $role_id, $target_label, $target->id(), $field);
      $failure = $this->getFailure($failure_prefix, $error_message, $field, $target, $account);
      if ($failure !== NULL) {
        $failures[] = $failure;
      }
    }

    return $failures;
  }

  /**
   * Submits the edit form of the host unchanged.
   *
   * @return string|null
   *   NULL if the content was saved, otherwise the error messages.
   */
  private function submitUnchangedEditForm(NodeInterface $host): ?string {
    $edit_form_path = $host->toUrl('edit-form')->toString();
    $this->drupalGet($host->toUrl('edit-form', ['query' => ['destination' => self::DESTINATION]]));
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Save');
    // A validation error renders the edit form again, a save redirects.
    $path = (string) parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
    if ($path !== $edit_form_path) {
      return NULL;
    }

    $elements = $this->getSession()->getPage()->findAll('css', '[role="alert"], .messages--error');
    return implode(' ', array_map(static fn (NodeElement $element): string => trim($element->getText()), $elements));
  }

  /**
   * A new, unsaved location is a valid reference for field_location.
   *
   * Reproduces the regression of the Inline Entity Form "add new location"
   * flow: the field's selection handler must implement
   * SelectionWithAutocreateInterface for ValidReferenceConstraintValidator to
   * accept an unsaved target.
   *
   * @see \Drupal\openculturas_custom\Plugin\EntityReferenceSelection\ViewsAutocreateSelection
   */
  public function testNewLocationIsReferenceable(): void {
    $account = $this->createUserWithPermissions(['create location content']);
    $location = Node::create(['type' => 'location', 'title' => 'New location']);
    $this->assertSame([], $this->getNewLocationViolationMessages($location, $account));
  }

  /**
   * A new entity of a bundle outside "target_bundles" is rejected.
   *
   * @see \Drupal\openculturas_custom\Plugin\EntityReferenceSelection\ViewsAutocreateSelection::validateReferenceableNewEntities()
   */
  public function testNewNonLocationIsRejected(): void {
    $account = $this->createUserWithPermissions(['create location content', 'create event content']);
    $event = Node::create(['type' => 'event', 'title' => 'Not a location']);
    $this->assertSame(
      ['This entity (node: Not a location) cannot be referenced.'],
      $this->getNewLocationViolationMessages($event, $account),
    );
  }

  /**
   * A new location is rejected if the user may not create locations.
   *
   * @see \Drupal\openculturas_custom\Plugin\EntityReferenceSelection\ViewsAutocreateSelection::validateReferenceableNewEntities()
   */
  public function testNewLocationWithoutCreateAccessIsRejected(): void {
    $location = Node::create(['type' => 'location', 'title' => 'Forbidden location']);
    $this->assertSame(
      ['This entity (node: Forbidden location) cannot be referenced.'],
      $this->getNewLocationViolationMessages($location, new AnonymousUserSession()),
    );
  }

  /**
   * Creates a user with the given permissions.
   *
   * @param list<string> $permissions
   *   The permissions.
   */
  private function createUserWithPermissions(array $permissions): UserInterface {
    $account = $this->createUser($permissions);
    $this->assertInstanceOf(UserInterface::class, $account);
    return $account;
  }

  /**
   * Validates field_location of an unsaved date referencing the new entity.
   *
   * @return list<string>
   *   The violation messages, stripped of markup.
   */
  private function getNewLocationViolationMessages(ContentEntityInterface $location, AccountInterface $account): array {
    $date = Node::create(['type' => 'date', 'title' => 'Date with new location', 'field_location' => $location]);
    $account_switcher = $this->container->get('account_switcher');
    $account_switcher->switchTo($account);
    try {
      return $this->getViolationMessages($date->get('field_location')->validate());
    }
    finally {
      $account_switcher->switchBack();
    }
  }

  /**
   * Returns the violation messages, stripped of markup.
   *
   * @return list<string>
   *   The messages.
   */
  private function getViolationMessages(iterable $violations): array {
    return array_values(array_map(
      static fn ($violation): string => strip_tags((string) $violation->getMessage()),
      iterator_to_array($violations),
    ));
  }

  /**
   * Returns the failure for the result of the form submission, if any.
   *
   * @param string $failure_prefix
   *   Describes the role, target and field.
   * @param string|null $error_message
   *   NULL if the content was saved, otherwise the error messages.
   * @param string $field
   *   The reference field.
   * @param \Drupal\Core\Entity\ContentEntityInterface $target
   *   The referenced target.
   * @param \Drupal\user\UserInterface $account
   *   The user who submitted the form.
   */
  private function getFailure(string $failure_prefix, ?string $error_message, string $field, ContentEntityInterface $target, UserInterface $account): ?string {
    if ($this->isAccepted($field, $target, $account)) {
      return $error_message === NULL ? NULL : sprintf('%s: %s', $failure_prefix, $error_message ?: 'content was not saved');
    }

    if ($error_message === NULL) {
      return sprintf('%s: content was saved, the reference must be rejected', $failure_prefix);
    }

    if (!str_contains($error_message, sprintf('(%s: %s) cannot be referenced', $target->getEntityTypeId(), $target->id()))) {
      return sprintf('%s: the reference must be rejected, got %s', $failure_prefix, $error_message ?: 'no error message');
    }

    return NULL;
  }

  /**
   * Whether the edit form must accept the stored reference to the target.
   *
   * @see \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator::validate()
   */
  private function isAccepted(string $field, ContentEntityInterface $target, UserInterface $account): bool {
    if (!$target instanceof EntityPublishedInterface || $target->isPublished()) {
      return TRUE;
    }

    if (in_array($field, self::FIELDS_WITH_UNPUBLISHED_TARGETS, TRUE)) {
      return TRUE;
    }

    // Core skips the validation of a stored reference to a target the user
    // cannot view, so the user can still save content referencing it.
    return !$target->access('view', $account);
  }

  /**
   * Returns every role that can be assigned, "authenticated" for none.
   *
   * @return list<string>
   *   Role IDs.
   */
  private function getRoleIds(): array {
    $role_ids = array_map(strval(...), array_keys(Role::loadMultiple()));
    return array_values(array_diff($role_ids, [RoleInterface::ANONYMOUS_ID]));
  }

  /**
   * Creates an active user with the given role.
   */
  private function createActiveUser(string $role_id): UserInterface {
    $roles = $role_id === RoleInterface::AUTHENTICATED_ID ? [] : [$role_id];
    $account = $this->createUser([], NULL, FALSE, ['roles' => $roles, 'status' => 1]);
    $this->assertInstanceOf(UserInterface::class, $account);
    return $account;
  }

  /**
   * Creates the targets to reference, keyed by a description.
   *
   * @return array<string, \Drupal\Core\Entity\ContentEntityInterface>
   *   The targets.
   */
  private function createTargets(string $field, UserInterface $account): array {
    if ($field === 'paragraph.teaser_term.field_term') {
      $vocabulary = Vocabulary::load('category');
      $this->assertNotNull($vocabulary);
      return [
        'published term' => $this->createTerm($vocabulary, ['name' => 'Published category', 'status' => 1]),
        'unpublished term' => $this->createTerm($vocabulary, ['name' => 'Unpublished category', 'status' => 0]),
      ];
    }

    $bundle = match ($field) {
      'node.article.field_references', 'node.date.field_event_description' => 'event',
      'node.date.field_location' => 'location',
      'node.date.field_parent_date' => 'date',
      'paragraph.teaser_node.field_article' => 'page',
      default => 'profile',
    };
    return [
      'published node' => $this->createTargetNode($bundle, NodeInterface::PUBLISHED, 1),
      'unpublished node of another user' => $this->createTargetNode($bundle, NodeInterface::NOT_PUBLISHED, 1),
      'own unpublished node' => $this->createTargetNode($bundle, NodeInterface::NOT_PUBLISHED, (int) $account->id()),
    ];
  }

  /**
   * Creates a node of the given bundle with its required fields filled.
   */
  private function createTargetNode(string $bundle, int $status, int $owner_id, array $values = []): NodeInterface {
    $values += [
      'type' => $bundle,
      'title' => sprintf('%s %s', $status === NodeInterface::PUBLISHED ? 'Published' : 'Unpublished', $bundle),
      'status' => $status,
      'uid' => $owner_id,
    ];
    if ($bundle === 'date') {
      $values += [
        'field_attendance_mode' => 'OnlineEventAttendanceMode',
        'field_date' => ['value' => strtotime('+1 week'), 'end_value' => strtotime('+1 week +2 hours'), 'duration' => 120, 'timezone' => ''],
        'field_event_description' => $this->createTargetNode('event', NodeInterface::PUBLISHED, 1),
        'field_organizer' => $this->createTargetNode('profile', NodeInterface::PUBLISHED, 1, ['field_profile_role' => 'organizer']),
      ];
    }
    elseif ($bundle === 'profile') {
      $values += [
        'field_profile_role' => 'organizer',
        'field_profile_type' => 'p',
      ];
    }
    elseif ($bundle === 'location') {
      $values += [
        'field_address_data' => ['country_code' => 'DE', 'locality' => 'Göttingen', 'postal_code' => '37073', 'address_line1' => 'Markt 1'],
      ];
    }

    return $this->createNode($values);
  }

  /**
   * {@inheritdoc}
   *
   * Leaves the body empty. The trait fills it with the default format of the
   * current user, which may not be allowed for the bundle, so the format
   * select of the edit form ends up without a value.
   */
  #[\Override]
  protected function createNode(array $settings = []): NodeInterface {
    return parent::createNode($settings + ['body' => []]);
  }

  /**
   * Creates content owned by the account that references the target.
   *
   * The content is saved without validation, like content that was stored
   * before a selection handler changed.
   */
  private function createHost(string $field, UserInterface $account, ContentEntityInterface $target): NodeInterface {
    $owner_id = (int) $account->id();
    return match ($field) {
      'node.article.field_author' => $this->createNode([
        'type' => 'article',
        'title' => 'Article with author',
        'uid' => $owner_id,
        'field_date_published' => gmdate('Y-m-d\TH:i:s'),
        'field_author' => $target,
      ]),
      'node.article.field_references' => $this->createNode([
        'type' => 'article',
        'title' => 'Article with mentioned content',
        'uid' => $owner_id,
        'field_date_published' => gmdate('Y-m-d\TH:i:s'),
        'field_references' => $target,
      ]),
      'node.date.field_event_description' => $this->createTargetNode('date', NodeInterface::PUBLISHED, $owner_id, [
        'field_event_description' => $target,
      ]),
      'node.date.field_location' => $this->createTargetNode('date', NodeInterface::PUBLISHED, $owner_id, [
        'field_attendance_mode' => 'OfflineEventAttendanceMode',
        'field_location' => $target,
      ]),
      'node.date.field_parent_date' => $this->createTargetNode('date', NodeInterface::PUBLISHED, $owner_id, [
        'field_event_series' => 'is_sub_event_of',
        'field_parent_date' => $target,
      ]),
      'paragraph.bookable_event.field_contact_person' => $this->createNode([
        'type' => 'event',
        'title' => 'Event with contact person',
        'uid' => $owner_id,
        'field_bookable_info' => Paragraph::create(['type' => 'bookable_event', 'field_contact_person' => $target]),
      ]),
      'paragraph.member.field_member' => $this->createNode([
        'type' => 'event',
        'title' => 'Event with member',
        'uid' => $owner_id,
        'field_people_reference' => Paragraph::create(['type' => 'member', 'field_member' => $target]),
      ]),
      'paragraph.teaser_node.field_article' => $this->createNode([
        'type' => 'page',
        'title' => 'Page with node teaser',
        'uid' => $owner_id,
        'field_content_paragraphs' => Paragraph::create([
          'type' => 'teaser_wrapper',
          'field_teasers' => Paragraph::create(['type' => 'teaser_node', 'field_article' => $target]),
        ]),
      ]),
      'paragraph.teaser_term.field_term' => $this->createNode([
        'type' => 'page',
        'title' => 'Page with term teaser',
        'uid' => $owner_id,
        'field_content_paragraphs' => Paragraph::create([
          'type' => 'teaser_wrapper',
          'field_teasers' => Paragraph::create(['type' => 'teaser_term', 'field_term' => $target]),
        ]),
      ]),
      default => throw new \InvalidArgumentException(sprintf('No host content defined for %s.', $field)),
    };
  }

}
