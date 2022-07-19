<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\extra_field\Plugin\ExtraFieldDisplayFormattedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function render;

/**
 * Social media buttons
 *
 * @ExtraFieldDisplay(
 *   id = "social_media_buttons",
 *   label = @Translation("Social media buttons"),
 *   visible = FALSE,
 *   bundles = {
 *     "node.*",
 *     "taxonomy_term.*",
 *   }
 * )
 */
class SocialMediaButtons extends ExtraFieldDisplayFormattedBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity): array {
    $build = [];
    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = \Drupal::service('token');
    $url = $token_service->replacePlain('https://www.linkedin.com/shareArticle?mini=true&url=[current-page:url]&title=[current-page:title]&source=[current-page:url]', ['site'], ['clear' => TRUE]);
    $build['sharelinks'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'sharelinks',
        ],
      ],
    ];
    $build['sharelinks']['title'] = [
      '#markup' => '<span class="sharelinks-title">' . t('Share') . '</span>',
    ];
    $build['sharelinks']['linkedin'] = [
      '#type' => 'link',
      '#title' => 'LinkedIn',
      '#url' =>  Url::fromUri($url),
      '#attributes' => ['class' => 'sharelinks-link button', 'data-provider' => 'linkedin']
    ];
    $url = $token_service->replacePlain('https://www.facebook.com/share.php?u=[current-page:url]&title=[current-page:title]', ['site'], ['clear' => TRUE]);
    $build['sharelinks']['facebook'] = [
      '#type' => 'link',
      '#title' => 'Facebook',
      '#url' =>  Url::fromUri($url),
      '#attributes' => ['class' => 'sharelinks-link button', 'data-provider' => 'facebook']
    ];
    $url = $token_service->replacePlain('https://twitter.com/intent/tweet?text=[current-page:title]&url=[current-page:url]&status=[current-page:title]+[current-page:url]', ['site'], ['clear' => TRUE]);
    $build['sharelinks']['twitter'] = [
      '#type' => 'link',
      '#title' => 'Twitter',
      '#url' =>  Url::fromUri($url),
      '#attributes' => ['class' => 'sharelinks-link button', 'data-provider' => 'twitter']
    ];
    return ['#markup' => $this->renderer->render($build)];
  }

}
