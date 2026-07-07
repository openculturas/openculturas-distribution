<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\EventSubscriber;

use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ImportEvent;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\pathauto\PathautoItem;
use Drupal\pathauto\PathautoState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function class_exists;

/**
 * Generates Pathauto aliases for translations imported via default content.
 */
final class OpenculturasCustomDefaultContentImportSubscriber implements EventSubscriberInterface {

  public function __construct(protected PathautoGeneratorInterface $pathautoGenerator) {
  }

  /**
   * Generates missing Pathauto aliases for imported translations.
   *
   * The default_content module saves each imported entity exactly once, in
   * its default language. Pathauto's own entity insert/update hooks act on
   * that single saved entity object only, so they never run for any other
   * translation. As a result, translations with automatic alias generation
   * enabled never get an alias during import. Generate them here once,
   * immediately after import, the same way Pathauto would have done it had
   * every translation been saved individually.
   *
   * @param \Drupal\default_content\Event\ImportEvent $event
   *   The import event.
   */
  public function onImport(ImportEvent $event): void {
    foreach ($event->getImportedEntities() as $entity) {
      if (!$entity->hasField('path')) {
        continue;
      }

      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $translation = $entity->getTranslation($langcode);
        $path_item = $translation->get('path')->first();
        if ($translation->isDefaultTranslation()) {
          continue;
        }

        if (!$path_item instanceof PathautoItem) {
          continue;
        }

        if ((int) $path_item->get('pathauto')->getValue() !== PathautoState::CREATE) {
          continue;
        }

        $this->pathautoGenerator->updateEntityAlias($translation, 'insert');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!class_exists(DefaultContentEvents::class)) {
      return [];
    }

    return [
      DefaultContentEvents::IMPORT => ['onImport'],
    ];
  }

}
