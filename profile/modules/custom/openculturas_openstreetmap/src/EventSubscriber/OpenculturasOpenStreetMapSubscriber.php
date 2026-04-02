<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardInterface;
use Drupal\node\NodeInterface;
use Drupal\openculturas_openstreetmap\AddressLookup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use function is_numeric;

/**
 * OpenCulturas Openstreetmap event subscriber.
 */
final class OpenculturasOpenStreetMapSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly RequestStack $request_stack,
    private readonly EntityTypeManagerInterface $entity_type_manager,
    private readonly AddressLookup $addressLookup,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!interface_exists(FormWizardInterface::class)) {
      return [];
    }

    return [
      FormWizardInterface::LOAD_VALUES => ['onWizardLoadValues'],
    ];
  }

  /**
   * Prepares the wizard with values when the user open it the first time.
   */
  public function onWizardLoadValues(WizardEvent $wizardEvent): void {
    $node_id = $this->request_stack->getMainRequest()?->get('node_id');
    if (is_numeric($node_id)) {
      $node = NULL;
      /** @var \Drupal\node\NodeInterface|null $node */
      try {
        $node = $this->entity_type_manager->getStorage('node')->load((int) $node_id);
      }
      catch (\Throwable) {
      }

      if ($node instanceof NodeInterface) {
        $values = $wizardEvent->getValues();
        $values['node_id'] = $node_id;
        if (!$node->get('field_osm_id')->isEmpty()) {
          $osm_id = (string) $node->get('field_osm_id')->first()?->getString();
          if ($osm_id !== '') {
            [1 => $location_name] = $this->addressLookup->lookup($osm_id);
            if ($location_name) {
              $values['location_name'] = $location_name;
              $values['locations'] = $osm_id;
            }
          }
        }

        $wizardEvent->setValues($values);
      }
    }

  }

}
