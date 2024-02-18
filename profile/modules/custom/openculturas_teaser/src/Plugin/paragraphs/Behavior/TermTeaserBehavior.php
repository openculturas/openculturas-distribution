<?php

declare(strict_types=1);

namespace Drupal\openculturas_teaser\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\taxonomy\TermInterface;

/**
 *
 * @ParagraphsBehavior(
 *   id = "term_teaser",
 *   label = @Translation("Term teaser."),
 *   description = @Translation("Allow overriding term's teaser values."),
 *   weight = 2
 * )
 */
class TermTeaserBehavior extends TeaserBehaviorBase {

  /**
   * {@inheritdoc}
   */
  protected string $descriptionField = 'description';

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $formState): array {
    parent::buildBehaviorForm($paragraph, $form, $formState);
    $referenceItems = $paragraph->get('field_term')->referencedEntities();
    $entity = reset($referenceItems);
    if ($entity instanceof TermInterface && !$entity->hasField('field_subtitle')) {
      unset($form['subtitle']);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $entityViewDisplay, $view_mode): void {
    $settings = $paragraph->getAllBehaviorSettings()[$this->getPluginId()];
    $buildTerm = &$build['field_term'][0];
    $this->cacheTags = $build['#cache']['tags'];

    /** @var \Drupal\taxonomy\Entity\Term|NULL $term */
    $term = &$buildTerm['#taxonomy_term'];
    if ($term instanceof TermInterface) {
      $buildTerm = $this->getBaseBuildArray($buildTerm, $settings, '#taxonomy_term');
      $buildTerm['#attributes'] = new Attribute([
        'class' => [
          'teaser-internal',
          'teaser-term',
        ],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsTypeInterface $paragraphsType): bool {
    $fieldManager = \Drupal::service('entity_field.manager');
    $fd = $fieldManager->getFieldDefinitions('paragraph', (string) $paragraphsType->id());
    $ef = $fieldManager->getBaseFieldDefinitions('paragraph');
    $fieldKeys = array_diff(array_keys($fd), array_keys($ef));
    foreach ($fieldKeys as $fieldKey) {
      $fieldDefinition = $fd[$fieldKey];
      if ($fieldDefinition->getType() == 'entity_reference') {
        $handler = $fieldDefinition->getSetting('handler');
        if ($handler == 'default:taxonomy_term') {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
