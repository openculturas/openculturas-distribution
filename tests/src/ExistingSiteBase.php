<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas;

use Drupal\user\Entity\Role;
use weitzman\DrupalTestTraits\ExistingSiteBase as OriginExistingSiteBase;
use weitzman\DrupalTestTraits\LoggerTrait;
use function array_map;

abstract class ExistingSiteBase extends OriginExistingSiteBase {

  use LoggerTrait;

  /**
   * Can be used for link generating or drupalGet.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language_en;

  /**
   * @var string[]|null
   */
  protected $legal_except_roles;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();

    $this->language_en = $this->container->get('language_manager')->getLanguage('en');
//    $config_factory = $this->container->get('config.factory');
//    $config = $config_factory->getEditable('legal.settings');
//    $this->legal_except_roles = $config->get('except_roles');
//    $roles =  array_map(function(Role $role) {
//      return $role->id();
//    }, Role::loadMultiple()
//    );
//    $config->set('except_roles', $roles);
//   // $config->save();
  }

//  /**
//   * {@inheritdoc}
//   */
//  public function tearDown() {
//    $config_factory = $this->container->get('config.factory');
//    $config = $config_factory->getEditable('legal.settings');
//    $config
//      ->set('except_roles', $this->legal_except_roles)
//      ->save();
//    parent::tearDown();
//  }
}
