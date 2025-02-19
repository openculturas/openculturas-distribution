<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Wizard;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Ajax\OpenModalWizardCommand;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\node\NodeInterface;
use Drupal\openculturas_openstreetmap\Form\OSM\FindLocation;
use Drupal\openculturas_openstreetmap\Form\OSM\PushData;
use Drupal\openculturas_openstreetmap\Form\OSM\ReviewData;
use Drupal\openculturas_openstreetmap\Form\OSM\SelectDataToCopy;
use Drupal\openculturas_openstreetmap\Form\OSM\SyncOperation;
use Drupal\openculturas_openstreetmap\OpenStreetMap\DrupalToOpenStreetMapTransformer;
use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_merge;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function strpos;
use function substr;

final class OSMFormWizard extends FormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getMachineName(): string {
    return $this->machine_name . $this->getRequest()->get('node_id');
  }

  protected function customizeForm(array $form, FormStateInterface $form_state): array {
    $form['trail'] = [
      '#theme' => ['ctools_wizard_trail'],
      '#wizard' => $this,
      '#cached_values' => $form_state->getTemporaryValue('wizard'),
    ];
    $form['#attached']['library'][] = 'openculturas_openstreetmap/finder';
    $form['#attached']['library'][] = 'openculturas_openstreetmap/wizard';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    if ($form_state->get('ajax')) {
      if ($this->step === 'findLocation') {
        $form['find']['#ajax'] = $form['actions']['submit']['#ajax'];
      }

      $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values): array {
    $operations['findLocation'] = [
      'title' => $this->t('Find'),
      'form' => FindLocation::class,
    ];
    $operations['selectData'] = [
      'title' => $this->t('Select'),
      'form' => SelectDataToCopy::class,
    ];
    $operations['reviewData'] = [
      'title' => $this->t('Review'),
      'form' => ReviewData::class,
    ];
    // Without node we have no data to push.
    if (is_array($cached_values) && ($cached_values['node_id'] ?? NULL) && (isset($cached_values['data']) && is_array($cached_values['data'])) && SyncOperation::canPush()) {
      $data_to_push = array_filter($cached_values['data'], static fn(array $value): bool => SyncOperation::tryFrom($value['operation']) === SyncOperation::Push);
      if ($data_to_push !== []) {
        $operations['pushData'] = [
          'title' => $this->t('Save'),
          'form' => PushData::class,
        ];
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state): AjaxResponse {
    $triggered_element = $form_state->getTriggeringElement();
    $button_name = $triggered_element['#name'] ?? NULL;
    if ($button_name === FindLocation::BUTTON_NAME || $form_state::hasAnyErrors()) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $form['#sorted'] = FALSE;
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
    }
    else {
      $response = $this->successfulAjaxSubmit($form_state);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxFinish(array $form, FormStateInterface $form_state): AjaxResponse {
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    if (isset($cached_values['data']) && is_array($cached_values['data']) && $cached_values['data'] !== []) {
      $data_to_pull = array_filter($cached_values['data'], static fn(array $value): bool => SyncOperation::tryFrom($value['operation']) === SyncOperation::Pull);
      $data_to_push = array_filter($cached_values['data'], static fn(array $value): bool => SyncOperation::tryFrom($value['operation']) === SyncOperation::Push);
      if (isset($cached_values['locations']) && is_string($cached_values['locations'])) {
        $response->addCommand(new InvokeCommand('[data-osm-id-value=""]', 'val', [$cached_values['locations']]));
      }
      else {
        // Should not happen, but in case we do nothing.
        return $response;
      }

      if ($data_to_pull !== []) {
        $response->addCommand(new InvokeCommand('[data-osm-rebuild-list]', 'val', [implode(',', array_keys($data_to_pull))]));
        $response->addCommand(new InvokeCommand('[data-osm-rebuild-form]', 'trigger', ['mousedown']));
      }

      if ($data_to_push !== []) {
        $node_id = $cached_values['node_id'];
        $node = \Drupal::entityTypeManager()->getStorage('node')->load((int) $node_id);
        if (!$node instanceof NodeInterface) {
          return $response;
        }

        /** @var \Drupal\openculturas_openstreetmap\OpenStreetMap\ApiClient $apiClient */
        $apiClient = \Drupal::service('openculturas_openstreetmap.api_client');
        $osm_id = substr($cached_values['locations'], 1);
        $osm_element = $apiClient->getElement('node', $osm_id);
        if ($osm_element) {
          $tags_to_push_list = [];
          foreach (array_keys($data_to_push) as $field_name) {
            $tags_to_push_list[] = DrupalToOpenStreetMapTransformer::fieldNameToTags($field_name);
          }

          $tags_to_push_list = array_filter(array_merge(...$tags_to_push_list));
          if ($tags_to_push_list !== []) {
            $existing_tags = (array) ($osm_element->{'tags'} ?? []);
            $new_tags = DrupalToOpenStreetMapTransformer::transformMultiple($node, $tags_to_push_list);
            $new_tags_deleted = array_diff($tags_to_push_list, array_keys($new_tags));
            $new_tags_deleted = array_fill_keys($new_tags_deleted, '');
            $new_tags = array_merge($new_tags, $new_tags_deleted);
            $groups_to_strip = ['contact'];
            foreach (array_keys($existing_tags) as $osm_tag) {
              if (strpos($osm_tag, ':')) {
                $group = explode(':', $osm_tag)[0];
                if (in_array($group, $groups_to_strip)) {
                  $osm_tag_without_group = explode(':', $osm_tag)[1];
                  if (isset($new_tags[$osm_tag_without_group])) {
                    $new_tags[$osm_tag] = $new_tags[$osm_tag_without_group];
                    unset($new_tags[$osm_tag_without_group]);
                  }
                }
              }
            }

            $merged_tags = array_filter(array_merge($existing_tags, $new_tags));
            $current_version = (string) $osm_element->version;
            $lat = (string) $osm_element->lat;
            $lon = (string) $osm_element->lon;
            $comment = $cached_values['changeset_comment'] ?? '';
            $config = \Drupal::config('openculturas_openstreetmap.settings');
            $token = \Drupal::token();
            if (is_string($config->get('changeset_footer'))) {
              $comment .= PHP_EOL . $token->replace($config->get('changeset_footer'), ['node' => $node]);
            }

            $comment = trim($comment) !== '' ? $comment : NULL;
            $changeSetID = $apiClient->createChangeSet(comment: $comment);
            if ($changeSetID) {
              /** @var \Psr\Log\LoggerInterface $logger */
              $logger = \Drupal::service('logger.channel.openculturas_openstreetmap');
              $updated = $apiClient->updateElement('node', $osm_id, $changeSetID, $current_version, $merged_tags, $lat, $lon);
              if ($updated) {
                $message = sprintf('Update element ID: %s, Changeset-ID: %s, changed tags: %s ', $osm_id, $changeSetID, implode(',', array_keys($new_tags)));
                $logger->info($message);
              }

              $apiClient->closeChangeSet($changeSetID);
            }
          }
        }
      }
    }

    return $response;
  }

  protected function successfulAjaxSubmit(FormStateInterface $form_state): AjaxResponse {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $response = new AjaxResponse();
    $parameters = $this->getNextParameters($cached_values);
    $response->addCommand(new OpenModalWizardCommand($this, $this->getTempstoreId(), $parameters));
    return $response;
  }

}
